<?php

namespace App\Services;

use App\Repositories\CategoryRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AIService
{
    public function __construct(
        private readonly CategoryRepository $categories,
    ) {}

    /**
     * Transcribe audio via OpenAI speech-to-text. Falls back to stub in non-configured env.
     *
     * @param  list<string>  $locationHints  Used as short vocab for newer STT models (not whisper-1).
     */
    public function transcribe(string $audioPathOrUrl, array $locationHints = []): string
    {
        $localPath = $this->resolveLocalPath($audioPathOrUrl);

        if (! is_file($localPath)) {
            throw new \RuntimeException('Audio file missing');
        }

        $bytes = filesize($localPath);
        // Near-empty clips (silence / accidental tap) — skip Whisper.
        if ($bytes !== false && $bytes < 4000) {
            Log::info('Audio too small to transcribe', ['bytes' => $bytes, 'path' => $localPath]);

            return '';
        }

        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            if (app()->environment('production')) {
                throw new \RuntimeException('Audio transcription is not configured');
            }

            // Local stub: short/silence clips must not invent a full demo request.
            if ($bytes !== false && $bytes < 20000) {
                Log::warning('OpenAI key missing — short audio treated as empty transcript', [
                    'bytes' => $bytes,
                ]);

                return '';
            }

            Log::warning('OpenAI key missing — returning stub transcript');

            return 'Nərimanovda saat 3-də it gəzdirən adam lazımdır 2 saatlıq';
        }

        $model = (string) config('services.openai.transcribe_model', 'gpt-4o-transcribe');
        $payload = [
            'model' => $model,
            'temperature' => 0,
        ];

        $language = config('services.openai.transcribe_language');
        if (is_string($language) && $language !== '') {
            $payload['language'] = $language;
        }

        // whisper-1 often echoed long prompts; gpt-4o-transcribe handles short vocab better.
        if (! str_starts_with($model, 'whisper')) {
            $vocab = $this->transcribeVocabPrompt($locationHints);
            if ($vocab !== '') {
                $payload['prompt'] = $vocab;
            }
        }

        $response = Http::withToken($apiKey)
            ->timeout(90)
            ->attach('file', file_get_contents($localPath), basename($localPath))
            ->post('https://api.openai.com/v1/audio/transcriptions', $payload);

        if (! $response->successful()) {
            // Fallback once to whisper-1 if the newer model is unavailable on the key.
            if ($model !== 'whisper-1') {
                Log::warning('Primary STT failed — retrying whisper-1', [
                    'model' => $model,
                    'body' => $response->body(),
                ]);
                $response = Http::withToken($apiKey)
                    ->timeout(90)
                    ->attach('file', file_get_contents($localPath), basename($localPath))
                    ->post('https://api.openai.com/v1/audio/transcriptions', [
                        'model' => 'whisper-1',
                    ]);
            }
        }

        if (! $response->successful()) {
            Log::error('Transcription failed', ['body' => $response->body()]);

            throw new \RuntimeException('Audio transcription failed');
        }

        return trim((string) $response->json('text'));
    }

    /**
     * Extract structured search criteria from transcript.
     * LLM when OpenAI is configured; otherwise keyword fallback.
     *
     * @param  list<array{slug: string, name_az: string, name_en: ?string}>  $leafCatalog
     * @param  list<string>  $locationHints
     * @return array<string, mixed>
     */
    public function parseRequestText(string $text, array $leafCatalog = [], array $locationHints = []): array
    {
        $leafCatalog = $leafCatalog !== [] ? $leafCatalog : $this->categories->leafCatalog();

        $parsed = $this->extractWithLlm($text, $leafCatalog, $locationHints)
            ?? $this->extractWithKeywords($text);

        // If first pass missed category, try a dedicated ASR-repair call once.
        if (empty($parsed['category_slug']) && config('services.openai.key')) {
            $repaired = $this->repairAsrAndParse($text, $leafCatalog, $locationHints);
            if ($repaired !== null) {
                $parsed = $repaired;
            }
        }

        $normalized = $this->nullIfEmpty($parsed['normalized_text'] ?? null);
        $parsed['raw_text'] = $text;
        if ($normalized) {
            $parsed['normalized_text'] = $normalized;
            $parsed['asr_corrected'] = $normalized !== trim($text);
        }
        $parsed['time_slot'] = $parsed['time_slot']
            ?? $this->slotFromClock($parsed['time_hhmm'] ?? null);

        return $parsed;
    }

    /**
     * Short STT vocabulary — place + service words only (no full sample sentences).
     *
     * @param  list<string>  $locationHints
     */
    private function transcribeVocabPrompt(array $locationHints): string
    {
        $places = array_slice(array_values(array_filter($locationHints)), 0, 40);
        $extraPlaces = [
            'Qara Qarayev', 'Gənclik', '28 May', 'İçərişəhər', 'Elmlər', 'Həzi Aslanov',
            'Nərimanov', 'Nəsimi', 'Nizami', 'Yasamal', 'Xətai', 'Bakı',
        ];
        $services = [
            'uşaq dayəsi', 'dayə', 'körpə dayəsi', 'məktəbli dayəsi',
            'təmizlik', 'it gəzdirmə', 'baxıcı', 'aşpaz', 'repetitor',
            'axtarılır', 'lazımdır', 'saat', 'saatıq',
        ];
        $bits = array_values(array_unique(array_merge($extraPlaces, $places, $services)));

        return 'Azərbaycan / Русский / English home-service request vocabulary: '
            .implode(', ', $bits).'.';
    }

    /**
     * @param  list<array{slug: string, name_az: string, name_en: ?string}>  $leafCatalog
     * @param  list<string>  $locationHints
     * @return array<string, mixed>|null
     */
    private function extractWithLlm(string $text, array $leafCatalog, array $locationHints): ?array
    {
        $apiKey = config('services.openai.key');
        if (! $apiKey) {
            return null;
        }

        $slugs = collect($leafCatalog)->pluck('slug')->all();
        $catalogJson = json_encode($leafCatalog, JSON_UNESCAPED_UNICODE);
        $locationsJson = json_encode($locationHints, JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
You are the request parser for My Sancho, a home-services marketplace in Azerbaijan.

Input is often a noisy ASR transcript (Whisper / gpt-4o-transcribe). It may be AZ, RU, EN, or mixed — with typos, slurred speech, and phonetic nonsense.
Goal: recover what a local human meant, rewrite as clear normalized_text, then fill JSON for search matching.

1) FIRST rewrite as normalized_text (this is what the app shows the user):
   - Keep the user's language when possible (AZ/RU/EN), but FIX ASR errors aggressively.
   - Infer intent from phonetics when words are wrong: "saxdarlıq/şaxta/axtarlıg" → "axtarılır";
     "uşag/uşax/usağ/uşaq" → "uşaq"; "dayasi/dayəsi/daya" → "dayəsi"; "Шахта/Şaxta" near a place often = speech garbage around "axtarılır".
   - Place names MUST map to this official list (phonetic / transliteration OK): {$locationsJson}
     Examples:
     "darimanov/Dərmolov/Dermolov/Нариманов/Narimanov" → district "Nərimanov"
     "Ясамал/Yasamal" → "Yasamal"
     "Гянджлик/Gənclik" → nearest district "Nərimanov"
     "Qaraqarayev/Qara Qarayev/Кара Караев/Гара Гараев" → nearest district "Nizami" (metro area)
   - Duration: "küsadlıq/iki saatlıq/два часа/for two hours/2 hours/2 saatlıq" → duration_hours=2.
   - Service words: dayə/няня/nanny/uşaq dayəsi; it gəzdirmə/выгул собаки/dog walking; təmizlik/уборка/cleaning.
   - Do not invent time/duration that were not implied. Do invent the clearest service sentence if ASR is garbled but intent is clear.
   - Example repair: ASR "Qaraqarayevdə Şaxta üçün saxdarlıq" → normalized_text "Qara Qarayevdə uşaq dayəsi axtarılır", district "Nizami", category_slug nanny/infant-nanny/school-nanny as fits.

2) THEN fill structured fields for DB matching (canonical AZ place names from the list):
Leaf category slugs only: {$catalogJson}

Rules:
- detected_language: "az" | "ru" | "en" | "mixed" | null
- category_slug: slug from list or null
- city, district: short official names from the list (e.g. Bakı, Nizami) or null.
  Neighbourhoods/metro → nearest official district.
- time_hhmm: 24h "HH:MM" (3pm = 15:00) or null
- duration_hours: number or null
- time_slot: morning|afternoon|evening|night or null
  (05–11 morning, 12–16 afternoon, 17–21 evening, 22–04 night)
- normalized_text: corrected full sentence for the UI (never leave ASR gibberish if intent is recoverable)

JSON only:
{"detected_language":"","normalized_text":"","category_slug":"","city":"","district":"","time_hhmm":"","duration_hours":null,"time_slot":""}
PROMPT;

        return $this->chatJsonParse($prompt, $text, $slugs, 'llm');
    }

    /**
     * Second pass focused on repairing gibberish ASR when category was missed.
     *
     * @param  list<array{slug: string, name_az: string, name_en: ?string}>  $leafCatalog
     * @param  list<string>  $locationHints
     * @return array<string, mixed>|null
     */
    private function repairAsrAndParse(string $text, array $leafCatalog, array $locationHints): ?array
    {
        $slugs = collect($leafCatalog)->pluck('slug')->all();
        $catalogJson = json_encode($leafCatalog, JSON_UNESCAPED_UNICODE);
        $locationsJson = json_encode($locationHints, JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
You repair bad ASR for My Sancho (Azerbaijan home services).
The previous parser could not find a category. Reconstruct the most likely home-service request.

Official places: {$locationsJson}
Leaf categories: {$catalogJson}

Neighbourhood hints: Gənclik→Nərimanov; Qara Qarayev→Nizami; 28 May→Nəsimi; İçərişəhər→Səbail.

Return JSON only with corrected normalized_text and best category_slug if any intent is plausible.
If truly impossible to guess a service, keep category_slug null but still clean the sentence.
{"detected_language":"","normalized_text":"","category_slug":"","city":"","district":"","time_hhmm":"","duration_hours":null,"time_slot":""}
PROMPT;

        $result = $this->chatJsonParse($prompt, $text, $slugs, 'llm_repair');
        if ($result === null) {
            return null;
        }
        if (empty($result['category_slug']) && empty($result['normalized_text'])) {
            return null;
        }

        return $result;
    }

    /**
     * @param  list<string>  $slugs
     * @return array<string, mixed>|null
     */
    private function chatJsonParse(string $systemPrompt, string $text, array $slugs, string $parserTag): ?array
    {
        $apiKey = config('services.openai.key');
        if (! $apiKey) {
            return null;
        }

        $model = (string) config('services.openai.parse_model', 'gpt-4o');

        try {
            $response = Http::withToken($apiKey)
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'temperature' => 0.1,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        [
                            'role' => 'user',
                            'content' => "ASR transcript (may be AZ/RU/EN, may have errors):\n".$text,
                        ],
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning('LLM parse request failed', ['error' => $e->getMessage(), 'parser' => $parserTag]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('LLM parse HTTP failed', [
                'body' => $response->body(),
                'parser' => $parserTag,
                'model' => $model,
            ]);

            return null;
        }

        $content = $response->json('choices.0.message.content');
        $data = is_string($content) ? json_decode($content, true) : null;
        if (! is_array($data)) {
            return null;
        }

        $slug = $data['category_slug'] ?? null;
        if ($slug && ! in_array($slug, $slugs, true)) {
            $slug = null;
        }

        $hours = $data['duration_hours'] ?? null;
        $lang = $this->nullIfEmpty($data['detected_language'] ?? null);

        return [
            'detected_language' => $lang,
            'normalized_text' => $this->nullIfEmpty($data['normalized_text'] ?? null),
            'category_slug' => $slug ?: null,
            'city' => $this->nullIfEmpty($data['city'] ?? null),
            'district' => $this->nullIfEmpty($data['district'] ?? null),
            'time_hhmm' => $this->nullIfEmpty($data['time_hhmm'] ?? null),
            'duration_hours' => is_numeric($hours) ? (float) $hours : null,
            'time_slot' => $this->nullIfEmpty($data['time_slot'] ?? null),
            'parser' => $parserTag,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractWithKeywords(string $text): array
    {
        $lower = mb_strtolower($text);
        $matchedSlug = null;

        $keywords = [
            'pet-walking' => [
                'it gəzdir', 'it gezdir', 'dog walk', 'it gəz', 'it gez', 'it gəzdirmə', 'kusad',
                'выгул', 'собак', 'гулять с соб',
            ],
            'infant-nanny' => ['körpə', 'korpe', 'infant', 'yenidoğulmuş', 'младен', 'груднич'],
            'school-nanny' => ['məktəbli', 'mektebli', 'school nanny', 'школьник'],
            'nanny' => [
                'dayə', 'daye', 'nanny', 'uşaq', 'usaq', 'uşaq dayası', 'dayəsi', 'dayasi',
                'няня', 'няню', 'ребен', 'ребён', 'ухаживать за ребен',
            ],
            'cleaner' => [
                'təmizlik', 'temizlik', 'cleaner', 'ev xadimə',
                'уборк', 'клининг', 'домработ',
            ],
            'caregiver' => ['baxıcı', 'baxici', 'caregiver', 'qoca', 'сиделк', 'пожилом'],
            'cook' => ['aşpaz', 'aspaz', 'cook', 'yemək', 'повар', 'готовить'],
            'tutor' => ['repetitor', 'tutor', 'müəllim', 'ders', 'репетитор', 'уроки'],
        ];

        foreach ($keywords as $slug => $words) {
            foreach ($words as $word) {
                if (str_contains($lower, $word)) {
                    $matchedSlug = $slug;
                    break 2;
                }
            }
        }

        $timeSlot = null;
        $timeHhmm = null;
        if (preg_match('/saat\s*(\d{1,2})(?::(\d{2}))?/u', $lower, $m)) {
            $hour = (int) $m[1];
            $min = isset($m[2]) ? (int) $m[2] : 0;
            if ($hour >= 1 && $hour <= 6) {
                $hour += 12;
            }
            $timeHhmm = sprintf('%02d:%02d', $hour, $min);
            $timeSlot = $this->slotFromClock($timeHhmm);
        } elseif (preg_match('/səhər|seher|morning|утр[ао]/u', $lower)) {
            $timeSlot = 'morning';
        } elseif (preg_match('/günorta|gunorta|afternoon|днём|днем|после полудн/u', $lower)) {
            $timeSlot = 'afternoon';
        } elseif (preg_match('/axşam|axsam|evening|вечер/u', $lower)) {
            $timeSlot = 'evening';
        } elseif (preg_match('/gecə|gece|night|ночь|ночн/u', $lower)) {
            $timeSlot = 'night';
        }

        $duration = null;
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*saat/u', $lower, $dm)) {
            $duration = (float) str_replace(',', '.', $dm[1]);
        } elseif (preg_match('/(\d+(?:[.,]\d+)?)\s*(час|часа|часов|hour|hours)\b/u', $lower, $dm)) {
            $duration = (float) str_replace(',', '.', $dm[1]);
        } elseif (preg_match('/k[uü]sad|iki\s*saat|2\s*saat|два\s*час|two\s*hour/u', $lower)) {
            $duration = 2.0;
        }

        $district = null;
        if (preg_match('/n[əe]rim[ao]nov|dərmolov|dermolov|nərimolov|nerimolov|dərmalov|dermalov|нариманов|narimanov/u', $lower)) {
            $district = 'Nərimanov';
        } elseif (preg_match('/g[əe]nclik|genclik|gənclikdə|генджлик|gyandjlik/u', $lower)) {
            $district = 'Nərimanov';
        } elseif (preg_match('/qara\s*qarayev|qaraqarayev|kara\s*karayev|кара\s*караев|гара\s*гараев/u', $lower)) {
            $district = 'Nizami';
        } elseif (preg_match('/nizami|низами/u', $lower)) {
            $district = 'Nizami';
        } elseif (preg_match('/yasamal|ясамал/u', $lower)) {
            $district = 'Yasamal';
        } elseif (preg_match('/n[əe]simi|nesimi|насими|nasimi/u', $lower)) {
            $district = 'Nəsimi';
        }

        return [
            'detected_language' => null,
            'normalized_text' => null,
            'category_slug' => $matchedSlug,
            'city' => (str_contains($lower, 'bak') || str_contains($lower, 'баку') || str_contains($lower, 'baku'))
                ? 'Bakı'
                : null,
            'district' => $district,
            'time_hhmm' => $timeHhmm,
            'duration_hours' => $duration,
            'time_slot' => $timeSlot,
            'parser' => 'keywords',
        ];
    }

    public function slotFromClock(?string $hhmm): ?string
    {
        if (! $hhmm || ! preg_match('/^(\d{1,2}):(\d{2})$/', $hhmm, $m)) {
            return null;
        }
        $h = (int) $m[1];
        if ($h >= 5 && $h <= 11) {
            return 'morning';
        }
        if ($h >= 12 && $h <= 16) {
            return 'afternoon';
        }
        if ($h >= 17 && $h <= 21) {
            return 'evening';
        }

        return 'night';
    }

    private function nullIfEmpty(mixed $v): ?string
    {
        if (! is_string($v)) {
            return null;
        }
        $v = trim($v);
        if ($v === '' || strtolower($v) === 'null') {
            return null;
        }

        return $v;
    }

    private function resolveLocalPath(string $audioPathOrUrl): string
    {
        if (str_starts_with($audioPathOrUrl, 'http')) {
            $tmp = tempnam(sys_get_temp_dir(), 'audio_');
            file_put_contents($tmp, file_get_contents($audioPathOrUrl));

            return $tmp;
        }

        if (Storage::disk('public')->exists($audioPathOrUrl)) {
            return Storage::disk('public')->path($audioPathOrUrl);
        }

        return $audioPathOrUrl;
    }
}

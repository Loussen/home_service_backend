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
     * Transcribe audio via OpenAI Whisper. Falls back to stub in non-configured env.
     *
     * @param  list<string>  $locationHints  City/district names to bias Whisper vocabulary
     */
    public function transcribe(string $audioPathOrUrl, array $locationHints = []): string
    {
        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            if (app()->environment('production')) {
                throw new \RuntimeException('Audio transcription is not configured');
            }

            Log::warning('OpenAI key missing — returning stub transcript');

            return 'Nərimanovda saat 3-də it gəzdirən adam lazımdır 2 saatlıq';
        }

        $localPath = $this->resolveLocalPath($audioPathOrUrl);

        $payload = [
            'model' => 'whisper-1',
            'language' => 'az',
            // Biases decoding toward real AZ place names + service phrases (cuts Dərmolov/küsadlıq-type errors).
            'prompt' => $this->whisperBiasPrompt($locationHints),
        ];

        $response = Http::withToken($apiKey)
            ->attach('file', file_get_contents($localPath), basename($localPath))
            ->post('https://api.openai.com/v1/audio/transcriptions', $payload);

        if (! $response->successful()) {
            Log::error('Whisper failed', ['body' => $response->body()]);

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
Sən Azərbaycan dilində ev xidməti marketplace-inin (MySancho) sorğu analitikisən.

Giriş mətni çox vaxt Whisper ASR-dən gəlir: səhv yazı, dil sürüşməsi, səhv intonasiya, fonetik təhrif ola bilər.
Məqsəd: insan kimi niyyəti başa düşmək, səs səhvlərini düzəltmək, sonra JSON çıxarmaq.

1) ƏVVƏL mətnı düzəlt (normalized_text):
   - Rayon/şəhər adlarını yalnız bu siyahıdan seç (fonetik oxşarlığı nəzərə al): {$locationsJson}
     Nümunə ASR səhvləri: "Dərmolov/Dermolov/Nərimolov" → "Nərimanov"; "Yasamal/Yasamalda" → "Yasamal".
   - Müddət: "küsadlıq/kusadliq/ikişaatlıq/iki saatlıg" və oxşar → "2 saatlıq" (duration_hours=2).
   - "sabah / bu gün / axşamüstü" saxla; kateqoriya sözlərini düzgün AZ yaz (dayə, it gəzdirmə, təmizlik…).
   - Məntiqi tamamla, amma uydurma fakt əlavə etmə.
   - Əgər rayon adı səhv səslənirsə (məs. Dərmolov), siyahıdakı ən yaxın real rayonu seç (Nərimanov).

2) SONRA JSON sahələri (siyahıya uyğun):
Yalnız bu kateqoriya slug-larından biri (yarpaq): {$catalogJson}

Qaydalar:
- category_slug: siyahıdakı slug və ya null
- city, district: siyahıdakı qısa ad (məs. Bakı, Nərimanov) və ya null.
  "Gənclik", "28 May", "İçərişəhər" kimi məhəllə/metro → ən yaxın rəsmi rayon (adətən Nərimanov / Nəsimi / Səbail).
- time_hhmm: 24 saat "HH:MM" (saat 3 günorta = 15:00). Yoxdursa null
- duration_hours: rəqəm və ya null
- time_slot: morning|afternoon|evening|night və ya null
  (05–11 morning, 12–16 afternoon, 17–21 evening, 22–04 night)
- normalized_text: düzəldilmiş tam cümlə (istifadəçiyə göstərilə bilər)

Yalnız JSON:
{"normalized_text":"","category_slug":"","city":"","district":"","time_hhmm":"","duration_hours":null,"time_slot":""}
PROMPT;

        try {
            $response = Http::withToken($apiKey)
                ->timeout(35)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $prompt],
                        [
                            'role' => 'user',
                            'content' => "ASR transcript (səhv ola bilər):\n".$text,
                        ],
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning('LLM parse request failed', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('LLM parse HTTP failed', ['body' => $response->body()]);

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

        return [
            'normalized_text' => $this->nullIfEmpty($data['normalized_text'] ?? null),
            'category_slug' => $slug ?: null,
            'city' => $this->nullIfEmpty($data['city'] ?? null),
            'district' => $this->nullIfEmpty($data['district'] ?? null),
            'time_hhmm' => $this->nullIfEmpty($data['time_hhmm'] ?? null),
            'duration_hours' => is_numeric($hours) ? (float) $hours : null,
            'time_slot' => $this->nullIfEmpty($data['time_slot'] ?? null),
            'parser' => 'llm',
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
            'pet-walking' => ['it gəzdir', 'it gezdir', 'dog walk', 'it gəz', 'it gez', 'it gəzdirmə', 'kusad'],
            'infant-nanny' => ['körpə', 'korpe', 'infant', 'yenidoğulmuş'],
            'school-nanny' => ['məktəbli', 'mektebli', 'school nanny'],
            'nanny' => ['dayə', 'daye', 'nanny', 'uşaq', 'usaq', 'uşaq dayası'],
            'cleaner' => ['təmizlik', 'temizlik', 'cleaner', 'ev xadimə'],
            'caregiver' => ['baxıcı', 'baxici', 'caregiver', 'qoca'],
            'cook' => ['aşpaz', 'aspaz', 'cook', 'yemək'],
            'tutor' => ['repetitor', 'tutor', 'müəllim', 'ders'],
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
        } elseif (preg_match('/səhər|seher|morning/u', $lower)) {
            $timeSlot = 'morning';
        } elseif (preg_match('/günorta|gunorta|afternoon/u', $lower)) {
            $timeSlot = 'afternoon';
        } elseif (preg_match('/axşam|axsam|evening/u', $lower)) {
            $timeSlot = 'evening';
        } elseif (preg_match('/gecə|gece|night/u', $lower)) {
            $timeSlot = 'night';
        }

        $duration = null;
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*saat/u', $lower, $dm)) {
            $duration = (float) str_replace(',', '.', $dm[1]);
        } elseif (preg_match('/k[uü]sad|iki\s*saat|2\s*saat/u', $lower)) {
            $duration = 2.0;
        }

        $district = null;
        if (preg_match('/n[əe]rim[ao]nov|dərmolov|dermolov|nərimolov|nerimolov|dərmalov|dermalov/u', $lower)) {
            $district = 'Nərimanov';
        } elseif (preg_match('/g[əe]nclik|genclik|gənclikdə|genclikde/u', $lower)) {
            $district = 'Nərimanov';
        } elseif (preg_match('/yasamal/u', $lower)) {
            $district = 'Yasamal';
        } elseif (preg_match('/n[əe]simi|nesimi/u', $lower)) {
            $district = 'Nəsimi';
        }

        return [
            'normalized_text' => null,
            'category_slug' => $matchedSlug,
            'city' => str_contains($lower, 'bak') ? 'Bakı' : null,
            'district' => $district,
            'time_hhmm' => $timeHhmm,
            'duration_hours' => $duration,
            'time_slot' => $timeSlot,
            'parser' => 'keywords',
        ];
    }

    /**
     * @param  list<string>  $locationHints
     */
    private function whisperBiasPrompt(array $locationHints): string
    {
        $places = array_values(array_unique(array_filter(array_map(
            static function (string $hint): string {
                // "Bakı / Nərimanov" → keep readable tokens
                return trim(str_replace('/', ' ', $hint));
            },
            $locationHints
        ))));

        // Whisper prompt works best as sample transcript prose, not a word list.
        $placeLine = $places !== []
            ? implode(', ', array_slice($places, 0, 40))
            : 'Bakı, Nərimanov, Nəsimi, Yasamal, Xətai, Səbail, Binəqədi, Gənclik';

        return 'Azərbaycan dili. Bakı rayonları: '.$placeLine.'. '
            .'Nümunə sorğular: Nərimanovda sabah üçün uşaq dayası lazımdır. '
            .'Gənclikdə 2 saatlıq it gəzdirmək üçün insan axtarılır. '
            .'Yasamalda axşam təmizlikçi lazımdır. '
            .'Saat 15:00-da 2 saatlıq dayə axtarıram.';
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

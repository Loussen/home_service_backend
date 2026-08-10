<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AIService
{
    /**
     * Transcribe audio via OpenAI Whisper. Falls back to stub in non-configured env.
     */
    public function transcribe(string $audioPathOrUrl): string
    {
        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            Log::warning('OpenAI key missing — returning stub transcript');

            return 'Looking for a nanny in Narimanov tomorrow afternoon for 2 hours';
        }

        $localPath = $this->resolveLocalPath($audioPathOrUrl);

        $response = Http::withToken($apiKey)
            ->attach('file', file_get_contents($localPath), basename($localPath))
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => 'whisper-1',
                'language' => 'az',
            ]);

        if (! $response->successful()) {
            Log::error('Whisper failed', ['body' => $response->body()]);

            throw new \RuntimeException('Audio transcription failed');
        }

        return (string) $response->json('text');
    }

    /**
     * Lightweight keyword parser for category / time / location hints.
     * Replace later with a structured LLM extract step.
     */
    public function parseRequestText(string $text, array $categorySlugs = []): array
    {
        $lower = mb_strtolower($text);
        $categoryId = null;
        $matchedSlug = null;

        $keywords = [
            'nanny' => ['dayə', 'daye', 'nanny', 'uşaq', 'usaq'],
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
        if (preg_match('/səhər|seher|morning/u', $lower)) {
            $timeSlot = 'morning';
        } elseif (preg_match('/günorta|gunorta|afternoon/u', $lower)) {
            $timeSlot = 'afternoon';
        } elseif (preg_match('/axşam|axsam|evening/u', $lower)) {
            $timeSlot = 'evening';
        } elseif (preg_match('/gecə|gece|night/u', $lower)) {
            $timeSlot = 'night';
        }

        return [
            'category_slug' => $matchedSlug,
            'time_slot' => $timeSlot,
            'raw_text' => $text,
        ];
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

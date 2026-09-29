<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Free-text answers for the help agent when no intent matches.
 *
 * OpenAI first (when OPENAI_API_KEY is set), then Ollama only when OLLAMA_URL
 * is set and answers a 2-second ping (result cached for a minute). Returns null
 * on any failure — unlike the shared providers, it never returns a canned
 * "temporarily unavailable" sentence — so the caller can show quick actions.
 */
class HelpAgentAi
{
    private const OLLAMA_PING_TIMEOUT = 2;
    private const OLLAMA_PING_CACHE   = 60;

    /** @return array{answer: string, provider: string}|null */
    public function answer(string $systemPrompt, string $question): ?array
    {
        if ($text = $this->openAi($systemPrompt, $question)) {
            return ['answer' => $text, 'provider' => 'openai'];
        }

        if ($this->ollamaReachable() && ($text = $this->ollama($systemPrompt, $question))) {
            return ['answer' => $text, 'provider' => 'ollama'];
        }

        return null;
    }

    public function ollamaReachable(): bool
    {
        $url = rtrim(trim((string) config('services.ollama.url')), '/');
        if ($url === '') {
            return false;
        }

        return Cache::remember('outy:ollama-up:' . md5($url), self::OLLAMA_PING_CACHE, function () use ($url) {
            try {
                return Http::connectTimeout(self::OLLAMA_PING_TIMEOUT)
                    ->timeout(self::OLLAMA_PING_TIMEOUT)
                    ->get("{$url}/api/tags")
                    ->successful();
            } catch (\Throwable) {
                return false;
            }
        });
    }

    private function openAi(string $systemPrompt, string $question): ?string
    {
        $key = trim((string) config('services.openai.key'));
        if ($key === '') {
            return null;
        }

        try {
            $response = Http::withToken($key)
                ->connectTimeout(3)
                ->timeout(15)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model'       => config('services.openai.model', 'gpt-4o'),
                    'max_tokens'  => 400,
                    'temperature' => 0.3,
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $question],
                    ],
                ]);

            if ($response->successful()) {
                return $this->clean($response->json('choices.0.message.content'));
            }

            Log::warning('Outy: OpenAI request failed', ['status' => $response->status()]);
        } catch (\Throwable $e) {
            Log::warning('Outy: OpenAI unreachable', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function ollama(string $systemPrompt, string $question): ?string
    {
        $url = rtrim(trim((string) config('services.ollama.url')), '/');

        try {
            $response = Http::connectTimeout(self::OLLAMA_PING_TIMEOUT)
                ->timeout(20)
                ->post("{$url}/api/generate", [
                    'model'   => config('services.ollama.model', 'llama3'),
                    'system'  => $systemPrompt,
                    'prompt'  => $question,
                    'stream'  => false,
                    'options' => ['temperature' => 0.2, 'num_predict' => 400],
                ]);

            if ($response->successful()) {
                return $this->clean($response->json('response'));
            }

            Log::warning('Outy: Ollama request failed', ['status' => $response->status()]);
        } catch (\Throwable $e) {
            Log::warning('Outy: Ollama unreachable', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function clean(?string $text): ?string
    {
        $text = trim((string) $text);

        return mb_strlen($text) >= 5 ? $text : null;
    }
}

<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiProvider implements AiProviderInterface
{
    private string $apiKey;
    private string $model;
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.openai.key', '');
        $this->model  = config('services.openai.model', 'gpt-4o');
    }

    public function getProviderName(): string
    {
        return 'openai';
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function generateSummary(array $data): string
    {
        $prompt = "You are OutraqHQ AI, executive assistant for engineering leaders. Generate a 3-paragraph executive summary:
1. Team health and output
2. Key concerns needing attention
3. One specific actionable recommendation

Data: " . json_encode($data) . "

Under 200 words. No bullets. Plain paragraphs.";

        return $this->callOpenAi($prompt);
    }

    public function answerQuery(string $question, array $context): string
    {
        $contextText = json_encode($context);
        $prompt = "You are OutraqHQ AI. Answer in 2-3 sentences.
Question: {$question}
Context: {$contextText}
Be direct and data-driven.";

        return $this->callOpenAi($prompt);
    }

    public function generateResponse(string $prompt): string
    {
        return $this->callOpenAi($prompt);
    }

    public function explainFlag(array $evidence, string $flagType): string
    {
        $prompt = "Explain this fairness flag in 2 sentences.
Type: {$flagType}
Evidence: " . json_encode($evidence) . "
Be neutral, factual, non-accusatory.";

        return $this->callOpenAi($prompt);
    }

    private function callOpenAi(string $prompt): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type'  => 'application/json',
            ])->timeout(30)->post($this->apiUrl, [
                'model'      => $this->model,
                'max_tokens' => 500,
                'messages'   => [
                    [
                        'role'    => 'system',
                        'content' => 'You are OutraqHQ AI, a work intelligence assistant for engineering teams.',
                    ],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content') ?? 'Unable to generate response.';
            }

            Log::error('OpenAI error', ['status' => $response->status(), 'body' => $response->body()]);
            return 'OpenAI temporarily unavailable.';
        } catch (\Exception $e) {
            Log::error('OpenAI exception', ['msg' => $e->getMessage()]);
            return 'OpenAI temporarily unavailable.';
        }
    }
}

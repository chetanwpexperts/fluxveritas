<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\AI\Providers\RuleBasedProvider;

class OllamaProvider implements AiProviderInterface
{
    private string $baseUrl;
    private string $model;

    public function __construct()
    {
        $this->baseUrl = config('services.ollama.url', 'http://localhost:11434');
        $this->model = config('services.ollama.model', 'llama3');
    }

    public function getProviderName(): string
    {
        return 'ollama';
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/api/tags");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function generateSummary(array $data): string
    {
        $prompt = "You are OutraqHQ AI for engineering teams. Generate a 3-paragraph executive summary based on:
- Members: {$data['total_members']}
- Commits this week: {$data['commits_this_week']}
- PRs this week: {$data['prs_this_week']}
- Open blockers: {$data['open_blockers']}
- Pending fairness flags: {$data['pending_flags']}
- Inactive members: {$data['inactive_count']}
- Top contributor: {$data['top_contributor']}

Be concise, direct, under 200 words. No bullet points. Plain paragraphs only.";

        return $this->callOllama($prompt);
    }

    public function answerQuery(string $question, array $context): string
    {
        $data = $context['data'];
        $members = collect($context['members'])->map(
            fn($m) => "{$m['name']}: {$m['commits']} commits, {$m['prs']} PRs, score {$m['avg_score']}"
        )->join("\n");

        $prompt = "You are OutraqHQ AI. Answer this question about the engineering team in 2-3 sentences max.

Question: {$question}

Team data:
Members: {$data['total_members']}
Commits this week: {$data['commits_this_week']}
Open blockers: {$data['open_blockers']}

Member stats (30 days):
{$members}

Answer directly based only on data provided.";

        return $this->callOllama($prompt);
    }

    public function generateResponse(string $prompt): string
    {
        $systemPrompt = "You are OutraqHQ AI, a helpful work intelligence assistant. "
            . "You ALWAYS answer questions about the organization's data, team performance, system health and work logs. "
            . "You NEVER refuse to answer. You NEVER say you cannot provide information. "
            . "You use real data provided to give accurate helpful answers. Always be direct and specific.";

        $fullPrompt = $systemPrompt . "\n\n" . $prompt;

        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/api/generate", [
                'model'   => $this->model,
                'prompt'  => $fullPrompt,
                'stream'  => false,
                'options' => [
                    'temperature' => 0.1,
                    'num_predict' => 400,
                    'top_p'       => 0.9,
                ],
            ]);

            if ($response->successful()) {
                $result = $response->json('response') ?? '';

                // If Ollama still refuses, fall back to rule-based extraction
                $resultLower = strtolower($result);
                if (str_contains($resultLower, 'cannot') ||
                    str_contains($resultLower, 'unable') ||
                    str_contains($resultLower, 'not able') ||
                    strlen(trim($result)) < 20) {
                    return (new RuleBasedProvider())->generateResponse($prompt);
                }

                return $result;
            }

            throw new \Exception('Ollama request failed: ' . $response->status());
        } catch (\Exception $e) {
            Log::error('Ollama generateResponse error', ['msg' => $e->getMessage()]);
            return (new RuleBasedProvider())->generateResponse($prompt);
        }
    }

    public function explainFlag(array $evidence, string $flagType): string
    {
        $evidenceText = json_encode($evidence);
        $prompt = "Explain this team fairness flag in 2 sentences.
Flag: {$flagType}
Evidence: {$evidenceText}
Be neutral and factual. Suggest what to investigate.";

        return $this->callOllama($prompt);
    }

    private function callOllama(string $prompt): string
    {
        try {
            $response = Http::timeout(60)->post("{$this->baseUrl}/api/generate", [
                'model'  => $this->model,
                'prompt' => $prompt,
                'stream' => false,
            ]);

            if ($response->successful()) {
                return $response->json('response') ?? 'Unable to generate response.';
            }

            Log::error('Ollama error', ['body' => $response->body()]);
            return 'Ollama AI temporarily unavailable.';
        } catch (\Exception $e) {
            Log::error('Ollama exception', ['msg' => $e->getMessage()]);
            return 'Ollama AI temporarily unavailable.';
        }
    }
}

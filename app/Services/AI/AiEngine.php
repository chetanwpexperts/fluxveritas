<?php

namespace App\Services\AI;

use App\Models\Organization;
use App\Services\AI\Providers\OllamaProvider;
use App\Services\AI\Providers\OpenAiProvider;
use App\Services\AI\Providers\RuleBasedProvider;
use Illuminate\Support\Facades\Log;

class AiEngine
{
    private AiProviderInterface $provider;
    private string $providerName;

    public function __construct(int $orgId)
    {
        $this->provider = $this->resolveProvider($orgId);
        $this->providerName = $this->provider->getProviderName();
    }

    private function resolveProvider(int $orgId): AiProviderInterface
    {
        $org = Organization::find($orgId);
        $plan = $org?->plan ?? 'free';

        if ($plan === 'enterprise') {
            $openai = new OpenAiProvider();
            if ($openai->isAvailable()) {
                Log::info("AiEngine: Using OpenAI for org {$orgId}");
                return $openai;
            }
        }

        if (in_array($plan, ['pro', 'enterprise'])) {
            $ollama = new OllamaProvider();
            if ($ollama->isAvailable()) {
                Log::info("AiEngine: Using Ollama for org {$orgId}");
                return $ollama;
            }
        }

        Log::info("AiEngine: Using RuleBased for org {$orgId}");
        return new RuleBasedProvider();
    }

    public function generateSummary(array $data): string
    {
        return $this->provider->generateSummary($data);
    }

    public function answerQuery(string $question, array $context): string
    {
        if (!empty($context['full_prompt'])) {
            return $this->provider->generateResponse($context['full_prompt']);
        }

        $data      = $context['data'] ?? [];
        $fullPrompt = $this->buildBasicPrompt($question, $data, $context);
        return $this->provider->generateResponse($fullPrompt);
    }

    private function buildBasicPrompt(string $question, array $data, array $context): string
    {
        $members       = $context['members'] ?? [];
        $memberSummary = '';
        foreach ($members as $m) {
            $name    = is_array($m) ? ($m['name'] ?? '') : ($m->name ?? '');
            $commits = is_array($m) ? ($m['commits'] ?? 0) : 0;
            $memberSummary .= "- {$name}: {$commits} commits\n";
        }

        return "You are OutraqHQ AI assistant.\n\n"
            . "Question: {$question}\n\n"
            . "Team data:\n"
            . "- Total members: " . ($data['total_members'] ?? 0) . "\n"
            . "- Commits this week: " . ($data['commits_this_week'] ?? 0) . "\n"
            . "- Open blockers: " . ($data['open_blockers'] ?? 0) . "\n"
            . "- Pending fairness flags: " . ($data['pending_flags'] ?? 0) . "\n\n"
            . "Members:\n{$memberSummary}\n"
            . "Answer helpfully and accurately.";
    }

    public function explainFlag(array $evidence, string $flagType): string
    {
        return $this->provider->explainFlag($evidence, $flagType);
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }

    public function getProviderBadge(): array
    {
        return match ($this->providerName) {
            'openai' => [
                'label'       => 'GPT-4o',
                'color'       => 'fv-badge-green',
                'description' => 'Powered by OpenAI',
            ],
            'ollama' => [
                'label'       => 'Llama 3 Local',
                'color'       => 'fv-badge-blue',
                'description' => 'Running locally on your server',
            ],
            default => [
                'label'       => 'Rule Engine',
                'color'       => 'fv-badge-amber',
                'description' => 'Upgrade to Pro for AI summaries',
            ],
        };
    }
}

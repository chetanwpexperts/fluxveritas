<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AiOrchestrator
{
    private string $openAiKey;
    private string $model = 'gpt-4o';
    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->openAiKey = config('services.openai.key');
    }

    /**
     * Generate executive summary for leader
     */
    public function generateExecutiveSummary(int $organizationId, string $period = 'daily'): array
    {
        $cacheKey = "exec_summary_{$organizationId}_{$period}_" . now()->format('Y-m-d');

        return Cache::remember($cacheKey, 3600, function () use ($organizationId, $period) {
            $data = $this->gatherOrganizationData($organizationId, $period);

            // Sanitize before sending to external AI
            $sanitizedData = app(AiAccessControl::class)->sanitizeForExternalAi($data);

            $prompt = $this->buildSummaryPrompt($sanitizedData, $period);

            try {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$this->openAiKey}",
                    'Content-Type' => 'application/json',
                ])->timeout(30)->post("{$this->baseUrl}/chat/completions", [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are FluxVeritas AI, an executive assistant that generates concise, data-backed summaries for technology leaders. Be factual, highlight anomalies, suggest actions. Keep summaries under 200 words. Never include personal information.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 400,
                ]);

                if ($response->successful()) {
                    $content = $response->json('choices.0.message.content');

                    // Verify response doesn't contain leaked data
                    if (app(AiAccessControl::class)->verifyExternalAiResponse($content)) {
                        return [
                            'summary' => $content,
                            'generated_at' => now()->toIso8601String(),
                            'data_points_analyzed' => count($data),
                            'period' => $period,
                        ];
                    }
                }

                return [
                    'summary' => 'Unable to generate summary. Please review dashboard directly.',
                    'generated_at' => now()->toIso8601String(),
                    'error' => true,
                ];

            } catch (\Exception $e) {
                Log::error('OpenAI API error', ['error' => $e->getMessage()]);
                return [
                    'summary' => 'Summary generation failed. Data available in dashboard.',
                    'generated_at' => now()->toIso8601String(),
                    'error' => true,
                ];
            }
        });
    }

    /**
     * Parse natural language intent into structured tasks
     */
    public function parseIntent(string $userInput): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->openAiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(15)->post("{$this->baseUrl}/chat/completions", [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Parse user intent into structured tasks. Return JSON with: intent_type (query|command|report), tasks[], priority (low|medium|high), estimated_effort_hours.',
                    ],
                    ['role' => 'user', 'content' => $userInput],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.2,
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                return json_decode($content, true) ?? [
                    'intent_type' => 'unknown',
                    'tasks' => [],
                    'priority' => 'low',
                ];
            }

            return ['intent_type' => 'error', 'tasks' => []];

        } catch (\Exception $e) {
            Log::error('Intent parsing failed', ['error' => $e->getMessage()]);
            return ['intent_type' => 'error', 'tasks' => []];
        }
    }

    /**
     * Analyze team health and predict issues
     */
    public function analyzeTeamHealth(int $teamId): array
    {
        // Use local analysis first, external AI for complex patterns
        $metrics = $this->calculateTeamMetrics($teamId);

        // Simple analysis = local
        // Complex pattern detection = external AI with sanitized data
        if ($metrics['complexity'] > 0.7) {
            return $this->analyzeWithAi($metrics);
        }

        return $this->analyzeLocally($metrics);
    }

    /**
     * Gather organization data for summary
     */
    private function gatherOrganizationData(int $orgId, string $period): array
    {
        $dateRange = match($period) {
            'daily' => now()->subDay(),
            'weekly' => now()->subWeek(),
            'monthly' => now()->subMonth(),
            default => now()->subDay(),
        };

        // Aggregate data (anonymized)
        return [
            'period' => $period,
            'tasks_completed' => rand(50, 200), // Placeholder
            'tasks_blocked' => rand(2, 15),
            'flags_raised' => rand(0, 5),
            'avg_quality_score' => round(rand(70, 95) / 10, 1),
            'team_size' => rand(5, 50),
            'active_projects' => rand(2, 10),
            'at_risk_projects' => rand(0, 2),
        ];
    }

    private function buildSummaryPrompt(array $data, string $period): string
    {
        return "Generate a {$period} executive summary for a technology team.\n\n" .
               "Data:\n" .
               "- Tasks completed: {$data['tasks_completed']}\n" .
               "- Tasks blocked: {$data['tasks_blocked']}\n" .
               "- Quality score: {$data['avg_quality_score']}/10\n" .
               "- Team size: {$data['team_size']}\n" .
               "- Active projects: {$data['active_projects']}\n" .
               "- At-risk projects: {$data['at_risk_projects']}\n" .
               "- Flags raised: {$data['flags_raised']}\n\n" .
               "Provide: 1) Overall health status, 2) Key concerns, 3) Suggested actions.";
    }

    private function calculateTeamMetrics(int $teamId): array
    {
        // Local calculation
        return [
            'complexity' => 0.5,
            'velocity' => 0.7,
            'quality' => 0.8,
            'morale' => 0.6,
        ];
    }

    private function analyzeLocally(array $metrics): array
    {
        return [
            'status' => 'healthy',
            'recommendations' => ['Continue current practices'],
        ];
    }

    private function analyzeWithAi(array $metrics): array
    {
        // External AI analysis with sanitized data
        return [
            'status' => 'needs_attention',
            'recommendations' => ['Review flagged items'],
        ];
    }
}

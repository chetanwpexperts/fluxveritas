<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Github\Client;
use Github\AuthMethod;

class GitHubAnalyzer
{
    private ?Client $client;

    public function __construct()
    {
        if (!$this->isConfigured()) {
            Log::info('GitHub token not configured — skipping GitHub tracking');
            $this->client = null;
            return;
        }

        try {
            $this->client = new Client();
            $this->client->authenticate(
                config('services.github.token'),
                null,
                AuthMethod::ACCESS_TOKEN
            );
        } catch (\Exception $e) {
            Log::warning('GitHub client init failed', ['error' => $e->getMessage()]);
            $this->client = null;
        }
    }

    private function isConfigured(): bool
    {
        return !empty(config('services.github.token'));
    }

    /**
     * Fetch all activity for a user in a repo
     */
    public function fetchUserActivity(string $username, string $owner, string $repo, int $days = 30): array
    {
        if ($this->client === null) {
            return $this->emptyActivity($username, $owner, $repo, $days);
        }

        $cacheKey = "github_{$username}_{$owner}_{$repo}_{$days}";

        try {
            return Cache::remember($cacheKey, 3600, function () use ($username, $owner, $repo, $days) {
                $commits = $this->getCommits($username, $owner, $repo, $days);
                $prs     = $this->getPullRequests($username, $owner, $repo);

                return [
                    'username'      => $username,
                    'repo'          => "{$owner}/{$repo}",
                    'period_days'   => $days,
                    'commits'       => [
                        'count'            => count($commits),
                        'complexity_score' => $this->analyzeComplexity($commits),
                    ],
                    'pull_requests' => [
                        'opened' => count($prs),
                        'merged' => count(array_filter($prs, fn($p) => !empty($p['merged_at']))),
                    ],
                    'overall_score' => $this->calculateScore($commits, $prs),
                    'fetched_at'    => now()->toDateTimeString(),
                ];
            });
        } catch (\Exception $e) {
            Log::warning('GitHub fetchUserActivity failed — continuing without GitHub data', [
                'username' => $username,
                'repo'     => "{$owner}/{$repo}",
                'error'    => $e->getMessage(),
            ]);
            return $this->emptyActivity($username, $owner, $repo, $days);
        }
    }

    /**
     * Get commits by user
     */
    public function getCommits(string $username, string $owner, string $repo, int $days = 30): array
    {
        if ($this->client === null) return [];

        try {
            $since   = now()->subDays($days)->format('Y-m-d\TH:i:s\Z');
            $commits = $this->client->api('repo')->commits()->all($owner, $repo, [
                'author'   => $username,
                'since'    => $since,
                'per_page' => 100,
            ]);
            return is_array($commits) ? $commits : [];
        } catch (\Exception $e) {
            Log::warning('GitHub commits fetch failed — continuing without commit data', [
                'repo'  => "{$owner}/{$repo}",
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get pull requests by user
     */
    public function getPullRequests(string $username, string $owner, string $repo): array
    {
        if ($this->client === null) return [];

        try {
            $prs = $this->client->api('pull_request')->all($owner, $repo, [
                'state'    => 'all',
                'per_page' => 100,
            ]);

            if (!is_array($prs)) return [];

            return array_values(array_filter($prs, fn($pr) =>
                isset($pr['user']['login']) && $pr['user']['login'] === $username
            ));
        } catch (\Exception $e) {
            Log::warning('GitHub PRs fetch failed — continuing without PR data', [
                'repo'  => "{$owner}/{$repo}",
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Analyze commit complexity based on commit messages
     */
    public function analyzeComplexity(array $commits): float
    {
        $score = 0;
        foreach ($commits as $commit) {
            $message = strtolower($commit['commit']['message'] ?? '');
            $score  += match (true) {
                str_contains($message, 'security') => 2.5,
                str_contains($message, 'perf')     => 2.0,
                str_contains($message, 'refactor') => 1.8,
                str_contains($message, 'feat')     => 1.5,
                str_contains($message, 'fix')      => 1.0,
                default                            => 0.8,
            };
        }
        return round($score, 2);
    }

    /**
     * Calculate overall score
     */
    public function calculateScore(array $commits, array $prs): float
    {
        $commitScore = count($commits) * 1.0;
        $prScore     = count($prs) * 3.0;
        return round(($commitScore * 0.6) + ($prScore * 0.4), 2);
    }

    /**
     * Return a zeroed-out activity result (used when GitHub is unavailable)
     */
    private function emptyActivity(string $username, string $owner, string $repo, int $days): array
    {
        return [
            'username'      => $username,
            'repo'          => "{$owner}/{$repo}",
            'period_days'   => $days,
            'commits'       => ['count' => 0, 'complexity_score' => 0.0],
            'pull_requests' => ['opened' => 0, 'merged' => 0],
            'overall_score' => 0.0,
            'fetched_at'    => now()->toDateTimeString(),
        ];
    }

    /**
     * Get authenticated GitHub client (null if token not configured)
     */
    public function getClient(): ?Client
    {
        return $this->client;
    }
}

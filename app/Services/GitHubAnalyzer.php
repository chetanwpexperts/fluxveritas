<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GitHubAnalyzer
{
    private string $token;
    private string $baseUrl = 'https://api.github.com';

    public function __construct(?string $token = null)
    {
        $this->token = $token ?? config('services.github.token');
    }

    /**
     * Fetch comprehensive user activity from GitHub
     */
    public function fetchUserActivity(string $username, string $repo, array $dateRange = []): array
    {
        $cacheKey = "github_activity_{$username}_{$repo}_" . ($dateRange['start'] ?? 'all');

        return Cache::remember($cacheKey, 3600, function () use ($username, $repo, $dateRange) {
            try {
                $commits = $this->getCommits($username, $repo, $dateRange);
                $prs = $this->getPullRequests($username, $repo, $dateRange);
                $reviews = $this->getCodeReviews($username, $repo, $dateRange);

                return [
                    'employee_id' => $username,
                    'period' => $dateRange,
                    'commits' => [
                        'count' => count($commits),
                        'lines_added' => $this->sumLinesAdded($commits),
                        'lines_deleted' => $this->sumLinesDeleted($commits),
                        'files_changed' => $this->countUniqueFiles($commits),
                        'complexity_score' => $this->analyzeComplexity($commits),
                    ],
                    'pull_requests' => [
                        'opened' => count(array_filter($prs, fn($p) => ($p['user']['login'] ?? '') === $username)),
                        'merged' => count(array_filter($prs, fn($p) => ($p['merged'] ?? false) && ($p['user']['login'] ?? '') === $username)),
                        'reviewed' => count($reviews),
                        'review_quality' => $this->calculateReviewQuality($reviews),
                    ],
                    'calculated_score' => $this->calculateOverallScore($commits, $prs, $reviews),
                    'fetched_at' => now()->toIso8601String(),
                ];
            } catch (\Exception $e) {
                Log::error('GitHub API error', [
                    'username' => $username,
                    'repo' => $repo,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'error' => true,
                    'message' => 'Failed to fetch GitHub data',
                ];
            }
        });
    }

    /**
     * Fetch commits for a user in a repository
     */
    private function getCommits(string $username, string $repo, array $dateRange): array
    {
        $url = "{$this->baseUrl}/repos/{$repo}/commits";
        $params = ['author' => $username, 'per_page' => 100];

        if (!empty($dateRange['start'])) {
            $params['since'] = $dateRange['start'];
        }
        if (!empty($dateRange['end'])) {
            $params['until'] = $dateRange['end'];
        }

        $response = Http::withToken($this->token)
            ->get($url, $params);

        if (!$response->successful()) {
            return [];
        }

        $commits = $response->json();

        // Fetch detailed commit data (files changed, stats)
        $detailedCommits = [];
        foreach (array_slice($commits, 0, 50) as $commit) {
            $detailResponse = Http::withToken($this->token)
                ->get($commit['url']);

            if ($detailResponse->successful()) {
                $detailedCommits[] = $detailResponse->json();
            }
        }

        return $detailedCommits;
    }

    /**
     * Fetch pull requests for a user
     */
    private function getPullRequests(string $username, string $repo, array $dateRange): array
    {
        $url = "{$this->baseUrl}/repos/{$repo}/pulls";
        $params = ['state' => 'all', 'per_page' => 100];

        $response = Http::withToken($this->token)
            ->get($url, $params);

        return $response->successful() ? $response->json() : [];
    }

    /**
     * Fetch code reviews by a user
     */
    private function getCodeReviews(string $username, string $repo, array $dateRange): array
    {
        // This requires fetching PRs and then their reviews
        $prs = $this->getPullRequests($username, $repo, $dateRange);
        $reviews = [];

        foreach (array_slice($prs, 0, 30) as $pr) {
            $reviewResponse = Http::withToken($this->token)
                ->get($pr['url'] . '/reviews');

            if ($reviewResponse->successful()) {
                $prReviews = $reviewResponse->json();
                $userReviews = array_filter($prReviews, 
                    fn($r) => ($r['user']['login'] ?? '') === $username
                );
                $reviews = array_merge($reviews, $userReviews);
            }
        }

        return $reviews;
    }

    /**
     * Analyze code complexity (not just lines changed)
     */
    private function analyzeComplexity(array $commits): float
    {
        $totalComplexity = 0;

        foreach ($commits as $commit) {
            $files = $commit['files'] ?? [];
            foreach ($files as $file) {
                $filename = $file['filename'] ?? '';
                $complexity = match(true) {
                    str_contains($filename, 'Test') => 0.5,
                    str_contains($filename, 'Config') => 0.3,
                    str_contains($filename, 'Migration') => 0.4,
                    str_contains($filename, 'Controller') => 1.2,
                    str_contains($filename, 'Service') => 1.5,
                    str_contains($filename, 'Repository') => 1.3,
                    str_contains($filename, 'Algorithm') => 2.0,
                    str_contains($filename, 'Middleware') => 1.1,
                    default => 1.0,
                };

                $changeSize = ($file['additions'] ?? 0) + ($file['deletions'] ?? 0);
                $totalComplexity += $complexity * min($changeSize / 10, 5);
            }
        }

        return round($totalComplexity, 2);
    }

    /**
     * Calculate overall score — NOT just activity volume
     */
    private function calculateOverallScore(array $commits, array $prs, array $reviews): array
    {
        $commitQuality = $this->analyzeCommitQuality($commits);
        $prImpact = $this->analyzePRImpact($prs);
        $reviewValue = $this->analyzeReviewValue($reviews);

        return [
            'raw_score' => round(($commitQuality * 0.4) + ($prImpact * 0.4) + ($reviewValue * 0.2), 2),
            'commit_quality' => $commitQuality,
            'pr_impact' => $prImpact,
            'review_value' => $reviewValue,
            'methodology' => 'Weighted: Commits 40%, PRs 40%, Reviews 20%',
        ];
    }

    private function analyzeCommitQuality(array $commits): float
    {
        if (empty($commits)) return 0;

        $scores = [];
        foreach ($commits as $commit) {
            $files = $commit['files'] ?? [];
            $testFiles = count(array_filter($files, fn($f) => str_contains($f['filename'] ?? '', 'Test')));
            $totalFiles = count($files);

            // Higher score for commits with tests, moderate file count
            $testRatio = $totalFiles > 0 ? $testFiles / $totalFiles : 0;
            $fileCountScore = $totalFiles > 0 && $totalFiles <= 10 ? 1.0 : 0.5;

            $scores[] = ($testRatio * 0.5 + $fileCountScore * 0.5) * 10;
        }

        return round(array_sum($scores) / count($scores), 2);
    }

    private function analyzePRImpact(array $prs): float
    {
        if (empty($prs)) return 0;

        $scores = [];
        foreach ($prs as $pr) {
            $score = 0;
            // Merged PRs score higher
            if ($pr['merged'] ?? false) $score += 5;
            // PRs with reviews score higher
            if (($pr['review_comments'] ?? 0) > 0) $score += 2;
            // PRs with descriptions score higher
            if (!empty($pr['body'])) $score += 1;
            // PRs without conflicts score higher
            if (!($pr['mergeable'] === false)) $score += 2;

            $scores[] = min($score, 10);
        }

        return round(array_sum($scores) / count($scores), 2);
    }

    private function analyzeReviewValue(array $reviews): float
    {
        if (empty($reviews)) return 0;

        $scores = [];
        foreach ($reviews as $review) {
            $score = match($review['state'] ?? '') {
                'APPROVED' => 8,
                'CHANGES_REQUESTED' => 7,
                'COMMENTED' => 5,
                default => 3,
            };
            $scores[] = $score;
        }

        return round(array_sum($scores) / count($scores), 2);
    }

    private function sumLinesAdded(array $commits): int
    {
        return array_sum(array_map(
            fn($c) => array_sum(array_column($c['files'] ?? [], 'additions')),
            $commits
        ));
    }

    private function sumLinesDeleted(array $commits): int
    {
        return array_sum(array_map(
            fn($c) => array_sum(array_column($c['files'] ?? [], 'deletions')),
            $commits
        ));
    }

    private function countUniqueFiles(array $commits): int
    {
        $files = [];
        foreach ($commits as $commit) {
            foreach ($commit['files'] ?? [] as $file) {
                $files[$file['filename'] ?? ''] = true;
            }
        }
        return count($files);
    }

    private function calculateReviewQuality(array $reviews): float
    {
        return $this->analyzeReviewValue($reviews);
    }
}

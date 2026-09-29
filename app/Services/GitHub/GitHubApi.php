<?php

namespace App\Services\GitHub;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Minimal GitHub REST client for syncing activity. Pages through results,
 * stops early when the rate limit is nearly used up, and never logs the token.
 */
class GitHubApi
{
    private const BASE      = 'https://api.github.com';
    private const PER_PAGE  = 100;
    private const MIN_QUOTA = 25;   // leave some headroom for other orgs sharing a token

    private ?int $remaining = null;

    public function __construct(private string $token) {}

    public function remaining(): ?int
    {
        return $this->remaining;
    }

    /** The account the token belongs to (used to check a token is valid). */
    public function user(): array
    {
        return $this->get('/user')->json();
    }

    /** @return array{private: bool, full_name: string} */
    public function repository(string $owner, string $repo): array
    {
        $data = $this->get("/repos/{$owner}/{$repo}")->json();

        return ['private' => (bool) ($data['private'] ?? true), 'full_name' => (string) ($data['full_name'] ?? "{$owner}/{$repo}")];
    }

    /** Commits since a date, newest first, up to $maxPages pages. */
    public function commits(string $owner, string $repo, Carbon $since, int $maxPages = 10): array
    {
        $all = [];
        for ($page = 1; $page <= $maxPages; $page++) {
            $batch = $this->get("/repos/{$owner}/{$repo}/commits", [
                'since' => $since->toIso8601ZuluString(), 'per_page' => self::PER_PAGE, 'page' => $page,
            ])->json();
            $all = array_merge($all, is_array($batch) ? $batch : []);
            if (!is_array($batch) || count($batch) < self::PER_PAGE) {
                break;
            }
        }

        return $all;
    }

    /** Pull requests updated since a date (most recently updated first). */
    public function pullRequests(string $owner, string $repo, Carbon $since, int $maxPages = 10): array
    {
        $all = [];
        for ($page = 1; $page <= $maxPages; $page++) {
            $batch = $this->get("/repos/{$owner}/{$repo}/pulls", [
                'state' => 'all', 'sort' => 'updated', 'direction' => 'desc', 'per_page' => self::PER_PAGE, 'page' => $page,
            ])->json();
            if (!is_array($batch) || !$batch) {
                break;
            }
            foreach ($batch as $pr) {
                if (Carbon::parse($pr['updated_at'] ?? '1970-01-01')->lt($since)) {
                    return $all; // everything after this is older
                }
                $all[] = $pr;
            }
            if (count($batch) < self::PER_PAGE) {
                break;
            }
        }

        return $all;
    }

    private function get(string $path, array $query = []): Response
    {
        if ($this->remaining !== null && $this->remaining < self::MIN_QUOTA) {
            throw new GitHubApiException('GitHub rate limit almost used up — sync paused until it resets.', 'rate_limited');
        }

        try {
            $response = Http::withToken($this->token)
                ->withHeaders(['Accept' => 'application/vnd.github+json', 'X-GitHub-Api-Version' => '2022-11-28'])
                ->connectTimeout(5)
                ->timeout(20)
                ->get(self::BASE . $path, $query);
        } catch (ConnectionException) {
            throw new GitHubApiException('GitHub could not be reached.', 'error');
        }

        if ($response->header('X-RateLimit-Remaining') !== '') {
            $this->remaining = (int) $response->header('X-RateLimit-Remaining');
        }

        return match (true) {
            $response->successful() => $response,
            $response->status() === 401 => throw new GitHubApiException('GitHub rejected the token (it may be expired or revoked).', 'auth'),
            $response->status() === 404 => throw new GitHubApiException('Repository not found, or the token has no access to it.', 'not_found'),
            $response->status() === 403 || $response->status() === 429 => throw new GitHubApiException('GitHub rate limit reached — sync paused until it resets.', 'rate_limited'),
            default => throw new GitHubApiException('GitHub returned an error (HTTP ' . $response->status() . ').', 'error'),
        };
    }
}

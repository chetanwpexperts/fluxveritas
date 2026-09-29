<?php

namespace App\Services\GitHub;

use App\Models\Activity;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\GitHubAnalyzer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Syncs GitHub commits and pull requests for a whole organization.
 *
 * - Uses the organization's own token (Settings → GitHub) when set. The shared
 *   platform token is only used for PUBLIC repositories, so one organization
 *   can never read another customer's private repo through it.
 * - Each repo is fetched once; activity is matched to every member by their
 *   GitHub username (Profile → GitHub). Unknown authors are ignored.
 * - Pull requests are stored as pr_opened / pr_merged — the event types the
 *   increment calculator and reports count.
 */
class GitHubSyncService
{
    public const DAYS = 30;

    /** Token to use for an organization, and where it came from. */
    public function tokenFor(Organization $org): array
    {
        $encrypted = $org->settings['github_token'] ?? null;
        if ($encrypted) {
            try {
                return ['token' => decrypt($encrypted), 'source' => 'organization'];
            } catch (\Throwable) {
                Log::warning('GitHub: stored organization token could not be decrypted', ['org_id' => $org->id]);
            }
        }

        $platform = trim((string) config('services.github.token'));

        return $platform !== '' ? ['token' => $platform, 'source' => 'platform'] : ['token' => null, 'source' => null];
    }

    /**
     * @return array{status: string, commits: int, prs: int, people: int, repos: int, messages: string[], at: string}
     */
    public function syncOrganization(Organization $org, int $days = self::DAYS): array
    {
        ['token' => $token, 'source' => $source] = $this->tokenFor($org);
        $result = ['status' => 'ok', 'commits' => 0, 'prs' => 0, 'people' => 0, 'repos' => 0, 'messages' => [], 'token_source' => $source, 'at' => now()->toIso8601String()];

        if (!$token) {
            return $this->finish($org, array_merge($result, ['status' => 'failed', 'messages' => ['GitHub is not connected. Add a token in Settings → GitHub.']]));
        }

        $people = User::where('organization_id', $org->id)
            ->where('is_active', true)
            ->whereNotNull('github_username')->where('github_username', '!=', '')
            ->pluck('id', 'github_username')
            ->mapWithKeys(fn ($id, $login) => [mb_strtolower($login) => $id]);

        $projects = Project::withoutGlobalScopes()->where('organization_id', $org->id)
            ->whereNotNull('github_owner')->where('github_owner', '!=', '')
            ->whereNotNull('github_repo')->where('github_repo', '!=', '')
            ->get();

        if ($projects->isEmpty()) {
            return $this->finish($org, array_merge($result, ['status' => 'failed', 'messages' => ['No projects are linked to a GitHub repository.']]));
        }
        if ($people->isEmpty()) {
            $result['messages'][] = 'Nobody has added their GitHub username yet (Profile → GitHub), so no activity could be matched.';
        }

        $api      = new GitHubApi($token);
        $since    = now()->subDays($days);
        $active   = [];
        $analyzer = app(GitHubAnalyzer::class);

        foreach ($projects as $project) {
            $repo = "{$project->github_owner}/{$project->github_repo}";
            try {
                if ($source === 'platform' && $api->repository($project->github_owner, $project->github_repo)['private']) {
                    $result['messages'][] = "{$repo} is private — add your organization's own GitHub token to sync it.";
                    continue;
                }

                foreach ($api->commits($project->github_owner, $project->github_repo, $since) as $commit) {
                    $userId = $people[mb_strtolower($commit['author']['login'] ?? '')] ?? null;
                    if (!$userId) {
                        continue;
                    }
                    $this->record($project, $userId, 'commit', (string) $commit['sha'],
                        $commit['commit']['author']['date'] ?? $commit['commit']['committer']['date'] ?? now(),
                        ['sha' => $commit['sha'], 'message' => mb_substr((string) ($commit['commit']['message'] ?? ''), 0, 500), 'url' => $commit['html_url'] ?? ''],
                        ['complexity_score' => $analyzer->analyzeComplexity([$commit])]);
                    $result['commits']++;
                    $active[$userId] = true;
                }

                foreach ($api->pullRequests($project->github_owner, $project->github_repo, $since) as $pr) {
                    $userId = $people[mb_strtolower($pr['user']['login'] ?? '')] ?? null;
                    if (!$userId) {
                        continue;
                    }
                    $meta = ['number' => $pr['number'], 'title' => mb_substr((string) ($pr['title'] ?? ''), 0, 300), 'state' => $pr['state'] ?? '', 'url' => $pr['html_url'] ?? ''];

                    if (!empty($pr['created_at']) && Carbon::parse($pr['created_at'])->gte($since)) {
                        $this->record($project, $userId, 'pr_opened', (string) $pr['number'], $pr['created_at'], $meta, ['quality_score' => 0.5]);
                        $result['prs']++;
                    }
                    if (!empty($pr['merged_at']) && Carbon::parse($pr['merged_at'])->gte($since)) {
                        $this->record($project, $userId, 'pr_merged', (string) $pr['number'], $pr['merged_at'], $meta, ['quality_score' => 1.0]);
                    }
                    $active[$userId] = true;
                }

                $result['repos']++;
            } catch (GitHubApiException $e) {
                $result['messages'][] = "{$repo}: {$e->getMessage()}";
                $result['status'] = 'partial';
                if (in_array($e->kind, ['auth', 'rate_limited'], true)) {
                    break; // the same problem would repeat for every repo
                }
            }
        }

        $result['people'] = count($active);
        // "failed" only when nothing at all was saved; otherwise it's partial
        if ($result['status'] !== 'ok' && $result['repos'] === 0 && $result['commits'] === 0 && $result['prs'] === 0) {
            $result['status'] = 'failed';
        }

        return $this->finish($org, $result);
    }

    private function record(Project $project, int $userId, string $event, string $externalId, $when, array $meta, array $scores): void
    {
        Activity::updateOrCreate(
            ['project_id' => $project->id, 'event_type' => $event, 'external_id' => $externalId],
            array_merge([
                'user_id'         => $userId,
                'organization_id' => $project->organization_id,
                'source'          => 'github',
                'metadata'        => $meta,
                'occurred_at'     => Carbon::parse($when),
            ], $scores)
        );
    }

    /** Stores the result on the organization for Settings → GitHub. */
    private function finish(Organization $org, array $result): array
    {
        $settings = $org->fresh()->settings ?? [];
        $settings['github_last_sync'] = $result;
        $org->forceFill(['settings' => $settings])->save();

        return $result;
    }
}

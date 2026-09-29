<?php

namespace Tests\Feature;

use App\Jobs\SyncOrganizationGitHub;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\GitHub\GitHubSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GitHubSyncTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $lead;
    private User $dev;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['services.github.token' => 'platform-token']);

        foreach (['owner', 'admin', 'team_lead', 'employee'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        foreach (['sync_github', 'manage_github_settings'] as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        Role::findByName('team_lead')->givePermissionTo('sync_github');
        Role::findByName('owner')->givePermissionTo(['sync_github', 'manage_github_settings']);

        $this->org  = $this->organization('Acme');
        $this->lead = $this->person('Lead', 'team_lead', $this->org, 'lead-gh');
        $this->dev  = $this->person('Developer', 'employee', $this->org, 'Dev-GH');
        Project::create(['organization_id' => $this->org->id, 'name' => 'Portal', 'github_owner' => 'acme', 'github_repo' => 'portal']);
    }

    private function organization(string $name): Organization
    {
        return Organization::create(['name' => $name, 'slug' => Str::slug($name) . '-' . Str::random(4), 'status' => 'active',
            'plan' => 'pro', 'plan_expires_at' => now()->addMonth()]);
    }

    private function person(string $name, string $role, Organization $org, ?string $github = null): User
    {
        $user = User::factory()->create(['name' => $name, 'organization_id' => $org->id, 'github_username' => $github]);
        $user->assignRole($role);

        return $user;
    }

    private function orgToken(Organization $org, string $token): void
    {
        $settings = $org->settings ?? [];
        $settings['github_token'] = encrypt($token);
        $org->update(['settings' => $settings]);
    }

    private static function commit(string $sha, string $login, int $daysAgo = 1): array
    {
        return ['sha' => $sha, 'html_url' => "https://github.com/c/{$sha}", 'author' => ['login' => $login],
            'commit' => ['message' => "feat: {$sha}", 'author' => ['date' => now()->subDays($daysAgo)->toIso8601String()]]];
    }

    private static function pr(int $number, string $login, ?int $mergedDaysAgo = null, int $createdDaysAgo = 3): array
    {
        return ['number' => $number, 'title' => "PR {$number}", 'state' => $mergedDaysAgo !== null ? 'closed' : 'open',
            'html_url' => "https://github.com/p/{$number}", 'user' => ['login' => $login],
            'created_at' => now()->subDays($createdDaysAgo)->toIso8601String(),
            'updated_at' => now()->subDays(min($createdDaysAgo, $mergedDaysAgo ?? $createdDaysAgo))->toIso8601String(),
            'merged_at' => $mergedDaysAgo !== null ? now()->subDays($mergedDaysAgo)->toIso8601String() : null];
    }

    private function fakeRepo(array $commits, array $prs = [], bool $private = false, array $extraCommitPages = []): void
    {
        // A fixed response unless the test needs several pages, so repeated syncs see the same data
        $commitPages = Http::response($commits, 200, ['X-RateLimit-Remaining' => '4000']);
        if ($extraCommitPages) {
            $commitPages = Http::sequence()->push($commits, 200, ['X-RateLimit-Remaining' => '4000']);
            foreach ($extraCommitPages as $page) {
                $commitPages->push($page, 200, ['X-RateLimit-Remaining' => '3999']);
            }
        }

        Http::fake([
            'api.github.com/repos/acme/portal/commits*' => $commitPages,
            'api.github.com/repos/acme/portal/pulls*'   => Http::response($prs, 200, ['X-RateLimit-Remaining' => '3998']),
            'api.github.com/repos/acme/portal'          => Http::response(['private' => $private, 'full_name' => 'acme/portal'], 200),
        ]);
    }

    public function test_sync_covers_everyone_in_the_org_not_just_the_person_clicking(): void
    {
        $this->orgToken($this->org, 'org-token');
        $this->fakeRepo([self::commit('a1', 'dev-gh'), self::commit('a2', 'LEAD-GH'), self::commit('a3', 'stranger')]);

        $this->actingAs($this->lead)->post(route('dashboard.sync-github'))->assertSessionHas('success');

        $this->assertSame(1, Activity::where('user_id', $this->dev->id)->where('event_type', 'commit')->count(), 'Developer (employee) synced by the lead');
        $this->assertSame(1, Activity::where('user_id', $this->lead->id)->where('event_type', 'commit')->count());
        $this->assertSame(2, Activity::count(), 'Unknown authors are ignored');
        $this->assertSame($this->org->id, Activity::first()->organization_id);
        Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer org-token'));
    }

    public function test_pull_requests_are_stored_as_opened_and_merged(): void
    {
        $this->orgToken($this->org, 'org-token');
        $this->fakeRepo([], [self::pr(7, 'dev-gh', mergedDaysAgo: 1), self::pr(8, 'dev-gh'), self::pr(9, 'dev-gh', createdDaysAgo: 60, mergedDaysAgo: 2)]);

        $result = app(GitHubSyncService::class)->syncOrganization($this->org);

        $this->assertEqualsCanonicalizing(['7', '8'], Activity::where('event_type', 'pr_opened')->pluck('external_id')->all());
        $this->assertEqualsCanonicalizing(['7', '9'], Activity::where('event_type', 'pr_merged')->pluck('external_id')->all());
        $this->assertSame(0, Activity::where('event_type', 'pull_request')->count());
        $this->assertSame(2, $result['prs']);
    }

    public function test_syncing_twice_does_not_duplicate(): void
    {
        $this->orgToken($this->org, 'org-token');
        $this->fakeRepo([self::commit('a1', 'dev-gh')], [self::pr(7, 'dev-gh', 1)]);

        app(GitHubSyncService::class)->syncOrganization($this->org);
        app(GitHubSyncService::class)->syncOrganization($this->org);

        $this->assertSame(3, Activity::count()); // 1 commit + pr_opened + pr_merged
    }

    public function test_commits_are_paginated(): void
    {
        $this->orgToken($this->org, 'org-token');
        $page1 = array_map(fn ($i) => self::commit("p1-{$i}", 'dev-gh'), range(1, 100));
        $this->fakeRepo($page1, [], false, [[self::commit('p2-1', 'dev-gh')]]);

        $result = app(GitHubSyncService::class)->syncOrganization($this->org);

        $this->assertSame(101, $result['commits']);
    }

    public function test_platform_token_never_reads_private_repositories(): void
    {
        // No organization token → shared platform token → public repos only
        $this->fakeRepo([self::commit('secret', 'dev-gh')], [], private: true);

        $result = app(GitHubSyncService::class)->syncOrganization($this->org);

        $this->assertSame(0, Activity::count());
        $this->assertStringContainsString('is private', implode(' ', $result['messages']));
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/commits'));
    }

    public function test_platform_token_syncs_public_repositories(): void
    {
        $this->fakeRepo([self::commit('open', 'dev-gh')], [], private: false);

        $result = app(GitHubSyncService::class)->syncOrganization($this->org);

        $this->assertSame('platform', $result['token_source']);
        $this->assertSame(1, Activity::count());
    }

    public function test_bad_token_is_reported_not_crashing(): void
    {
        $this->orgToken($this->org, 'expired-token');
        Http::fake(['api.github.com/*' => Http::response(['message' => 'Bad credentials'], 401)]);

        $result = app(GitHubSyncService::class)->syncOrganization($this->org);

        $this->assertSame('failed', $result['status']);
        $this->assertStringContainsString('rejected the token', implode(' ', $result['messages']));
        $this->assertSame('failed', $this->org->fresh()->settings['github_last_sync']['status']);
    }

    public function test_stops_before_the_rate_limit_runs_out(): void
    {
        $this->orgToken($this->org, 'org-token');
        Project::create(['organization_id' => $this->org->id, 'name' => 'Second', 'github_owner' => 'acme', 'github_repo' => 'second']);
        Http::fake([
            'api.github.com/repos/acme/portal/commits*' => Http::response([self::commit('a1', 'dev-gh')], 200, ['X-RateLimit-Remaining' => '3']),
            'api.github.com/*'                          => Http::response([], 200, ['X-RateLimit-Remaining' => '3']),
        ]);

        $result = app(GitHubSyncService::class)->syncOrganization($this->org);

        $this->assertSame('partial', $result['status']);
        $this->assertStringContainsString('rate limit', implode(' ', $result['messages']));
    }

    public function test_two_organizations_on_the_same_repo_only_get_their_own_people(): void
    {
        $rival      = $this->organization('Rival');
        $rivalDev   = $this->person('Rival Dev', 'employee', $rival, 'rival-gh');
        Project::create(['organization_id' => $rival->id, 'name' => 'Portal copy', 'github_owner' => 'acme', 'github_repo' => 'portal']);
        $this->orgToken($this->org, 'org-token');
        $this->orgToken($rival, 'rival-token');
        $this->fakeRepo([self::commit('a1', 'dev-gh'), self::commit('b1', 'rival-gh')]);

        app(GitHubSyncService::class)->syncOrganization($this->org);
        app(GitHubSyncService::class)->syncOrganization($rival);

        $this->assertSame(['a1'], Activity::where('organization_id', $this->org->id)->pluck('external_id')->all());
        $this->assertSame(['b1'], Activity::where('organization_id', $rival->id)->pluck('external_id')->all());
        $this->assertSame($rivalDev->id, Activity::where('organization_id', $rival->id)->value('user_id'));
    }

    public function test_employees_cannot_trigger_a_sync(): void
    {
        $this->actingAs($this->dev)->post(route('dashboard.sync-github'))->assertForbidden();
    }

    public function test_nightly_command_queues_eligible_organizations(): void
    {
        Queue::fake();
        $this->organization('No Repos');

        $this->artisan('github:sync')->assertSuccessful();

        Queue::assertPushed(SyncOrganizationGitHub::class, 1);
        Queue::assertPushed(SyncOrganizationGitHub::class, fn ($job) => $job->organization->id === $this->org->id);
    }

    public function test_token_is_checked_with_github_before_saving(): void
    {
        $owner = $this->person('Owner', 'owner', $this->org);
        Http::fake(['api.github.com/user' => Http::sequence()
            ->push(['message' => 'Bad credentials'], 401)
            ->push(['login' => 'acme-bot'], 200)]);

        $this->actingAs($owner)->post(route('settings.github.token'), ['github_token' => str_repeat('x', 30)])
            ->assertSessionHasErrors('github_token');
        $this->assertArrayNotHasKey('github_token', $this->org->fresh()->settings ?? []);

        $this->actingAs($owner)->post(route('settings.github.token'), ['github_token' => 'github_pat_' . str_repeat('y', 30)])
            ->assertSessionHas('success');
        $settings = $this->org->fresh()->settings;
        $this->assertSame('github_pat_' . str_repeat('y', 30), decrypt($settings['github_token']));
        $this->assertSame('acme-bot', $settings['github_token_login']);
        $this->assertSame(1, AuditLog::where('action', 'github.token_updated')->count());

        $this->actingAs($owner)->get(route('settings.github'))->assertOk()->assertSee('Connected with your token (acme-bot)');
    }

    public function test_old_pull_request_rows_are_converted(): void
    {
        $project = Project::first();
        foreach ([['1', 1.0], ['2', 0.5]] as [$number, $quality]) {
            Activity::create(['user_id' => $this->dev->id, 'project_id' => $project->id, 'organization_id' => $this->org->id,
                'source' => 'github', 'event_type' => 'pull_request', 'external_id' => $number, 'metadata' => [],
                'quality_score' => $quality, 'occurred_at' => now()]);
        }

        (require database_path('migrations/2026_09_30_000005_split_pull_request_activities.php'))->up();

        $this->assertSame('pr_merged', Activity::where('external_id', '1')->value('event_type'));
        $this->assertSame('pr_opened', Activity::where('external_id', '2')->value('event_type'));
    }
}

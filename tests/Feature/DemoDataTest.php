<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Announcement;
use App\Models\Blocker;
use App\Models\Department;
use App\Models\LeaveApplication;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkLog;
use App\Services\FairnessEngine;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DemoDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'owner', 'admin', 'hr', 'team_lead', 'employee', 'viewer'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    /** A real customer that must survive every demo operation untouched. */
    private function realCustomer(): array
    {
        $org  = Organization::create(['name' => 'Real Customer', 'slug' => 'real-customer', 'status' => 'active', 'plan' => 'pro']);
        $user = User::factory()->create(['organization_id' => $org->id, 'email' => 'boss@real.test']);
        $user->assignRole('owner');
        $project = Project::create(['organization_id' => $org->id, 'name' => 'Real project']);
        WorkLog::create(['user_id' => $user->id, 'organization_id' => $org->id, 'log_date' => now()->toDateString(), 'category' => 'Dev', 'title' => 'Real work']);
        Task::create(['project_id' => $project->id, 'assigned_to' => $user->id, 'assigned_by' => $user->id, 'title' => 'Real task']);

        return [$org, $user];
    }

    private function counts(): array
    {
        return [
            'orgs' => Organization::count(), 'users' => User::count(), 'depts' => Department::count(),
            'teams' => Team::count(), 'tasks' => Task::count(), 'logs' => WorkLog::count(),
            'leaves' => LeaveApplication::count(), 'announcements' => Announcement::count(),
            'blockers' => Blocker::count(), 'payments' => Payment::count(), 'activity' => Activity::count(),
        ];
    }

    public function test_seeder_creates_three_demo_orgs_with_a_login_per_role(): void
    {
        $this->seed(DemoDataSeeder::class);

        $orgs = Organization::demo()->get()->keyBy('slug');
        $this->assertCount(3, $orgs);
        $this->assertSame('free', $orgs['demo-startup']->plan);
        $this->assertSame('pro', $orgs['demo-agency']->plan);
        $this->assertTrue($orgs['demo-agency']->plan_expires_at->isFuture());
        $this->assertTrue($orgs['demo-suspended-co']->isSuspended());

        foreach ($orgs as $slug => $org) {
            foreach (DemoDataSeeder::ROLES as $role) {
                $user = User::where('email', "{$role}@{$slug}.test")->first();
                $this->assertNotNull($user, "{$role}@{$slug}.test missing");
                $this->assertTrue($user->hasRole($role));
                $this->assertSame($org->id, $user->organization_id);
                $this->assertTrue($user->is_active);
                $this->assertNotNull($user->email_verified_at);
                $this->assertTrue(Hash::check('Test@12345', $user->password));
            }
        }

        $active = fn ($slug) => User::where('organization_id', $orgs[$slug]->id)->where('onboarding_status', 'active')->count();
        $this->assertSame(8, $active('demo-startup'));
        $this->assertSame(20, $active('demo-agency'));
    }

    public function test_active_orgs_have_realistic_data(): void
    {
        $this->seed(DemoDataSeeder::class);
        $agency = Organization::where('slug', 'demo-agency')->first();

        $this->assertGreaterThanOrEqual(2, Team::where('organization_id', $agency->id)->whereNotNull('team_lead_id')->count());
        $this->assertSame(['approved', 'pending', 'rejected'],
            LeaveApplication::where('organization_id', $agency->id)->distinct()->orderBy('status')->pluck('status')->all());
        $this->assertSame(2, User::where('organization_id', $agency->id)->where('onboarding_status', 'pending')->count());
        $this->assertGreaterThan(0, Task::whereHas('project', fn ($q) => $q->where('organization_id', $agency->id))->where('status', 'done')->count());
        $this->assertSame(2, Payment::where('organization_id', $agency->id)->where('status', 'paid')->count());
        $this->assertSame(0, Payment::where('organization_id', Organization::where('slug', 'demo-startup')->value('id'))->count());

        // Seeded blockers are enough for the Fairness Engine to raise flags
        $types = collect((new FairnessEngine())->analyzeOrganization($agency->id))->pluck('type')->all();
        $this->assertContains('unresolved_blockers', $types);
        $this->assertContains('meeting_overload', $types);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(DemoDataSeeder::class);
        $first = $this->counts();
        $ownerId = User::where('email', 'owner@demo-agency.test')->value('id');

        $this->seed(DemoDataSeeder::class);

        $this->assertSame($first, $this->counts());
        $this->assertSame($ownerId, User::where('email', 'owner@demo-agency.test')->value('id'), 'Logins keep the same account');
    }

    public function test_seeder_does_not_touch_real_data(): void
    {
        [$org, $user] = $this->realCustomer();
        $before = [WorkLog::where('organization_id', $org->id)->count(), Task::count()];

        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $this->assertSame('Real Customer', $org->fresh()->name);
        $this->assertFalse($org->fresh()->is_demo);
        $this->assertSame($before[0], WorkLog::where('organization_id', $org->id)->count());
        $this->assertSame(1, Task::whereHas('project', fn ($q) => $q->where('organization_id', $org->id))->count());
        $this->assertTrue($user->fresh()->hasRole('owner'));
    }

    public function test_seeder_refuses_when_a_demo_slug_belongs_to_a_real_org(): void
    {
        Organization::create(['name' => 'Someone Real', 'slug' => 'demo-agency', 'status' => 'active', 'plan' => 'pro']);

        $this->expectException(\RuntimeException::class);
        $this->seed(DemoDataSeeder::class);
    }

    public function test_purge_removes_only_demo_data(): void
    {
        [$org, $user] = $this->realCustomer();
        $this->seed(DemoDataSeeder::class);

        $this->artisan('demo:purge')
            ->expectsConfirmation('Delete all of the above?', 'yes')
            ->assertSuccessful();

        $this->assertSame(0, Organization::demo()->count());
        $this->assertSame(0, User::where('email', 'like', '%@demo-%.test')->count());
        $this->assertSame(1, Organization::count());
        $this->assertSame(1, User::count());
        $this->assertSame(1, Task::count());
        $this->assertSame(1, WorkLog::count());
        $this->assertSame(0, LeaveApplication::count());
        $this->assertSame(0, Payment::count());
        $this->assertSame(0, Activity::count());
        $this->assertTrue($user->fresh()->hasRole('owner'));
        $this->assertSame('Real Customer', $org->fresh()->name);
    }

    public function test_purge_can_be_cancelled(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->artisan('demo:purge')
            ->expectsConfirmation('Delete all of the above?', 'no')
            ->expectsOutput('Cancelled. Nothing was deleted.')
            ->assertSuccessful();

        $this->assertSame(3, Organization::demo()->count());
    }

    public function test_seeder_is_not_run_by_database_seeder(): void
    {
        $this->assertStringNotContainsString('DemoDataSeeder', file_get_contents(database_path('seeders/DatabaseSeeder.php')));
    }

    public function test_suspended_demo_org_users_cannot_log_in(): void
    {
        $this->seed(DemoDataSeeder::class);

        $this->post('/login', ['email' => 'employee@demo-suspended-co.test', 'password' => 'Test@12345'])
            ->assertSessionHasErrors(['email' => Organization::SUSPENDED_MESSAGE]);
        $this->assertGuest();
    }

    public function test_admin_only_sees_pending_signups_from_own_org(): void
    {
        [$org, $realOwner] = $this->realCustomer();
        \Spatie\Permission\Models\Permission::findOrCreate('manage_roles', 'web');
        $realOwner->givePermissionTo('manage_roles');
        $this->seed(DemoDataSeeder::class);

        $demoPending = User::where('email', 'pending1@demo-agency.test')->first();
        $ownPending  = User::factory()->create(['organization_id' => $org->id, 'onboarding_status' => 'pending', 'email' => 'new@real.test']);

        // A real org's admin must not see or act on another org's pending sign-ups
        $this->actingAs($realOwner)->get(route('admin.pending'))
            ->assertOk()
            ->assertSee('new@real.test')
            ->assertDontSee('pending1@demo-agency.test');

        $this->actingAs($realOwner)
            ->post(route('admin.approve', $demoPending->id), ['role' => 'employee'])
            ->assertNotFound();
        $this->assertSame('pending', $demoPending->fresh()->onboarding_status);

        // ...and cannot approve anyone straight into super_admin
        $this->actingAs($realOwner)
            ->post(route('admin.approve', $ownPending->id), ['role' => 'super_admin'])
            ->assertSessionHasErrors('role');
        $this->assertFalse($ownPending->fresh()->hasRole('super_admin'));
    }
}

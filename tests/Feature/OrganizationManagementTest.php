<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use RefreshDatabase;

    private function org(array $attrs = []): Organization
    {
        return Organization::create(array_merge([
            'name'   => 'Acme ' . Str::random(4),
            'slug'   => 'acme-' . Str::random(6),
            'status' => 'active',
            'plan'   => 'free',
        ], $attrs));
    }

    private function userWithRole(string $role, ?Organization $org = null, array $attrs = []): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(array_merge(['organization_id' => $org?->id], $attrs));
        $user->assignRole($role);

        return $user;
    }

    private function superAdmin(): User
    {
        return $this->userWithRole('super_admin');
    }

    // ── Blocking suspended organizations ─────────────────────────────────────

    public function test_member_of_suspended_org_is_signed_out_on_next_request(): void
    {
        $org    = $this->org(['status' => 'suspended']);
        $member = $this->userWithRole('employee', $org);

        $this->actingAs($member)->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => Organization::SUSPENDED_MESSAGE]);

        $this->assertGuest();
    }

    public function test_member_of_suspended_org_gets_json_403_on_background_requests(): void
    {
        $org    = $this->org(['status' => 'suspended']);
        $member = $this->userWithRole('employee', $org);

        $this->actingAs($member)->getJson('/notifications/count')
            ->assertStatus(403)
            ->assertJson(['message' => Organization::SUSPENDED_MESSAGE]);
    }

    public function test_member_of_suspended_org_cannot_log_in(): void
    {
        $org    = $this->org(['status' => 'suspended']);
        $member = $this->userWithRole('employee', $org, ['email' => 'member@acme.test']);

        $this->post('/login', ['email' => 'member@acme.test', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => Organization::SUSPENDED_MESSAGE]);

        $this->assertGuest();
    }

    public function test_member_of_active_org_can_log_in(): void
    {
        $org = $this->org();
        $this->userWithRole('employee', $org, ['email' => 'member@acme.test']);

        $this->post('/login', ['email' => 'member@acme.test', 'password' => 'password'])
            ->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }

    public function test_super_admin_is_never_blocked_even_inside_a_suspended_org(): void
    {
        $org = $this->org(['status' => 'suspended']);
        $sa  = $this->userWithRole('super_admin', $org, ['email' => 'sa@platform.test']);

        $this->actingAs($sa)->get(route('settings.organizations.index'))->assertOk();

        auth()->logout();
        $this->post('/login', ['email' => 'sa@platform.test', 'password' => 'password'])
            ->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($sa);
    }

    // ── Suspend / activate ───────────────────────────────────────────────────

    public function test_super_admin_can_suspend_with_reason_and_it_is_audited(): void
    {
        $sa  = $this->superAdmin();
        $org = $this->org();

        $this->actingAs($sa)
            ->from(route('settings.organizations.index'))
            ->post(route('settings.organizations.suspend', $org), ['reason' => 'Unpaid invoices for three months'])
            ->assertRedirect(route('settings.organizations.index'))
            ->assertSessionHas('success');

        $org->refresh();
        $this->assertSame('suspended', $org->status);
        $this->assertSame($sa->id, $org->suspended_by);
        $this->assertSame('Unpaid invoices for three months', $org->suspension_reason);
        $this->assertNotNull($org->suspended_at);

        $log = AuditLog::where('action', 'organization.suspended')->sole();
        $this->assertSame($sa->id, $log->user_id);
        $this->assertSame($org->id, $log->entity_id);
        $this->assertSame('active', $log->old_values['status']);
        $this->assertSame('Unpaid invoices for three months', $log->new_values['reason']);
        $this->assertNotNull($log->created_at);
    }

    public function test_suspending_requires_a_reason(): void
    {
        $org = $this->org();

        $this->actingAs($this->superAdmin())
            ->post(route('settings.organizations.suspend', $org), ['reason' => 'short'])
            ->assertSessionHasErrors('reason');

        $this->assertSame('active', $org->fresh()->status);
        $this->assertSame(0, AuditLog::count());
    }

    public function test_super_admin_can_activate_and_individual_deactivations_are_kept(): void
    {
        $sa          = $this->superAdmin();
        $org         = $this->org(['status' => 'suspended', 'suspension_reason' => 'Abuse report', 'suspended_at' => now()]);
        $active      = $this->userWithRole('employee', $org, ['is_active' => true]);
        $deactivated = $this->userWithRole('employee', $org, ['is_active' => false]);

        $this->actingAs($sa)
            ->post(route('settings.organizations.activate', $org), ['note' => 'Resolved with owner'])
            ->assertSessionHas('success');

        $org->refresh();
        $this->assertSame('active', $org->status);
        $this->assertNull($org->suspension_reason);
        $this->assertTrue($active->fresh()->is_active);
        $this->assertFalse($deactivated->fresh()->is_active);

        $log = AuditLog::where('action', 'organization.activated')->sole();
        $this->assertSame($sa->id, $log->user_id);
        $this->assertSame('Resolved with owner', $log->new_values['reason']);
        $this->assertSame('Abuse report', $log->new_values['previous_suspension_reason']);
    }

    public function test_activated_org_member_can_use_the_app_again(): void
    {
        $sa     = $this->superAdmin();
        $org    = $this->org();
        $member = $this->userWithRole('employee', $org);

        $this->actingAs($sa)->post(route('settings.organizations.suspend', $org), ['reason' => 'Temporary security review']);
        $this->actingAs($member)->getJson('/notifications/count')->assertForbidden();

        $this->actingAs($sa)->post(route('settings.organizations.activate', $org));
        // fresh(): a real request reloads the user; the test object caches the old organization
        $this->actingAs($member->fresh())->getJson('/notifications/count')->assertOk();
    }

    // ── Access control ───────────────────────────────────────────────────────

    public function test_non_super_admin_gets_403_on_every_panel_route(): void
    {
        $org   = $this->org();
        $owner = $this->userWithRole('owner', $org);
        $other = $this->org();

        $this->actingAs($owner)->get(route('settings.organizations.index'))->assertForbidden();
        $this->actingAs($owner)->get(route('settings.organizations.show', $other))->assertForbidden();
        $this->actingAs($owner)->post(route('settings.organizations.suspend', $other), ['reason' => 'Trying to suspend a rival'])->assertForbidden();
        $this->actingAs($owner)->post(route('settings.organizations.activate', $other))->assertForbidden();

        $this->assertSame('active', $other->fresh()->status);
        $this->assertSame(0, AuditLog::count());
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get(route('settings.organizations.index'))->assertRedirect(route('login'));
    }

    // ── Pages ────────────────────────────────────────────────────────────────

    public function test_list_shows_owner_plan_and_user_count_and_supports_search(): void
    {
        $acme  = $this->org(['name' => 'Acme Robotics']);
        $this->userWithRole('owner', $acme, ['email' => 'founder@acme.test']);
        $this->userWithRole('employee', $acme);
        $this->org(['name' => 'Globex Foods']);

        $this->actingAs($this->superAdmin())->get(route('settings.organizations.index'))
            ->assertOk()
            ->assertSee('Acme Robotics')
            ->assertSee('founder@acme.test')
            ->assertSee('Globex Foods');

        // Assert on the listed rows: the Super Admin org switcher in the top bar names every org
        $this->actingAs($this->superAdmin())->get(route('settings.organizations.index', ['q' => 'founder@acme']))
            ->assertOk()
            ->assertViewHas('orgs', fn ($orgs) => $orgs->pluck('name')->all() === ['Acme Robotics']);

        $this->actingAs($this->superAdmin())->get(route('settings.organizations.index', ['status' => 'suspended']))
            ->assertOk()
            ->assertViewHas('orgs', fn ($orgs) => $orgs->isEmpty());
    }

    public function test_empty_platform_shows_empty_state(): void
    {
        $this->actingAs($this->superAdmin())->get(route('settings.organizations.index'))
            ->assertOk()
            ->assertSee('No organizations yet');
    }

    public function test_detail_page_shows_admin_info_but_not_private_employee_data(): void
    {
        $org = $this->org(['status' => 'suspended', 'suspension_reason' => 'Chargeback dispute', 'suspended_at' => now()]);
        $this->userWithRole('owner', $org, ['email' => 'owner@acme.test']);
        $this->userWithRole('employee', $org, [
            'email'  => 'staff@acme.test',
            'phone'  => '9811122233',
            'skills' => ['Negotiation'],
        ]);

        $this->actingAs($this->superAdmin())->get(route('settings.organizations.show', $org))
            ->assertOk()
            ->assertSee('owner@acme.test')
            ->assertSee('staff@acme.test')
            ->assertSee('Chargeback dispute')
            ->assertSee('Recent account activity')
            ->assertDontSee('9811122233')
            ->assertDontSee('Negotiation');
    }

    // ── Older Super Admin endpoints share the same rules ─────────────────────

    public function test_legacy_ajax_suspend_requires_reason_and_is_audited(): void
    {
        $sa     = $this->superAdmin();
        $org    = $this->org();
        $member = $this->userWithRole('employee', $org, ['is_active' => true]);

        $this->actingAs($sa)->patchJson(route('superadmin.organizations.suspend-ajax', $org->id))
            ->assertStatus(422);
        $this->assertSame('active', $org->fresh()->status);

        $this->actingAs($sa)->patchJson(route('superadmin.organizations.suspend-ajax', $org->id), ['reason' => 'Payment fraud investigation'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('suspended', $org->fresh()->status);
        $this->assertTrue($member->fresh()->is_active, 'Suspension must not change members\' own is_active flag');
        $this->assertSame(1, AuditLog::where('action', 'organization.suspended')->count());
    }
}

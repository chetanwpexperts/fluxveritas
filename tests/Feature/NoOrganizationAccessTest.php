<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Users without an organization (e.g. the first super_admin on a fresh
 * production DB) must be redirected with a message — never a 500 or 404.
 */
class NoOrganizationAccessTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role, array $attrs = []): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create(array_merge(['organization_id' => null], $attrs));
        $user->assignRole($role);

        return $user;
    }

    public function test_super_admin_without_org_is_redirected_from_github_settings_and_billing(): void
    {
        $sa = $this->userWithRole('super_admin');

        foreach (['/settings/github', '/billing'] as $url) {
            $this->actingAs($sa)->get($url)
                ->assertRedirect(route('superadmin.organizations'))
                ->assertSessionHas('info');
        }
    }

    public function test_super_admin_without_org_gets_json_message_for_background_requests(): void
    {
        $sa = $this->userWithRole('super_admin');

        $this->actingAs($sa)->getJson('/settings/github')
            ->assertStatus(409)
            ->assertJsonStructure(['message']);
    }

    public function test_super_admin_without_org_can_still_use_platform_pages(): void
    {
        $sa = $this->userWithRole('super_admin');

        $this->actingAs($sa)->get('/superadmin/organizations')->assertOk();
        $this->actingAs($sa)->get('/settings/platform')->assertOk();
    }

    public function test_redirect_message_is_shown_on_the_organizations_page(): void
    {
        $sa = $this->userWithRole('super_admin');

        $this->actingAs($sa)->followingRedirects()->get('/settings/github')
            ->assertOk()
            ->assertSee('That page belongs to an organization');
    }

    public function test_owner_setting_up_org_is_sent_to_create_it(): void
    {
        $owner = $this->userWithRole('owner', ['onboarding_type' => 'org_creator']);

        foreach (['/settings/github', '/billing'] as $url) {
            $this->actingAs($owner)->get($url)->assertRedirect(route('organization.create'));
        }
    }

    public function test_employee_without_org_is_logged_out_with_a_message(): void
    {
        $employee = $this->userWithRole('employee', ['onboarding_type' => 'team_member']);

        foreach (['/settings/github', '/billing'] as $url) {
            $this->actingAs($employee)->get($url)
                ->assertRedirect('/login')
                ->assertSessionHas('error');
        }
    }
}

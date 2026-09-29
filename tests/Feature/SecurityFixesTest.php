<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\IncrementPolicy;
use App\Models\IncrementReview;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use App\Services\FairnessCertificateService;
use App\Services\MagicActionTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityFixesTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $owner;
    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['super_admin', 'owner', 'admin', 'hr', 'employee'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->org      = $this->organization('Acme');
        $this->owner    = $this->person('Acme Owner', 'owner', $this->org);
        $this->employee = $this->person('Staff Member', 'employee', $this->org);
    }

    private function organization(string $name, array $attrs = []): Organization
    {
        return Organization::create(array_merge(['name' => $name, 'slug' => Str::slug($name) . '-' . Str::random(4),
            'status' => 'active', 'plan' => 'pro', 'plan_expires_at' => now()->addMonth()], $attrs));
    }

    private function person(string $name, string $role, Organization $org): User
    {
        $user = User::factory()->create(['name' => $name, 'organization_id' => $org->id]);
        $user->assignRole($role);

        return $user;
    }

    private function review(?Organization $org = null, ?User $employee = null, float $recommended = 12.5, string $status = 'pending'): IncrementReview
    {
        $org ??= $this->org;
        $policy = IncrementPolicy::withoutGlobalScopes()->create(['organization_id' => $org->id, 'name' => 'Policy', 'max_increment_percent' => 10,
            'review_period' => 'Annual', 'review_month' => 12, 'minimum_months_required' => 3, 'minimum_score_for_increment' => 40,
            'is_active' => true, 'created_by' => $this->owner->id]);

        return IncrementReview::withoutGlobalScopes()->create(['user_id' => ($employee ?? $this->employee)->id, 'organization_id' => $org->id,
            'policy_id' => $policy->id, 'review_year' => 2026, 'review_period' => 'Annual', 'avg_score' => 81.2,
            'recommended_increment' => $recommended, 'status' => $status]);
    }

    private function link(IncrementReview $review, ?User $for = null): string
    {
        return app(MagicActionTokenService::class)->generateToken($for ?? $this->owner, 'approve_increment', ['review_id' => $review->id]);
    }

    // ── Email approval links ─────────────────────────────────────────────────

    public function test_opening_the_link_only_shows_a_confirmation(): void
    {
        $review = $this->review();

        $this->get(route('action.execute', ['token' => $this->link($review)]))
            ->assertOk()
            ->assertSee("Approve Staff Member's increment?", false)
            ->assertSee('10%'); // recommendation 12.5% capped at the policy maximum

        $this->assertSame('pending', $review->fresh()->status, 'A GET (e.g. an email link scanner) must not approve');
    }

    public function test_confirming_approves_like_the_app_and_only_once(): void
    {
        $review = $this->review();
        $token  = $this->link($review);

        $this->post(route('action.execute.confirm'), ['token' => $token])->assertOk()->assertSee('Increment approved');

        $review->refresh();
        $this->assertSame('ceo_approved', $review->status);
        $this->assertEquals(10.0, $review->final_increment);
        $this->assertNotNull($review->ceo_approved_at);
        $this->assertSame(1, AuditLog::where('action', 'increment.approved_via_email')->where('user_id', $this->owner->id)->count());

        $this->post(route('action.execute.confirm'), ['token' => $token])->assertStatus(410);
        $this->get(route('action.execute', ['token' => $token]))->assertStatus(410);
    }

    public function test_links_without_single_use_id_or_past_expiry_are_refused(): void
    {
        $review = $this->review();
        $legacy = Crypt::encrypt(json_encode(['user_id' => $this->owner->id, 'org_id' => $this->org->id,
            'action_type' => 'approve_increment', 'params' => ['review_id' => $review->id], 'expires_at' => now()->addDay()->timestamp]));

        $this->post(route('action.execute.confirm'), ['token' => $legacy])->assertStatus(410);

        $expired = app(MagicActionTokenService::class)->generateToken($this->owner, 'approve_increment', ['review_id' => $review->id], 1);
        $this->travel(5)->minutes();
        $this->post(route('action.execute.confirm'), ['token' => $expired])->assertStatus(410);

        $this->assertSame('pending', $review->fresh()->status);
    }

    public function test_approver_who_lost_the_owner_role_cannot_use_the_link(): void
    {
        $review = $this->review();
        $token  = $this->link($review);
        $this->owner->syncRoles(['employee']);

        $this->post(route('action.execute.confirm'), ['token' => $token])->assertStatus(410)->assertSee('no longer approve');
        $this->assertSame('pending', $review->fresh()->status);
    }

    public function test_link_cannot_approve_another_organizations_review(): void
    {
        $rival  = $this->organization('Rival');
        $review = $this->review($rival, $this->person('Rival Staff', 'employee', $rival));

        $this->post(route('action.execute.confirm'), ['token' => $this->link($review)])->assertStatus(410);
        $this->assertSame('pending', $review->fresh()->status);
    }

    public function test_in_app_approval_uses_the_same_rules(): void
    {
        $review = $this->review(recommended: 8);

        $this->actingAs($this->owner)->post(route('increment.approve', $review->id), ['final_increment' => 2])
            ->assertSessionHasErrors('override_reason');

        $this->actingAs($this->owner)->post(route('increment.approve', $review->id), ['final_increment' => 7.5])
            ->assertSessionHas('success');
        $this->assertSame('ceo_approved', $review->fresh()->status);

        $this->actingAs($this->owner)->post(route('increment.approve', $review->id), ['final_increment' => 7.5])
            ->assertSessionHasErrors('error');
    }

    // ── Fairness certificate / badge ─────────────────────────────────────────

    public function test_numeric_badge_url_no_longer_exists(): void
    {
        $this->get("/org/{$this->org->id}/fairness-badge")->assertNotFound();
        $this->get('/org/999999/fairness-badge')->assertNotFound();
    }

    public function test_certificate_only_for_organizations_that_opted_in(): void
    {
        $token = app(FairnessCertificateService::class)->generateCertificateToken($this->org);

        $this->get(route('fairness.verify', $token))->assertOk()->assertSee('Certificate not found');

        $this->org->update(['settings' => ['fairness_certificate_public' => true]]);
        $this->get(route('fairness.verify', $token))
            ->assertOk()
            ->assertSee('Acme')
            ->assertDontSee('Unbiased')
            ->assertDontSee('ACTIVE EMPLOYEES');

        $this->org->update(['status' => 'suspended']);
        $this->get(route('fairness.verify', $token))->assertSee('Certificate not found');
    }

    // ── Leave export ─────────────────────────────────────────────────────────

    public function test_leave_export_neutralises_formulas(): void
    {
        $hr   = $this->person('HR Person', 'hr', $this->org);
        $type = LeaveType::create(['organization_id' => $this->org->id, 'name' => 'Casual', 'code' => 'CL', 'days_per_year' => 12, 'is_active' => true]);
        LeaveApplication::create(['user_id' => $this->employee->id, 'leave_type_id' => $type->id, 'organization_id' => $this->org->id,
            'from_date' => now()->toDateString(), 'to_date' => now()->toDateString(), 'days' => 1,
            'reason' => '=HYPERLINK("http://evil.test","click")', 'status' => 'pending']);

        $csv = $this->actingAs($hr)->get(route('leaves.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString(',=HYPERLINK', $csv);
    }

    // ── Directory buttons ────────────────────────────────────────────────────

    public function test_directory_only_shows_links_people_can_open(): void
    {
        Permission::findOrCreate('invite_members', 'web');
        $admin = $this->person('Admin Person', 'admin', $this->org);
        $admin->givePermissionTo('invite_members');
        $hr = $this->person('HR Person', 'hr', $this->org);

        $this->actingAs($hr)->get(route('directory.index'))->assertOk()
            ->assertDontSee('+ Invite Employee')
            ->assertSee(route('import.employees'), false);

        $this->actingAs($admin)->get(route('directory.index'))->assertOk()
            ->assertSee(route('team.invite'), false)
            ->assertDontSee(route('admin.users.create'), false);
    }

    // ── Public docs ──────────────────────────────────────────────────────────

    public function test_public_docs_no_longer_promise_the_unsent_digest(): void
    {
        $this->get(route('docs'))->assertOk()
            ->assertDontSee('127.0.0.1')
            ->assertDontSee('Magic Email Actions');
    }
}

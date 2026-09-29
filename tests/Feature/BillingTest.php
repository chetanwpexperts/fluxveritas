<?php

namespace Tests\Feature;

use App\Exceptions\BillingException;
use App\Models\Organization;
use App\Models\OrganizationModule;
use App\Models\Payment;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\BillingService;
use App\Services\ModuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Razorpay\Api\Api;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Records Razorpay calls instead of making them. */
class FakeRazorpay extends Api
{
    public array $orders  = [];
    public array $refunds = [];
    public bool $refundFails = false;

    public function __construct() {}

    public function __get($name)
    {
        $api = $this;

        return match ($name) {
            'order' => new class($api) {
                public function __construct(private FakeRazorpay $api) {}
                public function create(array $attrs): array
                {
                    $this->api->orders[] = $attrs;
                    return ['id' => 'order_' . count($this->api->orders)];
                }
            },
            'payment' => new class($api) {
                public function __construct(private FakeRazorpay $api) {}
                public function fetch(string $id): object
                {
                    return new class($this->api, $id) {
                        public function __construct(private FakeRazorpay $api, private string $id) {}
                        public function refund(array $attrs): array
                        {
                            if ($this->api->refundFails) {
                                throw new \RuntimeException('gateway down');
                            }
                            $this->api->refunds[] = ['payment' => $this->id] + $attrs;
                            return ['id' => 'rfnd_' . count($this->api->refunds), 'status' => 'pending'];
                        }
                    };
                }
            },
        };
    }
}

class BillingTest extends TestCase
{
    use RefreshDatabase;

    private FakeRazorpay $razorpay;
    private BillingService $billing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->razorpay = new FakeRazorpay();
        $this->billing  = new BillingService($this->razorpay);
        $this->app->instance(BillingService::class, $this->billing);

        config([
            'plans.gst_percent'          => 18,
            'plans.refund_window_days'   => 7,
            'plans.refund_limit_per_org' => null,
        ]);
    }

    private function org(string $plan = 'free', int $users = 3, array $attrs = []): Organization
    {
        $org = Organization::create(array_merge([
            'name'           => 'Acme',
            'slug'           => 'acme-' . Str::random(5),
            'status'         => 'active',
            'plan'           => $plan,
            'billing_status' => $plan === 'free' ? 'free' : 'active',
        ], $attrs));

        User::factory()->count($users)->create(['organization_id' => $org->id, 'is_active' => true]);

        return $org;
    }

    private function owner(Organization $org): User
    {
        Role::findOrCreate('owner', 'web');
        $owner = User::factory()->create(['organization_id' => $org->id, 'is_active' => true]);
        $owner->assignRole('owner');
        return $owner;
    }

    private function paidPayment(Organization $org, string $period = 'monthly'): Payment
    {
        $payment = $this->billing->createOrder($org, User::where('organization_id', $org->id)->first(), $period);
        return $this->billing->markPaid($payment, 'pay_' . $payment->id);
    }

    // ── Pricing ──────────────────────────────────────────────────────────────

    public function test_quote_applies_minimum_seats_and_gst(): void
    {
        $org = $this->org('free', 3);

        $monthly = $this->billing->quote($org, 'monthly');
        $this->assertSame(5, $monthly['seats']);
        $this->assertSame(5 * 19900, $monthly['subtotal']);
        $this->assertSame((int) round(5 * 19900 * 0.18), $monthly['tax']);
        $this->assertSame($monthly['subtotal'] + $monthly['tax'], $monthly['total']);

        $yearly = $this->billing->quote($org, 'yearly');
        $this->assertSame(5 * 14900 * 12, $yearly['subtotal']);
    }

    public function test_quote_bills_every_active_user_above_the_minimum(): void
    {
        $org = $this->org('free', 12);
        User::factory()->create(['organization_id' => $org->id, 'is_active' => false]);

        $this->assertSame(12, $this->billing->quote($org, 'monthly')['seats']);
    }

    public function test_inr_formatting_uses_indian_grouping(): void
    {
        $this->assertSame('₹199', BillingService::inr(19900));
        $this->assertSame('₹1,23,456', BillingService::inr(12345600));
        $this->assertSame('₹1,174.10', BillingService::inr(117410));
    }

    // ── Payment ──────────────────────────────────────────────────────────────

    public function test_order_is_created_for_the_quoted_total(): void
    {
        $org     = $this->org('free', 3);
        $payment = $this->billing->createOrder($org, User::first(), 'monthly');

        $this->assertSame('created', $payment->status);
        $this->assertSame($this->billing->quote($org, 'monthly')['total'], $this->razorpay->orders[0]['amount']);
        $this->assertSame('INR', $this->razorpay->orders[0]['currency']);
    }

    public function test_paying_activates_pro_and_is_safe_to_repeat(): void
    {
        $org     = $this->org('free', 3);
        $payment = $this->billing->createOrder($org, User::first(), 'monthly');

        $this->billing->markPaid($payment, 'pay_1', 'sig');
        $org->refresh();
        $expiry = $org->plan_expires_at->copy();

        $this->assertSame('pro', $org->plan);
        $this->assertSame('active', $org->billing_status);
        $this->assertSame('monthly', $org->billing_period);
        $this->assertSame(5, $org->seats);
        $this->assertTrue($expiry->between(now()->addMonthNoOverflow()->subMinute(), now()->addMonthNoOverflow()->addMinute()));
        $this->assertNotNull($payment->fresh()->receipt_number);

        // Webhook arriving after the browser verify must not add a second month
        $this->billing->markPaid($payment, 'pay_1');
        $this->assertTrue($org->fresh()->plan_expires_at->eq($expiry));
    }

    public function test_renewal_starts_when_the_current_period_ends(): void
    {
        $org   = $this->org('free', 3);
        $first = $this->paidPayment($org);
        $second = $this->paidPayment($org->fresh(), 'yearly');

        $this->assertTrue($second->period_start->eq($first->period_end));
        $this->assertTrue($org->fresh()->plan_expires_at->eq($second->period_end));
    }

    public function test_renewal_clears_a_scheduled_downgrade(): void
    {
        $org = $this->org('free', 3);
        $this->paidPayment($org);
        $this->billing->scheduleDowngrade($org->fresh());

        $this->paidPayment($org->fresh());

        $this->assertNull($org->fresh()->downgrade_scheduled_at);
    }

    // ── Refunds ──────────────────────────────────────────────────────────────

    public function test_refund_within_window_returns_full_amount_and_moves_to_free(): void
    {
        $org     = $this->org('free', 3);
        $payment = $this->paidPayment($org);

        $this->billing->refund($payment, User::first(), 'Not a fit');

        $payment->refresh();
        $org->refresh();
        $this->assertSame('refunded', $payment->status);
        $this->assertSame($payment->amount, $this->razorpay->refunds[0]['amount']);
        $this->assertSame('pending', $payment->refund_status);
        $this->assertSame('free', $org->plan);
        $this->assertNull($org->plan_expires_at);
    }

    public function test_refunding_a_renewal_keeps_the_earlier_paid_period(): void
    {
        $org    = $this->org('free', 3);
        $first  = $this->paidPayment($org);
        $second = $this->paidPayment($org->fresh());

        $this->billing->refund($second, User::first(), null);

        $org->refresh();
        $this->assertSame('pro', $org->plan);
        $this->assertTrue($org->plan_expires_at->eq($first->period_end));
    }

    public function test_refund_is_refused_after_the_window(): void
    {
        $org     = $this->org('free', 3);
        $payment = $this->paidPayment($org);
        $payment->update(['paid_at' => now()->subDays(8)]);

        $this->assertNull($this->billing->refundablePayment($org));
        $this->expectException(BillingException::class);

        try {
            $this->billing->refund($payment->fresh(), User::first(), null);
        } finally {
            $this->assertSame([], $this->razorpay->refunds);
            $this->assertSame('pro', $org->fresh()->plan);
        }
    }

    public function test_refund_limit_per_org_is_enforced_when_configured(): void
    {
        config(['plans.refund_limit_per_org' => 1]);
        $org = $this->org('free', 3);

        $this->billing->refund($this->paidPayment($org), User::first(), null);

        $this->expectException(BillingException::class);
        $this->billing->refund($this->paidPayment($org->fresh()), User::first(), null);
    }

    public function test_gateway_failure_leaves_payment_and_plan_unchanged(): void
    {
        $org     = $this->org('free', 3);
        $payment = $this->paidPayment($org);
        $this->razorpay->refundFails = true;

        try {
            $this->billing->refund($payment, User::first(), null);
            $this->fail('Expected BillingException');
        } catch (BillingException) {
        }

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('pro', $org->fresh()->plan);
    }

    // ── Downgrade & expiry ───────────────────────────────────────────────────

    public function test_scheduled_downgrade_moves_to_free_when_the_period_ends(): void
    {
        $org = $this->org('free', 3);
        $this->paidPayment($org);
        $this->billing->scheduleDowngrade($org->fresh());
        $org->update(['plan_expires_at' => now()->subMinute()]);

        $this->artisan('org:check-expirations')->assertSuccessful();

        $org->refresh();
        $this->assertSame('free', $org->plan);
        $this->assertSame('free', $org->billing_status);
        $this->assertNull($org->downgrade_scheduled_at);
    }

    public function test_expired_plan_counts_as_free_before_the_nightly_job(): void
    {
        $org = $this->org('pro', 1, ['plan_expires_at' => now()->subHour()]);

        $this->assertSame('free', $org->effectivePlan());
        $this->assertFalse((new ModuleService())->hasModule($org->id, 'fairness_engine'));
    }

    // ── Module gating ────────────────────────────────────────────────────────

    public function test_org_admin_override_cannot_unlock_a_paid_module(): void
    {
        $org   = $this->org('free', 1);
        $admin = User::first();
        (new ModuleService())->enableModule($org->id, 'fairness_engine', $admin->id);

        $this->assertFalse((new ModuleService())->hasModule($org->id, 'fairness_engine'));
    }

    public function test_super_admin_grant_unlocks_a_paid_module(): void
    {
        $org = $this->org('free', 1);
        Role::findOrCreate('super_admin', 'web');
        $sa = User::factory()->create();
        $sa->assignRole('super_admin');
        (new ModuleService())->enableModule($org->id, 'fairness_engine', $sa->id);

        $this->assertTrue((new ModuleService())->hasModule($org->id, 'fairness_engine'));
    }

    public function test_org_can_switch_off_a_module_in_its_plan(): void
    {
        $org = $this->org('pro', 1, ['plan_expires_at' => now()->addMonth()]);
        OrganizationModule::create(['organization_id' => $org->id, 'module_name' => 'reports', 'is_enabled' => false]);

        $this->assertFalse((new ModuleService())->hasModule($org->id, 'reports'));
        $this->assertTrue((new ModuleService())->hasModule($org->id, 'fairness_engine'));
    }

    // ── Free plan user limit ─────────────────────────────────────────────────

    public function test_free_plan_limit_counts_pending_invites(): void
    {
        $org = $this->org('free', 9);
        TeamInvitation::create([
            'organization_id' => $org->id,
            'invited_by'      => User::first()->id,
            'email'           => 'new@example.com',
            'role'            => 'employee',
            'token'           => Str::random(32),
            'expires_at'      => now()->addDays(7),
        ]);

        $this->assertNotNull($this->billing->seatLimitError($org, 1));
        // Re-inviting the same person doesn't take a second place
        $this->assertNull($this->billing->seatLimitError($org, 1, ['new@example.com']));
    }

    public function test_paid_plan_has_no_user_limit(): void
    {
        $org = $this->org('pro', 30, ['plan_expires_at' => now()->addMonth()]);

        $this->assertNull($this->billing->seatLimitError($org, 10));
    }

    // ── HTTP ─────────────────────────────────────────────────────────────────

    public function test_public_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('measured on real work', false);
        $this->get('/pricing')->assertOk()->assertSee('₹149', false)->assertSee('₹199', false);
        $this->get('/refund-policy')->assertOk()->assertSee('Full refund within 7 days', false);
    }

    public function test_owner_sees_billing_page_with_checkout(): void
    {
        $org   = $this->org('free', 3);
        $owner = $this->owner($org);

        $this->actingAs($owner)->get('/billing')
            ->assertOk()
            ->assertSee('Upgrade to Pro')
            ->assertSee('Transaction history');
    }

    public function test_super_admin_without_organization_is_redirected_not_404(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $sa = User::factory()->create(['organization_id' => null]);
        $sa->assignRole('super_admin');

        $this->actingAs($sa)->get('/billing')
            ->assertRedirect(route('superadmin.organizations'))
            ->assertSessionHas('info');

        $this->actingAs($sa)->postJson(route('billing.order'), ['period' => 'monthly'])
            ->assertStatus(409)
            ->assertJsonStructure(['message']);
    }

    public function test_super_admin_switched_into_an_org_cannot_pay_or_refund_for_it(): void
    {
        $org     = $this->org('free', 3);
        $payment = $this->paidPayment($org);
        Role::findOrCreate('super_admin', 'web');
        $sa = User::factory()->create(['organization_id' => $org->id]);
        $sa->assignRole('super_admin');

        $this->actingAs($sa)->get('/billing')->assertRedirect(route('superadmin.organizations'));
        $this->actingAs($sa)->post(route('billing.refund', $payment->id))->assertRedirect(route('superadmin.organizations'));

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame([], $this->razorpay->refunds);
    }

    public function test_owner_without_organization_is_sent_to_create_one(): void
    {
        $owner = User::factory()->create(['organization_id' => null, 'onboarding_type' => 'org_creator']);
        Role::findOrCreate('owner', 'web');
        $owner->assignRole('owner');

        // EnsureOrganizationAccess already routes org creators to setup; billing must not 404 either way
        $this->actingAs($owner)->get('/billing')->assertRedirect(route('organization.create'));
    }

    public function test_pro_owner_sees_refund_downgrade_and_receipt(): void
    {
        $org     = $this->org('free', 3);
        $owner   = $this->owner($org);
        $payment = $this->paidPayment($org);

        $this->actingAs($owner)->get('/billing')
            ->assertOk()
            ->assertSee('Renew or extend Pro')
            ->assertSee('Cancel &amp; refund', false)
            ->assertSee('Switch to Free')
            ->assertSee($payment->receipt_number);

        $this->actingAs($owner)->get(route('billing.receipt', $payment->id))
            ->assertOk()
            ->assertSee('Payment Receipt')
            ->assertSee(BillingService::inr($payment->amount));
    }

    public function test_scheduled_downgrade_shows_keep_pro_option(): void
    {
        $org   = $this->org('free', 3);
        $owner = $this->owner($org);
        $this->paidPayment($org);

        $this->actingAs($owner)->post(route('billing.downgrade'))->assertRedirect(route('billing.index'));

        $this->actingAs($owner)->get('/billing')
            ->assertOk()
            ->assertSee('Switching to Free')
            ->assertSee('Keep Pro');
    }

    public function test_locked_feature_shows_inline_upgrade_to_owner(): void
    {
        $org   = $this->org('free', 3);
        $owner = $this->owner($org);

        $this->actingAs($owner)->get('/reports/my')
            ->assertStatus(403)
            ->assertSee('Reports is included in Pro')
            ->assertSee('and upgrade');
    }

    public function test_owner_can_refund_from_billing_page(): void
    {
        $org     = $this->org('free', 3);
        $owner   = $this->owner($org);
        $payment = $this->paidPayment($org);

        $this->actingAs($owner)
            ->post(route('billing.refund', $payment->id), ['reason' => 'Testing'])
            ->assertRedirect(route('billing.index'))
            ->assertSessionHas('success');

        $this->assertSame('refunded', $payment->fresh()->status);
    }

    public function test_cannot_refund_another_organizations_payment(): void
    {
        $other   = $this->org('free', 1);
        $payment = $this->paidPayment($other);
        $owner   = $this->owner($this->org('free', 1));

        $this->actingAs($owner)->post(route('billing.refund', $payment->id))->assertNotFound();
        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_webhook_activates_plan_when_browser_never_returned(): void
    {
        // Real SDK: webhook signature checking is a local HMAC, no network
        $this->app->instance(BillingService::class, new BillingService(new Api('key', 'secret')));
        config(['services.razorpay.webhook_secret' => 'whsec']);

        $org     = $this->org('free', 3);
        $payment = Payment::create([
            'organization_id' => $org->id, 'plan' => 'pro', 'billing_period' => 'monthly',
            'seats' => 5, 'amount' => 117410, 'currency' => 'INR',
            'razorpay_order_id' => 'order_9', 'status' => 'created',
        ]);

        $body = json_encode([
            'event'   => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_9', 'order_id' => 'order_9', 'amount' => 117410]]],
        ]);

        $this->call('POST', '/billing/webhook', [], [], [], [
            'CONTENT_TYPE'              => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => hash_hmac('sha256', $body, 'whsec'),
        ], $body)->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('pro', $org->fresh()->plan);
    }

    public function test_webhook_rejects_bad_signature(): void
    {
        $this->app->instance(BillingService::class, new BillingService(new Api('key', 'secret')));
        config(['services.razorpay.webhook_secret' => 'whsec']);

        $this->call('POST', '/billing/webhook', [], [], [], [
            'CONTENT_TYPE'              => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => 'nope',
        ], '{"event":"payment.captured"}')->assertStatus(400);
    }
}

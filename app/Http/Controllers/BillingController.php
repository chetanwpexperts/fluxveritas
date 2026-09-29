<?php

namespace App\Http\Controllers;

use App\Exceptions\BillingException;
use App\Models\Organization;
use App\Models\Payment;
use App\Services\BillingService;
use App\Services\ModuleService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function __construct(private BillingService $billing) {}

    /**
     * The organization whose billing the current user manages.
     *
     * Billing is for an organization's owner or admins. super_admin is an internal
     * platform role: it never pays for or refunds a customer's plan from here (even
     * after switching into an org) and manages plans from the Super Admin panel.
     * Stops the request with a redirect (or JSON for checkout calls) instead of a 404.
     */
    private function billingOrg(): Organization
    {
        $user = auth()->user();

        if ($user->hasRole('super_admin')) {
            $this->stop('superadmin.organizations', 'info',
                'Organization billing is only for each organization’s owner and admins. Change an organization’s plan from this list.');
        }

        abort_if(!$user->hasAnyRole(['owner', 'admin']), 403,
            'Only the owner or an admin can manage billing.');

        $org = $user->organization_id ? Organization::find($user->organization_id) : null;

        if (!$org) {
            $this->stop('organization.create', 'warning',
                'Create your organization first — billing is set up per organization.');
        }

        return $org;
    }

    /** Ends the request: JSON 409 for checkout calls, otherwise a redirect with a message. */
    private function stop(string $route, string $flashKey, string $message): never
    {
        throw new HttpResponseException(request()->expectsJson()
            ? response()->json(['message' => $message], 409)
            : redirect()->route($route)->with($flashKey, $message));
    }

    private function orgPayment(Organization $org, int $paymentId): Payment
    {
        return Payment::where('organization_id', $org->id)->findOrFail($paymentId);
    }

    // ── Billing page ─────────────────────────────────────────────────────────

    public function index(Request $request, ModuleService $modules)
    {
        $org  = $this->billingOrg();
        $plan = $org->effectivePlan();

        $quotes = $plan === 'enterprise' ? [] : [
            'monthly' => $this->billing->quote($org, 'monthly'),
            'yearly'  => $this->billing->quote($org, 'yearly'),
        ];

        $period = $request->query('period');
        if (!isset($quotes[$period])) {
            $period = $org->billing_period ?: 'yearly';
        }

        $meta         = $modules->getModulesMeta();
        $planFeatures = collect($modules->getPlanModules($plan))
            ->map(fn ($m) => $meta[$m]['label'] ?? null)->filter()->values();
        $proFeatures  = collect($modules->getPlanModules('pro'))
            ->diff($modules->getPlanModules('free'))
            ->map(fn ($m) => $meta[$m]['label'] ?? null)->filter()->values();

        $refundable = $this->billing->refundablePayment($org);

        $payments = Payment::with(['paidBy:id,name,email'])
            ->where('organization_id', $org->id)
            ->where('status', '!=', 'created')
            ->latest()
            ->paginate(15);

        return view('billing.index', [
            'org'          => $org,
            'plan'         => $plan,
            'quotes'       => $quotes,
            'period'       => $period,
            'planFeatures' => $planFeatures,
            'proFeatures'  => $proFeatures,
            'payments'     => $payments,
            'refundable'   => $refundable,
            'refundKeepsProUntil' => $refundable ? $this->billing->expiryAfterRefund($refundable, $org) : null,
            'refundDays'   => $this->billing->refundWindowDays(),
            'freeLimit'    => $this->billing->freeUserLimit(),
            'activeUsers'  => $this->billing->activeUserCount($org),
            'usedSeats'    => $this->billing->usedFreeSeats($org),
        ]);
    }

    // ── Checkout (JSON, called from the Razorpay checkout script) ────────────

    public function createOrder(Request $request): JsonResponse
    {
        $request->validate(['period' => 'required|in:monthly,yearly']);

        $user = auth()->user();
        $org  = $this->billingOrg();

        try {
            $payment = $this->billing->createOrder($org, $user, $request->period);
        } catch (BillingException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error('Billing: order creation failed', ['org_id' => $org->id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'We could not start the payment. Please try again in a moment.'], 502);
        }

        return response()->json([
            'key'         => config('services.razorpay.key'),
            'order_id'    => $payment->razorpay_order_id,
            'amount'      => $payment->amount,
            'currency'    => $payment->currency,
            'name'        => 'OutraqHQ',
            'description' => 'Pro plan · ' . ucfirst($payment->billing_period) . ' · ' . $payment->seats . ' users',
            'prefill'     => ['name' => $user->name, 'email' => $user->email],
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        $org     = $this->billingOrg();
        $payment = Payment::where('organization_id', $org->id)
            ->where('razorpay_order_id', $request->razorpay_order_id)
            ->firstOrFail();

        try {
            $this->billing->api()->utility->verifyPaymentSignature($request->only(
                'razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature'
            ));
        } catch (\Throwable $e) {
            $this->billing->markFailed($payment, 'Payment signature could not be verified.');
            return response()->json([
                'message' => 'We could not verify this payment. If money was deducted, it will be refunded automatically by your bank — or contact support.',
            ], 400);
        }

        try {
            $this->billing->confirmWithGateway($payment, $request->razorpay_payment_id);
        } catch (BillingException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            // Signature is valid, so the payment is genuine; the webhook will reconcile capture.
            Log::warning('Billing: gateway confirmation unavailable', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
        }

        $payment = $this->billing->markPaid($payment, $request->razorpay_payment_id, $request->razorpay_signature);

        $message = 'Payment received — Pro is active until ' . $payment->period_end->format('j M Y') . '.';
        session()->flash('success', $message);

        return response()->json(['success' => true, 'message' => $message]);
    }

    /** Records a failed attempt reported by the checkout, so it shows in history. */
    public function failed(Request $request): JsonResponse
    {
        $request->validate([
            'razorpay_order_id' => 'required|string',
            'reason'            => 'nullable|string|max:500',
        ]);

        $org     = $this->billingOrg();
        $payment = Payment::where('organization_id', $org->id)
            ->where('razorpay_order_id', $request->razorpay_order_id)
            ->first();

        if ($payment) {
            $this->billing->markFailed($payment, $request->reason);
        }

        return response()->json(['success' => true]);
    }

    // ── Receipts ─────────────────────────────────────────────────────────────

    public function receipt(int $paymentId)
    {
        $org     = $this->billingOrg();
        $payment = $this->orgPayment($org, $paymentId);
        abort_unless(in_array($payment->status, ['paid', 'refunded'], true), 404);

        $payment->load('paidBy:id,name,email');

        return view('billing.receipt', [
            'payment' => $payment,
            'org'     => $org,
            'seller'  => config('plans.seller'),
        ]);
    }

    // ── Refund & downgrade ───────────────────────────────────────────────────

    public function refund(Request $request, int $paymentId): RedirectResponse
    {
        $request->validate(['reason' => 'nullable|string|max:1000']);

        $org     = $this->billingOrg();
        $payment = $this->orgPayment($org, $paymentId);

        try {
            $payment = $this->billing->refund($payment, auth()->user(), $request->reason);
        } catch (BillingException $e) {
            return redirect()->route('billing.index')->with('error', $e->getMessage());
        }

        $org->refresh();
        $amount = '₹' . number_format($payment->refund_amount / 100, 2);
        $after  = $org->isOnPaidPlan()
            ? 'Pro stays active until ' . $org->plan_expires_at->format('j M Y') . ' from your earlier payment.'
            : 'Your organization is now on the Free plan.';

        return redirect()->route('billing.index')->with('success',
            "Refund of {$amount} started. It usually reaches the original payment method within 5–7 working days. {$after}");
    }

    public function downgrade(): RedirectResponse
    {
        $org = $this->billingOrg();

        try {
            $this->billing->scheduleDowngrade($org);
        } catch (BillingException $e) {
            return redirect()->route('billing.index')->with('error', $e->getMessage());
        }

        $org->refresh();

        return redirect()->route('billing.index')->with('success', $org->isOnPaidPlan()
            ? 'Done. Pro stays active until ' . $org->plan_expires_at->format('j M Y') . ', then your organization moves to the Free plan.'
            : 'Done. Your organization is now on the Free plan.');
    }

    public function cancelDowngrade(): RedirectResponse
    {
        $org = $this->billingOrg();
        $this->billing->cancelDowngrade($org);

        return redirect()->route('billing.index')->with('success', 'You are staying on Pro.');
    }

    // ── Razorpay webhook (no session, CSRF-exempt) ───────────────────────────

    public function webhook(Request $request): JsonResponse
    {
        $secret    = config('services.razorpay.webhook_secret');
        $signature = $request->header('X-Razorpay-Signature');

        if (!$secret || !$signature) {
            return response()->json(['ok' => false], 400);
        }

        try {
            $this->billing->api()->utility->verifyWebhookSignature($request->getContent(), $signature, $secret);
        } catch (\Throwable $e) {
            Log::warning('Billing webhook: invalid signature');
            return response()->json(['ok' => false], 400);
        }

        $event = (string) $request->input('event');

        if (in_array($event, ['payment.captured', 'order.paid', 'payment.failed'], true)) {
            $entity  = (array) $request->input('payload.payment.entity', []);
            $payment = isset($entity['order_id'])
                ? Payment::where('razorpay_order_id', $entity['order_id'])->first()
                : null;

            if ($payment && $event === 'payment.failed') {
                $this->billing->markFailed($payment, $entity['error_description'] ?? null);
            } elseif ($payment && (int) ($entity['amount'] ?? 0) === (int) $payment->amount) {
                $this->billing->markPaid($payment, $entity['id']);
            }
        }

        if (in_array($event, ['refund.processed', 'refund.failed'], true)) {
            $refundId = $request->input('payload.refund.entity.id');
            if ($refundId) {
                $this->billing->applyRefundStatus($refundId, $event === 'refund.processed' ? 'processed' : 'failed');
            }
        }

        return response()->json(['ok' => true]);
    }
}

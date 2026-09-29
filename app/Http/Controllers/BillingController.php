<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Razorpay\Api\Api;

class BillingController extends Controller
{
    private function api(): Api
    {
        return new Api(
            config('services.razorpay.key'),
            config('services.razorpay.secret')
        );
    }

    public function index()
    {
        $user = auth()->user();
        abort_if(!$user->hasAnyRole(['owner', 'admin', 'super_admin']), 403,
            'Only the owner or admin can manage billing.');

        $org      = Organization::findOrFail($user->organization_id);
        $payments = Payment::where('organization_id', $org->id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return view('billing.index', compact('org', 'payments'));
    }

    public function createOrder(Request $request)
    {
        $user = auth()->user();
        abort_if(!$user->hasAnyRole(['owner', 'admin', 'super_admin']), 403);

        $plan       = $request->input('plan', 'pro');
        $planConfig = config("plans.$plan");
        abort_if(!$planConfig, 404, 'Plan not found.');

        $amount = $planConfig['monthly'];
        $org    = Organization::findOrFail($user->organization_id);

        $order = $this->api()->order->create([
            'amount'   => $amount,
            'currency' => 'INR',
            'receipt'  => 'org_' . $org->id . '_' . time(),
            'notes'    => [
                'organization_id' => $org->id,
                'plan'            => $plan,
            ],
        ]);

        $payment = Payment::create([
            'organization_id'   => $org->id,
            'paid_by'           => $user->id,
            'plan'              => $plan,
            'billing_period'    => 'monthly',
            'amount'            => $amount,
            'currency'          => 'INR',
            'razorpay_order_id' => $order['id'],
            'status'            => 'created',
        ]);

        return response()->json([
            'order_id'    => $order['id'],
            'amount'      => $amount,
            'currency'    => 'INR',
            'key'         => config('services.razorpay.key'),
            'name'        => 'OutraqHQ',
            'description' => ucfirst($plan) . ' plan — 1 month',
            'prefill'     => [
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'payment_id'  => $payment->id,
        ]);
    }

    public function verify(Request $request)
    {
        $user = auth()->user();
        abort_if(!$user->hasAnyRole(['owner', 'admin', 'super_admin']), 403);

        $request->validate([
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        $payment = Payment::where('razorpay_order_id', $request->razorpay_order_id)
            ->where('organization_id', $user->organization_id)
            ->firstOrFail();

        try {
            $this->api()->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature'  => $request->razorpay_signature,
            ]);
        } catch (\Exception $e) {
            $payment->update(['status' => 'failed']);
            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed.',
            ], 400);
        }

        $payment->update([
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'razorpay_signature'  => $request->razorpay_signature,
            'status'              => 'paid',
            'paid_at'             => now(),
        ]);

        $org = Organization::findOrFail($payment->organization_id);

        $base = ($org->plan_expires_at && Carbon::parse($org->plan_expires_at)->isFuture())
            ? $org->plan_expires_at
            : now();

        $org->update([
            'plan'            => $payment->plan,
            'plan_expires_at' => Carbon::parse($base)->addDays(30),
            'billing_status'  => 'active',
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Payment successful! Pro features unlocked.',
            'redirect' => route('billing.index'),
        ]);
    }
}

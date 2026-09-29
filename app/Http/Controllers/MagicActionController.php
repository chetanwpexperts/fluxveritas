<?php

namespace App\Http\Controllers;

use App\Exceptions\WorkflowException;
use App\Models\AuditLog;
use App\Models\IncrementReview;
use App\Models\User;
use App\Services\IncrementApprovalService;
use App\Services\MagicActionTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * One-click actions from emails (currently: approve an increment).
 *
 * GET only shows what will happen — link scanners in email systems open links
 * automatically, so nothing may change on GET. POST re-checks everything, uses
 * the link up (single use) and performs the action with the same rules as the app.
 */
class MagicActionController extends Controller
{
    public function __construct(
        private MagicActionTokenService $tokens,
        private IncrementApprovalService $approvals,
    ) {}

    public function show(Request $request)
    {
        $context = $this->resolve((string) $request->query('token'));
        if (is_string($context)) {
            return response()->view('actions.expired', ['reason' => $context], 410);
        }

        return view('actions.confirm', [
            'token'  => $request->query('token'),
            'review' => $context['review'],
            'final'  => $context['final'],
            'user'   => $context['user'],
        ]);
    }

    public function execute(Request $request)
    {
        $token   = (string) $request->input('token');
        $context = $this->resolve($token);
        if (is_string($context)) {
            return response()->view('actions.expired', ['reason' => $context], 410);
        }

        try {
            DB::transaction(function () use ($context) {
                if (!$this->tokens->consume($context['payload'])) {
                    throw new WorkflowException('This link has already been used.');
                }
                $this->approvals->approve($context['user'], $context['review'], $context['final'], 'Approved from email');

                AuditLog::create([
                    'organization_id' => $context['review']->organization_id,
                    'user_id'         => $context['user']->id,
                    'action'          => 'increment.approved_via_email',
                    'entity_type'     => 'increment_review',
                    'entity_id'       => $context['review']->id,
                    'new_values'      => ['final_increment' => $context['final'], 'employee_id' => $context['review']->user_id],
                    'ip_address'      => request()->ip(),
                    'user_agent'      => request()->userAgent(),
                ]);
            });
        } catch (WorkflowException $e) {
            return response()->view('actions.expired', ['reason' => $e->getMessage()], 409);
        }

        return view('actions.success', [
            'title'   => 'Increment approved',
            'message' => "{$context['review']->user->name}'s increment of {$context['final']}% is approved, and they have been notified.",
        ]);
    }

    /** @return array|string context, or a reason the link can't be used */
    private function resolve(string $token): array|string
    {
        $payload = $token !== '' ? $this->tokens->validateToken($token) : null;
        if (!$payload || ($payload['action_type'] ?? null) !== 'approve_increment') {
            return 'This link has expired or has already been used.';
        }

        $user = User::find($payload['user_id'] ?? 0);
        if (!$user || !$user->is_active || $user->organization?->isSuspended()) {
            return 'This link is no longer valid.';
        }

        $review = IncrementReview::withoutGlobalScopes()->with('policy', 'user')
            ->where('organization_id', $payload['org_id'] ?? 0)
            ->find($payload['params']['review_id'] ?? 0);
        if (!$review || !$this->approvals->canApprove($user, $review)) {
            return 'You can no longer approve this increment.';
        }
        if (in_array($review->status, ['ceo_approved', 'approved'], true)) {
            return "{$review->user->name}'s increment has already been approved.";
        }

        return [
            'payload' => $payload,
            'user'    => $user,
            'review'  => $review,
            'final'   => min((float) $review->recommended_increment, (float) $review->policy->max_increment_percent),
        ];
    }
}

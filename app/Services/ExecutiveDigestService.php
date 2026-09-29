<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Blocker;
use App\Models\FairnessFlag;
use App\Models\FeedbackBiasReport;
use App\Models\IncrementReview;
use App\Models\Organization;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Support\Facades\Log;

class ExecutiveDigestService
{
    public function __construct(private MagicActionTokenService $tokenService) {}

    /**
     * Generate the 30-Second Morning AI Executive Digest for CEO/Owner
     */
    public function generateMorningDigest(Organization $org, User $ceo): array
    {
        $orgId = $org->id;
        $yesterday = now()->subDay()->toDateString();

        // 1. Log consistency yesterday
        $totalUsers   = User::where('organization_id', $orgId)->where('is_active', true)->count();
        $loggedCount  = WorkLog::where('organization_id', $orgId)->where('log_date', $yesterday)->distinct('user_id')->count('user_id');
        $consistency  = $totalUsers > 0 ? round(($loggedCount / $totalUsers) * 100) : 100;

        // 2. Open & Escalated Blockers needing attention
        $openBlockers      = Blocker::where('organization_id', $orgId)->open()->count();
        $escalatedBlockers = Blocker::where('organization_id', $orgId)->where('status', 'escalated')->get();

        // 3. Pending Fairness Flags & Unreviewed Bias Reports
        $pendingFlags = FairnessFlag::where('organization_id', $orgId)->where('status', 'pending')->count();
        $biasReports  = FeedbackBiasReport::where('organization_id', $orgId)->where('bias_detected', true)->where('ceo_reviewed', false)->get();

        // 4. Pending Salary Increment Reviews
        $pendingIncrements = IncrementReview::where('organization_id', $orgId)
            ->whereIn('status', ['calculated', 'pending_approval'])
            ->with('user')
            ->get();

        // 5. Yesterday's Top Contributor (by commits + log hours)
        $topContributors = Activity::where('organization_id', $orgId)
            ->whereDate('occurred_at', $yesterday)
            ->selectRaw('user_id, COUNT(*) as event_count')
            ->groupBy('user_id')
            ->orderByDesc('event_count')
            ->with('user')
            ->take(3)
            ->get();

        // Generate 1-Click Magic Email Action Links for CEO
        $incrementActions = [];
        foreach ($pendingIncrements as $review) {
            $token = $this->tokenService->generateToken($ceo, 'approve_increment', ['review_id' => $review->id]);
            $incrementActions[] = [
                'user_name'         => $review->user->name,
                'recommended_pct'   => $review->recommended_increment_pct,
                'approve_url'       => url("/api/action/execute?token={$token}"),
            ];
        }

        $healthScore = round(
            ($consistency * 0.4) +
            (max(0, 100 - ($openBlockers * 5) - ($escalatedBlockers->count() * 15)) * 0.4) +
            (max(0, 100 - ($pendingFlags * 10)) * 0.2)
        );

        return [
            'org_name'            => $org->name,
            'ceo_name'            => $ceo->name,
            'health_score'        => max(0, min(100, $healthScore)),
            'log_consistency_pct' => $consistency,
            'logged_count'        => $loggedCount,
            'total_users'         => $totalUsers,
            'open_blockers'       => $openBlockers,
            'escalated_blockers'  => $escalatedBlockers,
            'pending_flags'       => $pendingFlags,
            'bias_reports_count'  => $biasReports->count(),
            'top_contributors'    => $topContributors,
            'increment_actions'   => $incrementActions,
            'generated_at'        => now()->toDateTimeString(),
        ];
    }
}

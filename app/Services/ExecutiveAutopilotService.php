<?php

namespace App\Services;

use App\Models\IncrementReview;
use App\Models\Organization;
use Illuminate\Support\Facades\Log;

class ExecutiveAutopilotService
{
    /**
     * Run CEO Outy Autopilot for an organization based on executive guardrails
     */
    public function processAutopilotApprovals(Organization $org): array
    {
        $settings = $org->autopilot_settings ?? [];

        if (empty($settings['auto_approve_enabled'])) {
            return ['processed' => 0, 'reason' => 'Autopilot disabled'];
        }

        $maxIncrementPct = (float) ($settings['max_increment_pct'] ?? 10.0);
        $minScore        = (float) ($settings['min_performance_score'] ?? 80.0);

        $pendingReviews = IncrementReview::where('organization_id', $org->id)
            ->whereIn('status', ['calculated', 'pending_approval'])
            ->where('recommended_increment_pct', '<=', $maxIncrementPct)
            ->where('calculated_score', '>=', $minScore)
            ->get();

        $autoApprovedCount = 0;
        $approvedNames = [];

        foreach ($pendingReviews as $review) {
            $review->update([
                'status'      => 'approved',
                'approved_at' => now(),
                'notes'       => 'Autonomously approved by Outy Executive Autopilot (Guardrails: Max ' . $maxIncrementPct . '%, Min Score ' . $minScore . '%)',
            ]);

            $autoApprovedCount++;
            $approvedNames[] = $review->user->name ?? 'User #' . $review->user_id;
        }

        Log::info("Outy Autopilot: Auto-approved {$autoApprovedCount} increment reviews for Org #{$org->id}");

        return [
            'processed'      => $autoApprovedCount,
            'approved_names' => $approvedNames,
            'max_pct'        => $maxIncrementPct,
            'min_score'      => $minScore,
        ];
    }
}

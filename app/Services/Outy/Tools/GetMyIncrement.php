<?php

namespace App\Services\Outy\Tools;

use App\Models\IncrementReview;
use App\Models\IncrementScore;
use App\Models\User;

/** The user's own increment scores and latest review. Never anyone else's. */
class GetMyIncrement extends OutyTool
{
    protected ?string $module = 'increment_calculator';

    public function name(): string
    {
        return 'get_my_increment';
    }

    public function description(): string
    {
        return "The current user's OWN monthly contribution scores (last 6 months) and their latest increment review "
            . '(average score, recommended increment %, status). Only ever returns the asking user\'s data.';
    }

    protected function handle(User $user, array $args): array
    {
        $scores = IncrementScore::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->orderByDesc('score_month')
            ->take(6)
            ->get(['score_month', 'final_score', 'is_adjusted', 'adjustment_reason']);

        $review = IncrementReview::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->orderByDesc('review_year')
            ->first();

        return [
            'monthly_scores' => $scores->map(fn ($s) => [
                'month'      => $s->score_month?->format('M Y'),
                'score'      => round((float) $s->final_score, 1),
                'adjustment' => $s->is_adjusted ? $s->adjustment_reason : null,
            ])->values()->all(),
            'latest_review'  => $review ? [
                'year'                  => $review->review_year,
                'average_score'         => round((float) $review->avg_score, 1),
                'recommended_increment' => $review->recommended_increment !== null ? (float) $review->recommended_increment : null,
                'final_increment'       => $review->status === 'approved' && $review->final_increment !== null ? (float) $review->final_increment : null,
                'status'                => $review->status,
            ] : null,
        ];
    }
}

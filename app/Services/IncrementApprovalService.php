<?php

namespace App\Services;

use App\Exceptions\WorkflowException;
use App\Models\IncrementReview;
use App\Models\User;

/** Final approval of an increment review — shared by the review screen and emailed approval links. */
class IncrementApprovalService
{
    public const APPROVER_ROLES = ['owner', 'super_admin'];

    public function canApprove(User $approver, IncrementReview $review): bool
    {
        return ($approver->is_active ?? true) !== false   // column defaults to active
            && $approver->hasAnyRole(self::APPROVER_ROLES)
            && ($approver->hasRole('super_admin') || (int) $approver->organization_id === (int) $review->organization_id);
    }

    public function approve(User $approver, IncrementReview $review, float $finalIncrement, ?string $notes = null, ?string $overrideReason = null): IncrementReview
    {
        if (!$this->canApprove($approver, $review)) {
            throw new WorkflowException('Only the organization owner can approve increments.');
        }
        if (in_array($review->status, ['ceo_approved', 'approved'], true)) {
            throw new WorkflowException("{$review->user->name}'s increment is already approved.");
        }

        $final = min($finalIncrement, (float) $review->policy->max_increment_percent);
        if (abs($final - (float) $review->recommended_increment) > 5 && mb_strlen(trim((string) $overrideReason)) < 20) {
            throw new WorkflowException('Give a reason of at least 20 characters when the final increment differs from the recommendation by more than 5%.', 'override_reason');
        }

        $review->update([
            'final_increment' => $final,
            'ceo_notes'       => $notes,
            'override_reason' => abs($final - (float) $review->recommended_increment) > 5 ? $overrideReason : null,
            'status'          => 'ceo_approved',
            'ceo_approved_at' => now(),
        ]);

        NotificationService::send(
            $review->user_id,
            $review->organization_id,
            'increment_approved',
            '🎉 Your Increment Has Been Approved!',
            'Your ' . $review->review_year . ' increment of ' . $final . '% has been approved. Congratulations!',
            '/increment/my',
            'View Details',
            'high',
            ['review_id' => $review->id, 'final_increment' => $final],
            $approver->id
        );

        return $review;
    }
}

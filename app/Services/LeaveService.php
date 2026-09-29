<?php

namespace App\Services;

use App\Exceptions\WorkflowException;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Leave rules shared by the Leaves screens and Outy: applying, and approving
 * or rejecting. Every query is scoped to the acting user's organization.
 */
class LeaveService
{
    /** Roles that can review any leave request in their organization. */
    public const APPROVER_ROLES = ['admin', 'owner', 'ceo', 'super_admin', 'hr', 'manager'];

    /**
     * Validates a leave request without saving it.
     *
     * @return array{type: LeaveType, days: float, available: float}
     */
    public function check(User $user, int $leaveTypeId, string $from, string $to, string $halfDay = 'none'): array
    {
        $type = LeaveType::where('id', $leaveTypeId)->where('organization_id', $user->organization_id)->first();
        if (!$type) {
            throw new WorkflowException('That leave type does not exist in your organization.', 'leave_type_id');
        }

        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate   = Carbon::parse($to)->startOfDay();
        if ($fromDate->lt(today())) {
            throw new WorkflowException('Leave must start today or later.', 'from_date');
        }
        if ($toDate->lt($fromDate)) {
            throw new WorkflowException('The end date must be on or after the start date.', 'to_date');
        }

        $isHalfDay = $halfDay !== 'none';
        $days      = LeaveApplication::calculateWorkingDays($fromDate->toDateString(), $toDate->toDateString(), $isHalfDay);
        if ($days <= 0) {
            throw new WorkflowException('Selected dates fall on non-working days.', 'from_date');
        }

        $balance   = LeaveBalance::where('user_id', $user->id)->where('leave_type_id', $type->id)->where('year', now()->year)->first();
        $available = $balance ? $balance->available : (float) $type->days_per_year;
        if ($available < $days) {
            throw new WorkflowException("Insufficient leave balance. Available: {$available} days.", 'leave_type_id');
        }

        return ['type' => $type, 'days' => $days, 'available' => $available];
    }

    public function apply(User $user, int $leaveTypeId, string $from, string $to, string $reason, string $halfDay = 'none'): LeaveApplication
    {
        $reason = trim($reason);
        if (mb_strlen($reason) < 5 || mb_strlen($reason) > 500) {
            throw new WorkflowException('Please give a reason between 5 and 500 characters.', 'reason');
        }

        ['type' => $type, 'days' => $days] = $this->check($user, $leaveTypeId, $from, $to, $halfDay);
        $isHalfDay = $halfDay !== 'none';

        $application = DB::transaction(function () use ($user, $type, $days, $from, $to, $reason, $isHalfDay, $halfDay) {
            $balance = LeaveBalance::firstOrCreate(
                ['user_id' => $user->id, 'leave_type_id' => $type->id, 'year' => now()->year],
                ['organization_id' => $user->organization_id, 'allocated' => $type->days_per_year, 'used' => 0, 'pending' => 0, 'carried_forward' => 0]
            );

            $application = LeaveApplication::create([
                'user_id'         => $user->id,
                'leave_type_id'   => $type->id,
                'organization_id' => $user->organization_id,
                'from_date'       => Carbon::parse($from)->toDateString(),
                'to_date'         => Carbon::parse($to)->toDateString(),
                'days'            => $days,
                'reason'          => $reason,
                'status'          => $type->requires_approval ? 'pending' : 'approved',
                'is_half_day'     => $isHalfDay,
                'half_day_period' => $isHalfDay ? $halfDay : null,
            ]);

            $type->requires_approval
                ? $balance->increment('pending', $days)
                : $balance->increment('used', $days);

            return $application;
        });

        if ($type->requires_approval) {
            $this->notifyApprovers($user, $application->load('leaveType'));
        }

        return $application;
    }

    /**
     * Pending requests this person may review: anyone in the organization for
     * approver roles, their own team(s) for team leads — never their own request.
     */
    public function approvableQuery(User $reviewer): Builder
    {
        $query = LeaveApplication::where('organization_id', $reviewer->organization_id)
            ->where('user_id', '!=', $reviewer->id);

        if ($reviewer->hasAnyRole(self::APPROVER_ROLES)) {
            return $query;
        }

        if ($reviewer->hasRole('team_lead')) {
            $teamIds = Team::where('organization_id', $reviewer->organization_id)->where('team_lead_id', $reviewer->id)->pluck('id');
            return $query->whereHas('user', fn ($q) => $q->whereIn('team_id', $teamIds));
        }

        return $query->whereRaw('1 = 0');
    }

    public function findApprovable(User $reviewer, int $id): ?LeaveApplication
    {
        return $this->approvableQuery($reviewer)->with('leaveType', 'user')->find($id);
    }

    public function review(User $reviewer, LeaveApplication $application, bool $approve, ?string $note = null): LeaveApplication
    {
        $application = DB::transaction(function () use ($reviewer, $application, $approve, $note) {
            $application = LeaveApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();

            if ($application->status !== 'pending') {
                throw new WorkflowException('This leave is no longer pending.');
            }

            $application->update([
                'status'        => $approve ? 'approved' : 'rejected',
                'reviewed_by'   => $reviewer->id,
                'reviewer_note' => $note ? mb_substr(trim($note), 0, 300) : null,
                'reviewed_at'   => now(),
            ]);

            $balance = LeaveBalance::where('user_id', $application->user_id)
                ->where('leave_type_id', $application->leave_type_id)
                ->where('year', $application->from_date->year)
                ->first();

            if ($balance) {
                if ($approve) {
                    $balance->increment('used', $application->days);
                }
                $balance->decrement('pending', $application->days);
            }

            return $application->load('leaveType');
        });

        NotificationService::send(
            $application->user_id,
            $reviewer->organization_id,
            $approve ? 'leave_approved' : 'leave_rejected',
            $approve ? 'Leave Approved' : 'Leave Rejected',
            "Your {$application->leaveType->name} from {$application->from_date->format('M d')} to {$application->to_date->format('M d')} "
                . ($approve ? 'has been approved.' : 'was not approved.'),
            route('leaves.index')
        );

        return $application;
    }

    private function notifyApprovers(User $applicant, LeaveApplication $application): void
    {
        $orgId       = $applicant->organization_id;
        $approverIds = [];

        if ($applicant->team_id) {
            $lead = Team::where('id', $applicant->team_id)->value('team_lead_id');
            if ($lead) {
                $approverIds[] = $lead;
            }
        }

        $admins = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'owner', 'ceo']))
            ->pluck('id')
            ->all();

        $approverIds = array_values(array_diff(array_unique(array_merge($approverIds, $admins)), [$applicant->id]));

        if ($approverIds) {
            NotificationService::sendToMany(
                $approverIds,
                $orgId,
                'leave_request',
                'New Leave Request',
                "{$applicant->name} has applied for {$application->leaveType->name} from {$application->from_date->format('M d')} to {$application->to_date->format('M d')}.",
                route('leaves.index')
            );
        }
    }
}

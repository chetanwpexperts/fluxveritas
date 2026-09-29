<?php

namespace App\Services\Outy\Tools;

use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;

/** The user's own leave balances and requests. */
class GetMyLeaveBalance extends OutyTool
{
    protected ?string $module = 'leave_management';

    public function name(): string
    {
        return 'get_my_leave_balance';
    }

    public function description(): string
    {
        return "The current user's own leave balance for this year per leave type, plus their pending and upcoming approved leave requests.";
    }

    protected function handle(User $user, array $args): array
    {
        $year     = now()->year;
        $balances = LeaveBalance::where('user_id', $user->id)->where('organization_id', $user->organization_id)->where('year', $year)->get();
        $types    = LeaveType::where('organization_id', $user->organization_id)->pluck('name', 'id');

        $requests = LeaveApplication::where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->where(fn ($q) => $q->where('status', 'pending')
                ->orWhere(fn ($q) => $q->where('status', 'approved')->whereDate('to_date', '>=', now()->toDateString())))
            ->orderBy('from_date')
            ->get();

        return [
            'year'     => $year,
            'balances' => $balances->map(fn ($b) => [
                'type'      => $types[$b->leave_type_id] ?? 'Leave',
                'allocated' => (float) $b->allocated + (float) $b->carried_forward,
                'used'      => (float) $b->used,
                'pending'   => (float) $b->pending,
                'left'      => max(0, (float) $b->allocated + (float) $b->carried_forward - (float) $b->used),
            ])->values()->all(),
            'requests' => $requests->map(fn ($r) => [
                'type'   => $types[$r->leave_type_id] ?? 'Leave',
                'from'   => substr((string) $r->from_date, 0, 10),
                'to'     => substr((string) $r->to_date, 0, 10),
                'days'   => (float) $r->days,
                'status' => $r->status,
            ])->all(),
        ];
    }
}

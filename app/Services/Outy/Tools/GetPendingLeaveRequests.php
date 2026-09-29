<?php

namespace App\Services\Outy\Tools;

use App\Models\User;
use App\Services\LeaveService;

/** Leave requests waiting for this approver — needed before approve/reject. */
class GetPendingLeaveRequests extends OutyTool
{
    protected array $roles = ['owner', 'admin', 'hr', 'team_lead', 'ceo', 'manager'];

    protected ?string $module = 'leave_management';

    public function name(): string
    {
        return 'get_pending_leave_requests';
    }

    public function description(): string
    {
        return 'Pending leave requests the current user is allowed to approve or reject (their team for team leads, everyone for HR/admin/owner). '
            . 'Returns request ids to use with approve_leave / reject_leave.';
    }

    protected function handle(User $user, array $args): array
    {
        $requests = app(LeaveService::class)->approvableQuery($user)
            ->where('status', 'pending')
            ->with('leaveType:id,name', 'user:id,name')
            ->orderBy('from_date')
            ->take(25)
            ->get();

        return [
            'count'    => $requests->count(),
            'requests' => $requests->map(fn ($r) => [
                'request_id' => $r->id,
                'person'     => $r->user?->name,
                'type'       => $r->leaveType?->name,
                'from'       => $r->from_date->toDateString(),
                'to'         => $r->to_date->toDateString(),
                'days'       => (float) $r->days,
                'reason'     => $r->reason,
            ])->all(),
        ];
    }
}

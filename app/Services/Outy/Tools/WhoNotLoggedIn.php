<?php

namespace App\Services\Outy\Tools;

use App\Models\LeaveApplication;
use App\Models\User;
use App\Models\WorkLog;
use App\Services\TeamStatusService;

/** People who haven't logged work today or in the last N days. */
class WhoNotLoggedIn extends OutyTool
{
    protected array $roles = ['owner', 'admin', 'hr', 'team_lead'];

    public function name(): string
    {
        return 'who_not_logged_in';
    }

    public function description(): string
    {
        return 'People (in the teams the user leads, or the whole organization for owner/admin/HR) who have not logged any work '
            . 'in the last N days (days=1 means today). Includes each person\'s last log date and whether they are on leave today.';
    }

    public function parameters(): array
    {
        return [
            'type'                 => 'object',
            'properties'           => [
                'days' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 30, 'description' => 'Look-back window in days, 1 = today only'],
            ],
            'additionalProperties' => false,
        ];
    }

    protected function handle(User $user, array $args): array
    {
        $days  = $this->intArg($args, 'days', 1, 1, 30);
        $today = now()->setTimezone('Asia/Kolkata');
        $from  = $today->copy()->subDays($days - 1)->toDateString();

        ['label' => $label, 'members' => $members] = app(TeamStatusService::class)->scopeFor($user);
        $ids = $members->pluck('id');

        $lastLog = WorkLog::withoutGlobalScopes()
            ->where('organization_id', $user->organization_id)
            ->whereIn('user_id', $ids)
            ->selectRaw('user_id, MAX(log_date) as last_log')
            ->groupBy('user_id')
            ->pluck('last_log', 'user_id');

        $onLeave = LeaveApplication::where('organization_id', $user->organization_id)
            ->whereIn('user_id', $ids)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $today->toDateString())
            ->whereDate('to_date', '>=', $today->toDateString())
            ->pluck('user_id');

        $missing = $members
            ->filter(fn ($m) => !isset($lastLog[$m->id]) || substr((string) $lastLog[$m->id], 0, 10) < $from)
            ->map(fn ($m) => [
                'person'         => $m->name,
                'last_logged_on' => isset($lastLog[$m->id]) ? substr((string) $lastLog[$m->id], 0, 10) : null,
                'on_leave_today' => $onLeave->contains($m->id),
            ])->values();

        return [
            'scope'        => $label ?: null,
            'window'       => $days === 1 ? 'today' : "last {$days} days (since {$from})",
            'people'       => $members->count(),
            'not_logged'   => $missing->all(),
            'count'        => $missing->count(),
        ];
    }
}

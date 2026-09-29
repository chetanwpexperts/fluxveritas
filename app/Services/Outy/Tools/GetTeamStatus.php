<?php

namespace App\Services\Outy\Tools;

use App\Models\User;
use App\Services\TeamStatusService;
use Carbon\Carbon;

/** Today's work logs, open blockers and leave for the user's team. */
class GetTeamStatus extends OutyTool
{
    public function name(): string
    {
        return 'get_team_status';
    }

    public function description(): string
    {
        return "Live status of the user's team today: who logged work, who hasn't, open blockers (who is blocked and on what), and who is on leave. "
            . 'Team = teams the user leads; for owner/admin/HR the whole organization; otherwise the user\'s own team.';
    }

    protected function handle(User $user, array $args): array
    {
        $today = now()->setTimezone('Asia/Kolkata')->toDateString();
        $s     = app(TeamStatusService::class)->snapshot($user, $today);
        $names = $s['members']->pluck('name', 'id');

        return [
            'team'           => $s['label'] ?: null,
            'date'           => $today,
            'members'        => $s['members']->count(),
            'logged_today'   => $s['logged']->pluck('name')->all(),
            'not_logged_yet' => $s['not_logged']->pluck('name')->all(),
            'open_blockers'  => $s['blockers']->map(fn ($b) => [
                'person'    => $names[$b->blocked_user_id] ?? null,
                'title'     => $b->title,
                'days_open' => (int) $b->created_at->copy()->startOfDay()->diffInDays(Carbon::parse($today)),
            ])->values()->all(),
            'on_leave'       => $s['on_leave']->map(fn ($l) => [
                'person'  => $l['user']->name,
                'back_on' => $l['back']?->toDateString(),
            ])->all(),
        ];
    }
}

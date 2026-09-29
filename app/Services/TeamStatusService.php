<?php

namespace App\Services;

use App\Models\Blocker;
use App\Models\EmployeeStatus;
use App\Models\LeaveApplication;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Live "how is my team doing" data for the help agent: who logged work today,
 * who has open blockers, and who is on leave — for the asking user's team.
 *
 * Team = the teams the user leads (plus direct reports); for owner/admin/hr the
 * whole organization; otherwise the user's own team; otherwise direct reports.
 */
class TeamStatusService
{
    private const NAME_LIMIT = 6;

    /** @return array{label: string, members: Collection<int, User>} */
    public function scopeFor(User $user): array
    {
        $orgId = $user->organization_id;
        if (!$orgId) {
            return ['label' => '', 'members' => collect()];
        }

        $people = fn () => User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->where('onboarding_status', 'active')
            ->where('id', '!=', $user->id)
            ->orderBy('name');

        $led = Team::where('organization_id', $orgId)->where('team_lead_id', $user->id)->get(['id', 'name']);
        if ($led->isNotEmpty()) {
            return [
                'label'   => $led->pluck('name')->implode(' & '),
                'members' => $people()->where(fn ($q) => $q
                    ->whereIn('team_id', $led->pluck('id'))
                    ->orWhere('reporting_manager_id', $user->id))->get(['id', 'name']),
            ];
        }

        if ($user->hasAnyRole(['owner', 'admin', 'hr', 'super_admin'])) {
            return ['label' => $user->organization?->name ?? 'Your organization', 'members' => $people()->get(['id', 'name'])];
        }

        if ($user->team_id) {
            return [
                'label'   => Team::whereKey($user->team_id)->value('name') ?? 'Your team',
                'members' => $people()->where('team_id', $user->team_id)->get(['id', 'name']),
            ];
        }

        return ['label' => 'Your direct reports', 'members' => $people()->where('reporting_manager_id', $user->id)->get(['id', 'name'])];
    }

    /**
     * @return array{label: string, members: Collection, logged: Collection, not_logged: Collection,
     *               on_leave: Collection, blockers: Collection, blocked_people: Collection}
     */
    public function snapshot(User $user, string $today): array
    {
        ['label' => $label, 'members' => $members] = $this->scopeFor($user);
        $ids   = $members->pluck('id');
        $day   = Carbon::parse($today);

        // user_id => date they're back (last leave day + 1)
        $onLeave = LeaveApplication::whereIn('user_id', $ids)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)
            ->get(['user_id', 'to_date'])
            ->mapWithKeys(fn ($l) => [$l->user_id => Carbon::parse($l->to_date)->addDay()]);

        EmployeeStatus::whereIn('user_id', $ids)
            ->where('status', 'on_leave')
            ->where('starts_at', '<=', $day->copy()->endOfDay())
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $day->copy()->startOfDay()))
            ->get(['user_id', 'ends_at'])
            ->each(function ($s) use (&$onLeave) {
                if (!$onLeave->has($s->user_id)) {
                    $onLeave[$s->user_id] = $s->ends_at ? Carbon::parse($s->ends_at)->addDay()->startOfDay() : null;
                }
            });

        $loggedIds = WorkLog::withoutGlobalScopes()
            ->whereIn('user_id', $ids)
            ->whereDate('log_date', $today)
            ->distinct()
            ->pluck('user_id');

        $blockers = Blocker::whereIn('blocked_user_id', $ids)
            ->where('status', '!=', 'resolved')
            ->orderBy('created_at')
            ->get(['id', 'blocked_user_id', 'title', 'created_at']);

        $available = $members->reject(fn ($m) => $onLeave->has($m->id));

        return [
            'label'          => $label,
            'members'        => $members,
            'logged'         => $available->filter(fn ($m) => $loggedIds->contains($m->id))->values(),
            'not_logged'     => $available->reject(fn ($m) => $loggedIds->contains($m->id))->values(),
            'on_leave'       => $members->filter(fn ($m) => $onLeave->has($m->id))
                                    ->map(fn ($m) => ['user' => $m, 'back' => $onLeave[$m->id]])->values(),
            'blockers'       => $blockers,
            'blocked_people' => $members->filter(fn ($m) => $blockers->contains('blocked_user_id', $m->id))->values(),
        ];
    }

    /** Plain-text summary for the chat widget (**bold** and line breaks only). */
    public function summary(User $user, string $today): string
    {
        $s = $this->snapshot($user, $today);

        if ($s['members']->isEmpty()) {
            return "You're not part of a team yet, so there's no team status to show. Ask your admin to add you to a team.";
        }

        $day   = Carbon::parse($today);
        $names = $s['members']->pluck('name', 'id');
        $lines = ["**{$s['label']}** — " . $day->format('D j M') . ' (' . $s['members']->count() . ' people)'];

        $available = $s['logged']->count() + $s['not_logged']->count();
        $lines[] = $day->isWeekend()
            ? "✅ Logged work: {$s['logged']->count()} of {$available} (it's the weekend)"
            : "✅ Logged work: {$s['logged']->count()} of {$available}";
        if (!$day->isWeekend() && $s['not_logged']->isNotEmpty()) {
            $lines[] = '⏳ Not logged yet: ' . $this->names($s['not_logged']->pluck('name'));
        }

        if ($s['blockers']->isEmpty()) {
            $lines[] = '🚫 No open blockers.';
        } else {
            $items = $s['blockers']->take(5)->map(fn ($b) => ($names[$b->blocked_user_id] ?? 'Someone')
                . ' — ' . $b->title . ' (' . $this->age($b->created_at, $day) . ')');
            $more  = $s['blockers']->count() > 5 ? ' and ' . ($s['blockers']->count() - 5) . ' more' : '';
            $lines[] = "🚫 Open blockers ({$s['blockers']->count()}): " . $items->implode('; ') . $more;
        }

        if ($s['on_leave']->isNotEmpty()) {
            $lines[] = '🌴 On leave: ' . $s['on_leave']
                ->map(fn ($l) => $l['user']->name . ($l['back'] ? ' (back ' . $l['back']->format('j M') . ')' : ''))
                ->implode(', ');
        }

        $lines[] = $s['blocked_people']->isNotEmpty()
            ? '🆘 Needs help: ' . $this->names($s['blocked_people']->pluck('name')) . ' — they have open blockers.'
            : '🆘 Nobody is blocked right now.';

        return implode("\n", $lines);
    }

    private function names(Collection $names): string
    {
        $shown = $names->take(self::NAME_LIMIT)->implode(', ');
        $rest  = $names->count() - self::NAME_LIMIT;

        return $rest > 0 ? "{$shown} and {$rest} more" : $shown;
    }

    private function age(Carbon $since, Carbon $day): string
    {
        $days = (int) $since->copy()->startOfDay()->diffInDays($day->copy()->startOfDay());

        return match (true) {
            $days <= 0 => 'since today',
            $days === 1 => '1 day',
            default => "{$days} days",
        };
    }
}

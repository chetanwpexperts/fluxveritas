<?php

namespace App\Services\Outy;

use App\Models\User;
use App\Services\Outy\Tools;
use Illuminate\Support\Collection;

/** All Outy tools, and the subset a given user may use. */
class ToolRegistry
{
    /** @var class-string<Tools\OutyTool>[] */
    public const TOOLS = [
        Tools\GetTeamStatus::class,
        Tools\WhoNotLoggedIn::class,
        Tools\GetMyLeaveBalance::class,
        Tools\GetMyTasks::class,
        Tools\GetMyIncrement::class,
        Tools\GetOrgStats::class,
        Tools\GetSystemHealth::class,
        Tools\ExplainFeature::class,
        Tools\GetPendingLeaveRequests::class,
        // Actions — prepare a confirm card; run only after the user confirms
        Tools\ApplyLeave::class,
        Tools\ApproveLeave::class,
        Tools\RejectLeave::class,
        Tools\CreateAnnouncement::class,
        Tools\RaiseBlocker::class,
    ];

    /** @return Collection<string, Tools\OutyTool> keyed by tool name */
    public function all(): Collection
    {
        return collect(self::TOOLS)
            ->map(fn ($class) => app($class))
            ->keyBy(fn (Tools\OutyTool $tool) => $tool->name());
    }

    /** @return Collection<string, Tools\OutyTool> tools the user's role, permissions and plan allow */
    public function forUser(User $user): Collection
    {
        return $this->all()->filter(fn (Tools\OutyTool $tool) => $tool->allows($user));
    }
}

<?php

namespace App\Services\Outy\Tools;

use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use App\Services\ModuleService;

/** Organization overview for owners and admins. */
class GetOrgStats extends OutyTool
{
    protected array $roles = ['owner', 'admin'];

    public function name(): string
    {
        return 'get_org_stats';
    }

    public function description(): string
    {
        return "Overview of the user's organization: active and deactivated headcount, pending sign-up approvals, departments, teams, "
            . 'plan and subscription, and which modules are enabled.';
    }

    protected function handle(User $user, array $args): array
    {
        $org    = $user->organization;
        $people = User::where('organization_id', $org->id);

        return [
            'organization'      => $org->name,
            'headcount_active'  => (clone $people)->where('is_active', true)->where('onboarding_status', 'active')->count(),
            'deactivated'       => (clone $people)->where('is_active', false)->count(),
            'pending_approvals' => (clone $people)->where('onboarding_status', 'pending')->count(),
            'departments'       => Department::withoutGlobalScopes()->where('organization_id', $org->id)->count(),
            'teams'             => Team::where('organization_id', $org->id)->count(),
            'plan'              => $org->effectivePlan(),
            'plan_expires_on'   => $org->plan_expires_at?->toDateString(),
            'enabled_modules'   => collect(app(ModuleService::class)->getOrgModules($org->id))
                ->where('is_enabled', true)->pluck('label')->values()->all(),
        ];
    }
}

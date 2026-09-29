<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationModule;
use App\Models\User;

class ModuleService
{
    private array $planModules = [
        'free' => [
            'github_sync',
            'employee_directory',
            'document_center',
            'leave_management',
            'onboarding',
            'announcements',
        ],
        'pro' => [
            'github_sync',
            'employee_directory',
            'document_center',
            'leave_management',
            'onboarding',
            'announcements',
            'fairness_engine',
            'ai_intelligence',
            'reports',
            'hr_reports',
            'blockers',
            'increment_calculator',
            'peer_feedback',
        ],
        'enterprise' => [
            'github_sync',
            'employee_directory',
            'document_center',
            'leave_management',
            'onboarding',
            'announcements',
            'fairness_engine',
            'ai_intelligence',
            'reports',
            'hr_reports',
            'blockers',
            'increment_calculator',
            'peer_feedback',
            'command_center',
            'api_access',
        ],
    ];

    private array $modulesMeta = [
        'github_sync' => [
            'label'         => 'GitHub Sync',
            'description'   => 'Track commits and PRs',
            'icon'          => 'github',
            'plan_required' => 'free',
        ],
        'fairness_engine' => [
            'label'         => 'Fairness Engine',
            'description'   => 'Bias detection and flags',
            'icon'          => 'shield',
            'plan_required' => 'pro',
        ],
        'ai_intelligence' => [
            'label'         => 'AI Intelligence',
            'description'   => 'AI summaries and queries',
            'icon'          => 'cpu',
            'plan_required' => 'pro',
        ],
        'blockers' => [
            'label'         => 'Blockers & Dependencies',
            'description'   => 'Track blockers',
            'icon'          => 'alert',
            'plan_required' => 'pro',
        ],
        'command_center' => [
            'label'         => 'Command Center',
            'description'   => 'CEO view',
            'icon'          => 'monitor',
            'plan_required' => 'enterprise',
        ],
        'reports' => [
            'label'         => 'Reports',
            'description'   => 'Analytics and exports',
            'icon'          => 'chart',
            'plan_required' => 'pro',
        ],
        'api_access' => [
            'label'         => 'API Access',
            'description'   => 'REST API',
            'icon'          => 'code',
            'plan_required' => 'enterprise',
        ],
        'employee_directory' => [
            'label'         => 'Employee Directory',
            'description'   => 'Employee profiles, skills, and contacts',
            'icon'          => 'users',
            'plan_required' => 'free',
        ],
        'document_center' => [
            'label'         => 'Document Center',
            'description'   => 'Company, department, and personal documents',
            'icon'          => 'folder',
            'plan_required' => 'free',
        ],
        'leave_management' => [
            'label'         => 'Leave Management',
            'description'   => 'Leave types, applications, and approvals',
            'icon'          => 'calendar',
            'plan_required' => 'free',
        ],
        'onboarding' => [
            'label'         => 'Onboarding',
            'description'   => 'New employee onboarding checklists',
            'icon'          => 'check',
            'plan_required' => 'free',
        ],
        'announcements' => [
            'label'         => 'Announcements',
            'description'   => 'Org, department, and team announcements',
            'icon'          => 'bell',
            'plan_required' => 'free',
        ],
        'hr_reports' => [
            'label'         => 'HR Reports',
            'description'   => 'Headcount, leave, and profile analytics',
            'icon'          => 'chart',
            'plan_required' => 'pro',
        ],
        'increment_calculator' => [
            'label'         => 'Increment Calculator',
            'description'   => 'Monthly contribution scores and increment recommendations',
            'icon'          => 'chart',
            'plan_required' => 'pro',
        ],
        'peer_feedback' => [
            'label'         => 'Peer Feedback',
            'description'   => 'Peer reviews and feedback bias reports',
            'icon'          => 'users',
            'plan_required' => 'pro',
        ],
    ];

    /**
     * An org can use a module when its current plan includes it, unless the
     * org has switched it off. Modules outside the plan only work when a
     * platform super admin granted them — org admins cannot unlock paid modules.
     */
    public function hasModule(int $orgId, string $moduleName): bool
    {
        $org      = Organization::find($orgId);
        $inPlan   = in_array($moduleName, $this->getPlanModules($org?->effectivePlan() ?? 'free'));
        $override = OrganizationModule::where('organization_id', $orgId)
            ->where('module_name', $moduleName)
            ->first();

        return $this->resolve($inPlan, $override);
    }

    public function isInPlan(Organization $org, string $moduleName): bool
    {
        return in_array($moduleName, $this->getPlanModules($org->effectivePlan()));
    }

    private function resolve(bool $inPlan, ?OrganizationModule $override): bool
    {
        if (!$override) {
            return $inPlan;
        }

        if (!$override->is_enabled) {
            return false;
        }

        return $inPlan || $this->isPlatformGrant($override);
    }

    private function isPlatformGrant(OrganizationModule $override): bool
    {
        return $override->enabled_by
            && User::find($override->enabled_by)?->hasRole('super_admin');
    }

    public function enableModule(int $orgId, string $moduleName, int $enabledBy): void
    {
        OrganizationModule::updateOrCreate(
            ['organization_id' => $orgId, 'module_name' => $moduleName],
            ['is_enabled' => true, 'enabled_at' => now(), 'disabled_at' => null, 'enabled_by' => $enabledBy]
        );
    }

    public function disableModule(int $orgId, string $moduleName, int $disabledBy): void
    {
        OrganizationModule::updateOrCreate(
            ['organization_id' => $orgId, 'module_name' => $moduleName],
            ['is_enabled' => false, 'disabled_at' => now(), 'enabled_by' => $disabledBy]
        );
    }

    public function getOrgModules(int $orgId): array
    {
        $org      = Organization::find($orgId);
        $planMods = $this->getPlanModules($org?->effectivePlan() ?? 'free');

        $orgOverrides = OrganizationModule::where('organization_id', $orgId)
            ->get()
            ->keyBy('module_name');

        return collect($this->modulesMeta)
            ->map(function ($module, $name) use ($orgOverrides, $planMods) {
                $override       = $orgOverrides->get($name);
                $includedInPlan = in_array($name, $planMods);
                $isEnabled      = $this->resolve($includedInPlan, $override);

                return array_merge($module, [
                    'name'            => $name,
                    'is_enabled'      => $isEnabled,
                    'included_in_plan'=> $includedInPlan,
                    'override'        => $override !== null,
                    'enabled_at'      => $override?->enabled_at,
                    'override_record' => $override,
                ]);
            })
            ->values()
            ->toArray();
    }

    public function getPlanModules(string $plan): array
    {
        return $this->planModules[$plan] ?? $this->planModules['free'];
    }

    public function getAllPlanModules(): array
    {
        return $this->planModules;
    }

    public function getModulesMeta(): array
    {
        return $this->modulesMeta;
    }
}

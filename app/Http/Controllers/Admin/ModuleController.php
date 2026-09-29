<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ModuleService;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    public function __construct(private ModuleService $moduleService) {}

    public function index()
    {
        $user    = auth()->user();
        $org     = $user->organization;
        $plan    = $org?->plan ?? 'free';
        $modules = $this->moduleService->getOrgModules($org->id);

        return view('admin.modules', compact('org', 'plan', 'modules'));
    }

    public function toggle(Request $request, string $moduleName)
    {
        $request->validate(['action' => 'required|in:enable,disable']);

        $user = auth()->user();
        $org  = $user->organization;

        if ($request->action === 'enable') {
            if (!$this->moduleService->isInPlan($org, $moduleName)) {
                $meta = $this->moduleService->getModulesMeta()[$moduleName] ?? [];
                return back()->with('error', ($meta['label'] ?? 'This module') . ' is part of the '
                    . ucfirst($meta['plan_required'] ?? 'Pro') . ' plan. Upgrade from Billing to enable it.');
            }

            $this->moduleService->enableModule($org->id, $moduleName, $user->id);
            $label = ucwords(str_replace('_', ' ', $moduleName));
            return back()->with('success', "{$label} has been enabled.");
        } else {
            $this->moduleService->disableModule($org->id, $moduleName, $user->id);
            $label = ucwords(str_replace('_', ' ', $moduleName));
            return back()->with('success', "{$label} has been disabled.");
        }
    }
}

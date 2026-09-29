<?php

namespace App\Http\Middleware;

use App\Services\ModuleService;
use Closure;
use Illuminate\Http\Request;

class CheckModule
{
    public function handle(Request $request, Closure $next, string $moduleName): mixed
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        if (!$user->organization_id) {
            return redirect()->route('dashboard')
                ->with('error', 'No organization found.');
        }

        $moduleService = new ModuleService();

        if (!$moduleService->hasModule($user->organization_id, $moduleName)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'Module not available',
                    'message' => 'This feature is not available on your current plan.',
                    'module'  => $moduleName,
                ], 403);
            }

            // Show a proper upgrade page (not a dashboard bounce)
            $meta = $moduleService->getModulesMeta()[$moduleName] ?? null;
            $org  = \App\Models\Organization::find($user->organization_id);

            return response()->view('billing.upgrade-required', [
                'moduleName'  => $moduleName,
                'moduleLabel' => $meta['label'] ?? 'This feature',
                'moduleDesc'  => $meta['description'] ?? '',
                'planNeeded'  => $meta['plan_required'] ?? 'pro',
                'currentPlan' => $org->plan ?? 'free',
                'canBuy'      => $user->hasAnyRole(['owner', 'admin', 'super_admin']),
            ], 403);
        }

        return $next($request);
    }
}

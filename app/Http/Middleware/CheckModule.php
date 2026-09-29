<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use App\Services\BillingService;
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

            // Upgrade page with checkout built in: pay here, then the page reloads into the feature
            $meta       = $moduleService->getModulesMeta()[$moduleName] ?? null;
            $org        = Organization::find($user->organization_id);
            $planNeeded = $meta['plan_required'] ?? 'pro';
            $canBuy     = $user->hasAnyRole(['owner', 'admin']);
            $billing    = app(BillingService::class);

            $quotes = ($canBuy && $planNeeded === 'pro' && $org->effectivePlan() !== 'enterprise') ? [
                'monthly' => $billing->quote($org, 'monthly'),
                'yearly'  => $billing->quote($org, 'yearly'),
            ] : [];

            $owner = $canBuy ? null : User::where('organization_id', $org->id)->role('owner')->first(['id', 'name', 'email']);

            return response()->view('billing.upgrade-required', [
                'moduleName'  => $moduleName,
                'moduleLabel' => $meta['label'] ?? 'This feature',
                'moduleDesc'  => $meta['description'] ?? '',
                'planNeeded'  => $planNeeded,
                'currentPlan' => $org->effectivePlan(),
                'canBuy'      => $canBuy,
                'quotes'      => $quotes,
                'owner'       => $owner,
            ], 403);
        }

        return $next($request);
    }
}

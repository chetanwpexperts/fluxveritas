<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckOnboardingStatus
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        $allowedRoutes = [
            'pending-approval',
            'onboarding.activate',
            'logout',
            'organization.create',
            'organization.store',
        ];

        $currentRoute = $request->route()?->getName();

        if (in_array($currentRoute, $allowedRoutes)) {
            return $next($request);
        }

        // Pending team member with no org — hold at pending page
        if ($user->onboarding_status === 'pending' && !$user->organization_id) {
            return redirect()->route('pending-approval');
        }

        // Rejected — log out and show reason
        if ($user->onboarding_status === 'rejected') {
            auth()->logout();
            return redirect()->route('login')
                ->with('error', 'Your account has been rejected. ' . ($user->rejection_reason ?? 'Please contact support.'));
        }

        // Org creator who hasn't set up their org yet
        if ($user->onboarding_type === 'org_creator' && !$user->organization_id) {
            return redirect()->route('organization.create')
                ->with('info', 'Please set up your organization first.');
        }

        return $next($request);
    }
}

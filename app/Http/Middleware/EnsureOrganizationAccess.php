<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureOrganizationAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Super admins bypass org checks
            if ($user->hasRole('super_admin')) {
                return $next($request);
            }

            // Ensure user belongs to an organization
            if (!$user->organization_id) {
                // Org creator mid-setup: stay logged in, redirect to org setup
                if ($user->onboarding_type === 'org_creator') {
                    $setupRoutes = ['organization.create', 'organization.store', 'logout'];
                    if (!in_array($request->route()?->getName(), $setupRoutes)) {
                        return redirect()->route('organization.create');
                    }
                    return $next($request);
                }

                // Truly orphaned account — log out and surface an error
                Auth::logout();
                return redirect('/login')->with('error',
                    'No organization found. Please contact support.');
            }

            // Store org_id in session for query scoping
            session(['current_org_id' => $user->organization_id]);
        }

        return $next($request);
    }
}

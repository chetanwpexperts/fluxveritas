<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureOrganizationAccess
{
    /**
     * Pages a super_admin can open without being inside an organization:
     * the platform panel, their own account, public pages, and the
     * background requests the app layout makes on every page.
     */
    private const PLATFORM_ROUTES = [
        'superadmin.*', 'agent.*', 'dashboard', 'logout',
        'profile.*', 'password.*', 'verification.*',
        'settings.index', 'settings.profile', 'settings.profile.update', 'settings.password.update',
        'settings.notifications', 'settings.notifications.update',
        'settings.platform', 'settings.ai', 'settings.ai.*', 'settings.security', 'settings.audit',
        'notifications.*', 'help.*', 'search',
        'home', 'tour', 'docs', 'contact', 'contact.submit', 'pricing', 'refund-policy',
        'fairness.verify', 'fairness.badge', 'peer-feedback.*', 'action.execute', 'team.accept', 'billing.webhook',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Super admins skip membership checks, but org pages need an org to show.
            // Without one they are sent to pick an organization instead of hitting a 500/404.
            if ($user->hasRole('super_admin')) {
                if (!$user->organization_id && $request->route()?->getName() && !$request->routeIs(...self::PLATFORM_ROUTES)) {
                    $message = 'That page belongs to an organization. Choose one in the Super Admin organization switcher at the top of the page to open it.';

                    return $request->expectsJson()
                        ? response()->json(['message' => $message], 409)
                        : redirect()->route('superadmin.organizations')->with('info', $message);
                }

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

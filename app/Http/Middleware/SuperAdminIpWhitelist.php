<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SuperAdminIpWhitelist
{
    // Add production IP addresses here; localhost entries allow local dev
    protected $allowedIps = [
        '127.0.0.1',
        '::1',
        // Add your production server IP here
    ];

    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->hasRole('super_admin')) {
            // Log all super admin access attempts
            Log::channel('audit')->info('SuperAdmin access', [
                'user'       => Auth::user()->email,
                'ip'         => $request->ip(),
                'url'        => $request->fullUrl(),
                'method'     => $request->method(),
                'user_agent' => $request->userAgent(),
                'time'       => now()->toDateTimeString(),
            ]);
        }

        return $next($request);
    }
}

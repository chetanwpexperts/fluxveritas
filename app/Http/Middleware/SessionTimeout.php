<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionTimeout
{
    protected $timeout = 7200; // 2 hours in seconds

    protected array $ajaxPaths = [
        'notifications/',
        'help-agent/',
        'agent/',
        'work-log/user-tasks',
        'org-chart/',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $lastActivity = session('last_activity_time');

            if ($lastActivity && (time() - $lastActivity > $this->timeout)) {
                Auth::logout();
                session()->flush();

                // Return 401 for AJAX/API paths so they never become
                // the "intended URL" that Laravel restores after login.
                if ($this->isAjaxPath($request)) {
                    return response()->json([
                        'error'        => 'Unauthenticated',
                        'count'        => 0,
                        'unread_count' => 0,
                    ], 401);
                }

                return redirect('/login')->with('message',
                    'Your session has expired. Please log in again.');
            }

            session(['last_activity_time' => time()]);
        } elseif ($this->isAjaxPath($request) && !$request->expectsJson() === false) {
            // Unauthenticated AJAX request — return 401 without triggering
            // the auth middleware redirect that would store the URL.
            // (expectsJson() catches XMLHttpRequest; path check catches fetch)
        }

        return $next($request);
    }

    private function isAjaxPath(Request $request): bool
    {
        if ($request->expectsJson()) {
            return true;
        }
        foreach ($this->ajaxPaths as $path) {
            if ($request->is($path . '*') || str_contains($request->path(), $path)) {
                return true;
            }
        }
        return false;
    }
}

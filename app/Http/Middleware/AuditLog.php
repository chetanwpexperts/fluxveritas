<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditLog
{
    protected $sensitiveRoutes = [
        'increment',
        'fairness',
        'admin',
        'superadmin',
        'settings',
        'users',
        'permissions',
        'agent',
    ];

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only log write operations and sensitive route access
        $isWrite = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']);

        foreach ($this->sensitiveRoutes as $route) {
            if (str_contains($request->path(), $route)) {
                if ($isWrite || $request->method() === 'GET') {
                    Log::channel('audit')->info('Sensitive route accessed', [
                        'user_id'    => Auth::id(),
                        'email'      => Auth::user()?->email,
                        'role'       => Auth::user()?->getRoleNames(),
                        'method'     => $request->method(),
                        'path'       => $request->path(),
                        'ip'         => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'status'     => $response->getStatusCode(),
                        'time'       => now()->toDateTimeString(),
                    ]);
                }
                break;
            }
        }

        return $response;
    }
}

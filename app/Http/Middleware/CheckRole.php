<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!auth()->user()->hasAnyRole($roles)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'Unauthorized',
                    'message' => 'You do not have the required role.',
                ], 403);
            }

            abort(403, 'Access denied. Insufficient role.');
        }

        return $next($request);
    }
}

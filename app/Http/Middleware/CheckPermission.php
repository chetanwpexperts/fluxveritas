<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!auth()->user()->can($permission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'Unauthorized',
                    'message' => 'You do not have permission to perform this action.',
                ], 403);
            }

            return redirect()->back()
                ->with('error', 'You do not have permission to access this.');
        }

        return $next($request);
    }
}

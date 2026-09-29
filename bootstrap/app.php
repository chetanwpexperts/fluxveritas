<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware — applied to every request
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Web middleware group additions
        $middleware->web(append: [
            \App\Http\Middleware\SessionTimeout::class,
            \App\Http\Middleware\EnsureOrganizationAccess::class,
            \App\Http\Middleware\AuditLog::class,
        ]);

        // Named middleware aliases
        $middleware->alias([
            'role'                => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'          => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission'  => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'check.role'          => \App\Http\Middleware\CheckRole::class,
            'check.permission'    => \App\Http\Middleware\CheckPermission::class,
            'check.onboarding'    => \App\Http\Middleware\CheckOnboardingStatus::class,
            'super_admin'         => \App\Http\Middleware\SuperAdminMiddleware::class,
            'module'              => \App\Http\Middleware\CheckModule::class,
            'superadmin.audit'    => \App\Http\Middleware\SuperAdminIpWhitelist::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (
            \Illuminate\Auth\AuthenticationException $e,
            \Illuminate\Http\Request $request
        ) {
            $ajaxPaths = [
                'notifications/',
                'help-agent/',
                'agent/',
                'work-log/user-tasks',
                'org-chart/',
            ];

            $isAjax = $request->expectsJson();
            if (!$isAjax) {
                foreach ($ajaxPaths as $path) {
                    if (str_contains($request->path(), $path)) {
                        $isAjax = true;
                        break;
                    }
                }
            }

            if ($isAjax) {
                return response()->json([
                    'error'        => 'Unauthenticated',
                    'count'        => 0,
                    'unread_count' => 0,
                ], 401);
            }

            return redirect()->guest(route('login'))
                ->with('message', 'Your session expired. Please log in again.');
        });
    })->create();

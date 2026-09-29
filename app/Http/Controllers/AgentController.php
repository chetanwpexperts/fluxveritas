<?php

namespace App\Http\Controllers;

use App\Models\AgentNotification;
use App\Services\AutonomousAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function health(): View
    {
        abort_if(!auth()->user()->hasRole('super_admin'), 403);

        $health   = Cache::get('system_health', []);
        $security = Cache::get('security_scan', []);

        if (empty($health)) {
            $agent  = new AutonomousAgent();
            $health = $agent->checkSystemHealth();
        }

        $overallStatus = 'healthy';
        foreach ($health as $check) {
            if (($check['status'] ?? '') === 'critical') { $overallStatus = 'critical'; break; }
            if (($check['status'] ?? '') === 'warning')  { $overallStatus = 'warning'; }
        }

        $checkedAt  = Cache::get('system_health_at', 'Never');
        $scannedAt  = Cache::get('security_scan_at',  'Never');

        $recentActions = AgentNotification::where('notification_type', 'system_health')
            ->orWhere('notification_type', 'work_log_reminder')
            ->orWhere('notification_type', 'blocker_escalation')
            ->orWhere('notification_type', 'fairness_alert')
            ->orWhere('notification_type', 'daily_digest')
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        return view('agent.health', compact(
            'health', 'security', 'overallStatus',
            'checkedAt', 'scannedAt', 'recentActions'
        ));
    }

    public function runNow(Request $request): JsonResponse
    {
        abort_if(!auth()->user()->hasRole('super_admin'), 403);

        $agent    = new AutonomousAgent();
        $results  = $agent->run();
        $health   = Cache::get('system_health', []);
        $security = Cache::get('security_scan', []);

        $overallStatus = 'healthy';
        foreach ($health as $check) {
            if (($check['status'] ?? '') === 'critical') { $overallStatus = 'critical'; break; }
            if (($check['status'] ?? '') === 'warning')  { $overallStatus = 'warning'; }
        }

        return response()->json([
            'success'         => true,
            'actions'         => count($results),
            'results'         => $results,
            'health'          => $health,
            'overall_status'  => $overallStatus,
            'security_issues' => count($security),
        ]);
    }

    public function clearCache(): RedirectResponse
    {
        abort_if(!auth()->user()->hasRole('super_admin'), 403);

        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');

        return back()->with('success', 'All caches cleared successfully!');
    }

    public function clearLogs(): RedirectResponse
    {
        abort_if(!auth()->user()->hasRole('super_admin'), 403);

        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }

        return back()->with('success', 'Log file cleared!');
    }

    public function optimizeApp(): RedirectResponse
    {
        abort_if(!auth()->user()->hasRole('super_admin'), 403);

        Artisan::call('optimize');

        return back()->with('success', 'Application optimized!');
    }
}

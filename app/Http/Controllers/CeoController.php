<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Blocker;
use App\Models\Department;
use App\Models\EmployeeStatus;
use App\Models\FairnessFlag;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkLog;
use App\Services\AI\AiEngine;
use App\Services\ModuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CeoController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        if (!$user->hasAnyRole(['ceo', 'super_admin'])) {
            abort(403, 'Access denied. CEO only.');
        }

        $members = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->with(['roles', 'department', 'employeeStatuses'])
            ->get()
            ->map(function ($member) {
                $commits = Activity::where('user_id', $member->id)
                    ->where('event_type', 'commit')
                    ->where('occurred_at', '>=', now()->subDays(30))
                    ->count();

                $prs = Activity::where('user_id', $member->id)
                    ->whereIn('event_type', ['pr_opened', 'pr_merged'])
                    ->where('occurred_at', '>=', now()->subDays(30))
                    ->count();

                $avgScore = Activity::where('user_id', $member->id)
                    ->where('occurred_at', '>=', now()->subDays(30))
                    ->avg('complexity_score') ?? 0;

                $commitsThisWeek = Activity::where('user_id', $member->id)
                    ->where('event_type', 'commit')
                    ->where('occurred_at', '>=', now()->subDays(7))
                    ->count();

                $lastActivity = Activity::where('user_id', $member->id)
                    ->orderBy('occurred_at', 'desc')
                    ->first();

                $openBlockers = Blocker::where('blocked_user_id', $member->id)
                    ->where('status', 'open')
                    ->count();

                $pendingFlags = FairnessFlag::where('flagged_user_id', $member->id)
                    ->where('status', 'pending')
                    ->count();

                $currentStatus = EmployeeStatus::where('user_id', $member->id)
                    ->where('starts_at', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();

                $performanceScore = $this->calculatePerformance(
                    $commits, $prs, $avgScore,
                    $openBlockers, $pendingFlags
                );

                $trend = $this->calculateTrend($member->id);

                // Extra data for expandable rows
                $recentActivities = Activity::where('user_id', $member->id)
                    ->orderBy('occurred_at', 'desc')
                    ->take(5)
                    ->get(['event_type', 'occurred_at', 'complexity_score', 'metadata']);

                $openBlockerList = Blocker::where('blocked_user_id', $member->id)
                    ->where('status', 'open')
                    ->with('blockingUser')
                    ->get(['id', 'title', 'priority', 'blocker_type', 'blocking_user_id', 'created_at']);

                $pendingFlagList = FairnessFlag::where('flagged_user_id', $member->id)
                    ->where('status', 'pending')
                    ->get(['id', 'flag_type', 'confidence_score', 'status', 'evidence', 'created_at']);

                return [
                    'id'                  => $member->id,
                    'name'                => $member->name,
                    'email'               => $member->email,
                    'role'                => $member->role,
                    'github_username'     => $member->github_username,
                    'avatar'              => strtoupper(substr($member->name, 0, 2)),
                    'commits'             => $commits,
                    'prs'                 => $prs,
                    'avg_score'           => round($avgScore, 2),
                    'commits_this_week'   => $commitsThisWeek,
                    'last_activity'       => $lastActivity?->occurred_at,
                    'open_blockers'       => $openBlockers,
                    'pending_flags'       => $pendingFlags,
                    'performance_score'   => $performanceScore,
                    'trend'               => $trend,
                    'status'              => $currentStatus?->status ?? 'active',
                    'status_reason'       => $currentStatus?->reason,
                    'status_ends'         => $currentStatus?->ends_at,
                    'recent_activities'   => $recentActivities,
                    'open_blocker_list'   => $openBlockerList,
                    'pending_flag_list'   => $pendingFlagList,
                ];
            })
            ->sortByDesc('performance_score')
            ->values();

        $overview = [
            'total_members'   => $members->count(),
            'total_commits'   => $members->sum('commits'),
            'total_prs'       => $members->sum('prs'),
            'total_blockers'  => Blocker::where('organization_id', $orgId)->where('status', 'open')->count(),
            'total_flags'     => FairnessFlag::where('organization_id', $orgId)->where('status', 'pending')->count(),
            'active_projects' => Project::where('organization_id', $orgId)->where('status', 'active')->count(),
            'top_performer'   => $members->first()['name'] ?? 'N/A',
            'needs_attention' => $members->filter(
                fn($m) => $m['pending_flags'] > 0 || $m['open_blockers'] > 0
            )->count(),
        ];

        $recentFlags = FairnessFlag::where('organization_id', $orgId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->with(['flaggedUser'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $recentBlockers = Blocker::where('organization_id', $orgId)
            ->where('status', 'open')
            ->with(['blockedUser', 'blockingUser', 'project'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $engine = new AiEngine($orgId);
        $aiData = [
            'total_members'    => $overview['total_members'],
            'active_members'   => $members->where('status', 'active')->count(),
            'commits_this_week'=> $members->sum('commits_this_week'),
            'prs_this_week'    => $overview['total_prs'],
            'open_blockers'    => $overview['total_blockers'],
            'pending_flags'    => $overview['total_flags'],
            'top_contributor'  => $overview['top_performer'],
            'inactive_count'   => $members->where('commits', 0)->count(),
            'project_names'    => Project::where('organization_id', $orgId)->pluck('name')->join(', '),
        ];

        $aiSummary = Cache::remember(
            "ceo_summary_{$orgId}_" . now()->format('Y-m-d-H'),
            3600,
            fn() => $engine->generateSummary($aiData)
        );

        // Chart data
        $chartMembers = $members->pluck('name')->values();
        $chartCommits = $members->pluck('commits')->values();
        $chartScores  = $members->pluck('performance_score')->values();

        $excellent  = $members->filter(fn ($m) => $m['performance_score'] >= 80)->count();
        $good       = $members->filter(fn ($m) => $m['performance_score'] >= 60 && $m['performance_score'] < 80)->count();
        $fair       = $members->filter(fn ($m) => $m['performance_score'] >= 40 && $m['performance_score'] < 60)->count();
        $needsHelp  = $members->filter(fn ($m) => $m['performance_score'] < 40)->count();

        $last7Days = collect(range(6, 0))->map(fn ($d) => now()->subDays($d)->format('Y-m-d'));

        $trendActivity = Activity::where('organization_id', $orgId)
            ->where('occurred_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(occurred_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get();

        $trendLabels = $last7Days->map(fn ($d) => \Carbon\Carbon::parse($d)->format('M d'));
        $trendCounts = $last7Days->map(fn ($date) => $trendActivity->firstWhere('date', $date)?->count ?? 0);

        $moduleService = new ModuleService();
        $modules = [
            'github_sync'     => $moduleService->hasModule($orgId, 'github_sync'),
            'fairness_engine' => $moduleService->hasModule($orgId, 'fairness_engine'),
            'ai_intelligence' => $moduleService->hasModule($orgId, 'ai_intelligence'),
            'blockers'        => $moduleService->hasModule($orgId, 'blockers'),
        ];

        // Department breakdown
        $departments = Department::where('organization_id', $orgId)
            ->with('users')
            ->get()
            ->map(function ($dept) {
                $memberIds = $dept->users->pluck('id');

                $todayLogs = WorkLog::whereIn('user_id', $memberIds)
                    ->where('log_date', today())
                    ->count();

                $weekHours = WorkLog::whereIn('user_id', $memberIds)
                    ->where('log_date', '>=', now()->startOfWeek())
                    ->sum('duration_minutes');

                $githubActivity = Activity::whereIn('user_id', $memberIds)
                    ->where('occurred_at', '>=', now()->subDays(7))
                    ->count();

                return [
                    'id'              => $dept->id,
                    'name'            => $dept->name,
                    'color'           => $dept->color,
                    'type'            => $dept->type,
                    'work_mode'       => $dept->work_mode,
                    'member_count'    => $dept->users->count(),
                    'today_logs'      => $todayLogs,
                    'week_hours'      => round($weekHours / 60, 1),
                    'github_activity' => $githubActivity,
                ];
            });

        return view('ceo.index', compact(
            'members', 'overview', 'recentFlags', 'recentBlockers', 'aiSummary',
            'chartMembers', 'chartCommits', 'chartScores',
            'excellent', 'good', 'fair', 'needsHelp',
            'trendLabels', 'trendCounts', 'modules', 'departments'
        ));
    }

    private function calculatePerformance(
        int $commits, int $prs, float $avgScore,
        int $blockers, int $flags
    ): int {
        $score  = 0;
        $score += min($commits * 5, 40);
        $score += min($prs * 10, 30);
        $score += min($avgScore * 10, 20);
        $score -= $blockers * 5;
        $score -= $flags * 10;
        return max(0, min(100, (int) $score));
    }

    private function calculateTrend(int $userId): string
    {
        $thisWeek = Activity::where('user_id', $userId)
            ->where('occurred_at', '>=', now()->subDays(7))
            ->count();

        $lastWeek = Activity::where('user_id', $userId)
            ->where('occurred_at', '>=', now()->subDays(14))
            ->where('occurred_at', '<', now()->subDays(7))
            ->count();

        if ($lastWeek === 0 && $thisWeek === 0) return 'neutral';
        if ($lastWeek === 0) return 'up';

        $change = (($thisWeek - $lastWeek) / $lastWeek) * 100;

        if ($change > 10) return 'up';
        if ($change < -10) return 'down';
        return 'neutral';
    }
}

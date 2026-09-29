<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AgentNotification;
use App\Models\Blocker;
use App\Models\Designation;
use App\Models\FairnessFlag;
use App\Models\FeedbackBiasReport;
use App\Models\IncrementReview;
use App\Models\Organization;
use App\Models\PerformanceFeedback;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkLog;
use App\Services\GitHubAnalyzer;
use App\Services\ModuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private GitHubAnalyzer $github) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        // If Super Admin has switched to view a specific organization, show that Organization's Dashboard
        if (session()->has('switched_org')) {
            return $this->orgDashboard();
        }

        if ($user->hasRole('super_admin')) {
            return $this->superAdminDashboard();
        }

        if ($user->hasRole('hr') && !$user->hasAnyRole(['admin', 'owner', 'super_admin'])) {
            return $this->hrDashboard();
        }

        if ($user->hasAnyRole(['admin', 'owner', 'ceo'])) {
            return $this->orgDashboard();
        }

        if ($user->hasRole('manager')) {
            return $this->managerDashboard($user);
        }

        if ($user->hasRole('team_lead')) {
            return $this->teamLeadDashboard($user);
        }

        return $this->employeeDashboard($user);
    }

    private function hrDashboard(): View
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;
        $year  = now()->year;

        $totalEmployees = \App\Models\User::where('organization_id', $orgId)
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'))
            ->count();

        $newThisMonth = \App\Models\User::where('organization_id', $orgId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $pendingLeaves = \App\Models\LeaveApplication::where('organization_id', $orgId)
            ->where('status', 'pending')
            ->with('user', 'leaveType')
            ->latest()
            ->take(5)
            ->get();

        $pendingCount = \App\Models\LeaveApplication::where('organization_id', $orgId)
            ->where('status', 'pending')
            ->count();

        $onLeaveToday = \App\Models\LeaveApplication::where('organization_id', $orgId)
            ->where('status', 'approved')
            ->where('from_date', '<=', now()->toDateString())
            ->where('to_date', '>=', now()->toDateString())
            ->count();

        $incompleteProfiles = \App\Models\User::where('organization_id', $orgId)
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'))
            ->where(function ($q) {
                $q->whereDoesntHave('employeeProfile')
                  ->orWhereHas('employeeProfile', fn($p) =>
                      $p->whereNull('designation')
                        ->orWhereNull('skills')
                  );
            })
            ->count();

        $announcements = \App\Models\Announcement::where('organization_id', $orgId)
            ->latest()
            ->take(3)
            ->get();

        $departments = \App\Models\Department::where('organization_id', $orgId)
            ->withCount('users')
            ->orderByDesc('users_count')
            ->get();

        return view('dashboard.hr', compact(
            'user',
            'totalEmployees',
            'newThisMonth',
            'pendingLeaves',
            'pendingCount',
            'onLeaveToday',
            'incompleteProfiles',
            'announcements',
            'departments'
        ));
    }

    private function orgDashboard(): View
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;
        $today = now()->toDateString();

        $totalUsers  = User::where('organization_id', $orgId)->where('is_active', true)->count();
        $loggedToday = WorkLog::where('organization_id', $orgId)->where('log_date', $today)->distinct('user_id')->count('user_id');

        $openBlockers      = Blocker::where('organization_id', $orgId)->where('status', 'open')->count();
        $escalatedBlockers = Blocker::where('organization_id', $orgId)->where('status', 'escalated')->count();
        $fairnessFlags     = FairnessFlag::where('organization_id', $orgId)->where('status', 'pending')->count();
        $biasReports       = FeedbackBiasReport::where('organization_id', $orgId)->where('bias_detected', true)->where('ceo_reviewed', false)->count();
        $activeSprints     = Sprint::where('organization_id', $orgId)->where('status', 'active')->count();
        $pendingFeedback   = PerformanceFeedback::where('organization_id', $orgId)->where('status', 'draft')->count();
        $pendingIncrements = IncrementReview::where('organization_id', $orgId)->whereIn('status', ['pending', 'calculated'])->count();
        $activeTasks       = Task::whereHas('project', fn($q) => $q->where('organization_id', $orgId))->whereNotIn('status', ['done', 'cancelled'])->count();

        // Factor 1: Today's log consistency (20%)
        $logConsistency = $totalUsers > 0 ? ($loggedToday / $totalUsers) * 100 : 0;

        // Factor 2: Weekly consistency (20%)
        $weekStart       = now()->startOfWeek()->toDateString();
        $weekLoggedIds   = \App\Models\WorkLog::where('organization_id', $orgId)
            ->where('log_date', '>=', $weekStart)
            ->distinct('user_id')->count('user_id');
        $weekConsistency = $totalUsers > 0 ? ($weekLoggedIds / $totalUsers) * 100 : 0;

        // Factor 3: Blocker health (20%) — each open -5pts, each escalated -15pts
        $blockerHealth = max(0, 100 - ($openBlockers * 5) - ($escalatedBlockers * 15));

        // Factor 4: Sprint progress (15%)
        $sprintHealth = 100;
        if ($activeSprints > 0) {
            $avgCompletion = Sprint::where('organization_id', $orgId)
                ->where('status', 'active')
                ->get()
                ->map(function ($sprint) {
                    $total = $sprint->tasks()->count();
                    $done  = $sprint->tasks()->where('status', 'done')->count();
                    return $total > 0 ? round(($done / $total) * 100) : 0;
                })
                ->avg() ?? 100;
            $sprintHealth = $avgCompletion;
        }

        // Factor 5: Feedback completion (15%)
        $currentPeriod     = now()->month <= 3 ? 'Q1' : (now()->month <= 6 ? 'Q2' : (now()->month <= 9 ? 'Q3' : 'Q4'));
        $feedbackDue       = User::where('organization_id', $orgId)->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'team_lead', 'manager']))->count();
        $feedbackSubmitted = PerformanceFeedback::where('organization_id', $orgId)
            ->where('review_period', $currentPeriod)->where('review_year', now()->year)
            ->where('status', '!=', 'draft')->count();
        $feedbackHealth = $feedbackDue > 0
            ? min(100, ($feedbackSubmitted / $feedbackDue) * 100)
            : 100;

        // Factor 6: Task velocity (10%)
        $tasksThisWeek = Task::whereHas('project', fn ($q) => $q->where('organization_id', $orgId))
            ->where('status', 'done')->where('completed_at', '>=', now()->startOfWeek())->count();
        $tasksLastWeek = Task::whereHas('project', fn ($q) => $q->where('organization_id', $orgId))
            ->where('status', 'done')
            ->whereBetween('completed_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->count();
        $taskVelocity = $tasksLastWeek > 0
            ? min(100, ($tasksThisWeek / $tasksLastWeek) * 100)
            : ($tasksThisWeek > 0 ? 80 : 60);

        // Weighted health score
        $healthScore = round(
            ($logConsistency  * 0.20) +
            ($weekConsistency * 0.20) +
            ($blockerHealth   * 0.20) +
            ($sprintHealth    * 0.15) +
            ($feedbackHealth  * 0.15) +
            ($taskVelocity    * 0.10)
        );
        $healthScore  = max(0, min(100, $healthScore));
        $consistency  = round($logConsistency); // keep for compact() compatibility
        $healthStatus = $healthScore >= 80 ? 'healthy' : ($healthScore >= 60 ? 'attention' : 'critical');

        $alerts = [];
        if ($escalatedBlockers > 0) {
            $alerts[] = ['type' => 'critical', 'icon' => '🚨', 'message' => "{$escalatedBlockers} escalated blocker(s) need immediate attention", 'url' => route('dependency.index'), 'label' => 'Resolve'];
        }
        if ($biasReports > 0) {
            $alerts[] = ['type' => 'warning', 'icon' => '⚠️', 'message' => "{$biasReports} unreviewed bias report(s) detected", 'url' => route('feedback.bias-reports'), 'label' => 'Review'];
        }
        if ($fairnessFlags > 0) {
            $alerts[] = ['type' => 'warning', 'icon' => '⚖️', 'message' => "{$fairnessFlags} fairness flag(s) pending review", 'url' => route('fairness.index'), 'label' => 'Review'];
        }
        $notLoggedCount = $totalUsers - $loggedToday;
        if ($notLoggedCount > 3) {
            $alerts[] = ['type' => 'info', 'icon' => '📝', 'message' => "{$notLoggedCount} employees haven't logged work today", 'url' => route('team.index'), 'label' => 'View Team'];
        }
        if ($pendingIncrements > 0) {
            $alerts[] = ['type' => 'info', 'icon' => '💰', 'message' => "{$pendingIncrements} increment review(s) awaiting approval", 'url' => route('increment.reviews'), 'label' => 'Approve'];
        }

        $loggedIds    = WorkLog::where('organization_id', $orgId)->where('log_date', $today)->pluck('user_id')->unique();
        $notLoggedUsers = User::where('organization_id', $orgId)->where('is_active', true)->whereNotIn('id', $loggedIds)->take(8)->get(['id', 'name']);

        $recentNotifications = AgentNotification::forUser($user->id)->active()->unread()->orderByDesc('created_at')->take(5)->get();

        $month  = now()->month;
        $period = $month <= 3 ? 'Q1' : ($month <= 6 ? 'Q2' : ($month <= 9 ? 'Q3' : 'Q4'));

        // GitHub activity summary
        $activitySummary = [
            'total_commits' => Activity::where('organization_id', $orgId)->where('event_type', 'commit')->where('occurred_at', '>=', now()->subDays(30))->count(),
            'total_prs'     => Activity::where('organization_id', $orgId)->whereIn('event_type', ['pr_opened', 'pr_merged', 'pull_request'])->where('occurred_at', '>=', now()->subDays(30))->count(),
            'avg_score'     => round(Activity::where('organization_id', $orgId)->whereNotNull('quality_score')->where('occurred_at', '>=', now()->subDays(30))->avg('quality_score') ?? 0, 1),
            'last_synced'   => Activity::where('organization_id', $orgId)->max('occurred_at'),
        ];

        // Chart data (last 30 days grouped by date)
        $last30Days  = collect(range(29, 0))->map(fn ($d) => now()->subDays($d)->format('Y-m-d'));
        $activityData = Activity::where('organization_id', $orgId)
            ->where('occurred_at', '>=', now()->subDays(30))
            ->selectRaw("DATE(occurred_at) as date,
                SUM(CASE WHEN event_type = 'commit' THEN 1 ELSE 0 END) as commits,
                SUM(CASE WHEN event_type IN ('pr_opened','pr_merged','pull_request') THEN 1 ELSE 0 END) as prs")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chartLabels = $last30Days->map(fn ($d) => \Carbon\Carbon::parse($d)->format('M d'));
        $commitData  = $last30Days->map(fn ($date) => $activityData->firstWhere('date', $date)?->commits ?? 0);
        $prData      = $last30Days->map(fn ($date) => $activityData->firstWhere('date', $date)?->prs ?? 0);

        $recentActivities = Activity::where('organization_id', $orgId)
            ->with('project')
            ->orderByDesc('occurred_at')
            ->take(20)
            ->get();

        // Direct reports
        $myDirectReports = User::where('organization_id', $orgId)
            ->where('reporting_manager_id', $user->id)
            ->where('is_active', true)
            ->count();
        $myReportsLogged = $myDirectReports > 0
            ? User::where('organization_id', $orgId)
                ->where('reporting_manager_id', $user->id)
                ->where('is_active', true)
                ->whereHas('workLogs', fn ($q) => $q->where('log_date', $today))
                ->count()
            : 0;

        // Module flags
        $modules = \App\Models\OrganizationModule::where('organization_id', $orgId)
            ->pluck('is_enabled', 'module_name')
            ->toArray();

        // AI summary (optional — silently skip on any error)
        $aiSummary = null;
        try {
            $aiSummary = (new \App\Services\AI\AiEngine($orgId))->generateSummary([
                'type'       => 'org_overview',
                'org_id'     => $orgId,
                'health'     => $healthScore,
                'blockers'   => $openBlockers,
                'flags'      => $fairnessFlags,
            ]);
        } catch (\Throwable $e) {
            $aiSummary = null;
        }

        $latestAnnouncements = \App\Models\Announcement::forUser(auth()->user())
            ->with('author')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->take(3)
            ->get();

        return view('dashboard.org', compact(
            'totalUsers', 'loggedToday', 'openBlockers', 'escalatedBlockers',
            'fairnessFlags', 'biasReports', 'activeSprints', 'pendingFeedback',
            'pendingIncrements', 'activeTasks', 'healthScore', 'healthStatus',
            'alerts', 'notLoggedUsers', 'notLoggedCount', 'recentNotifications',
            'period', 'consistency',
            'activitySummary', 'chartLabels', 'commitData', 'prData',
            'recentActivities', 'myDirectReports', 'myReportsLogged',
            'modules', 'aiSummary', 'latestAnnouncements'
        ));
    }

    private function superAdminDashboard(): View
    {
        $totalOrgs     = Organization::count();
        $activeOrgs    = Organization::where('status', 'active')->count();
        $suspendedOrgs = Organization::where('status', 'suspended')->count();
        $totalUsers    = User::count();
        $activeUsers   = User::where('is_active', true)->count();
        $activeToday   = WorkLog::where('log_date', today())->distinct('user_id')->count('user_id');
        $totalDesignations = Designation::whereNull('organization_id')->count();
        $recentSignups = Organization::where('created_at', '>=', now()->subDays(7))->count();

        $freePlanOrgs       = Organization::where('plan', 'free')->count();
        $growthPlanOrgs     = Organization::where('plan', 'growth')->count();
        $enterprisePlanOrgs = Organization::where('plan', 'enterprise')->count();

        // Org growth — last 30 days
        $orgGrowthLabels = collect();
        $orgGrowthData   = collect();
        for ($i = 29; $i >= 0; $i--) {
            $orgGrowthLabels->push(now()->subDays($i)->format('M j'));
            $orgGrowthData->push(Organization::whereDate('created_at', now()->subDays($i))->count());
        }

        // User growth — last 8 weeks
        $userGrowthLabels = collect();
        $userGrowthData   = collect();
        for ($i = 7; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd   = now()->subWeeks($i)->endOfWeek();
            $userGrowthLabels->push('W' . now()->subWeeks($i)->weekOfYear);
            $userGrowthData->push(User::whereBetween('created_at', [$weekStart, $weekEnd])->count());
        }

        $recentNotifications = AgentNotification::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        return view('dashboard.superadmin', compact(
            'totalOrgs', 'activeOrgs', 'suspendedOrgs',
            'totalUsers', 'activeUsers', 'activeToday',
            'totalDesignations', 'recentSignups',
            'freePlanOrgs', 'growthPlanOrgs', 'enterprisePlanOrgs',
            'orgGrowthLabels', 'orgGrowthData',
            'userGrowthLabels', 'userGrowthData',
            'recentNotifications'
        ));
    }

    private function ownerDashboard($user): View
    {
        $orgId = $user->organization_id;

        $recentActivities = Activity::where('organization_id', $orgId)
            ->with('project')
            ->orderByDesc('occurred_at')
            ->limit(20)
            ->get();

        $githubStats = Cache::store('file')->get("github_stats_{$user->id}");

        $activitySummary = [
            'total_commits' => Activity::where('organization_id', $orgId)
                ->where('event_type', 'commit')
                ->where('occurred_at', '>=', now()->subDays(30))
                ->count(),
            'total_prs' => Activity::where('organization_id', $orgId)
                ->where('event_type', 'pull_request')
                ->where('occurred_at', '>=', now()->subDays(30))
                ->count(),
            'avg_score' => round(
                Activity::where('organization_id', $orgId)
                    ->where('occurred_at', '>=', now()->subDays(30))
                    ->whereNotNull('complexity_score')
                    ->avg('complexity_score') ?? 0,
                1
            ),
            'last_synced' => $githubStats['fetched_at'] ?? null,
        ];

        $last30Days = collect(range(29, 0))->map(fn ($d) => now()->subDays($d)->format('Y-m-d'));

        $activityData = Activity::where('organization_id', $orgId)
            ->where('occurred_at', '>=', now()->subDays(30))
            ->selectRaw("DATE(occurred_at) as date,
                SUM(CASE WHEN event_type = 'commit' THEN 1 ELSE 0 END) as commits,
                SUM(CASE WHEN event_type LIKE 'pr%' OR event_type = 'pull_request' THEN 1 ELSE 0 END) as prs")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chartLabels = $last30Days->map(fn ($d) => \Carbon\Carbon::parse($d)->format('M d'));
        $commitData  = $last30Days->map(fn ($date) => $activityData->firstWhere('date', $date)?->commits ?? 0);
        $prData      = $last30Days->map(fn ($date) => $activityData->firstWhere('date', $date)?->prs ?? 0);

        $moduleService = new ModuleService();
        $modules = [
            'github_sync'     => $moduleService->hasModule($orgId, 'github_sync'),
            'fairness_engine' => $moduleService->hasModule($orgId, 'fairness_engine'),
            'ai_intelligence' => $moduleService->hasModule($orgId, 'ai_intelligence'),
            'blockers'        => $moduleService->hasModule($orgId, 'blockers'),
            'command_center'  => $moduleService->hasModule($orgId, 'command_center'),
        ];

        $directReportIds = User::where('organization_id', $orgId)
            ->where('reporting_manager_id', $user->id)
            ->where('is_active', true)
            ->pluck('id');

        $myDirectReports = $directReportIds->count();
        $myReportsLogged = $myDirectReports > 0
            ? WorkLog::whereIn('user_id', $directReportIds)
                ->where('log_date', today())
                ->distinct('user_id')
                ->count()
            : 0;

        return view('dashboard', compact(
            'githubStats', 'recentActivities', 'activitySummary',
            'chartLabels', 'commitData', 'prData', 'modules',
            'myDirectReports', 'myReportsLogged'
        ));
    }

    private function managerDashboard(User $user): View
    {
        $orgId = $user->organization_id;

        // 1. Direct reports to this manager
        $directReportIds = User::where('organization_id', $orgId)
            ->where('reporting_manager_id', $user->id)
            ->where('is_active', true)
            ->pluck('id');

        // 2. Teams led by team leads who report to manager, or led directly by manager
        $teamIds = \App\Models\Team::where('organization_id', $orgId)
            ->where(function ($q) use ($directReportIds, $user) {
                $q->whereIn('team_lead_id', $directReportIds)
                  ->orWhere('team_lead_id', $user->id);
            })
            ->pluck('id');

        // 3. All active team members + direct reports (excluding the manager)
        $memberIds = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->where(function ($q) use ($directReportIds, $teamIds) {
                $q->whereIn('id', $directReportIds)
                  ->orWhereIn('team_id', $teamIds);
            })
            ->pluck('id')
            ->unique();

        $teamMembers = User::whereIn('id', $memberIds)
            ->with('department')
            ->orderBy('name')
            ->get();

        $pendingLeaves = \App\Models\LeaveApplication::whereIn('user_id', $memberIds)
            ->where('status', 'pending')
            ->with('user', 'leaveType')
            ->latest()
            ->get();

        $notLoggedToday = $teamMembers->filter(
            fn ($m) => !WorkLog::where('user_id', $m->id)->whereDate('log_date', today())->exists()
        );

        $activeBlockers = \App\Models\Blocker::where('organization_id', $orgId)
            ->whereIn('blocked_user_id', $memberIds)
            ->whereIn('status', ['open', 'escalated'])
            ->count();

        return view('dashboard.manager', compact(
            'user', 'teamMembers', 'pendingLeaves', 'notLoggedToday', 'activeBlockers'
        ));
    }

    private function teamLeadDashboard(User $user): View
    {
        $orgId = $user->organization_id;

        // Teams led by this team lead
        $teamIds = \App\Models\Team::where('organization_id', $orgId)
            ->where('team_lead_id', $user->id)
            ->pluck('id');

        // Direct reports + team members
        $memberIds = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->where('id', '!=', $user->id)
            ->where(function ($q) use ($user, $teamIds) {
                $q->where('reporting_manager_id', $user->id)
                  ->orWhereIn('team_id', $teamIds);
            })
            ->pluck('id')
            ->unique();

        $teamMembers     = User::whereIn('id', $memberIds)->orderBy('name')->get();
        $myDirectReports = $teamMembers->count();
        $myReportsLogged = $myDirectReports > 0
            ? WorkLog::whereIn('user_id', $memberIds)
                ->where('log_date', today())
                ->distinct('user_id')
                ->count()
            : 0;

        $teamBlockers = Blocker::where('organization_id', $orgId)
            ->whereIn('blocked_user_id', $memberIds)
            ->whereIn('status', ['open', 'escalated'])
            ->with(['blockedUser', 'project'])
            ->orderByDesc('created_at')
            ->get();

        $recentActivities = Activity::where('organization_id', $orgId)
            ->whereIn('user_id', $memberIds->concat([$user->id]))
            ->with('project')
            ->orderByDesc('occurred_at')
            ->limit(20)
            ->get();

        $githubStats = Cache::store('file')->get("github_stats_{$user->id}");

        $activitySummary = [
            'total_commits' => Activity::where('organization_id', $orgId)
                ->whereIn('user_id', $memberIds->concat([$user->id]))
                ->where('event_type', 'commit')
                ->where('occurred_at', '>=', now()->subDays(30))
                ->count(),
            'total_prs' => Activity::where('organization_id', $orgId)
                ->whereIn('user_id', $memberIds->concat([$user->id]))
                ->whereIn('event_type', ['pr_opened', 'pr_merged', 'pull_request'])
                ->where('occurred_at', '>=', now()->subDays(30))
                ->count(),
            'avg_score' => round(
                Activity::where('organization_id', $orgId)
                    ->whereIn('user_id', $memberIds->concat([$user->id]))
                    ->where('occurred_at', '>=', now()->subDays(30))
                    ->whereNotNull('complexity_score')
                    ->avg('complexity_score') ?? 0,
                1
            ),
            'last_synced' => $githubStats['fetched_at'] ?? null,
        ];

        $last30Days  = collect(range(29, 0))->map(fn ($d) => now()->subDays($d)->format('Y-m-d'));
        $chartLabels = $last30Days->map(fn ($d) => \Carbon\Carbon::parse($d)->format('M d'));
        $commitData  = $last30Days->map(fn ($date) => 0);
        $prData      = $last30Days->map(fn ($date) => 0);

        return view('teamlead.dashboard', compact(
            'user', 'teamMembers', 'recentActivities', 'activitySummary',
            'chartLabels', 'commitData', 'prData',
            'myDirectReports', 'myReportsLogged', 'teamBlockers'
        ));
    }

    private function employeeDashboard($user): View
    {
        $commits = Activity::where('user_id', $user->id)
            ->where('event_type', 'commit')
            ->where('occurred_at', '>=', now()->subDays(30))
            ->count();

        $prs = Activity::where('user_id', $user->id)
            ->whereIn('event_type', ['pr_opened', 'pr_merged', 'pull_request'])
            ->where('occurred_at', '>=', now()->subDays(30))
            ->count();

        $avgScore = round(
            Activity::where('user_id', $user->id)
                ->where('occurred_at', '>=', now()->subDays(30))
                ->whereNotNull('complexity_score')
                ->avg('complexity_score') ?? 0,
            1
        );

        $recentActivities = Activity::where('user_id', $user->id)
            ->with('project')
            ->orderByDesc('occurred_at')
            ->limit(10)
            ->get();

        $myBlockers = Blocker::where('blocked_user_id', $user->id)
            ->where('status', 'open')
            ->with('project')
            ->orderByDesc('created_at')
            ->get();

        $myProjects = Project::where('organization_id', $user->organization_id)
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $last30Days = collect(range(29, 0))->map(fn ($d) => now()->subDays($d)->format('Y-m-d'));

        $personalActivity = Activity::where('user_id', $user->id)
            ->where('occurred_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(occurred_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->get();

        $chartLabels    = $last30Days->map(fn ($d) => \Carbon\Carbon::parse($d)->format('M d'));
        $activityCounts = $last30Days->map(fn ($date) => $personalActivity->firstWhere('date', $date)?->count ?? 0);

        return view('employee.dashboard', compact(
            'commits', 'prs', 'avgScore',
            'recentActivities', 'myBlockers', 'myProjects',
            'chartLabels', 'activityCounts'
        ));
    }

    public function syncGitHub(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (!$user->github_username) {
            return back()->withErrors(['github' => 'No GitHub username set on your profile.']);
        }

        $projects = $user->organization?->projects()
            ->whereNotNull('github_owner')
            ->whereNotNull('github_repo')
            ->get();

        if (!$projects || $projects->isEmpty()) {
            return back()->with('warning', 'No projects with GitHub repositories found in your organization.');
        }

        $totalCommits = 0;
        $totalPrs     = 0;

        foreach ($projects as $project) {
            $commits = $this->github->getCommits(
                $user->github_username,
                $project->github_owner,
                $project->github_repo,
                30
            );

            $prs = $this->github->getPullRequests(
                $user->github_username,
                $project->github_owner,
                $project->github_repo
            );

            foreach ($commits as $commit) {
                Activity::updateOrCreate(
                    [
                        'external_id' => $commit['sha'],
                        'event_type'  => 'commit',
                        'project_id'  => $project->id,
                    ],
                    [
                        'user_id'          => $user->id,
                        'organization_id'  => $user->organization_id,
                        'source'           => 'github',
                        'complexity_score' => $this->github->analyzeComplexity([$commit]),
                        'metadata'         => [
                            'sha'     => $commit['sha'],
                            'message' => $commit['commit']['message'] ?? '',
                            'url'     => $commit['html_url'] ?? '',
                            'author'  => $commit['commit']['author']['name'] ?? '',
                        ],
                        'occurred_at' => $commit['commit']['author']['date']
                            ?? $commit['commit']['committer']['date']
                            ?? now(),
                    ]
                );
            }

            foreach ($prs as $pr) {
                Activity::updateOrCreate(
                    [
                        'external_id' => (string) $pr['number'],
                        'event_type'  => 'pull_request',
                        'project_id'  => $project->id,
                    ],
                    [
                        'user_id'         => $user->id,
                        'organization_id' => $user->organization_id,
                        'source'          => 'github',
                        'quality_score'   => match (true) {
                            !empty($pr['merged_at'])    => 1.0,
                            $pr['state'] === 'open'     => 0.5,
                            default                     => 0.2,
                        },
                        'metadata' => [
                            'number' => $pr['number'],
                            'title'  => $pr['title'] ?? '',
                            'state'  => $pr['state'] ?? '',
                            'url'    => $pr['html_url'] ?? '',
                        ],
                        'occurred_at' => $pr['created_at'] ?? now(),
                    ]
                );
            }

            $totalCommits += count($commits);
            $totalPrs     += count($prs);

            Cache::store('file')->forget(
                "github_{$user->github_username}_{$project->github_owner}_{$project->github_repo}_30"
            );
        }

        Cache::store('file')->put("github_stats_{$user->id}", [
            'commits'    => $totalCommits,
            'prs'        => $totalPrs,
            'fetched_at' => now()->toDateTimeString(),
        ], 3600);

        return back()->with('success', "Synced {$totalCommits} commits and {$totalPrs} pull requests.");
    }
}

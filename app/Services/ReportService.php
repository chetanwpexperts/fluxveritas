<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Blocker;
use App\Models\FairnessFlag;
use App\Models\FeedbackBiasReport;
use App\Models\IncrementScore;
use App\Models\ManagerAccountabilityScore;
use App\Models\PerformanceFeedback;
use App\Models\PeerFeedback;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkLog;
use Carbon\Carbon;

class ReportService
{
    // =============================================
    // CEO DAILY DIGEST
    // =============================================

    public function getCeoDailyDigest(int $orgId): array
    {
        $today    = now()->toDateString();
        $orgUsers = User::where('organization_id', $orgId)->where('is_active', true)->get();
        $total    = $orgUsers->count();

        $loggedIds       = WorkLog::where('organization_id', $orgId)->where('log_date', $today)->pluck('user_id')->unique();
        $loggedCount     = $loggedIds->count();
        $notLoggedCount  = max(0, $total - $loggedCount);
        $notLoggedNames  = $orgUsers->whereNotIn('id', $loggedIds->toArray())->take(5)->pluck('name')->toArray();

        $tasksCompleted  = Task::whereHas('project', fn($q) => $q->where('organization_id', $orgId))->where('status', 'done')->whereDate('completed_at', $today)->count();
        $openBlockers    = Blocker::where('organization_id', $orgId)->where('status', 'open')->count();
        $criticalBlockers= Blocker::where('organization_id', $orgId)->where('status', 'escalated')->count();
        $fairnessFlags   = FairnessFlag::where('organization_id', $orgId)->where('status', 'pending')->count();
        $biasReports     = FeedbackBiasReport::where('organization_id', $orgId)->where('bias_detected', true)->where('ceo_reviewed', false)->count();
        $activeSprints   = Sprint::where('organization_id', $orgId)->where('status', 'active')->count();

        $overloaded      = Task::whereHas('project', fn($q) => $q->where('organization_id', $orgId))->whereNotIn('status', ['done', 'cancelled'])
            ->selectRaw('assigned_to, COUNT(*) as task_count')->groupBy('assigned_to')->having('task_count', '>', 8)->get();
        $overloadedNames = User::whereIn('id', $overloaded->pluck('assigned_to'))->pluck('name')->toArray();

        $consistencyScore = $total > 0 ? round(($loggedCount / $total) * 100) : 0;
        $blockersScore    = max(0, 100 - ($openBlockers * 10));
        $healthScore      = round(($consistencyScore * 0.6) + ($blockersScore * 0.4));
        $status           = $healthScore >= 80 ? 'healthy' : ($healthScore >= 60 ? 'attention' : 'critical');

        return [
            'date'              => now()->format('l, F j, Y'),
            'total_employees'   => $total,
            'logged_today'      => $loggedCount,
            'not_logged_count'  => $notLoggedCount,
            'not_logged_names'  => $notLoggedNames,
            'tasks_completed'   => $tasksCompleted,
            'open_blockers'     => $openBlockers,
            'critical_blockers' => $criticalBlockers,
            'fairness_flags'    => $fairnessFlags,
            'bias_reports'      => $biasReports,
            'active_sprints'    => $activeSprints,
            'overloaded_count'  => $overloaded->count(),
            'overloaded_names'  => $overloadedNames,
            'health_score'      => $healthScore,
            'status'            => $status,
            'alerts'            => $this->getCeoAlerts($orgId, $openBlockers, $criticalBlockers, $fairnessFlags, $biasReports, $overloadedNames, $notLoggedCount),
        ];
    }

    private function getCeoAlerts(int $orgId, int $openBlockers, int $criticalBlockers, int $fairnessFlags, int $biasReports, array $overloadedNames, int $notLoggedCount): array
    {
        $alerts = [];

        if ($criticalBlockers > 0) {
            $alerts[] = ['type' => 'critical', 'icon' => '🚨', 'message' => "{$criticalBlockers} escalated blocker(s) need immediate attention", 'url' => '/dependencies'];
        }
        if ($biasReports > 0) {
            $alerts[] = ['type' => 'warning', 'icon' => '⚠️', 'message' => "{$biasReports} unreviewed bias report(s) detected", 'url' => '/feedback/bias-reports'];
        }
        if ($fairnessFlags > 0) {
            $alerts[] = ['type' => 'warning', 'icon' => '⚖️', 'message' => "{$fairnessFlags} fairness flag(s) pending review", 'url' => '/fairness'];
        }
        if (!empty($overloadedNames)) {
            $names    = implode(', ', array_slice($overloadedNames, 0, 3));
            $alerts[] = ['type' => 'warning', 'icon' => '😰', 'message' => "Overloaded employees: {$names}", 'url' => '/team'];
        }
        if ($notLoggedCount > 3) {
            $alerts[] = ['type' => 'info', 'icon' => '📝', 'message' => "{$notLoggedCount} employees haven't logged work today", 'url' => '/team'];
        }

        return $alerts;
    }

    // =============================================
    // MANAGER DAILY REPORT
    // =============================================

    public function getManagerDailyReport(User $manager): array
    {
        $orgId         = $manager->organization_id;
        $today         = now()->toDateString();
        $directReports = User::where('organization_id', $orgId)->where('reporting_manager_id', $manager->id)->where('is_active', true)->get();
        $totalReports  = $directReports->count();

        if ($totalReports === 0) return [];

        $loggedToday    = WorkLog::where('organization_id', $orgId)->where('log_date', $today)->whereIn('user_id', $directReports->pluck('id'))->pluck('user_id')->unique();
        $notLoggedNames = $directReports->whereNotIn('id', $loggedToday->toArray())->pluck('name')->toArray();
        $teamTasksDone  = Task::whereIn('assigned_to', $directReports->pluck('id'))->where('status', 'done')->whereDate('completed_at', $today)->count();
        $teamBlockers   = Blocker::where('organization_id', $orgId)->whereIn('blocked_user_id', $directReports->pluck('id'))->where('status', 'open')->count();

        $overloaded      = Task::whereIn('assigned_to', $directReports->pluck('id'))->whereNotIn('status', ['done', 'cancelled'])->selectRaw('assigned_to, COUNT(*) as cnt')->groupBy('assigned_to')->having('cnt', '>', 6)->get();
        $overloadedNames = $directReports->whereIn('id', $overloaded->pluck('assigned_to'))->pluck('name')->toArray();

        $period          = $this->getCurrentPeriod();
        $pendingFeedback = $directReports->filter(fn($r) => !PerformanceFeedback::where('employee_id', $r->id)->where('manager_id', $manager->id)->where('review_period', $period)->where('review_year', now()->year)->where('status', '!=', 'draft')->exists())->pluck('name')->toArray();
        $accountability  = ManagerAccountabilityScore::where('manager_id', $manager->id)->where('review_period', $period)->where('review_year', now()->year)->value('accountability_score') ?? 100;

        return [
            'manager_name'        => $manager->name,
            'date'                => now()->format('l, F j, Y'),
            'total_reports'       => $totalReports,
            'logged_today'        => $loggedToday->count(),
            'not_logged_count'    => $totalReports - $loggedToday->count(),
            'not_logged_names'    => $notLoggedNames,
            'tasks_done_today'    => $teamTasksDone,
            'open_blockers'       => $teamBlockers,
            'overloaded_names'    => $overloadedNames,
            'pending_feedback'    => $pendingFeedback,
            'accountability_score'=> round($accountability),
            'period'              => $period,
        ];
    }

    // =============================================
    // EMPLOYEE QUARTERLY REPORT
    // =============================================

    public function getEmployeeQuarterlyReport(User $employee, string $period, int $year): array
    {
        $dateRange = $this->getPeriodDates($period, $year);
        [$start, $end] = $dateRange;

        $logs        = WorkLog::where('user_id', $employee->id)->whereBetween('log_date', [$start, $end])->get();
        $totalHours  = round($logs->sum('duration_minutes') / 60, 1);
        $loggedDays  = $logs->groupBy('log_date')->count();
        $avgQuality  = round($logs->avg('output_value') ?? 0, 1);
        $workingDays = $this->getWorkingDays($start, $end);
        $consistency = $workingDays > 0 ? round(($loggedDays / $workingDays) * 100) : 0;

        $tasksCompleted  = Task::where('assigned_to', $employee->id)->where('status', 'done')->whereBetween('completed_at', [$start, $end])->count();
        $totalTasks      = Task::where('assigned_to', $employee->id)->whereBetween('created_at', [$start, $end])->count();
        $githubCommits   = Activity::where('user_id', $employee->id)->where('event_type', 'commit')->whereBetween('occurred_at', [$start, $end])->count();
        $blockersResolved= Blocker::where('blocked_user_id', $employee->id)->where('status', 'resolved')->whereBetween('resolved_at', [$start, $end])->count();

        $aiScore     = IncrementScore::where('user_id', $employee->id)->orderByDesc('score_month')->value('final_score');
        $feedback    = PerformanceFeedback::where('employee_id', $employee->id)->where('review_period', $period)->where('review_year', $year)->where('status', '!=', 'draft')->with('manager')->first();

        $peerFeedbacks = PeerFeedback::where('employee_id', $employee->id)->where('review_period', $period)->where('review_year', $year)->where('is_submitted', true)->get();
        $peerAvg       = $peerFeedbacks->count() > 0 ? round($peerFeedbacks->avg(fn($p) => $p->average_score), 1) : null;

        $benchmark = match($employee->seniority_level ?? 'mid') {
            'intern'    => 55, 'junior' => 65, 'mid'  => 72,
            'senior'    => 78, 'lead'   => 82, 'principal' => 85,
            default     => 70,
        };

        $isTech = DesignationService::requiresGithub($employee->designation);

        return [
            'employee'            => $employee,
            'period'              => $period,
            'year'                => $year,
            'designation_label'   => DesignationService::getDesignationLabel($employee->designation, $employee->seniority_level),
            'department'          => \App\Models\Department::find($employee->department_id)?->name,
            'seniority_benchmark' => $benchmark,
            'total_hours'         => $totalHours,
            'logged_days'         => $loggedDays,
            'working_days'        => $workingDays,
            'consistency'         => $consistency,
            'avg_quality'         => $avgQuality,
            'tasks_completed'     => $tasksCompleted,
            'total_tasks'         => $totalTasks,
            'github_commits'      => $isTech ? $githubCommits : null,
            'blockers_resolved'   => $blockersResolved,
            'ai_score'            => $aiScore ? round($aiScore, 1) : null,
            'feedback'            => $feedback,
            'peer_avg'            => $peerAvg,
            'peer_count'          => $peerFeedbacks->count(),
            'is_tech'             => $isTech,
        ];
    }

    private function getCurrentPeriod(): string
    {
        $m = now()->month;
        if ($m <= 3) return 'Q1';
        if ($m <= 6) return 'Q2';
        if ($m <= 9) return 'Q3';
        return 'Q4';
    }

    private function getPeriodDates(string $period, int $year): array
    {
        return match($period) {
            'Q1'    => ["{$year}-01-01", "{$year}-03-31"],
            'Q2'    => ["{$year}-04-01", "{$year}-06-30"],
            'Q3'    => ["{$year}-07-01", "{$year}-09-30"],
            'Q4'    => ["{$year}-10-01", "{$year}-12-31"],
            default => ["{$year}-01-01", "{$year}-12-31"],
        };
    }

    private function getWorkingDays(string $start, string $end): int
    {
        $current = Carbon::parse($start);
        $endDate = Carbon::parse($end);
        $days    = 0;
        while ($current->lte($endDate)) {
            if (!$current->isWeekend()) $days++;
            $current->addDay();
        }
        return $days;
    }
}

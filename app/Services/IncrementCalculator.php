<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Blocker;
use App\Models\EmployeeStatus;
use App\Models\IncrementCriteria;
use App\Models\IncrementPolicy;
use App\Models\IncrementReview;
use App\Models\IncrementScore;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class IncrementCalculator
{
    private IncrementPolicy $policy;
    private array $criteria;

    public function __construct(IncrementPolicy $policy)
    {
        $this->policy   = $policy;
        $this->criteria = IncrementCriteria::where('policy_id', $policy->id)
            ->where('is_active', true)
            ->get()
            ->toArray();
    }

    public function calculateMonthlyScore(User $user, Carbon $month): IncrementScore
    {
        $start = $month->copy()->startOfMonth();
        $end   = $month->copy()->endOfMonth();

        $leaveDays   = $this->getLeaveDays($user, $start, $end);
        $workingDays = 0;
        $current     = $start->copy();
        while ($current->lte($end)) {
            if ($current->isWeekday()) $workingDays++;
            $current->addDay();
        }
        $workingDays = max(1, $workingDays);
        $effectiveWork   = max(1, $workingDays - $leaveDays);
        $attendancePct   = min(1, $effectiveWork / max(1, $workingDays));

        $breakdown       = [];
        $totalWeighted   = 0;
        $totalWeight     = 0;

        foreach ($this->criteria as $c) {
            $score = $this->calculateCriteriaScore($user, $c, $start, $end, $attendancePct);

            // -1 signals criteria is not applicable — exclude from weighted average
            if ($score === -1.0) {
                $breakdown[$c['criteria_name']] = [
                    'label'    => $c['criteria_label'],
                    'raw_score'=> null,
                    'weight'   => $c['weight_percent'],
                    'weighted' => 0,
                    'type'     => $c['criteria_type'],
                    'skipped'  => true,
                    'reason'   => 'Not applicable for this role/department',
                ];
                continue;
            }

            $weighted      = $score * ($c['weight_percent'] / 100);
            $totalWeighted += $weighted;
            $totalWeight   += $c['weight_percent'];

            $breakdown[$c['criteria_name']] = [
                'label'    => $c['criteria_label'],
                'raw_score'=> round($score, 2),
                'weight'   => $c['weight_percent'],
                'weighted' => round($weighted, 2),
                'type'     => $c['criteria_type'],
                'skipped'  => false,
            ];
        }

        // Recalculate based on applicable criteria only
        $rawScore = $totalWeight > 0 ? ($totalWeighted / $totalWeight) * 100 : 0;
        $rawScore = $this->applySeniorityAdjustment($rawScore, $user);

        $antiGamingPenalty = 0;
        if ($this->policy->anti_gaming_enabled) {
            $antiGamingPenalty = $this->detectGaming($user, $month);
        }

        $adjustedScore    = $rawScore - $antiGamingPenalty;
        $isAdjusted       = false;
        $adjustmentReason = null;

        $inDispute = Blocker::where('blocked_user_id', $user->id)
            ->where('ownership_disputed', true)
            ->whereBetween('created_at', [$start, $end])
            ->exists();

        if ($inDispute) {
            $adjustedScore    = min(100, $adjustedScore * 1.1);
            $isAdjusted       = true;
            $adjustmentReason = 'Score adjusted: User was in ownership dispute (victim protection)';
        }

        if ($leaveDays > 0) {
            $isAdjusted       = true;
            $adjustmentReason = ($adjustmentReason ? $adjustmentReason . '. ' : '')
                . "Score normalized for {$leaveDays} leave days";
        }

        $finalScore = max(0, min(100, $adjustedScore));

        return IncrementScore::updateOrCreate(
            ['user_id' => $user->id, 'policy_id' => $this->policy->id, 'score_month' => $start->format('Y-m-01')],
            [
                'organization_id'     => $user->organization_id,
                'raw_score'           => round($rawScore, 4),
                'weighted_score'      => round($totalWeighted, 4),
                'criteria_breakdown'  => $breakdown,
                'anti_gaming_penalty' => $antiGamingPenalty,
                'final_score'         => round($finalScore, 4),
                'is_adjusted'         => $isAdjusted,
                'adjustment_reason'   => $adjustmentReason,
                'calculated_at'       => now(),
            ]
        );
    }

    private function calculateCriteriaScore(User $user, array $c, Carbon $start, Carbon $end, float $attendancePct): float
    {
        $source      = $c['data_source'] ?? '';
        $designation = $user->designation ?? '';
        $isTechRole  = $this->isTechDesignation($designation);

        // GitHub criteria — only for tech roles with connected GitHub
        if (in_array($source, ['github_commits', 'github_prs'])) {
            if (!$isTechRole) return -1.0;

            $hasGithub = Activity::where('organization_id', $user->organization_id)
                ->whereIn('event_type', ['commit', 'pr_opened', 'pr_merged'])
                ->whereBetween('occurred_at', [$start, $end])
                ->exists();

            if (!$hasGithub) return -1.0;
        }

        return match($source) {
            'github_commits'     => $this->scoreGithubCommits($user, $start, $end),
            'github_prs'         => $this->scoreGithubPRs($user, $start, $end),
            'work_logs'          => $this->scoreWorkLogs($user, $start, $end, $attendancePct),
            'tasks_completed'    => $this->scoreTasksCompleted($user, $start, $end),
            'task_complexity'    => $this->scoreTaskComplexity($user, $start, $end),
            'blocker_resolution' => $this->scoreBlockerResolution($user, $start, $end),
            'attendance'         => $attendancePct * 100,
            default              => 0,
        };
    }

    private function isTechDesignation(string $designation): bool
    {
        $techKeywords = [
            'developer', 'devops', 'engineer', 'architect',
            'tech-lead', 'techlead', 'automation', 'dba', 'database',
        ];

        $d = strtolower(str_replace(['-', '_', ' '], '', $designation));

        foreach ($techKeywords as $keyword) {
            $k = str_replace('-', '', $keyword);
            if (str_contains($d, $k)) return true;
        }

        return false;
    }

    private function getSeniorityBenchmark(User $user): array
    {
        return match($user->seniority_level ?? 'mid') {
            'intern'    => ['min' => 40, 'target' => 55, 'max' => 70],
            'junior'    => ['min' => 50, 'target' => 65, 'max' => 80],
            'mid'       => ['min' => 60, 'target' => 72, 'max' => 85],
            'senior'    => ['min' => 65, 'target' => 78, 'max' => 90],
            'lead'      => ['min' => 70, 'target' => 82, 'max' => 92],
            'principal' => ['min' => 75, 'target' => 85, 'max' => 95],
            'manager'   => ['min' => 65, 'target' => 78, 'max' => 90],
            'director'  => ['min' => 70, 'target' => 82, 'max' => 92],
            'c_level'   => ['min' => 75, 'target' => 85, 'max' => 95],
            default     => ['min' => 55, 'target' => 70, 'max' => 85],
        };
    }

    private function applySeniorityAdjustment(float $score, User $user): float
    {
        $benchmark = $this->getSeniorityBenchmark($user);

        if ($score >= $benchmark['target']) {
            return $score;
        }

        // Apply a soft 10% penalty for the gap below target — not a harsh cut
        $gap = $benchmark['target'] - $score;
        return max($benchmark['min'], $score - ($gap * 0.1));
    }

    private function scoreGithubCommits(User $user, Carbon $start, Carbon $end): float
    {
        $commits = Activity::where('user_id', $user->id)
            ->where('event_type', 'commit')
            ->whereBetween('occurred_at', [$start, $end])
            ->count();

        if ($commits === 0) return 0;

        $orgAvg = Activity::where('organization_id', $user->organization_id)
            ->where('event_type', 'commit')
            ->whereBetween('occurred_at', [$start, $end])
            ->selectRaw('user_id, COUNT(*) as cnt')
            ->groupBy('user_id')
            ->avg('cnt') ?? 1;

        if ($orgAvg == 0) return 0;
        return min(100, ($commits / $orgAvg) * 100);
    }

    private function scoreGithubPRs(User $user, Carbon $start, Carbon $end): float
    {
        $prs = Activity::where('user_id', $user->id)
            ->whereIn('event_type', ['pr_opened', 'pr_merged'])
            ->whereBetween('occurred_at', [$start, $end])
            ->count();

        if ($prs === 0) return 0;

        $orgAvg = Activity::where('organization_id', $user->organization_id)
            ->whereIn('event_type', ['pr_opened', 'pr_merged'])
            ->whereBetween('occurred_at', [$start, $end])
            ->selectRaw('user_id, COUNT(*) as cnt')
            ->groupBy('user_id')
            ->avg('cnt') ?? 1;

        if ($orgAvg == 0) return 0;
        return min(100, ($prs / max(1, $orgAvg)) * 100);
    }

    private function scoreWorkLogs(User $user, Carbon $start, Carbon $end, float $attendancePct): float
    {
        $workingDays = 0;
        $current     = $start->copy();
        while ($current->lte($end)) {
            if ($current->isWeekday()) $workingDays++;
            $current->addDay();
        }
        $workingDays = max(1, $workingDays);
        $loggedDays      = WorkLog::where('user_id', $user->id)
            ->whereBetween('log_date', [$start, $end])
            ->select('log_date')->distinct()->count();

        $expectedDays    = max(1, $workingDays * $attendancePct);
        $consistency     = min(100, ($loggedDays / $expectedDays) * 100);

        $avgOutput       = WorkLog::where('user_id', $user->id)
            ->whereBetween('log_date', [$start, $end])
            ->avg('output_value') ?? 5;
        $quality         = ($avgOutput / 10) * 100;

        $prevMonthLogs   = WorkLog::where('user_id', $user->id)
            ->whereBetween('log_date', [$start->copy()->subMonth()->startOfMonth(), $start->copy()->subMonth()->endOfMonth()])
            ->count();
        $currentLogs     = WorkLog::where('user_id', $user->id)
            ->whereBetween('log_date', [$start, $end])
            ->count();

        $multiplier = ($prevMonthLogs > 0 && $currentLogs > ($prevMonthLogs * 3)) ? 0.7 : 1.0;

        return (($consistency * 0.6) + ($quality * 0.4)) * $multiplier;
    }

    private function scoreTasksCompleted(User $user, Carbon $start, Carbon $end): float
    {
        $completed = Task::where('assigned_to', $user->id)
            ->where('status', 'done')
            ->whereBetween('completed_at', [$start, $end])
            ->count();

        $orgAvg = Task::whereHas('project', fn($q) => $q->where('organization_id', $user->organization_id))
            ->where('status', 'done')
            ->whereBetween('completed_at', [$start, $end])
            ->selectRaw('assigned_to, COUNT(*) as cnt')
            ->groupBy('assigned_to')
            ->avg('cnt') ?? 1;

        if ($completed === 0) return 0;
        if ($orgAvg == 0) return 0;
        return min(100, ($completed / $orgAvg) * 100);
    }

    private function scoreTaskComplexity(User $user, Carbon $start, Carbon $end): float
    {
        $completedTasks = Task::where('assigned_to', $user->id)
            ->where('status', 'done')
            ->whereBetween('completed_at', [$start, $end])
            ->count();

        if ($completedTasks === 0) return 0;

        $avg = Task::where('assigned_to', $user->id)
            ->where('status', 'done')
            ->whereBetween('completed_at', [$start, $end])
            ->avg('difficulty') ?? 0;

        return ($avg / 10) * 100;
    }

    private function scoreBlockerResolution(User $user, Carbon $start, Carbon $end): float
    {
        $totalBlockers = Blocker::where('blocking_user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        if ($totalBlockers === 0) return 100;

        $resolved = Blocker::where('blocking_user_id', $user->id)
            ->where('status', 'resolved')
            ->whereBetween('resolved_at', [$start, $end])
            ->count();

        $quick = Blocker::where('blocking_user_id', $user->id)
            ->where('status', 'resolved')
            ->whereBetween('resolved_at', [$start, $end])
            ->where('days_to_resolve', '<=', 3)
            ->count();

        $disputes = Blocker::where('blocking_user_id', $user->id)
            ->where('ownership_disputed', true)
            ->whereBetween('dispute_raised_at', [$start, $end])
            ->count();

        $unresolved = Blocker::where('blocking_user_id', $user->id)
            ->where('status', '!=', 'resolved')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $resolutionRate = $resolved / max(1, $totalBlockers);
        $baseScore      = $resolutionRate * 60;
        $quickBonus     = min(30, $quick * 10);
        $disputePenalty    = $disputes * 20;
        $unresolvedPenalty = $unresolved * 10;

        return max(0, min(100, $baseScore + $quickBonus - $disputePenalty - $unresolvedPenalty));
    }

    private function detectGaming(User $user, Carbon $month): float
    {
        $penalty   = 0;
        $start     = $month->copy()->startOfMonth();
        $end       = $month->copy()->endOfMonth();

        $lastWeek  = WorkLog::where('user_id', $user->id)
            ->where('log_date', '>=', $end->copy()->subWeek())
            ->where('log_date', '<=', $end)
            ->count();

        $rest      = WorkLog::where('user_id', $user->id)
            ->where('log_date', '>=', $start)
            ->where('log_date', '<', $end->copy()->subWeek())
            ->count();

        $restDays  = max(1, $end->copy()->subWeek()->diffInWeekdays($start));

        if (($rest / $restDays) > 0 && ($lastWeek / 5) > (($rest / $restDays) * 3)) {
            $penalty += 15;
        }

        $commitDays   = Activity::where('user_id', $user->id)
            ->where('event_type', 'commit')
            ->whereBetween('occurred_at', [$start, $end])
            ->selectRaw('DAYOFWEEK(occurred_at) as day')
            ->groupBy('day')
            ->count();

        $totalCommits = Activity::where('user_id', $user->id)
            ->where('event_type', 'commit')
            ->whereBetween('occurred_at', [$start, $end])
            ->count();

        if ($totalCommits > 5 && $commitDays <= 2) {
            $penalty += 10;
        }

        return min(30, $penalty);
    }

    private function getLeaveDays(User $user, Carbon $start, Carbon $end): int
    {
        $leaves = EmployeeStatus::where('user_id', $user->id)
            ->where('status', 'on_leave')
            ->where('starts_at', '<=', $end)
            ->where(function ($q) use ($start) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $start);
            })->get();

        $total = 0;
        foreach ($leaves as $leave) {
            $ls    = max($start, Carbon::parse($leave->starts_at));
            $le    = min($end, $leave->ends_at ? Carbon::parse($leave->ends_at) : $end);
            $total += $ls->diffInWeekdays($le);
        }
        return $total;
    }

    public function generateAnnualReview(User $user, int $year): IncrementReview
    {
        $scores = IncrementScore::where('user_id', $user->id)
            ->where('policy_id', $this->policy->id)
            ->whereYear('score_month', $year)
            ->orderBy('score_month')
            ->get();

        if ($scores->count() < $this->policy->minimum_months_required) {
            throw new \Exception("Insufficient data: Need at least {$this->policy->minimum_months_required} months of data for {$user->name}");
        }

        $sorted   = $scores->sortBy('final_score');
        $excluded = $sorted->take(max(0, $scores->count() - 10));
        $included = $sorted->slice(max(0, $scores->count() - 10));
        $avgScore = $included->avg('final_score');

        $monthsIncluded = $included->map(fn($s) => [
            'month' => \Carbon\Carbon::parse($s->score_month)->format('M Y'),
            'score' => round($s->final_score, 1),
        ])->values()->toArray();

        $monthsExcluded = $excluded->map(fn($s) => [
            'month'  => \Carbon\Carbon::parse($s->score_month)->format('M Y'),
            'score'  => round($s->final_score, 1),
            'reason' => 'Lowest scoring month (2 months forgiven)',
        ])->values()->toArray();

        return IncrementReview::updateOrCreate(
            ['user_id' => $user->id, 'policy_id' => $this->policy->id, 'review_year' => $year],
            [
                'organization_id'      => $user->organization_id,
                'review_period'        => $this->policy->review_period,
                'months_included'      => $monthsIncluded,
                'months_excluded'      => $monthsExcluded,
                'avg_score'            => round($avgScore, 4),
                'recommended_increment'=> $this->calculateIncrement($avgScore),
                'status'               => 'pending',
            ]
        );
    }

    private function calculateIncrement(float $score): float
    {
        $max      = $this->policy->max_increment_percent;
        $minScore = $this->policy->minimum_score_for_increment;
        if ($score < $minScore) return 0;
        $normalized = ($score - $minScore) / (100 - $minScore);
        return min($max, round(pow($normalized, 1.5) * $max, 2));
    }

    public function calculateOrgMonthlyScores(int $orgId, Carbon $month): void
    {
        $users = User::where('organization_id', $orgId)->where('is_active', true)->get();
        foreach ($users as $user) {
            try {
                $this->calculateMonthlyScore($user, $month);
            } catch (\Exception $e) {
                Log::error("Score calc failed for {$user->name}: " . $e->getMessage());
            }
        }
    }
}

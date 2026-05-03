<?php

namespace App\Services;

use App\Models\FairnessFlag;
use App\Models\Activity;
use App\Models\Task;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FairnessEngine
{
    private float $confidenceThreshold = 0.95;
    private int $minimumPatternDays = 14;

    /**
     * Main entry: analyze team for fairness issues
     */
    public function analyzeTeam(int $teamId): array
    {
        $flags = [];

        $checks = [
            'assignment_bias' => $this->checkAssignmentBias($teamId),
            'credit_theft' => $this->checkCreditTheft($teamId),
            'overload_imbalance' => $this->checkOverloadImbalance($teamId),
            'visibility_bias' => $this->checkVisibilityBias($teamId),
            'promotion_bias' => $this->checkPromotionBias($teamId),
            'blocker_resolution_bias' => $this->checkBlockerResolutionBias($teamId),
        ];

        foreach ($checks as $type => $flag) {
            if ($flag !== null && $flag['confidence'] >= $this->confidenceThreshold) {
                $flag = $this->applyContextCheck($flag);
                if ($flag !== null) {
                    $flags[] = $flag;
                }
            }
        }

        return $flags;
    }

    /**
     * Check 1: Are tasks assigned fairly by difficulty?
     */
    private function checkAssignmentBias(int $teamId): ?array
    {
        $startDate = now()->subDays($this->minimumPatternDays);

        $tasks = Task::where('team_id', $teamId)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('assigned_to')
            ->get();

        if ($tasks->count() < 20) {
            return null;
        }

        $assignments = $tasks->groupBy('assigned_to');
        $difficultyDistribution = [];

        foreach ($assignments as $employeeId => $employeeTasks) {
            $difficultyDistribution[$employeeId] = [
                'easy' => $employeeTasks->where('difficulty', '<=', 3)->count(),
                'medium' => $employeeTasks->where('difficulty', '>', 3)->where('difficulty', '<=', 7)->count(),
                'hard' => $employeeTasks->where('difficulty', '>', 7)->count(),
                'total' => $employeeTasks->count(),
                'avg_difficulty' => $employeeTasks->avg('difficulty') ?? 0,
            ];
        }

        $avgDifficulties = array_column($difficultyDistribution, 'avg_difficulty');
        $mean = array_sum($avgDifficulties) / count($avgDifficulties);
        $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $avgDifficulties)) / count($avgDifficulties);
        $stdDev = sqrt($variance);

        $outliers = [];
        foreach ($difficultyDistribution as $employeeId => $data) {
            $zScore = $stdDev > 0 ? abs($data['avg_difficulty'] - $mean) / $stdDev : 0;
            if ($zScore > 2) {
                $outliers[] = [
                    'employee_id' => $employeeId,
                    'z_score' => round($zScore, 2),
                    'their_avg' => round($data['avg_difficulty'], 2),
                    'team_avg' => round($mean, 2),
                ];
            }
        }

        if (count($outliers) > 0) {
            $biasScore = min(0.99, 0.85 + (count($outliers) * 0.05));

            return [
                'type' => 'assignment_bias',
                'confidence' => $biasScore,
                'description' => 'Task difficulty distribution is statistically uneven across team members',
                'evidence' => [
                    'distribution' => $difficultyDistribution,
                    'outliers' => $outliers,
                    'team_average_difficulty' => round($mean, 2),
                    'standard_deviation' => round($stdDev, 2),
                ],
                'layer' => 1,
                'severity' => $biasScore > 0.95 ? 'high' : 'medium',
                'suggested_action' => 'Review task assignments for fairness. Consider rotating challenging tasks.',
            ];
        }

        return null;
    }

    /**
     * Check 2: Is someone taking credit for others work?
     */
    private function checkCreditTheft(int $teamId): ?array
    {
        return null;
    }

    /**
     * Check 3: Is workload distributed fairly?
     */
    private function checkOverloadImbalance(int $teamId): ?array
    {
        $startDate = now()->subDays($this->minimumPatternDays);

        $tasks = Task::where('team_id', $teamId)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('assigned_to')
            ->get();

        $workloads = $tasks->groupBy('assigned_to')->map(fn($t) => $t->count());

        if ($workloads->count() < 2) return null;

        $avgWorkload = $workloads->avg();
        $maxWorkload = $workloads->max();
        $minWorkload = $workloads->min();

        $overloaded = [];
        foreach ($workloads as $employeeId => $count) {
            if ($count > $avgWorkload * 2) {
                $overloaded[] = [
                    'employee_id' => $employeeId,
                    'their_tasks' => $count,
                    'team_avg' => round($avgWorkload, 1),
                    'ratio' => round($count / $avgWorkload, 2),
                ];
            }
        }

        if (count($overloaded) > 0) {
            return [
                'type' => 'overload_imbalance',
                'confidence' => 0.92,
                'description' => 'Team member(s) assigned significantly more tasks than average',
                'evidence' => [
                    'workloads' => $workloads->toArray(),
                    'overloaded' => $overloaded,
                    'team_average' => round($avgWorkload, 1),
                    'max_min_ratio' => $minWorkload > 0 ? round($maxWorkload / $minWorkload, 2) : null,
                ],
                'layer' => 1,
                'severity' => 'high',
                'suggested_action' => 'Redistribute tasks or hire additional resources. Risk of burnout.',
            ];
        }

        return null;
    }

    /**
     * Check 4: Are high-visibility tasks distributed fairly?
     */
    private function checkVisibilityBias(int $teamId): ?array
    {
        $startDate = now()->subDays($this->minimumPatternDays);

        $tasks = Task::where('team_id', $teamId)
            ->where('created_at', '>=', $startDate)
            ->where('visibility_score', '>=', 8)
            ->whereNotNull('assigned_to')
            ->get();

        if ($tasks->count() < 10) return null;

        $visibilityDistribution = $tasks->groupBy('assigned_to')->map(fn($t) => $t->count());

        $totalHighVisibility = $tasks->count();
        $avgPerPerson = $totalHighVisibility / $visibilityDistribution->count();

        $biased = [];
        foreach ($visibilityDistribution as $employeeId => $count) {
            if ($count > $avgPerPerson * 2.5) {
                $biased[] = [
                    'employee_id' => $employeeId,
                    'high_visibility_tasks' => $count,
                    'team_avg' => round($avgPerPerson, 1),
                    'percentage_of_total' => round(($count / $totalHighVisibility) * 100, 1),
                ];
            }
        }

        if (count($biased) > 0) {
            return [
                'type' => 'visibility_bias',
                'confidence' => 0.88,
                'description' => 'High-visibility tasks disproportionately assigned to specific team members',
                'evidence' => [
                    'distribution' => $visibilityDistribution->toArray(),
                    'biased_assignments' => $biased,
                    'total_high_visibility' => $totalHighVisibility,
                ],
                'layer' => 1,
                'severity' => 'medium',
                'suggested_action' => 'Rotate high-visibility opportunities to ensure balanced career growth.',
            ];
        }

        return null;
    }

    /**
     * Check 5: Promotion recommendations vs. actual output
     */
    private function checkPromotionBias(int $teamId): ?array
    {
        return null;
    }

    /**
     * Check 6: Blocker resolution speed by person
     */
    private function checkBlockerResolutionBias(int $teamId): ?array
    {
        $startDate = now()->subDays($this->minimumPatternDays);

        $blockers = Task::where('team_id', $teamId)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('blocked_by')
            ->get();

        if ($blockers->count() < 10) return null;

        $resolutionTimes = [];
        foreach ($blockers as $task) {
            $blockerOwner = $task->blocked_by;
            if ($task->started_at && $task->completed_at) {
                $hours = $task->started_at->diffInHours($task->completed_at);
                $resolutionTimes[$blockerOwner][] = $hours;
            }
        }

        $averages = [];
        foreach ($resolutionTimes as $owner => $times) {
            $averages[$owner] = array_sum($times) / count($times);
        }

        if (count($averages) < 2) return null;

        $overallAvg = array_sum($averages) / count($averages);
        $slowResolvers = [];

        foreach ($averages as $owner => $avg) {
            if ($avg > $overallAvg * 2) {
                $slowResolvers[] = [
                    'owner_id' => $owner,
                    'avg_resolution_hours' => round($avg, 1),
                    'team_avg' => round($overallAvg, 1),
                    'ratio' => round($avg / $overallAvg, 2),
                ];
            }
        }

        if (count($slowResolvers) > 0) {
            return [
                'type' => 'blocker_resolution_bias',
                'confidence' => 0.85,
                'description' => 'Some team members consistently slower at resolving blockers',
                'evidence' => [
                    'resolution_averages' => $averages,
                    'slow_resolvers' => $slowResolvers,
                ],
                'layer' => 1,
                'severity' => 'medium',
                'suggested_action' => 'Investigate if slow resolution is due to skill gap, overload, or other factors.',
            ];
        }

        return null;
    }

    /**
     * Layer 2: Context check
     */
    private function applyContextCheck(array $flag): ?array
    {
        $contextChecks = [
            'training_assignment' => $this->isTrainingAssignment($flag),
            'pto_period' => $this->isPtoPeriod($flag),
            'seniority_difference' => $this->isSeniorityDifference($flag),
            'project_requirement' => $this->isProjectRequirement($flag),
        ];

        $validContexts = array_filter($contextChecks);

        if (count($validContexts) > 0) {
            Log::info('Fairness flag invalidated by context', [
                'flag_type' => $flag['type'],
                'contexts' => array_keys($validContexts),
            ]);
            return null;
        }

        return $flag;
    }

    private function isTrainingAssignment(array $flag): bool
    {
        return false;
    }

    private function isPtoPeriod(array $flag): bool
    {
        return false;
    }

    private function isSeniorityDifference(array $flag): bool
    {
        return false;
    }

    private function isProjectRequirement(array $flag): bool
    {
        return false;
    }

    /**
     * Layer 3: Request employee response
     */
    public function requestEmployeeResponse(array $flag): void
    {
        // Implementation
    }

    /**
     * Store flag in database
     */
    public function storeFlag(array $flagData, int $organizationId): FairnessFlag
    {
        return FairnessFlag::create([
            'organization_id' => $organizationId,
            'flag_type' => $flagData['type'],
            'confidence_score' => $flagData['confidence'],
            'layer' => $flagData['layer'],
            'status' => 'pending',
            'evidence' => $flagData['evidence'],
            'severity' => $flagData['severity'],
            'suggested_action' => $flagData['suggested_action'] ?? null,
        ]);
    }
}

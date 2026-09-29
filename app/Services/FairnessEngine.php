<?php

namespace App\Services;

use App\Models\Blocker;
use App\Models\Department;
use App\Models\EmployeeStatus;
use App\Models\FairnessFlag;
use App\Models\Activity;
use App\Models\Task;
use App\Models\TaskDependency;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FairnessEngine
{
    private float $confidenceThreshold = 0.95;
    private int $minimumPatternDays = 14;

    /**
     * Org-level entry: analyze organization for fairness issues with context
     */
    public function analyzeOrganization(int $orgId): array
    {
        $context = $this->buildOrgContext($orgId);
        $flags = [];

        $checks = [
            'overload_imbalance'        => $this->checkOverloadImbalanceForOrg($orgId, $context),
            'unresolved_blockers'       => $this->checkUnresolvedBlockers($orgId, $context),
            'meeting_overload'          => $this->checkMeetingOverload($orgId, $context),
            'ownership_dispute_pattern' => $this->checkOwnershipDisputePattern($orgId),
            'cross_team_blockers'       => $this->checkCrossTeamBlockers($context),
            'worklog_imbalance'         => $this->checkWorkLogImbalance($orgId),
            'log_frequency'             => $this->checkLogFrequency($orgId, $context),
            'task_difficulty_bias'      => $this->checkTaskDifficultyBiasForOrg($orgId, $context),
            'task_visibility_bias'      => $this->checkTaskVisibilityBiasForOrg($orgId, $context),
        ];

        foreach ($checks as $type => $flag) {
            if ($flag !== null && $flag['confidence'] >= $this->confidenceThreshold) {
                $flags[] = $flag;
            }
        }

        return $flags;
    }

    /**
     * Build org-level context array to inform fairness checks
     */
    public function buildOrgContext(int $orgId): array
    {
        $onLeave = EmployeeStatus::where('organization_id', $orgId)
            ->where('status', 'on_leave')
            ->active()
            ->pluck('user_id')
            ->toArray();

        $resigned = EmployeeStatus::where('organization_id', $orgId)
            ->where('status', 'resigned')
            ->active()
            ->pluck('user_id')
            ->toArray();

        $openBlockers = Blocker::where('organization_id', $orgId)
            ->open()
            ->get(['id', 'blocked_user_id', 'blocking_user_id', 'blocker_type', 'created_at', 'ownership_disputed']);

        $waitingDeps = TaskDependency::where('organization_id', $orgId)
            ->waiting()
            ->get(['id', 'task_id', 'depends_on_user_id', 'dependency_type', 'created_at']);

        // Users stuck in an ownership dispute — treat as blocked, not inactive
        $usersInDisputedBlockers = $openBlockers
            ->where('ownership_disputed', true)
            ->pluck('blocked_user_id')
            ->unique()
            ->toArray();

        // Cross-team blocker pattern: departments repeatedly blocking this org
        $crossTeamBlockerDepts = Blocker::where('organization_id', $orgId)
            ->where('blocker_type', 'internal_other_team')
            ->where('status', 'open')
            ->whereNotNull('external_person_company')
            ->selectRaw('external_person_company, COUNT(*) as count')
            ->groupBy('external_person_company')
            ->orderByDesc('count')
            ->get()
            ->toArray();

        return [
            'users_on_leave'               => $onLeave,
            'users_resigned'               => $resigned,
            'open_blockers'                => $openBlockers,
            'waiting_dependencies'         => $waitingDeps,
            'users_in_disputed_blockers'   => $usersInDisputedBlockers,
            'cross_team_blocker_depts'     => $crossTeamBlockerDepts,
            'skip_users'                   => array_unique(array_merge($onLeave, $resigned)),
        ];
    }

    /**
     * Org-level: Are tasks distributed fairly by difficulty?
     */
    private function checkTaskDifficultyBiasForOrg(int $orgId, array $context): ?array
    {
        $skipUsers = $context['skip_users'] ?? [];
        $startDate = now()->subDays($this->minimumPatternDays);

        $tasks = Task::whereHas('project', fn($q) => $q->where('organization_id', $orgId))
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('assigned_to')
            ->whereNotNull('difficulty')
            ->when(!empty($skipUsers), fn($q) => $q->whereNotIn('assigned_to', $skipUsers))
            ->whereNull('archived_at')
            ->get(['assigned_to', 'difficulty']);

        if ($tasks->count() < 10) return null;

        $grouped = $tasks->groupBy('assigned_to');
        if ($grouped->count() < 2) return null;

        $distribution = [];
        foreach ($grouped as $userId => $userTasks) {
            $distribution[$userId] = [
                'easy'           => $userTasks->where('difficulty', '<=', 3)->count(),
                'medium'         => $userTasks->where('difficulty', '>', 3)->where('difficulty', '<=', 7)->count(),
                'hard'           => $userTasks->where('difficulty', '>', 7)->count(),
                'total'          => $userTasks->count(),
                'avg_difficulty' => round($userTasks->avg('difficulty') ?? 0, 2),
            ];
        }

        $avgs   = array_column($distribution, 'avg_difficulty');
        $mean   = array_sum($avgs) / count($avgs);
        $stdDev = sqrt(array_sum(array_map(fn($v) => pow($v - $mean, 2), $avgs)) / count($avgs));

        $outliers = [];
        foreach ($distribution as $userId => $data) {
            $z = $stdDev > 0 ? abs($data['avg_difficulty'] - $mean) / $stdDev : 0;
            if ($z > 2) {
                $outliers[] = [
                    'user_id'    => $userId,
                    'z_score'    => round($z, 2),
                    'their_avg'  => $data['avg_difficulty'],
                    'team_avg'   => round($mean, 2),
                    'direction'  => $data['avg_difficulty'] > $mean ? 'harder' : 'easier',
                ];
            }
        }

        if (empty($outliers)) return null;

        return [
            'type'             => 'task_difficulty_bias',
            'confidence'       => min(0.99, 0.85 + count($outliers) * 0.05),
            'description'      => 'Task difficulty is unevenly distributed — some members consistently receive harder work',
            'evidence'         => [
                'distribution'         => $distribution,
                'outliers'             => $outliers,
                'team_avg_difficulty'  => round($mean, 2),
                'std_deviation'        => round($stdDev, 2),
            ],
            'layer'            => 1,
            'severity'         => count($outliers) >= 2 ? 'high' : 'medium',
            'suggested_action' => 'Review task assignments. Rotate challenging tasks to ensure equitable workload.',
        ];
    }

    /**
     * Org-level: Are high-visibility tasks concentrated with a few people?
     */
    private function checkTaskVisibilityBiasForOrg(int $orgId, array $context): ?array
    {
        $skipUsers = $context['skip_users'] ?? [];
        $startDate = now()->subDays($this->minimumPatternDays);

        $tasks = Task::whereHas('project', fn($q) => $q->where('organization_id', $orgId))
            ->where('created_at', '>=', $startDate)
            ->where('visibility_score', '>=', 8)
            ->whereNotNull('assigned_to')
            ->when(!empty($skipUsers), fn($q) => $q->whereNotIn('assigned_to', $skipUsers))
            ->whereNull('archived_at')
            ->get(['assigned_to', 'visibility_score']);

        if ($tasks->count() < 6) return null;

        $byUser = $tasks->groupBy('assigned_to')->map(fn($t) => $t->count());
        if ($byUser->count() < 2) return null;

        $total   = $tasks->count();
        $avgPer  = $total / $byUser->count();
        $biased  = [];

        foreach ($byUser as $userId => $count) {
            if ($count > $avgPer * 2.5) {
                $biased[] = [
                    'user_id'             => $userId,
                    'high_vis_tasks'      => $count,
                    'team_avg'            => round($avgPer, 1),
                    'share_of_total'      => round(($count / $total) * 100, 1) . '%',
                ];
            }
        }

        if (empty($biased)) return null;

        return [
            'type'             => 'task_visibility_bias',
            'confidence'       => 0.88,
            'description'      => 'High-visibility tasks are disproportionately assigned to specific members',
            'evidence'         => [
                'distribution'         => $byUser->toArray(),
                'biased_assignments'   => $biased,
                'total_high_vis_tasks' => $total,
                'team_avg_per_person'  => round($avgPer, 1),
            ],
            'layer'            => 4,
            'severity'         => 'medium',
            'suggested_action' => 'Rotate high-visibility tasks so all team members get leadership opportunities.',
        ];
    }

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
     * Check 3: Is workload distributed fairly? (team-level)
     */
    private function checkOverloadImbalance(int $teamId): ?array
    {
        return $this->checkOverloadImbalanceForOrg(null, ['skip_users' => []], $teamId);
    }

    /**
     * Org-level workload check — skips users on leave or resigned
     */
    private function checkOverloadImbalanceForOrg(?int $orgId, array $context, ?int $teamId = null): ?array
    {
        $startDate = now()->subDays($this->minimumPatternDays);
        $skipUsers = $context['skip_users'] ?? [];

        $query = Task::where('created_at', '>=', $startDate)->whereNotNull('assigned_to');

        if ($orgId !== null) {
            $query->whereHas('project', function ($q) use ($orgId) {
                $q->where('organization_id', $orgId);
            });
        } elseif ($teamId !== null) {
            $query->where('team_id', $teamId);
        }

        if (!empty($skipUsers)) {
            $query->whereNotIn('assigned_to', $skipUsers);
            Log::info('FairnessEngine: skipping users in overload check', ['skipped' => $skipUsers]);
        }

        $tasks = $query->get();
        $workloads = $tasks->groupBy('assigned_to')->map(fn($t) => $t->count());

        if ($workloads->count() < 2) return null;

        $avgWorkload = $workloads->avg();
        $maxWorkload = $workloads->max();
        $minWorkload = $workloads->min();

        // Designation-aware overload: first check within same designation, fall back to department
        $overloaded = [];
        foreach ($workloads as $employeeId => $count) {
            if ($count <= $avgWorkload * 2) continue;

            // Check if overload is expected given designation peers
            if ($orgId !== null) {
                $employee = User::find($employeeId);
                if ($employee) {
                    $peerWorkloads = User::where('organization_id', $orgId)
                        ->where('designation', $employee->designation)
                        ->where('is_active', true)
                        ->where('id', '!=', $employeeId)
                        ->pluck('id');

                    $peerAvg = $tasks->whereIn('assigned_to', $peerWorkloads->toArray())->groupBy('assigned_to')->map(fn($t) => $t->count())->avg();

                    // If not enough designation peers, fall back to department peers
                    if (!$peerAvg && $employee->department_id) {
                        $deptPeers = User::where('organization_id', $orgId)
                            ->where('department_id', $employee->department_id)
                            ->where('is_active', true)
                            ->where('id', '!=', $employeeId)
                            ->pluck('id');
                        $peerAvg = $tasks->whereIn('assigned_to', $deptPeers->toArray())->groupBy('assigned_to')->map(fn($t) => $t->count())->avg();
                    }

                    // Only flag if > 40% above designation/department peers
                    if ($peerAvg && $count <= $peerAvg * 1.4) continue;
                }
            }

            $overloaded[] = [
                'employee_id' => $employeeId,
                'their_tasks' => $count,
                'team_avg'    => round($avgWorkload, 1),
                'ratio'       => round($count / $avgWorkload, 2),
            ];
        }

        if (count($overloaded) > 0) {
            return [
                'type'        => 'overload_imbalance',
                'confidence'  => 0.92,
                'description' => 'Team member(s) assigned significantly more tasks than average',
                'evidence'    => [
                    'workloads'     => $workloads->toArray(),
                    'overloaded'    => $overloaded,
                    'team_average'  => round($avgWorkload, 1),
                    'max_min_ratio' => $minWorkload > 0 ? round($maxWorkload / $minWorkload, 2) : null,
                    'skipped_users' => $skipUsers,
                ],
                'layer'            => 1,
                'severity'         => 'high',
                'suggested_action' => 'Redistribute tasks or hire additional resources. Risk of burnout.',
            ];
        }

        return null;
    }

    /**
     * Check: Are there blockers older than 3 days with no action?
     */
    private function checkUnresolvedBlockers(int $orgId, array $context): ?array
    {
        $skipUsers             = $context['skip_users'] ?? [];
        $disputedBlockerUsers  = $context['users_in_disputed_blockers'] ?? [];
        $thresholdDate         = now()->subDays(3);

        $staleBlockers = ($context['open_blockers'] ?? collect())
            ->filter(fn($b) => $b->created_at->lt($thresholdDate))
            ->filter(fn($b) => !in_array($b->blocking_user_id, $skipUsers))
            ->filter(function ($b) use ($disputedBlockerUsers) {
                if (in_array($b->blocked_user_id, $disputedBlockerUsers)) {
                    Log::info('FairnessEngine: skipping flag for user ' . $b->blocked_user_id . ': blocked by ownership dispute');
                    return false;
                }
                return true;
            });

        if ($staleBlockers->isEmpty()) return null;

        $byBlockingUser = $staleBlockers
            ->whereNotNull('blocking_user_id')
            ->groupBy('blocking_user_id');

        if ($byBlockingUser->isEmpty()) return null;

        $flagged = [];
        foreach ($byBlockingUser as $userId => $blockers) {
            $flagged[] = [
                'blocking_user_id' => $userId,
                'stale_count'      => $blockers->count(),
                'oldest_days'      => (int) $blockers->min('created_at')->diffInDays(now()),
                'blocker_ids'      => $blockers->pluck('id')->toArray(),
            ];
        }

        return [
            'type'        => 'unresolved_blockers',
            'confidence'  => 0.96,
            'description' => 'Users have open blockers older than 3 days with no resolution',
            'evidence'    => [
                'flagged_users'   => $flagged,
                'total_stale'     => $staleBlockers->count(),
                'skipped_users'   => $skipUsers,
            ],
            'layer'            => 2,
            'severity'         => 'high',
            'suggested_action' => 'Follow up with blocking users. Escalate if no response within 24 hours.',
        ];
    }

    /**
     * Check: Are any users overwhelmed with meeting-required blockers?
     */
    private function checkMeetingOverload(int $orgId, array $context): ?array
    {
        $skipUsers = $context['skip_users'] ?? [];

        $meetingBlockers = ($context['open_blockers'] ?? collect())
            ->where('blocker_type', 'meeting_required')
            ->filter(fn($b) => !in_array($b->blocked_user_id, $skipUsers));

        if ($meetingBlockers->isEmpty()) return null;

        $byUser = $meetingBlockers->groupBy('blocked_user_id');

        $overwhelmed = [];
        foreach ($byUser as $userId => $blockers) {
            if ($blockers->count() >= 3) {
                $overwhelmed[] = [
                    'user_id'       => $userId,
                    'meeting_count' => $blockers->count(),
                    'blocker_ids'   => $blockers->pluck('id')->toArray(),
                ];
            }
        }

        if (empty($overwhelmed)) return null;

        return [
            'type'        => 'meeting_overload',
            'confidence'  => 0.95,
            'description' => 'User(s) blocked on 3 or more unresolved meeting-required items',
            'evidence'    => [
                'overwhelmed_users' => $overwhelmed,
                'skipped_users'     => $skipUsers,
            ],
            'layer'            => 2,
            'severity'         => 'medium',
            'suggested_action' => 'Schedule or cancel pending meetings. Prevent cascading task delays.',
        ];
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
     * Check: departments in the same company that repeatedly block this team (3+ open blockers)
     * Returns an info-level insight rather than a hard flag.
     */
    private function checkCrossTeamBlockers(array $context): ?array
    {
        $depts = $context['cross_team_blocker_depts'] ?? [];

        $heavyBlockers = array_filter($depts, fn($d) => $d['count'] >= 3);

        if (empty($heavyBlockers)) return null;

        $insights = array_map(fn($d) => [
            'department'     => $d['external_person_company'],
            'blocker_count'  => $d['count'],
            'suggestion'     => $d['external_person_company'] . ' has blocked your team ' . $d['count'] . ' times. Consider a cross-team meeting to resolve the dependency.',
        ], array_values($heavyBlockers));

        return [
            'type'        => 'cross_team_dependency',
            'confidence'  => 0.75,
            'description' => 'One or more internal departments are repeatedly blocking this team',
            'evidence'    => [
                'departments' => $insights,
                'total_depts' => count($heavyBlockers),
            ],
            'layer'            => 1,
            'severity'         => 'medium',
            'suggested_action' => 'Schedule a cross-team sync or escalate to a shared manager to unblock dependencies.',
        ];
    }

    /**
     * Check: users who repeatedly dispute ownership (3+ times)
     */
    private function checkOwnershipDisputePattern(int $orgId): ?array
    {
        $disputes = Blocker::where('organization_id', $orgId)
            ->where('ownership_disputed', true)
            ->whereNotNull('blocking_user_id')
            ->get(['id', 'blocking_user_id', 'dispute_raised_at', 'days_to_resolve', 'title']);

        if ($disputes->isEmpty()) return null;

        $byUser = $disputes->groupBy('blocking_user_id');

        $flagged = [];
        foreach ($byUser as $userId => $userDisputes) {
            if ($userDisputes->count() >= 3) {
                $flagged[] = [
                    'user_id'       => $userId,
                    'dispute_count' => $userDisputes->count(),
                    'disputes'      => $userDisputes->map(fn($d) => [
                        'blocker_id'      => $d->id,
                        'title'           => $d->title,
                        'raised_at'       => $d->dispute_raised_at?->toDateString(),
                        'days_to_resolve' => $d->days_to_resolve,
                    ])->toArray(),
                ];
            }
        }

        if (empty($flagged)) return null;

        return [
            'type'        => 'ownership_dispute_pattern',
            'confidence'  => 0.85,
            'description' => 'Team member(s) have repeatedly disputed ownership of blockers (3 or more times)',
            'evidence'    => [
                'flagged_users'  => $flagged,
                'total_disputes' => $disputes->count(),
            ],
            'layer'            => 2,
            'severity'         => 'high',
            'suggested_action' => 'Review accountability patterns. Repeated ownership avoidance affects team trust and delivery.',
        ];
    }

    /**
     * Check work log hours imbalance within manual/hybrid departments
     */
    private function checkWorkLogImbalance(int $orgId): ?array
    {
        $depts = Department::where('organization_id', $orgId)
            ->whereIn('work_mode', ['manual', 'hybrid'])
            ->with('users')
            ->get();

        $flagged = [];

        foreach ($depts as $dept) {
            if ($dept->users->count() < 2) continue;

            $memberIds = $dept->users->pluck('id')->toArray();

            $hoursByUser = WorkLog::whereIn('user_id', $memberIds)
                ->where('log_date', '>=', now()->subDays(7))
                ->selectRaw('user_id, SUM(duration_minutes) as total_minutes')
                ->groupBy('user_id')
                ->get()
                ->pluck('total_minutes', 'user_id');

            if ($hoursByUser->count() < 2) continue;

            $avg = $hoursByUser->avg();
            if ($avg <= 0) continue;

            foreach ($hoursByUser as $userId => $minutes) {
                if ($minutes > $avg * 3) {
                    $flagged[] = [
                        'department_id'   => $dept->id,
                        'department_name' => $dept->name,
                        'user_id'         => $userId,
                        'their_hours'     => round($minutes / 60, 1),
                        'avg_hours'       => round($avg / 60, 1),
                        'ratio'           => round($minutes / $avg, 2),
                    ];
                }
            }
        }

        if (empty($flagged)) return null;

        return [
            'type'        => 'worklog_hours_imbalance',
            'confidence'  => 0.88,
            'description' => 'Work log hours severely imbalanced within department(s) — possible overload',
            'evidence'    => ['flagged' => $flagged],
            'layer'       => 1,
            'severity'    => 'high',
            'suggested_action' => 'Review workload distribution in affected departments.',
        ];
    }

    /**
     * Check for team members who haven't logged work in 3+ days (and aren't on leave)
     */
    private function checkLogFrequency(int $orgId, array $context): ?array
    {
        $skipUsers  = $context['skip_users'] ?? [];
        $threshold  = now()->subDays(3)->toDateString();

        $depts = Department::where('organization_id', $orgId)
            ->whereIn('work_mode', ['manual', 'hybrid'])
            ->with('users')
            ->get();

        $inactive = [];

        foreach ($depts as $dept) {
            foreach ($dept->users as $user) {
                if (in_array($user->id, $skipUsers)) continue;

                $lastLog = WorkLog::where('user_id', $user->id)
                    ->orderByDesc('log_date')
                    ->value('log_date');

                $daysSinceLog = $lastLog
                    ? now()->diffInDays(\Carbon\Carbon::parse($lastLog))
                    : null;

                if ($daysSinceLog === null || $daysSinceLog >= 3) {
                    $inactive[] = [
                        'user_id'          => $user->id,
                        'user_name'        => $user->name,
                        'department'       => $dept->name,
                        'days_since_log'   => $daysSinceLog ?? 'never',
                        'last_log_date'    => $lastLog,
                    ];
                }
            }
        }

        if (empty($inactive)) return null;

        return [
            'type'        => 'log_frequency_gap',
            'confidence'  => 0.82,
            'description' => 'Team member(s) in manual-tracked departments have not logged work for 3+ days',
            'evidence'    => ['inactive_members' => $inactive, 'count' => count($inactive)],
            'layer'       => 1,
            'severity'    => 'medium',
            'suggested_action' => 'Check in with members. They may need support or may have forgotten to log.',
        ];
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

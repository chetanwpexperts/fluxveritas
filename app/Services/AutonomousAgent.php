<?php

namespace App\Services;

use App\Models\AgentNotification;
use App\Models\AgentRule;
use App\Models\Activity;
use App\Models\Blocker;
use App\Models\Department;
use App\Models\EmployeeStatus;
use App\Models\FairnessFlag;
use App\Models\Organization;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkLog;
use App\Services\EmailService;
use App\Services\FairnessEngine;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutonomousAgent
{
    private array $results = [];

    public function run(): array
    {
        $this->results = [];
        Log::info('AutonomousAgent: Starting run');

        $organizations = Organization::where('status', 'active')->get();

        foreach ($organizations as $org) {
            $this->runForOrganization($org);
        }

        // System-level checks on every run
        $this->checkSystemHealth();
        $this->clearStaleCache();

        // Security scan once a day at midnight
        if (now()->hour === 0) {
            $this->runSecurityScan();
        }

        Log::info('AutonomousAgent: Completed', $this->results);
        return $this->results;
    }

    private function runForOrganization(Organization $org): void
    {
        $isWeekend = now()->isWeekend();

        if (!$isWeekend) {
            $this->checkWorkLogs($org);
            $this->checkBlockers($org);
            $this->checkGithubStale($org);
            $this->checkDepartmentActivity($org);
            $this->checkWorkloadImbalance($org);
            $this->checkSprintRisk($org);
            $this->checkInactivity($org);
            $this->checkGithubAnomaly($org);
        }

        $this->checkDisputeEscalations($org);
        $this->checkCriticalBlockers($org);

        if (now()->hour === 9 && !$isWeekend) {
            $this->sendMorningDigest($org);
        }

        if (now()->hour === 18 && !$isWeekend) {
            $this->sendEveningSummary($org);
        }

        if (now()->dayOfWeek === 1 && now()->hour === 8) {
            $this->sendWeeklyReport($org);
        }
    }

    private function checkWorkLogs(Organization $org): void
    {
        if (now()->hour < 14) return;

        $members = User::where('organization_id', $org->id)
            ->where('is_active', true)
            ->get();

        foreach ($members as $member) {
            if ($this->isOnLeave($member->id)) continue;

            $todayLogs = WorkLog::where('user_id', $member->id)
                ->where('log_date', today())
                ->count();

            if ($todayLogs === 0) {
                if ($this->canNotify($org->id, $member->id, 'work_log_reminder')) {
                    $this->createNotification([
                        'organization_id'       => $org->id,
                        'user_id'               => $member->id,
                        'notification_type'     => 'work_log_reminder',
                        'title'                 => '📝 Daily Work Log Missing',
                        'message'               => "Hey {$member->name}! You haven't logged your work activities for today. Take 2 minutes to log what you've been working on. This protects you from unfair performance reviews.",
                        'action_url'            => '/work-log/today',
                        'action_label'          => "Log Today's Work",
                        'priority'              => 'normal',
                        'metadata'              => [
                            'date'          => today()->toDateString(),
                            'hour_triggered' => now()->hour,
                        ],
                    ]);

                    $this->addResult('work_log_reminder', $member->name . ' nudged to log work');

                    (new EmailService())->sendWorkLogReminder($member);
                }

                if (now()->hour >= 17) {
                    $manager = $this->getManager($member, $org);

                    if ($manager && $this->canNotify($org->id, $manager->id, 'work_log_reminder_mgr_' . $member->id)) {
                        $this->createNotification([
                            'organization_id'       => $org->id,
                            'user_id'               => $manager->id,
                            'triggered_for_user_id' => $member->id,
                            'notification_type'     => 'work_log_reminder',
                            'title'                 => "⚠️ {$member->name} hasn't logged today",
                            'message'               => "{$member->name} has not logged any work activities for today as of " . now()->format('g:i A') . ". You may want to follow up.",
                            'action_url'            => '/team',
                            'action_label'          => 'View Team',
                            'priority'              => 'normal',
                        ]);
                    }
                }
            }

            $recentLogs = WorkLog::where('user_id', $member->id)
                ->where('log_date', '>=', now()->subDays(3))
                ->count();

            if ($recentLogs === 0) {
                $owner = $this->getOwner($org);
                if ($owner && $this->canNotify($org->id, $owner->id, 'no_log_3days_' . $member->id)) {
                    $this->createNotification([
                        'organization_id'       => $org->id,
                        'user_id'               => $owner->id,
                        'triggered_for_user_id' => $member->id,
                        'notification_type'     => 'team_health',
                        'title'                 => "🚨 {$member->name} inactive for 3+ days",
                        'message'               => "{$member->name} has not logged any work for 3 or more days. No GitHub activity either. Please check in with them directly.",
                        'action_url'            => '/ceo',
                        'action_label'          => 'View Command Center',
                        'priority'              => 'high',
                        'metadata'              => [
                            'days_inactive' => 3,
                            'member_id'     => $member->id,
                        ],
                    ]);
                }
            }
        }
    }

    private function checkBlockers(Organization $org): void
    {
        $oldBlockers = Blocker::where('organization_id', $org->id)
            ->where('status', 'open')
            ->where('created_at', '<=', now()->subDays(3))
            ->with(['blockedUser', 'blockingUser'])
            ->get();

        foreach ($oldBlockers as $blocker) {
            $daysOpen = $blocker->daysOpen();

            if ($blocker->blocking_user_id && $this->canNotify($org->id, $blocker->blocking_user_id, 'blocker_reminder_' . $blocker->id)) {
                $this->createNotification([
                    'organization_id'       => $org->id,
                    'user_id'               => $blocker->blocking_user_id,
                    'triggered_for_user_id' => $blocker->blocked_user_id,
                    'notification_type'     => 'blocker_escalation',
                    'title'                 => "⏰ Blocker needs your attention ({$daysOpen} days)",
                    'message'               => "{$blocker->blockedUser->name} is blocked waiting for you. Blocker: \"{$blocker->title}\" has been open for {$daysOpen} days. Please resolve or acknowledge this.",
                    'action_url'            => '/dependencies/blocker/' . $blocker->id,
                    'action_label'          => 'View Blocker',
                    'priority'              => $daysOpen >= 7 ? 'critical' : 'high',
                    'metadata'              => [
                        'blocker_id' => $blocker->id,
                        'days_open'  => $daysOpen,
                    ],
                ]);

                if ($daysOpen >= 7 && $blocker->blockingUser) {
                    (new EmailService())->sendBlockerEscalation($blocker, $blocker->blockingUser, $daysOpen);
                }
            }
        }
    }

    private function checkDisputeEscalations(Organization $org): void
    {
        // Alias for clarity — same logic as checkCriticalBlockers
        $this->checkCriticalBlockers($org);
    }

    private function checkCriticalBlockers(Organization $org): void
    {
        $disputes = Blocker::where('organization_id', $org->id)
            ->where('ownership_disputed', true)
            ->whereIn('status', ['open', 'escalated'])
            ->where('dispute_raised_at', '<=', now()->subDays(5))
            ->with(['blockedUser', 'blockingUser'])
            ->get();

        foreach ($disputes as $dispute) {
            $owner = $this->getOwner($org);

            if ($owner && $this->canNotify($org->id, $owner->id, 'dispute_critical_' . $dispute->id)) {
                $days         = (int) $dispute->dispute_raised_at->diffInDays(now());
                $blockingName = $dispute->blockingUser?->name ?? 'Unknown';
                $blockedName  = $dispute->blockedUser?->name ?? 'Unknown';

                $this->createNotification([
                    'organization_id'   => $org->id,
                    'user_id'           => $owner->id,
                    'notification_type' => 'dispute_unresolved',
                    'title'             => "🚨 Ownership dispute unresolved {$days} days",
                    'message'           => "URGENT: {$blockedName} has been blocked by {$blockingName} for {$days} days with an unresolved ownership dispute. This requires your immediate attention. Blocker: \"{$dispute->title}\"",
                    'action_url'        => '/dependencies/blocker/' . $dispute->id,
                    'action_label'      => 'Resolve Now',
                    'priority'          => 'critical',
                    'metadata'          => [
                        'blocker_id'    => $dispute->id,
                        'days_disputed' => $days,
                        'blocked_user'  => $blockedName,
                        'blocking_user' => $blockingName,
                    ],
                ]);
            }
        }
    }

    private function checkGithubStale(Organization $org): void
    {
        $lastActivity = Activity::where('organization_id', $org->id)->max('occurred_at');

        if (!$lastActivity) return;

        $daysSinceSync = (int) Carbon::parse($lastActivity)->diffInDays(now());

        if ($daysSinceSync >= 7) {
            $owner = $this->getOwner($org);

            if ($owner && $this->canNotify($org->id, $owner->id, 'github_stale')) {
                $this->createNotification([
                    'organization_id'   => $org->id,
                    'user_id'           => $owner->id,
                    'notification_type' => 'github_stale',
                    'title'             => "🔄 GitHub data is {$daysSinceSync} days old",
                    'message'           => "Your GitHub data hasn't been synced for {$daysSinceSync} days. The AI insights and fairness analysis may be outdated. Sync now to get fresh data.",
                    'action_url'        => '/dashboard',
                    'action_label'      => 'Sync GitHub',
                    'priority'          => 'normal',
                ]);
            }
        }
    }

    private function checkDepartmentActivity(Organization $org): void
    {
        $departments = Department::where('organization_id', $org->id)
            ->where('is_active', true)
            ->with('users')
            ->get();

        foreach ($departments as $dept) {
            if ($dept->users->isEmpty()) continue;

            if (in_array($dept->work_mode, ['manual', 'hybrid'])) {
                $todayLogs = WorkLog::where('organization_id', $org->id)
                    ->where('department_id', $dept->id)
                    ->where('log_date', today())
                    ->count();

                if ($todayLogs === 0 && now()->hour >= 15) {
                    $head = $dept->head_user_id
                        ? User::find($dept->head_user_id)
                        : $this->getOwner($org);

                    if ($head && $this->canNotify($org->id, $head->id, 'dept_no_logs_' . $dept->id)) {
                        $this->createNotification([
                            'organization_id'   => $org->id,
                            'user_id'           => $head->id,
                            'notification_type' => 'department_inactive',
                            'title'             => "📊 {$dept->name} has no logs today",
                            'message'           => "No one in the {$dept->name} department has logged any work activity today. Remind your team to update their work logs.",
                            'action_url'        => '/departments/' . $dept->id,
                            'action_label'      => 'View Department',
                            'priority'          => 'normal',
                        ]);
                    }
                }
            }
        }
    }

    private function sendMorningDigest(Organization $org): void
    {
        $owner = $this->getOwner($org);
        if (!$owner) return;

        if (!$this->canNotify($org->id, $owner->id, 'morning_digest')) return;

        $members = User::where('organization_id', $org->id)->where('is_active', true)->count();

        $onLeave = EmployeeStatus::where('organization_id', $org->id)
            ->where('status', 'on_leave')
            ->where('starts_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })->count();

        $openBlockers   = Blocker::where('organization_id', $org->id)->where('status', 'open')->count();
        $pendingFlags   = FairnessFlag::where('organization_id', $org->id)->where('status', 'pending')->count();
        $yesterdayLogs  = WorkLog::where('organization_id', $org->id)->where('log_date', today()->subDay())->count();

        $message = "Good morning! Here's your team summary:\n\n"
            . "👥 Active members: {$members}" . ($onLeave > 0 ? " ({$onLeave} on leave)" : "")
            . "\n📋 Open blockers: {$openBlockers}"
            . "\n⚖️ Pending fairness flags: {$pendingFlags}"
            . "\n📝 Yesterday's work logs: {$yesterdayLogs}"
            . "\n\nHave a productive day!";

        $this->createNotification([
            'organization_id'   => $org->id,
            'user_id'           => $owner->id,
            'notification_type' => 'daily_digest',
            'title'             => '🌅 Good Morning — Team Summary',
            'message'           => $message,
            'action_url'        => '/ceo',
            'action_label'      => 'View Command Center',
            'priority'          => 'low',
            'metadata'          => [
                'type' => 'morning_digest',
                'date' => today()->toDateString(),
            ],
        ]);
    }

    private function sendEveningSummary(Organization $org): void
    {
        $members = User::where('organization_id', $org->id)->where('is_active', true)->get();

        foreach ($members as $member) {
            if ($this->isOnLeave($member->id)) continue;

            if (!$this->canNotify($org->id, $member->id, 'evening_summary')) continue;

            $todayLogs    = WorkLog::where('user_id', $member->id)->where('log_date', today())->get();
            $totalMinutes = $todayLogs->sum('duration_minutes');
            $logCount     = $todayLogs->count();
            $avgOutput    = $todayLogs->avg('output_value') ?? 0;

            if ($logCount === 0) {
                $title    = "📝 Don't forget to log today!";
                $message  = "You haven't logged any work today. Before you finish, take 2 minutes to log your activities. It protects you and shows your real output.";
                $priority = 'high';
            } else {
                $hours    = round($totalMinutes / 60, 1);
                $title    = "✅ Today's work is logged!";
                $message  = "Great work today! You logged {$logCount} activities ({$hours} hours) with an average output score of " . round($avgOutput, 1) . "/10. Your work is on record.";
                $priority = 'low';
            }

            $this->createNotification([
                'organization_id'   => $org->id,
                'user_id'           => $member->id,
                'notification_type' => 'daily_digest',
                'title'             => $title,
                'message'           => $message,
                'action_url'        => '/work-log/today',
                'action_label'      => 'View Today',
                'priority'          => $priority,
                'metadata'          => [
                    'type'          => 'evening_summary',
                    'log_count'     => $logCount,
                    'total_minutes' => $totalMinutes,
                ],
            ]);
        }
    }

    private function sendWeeklyReport(Organization $org): void
    {
        $owner = $this->getOwner($org);
        if (!$owner) return;

        if (!$this->canNotify($org->id, $owner->id, 'weekly_report')) return;

        $weekStart = now()->startOfWeek();
        $weekEnd   = now()->endOfWeek();

        $totalLogs = WorkLog::where('organization_id', $org->id)
            ->whereBetween('log_date', [$weekStart, $weekEnd])
            ->count();

        $totalHours = WorkLog::where('organization_id', $org->id)
            ->whereBetween('log_date', [$weekStart, $weekEnd])
            ->sum('duration_minutes');

        $resolvedBlockers = Blocker::where('organization_id', $org->id)
            ->where('status', 'resolved')
            ->whereBetween('resolved_at', [$weekStart, $weekEnd])
            ->count();

        $newFlags = FairnessFlag::where('organization_id', $org->id)
            ->whereBetween('created_at', [$weekStart, $weekEnd])
            ->count();

        $hours = round($totalHours / 60, 1);

        $openBlockers = Blocker::where('organization_id', $org->id)->where('status', 'open')->count();
        $pendingFlags = FairnessFlag::where('organization_id', $org->id)->where('status', 'pending')->count();
        $members      = User::where('organization_id', $org->id)->where('is_active', true)->count();

        $this->createNotification([
            'organization_id'   => $org->id,
            'user_id'           => $owner->id,
            'notification_type' => 'weekly_report',
            'title'             => '📊 Weekly Team Report — ' . $weekStart->format('M j') . ' to ' . $weekEnd->format('M j'),
            'message'           => "Weekly summary:\n\n📝 Work logs: {$totalLogs} entries ({$hours} hours tracked)\n✅ Blockers resolved: {$resolvedBlockers}\n⚖️ New fairness flags: {$newFlags}\n\nView Command Center for full details.",
            'action_url'        => '/ceo',
            'action_label'      => 'View Full Report',
            'priority'          => 'normal',
            'metadata'          => [
                'type'        => 'weekly_report',
                'week_start'  => $weekStart->toDateString(),
                'total_logs'  => $totalLogs,
                'total_hours' => $hours,
            ],
        ]);

        $stats = [
            'total_members'  => $members,
            'total_logs'     => $totalLogs,
            'total_hours'    => $hours,
            'tasks_done'     => $resolvedBlockers,
            'open_blockers'  => $openBlockers,
            'pending_flags'  => $pendingFlags,
        ];
        (new EmailService())->sendWeeklyDigest($owner, $org, $stats);
    }

    private function canNotify(int $orgId, int $userId, string $type, int $cooldownHours = 24): bool
    {
        return !AgentNotification::where('organization_id', $orgId)
            ->where('user_id', $userId)
            ->where('notification_type', $type)
            ->where('created_at', '>=', now()->subHours($cooldownHours))
            ->exists();
    }

    private function createNotification(array $data): void
    {
        AgentNotification::create($data);
        $this->addResult($data['notification_type'], $data['title']);
    }

    private function isOnLeave(int $userId): bool
    {
        return EmployeeStatus::where('user_id', $userId)
            ->where('status', 'on_leave')
            ->where('starts_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })->exists();
    }

    private function getOwner(Organization $org): ?User
    {
        return User::where('organization_id', $org->id)->where('role', 'owner')->first();
    }

    private function getManager(User $member, Organization $org): ?User
    {
        if ($member->department_id) {
            $dept = Department::find($member->department_id);
            if ($dept?->head_user_id) {
                return User::find($dept->head_user_id);
            }
        }

        return User::where('organization_id', $org->id)
            ->whereIn('role', ['admin', 'owner'])
            ->where('id', '!=', $member->id)
            ->first();
    }

    private function addResult(string $type, string $message): void
    {
        $this->results[] = [
            'type'    => $type,
            'message' => $message,
            'time'    => now()->toTimeString(),
        ];
    }

    // ─── System Guardian ──────────────────────────────────────────────────────────

    public function checkSystemHealth(): array
    {
        $health = [];

        // 1. Database
        try {
            \DB::select('SELECT 1');
            $dbSize = \DB::select(
                "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                 FROM information_schema.tables WHERE table_schema = ?",
                [config('database.connections.mysql.database')]
            );
            $health['database'] = [
                'status'  => 'healthy',
                'size_mb' => $dbSize[0]->size_mb ?? 0,
                'message' => 'Database connected',
            ];
        } catch (\Exception $e) {
            $health['database'] = ['status' => 'critical', 'message' => 'Database error: ' . $e->getMessage()];
            $this->alertSuperAdmin('🚨 Database Health Critical', 'Database connection failed: ' . $e->getMessage(), 'critical');
        }

        // 2. Storage
        try {
            $storagePath = storage_path();
            $freeBytes   = disk_free_space($storagePath);
            $totalBytes  = disk_total_space($storagePath);
            $usedPct     = round((1 - $freeBytes / $totalBytes) * 100, 1);
            $status      = $usedPct > 90 ? 'critical' : ($usedPct > 75 ? 'warning' : 'healthy');

            $health['storage'] = [
                'status'       => $status,
                'used_percent' => $usedPct,
                'free_gb'      => round($freeBytes / 1073741824, 2),
                'message'      => "Disk {$usedPct}% used",
            ];

            if ($status === 'critical') {
                $this->alertSuperAdmin('🚨 Storage Critical', "Disk usage at {$usedPct}%. Free up space immediately.", 'critical');
            } elseif ($status === 'warning') {
                $this->alertSuperAdmin('⚠️ Storage Warning', "Disk usage at {$usedPct}%. Consider cleaning up.", 'high');
            }
        } catch (\Exception $e) {
            $health['storage'] = ['status' => 'unknown', 'message' => 'Cannot check storage'];
        }

        // 3. Cache
        try {
            \Cache::put('health_check', 'ok', 10);
            $cacheOk       = \Cache::get('health_check') === 'ok';
            $health['cache'] = [
                'status'  => $cacheOk ? 'healthy' : 'warning',
                'driver'  => config('cache.default'),
                'message' => $cacheOk ? 'Cache working' : 'Cache read/write mismatch',
            ];
        } catch (\Exception $e) {
            $health['cache'] = ['status' => 'warning', 'driver' => config('cache.default'), 'message' => 'Cache error: ' . $e->getMessage()];
        }

        // 4. Queue
        try {
            $failedJobs  = \DB::table('failed_jobs')->count();
            $pendingJobs = \DB::table('jobs')->count();
            $health['queue'] = [
                'status'       => $failedJobs > 10 ? 'warning' : 'healthy',
                'failed_jobs'  => $failedJobs,
                'pending_jobs' => $pendingJobs,
                'message'      => "{$failedJobs} failed, {$pendingJobs} pending",
            ];
            if ($failedJobs > 10) {
                $this->alertSuperAdmin('⚠️ Queue Warning', "{$failedJobs} failed jobs in queue. Review needed.", 'high');
            }
        } catch (\Exception $e) {
            $health['queue'] = ['status' => 'unknown', 'failed_jobs' => 0, 'pending_jobs' => 0, 'message' => 'Queue tables not found'];
        }

        // 5. Memory
        $memBytes = memory_get_usage(true);
        $memMb    = round($memBytes / 1048576, 2);
        $health['memory'] = [
            'status'     => 'healthy',
            'usage_mb'   => $memMb,
            'limit'      => ini_get('memory_limit'),
            'message'    => "{$memMb}MB used",
        ];

        // 6. Error logs
        try {
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $logSizeMb  = round(filesize($logFile) / 1048576, 2);
                $fp         = fopen($logFile, 'r');
                fseek($fp, -min(filesize($logFile), 8000), SEEK_END);
                $tail       = '';
                while (!feof($fp)) $tail .= fgets($fp);
                fclose($fp);
                $errorCount = substr_count($tail, '.ERROR:');

                if ($logSizeMb > 100) {
                    file_put_contents($logFile, '');
                    $this->alertSuperAdmin('🧹 Log File Auto-Cleared', "Laravel log was {$logSizeMb}MB. Auto-cleared by System Guardian.", 'normal');
                    $logSizeMb  = 0;
                    $errorCount = 0;
                }

                $health['logs'] = [
                    'status'        => $errorCount > 10 ? 'warning' : 'healthy',
                    'size_mb'       => $logSizeMb,
                    'recent_errors' => $errorCount,
                    'message'       => "{$errorCount} recent errors, {$logSizeMb}MB",
                ];
            } else {
                $health['logs'] = ['status' => 'healthy', 'size_mb' => 0, 'recent_errors' => 0, 'message' => 'No log file'];
            }
        } catch (\Exception $e) {
            $health['logs'] = ['status' => 'unknown', 'size_mb' => 0, 'recent_errors' => 0, 'message' => 'Cannot check logs'];
        }

        // 7. Data integrity
        try {
            $orphanUsers = User::whereNull('organization_id')
                ->where('onboarding_status', 'active')
                ->count();

            $orphanActivities = \App\Models\Activity::whereNotExists(function ($q) {
                $q->select(\DB::raw(1))->from('users')->whereRaw('users.id = activities.user_id');
            })->count();

            $health['data_integrity'] = [
                'status'             => ($orphanUsers > 0 || $orphanActivities > 0) ? 'warning' : 'healthy',
                'orphan_users'       => $orphanUsers,
                'orphan_activities'  => $orphanActivities,
                'message'            => "Users without org: {$orphanUsers}, Orphan activities: {$orphanActivities}",
            ];
        } catch (\Exception $e) {
            $health['data_integrity'] = ['status' => 'unknown', 'orphan_users' => 0, 'orphan_activities' => 0, 'message' => 'Cannot check integrity'];
        }

        // 8. View cache
        try {
            \Artisan::call('view:clear');
            $health['view_cache'] = ['status' => 'healthy', 'message' => 'View cache cleared'];
        } catch (\Exception $e) {
            $health['view_cache'] = ['status' => 'warning', 'message' => 'View cache clear failed'];
        }

        // 9. Ollama
        try {
            $ollamaUrl = config('services.ollama.url', 'http://localhost:11434');
            $ch        = curl_init($ollamaUrl . '/api/tags');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            $result   = curl_exec($ch);
            $ollamaOk = !curl_error($ch) && $result !== false;
            curl_close($ch);

            $health['ollama'] = [
                'status'  => $ollamaOk ? 'healthy' : 'warning',
                'message' => $ollamaOk ? 'Ollama running' : 'Ollama not running — fallback active',
            ];
        } catch (\Exception $e) {
            $health['ollama'] = ['status' => 'unknown', 'message' => 'Cannot check Ollama'];
        }

        \Cache::put('system_health', $health, 3600);
        \Cache::put('system_health_at', now()->toDateTimeString(), 3600);

        return $health;
    }

    public function clearStaleCache(): void
    {
        $orgs = Organization::all();
        foreach ($orgs as $org) {
            \Cache::forget("ai_summary_{$org->id}_" . now()->subHours(2)->format('Y-m-d-H'));
            \Cache::forget("ceo_summary_{$org->id}_" . now()->subHours(2)->format('Y-m-d-H'));
        }
        Log::info('AutonomousAgent: Stale cache cleared');
    }

    public function runSecurityScan(): array
    {
        $issues = [];

        if (config('app.debug')) {
            $issues[] = ['severity' => 'critical', 'issue' => 'APP_DEBUG is ON', 'fix' => 'Set APP_DEBUG=false in .env'];
        }

        if (empty(config('app.key'))) {
            $issues[] = ['severity' => 'critical', 'issue' => 'APP_KEY not set', 'fix' => 'Run php artisan key:generate'];
        }

        try {
            $defaultPasswords = User::all()->filter(fn($u) => \Hash::check('password', $u->password))->count();
            if ($defaultPasswords > 0) {
                $issues[] = ['severity' => 'high', 'issue' => "{$defaultPasswords} user(s) have default password 'password'", 'fix' => 'Force password change for these users'];
            }
        } catch (\Exception $e) {}

        if (!is_writable(storage_path())) {
            $issues[] = ['severity' => 'high', 'issue' => 'Storage not writable', 'fix' => 'chmod -R 775 storage/'];
        }

        if (config('session.driver') === 'file') {
            $issues[] = ['severity' => 'low', 'issue' => 'Using file sessions', 'fix' => 'Consider Redis for sessions in production'];
        }

        if (!empty($issues)) {
            $criticalCount = count(array_filter($issues, fn($i) => $i['severity'] === 'critical'));
            if ($criticalCount > 0) {
                $this->alertSuperAdmin("🔐 {$criticalCount} Critical Security Issue(s)", "Security scan found {$criticalCount} critical issue(s). Review at /agent/health", 'critical');
            }
        }

        \Cache::put('security_scan', $issues, 3600);
        \Cache::put('security_scan_at', now()->toDateTimeString(), 3600);

        return $issues;
    }

    private function alertSuperAdmin(string $title, string $message, string $priority = 'normal'): void
    {
        $superAdmins = User::role('super_admin')->get();

        foreach ($superAdmins as $admin) {
            $orgId = $admin->organization_id ?? 0;
            if ($this->canNotify($orgId, $admin->id, 'system_' . md5($title), 6)) {
                AgentNotification::create([
                    'organization_id'   => $orgId,
                    'user_id'           => $admin->id,
                    'notification_type' => 'system_health',
                    'title'             => $title,
                    'message'           => $message,
                    'action_url'        => '/agent/health',
                    'action_label'      => 'View Health',
                    'priority'          => $priority,
                    'metadata'          => ['type' => 'system_alert', 'time' => now()->toDateTimeString()],
                ]);
            }
        }
    }

    private function checkWorkloadImbalance(Organization $org): void
    {
        $overloaded = Task::whereHas('project', fn($q) => $q->where('organization_id', $org->id))
            ->whereNotIn('status', ['done', 'cancelled'])
            ->selectRaw('assigned_to, COUNT(*) as task_count')
            ->groupBy('assigned_to')
            ->having('task_count', '>', 8)
            ->get();

        foreach ($overloaded as $row) {
            $member = User::find($row->assigned_to);
            if (!$member) continue;

            $owner = $this->getOwner($org);
            if ($owner && $this->canNotify($org->id, $owner->id, 'workload_imbalance_' . $member->id)) {
                $this->createNotification([
                    'organization_id'       => $org->id,
                    'user_id'               => $owner->id,
                    'triggered_for_user_id' => $member->id,
                    'notification_type'     => 'workload_alert',
                    'title'                 => "😰 {$member->name} is overloaded ({$row->task_count} tasks)",
                    'message'               => "{$member->name} has {$row->task_count} active tasks assigned. Consider redistributing workload to prevent burnout and ensure quality.",
                    'action_url'            => '/team',
                    'action_label'          => 'View Team',
                    'priority'              => 'high',
                    'metadata'              => ['member_id' => $member->id, 'task_count' => $row->task_count],
                ]);
            }
        }
    }

    private function checkSprintRisk(Organization $org): void
    {
        $atRiskSprints = Sprint::where('organization_id', $org->id)
            ->where('status', 'active')
            ->where('end_date', '<=', now()->addDays(2))
            ->get();

        foreach ($atRiskSprints as $sprint) {
            $total     = $sprint->tasks()->count();
            $done      = $sprint->tasks()->where('status', 'done')->count();
            $pct       = $total > 0 ? round(($done / $total) * 100) : 0;

            if ($pct < 60) {
                $owner = $this->getOwner($org);
                if ($owner && $this->canNotify($org->id, $owner->id, 'sprint_risk_' . $sprint->id)) {
                    $daysLeft = (int) now()->diffInDays($sprint->end_date, false);
                    $this->createNotification([
                        'organization_id'   => $org->id,
                        'user_id'           => $owner->id,
                        'notification_type' => 'sprint_risk',
                        'title'             => "⚠️ Sprint \"{$sprint->name}\" at risk ({$pct}% done)",
                        'message'           => "Sprint \"{$sprint->name}\" ends in {$daysLeft} day(s) but only {$pct}% complete ({$done}/{$total} tasks done). Intervention may be needed.",
                        'action_url'        => '/sprints',
                        'action_label'      => 'View Sprints',
                        'priority'          => 'high',
                        'metadata'          => ['sprint_id' => $sprint->id, 'completion_pct' => $pct, 'days_left' => $daysLeft],
                    ]);
                }
            }
        }
    }

    private function checkInactivity(Organization $org): void
    {
        $members = User::where('organization_id', $org->id)->where('is_active', true)->get();

        foreach ($members as $member) {
            if ($this->isOnLeave($member->id)) continue;

            $recentLogs = WorkLog::where('user_id', $member->id)
                ->where('log_date', '>=', now()->subDays(3)->toDateString())
                ->count();

            if ($recentLogs === 0) {
                $manager = $this->getManager($member, $org);
                $notify  = $manager ?? $this->getOwner($org);

                if ($notify && $this->canNotify($org->id, $notify->id, 'inactivity_3d_' . $member->id)) {
                    $this->createNotification([
                        'organization_id'       => $org->id,
                        'user_id'               => $notify->id,
                        'triggered_for_user_id' => $member->id,
                        'notification_type'     => 'team_health',
                        'title'                 => "🔴 {$member->name} inactive for 3+ days",
                        'message'               => "{$member->name} has not logged any work for 3 or more consecutive days. No work log entries found since " . now()->subDays(3)->format('M j') . ". Please check in.",
                        'action_url'            => '/team',
                        'action_label'          => 'View Team',
                        'priority'              => 'high',
                        'metadata'              => ['member_id' => $member->id, 'days_inactive' => 3],
                    ]);
                }
            }
        }
    }

    private function checkGithubAnomaly(Organization $org): void
    {
        $thisWeekStart = now()->startOfWeek();
        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd   = now()->subWeek()->endOfWeek();

        $thisWeek = Activity::where('organization_id', $org->id)
            ->where('event_type', 'commit')
            ->where('occurred_at', '>=', $thisWeekStart)
            ->count();

        $lastWeek = Activity::where('organization_id', $org->id)
            ->where('event_type', 'commit')
            ->whereBetween('occurred_at', [$lastWeekStart, $lastWeekEnd])
            ->count();

        if ($lastWeek > 0 && $thisWeek < ($lastWeek * 0.5)) {
            $owner = $this->getOwner($org);
            if ($owner && $this->canNotify($org->id, $owner->id, 'github_anomaly_' . now()->weekOfYear)) {
                $drop = round((1 - $thisWeek / $lastWeek) * 100);
                $this->createNotification([
                    'organization_id'   => $org->id,
                    'user_id'           => $owner->id,
                    'notification_type' => 'github_stale',
                    'title'             => "📉 GitHub commit activity down {$drop}% this week",
                    'message'           => "Commit activity dropped significantly: {$thisWeek} commits this week vs {$lastWeek} last week ({$drop}% decrease). This may indicate a productivity issue or sync problem.",
                    'action_url'        => '/ai',
                    'action_label'      => 'View AI Intel',
                    'priority'          => 'normal',
                    'metadata'          => ['this_week' => $thisWeek, 'last_week' => $lastWeek, 'drop_pct' => $drop],
                ]);
            }
        }
    }

    public function runFairnessCheck(Organization $org): void
    {
        $engine = new FairnessEngine();
        $flags  = $engine->analyzeOrganization($org->id);

        if (!empty($flags)) {
            $owner = $this->getOwner($org);
            if ($owner) {
                $this->createNotification([
                    'organization_id'   => $org->id,
                    'user_id'           => $owner->id,
                    'notification_type' => 'fairness_alert',
                    'title'             => '⚖️ Fairness Analysis: ' . count($flags) . ' new issue(s) detected',
                    'message'           => 'The AI fairness engine detected ' . count($flags) . ' potential issue(s) in your team. Review them in the Fairness Engine before they escalate.',
                    'action_url'        => '/fairness',
                    'action_label'      => 'Review Flags',
                    'priority'          => 'high',
                ]);
            }
        }
    }
}

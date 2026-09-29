<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Models\AgentNotification;
use App\Models\Blocker;
use App\Models\Organization;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EmployeeSafetyNetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = now()->toDateString();
        $orgs  = Organization::where('status', 'active')->get();

        foreach ($orgs as $org) {
            $this->processWorkLogAutoDrafts($org->id, $today);
            $this->processStalledTasks($org->id);
            $this->processOrphanedBlockers($org->id);
        }
    }

    private function processWorkLogAutoDrafts(int $orgId, string $today): void
    {
        $loggedUserIds = WorkLog::where('organization_id', $orgId)
            ->where('log_date', $today)
            ->pluck('user_id')
            ->toArray();

        $inactiveUsers = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->whereNotIn('id', $loggedUserIds)
            ->get();

        foreach ($inactiveUsers as $user) {
            $commitsCount = Activity::where('user_id', $user->id)
                ->where('event_type', 'commit')
                ->whereDate('occurred_at', $today)
                ->count();

            $completedTasksCount = Task::where('assigned_to', $user->id)
                ->where('status', 'done')
                ->whereDate('completed_at', $today)
                ->count();

            if ($commitsCount > 0 || $completedTasksCount > 0) {
                AgentNotification::create([
                    'user_id'         => $user->id,
                    'organization_id' => $orgId,
                    'type'            => 'worklog_reminder',
                    'title'           => 'Outy Drafted Your Work Log 📝',
                    'message'         => "I noticed {$commitsCount} commits and {$completedTasksCount} completed tasks today. Tap to confirm your daily work log!",
                    'data'            => [
                        'action_url' => route('worklog.today'),
                        'commits'    => $commitsCount,
                        'tasks'      => $completedTasksCount,
                    ],
                ]);
            }
        }
    }

    private function processStalledTasks(int $orgId): void
    {
        $stalledCutoff = now()->subHours(48);

        $stalledTasks = Task::whereHas('project', fn($q) => $q->where('organization_id', $orgId))
            ->whereIn('status', ['in_progress', 'in_review'])
            ->where('updated_at', '<', $stalledCutoff)
            ->whereNotNull('assigned_to')
            ->get();

        foreach ($stalledTasks as $task) {
            AgentNotification::create([
                'user_id'         => $task->assigned_to,
                'organization_id' => $orgId,
                'type'            => 'stalled_task',
                'title'           => 'Task Activity Alert ⏳',
                'message'         => "Task \"{$task->title}\" has had no updates for 48+ hours. Are you blocked or waiting on code review?",
                'data'            => ['task_id' => $task->id],
            ]);
        }
    }

    private function processOrphanedBlockers(int $orgId): void
    {
        $orphanedBlockers = Blocker::where('organization_id', $orgId)
            ->open()
            ->whereNull('blocking_user_id')
            ->get();

        foreach ($orphanedBlockers as $blocker) {
            $blockedUser = $blocker->blockedUser;
            if ($blockedUser && $blockedUser->reporting_manager_id) {
                $blocker->update(['blocking_user_id' => $blockedUser->reporting_manager_id]);
                Log::info("Orphaned blocker #{$blocker->id} auto-routed to reporting manager #{$blockedUser->reporting_manager_id}");
            }
        }
    }
}

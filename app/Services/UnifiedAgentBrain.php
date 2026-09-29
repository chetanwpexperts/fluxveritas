<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\AgentNotification;
use App\Models\Blocker;
use App\Models\Department;
use App\Models\EmployeeStatus;
use App\Models\FairnessFlag;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class UnifiedAgentBrain
{
    private int $orgId;
    private User $user;

    public function __construct(User $user)
    {
        $this->user  = $user;
        $this->orgId = $user->organization_id ?? 1;
    }

    public function buildContext(string $question): array
    {
        $q       = strtolower($question);
        $context = [
            'org'      => $this->getOrgInfo(),
            'user'     => $this->getUserInfo(),
            'question' => $question,
        ];

        if ($this->isAbout($q, ['system', 'health', 'database', 'server', 'cache', 'storage', 'memory', 'queue', 'ollama', 'healthy', 'status', 'working', 'performance', 'error', 'log'])) {
            $context['system_health'] = $this->getSystemHealth();
        }

        if ($this->isAbout($q, ['team', 'member', 'employee', 'people', 'who', 'staff', 'performing', 'performance', 'active', 'inactive', 'working'])) {
            $context['team'] = $this->getTeamData();
        }

        if ($this->isAbout($q, ['log', 'logged', 'work log', 'today', 'activity', 'activities', 'working on', 'done today', 'hours', 'time'])) {
            $context['work_logs'] = $this->getWorkLogData();
        }

        if ($this->isAbout($q, ['task', 'ticket', 'todo', 'assigned', 'pending', 'doing', 'completed', 'backlog', 'kanban', 'sprint', 'blocked task'])) {
            $context['tasks'] = $this->getTaskData();
        }

        if ($this->isAbout($q, ['blocker', 'blocked', 'blocking', 'stuck', 'dependency', 'waiting', 'dispute', 'conflict', 'issue'])) {
            $context['blockers'] = $this->getBlockerData();
        }

        if ($this->isAbout($q, ['fairness', 'fair', 'bias', 'unfair', 'favourit', 'favorit', 'flag', 'flagged', 'equal', 'workload', 'distribution'])) {
            $context['fairness'] = $this->getFairnessData();
        }

        if ($this->isAbout($q, ['github', 'commit', 'pr', 'pull request', 'code', 'push', 'repository', 'repo', 'sync'])) {
            $context['github'] = $this->getGithubData();
        }

        if ($this->isAbout($q, ['department', 'dept', 'engineering', 'sales', 'hr', 'finance', 'operations', 'design'])) {
            $context['departments'] = $this->getDepartmentData();
        }

        if ($this->isAbout($q, ['sprint', 'iteration', 'cycle', 'release', 'milestone', 'planning'])) {
            $context['sprints'] = $this->getSprintData();
        }

        if ($this->isAbout($q, ['notification', 'alert', 'remind', 'warning', 'critical', 'urgent'])) {
            $context['notifications'] = $this->getNotificationData();
        }

        if ($this->isAbout($q, ['module', 'feature', 'enabled', 'disabled', 'plan', 'available', 'access', 'permission'])) {
            $context['modules'] = $this->getModuleData();
        }

        if ($this->isAbout($q, ['how', 'what is', 'explain', 'guide', 'help', 'tutorial', 'steps', 'where', 'navigate'])) {
            $context['platform_knowledge'] = $this->getPlatformKnowledge($question);
        }

        if ($this->isAbout($q, ['increment', 'salary', 'raise', 'appraisal', 'review', 'hike', 'promotion', 'performance review'])) {
            $context['increment'] = $this->getIncrementData();
        }

        return $context;
    }

    public function buildPrompt(string $question, array $context): string
    {
        $orgName = $context['org']['name'] ?? 'the organization';

        $prompt  = "You are OutraqHQ AI assistant for {$orgName}.\n";
        $prompt .= "Answer this question using the data below. Be direct, specific and helpful.\n";
        $prompt .= "Use the actual numbers and names from the data. Do not refuse. Do not say you cannot help.\n\n";
        $prompt .= "Question: \"{$question}\"\n\n";
        $prompt .= "AVAILABLE DATA:\n";

        if (isset($context['system_health']) && !empty($context['system_health'])) {
            $prompt .= "\nSYSTEM HEALTH STATUS:\n";
            foreach ($context['system_health'] as $check => $data) {
                $status  = $data['status'] ?? 'unknown';
                $message = $data['message'] ?? '';
                $emoji   = match($status) { 'healthy' => '✅', 'warning' => '⚠️', 'critical' => '🔴', default => 'ℹ️' };
                $prompt .= "{$emoji} {$check}: {$status} — {$message}\n";
            }
        }

        if (isset($context['team'])) {
            $team    = $context['team'];
            $prompt .= "\nTEAM STATUS:\n";
            $prompt .= "Total members: {$team['total_members']}\n";
            $prompt .= "Active: {$team['active_members']}\n";
            $prompt .= "On leave: {$team['on_leave']}\n";
            $prompt .= "Members and stats:\n";
            foreach ($team['members'] as $m) {
                $prompt .= "- {$m['name']} (Role: {$m['role']}, Commits: {$m['commits']}, Score: {$m['score']}, Status: {$m['status']})\n";
            }
        }

        if (isset($context['work_logs'])) {
            $logs    = $context['work_logs'];
            $prompt .= "\nWORK LOG STATUS TODAY:\n";
            $prompt .= "Entries today: {$logs['today_count']}\n";
            $prompt .= "Hours tracked: {$logs['total_hours_today']}h\n";
            $prompt .= "Who logged: {$logs['logged_today']}\n";
            $prompt .= "Who did NOT log: {$logs['not_logged_today']}\n";
        }

        if (isset($context['tasks'])) {
            $tasks   = $context['tasks'];
            $prompt .= "\nTASK STATUS:\n";
            $prompt .= "Total: {$tasks['total']}\n";
            $prompt .= "In progress: {$tasks['in_progress']}\n";
            $prompt .= "Blocked: {$tasks['blocked']}\n";
            $prompt .= "Done: {$tasks['done']}\n";
            $prompt .= "Overdue: {$tasks['overdue']}\n";
            if (!empty($tasks['blocked_tasks'])) {
                $prompt .= "Blocked task details:\n";
                foreach ($tasks['blocked_tasks'] as $t) {
                    $prompt .= "- {$t['ticket']}: {$t['title']} → {$t['assignee']}\n";
                }
            }
        }

        if (isset($context['blockers'])) {
            $b       = $context['blockers'];
            $prompt .= "\nBLOCKER STATUS:\n";
            $prompt .= "Open: {$b['open_count']}\n";
            $prompt .= "Disputed: {$b['disputed_count']}\n";
            $prompt .= "Critical (7+ days): {$b['critical_count']}\n";
            if (!empty($b['list'])) {
                $prompt .= "Active blockers:\n";
                foreach ($b['list'] as $bl) {
                    $disputed = $bl['disputed'] ? ' ⚠️ DISPUTED' : '';
                    $prompt  .= "- {$bl['title']} ({$bl['days']} days open, {$bl['priority']} priority){$disputed}\n";
                }
            }
        }

        if (isset($context['fairness'])) {
            $f       = $context['fairness'];
            $prompt .= "\nFAIRNESS STATUS:\n";
            $prompt .= "Pending flags: {$f['pending_flags']}\n";
            $prompt .= "Confirmed: {$f['confirmed_flags']}\n";
            if (!empty($f['flags'])) {
                foreach ($f['flags'] as $fl) {
                    $prompt .= "- {$fl['type']}: {$fl['description']} ({$fl['confidence']}% confidence)\n";
                }
            }
        }

        if (isset($context['github'])) {
            $g       = $context['github'];
            $prompt .= "\nGITHUB ACTIVITY:\n";
            $prompt .= "Commits this week: {$g['commits_week']}\n";
            $prompt .= "PRs this week: {$g['prs_week']}\n";
            $prompt .= "Last synced: {$g['last_synced']}\n";
            if (!empty($g['top_contributors'])) {
                $prompt .= "Top contributors:\n";
                foreach ($g['top_contributors'] as $c) {
                    $prompt .= "- {$c['name']}: {$c['commits']} commits\n";
                }
            }
        }

        if (isset($context['departments'])) {
            $prompt .= "\nDEPARTMENTS:\n";
            foreach ($context['departments'] as $d) {
                $prompt .= "- {$d['name']} ({$d['type']}): {$d['member_count']} members, {$d['today_logs']} logs today\n";
            }
        }

        if (isset($context['sprints'])) {
            $s       = $context['sprints'];
            $prompt .= "\nSPRINT STATUS:\n";
            $prompt .= "Active sprint: {$s['active_name']}\n";
            $prompt .= "Progress: {$s['completion_pct']}%\n";
            $prompt .= "Tasks: {$s['completed_tasks']} of {$s['total_tasks']} done\n";
            $prompt .= "Days remaining: {$s['days_remaining']}\n";
        }

        if (isset($context['notifications'])) {
            $n       = $context['notifications'];
            $prompt .= "\nALERTS:\n";
            $prompt .= "Unread: {$n['unread_count']}\n";
            $prompt .= "Critical: {$n['critical_count']}\n";
            if (!empty($n['recent'])) {
                foreach ($n['recent'] as $notif) {
                    $prompt .= "- [{$notif['priority']}] {$notif['title']}\n";
                }
            }
        }

        if (isset($context['modules'])) {
            $prompt .= "\nMODULES:\n";
            foreach ($context['modules'] as $mod) {
                $status  = ($mod['is_enabled'] ?? false) ? 'enabled' : 'disabled';
                $label   = $mod['label'] ?? $mod['name'] ?? 'Unknown';
                $prompt .= "- {$label}: {$status}\n";
            }
        }

        if (isset($context['platform_knowledge']) && $context['platform_knowledge']) {
            $prompt .= "\nPLATFORM KNOWLEDGE:\n" . $context['platform_knowledge'] . "\n";
        }

        if (isset($context['increment'])) {
            $prompt .= "\nINCREMENT SYSTEM:\n" . $context['increment'] . "\n";
        }

        $prompt .= "\nNow answer the question \"{$question}\" using the data above.\n";
        $prompt .= "Be specific. Use real names and numbers. Do not make up information.\n";
        $prompt .= "If the data shows a problem, mention it. Format your answer clearly.";

        return $prompt;
    }

    // ── Data collectors ──────────────────────────────────────────────────────

    private function getOrgInfo(): array
    {
        $org = Organization::find($this->orgId);
        return [
            'name'   => $org?->name ?? 'Unknown',
            'plan'   => $org?->plan ?? 'free',
            'status' => $org?->status ?? 'active',
        ];
    }

    private function getUserInfo(): array
    {
        return [
            'name'       => $this->user->name,
            'role'       => $this->user->getRoleNames()->first() ?? 'user',
            'department' => \App\Models\Department::find($this->user->department_id)?->name ?? 'Unknown',
        ];
    }

    private function getSystemHealth(): array
    {
        $health = Cache::get('system_health', []);
        if (empty($health)) {
            $agent  = new AutonomousAgent();
            $health = $agent->checkSystemHealth();
        }
        return $health;
    }

    private function getTeamData(): array
    {
        $members = User::where('organization_id', $this->orgId)
            ->where('is_active', true)
            ->with('roles')
            ->get();

        $onLeave = EmployeeStatus::where('organization_id', $this->orgId)
            ->where('status', 'on_leave')
            ->where('starts_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })->pluck('user_id')->toArray();

        $memberData = $members->map(function ($m) use ($onLeave) {
            $commits = Activity::where('user_id', $m->id)
                ->where('event_type', 'commit')
                ->where('occurred_at', '>=', now()->subDays(30))
                ->count();

            $score = Activity::where('user_id', $m->id)
                ->where('occurred_at', '>=', now()->subDays(30))
                ->avg('complexity_score') ?? 0;

            return [
                'name'        => $m->name,
                'email'       => $m->email,
                'role'        => $m->getRoleNames()->first() ?? $m->role,
                'commits'     => $commits,
                'score'       => round($score, 1),
                'status'      => in_array($m->id, $onLeave) ? 'On Leave' : 'Active',
                'github'      => $m->github_username ?? 'Not set',
                'last_active' => Activity::where('user_id', $m->id)->max('occurred_at') ?? 'Never',
            ];
        });

        return [
            'total_members'  => $members->count(),
            'active_members' => $members->count() - count($onLeave),
            'on_leave'       => count($onLeave),
            'members'        => $memberData->toArray(),
        ];
    }

    private function getWorkLogData(): array
    {
        $todayLogs = WorkLog::where('organization_id', $this->orgId)
            ->where('log_date', today())
            ->get();

        $loggedUserIds = $todayLogs->pluck('user_id')->unique()->toArray();

        $allUserIds = User::where('organization_id', $this->orgId)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        $notLoggedIds = array_diff($allUserIds, $loggedUserIds);

        $notLoggedNames = User::whereIn('id', $notLoggedIds)->pluck('name')->join(', ');
        $loggedNames    = User::whereIn('id', $loggedUserIds)->pluck('name')->join(', ');
        $totalMinutes   = $todayLogs->sum('duration_minutes');

        $weekLogs = WorkLog::where('organization_id', $this->orgId)
            ->where('log_date', '>=', now()->startOfWeek())
            ->count();

        return [
            'today_count'       => $todayLogs->count(),
            'logged_today'      => $loggedNames ?: 'Nobody yet',
            'not_logged_today'  => $notLoggedNames ?: 'Everyone has logged',
            'total_hours_today' => round($totalMinutes / 60, 1),
            'week_logs'         => $weekLogs,
        ];
    }

    private function getTaskData(): array
    {
        try {
            $tasks = Task::whereHas('project', fn($q) => $q->where('organization_id', $this->orgId))
                ->with(['assignedTo', 'project'])
                ->whereNull('archived_at')
                ->get();
        } catch (\Exception $e) {
            return ['total' => 0, 'in_progress' => 0, 'blocked' => 0, 'done' => 0, 'todo' => 0, 'overdue' => 0, 'blocked_tasks' => [], 'overdue_tasks' => []];
        }

        $blocked = $tasks->where('status', 'blocked');
        $overdue = $tasks->filter(fn($t) => $t->due_date && $t->due_date < now() && $t->status !== 'done');

        return [
            'total'        => $tasks->count(),
            'in_progress'  => $tasks->where('status', 'in_progress')->count(),
            'blocked'      => $blocked->count(),
            'done'         => $tasks->where('status', 'done')->count(),
            'todo'         => $tasks->where('status', 'todo')->count(),
            'overdue'      => $overdue->count(),
            'blocked_tasks'=> $blocked->map(fn($t) => [
                'ticket'   => $t->ticket_number ?? '#',
                'title'    => $t->title,
                'assignee' => $t->assignedTo?->name ?? 'Unassigned',
            ])->toArray(),
            'overdue_tasks'=> $overdue->map(fn($t) => [
                'ticket'   => $t->ticket_number ?? '#',
                'title'    => $t->title,
                'assignee' => $t->assignedTo?->name ?? 'Unassigned',
                'due'      => $t->due_date->format('M j'),
            ])->toArray(),
        ];
    }

    private function getBlockerData(): array
    {
        $blockers = Blocker::where('organization_id', $this->orgId)
            ->where('status', 'open')
            ->with(['blockedUser', 'blockingUser'])
            ->get();

        $disputed = $blockers->where('ownership_disputed', true);
        $critical = $blockers->filter(fn($b) => $b->daysOpen() >= 7);

        return [
            'open_count'     => $blockers->count(),
            'disputed_count' => $disputed->count(),
            'critical_count' => $critical->count(),
            'list'           => $blockers->map(fn($b) => [
                'title'    => $b->title,
                'days'     => $b->daysOpen(),
                'priority' => $b->priority,
                'disputed' => (bool) $b->ownership_disputed,
                'blocked'  => $b->blockedUser?->name ?? 'Unknown',
                'blocking' => $b->blockingUser?->name ?? $b->external_person_name ?? 'External',
            ])->toArray(),
        ];
    }

    private function getFairnessData(): array
    {
        $flags     = FairnessFlag::where('organization_id', $this->orgId)->with('flaggedUser')->get();
        $pending   = $flags->where('status', 'pending');
        $confirmed = $flags->where('status', 'confirmed');

        return [
            'pending_flags'   => $pending->count(),
            'confirmed_flags' => $confirmed->count(),
            'total_flags'     => $flags->count(),
            'flags'           => $pending->map(fn($f) => [
                'type'        => ucwords(str_replace('_', ' ', $f->flag_type)),
                'description' => $f->evidence['description'] ?? 'No description',
                'confidence'  => round($f->confidence_score * 100),
                'person'      => $f->flaggedUser?->name ?? 'Unknown',
            ])->toArray(),
        ];
    }

    private function getGithubData(): array
    {
        $commitsWeek = Activity::where('organization_id', $this->orgId)
            ->where('event_type', 'commit')
            ->where('occurred_at', '>=', now()->startOfWeek())
            ->count();

        $prsWeek = Activity::where('organization_id', $this->orgId)
            ->whereIn('event_type', ['pr_opened', 'pr_merged'])
            ->where('occurred_at', '>=', now()->startOfWeek())
            ->count();

        $lastSync = Activity::where('organization_id', $this->orgId)->max('occurred_at');

        $topContributors = Activity::where('organization_id', $this->orgId)
            ->where('occurred_at', '>=', now()->subDays(30))
            ->where('event_type', 'commit')
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->with('user')
            ->take(5)
            ->get()
            ->map(fn($a) => [
                'name'    => $a->user?->name ?? 'Unknown',
                'commits' => $a->total,
            ])->toArray();

        return [
            'commits_week'     => $commitsWeek,
            'prs_week'         => $prsWeek,
            'last_synced'      => $lastSync ? Carbon::parse($lastSync)->diffForHumans() : 'Never',
            'top_contributors' => $topContributors,
        ];
    }

    private function getDepartmentData(): array
    {
        $depts = Department::where('organization_id', $this->orgId)->with('users')->get();

        return $depts->map(fn($d) => [
            'name'         => $d->name,
            'type'         => $d->type ?? 'general',
            'work_mode'    => $d->work_mode ?? 'hybrid',
            'member_count' => $d->users->count(),
            'today_logs'   => WorkLog::where('department_id', $d->id)->where('log_date', today())->count(),
        ])->toArray();
    }

    private function getSprintData(): array
    {
        try {
            $activeSprint = Sprint::whereHas('project', fn($q) => $q->where('organization_id', $this->orgId))
                ->where('status', 'active')
                ->with('tasks')
                ->first();
        } catch (\Exception $e) {
            $activeSprint = null;
        }

        if (!$activeSprint) {
            return ['active_name' => 'No active sprint', 'completion_pct' => 0, 'total_tasks' => 0, 'completed_tasks' => 0, 'days_remaining' => 0];
        }

        $tasks     = $activeSprint->tasks;
        $completed = $tasks->where('status', 'done')->count();
        $total     = $tasks->count();
        $pct       = $total > 0 ? round(($completed / $total) * 100) : 0;

        return [
            'active_name'     => $activeSprint->name,
            'goal'            => $activeSprint->goal ?? '',
            'completion_pct'  => $pct,
            'total_tasks'     => $total,
            'completed_tasks' => $completed,
            'days_remaining'  => $activeSprint->end_date
                ? now()->diffInDays($activeSprint->end_date, false)
                : 'Unknown',
        ];
    }

    private function getModuleData(): array
    {
        $moduleService = new ModuleService();
        return $moduleService->getOrgModules($this->orgId);
    }

    private function getNotificationData(): array
    {
        $notifs = AgentNotification::where('user_id', $this->user->id)
            ->where('is_dismissed', false)
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        return [
            'unread_count'   => $notifs->where('is_read', false)->count(),
            'critical_count' => $notifs->where('priority', 'critical')->count(),
            'recent'         => $notifs->map(fn($n) => [
                'title'    => $n->title,
                'priority' => $n->priority,
                'type'     => $n->notification_type,
                'time'     => $n->created_at->diffForHumans(),
            ])->toArray(),
        ];
    }

    private function getPlatformKnowledge(string $question): string
    {
        $helpService = new HelpAgentService();
        return $helpService->answerFromKnowledge($question) ?? '';
    }

    private function getIncrementData(): string
    {
        return "The increment system is planned but not yet implemented. It will calculate performance-based salary increments automatically using multi-layer verification. Coming soon in the next update.";
    }

    private function isAbout(string $question, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($question, $keyword)) {
                return true;
            }
        }
        return false;
    }
}

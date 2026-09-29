<?php

namespace App\Services\Outy\Tools;

use App\Models\Task;
use App\Models\User;

/** The user's own open tasks. */
class GetMyTasks extends OutyTool
{
    public function name(): string
    {
        return 'get_my_tasks';
    }

    public function description(): string
    {
        return "The current user's own open tasks (not done or cancelled): ticket, title, status, priority, due date and whether it is overdue.";
    }

    protected function handle(User $user, array $args): array
    {
        $open = Task::where('assigned_to', $user->id)
            ->whereHas('project', fn ($q) => $q->withoutGlobalScopes()->where('organization_id', $user->organization_id))
            ->whereNotIn('status', ['done', 'cancelled'])
            ->whereNull('archived_at')
            ->orderByRaw('due_date IS NULL, due_date')
            ->get(['ticket_number', 'title', 'status', 'priority', 'due_date']);

        $today = now()->toDateString();

        return [
            'open_count' => $open->count(),
            'by_status'  => $open->countBy('status')->all(),
            'overdue'    => $open->filter(fn ($t) => $t->due_date && substr((string) $t->due_date, 0, 10) < $today)->count(),
            'tasks'      => $open->take(20)->map(fn ($t) => [
                'ticket'   => $t->ticket_number,
                'title'    => $t->title,
                'status'   => $t->status,
                'priority' => $t->priority,
                'due'      => $t->due_date ? substr((string) $t->due_date, 0, 10) : null,
            ])->values()->all(),
            'shown'      => min(20, $open->count()),
        ];
    }
}

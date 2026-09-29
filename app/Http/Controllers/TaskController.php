<?php

namespace App\Http\Controllers;

use App\Models\AgentNotification;
use App\Models\Department;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskHistory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        // Admin / Owner / CEO / SA → see ALL org tasks
        if ($user->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin'])) {
            $query = Task::whereHas('project',
                fn($q) => $q->where('organization_id', $orgId)
            )->with(['assignee', 'project']);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('assignee')) {
                $query->where('assigned_to', $request->assignee);
            }
            if ($request->filled('priority')) {
                $query->where('priority', $request->priority);
            }

            $tasks = $query->orderByRaw(
                "FIELD(status,'in_progress','pending','review','done','cancelled')"
            )->orderByRaw(
                "FIELD(priority,'urgent','high','medium','low')"
            )->paginate(25);

            $base = fn() => Task::whereHas('project', fn($q) => $q->where('organization_id', $orgId));

            $stats = [
                'total'       => $base()->count(),
                'in_progress' => $base()->where('status', 'in_progress')->count(),
                'overdue'     => $base()->where('status', '!=', 'done')->where('due_date', '<', today())->count(),
                'done_week'   => $base()->where('status', 'done')->where('completed_at', '>=', now()->startOfWeek())->count(),
                'unassigned'  => $base()->whereNull('assigned_to')->count(),
            ];

            $teamMembers = User::where('organization_id', $orgId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);

            return view('tasks.index', compact('tasks', 'stats', 'teamMembers'))
                ->with('isAdminView', true);
        }

        // Manager / Team Lead → tasks for all users in their reporting chain
        if ($user->hasAnyRole(['manager', 'team_lead'])) {
            $reportIds = $user->manageableUserIds();

            $query = Task::whereIn('assigned_to', $reportIds)
                ->with(['assignee', 'project']);

            if ($request->filled('status'))   $query->where('status', $request->status);
            if ($request->filled('assignee')) $query->where('assigned_to', $request->assignee);
            if ($request->filled('priority')) $query->where('priority', $request->priority);

            $tasks = $query->orderByRaw(
                "FIELD(status,'in_progress','pending','review','done','cancelled')"
            )->paginate(25);

            $stats = [
                'total'       => Task::whereIn('assigned_to', $reportIds)->count(),
                'in_progress' => Task::whereIn('assigned_to', $reportIds)->where('status', 'in_progress')->count(),
                'overdue'     => Task::whereIn('assigned_to', $reportIds)->where('status', '!=', 'done')->where('due_date', '<', today())->count(),
                'done_week'   => Task::whereIn('assigned_to', $reportIds)->where('status', 'done')->where('completed_at', '>=', now()->startOfWeek())->count(),
                'unassigned'  => 0,
            ];

            $teamMembers = User::whereIn('id', $reportIds)->orderBy('name')->get(['id', 'name']);

            return view('tasks.index', compact('tasks', 'stats', 'teamMembers'))
                ->with('isAdminView', true);
        }

        // Employee → own tasks only
        $tasks = Task::where('assigned_to', $user->id)
            ->with(['project'])
            ->orderByRaw("FIELD(status,'in_progress','pending','review','done','cancelled')")
            ->orderByRaw("FIELD(priority,'urgent','high','medium','low')")
            ->paginate(25);

        $stats = [
            'total'       => Task::where('assigned_to', $user->id)->count(),
            'in_progress' => Task::where('assigned_to', $user->id)->where('status', 'in_progress')->count(),
            'overdue'     => Task::where('assigned_to', $user->id)->where('status', '!=', 'done')->where('due_date', '<', today())->count(),
            'done_week'   => Task::where('assigned_to', $user->id)->where('status', 'done')->where('completed_at', '>=', now()->startOfWeek())->count(),
            'unassigned'  => 0,
        ];

        $teamMembers = collect();

        return view('tasks.index', compact('tasks', 'stats', 'teamMembers'))
            ->with('isAdminView', false);
    }

    public function create(Request $request)
    {
        $orgId    = auth()->user()->organization_id;
        $projects = Project::where('organization_id', $orgId)->get();
        $members  = User::where('organization_id', $orgId)->where('is_active', true)->get();
        $sprints  = Sprint::whereHas('project', fn($q) => $q->where('organization_id', $orgId))
            ->whereIn('status', ['planning', 'active'])
            ->with('project')
            ->get();
        $departments = Department::where('organization_id', $orgId)->get();
        $parentTask = $request->filled('parent_id') ? Task::find($request->parent_id) : null;
        $preProject = $request->filled('project_id') ? $request->project_id : null;

        return view('tasks.create', compact('projects', 'members', 'sprints', 'departments', 'parentTask', 'preProject'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'type'       => 'required|in:task,bug,feature,improvement,story,epic,question,incident',
            'priority'   => 'required|in:critical,high,medium,low',
            'assigned_to'=> 'nullable|exists:users,id',
            'status'     => 'nullable|in:backlog,todo,in_progress,in_review,blocked,done,cancelled',
        ]);

        $task = Task::create([
            'project_id'      => $request->project_id,
            'title'           => $request->title,
            'description'     => $request->description,
            'type'            => $request->type,
            'status'          => $request->status ?? 'todo',
            'priority'        => $request->priority,
            'label'           => $request->label,
            'assigned_to'     => $request->assigned_to,
            'assigned_by'     => auth()->id(),
            'reporter_id'     => auth()->id(),
            'department_id'   => $request->department_id,
            'sprint_id'       => $request->sprint_id,
            'parent_task_id'  => $request->parent_task_id,
            'due_date'        => $request->due_date,
            'estimated_hours' => $request->estimated_hours,
            'watchers'        => $request->watchers ? explode(',', $request->watchers) : null,
            'difficulty'      => $request->difficulty ?? 5,
            'visibility_score'=> $request->visibility_score ?? 5,
        ]);

        TaskHistory::create([
            'task_id'   => $task->id,
            'user_id'   => auth()->id(),
            'action'    => 'created',
            'new_value' => $task->title,
        ]);

        if ($task->assigned_to && $task->assigned_to !== auth()->id()) {
            $org = auth()->user()->organization_id;
            AgentNotification::create([
                'organization_id'       => $org,
                'user_id'               => $task->assigned_to,
                'triggered_for_user_id' => auth()->id(),
                'notification_type'     => 'task_assigned',
                'title'                 => '📋 New task assigned: ' . $task->ticket_number,
                'message'               => auth()->user()->name . ' assigned you: ' . $task->title,
                'action_url'            => '/tasks/' . $task->id,
                'action_label'          => 'View Task',
                'priority'              => $task->priority === 'critical' ? 'critical' : ($task->priority === 'high' ? 'high' : 'normal'),
                'is_read'               => false,
            ]);
        }

        return redirect()->route('tasks.show', $task->id)->with('success', 'Task created successfully.');
    }

    public function show(int $id)
    {
        $task = Task::with([
            'project', 'assignee', 'assigner', 'reporter', 'completedBy',
            'subtasks.assignee', 'comments.user', 'history.user',
            'sprint', 'department', 'parent',
        ])->findOrFail($id);

        $orgId    = auth()->user()->organization_id;
        $members  = User::where('organization_id', $orgId)->where('is_active', true)->get();
        $sprints  = Sprint::where('organization_id', $orgId)->get();
        $timeline = $task->comments->map(fn($c) => ['type' => 'comment', 'item' => $c, 'at' => $c->created_at])
            ->merge($task->history->map(fn($h) => ['type' => 'history', 'item' => $h, 'at' => $h->created_at]))
            ->sortBy('at')
            ->values();

        return view('tasks.show', compact('task', 'members', 'sprints', 'timeline'));
    }

    public function update(Request $request, int $id)
    {
        $task = Task::findOrFail($id);
        $changes = [];

        if ($request->filled('status') && $request->status !== $task->status) {
            $changes[] = ['action' => 'status_changed', 'old' => $task->getStatusLabel(), 'new' => ucfirst(str_replace('_', ' ', $request->status))];
            if ($request->status === 'in_progress' && !$task->started_at) {
                $request->merge(['started_at' => now()]);
            }
            if ($request->status === 'done') {
                $request->merge(['completed_at' => now(), 'completed_by' => auth()->id()]);
            }
        }
        if ($request->filled('assigned_to') && $request->assigned_to != $task->assigned_to) {
            $newUser = User::find($request->assigned_to);
            $changes[] = ['action' => 'assigned', 'old' => $task->assignee?->name ?? 'Unassigned', 'new' => $newUser?->name ?? 'Unassigned'];
        }
        if ($request->filled('priority') && $request->priority !== $task->priority) {
            $changes[] = ['action' => 'priority_changed', 'old' => $task->priority, 'new' => $request->priority];
        }

        $task->update($request->only([
            'title', 'description', 'status', 'priority', 'type', 'label',
            'assigned_to', 'department_id', 'sprint_id', 'due_date',
            'estimated_hours', 'actual_hours', 'started_at', 'completed_at', 'completed_by',
        ]));

        foreach ($changes as $change) {
            TaskHistory::create([
                'task_id'   => $task->id,
                'user_id'   => auth()->id(),
                'action'    => $change['action'],
                'old_value' => $change['old'],
                'new_value' => $change['new'],
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return back()->with('success', 'Task updated.');
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate(['status' => 'required|in:backlog,todo,in_progress,in_review,blocked,done,cancelled']);
        $task = Task::findOrFail($id);
        $old  = $task->getStatusLabel();

        $task->status = $request->status;
        if ($request->status === 'in_progress' && !$task->started_at) {
            $task->started_at = now();
        }
        if ($request->status === 'done') {
            $task->completed_at = now();
            $task->completed_by = auth()->id();
        }
        $task->save();

        TaskHistory::create([
            'task_id'   => $task->id,
            'user_id'   => auth()->id(),
            'action'    => 'status_changed',
            'old_value' => $old,
            'new_value' => $task->getStatusLabel(),
        ]);

        return response()->json(['success' => true]);
    }

    public function comment(Request $request, int $id)
    {
        $request->validate(['comment' => 'required|string']);
        $task = Task::findOrFail($id);

        TaskComment::create([
            'task_id' => $task->id,
            'user_id' => auth()->id(),
            'comment' => $request->comment,
        ]);

        TaskHistory::create([
            'task_id'   => $task->id,
            'user_id'   => auth()->id(),
            'action'    => 'commented',
            'new_value' => substr($request->comment, 0, 100),
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function destroy(int $id)
    {
        $task  = Task::findOrFail($id);
        $actor = auth()->user();
        $canManageTask = $task->reporter_id === $actor->id
            || $actor->hasAnyRole(['admin', 'owner', 'super_admin'])
            || ($actor->hasAnyRole(['manager', 'team_lead'])
                && $actor->manageableUserIds()->contains($task->assigned_to));
        if (!$canManageTask) {
            abort(403);
        }
        $task->update(['archived_at' => now()]);
        return redirect()->route('tasks.index')->with('success', 'Task archived.');
    }

    public function myTasks()
    {
        $tasks = Task::with(['project', 'sprint'])
            ->where('assigned_to', auth()->id())
            ->whereNull('archived_at')
            ->orderBy('due_date')
            ->get();

        $today = $tasks->filter(fn($t) =>
            ($t->due_date && $t->due_date->isToday()) || $t->status === 'in_progress'
        );

        $grouped = $tasks->groupBy('status');

        $stats = [
            'done_week'  => Task::where('assigned_to', auth()->id())
                ->where('status', 'done')
                ->where('completed_at', '>=', now()->startOfWeek())
                ->count(),
            'overdue'    => $tasks->filter(fn($t) => $t->isOverdue())->count(),
            'total'      => $tasks->count(),
            'in_progress'=> $tasks->where('status', 'in_progress')->count(),
        ];

        return view('tasks.my-tasks', compact('tasks', 'today', 'grouped', 'stats'));
    }
}

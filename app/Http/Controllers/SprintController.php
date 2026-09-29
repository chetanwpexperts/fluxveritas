<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class SprintController extends Controller
{
    public function index(Request $request)
    {
        $orgId     = auth()->user()->organization_id;
        $projectId = $request->project_id;

        $query = Sprint::whereHas('project', fn($q) => $q->where('organization_id', $orgId))
            ->with(['project', 'tasks'])
            ->orderByRaw("FIELD(status, 'active', 'planning', 'completed')")
            ->orderBy('start_date', 'desc');

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        $mapSprint = function ($sprint) {
            $tasks = $sprint->tasks;
            return [
                'id'                => $sprint->id,
                'name'              => $sprint->name,
                'goal'              => $sprint->goal,
                'status'            => $sprint->status,
                'project'           => $sprint->project?->name,
                'start_date'        => $sprint->start_date?->toDateString(),
                'end_date'          => $sprint->end_date?->toDateString(),
                'days_remaining'    => $sprint->end_date ? now()->diffInDays($sprint->end_date, false) : null,
                'total_tasks'       => $tasks->count(),
                'completed_tasks'   => $tasks->where('status', 'done')->count(),
                'in_progress_tasks' => $tasks->where('status', 'in_progress')->count(),
                'blocked_tasks'     => $tasks->where('status', 'blocked')->count(),
                'completion_pct'    => $tasks->count() > 0
                    ? round(($tasks->where('status', 'done')->count() / $tasks->count()) * 100)
                    : 0,
            ];
        };

        if ($request->ajax()) {
            $perPage = (int) $request->get('per_page', 10);
            $sprints = $query->paginate($perPage)->through($mapSprint);
            return response()->json($sprints);
        }

        $sprints  = $query->get()->map(function ($sprint) {
            $tasks = $sprint->tasks;
            return [
                'id'               => $sprint->id,
                'name'             => $sprint->name,
                'goal'             => $sprint->goal,
                'status'           => $sprint->status,
                'project'          => $sprint->project,
                'start_date'       => $sprint->start_date,
                'end_date'         => $sprint->end_date,
                'days_remaining'   => $sprint->end_date ? now()->diffInDays($sprint->end_date, false) : null,
                'total_tasks'      => $tasks->count(),
                'completed_tasks'  => $tasks->where('status', 'done')->count(),
                'in_progress_tasks'=> $tasks->where('status', 'in_progress')->count(),
                'blocked_tasks'    => $tasks->where('status', 'blocked')->count(),
                'completion_pct'   => $tasks->count() > 0
                    ? round(($tasks->where('status', 'done')->count() / $tasks->count()) * 100)
                    : 0,
            ];
        });

        $projects = Project::where('organization_id', $orgId)->get();

        return view('sprints.index', compact('sprints', 'projects'));
    }

    public function create()
    {
        $orgId    = auth()->user()->organization_id;
        $projects = Project::where('organization_id', $orgId)->get();
        return view('sprints.create', compact('projects'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|min:2|max:100',
            'project_id' => 'required|exists:projects,id',
            'goal'       => 'nullable|string|max:500',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $sprint = Sprint::create([
            'organization_id' => auth()->user()->organization_id,
            'project_id'      => $request->project_id,
            'name'            => $request->name,
            'goal'            => $request->goal,
            'status'          => 'planning',
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'created_by'      => auth()->id(),
        ]);

        $memberIds = $sprint->tasks()
            ->whereNotNull('assigned_to')
            ->pluck('assigned_to')
            ->unique()
            ->toArray();

        if (!empty($memberIds)) {
            NotificationService::sendToMany(
                $memberIds,
                auth()->user()->organization_id,
                'sprint_created',
                '🚀 New Sprint Created: ' . $sprint->name,
                auth()->user()->name . ' created a new sprint "' . $sprint->name . '". Check your assigned tasks.',
                '/sprints/' . $sprint->id,
                'View Sprint',
                'normal',
                ['sprint_id' => $sprint->id],
                auth()->id()
            );
        }

        return redirect()->route('sprints.index')->with('success', 'Sprint created successfully!');
    }

    public function show(int $id)
    {
        $sprint = Sprint::with([
            'project',
            'tasks.assignee',
            'createdBy',
        ])->findOrFail($id);

        $orgId = auth()->user()->organization_id;
        if ($sprint->project->organization_id !== $orgId) {
            abort(403);
        }

        $availableTasks = Task::whereHas('project', fn($q) => $q->where('organization_id', $orgId))
            ->whereNull('sprint_id')
            ->where('status', '!=', 'done')
            ->whereNull('archived_at')
            ->get();

        return view('sprints.show', compact('sprint', 'availableTasks'));
    }

    public function start(int $id)
    {
        $sprint = Sprint::findOrFail($id);
        $sprint->update(['status' => 'active']);
        return back()->with('success', 'Sprint started!');
    }

    public function complete(int $id)
    {
        $sprint = Sprint::findOrFail($id);
        $incompleteTasks = $sprint->tasks()->where('status', '!=', 'done')->count();

        $memberIds = $sprint->tasks()
            ->whereNotNull('assigned_to')
            ->pluck('assigned_to')
            ->unique()
            ->toArray();

        $sprint->update(['status' => 'completed', 'completed_at' => now()]);

        if ($incompleteTasks > 0) {
            $sprint->tasks()->where('status', '!=', 'done')->update([
                'sprint_id' => null,
                'status'    => 'backlog',
            ]);
        }

        if (!empty($memberIds)) {
            NotificationService::sendToMany(
                $memberIds,
                auth()->user()->organization_id,
                'sprint_completed',
                '🏆 Sprint Completed: ' . $sprint->name,
                'Sprint "' . $sprint->name . '" has been completed. Great work team!',
                '/sprints/' . $sprint->id,
                'View Results',
                'normal',
                ['sprint_id' => $sprint->id],
                auth()->id()
            );
        }

        $msg = 'Sprint completed!';
        if ($incompleteTasks > 0) {
            $msg .= " {$incompleteTasks} incomplete task(s) moved to backlog.";
        }

        return back()->with('success', $msg);
    }

    public function addTask(Request $request, int $id)
    {
        $request->validate(['task_id' => 'required|exists:tasks,id']);
        Task::where('id', $request->task_id)->update(['sprint_id' => $id]);
        return back()->with('success', 'Task added to sprint.');
    }

    public function removeTask(int $id, int $taskId)
    {
        Task::where('id', $taskId)->update(['sprint_id' => null]);
        return back()->with('success', 'Task removed from sprint.');
    }
}

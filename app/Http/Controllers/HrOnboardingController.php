<?php

namespace App\Http\Controllers;

use App\Models\OnboardingChecklist;
use App\Models\OnboardingTask;
use App\Models\User;
use Illuminate\Http\Request;

class HrOnboardingController extends Controller
{
    private function isHrOrAdmin(): bool
    {
        return auth()->user()->hasAnyRole(['hr', 'admin', 'owner', 'super_admin']);
    }

    public function index()
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        if ($this->isHrOrAdmin()) {
            $checklists = OnboardingChecklist::where('organization_id', $orgId)
                ->with(['employee', 'tasks'])
                ->orderByDesc('created_at')
                ->paginate(20);

            return view('hr-onboarding.index', compact('checklists'));
        }

        $checklist = OnboardingChecklist::where('organization_id', $orgId)
            ->where('user_id', $user->id)
            ->with(['tasks' => fn($q) => $q->orderBy('sort_order')])
            ->first();

        return view('hr-onboarding.employee', compact('checklist'));
    }

    public function create()
    {
        abort_if(!$this->isHrOrAdmin(), 403);

        $orgId     = auth()->user()->organization_id;
        $employees = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['super_admin']))
            ->orderBy('name')
            ->get();

        $defaultTasks = OnboardingTask::defaultTasks();

        return view('hr-onboarding.create', compact('employees', 'defaultTasks'));
    }

    public function store(Request $request)
    {
        abort_if(!$this->isHrOrAdmin(), 403);

        $auth  = auth()->user();
        $orgId = $auth->organization_id;

        $data = $request->validate([
            'user_id'  => 'required|integer|exists:users,id',
            'due_date' => 'nullable|date|after_or_equal:today',
            'tasks'    => 'nullable|array',
            'tasks.*'  => 'required|string|max:255',
        ]);

        $employee = User::where('id', $data['user_id'])
            ->where('organization_id', $orgId)
            ->firstOrFail();

        $checklist = OnboardingChecklist::create([
            'organization_id' => $orgId,
            'user_id'         => $employee->id,
            'created_by'      => $auth->id,
            'due_date'        => $data['due_date'] ?? null,
        ]);

        $tasks  = $data['tasks'] ?? OnboardingTask::defaultTasks();
        foreach ($tasks as $i => $title) {
            OnboardingTask::create([
                'checklist_id' => $checklist->id,
                'title'        => $title,
                'sort_order'   => $i,
            ]);
        }

        return redirect()->route('hr-onboarding.show', $checklist)
            ->with('success', "Onboarding checklist created for {$employee->name}.");
    }

    public function show(OnboardingChecklist $checklist)
    {
        $user = auth()->user();
        abort_if($checklist->organization_id !== $user->organization_id, 403);

        if (!$this->isHrOrAdmin() && $checklist->user_id !== $user->id) {
            abort(403);
        }

        $checklist->load(['employee', 'creator', 'tasks.assignedTo']);

        return view('hr-onboarding.show', compact('checklist'));
    }

    public function completeTask(Request $request, OnboardingTask $task)
    {
        $user      = auth()->user();
        $checklist = $task->checklist;
        abort_if($checklist->organization_id !== $user->organization_id, 403);

        $canComplete = $this->isHrOrAdmin()
            || $checklist->user_id === $user->id
            || $task->assigned_to_user === $user->id;

        abort_if(!$canComplete, 403);

        $task->update([
            'is_completed' => !$task->is_completed,
            'completed_at' => !$task->is_completed ? now() : null,
        ]);

        if ($checklist->tasks()->where('is_completed', false)->doesntExist()) {
            $checklist->update(['completed_at' => now()]);
        } else {
            $checklist->update(['completed_at' => null]);
        }

        return back()->with('success', $task->is_completed ? 'Task marked complete.' : 'Task marked incomplete.');
    }

    public function addTask(Request $request, OnboardingChecklist $checklist)
    {
        abort_if(!$this->isHrOrAdmin(), 403);
        abort_if($checklist->organization_id !== auth()->user()->organization_id, 403);

        $data = $request->validate(['title' => 'required|string|max:255']);

        $lastOrder = $checklist->tasks()->max('sort_order') ?? -1;

        OnboardingTask::create([
            'checklist_id' => $checklist->id,
            'title'        => $data['title'],
            'sort_order'   => $lastOrder + 1,
        ]);

        return back()->with('success', 'Task added.');
    }
}

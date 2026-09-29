<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        if ($user->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin'])) {
            $projects = \App\Models\Project::where('organization_id', $orgId)
                ->with(['tasks'])
                ->withCount('activities')
                ->orderByRaw("FIELD(status,'active','planning','on_hold','completed')")
                ->orderByDesc('created_at')
                ->get();

            return view('projects.index', compact('projects'))->with('viewMode', 'all');
        }

        if ($user->hasAnyRole(['team_lead', 'manager'])) {
            $ids = $user->manageableUserIds();

            $allProjectIds = \App\Models\Task::whereIn('assigned_to', $ids)
                ->whereNotNull('project_id')
                ->pluck('project_id')
                ->unique();

            $projects = \App\Models\Project::where('organization_id', $orgId)
                ->whereIn('id', $allProjectIds)
                ->with(['tasks'])
                ->withCount('activities')
                ->orderByRaw("FIELD(status,'active','planning','on_hold','completed')")
                ->orderByDesc('created_at')
                ->get();

            return view('projects.index', compact('projects'))->with('viewMode', 'managed');
        }

        $assignedProjectIds = \App\Models\Task::where('assigned_to', $user->id)
            ->whereNotNull('project_id')
            ->pluck('project_id')
            ->unique();

        $projects = \App\Models\Project::where('organization_id', $orgId)
            ->whereIn('id', $assignedProjectIds)
            ->with(['tasks'])
            ->withCount('activities')
            ->orderByRaw("FIELD(status,'active','planning','on_hold','completed')")
            ->orderByDesc('created_at')
            ->get();

        return view('projects.index', compact('projects'))->with('viewMode', 'assigned');
    }

    public function create(Request $request): View|RedirectResponse
    {
        if (!$request->user()->organization_id) {
            return redirect()->route('organization.create')
                ->with('warning', 'You need to create an organization before adding projects.');
        }

        return view('projects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        if (!$request->user()->organization_id) {
            return redirect()->route('organization.create')
                ->with('warning', 'You need to create an organization before adding projects.');
        }

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'github_owner' => ['nullable', 'string', 'max:255'],
            'github_repo'  => ['nullable', 'string', 'max:255'],
            'status'       => ['required', 'in:active,inactive,archived'],
        ]);

        $project = Project::create([
            ...$validated,
            'organization_id' => $request->user()->organization_id,
        ]);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    public function edit(Request $request, int $id): View
    {
        $project = Project::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->firstOrFail();

        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $project = Project::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->firstOrFail();

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'github_owner' => ['nullable', 'string', 'max:255'],
            'github_repo'  => ['nullable', 'string', 'max:255'],
            'status'       => ['required', 'in:active,inactive,archived'],
        ]);

        $project->update($validated);

        return redirect()->route('projects.show', $id)
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $project = Project::where('id', $id)
            ->where('organization_id', $request->user()->organization_id)
            ->firstOrFail();

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', "Project \"{$project->name}\" deleted.");
    }

    public function show(Request $request, int $id): View
    {
        $user    = auth()->user();
        $orgId   = $user->organization_id;
        $project = Project::where('organization_id', $orgId)->findOrFail($id);

        if (!$user->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin'])) {
            if ($user->hasAnyRole(['team_lead', 'manager'])) {
                $ids = $user->manageableUserIds();
                $hasTeamTask = \App\Models\Task::where('project_id', $project->id)
                    ->whereIn('assigned_to', $ids)
                    ->exists();
                abort_if(!$hasTeamTask, 403, 'Your team has no tasks in this project.');
            } else {
                $hasTask = \App\Models\Task::where('project_id', $project->id)
                    ->where('assigned_to', $user->id)
                    ->exists();
                abort_if(!$hasTask, 403, 'You have no tasks in this project.');
            }
        }

        $activities = $project->activities()
            ->with('user')
            ->orderByDesc('occurred_at')
            ->limit(50)
            ->get();

        $stats = [
            'commits' => $project->activities()->where('event_type', 'commit')->count(),
            'prs'     => $project->activities()->where('event_type', 'pull_request')->count(),
        ];

        return view('projects.show', compact('project', 'activities', 'stats'));
    }
}

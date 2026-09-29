<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Document;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $user  = auth()->user();
        $orgId = $user->organization_id;
        $results = [];

        // Employees
        $employees = User::where('organization_id', $orgId)
            ->where(function ($query) use ($q) {
                $query->where('name', 'LIKE', "%{$q}%")
                      ->orWhere('email', 'LIKE', "%{$q}%");
            })
            ->with(['employeeProfile', 'department'])
            ->whereDoesntHave('roles', fn($r) => $r->where('name', 'super_admin'))
            ->limit(5)
            ->get();

        foreach ($employees as $emp) {
            $subtitle = $emp->employeeProfile->designation ?? $emp->department->name ?? '';
            $results[] = [
                'type'     => 'employee',
                'icon'     => 'user',
                'title'    => $emp->name,
                'subtitle' => $subtitle,
                'url'      => route('directory.show', $emp->id),
            ];
        }

        // Announcements
        try {
            $announcements = Announcement::where('organization_id', $orgId)
                ->where('title', 'LIKE', "%{$q}%")
                ->limit(3)
                ->get();

            foreach ($announcements as $ann) {
                $results[] = [
                    'type'     => 'announcement',
                    'icon'     => 'speakerphone',
                    'title'    => $ann->title,
                    'subtitle' => 'Announcement · ' . $ann->created_at->diffForHumans(),
                    'url'      => route('announcements.index'),
                ];
            }
        } catch (\Throwable $e) {
            // Announcements search unavailable
        }

        // Documents
        try {
            $documents = Document::visibleTo($user)
                ->where('title', 'LIKE', "%{$q}%")
                ->limit(3)
                ->get();

            foreach ($documents as $doc) {
                $results[] = [
                    'type'     => 'document',
                    'icon'     => 'file',
                    'title'    => $doc->title,
                    'subtitle' => $doc->category_label . ' · ' . $doc->file_size_formatted,
                    'url'      => route('documents.show', $doc->id),
                ];
            }
        } catch (\Throwable $e) {
            // Documents search unavailable
        }

        // Projects
        try {
            $projects = Project::where('organization_id', $orgId)
                ->where('name', 'LIKE', "%{$q}%")
                ->limit(3)
                ->get();

            foreach ($projects as $project) {
                $results[] = [
                    'type'     => 'project',
                    'icon'     => 'folder',
                    'title'    => $project->name,
                    'subtitle' => 'Project',
                    'url'      => route('projects.show', $project->id),
                ];
            }
        } catch (\Throwable $e) {
            // Projects search unavailable
        }

        // Tasks
        try {
            $tasks = Task::whereHas('project', fn($q2) => $q2->where('organization_id', $orgId))
                ->where('title', 'LIKE', "%{$q}%")
                ->with('project')
                ->limit(3)
                ->get();

            foreach ($tasks as $task) {
                $results[] = [
                    'type'     => 'task',
                    'icon'     => 'check',
                    'title'    => $task->title,
                    'subtitle' => 'Task · ' . ($task->project->name ?? ''),
                    'url'      => route('tasks.index'),
                ];
            }
        } catch (\Throwable $e) {
            // Tasks search unavailable
        }

        return response()->json(['results' => $results, 'query' => $q]);
    }
}

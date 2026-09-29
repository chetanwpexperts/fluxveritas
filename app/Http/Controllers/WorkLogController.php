<?php

namespace App\Http\Controllers;

use App\Models\MetricEntry;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkLog;
use App\Models\WorkLogTemplate;
use App\Services\AI\AiEngine;
use App\Services\DesignationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkLogController extends Controller
{
    public function index(Request $request)
    {
        $user    = auth()->user();

        if ($user->hasAnyRole(['admin', 'owner', 'ceo', 'manager', 'team_lead'])) {
            return redirect()->route('worklog.team');
        }

        $orgId   = $user->organization_id;
        $perPage = (int) $request->get('per_page', 15);

        // Stats: un-paginated, no eager loads needed
        $allLogs = WorkLog::where('user_id', $user->id)
            ->where('log_date', '>=', now()->subDays(30))
            ->orderByDesc('log_date')
            ->orderByDesc('created_at')
            ->get();

        // AJAX: return paginated JSON for the JS history paginator
        if ($request->ajax()) {
            $logs = WorkLog::where('user_id', $user->id)
                ->where('log_date', '>=', now()->subDays(30))
                ->with(['project'])
                ->orderByDesc('log_date')
                ->orderByDesc('created_at')
                ->paginate($perPage);
            return response()->json($logs);
        }

        $totalMinutes = $allLogs->sum('duration_minutes');
        $totalLogs    = $allLogs->count();
        $avgOutput    = $allLogs->avg('output_value') ?? 0;
        $byCategory   = $allLogs->groupBy('category')
            ->map(fn($g) => ['count' => $g->count(), 'minutes' => $g->sum('duration_minutes')]);

        $weekStart   = now()->startOfWeek(Carbon::MONDAY);
        $weekLogs    = $allLogs->filter(fn($l) => $l->log_date->gte($weekStart));
        $weekMinutes = $weekLogs->sum('duration_minutes');
        $todayLogs   = $allLogs->filter(fn($l) => $l->log_date->isToday());

        // Paginated history for initial server render (page 1)
        $logs   = WorkLog::where('user_id', $user->id)
            ->where('log_date', '>=', now()->subDays(30))
            ->with(['user', 'project', 'department'])
            ->orderByDesc('log_date')
            ->orderByDesc('created_at')
            ->paginate($perPage);
        $byDate = collect($logs->items())->groupBy(fn($l) => $l->log_date->toDateString());

        $projects            = Project::where('organization_id', $orgId)->get(['id', 'name']);
        $dept                = $user->department;
        $workLogCategories   = DesignationService::getWorkLogCategories($user->designation);

        return view('worklog.index', compact(
            'logs', 'byDate', 'totalMinutes', 'totalLogs', 'avgOutput',
            'byCategory', 'weekLogs', 'weekMinutes', 'todayLogs', 'projects', 'dept',
            'workLogCategories'
        ));
    }

    public function todayLog(): View
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        $todayLogs    = WorkLog::where('user_id', $user->id)
            ->where('log_date', today())
            ->with('project')
            ->orderBy('created_at')
            ->get();

        $todayMinutes = $todayLogs->sum('duration_minutes');
        $todayOutput  = $todayLogs->avg('output_value') ?? 0;
        $projects     = Project::where('organization_id', $orgId)->get(['id', 'name']);
        $dept         = $user->department;

        return view('worklog.today', compact(
            'todayLogs', 'todayMinutes', 'todayOutput', 'projects', 'dept'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category'         => 'required|string',
            'title'            => 'required|string|min:3|max:255',
            'log_date'         => 'required|date',
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
            'output_value'     => 'nullable|integer|min:1|max:10',
            'project_id'       => 'nullable|exists:projects,id',
            'description'      => 'nullable|string|max:1000',
            'evidence'         => 'nullable|string|max:500',
            'is_billable'      => 'nullable|boolean',
        ]);

        $user = auth()->user();

        WorkLog::create([
            'user_id'          => $user->id,
            'organization_id'  => $user->organization_id,
            'department_id'    => $user->department_id,
            'project_id'       => $validated['project_id'] ?? null,
            'log_date'         => $validated['log_date'],
            'category'         => $validated['category'],
            'title'            => $validated['title'],
            'description'      => $validated['description'] ?? null,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'output_value'     => $validated['output_value'] ?? 5,
            'is_billable'      => $validated['is_billable'] ?? false,
            'evidence'         => $validated['evidence'] ?? null,
        ]);

        $redirect = $request->input('redirect', 'index');

        return redirect()->route($redirect === 'today' ? 'worklog.today' : 'worklog.index')
            ->with('success', 'Activity logged successfully.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $log = WorkLog::where('user_id', auth()->id())->findOrFail($id);

        $validated = $request->validate([
            'category'         => 'required|string',
            'title'            => 'required|string|min:3|max:255',
            'duration_minutes' => 'nullable|integer|min:1|max:1440',
            'output_value'     => 'nullable|integer|min:1|max:10',
            'description'      => 'nullable|string|max:1000',
            'evidence'         => 'nullable|string|max:500',
        ]);

        $log->update($validated);

        return back()->with('success', 'Log entry updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $log = WorkLog::where('user_id', auth()->id())->findOrFail($id);
        $log->delete();

        return back()->with('success', 'Log entry deleted.');
    }

    public function teamLogs(Request $request): View
    {
        $user = auth()->user();

        abort_if(!$user->hasAnyRole(['admin', 'owner', 'ceo', 'team_lead', 'manager', 'super_admin']), 403);

        $orgId = $user->organization_id;

        $dateFrom   = $request->date_from ? Carbon::parse($request->date_from) : now()->subDays(7);
        $dateTo     = $request->date_to   ? Carbon::parse($request->date_to)   : today();
        $filterDept = $request->department_id;
        $filterUser = $request->user_id;

        $isOrgWide = $user->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin']);

        if ($isOrgWide) {
            $query   = WorkLog::where('organization_id', $orgId);
            $members = User::where('organization_id', $orgId)->get(['id', 'name', 'email']);
        } else {
            $memberIds = $user->manageableUserIds();
            $query     = WorkLog::whereIn('user_id', $memberIds);
            $members   = User::whereIn('id', $memberIds)->get(['id', 'name', 'email']);
        }

        $query->whereBetween('log_date', [$dateFrom, $dateTo])
              ->with(['user', 'project', 'department']);

        if ($filterDept) $query->where('department_id', $filterDept);
        if ($filterUser) $query->where('user_id', $filterUser);

        $logs   = $query->orderByDesc('log_date')->orderByDesc('created_at')->get();
        $byUser = $logs->groupBy('user_id');

        $departments = \App\Models\Department::where('organization_id', $orgId)->get(['id', 'name']);

        return view('worklog.team', compact(
            'logs', 'byUser', 'departments', 'members',
            'dateFrom', 'dateTo', 'filterDept', 'filterUser'
        ));
    }

    public function team(Request $request)
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        abort_if(!$user->hasAnyRole(['admin', 'owner', 'ceo', 'team_lead', 'manager', 'super_admin']), 403);

        $date      = $request->get('date', today()->toDateString());
        $isOrgWide = $user->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin']);

        if ($isOrgWide) {
            $members = User::where('organization_id', $orgId)
                ->where('is_active', true)
                ->with(['workLogs' => fn($q) => $q->where('log_date', $date)])
                ->orderBy('name')
                ->get();

            $totalHours = round(
                WorkLog::where('organization_id', $orgId)->where('log_date', $date)->sum('duration_minutes') / 60, 1
            );
            $avgQuality = round(
                WorkLog::where('organization_id', $orgId)->where('log_date', $date)->avg('output_value') ?? 0, 1
            );
        } else {
            $memberIds = $user->manageableUserIds();

            $members = User::whereIn('id', $memberIds)
                ->where('is_active', true)
                ->with(['workLogs' => fn($q) => $q->where('log_date', $date)])
                ->orderBy('name')
                ->get();

            $totalHours = round(
                WorkLog::whereIn('user_id', $memberIds)->where('log_date', $date)->sum('duration_minutes') / 60, 1
            );
            $avgQuality = round(
                WorkLog::whereIn('user_id', $memberIds)->where('log_date', $date)->avg('output_value') ?? 0, 1
            );
        }

        $totalLogged = $members->filter(fn($m) => $m->workLogs->count() > 0)->count();
        $notLogged   = $members->filter(fn($m) => $m->workLogs->count() === 0);

        return view('worklog.team', compact(
            'members', 'date', 'totalLogged', 'totalHours', 'avgQuality', 'notLogged'
        ));
    }

    public function generateDailySummary(Request $request): JsonResponse
    {
        $request->validate(['date' => 'required|date']);

        $user = auth()->user();
        $logs = WorkLog::where('user_id', $user->id)
            ->where('log_date', $request->date)
            ->get();

        if ($logs->isEmpty()) {
            return response()->json(['summary' => 'No activities logged for this date.']);
        }

        $totalHours = round($logs->sum('duration_minutes') / 60, 1);
        $categories = $logs->groupBy('category')->map->count();
        $avgOutput  = round($logs->avg('output_value'), 1);
        $titles     = $logs->pluck('title')->implode(', ');

        try {
            $engine  = new AiEngine($user->organization_id);
            $context = [
                'date'          => $request->date,
                'employee_name' => $user->name,
                'total_hours'   => $totalHours,
                'avg_output'    => $avgOutput,
                'activities'    => $titles,
                'categories'    => $categories->toArray(),
                'log_count'     => $logs->count(),
            ];
            $summary = $engine->answerQuery(
                "Summarize this employee's work day in 2-3 sentences based on: " . json_encode($context),
                $context
            );
        } catch (\Exception $e) {
            $summary = "Logged {$logs->count()} activities totaling {$totalHours}h with avg output score of {$avgOutput}/10. Activities: {$titles}.";
        }

        return response()->json(['summary' => $summary]);
    }

    public function addMetricEntry(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'metric_id'  => 'required|exists:department_metrics,id',
            'value'      => 'required|numeric|min:0',
            'entry_date' => 'required|date',
            'notes'      => 'nullable|string|max:500',
        ]);

        $user = auth()->user();

        MetricEntry::create([
            'user_id'         => $user->id,
            'organization_id' => $user->organization_id,
            'department_id'   => $user->department_id,
            'metric_id'       => $validated['metric_id'],
            'value'           => $validated['value'],
            'entry_date'      => $validated['entry_date'],
            'notes'           => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Metric entry recorded.');
    }

    // ─── NEW: Quick Log (AI-powered) ─────────────────────────────────────────

    public function getUserTasks(): JsonResponse
    {
        $tasks = Task::where('assigned_to', auth()->id())
            ->whereNotIn('status', ['done', 'cancelled', 'archived'])
            ->orderBy('created_at', 'desc')
            ->get(['id', 'ticket_number', 'title', 'status']);

        return response()->json($tasks);
    }

    public function quickLog(Request $request): JsonResponse
    {
        $request->validate([
            'text'             => 'required|string|max:1000',
            'log_date'         => 'required|date',
            'duration_minutes' => 'required|integer|min:15|max:480',
            'output_value'     => 'required|integer|min:1|max:10',
            'task_id'          => 'nullable|exists:tasks,id',
        ]);

        $prompt = "Parse this work log entry and return JSON only with these exact fields: category, title, description.\n\n"
            . "Available categories: meeting, code_review, development, research, support, training, travel, client_call, vendor_call, planning, documentation, design, testing, reporting, recruitment, other\n\n"
            . "Work log text: {$request->text}\n\n"
            . "Return ONLY valid JSON with lowercase_underscore category values:\n"
            . '{"category":"development","title":"Fixed login bug and wrote unit tests","description":"Resolved authentication issue"}';

        $parsed = $this->parseWithAI($prompt);

        if (!$parsed) {
            $parsed = [
                'category'    => 'other',
                'title'       => substr($request->text, 0, 100),
                'description' => $request->text,
            ];
        }

        WorkLog::create([
            'user_id'          => auth()->id(),
            'organization_id'  => auth()->user()->organization_id,
            'department_id'    => auth()->user()->department_id,
            'log_date'         => $request->log_date,
            'category'         => $parsed['category'],
            'title'            => $parsed['title'],
            'description'      => $parsed['description'],
            'duration_minutes' => $request->duration_minutes,
            'output_value'     => $request->output_value,
            'is_billable'      => 0,
            'task_id'          => $request->task_id ?: null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Work logged successfully!',
            'parsed'  => $parsed,
        ]);
    }

    // ─── NEW: Bulk Store (Weekly Grid) ───────────────────────────────────────

    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'logs'                       => 'required|array|min:1',
            'logs.*.log_date'            => 'required|date',
            'logs.*.category'            => 'required|string',
            'logs.*.title'               => 'required|string|max:255',
            'logs.*.duration_minutes'    => 'required|integer|min:15',
            'logs.*.output_value'        => 'required|integer|min:1|max:10',
            'logs.*.task_id'             => 'nullable|exists:tasks,id',
        ]);

        $saved = 0;
        foreach ($request->logs as $log) {
            if (empty(trim($log['title']))) continue;
            WorkLog::create([
                'user_id'          => auth()->id(),
                'organization_id'  => auth()->user()->organization_id,
                'department_id'    => auth()->user()->department_id,
                'log_date'         => $log['log_date'],
                'category'         => $log['category'],
                'title'            => $log['title'],
                'description'      => $log['description'] ?? null,
                'duration_minutes' => $log['duration_minutes'],
                'output_value'     => $log['output_value'],
                'is_billable'      => 0,
                'task_id'          => $log['task_id'] ?? null,
            ]);
            $saved++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$saved} log" . ($saved !== 1 ? 's' : '') . " saved successfully!",
        ]);
    }

    // ─── NEW: Get logs for a week (Weekly Grid navigation) ───────────────────

    public function getWeekLogs(Request $request): JsonResponse
    {
        $request->validate(['week_start' => 'required|date']);

        $start = Carbon::parse($request->week_start)->startOfWeek(Carbon::MONDAY);
        $end   = $start->copy()->endOfWeek(Carbon::SUNDAY);

        $logs = WorkLog::where('user_id', auth()->id())
            ->whereBetween('log_date', [$start, $end])
            ->orderBy('log_date')
            ->orderBy('created_at')
            ->get(['id', 'log_date', 'category', 'title', 'duration_minutes', 'output_value']);

        return response()->json($logs->map(function ($l) {
            return [
                'id'               => $l->id,
                'log_date'         => $l->log_date->toDateString(),
                'category'         => $l->category,
                'title'            => $l->title,
                'duration_minutes' => $l->duration_minutes,
                'output_value'     => $l->output_value,
            ];
        }));
    }

    // ─── NEW: Yesterday's log (Copy Yesterday) ───────────────────────────────

    public function getYesterdayLog(): JsonResponse
    {
        $yesterday = now()->subDay()->toDateString();
        $log = WorkLog::where('user_id', auth()->id())
            ->where('log_date', $yesterday)
            ->latest()
            ->first();
        return response()->json($log);
    }

    // ─── NEW: Templates ──────────────────────────────────────────────────────

    public function getTemplates(): JsonResponse
    {
        $templates = WorkLogTemplate::where('user_id', auth()->id())
            ->orderBy('usage_count', 'desc')
            ->get();
        return response()->json($templates);
    }

    public function saveTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'category'         => 'required|string',
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'duration_minutes' => 'nullable|integer|min:15|max:480',
            'output_value'     => 'nullable|integer|min:1|max:10',
        ]);

        $template = WorkLogTemplate::create([
            'user_id'          => auth()->id(),
            'organization_id'  => auth()->user()->organization_id,
            'name'             => $request->name,
            'category'         => $request->category,
            'title'            => $request->title,
            'description'      => $request->description,
            'duration_minutes' => $request->duration_minutes,
            'output_value'     => $request->output_value ?? 5,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Template saved!',
            'template' => $template,
        ]);
    }

    public function deleteTemplate(int $id): JsonResponse
    {
        WorkLogTemplate::where('id', $id)
            ->where('user_id', auth()->id())
            ->delete();
        return response()->json(['success' => true]);
    }

    public function useTemplate(int $id): JsonResponse
    {
        WorkLogTemplate::where('id', $id)
            ->where('user_id', auth()->id())
            ->increment('usage_count');

        $template = WorkLogTemplate::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        return response()->json($template);
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function parseWithAI(string $prompt): ?array
    {
        $validCategories = [
            'meeting', 'code_review', 'development', 'research', 'support',
            'training', 'travel', 'client_call', 'vendor_call', 'planning',
            'documentation', 'design', 'testing', 'reporting', 'recruitment', 'other',
        ];

        try {
            $engine   = new AiEngine(auth()->user()->organization_id);
            $response = $engine->answerQuery('', ['full_prompt' => $prompt]);

            preg_match('/\{[^{}]+\}/s', $response, $matches);
            if (!empty($matches[0])) {
                $data = json_decode($matches[0], true);
                if (json_last_error() === JSON_ERROR_NONE && isset($data['category'], $data['title'])) {
                    $category = strtolower(str_replace([' ', '-'], '_', $data['category']));
                    if (!in_array($category, $validCategories)) {
                        $category = 'other';
                    }
                    return [
                        'category'    => $category,
                        'title'       => substr((string) $data['title'], 0, 255),
                        'description' => isset($data['description']) ? substr((string) $data['description'], 0, 1000) : null,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Fall through to return null
        }

        return null;
    }
}

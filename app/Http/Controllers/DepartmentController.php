<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentMetric;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(): View
    {
        $orgId = auth()->user()->organization_id;

        $departments = Department::where('organization_id', $orgId)
            ->with(['users', 'head'])
            ->withCount('users')
            ->get()
            ->map(function ($dept) {
                $memberIds  = $dept->users->pluck('id');
                $todayLogs  = WorkLog::whereIn('user_id', $memberIds)
                    ->where('log_date', today())
                    ->count();

                return [
                    'dept'       => $dept,
                    'today_logs' => $todayLogs,
                ];
            });

        return view('departments.index', compact('departments'));
    }

    public function create(): View
    {
        $orgId   = auth()->user()->organization_id;
        $members = User::where('organization_id', $orgId)->get(['id', 'name', 'email']);

        return view('departments.create', compact('members'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'type'         => 'required|in:tech,sales,hr,finance,operations,design,marketing,other',
            'work_mode'    => 'required|in:github,manual,hybrid',
            'color'        => 'nullable|string|max:20',
            'description'  => 'nullable|string|max:500',
            'head_user_id' => 'nullable|exists:users,id',
        ]);

        $orgId = auth()->user()->organization_id;
        $slug  = Str::slug($validated['name']);

        // Ensure unique slug within org
        $baseSlug = $slug;
        $count = 1;
        while (Department::where('organization_id', $orgId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $count++;
        }

        $dept = Department::create([
            'organization_id' => $orgId,
            'name'            => $validated['name'],
            'slug'            => $slug,
            'type'            => $validated['type'],
            'work_mode'       => $validated['work_mode'],
            'color'           => $validated['color'] ?? '#18181b',
            'description'     => $validated['description'] ?? null,
            'head_user_id'    => $validated['head_user_id'] ?? null,
        ]);

        $this->createDefaultMetrics($dept);

        return redirect()->route('departments.show', $dept->id)
            ->with('success', 'Department created successfully.');
    }

    public function show(int $id): View
    {
        $orgId = auth()->user()->organization_id;

        $dept = Department::where('organization_id', $orgId)
            ->with(['users', 'head', 'metrics'])
            ->findOrFail($id);

        $memberIds = $dept->users->pluck('id');

        $recentLogs = WorkLog::whereIn('user_id', $memberIds)
            ->with('user')
            ->orderByDesc('log_date')
            ->orderByDesc('created_at')
            ->take(10)
            ->get();

        $weekStart  = now()->startOfWeek();
        $weekHours  = WorkLog::whereIn('user_id', $memberIds)
            ->where('log_date', '>=', $weekStart)
            ->sum('duration_minutes');

        $todayLogs = WorkLog::whereIn('user_id', $memberIds)
            ->where('log_date', today())
            ->count();

        $avgOutput = WorkLog::whereIn('user_id', $memberIds)
            ->where('log_date', '>=', $weekStart)
            ->avg('output_value') ?? 0;

        $metricTotals = [];
        foreach ($dept->metrics->where('is_active', true) as $metric) {
            $total = \App\Models\MetricEntry::where('metric_id', $metric->id)
                ->where('entry_date', '>=', $weekStart)
                ->sum('value');
            $metricTotals[$metric->id] = $total;
        }

        $nonMembers = User::where('organization_id', $orgId)
            ->where(function ($q) use ($id) {
                $q->whereNull('department_id')->orWhere('department_id', '!=', $id);
            })
            ->get(['id', 'name', 'email', 'role']);

        return view('departments.show', compact(
            'dept', 'recentLogs', 'weekHours', 'todayLogs',
            'avgOutput', 'metricTotals', 'nonMembers'
        ));
    }

    public function edit(int $id): View
    {
        $orgId   = auth()->user()->organization_id;
        $dept    = Department::where('organization_id', $orgId)->findOrFail($id);
        $members = User::where('organization_id', $orgId)->get(['id', 'name', 'email']);

        return view('departments.edit', compact('dept', 'members'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $orgId = auth()->user()->organization_id;
        $dept  = Department::where('organization_id', $orgId)->findOrFail($id);

        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'type'         => 'required|in:tech,sales,hr,finance,operations,design,marketing,other',
            'work_mode'    => 'required|in:github,manual,hybrid',
            'color'        => 'nullable|string|max:20',
            'description'  => 'nullable|string|max:500',
            'head_user_id' => 'nullable|exists:users,id',
        ]);

        $dept->update($validated);

        return redirect()->route('departments.show', $dept->id)
            ->with('success', 'Department updated.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $orgId = auth()->user()->organization_id;
        $dept  = Department::where('organization_id', $orgId)->findOrFail($id);

        if ($dept->users()->count() > 0) {
            return back()->withErrors(['dept' => 'Cannot delete department with active members. Remove all members first.']);
        }

        $dept->delete();

        return redirect()->route('departments.index')->with('success', 'Department deleted.');
    }

    public function assignMember(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'user_id'       => 'required|exists:users,id',
        ]);

        $orgId = auth()->user()->organization_id;
        $dept  = Department::where('organization_id', $orgId)->findOrFail($validated['department_id']);
        $user  = User::where('organization_id', $orgId)->findOrFail($validated['user_id']);

        $user->update(['department_id' => $dept->id]);

        return back()->with('success', $user->name . ' added to ' . $dept->name . '.');
    }

    public function removeMember(Request $request, int $id, int $userId): RedirectResponse
    {
        $orgId = auth()->user()->organization_id;
        Department::where('organization_id', $orgId)->findOrFail($id);
        $user = User::where('organization_id', $orgId)->findOrFail($userId);

        $user->update(['department_id' => null]);

        return back()->with('success', $user->name . ' removed from department.');
    }

    private function createDefaultMetrics(Department $dept): void
    {
        $defaults = match($dept->type) {
            'tech'       => [
                ['bugs_fixed',         'Bugs Fixed',          'count'],
                ['features_completed', 'Features Completed',  'count'],
                ['code_reviews',       'Code Reviews Done',   'count'],
            ],
            'sales'      => [
                ['calls_made',         'Calls Made',          'count'],
                ['meetings_held',      'Meetings Held',       'count'],
                ['deals_closed',       'Deals Closed',        'count'],
                ['revenue_generated',  'Revenue Generated',   'currency'],
            ],
            'hr'         => [
                ['interviews_conducted','Interviews Done',    'count'],
                ['positions_filled',   'Positions Filled',    'count'],
                ['training_sessions',  'Training Sessions',   'count'],
            ],
            'finance'    => [
                ['reports_prepared',   'Reports Prepared',    'count'],
                ['invoices_processed', 'Invoices Processed',  'count'],
                ['reconciliations',    'Reconciliations Done','count'],
            ],
            'operations' => [
                ['site_visits',        'Site Visits',         'count'],
                ['issues_resolved',    'Issues Resolved',     'count'],
                ['vendor_meetings',    'Vendor Meetings',     'count'],
            ],
            default      => [],
        };

        foreach ($defaults as [$name, $label, $type]) {
            DepartmentMetric::create([
                'department_id'   => $dept->id,
                'organization_id' => $dept->organization_id,
                'metric_name'     => $name,
                'metric_label'    => $label,
                'metric_type'     => $type,
            ]);
        }
    }
}

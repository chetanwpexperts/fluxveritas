<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;

class OrgChartController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        $tree = $this->buildTree($orgId, $user);

        $allUsers = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'))
            ->with(['reportingManager', 'department'])
            ->get(['id', 'name', 'job_title',
                   'designation', 'seniority_level',
                   'department_id', 'reporting_manager_id',
                   'work_location', 'profile_photo'])
            ->map(fn ($u) => [
                'id'              => $u->id,
                'name'            => $u->name,
                'job_title'       => $u->job_title ?? 'Team Member',
                'designation'     => $u->designation,
                'seniority_level' => $u->seniority_level,
                'department'      => $u->getRelation('department')?->name,
                'manager'         => $u->reportingManager?->name,
                'work_location'   => $u->work_location,
                'initials'        => $this->initials($u->name),
            ]);

        $departments = Department::where('organization_id', $orgId)
            ->where('is_active', true)
            ->withCount('users')
            ->get();

        $stats = [
            'total'       => User::where('organization_id', $orgId)->where('is_active', true)->count(),
            'no_manager'  => User::where('organization_id', $orgId)->where('is_active', true)->whereNull('reporting_manager_id')->count(),
            'departments' => $departments->count(),
            'locations'   => User::where('organization_id', $orgId)->where('is_active', true)->distinct('work_location')->count('work_location'),
        ];

        return view('org-chart.index', compact('tree', 'allUsers', 'departments', 'stats'));
    }

    /* ===== AJAX: direct reports list ===== */
    public function directReports(int $userId)
    {
        $orgId   = auth()->user()->organization_id;
        $reports = User::where('organization_id', $orgId)
            ->where('reporting_manager_id', $userId)
            ->where('is_active', true)
            ->with(['department', 'directReports'])
            ->get()
            ->map(fn ($u) => [
                'id'              => $u->id,
                'name'            => $u->name,
                'job_title'       => $u->job_title,
                'designation'     => $u->designation,
                'seniority_level' => $u->seniority_level,
                'department'      => $u->getRelation('department')?->name,
                'reports_count'   => $u->directReports->count(),
            ]);

        return response()->json($reports);
    }

    /* ===== AJAX: update reporting manager ===== */
    public function updateManager(Request $request, int $userId)
    {
        $orgId = auth()->user()->organization_id;

        $request->validate([
            'reporting_manager_id' => 'nullable|exists:users,id',
        ]);

        $user = User::where('organization_id', $orgId)->findOrFail($userId);

        if ($request->reporting_manager_id) {
            if ($this->wouldCreateCircle($userId, $request->reporting_manager_id, $orgId)) {
                return response()->json(['success' => false, 'message' => 'Cannot create circular hierarchy.'], 422);
            }
        }

        $user->update(['reporting_manager_id' => $request->reporting_manager_id]);

        return response()->json(['success' => true, 'message' => 'Reporting manager updated.']);
    }

    /* ===== Tree building ===== */

    private function buildTree(int $orgId, User $authUser): array
    {
        $users = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'))
            ->with(['department', 'directReports'])
            ->get();

        $roots = $users->filter(function ($u) use ($users) {
            return is_null($u->reporting_manager_id) ||
                !$users->contains('id', $u->reporting_manager_id);
        });

        return $roots->map(fn ($u) => $this->buildNode($u, $users))->values()->toArray();
    }

    private function buildNode(User $user, $allUsers): array
    {
        $reports = $allUsers->where('reporting_manager_id', $user->id);

        return [
            'id'              => $user->id,
            'name'            => $user->name,
            'job_title'       => $user->job_title ?? 'Team Member',
            'designation'     => $user->designation,
            'seniority_level' => $user->seniority_level,
            'department'      => $user->getRelation('department')?->name,
            'work_location'   => $user->work_location,
            'is_active'       => $user->is_active,
            'initials'        => $this->initials($user->name),
            'reports_count'   => $reports->count(),
            'children'        => $reports->map(fn ($r) => $this->buildNode($r, $allUsers))->values()->toArray(),
        ];
    }

    private function initials(string $name): string
    {
        $parts = explode(' ', trim($name));
        $first = strtoupper(substr($parts[0] ?? '', 0, 1));
        $last  = strtoupper(substr($parts[1] ?? '', 0, 1));
        return $first . $last;
    }

    private function wouldCreateCircle(int $userId, int $managerId, int $orgId): bool
    {
        $current = $managerId;
        $visited = [];

        while ($current) {
            if (in_array($current, $visited)) break;
            if ($current === $userId) return true;
            $visited[] = $current;

            $u       = User::where('organization_id', $orgId)->find($current);
            $current = $u?->reporting_manager_id;
        }

        return false;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Task;
use App\Models\User;
use App\Models\Organization;
use App\Services\BillingService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function index()
    {
        $org = auth()->user()->organization;

        $pendingCount = User::where('onboarding_status', 'pending')->count();

        $stats = [
            'total_users'       => User::where('organization_id', $org->id)->count(),
            'total_roles'       => Role::count(),
            'total_permissions' => Permission::count(),
            'pending_users'     => $pendingCount,
        ];

        $recentUsers = User::where('organization_id', $org->id)
            ->with('roles')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return redirect()->route('settings.index');
    }

    public function users(Request $request)
    {
        $org = auth()->user()->organization;

        if ($request->ajax()) {
            $perPage = (int) $request->get('per_page', 15);
            $users   = User::where('organization_id', $org->id)
                ->with(['roles', 'permissions', 'department', 'reportingManager'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage)
                ->through(function ($user) {
                    return [
                        'id'                 => $user->id,
                        'name'               => $user->name,
                        'email'              => $user->email,
                        'avatar'             => strtoupper(substr($user->name, 0, 2)),
                        'roles'              => $user->roles->map(fn($r) => ['name' => $r->name]),
                        'direct_permissions' => $user->getDirectPermissions()->pluck('name'),
                        'is_active'          => $user->is_active,
                        'github_username'    => $user->github_username,
                        'job_title'          => $user->job_title,
                        'designation'        => $user->designation,
                        'seniority_level'    => $user->seniority_level,
                        'employment_type'    => $user->employment_type,
                        'department'         => $user->getRelation('department') ? ['name' => $user->getRelation('department')->name] : null,
                        'reporting_manager'  => $user->getRelation('reportingManager') ? ['name' => $user->getRelation('reportingManager')->name] : null,
                        'created_at'         => optional($user->created_at)->toDateString(),
                        'is_self'            => $user->id === auth()->id(),
                    ];
                });
            return response()->json($users);
        }

        $users = User::where('organization_id', $org->id)
            ->with(['roles', 'permissions', 'department', 'reportingManager'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id'                 => $user->id,
                    'name'               => $user->name,
                    'email'              => $user->email,
                    'avatar'             => strtoupper(substr($user->name, 0, 2)),
                    'roles'              => $user->roles->pluck('name'),
                    'direct_permissions' => $user->getDirectPermissions()->pluck('name'),
                    'is_active'          => $user->is_active,
                    'github_username'    => $user->github_username,
                    'job_title'          => $user->job_title,
                    'designation'        => $user->designation,
                    'seniority_level'    => $user->seniority_level,
                    'employment_type'    => $user->employment_type,
                    'department'         => $user->getRelation('department')?->name,
                    'reporting_manager'  => $user->getRelation('reportingManager')?->name,
                    'created_at'         => $user->created_at,
                    'is_self'            => $user->id === auth()->id(),
                    'has_super_admin'    => $user->hasRole('super_admin'),
                ];
            });

        $roles = Role::whereNotIn('name', ['super_admin'])->get();

        return view('admin.users', compact('users', 'roles'));
    }

    public function updateUserRole(Request $request, int $userId)
    {
        $request->validate(['role' => 'required|exists:roles,name']);

        $user = User::where('organization_id', auth()->user()->organization_id)
            ->findOrFail($userId);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot change your own role.');
        }

        if ($request->role === 'super_admin') {
            return back()->with('error', 'Super Admin role cannot be assigned here.');
        }

        (new PermissionService())->assignRole($user, $request->role);

        return back()->with('success', "{$user->name}'s role updated to {$request->role}.");
    }

    public function toggleUserStatus(int $userId)
    {
        $user = User::where('organization_id', auth()->user()->organization_id)
            ->findOrFail($userId);

        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate yourself.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "{$user->name} has been {$status}.");
    }

    public function roles()
    {
        $orgId = auth()->user()->organization_id;

        $roles = Role::with('permissions')->get()->map(function ($role) use ($orgId) {
            return [
                'id'                => $role->id,
                'name'              => $role->name,
                'label'             => ucwords(str_replace('_', ' ', $role->name)),
                'permissions_count' => $role->permissions->count(),
                'permissions'       => $role->permissions->pluck('name'),
                'users_count'       => User::role($role->name)->where('organization_id', $orgId)->count(),
            ];
        });

        $allPermissions = Permission::all()
            ->groupBy(function ($p) {
                $parts = explode('_', $p->name);
                // Use the first word as the category key
                return $parts[0];
            })
            ->map(fn ($group) => $group->pluck('name'));

        return view('admin.roles', compact('roles', 'allPermissions'));
    }

    public function permissions(Request $request)
    {
        $orgId = auth()->user()->organization_id;

        $users = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->with('roles')
            ->orderBy('name')
            ->get();

        $selectedUser = null;
        if ($request->has('user_id')) {
            $selectedUser = User::where('organization_id', $orgId)
                ->with('roles')
                ->findOrFail($request->user_id);
        }

        return view('admin.permissions', compact('users', 'selectedUser'));
    }

    public function givePermission(Request $request, int $userId)
    {
        $request->validate(['permission' => 'required|exists:permissions,name']);

        $user = User::where('organization_id', auth()->user()->organization_id)
            ->findOrFail($userId);

        $user->givePermissionTo($request->permission);

        return back()->with('success', "Permission granted to {$user->name}.");
    }

    public function revokePermission(Request $request, int $userId)
    {
        $request->validate(['permission' => 'required|exists:permissions,name']);

        $user = User::where('organization_id', auth()->user()->organization_id)
            ->findOrFail($userId);

        $user->revokePermissionTo($request->permission);

        return back()->with('success', "Permission revoked from {$user->name}.");
    }

    /**
     * Pending sign-ups this admin may act on: people already in their organization,
     * plus sign-ups not yet attached to any organization. super_admin sees all.
     */
    private function pendingQuery()
    {
        $me = auth()->user();

        return User::where('onboarding_status', 'pending')
            ->when(!$me->hasRole('super_admin'), fn ($q) => $q->where(fn ($q) => $q
                ->where('organization_id', $me->organization_id)
                ->orWhereNull('organization_id')));
    }

    /** Roles an admin can grant when approving (never super_admin). */
    private const APPROVABLE_EXCLUDED_ROLES = ['super_admin', 'viewer'];

    public function pendingUsers()
    {
        $pendingUsers = $this->pendingQuery()
            ->orderBy('created_at', 'desc')
            ->get();

        $roles = Role::whereNotIn('name', self::APPROVABLE_EXCLUDED_ROLES)->get();

        return view('admin.pending-users', compact('pendingUsers', 'roles'));
    }

    public function approveUser(Request $request, int $userId)
    {
        $allowedRoles = Role::whereNotIn('name', self::APPROVABLE_EXCLUDED_ROLES)->pluck('name')->all();
        $request->validate(['role' => ['required', \Illuminate\Validation\Rule::in($allowedRoles)]]);

        $user = $this->pendingQuery()->findOrFail($userId);

        if ($limitError = app(BillingService::class)->seatLimitError(auth()->user()->organization)) {
            return back()->with('error', $limitError);
        }

        if (!$user->organization_id) {
            $user->organization_id = auth()->user()->organization_id;
        }

        $user->update([
            'onboarding_status' => 'active',
            'approved_at'       => now(),
            'approved_by'       => auth()->id(),
        ]);

        (new PermissionService())->assignRole($user, $request->role);

        return back()->with('success', "{$user->name} has been approved with the {$request->role} role.");
    }

    public function rejectUser(Request $request, int $userId)
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $user = $this->pendingQuery()->findOrFail($userId);

        $user->update([
            'onboarding_status' => 'rejected',
            'rejection_reason'  => $request->rejection_reason,
        ]);

        return back()->with('success', "{$user->name}'s account has been rejected.");
    }

    public function deactivateUser(Request $request, int $id)
    {
        $orgId = auth()->user()->organization_id;
        $user  = User::where('organization_id', $orgId)->findOrFail($id);

        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return back()->with('error', 'Cannot deactivate admin accounts.');
        }

        $user->update(['is_active' => false]);
        return back()->with('success', "{$user->name} has been deactivated.");
    }

    public function activateUser(Request $request, int $id)
    {
        $orgId = auth()->user()->organization_id;
        $user  = User::where('organization_id', $orgId)->findOrFail($id);
        $user->update(['is_active' => true]);
        return back()->with('success', "{$user->name} has been activated.");
    }

    /* ===== CREATE USER ===== */

    public function createUser()
    {
        $orgId        = auth()->user()->organization_id;
        $roles        = Role::whereNotIn('name', ['super_admin'])->get();
        $departments  = Department::where('organization_id', $orgId)->where('is_active', true)->get();
        $designations = Designation::forOrganization($orgId);
        $managers     = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'ceo', 'team_lead']))
            ->get(['id', 'name', 'job_title']);

        return view('admin.users.create', compact('roles', 'departments', 'designations', 'managers'));
    }

    public function storeUser(Request $request)
    {
        $orgId = auth()->user()->organization_id;

        if ($limitError = app(BillingService::class)->seatLimitError(Organization::find($orgId))) {
            return back()->withErrors(['email' => $limitError])->withInput();
        }

        $request->validate([
            'name'                 => 'required|string|max:255',
            'email'                => 'required|email|unique:users,email',
            'password'             => 'required|min:8|confirmed',
            'role'                 => 'required|string',
            'department_id'        => 'nullable|exists:departments,id',
            'job_title'            => 'nullable|string|max:255',
            'designation'          => 'nullable|string|max:255',
            'seniority_level'      => 'nullable|string',
            'employment_type'      => 'required|string',
            'reporting_manager_id' => 'nullable|exists:users,id',
            'work_location'        => 'nullable|string',
            'phone'                => 'nullable|string|max:20',
            'join_date'            => 'nullable|date',
            'github_username'      => 'nullable|string|max:255',
        ]);

        $skills = null;
        if ($request->filled('skills_input')) {
            $skills = array_values(array_filter(array_map('trim', explode(',', $request->skills_input))));
        }

        $user = User::create([
            'name'                 => $request->name,
            'email'                => $request->email,
            'password'             => bcrypt($request->password),
            'organization_id'      => $orgId,
            'department_id'        => $request->department_id,
            'job_title'            => $request->job_title,
            'designation'          => $request->designation,
            'seniority_level'      => $request->seniority_level,
            'employment_type'      => $request->employment_type ?? 'full_time',
            'reporting_manager_id' => $request->reporting_manager_id,
            'work_location'        => $request->work_location ?? 'onsite',
            'phone'                => $request->phone,
            'join_date'            => $request->join_date,
            'github_username'      => $request->github_username,
            'skills'               => $skills,
            'is_active'            => true,
            'onboarding_status'    => 'active',
        ]);

        $user->assignRole($request->role);

        return redirect()->route('admin.users')
            ->with('success', "User {$user->name} created successfully.");
    }

    /* ===== EDIT USER ===== */

    public function editUser(int $id)
    {
        $orgId        = auth()->user()->organization_id;
        $user         = User::where('organization_id', $orgId)->with('roles')->findOrFail($id);
        $roles        = Role::whereNotIn('name', ['super_admin'])->get();
        $departments  = Department::where('organization_id', $orgId)->where('is_active', true)->get();
        $designations = Designation::forOrganization($orgId);
        $managers     = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->where('id', '!=', $id)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'ceo', 'team_lead']))
            ->get(['id', 'name', 'job_title']);

        return view('admin.users.edit', compact('user', 'roles', 'departments', 'designations', 'managers'));
    }

    public function updateUser(Request $request, int $id)
    {
        $orgId = auth()->user()->organization_id;
        $user  = User::where('organization_id', $orgId)->findOrFail($id);

        $request->validate([
            'name'                 => 'required|string|max:255',
            'email'                => 'required|email|unique:users,email,' . $id,
            'role'                 => 'required|string',
            'department_id'        => 'nullable|exists:departments,id',
            'job_title'            => 'nullable|string|max:255',
            'designation'          => 'nullable|string|max:255',
            'seniority_level'      => 'nullable|string',
            'employment_type'      => 'required|string',
            'reporting_manager_id' => 'nullable|exists:users,id',
            'work_location'        => 'nullable|string',
            'phone'                => 'nullable|string|max:20',
            'join_date'            => 'nullable|date',
            'github_username'      => 'nullable|string|max:255',
        ]);

        $skills = null;
        if ($request->filled('skills_input')) {
            $skills = array_values(array_filter(array_map('trim', explode(',', $request->skills_input))));
        }

        $user->update([
            'name'                 => $request->name,
            'email'                => $request->email,
            'department_id'        => $request->department_id,
            'job_title'            => $request->job_title,
            'designation'          => $request->designation,
            'seniority_level'      => $request->seniority_level,
            'employment_type'      => $request->employment_type,
            'reporting_manager_id' => $request->reporting_manager_id,
            'work_location'        => $request->work_location,
            'phone'                => $request->phone,
            'join_date'            => $request->join_date,
            'github_username'      => $request->github_username,
            'skills'               => $skills ?? $user->skills,
        ]);

        $user->syncRoles([$request->role]);

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:8|confirmed']);
            $user->update(['password' => bcrypt($request->password)]);
        }

        return redirect()->route('admin.users')
            ->with('success', "User {$user->name} updated successfully.");
    }

    /* ===== DELETE USER ===== */

    public function deleteUser(int $id)
    {
        $orgId = auth()->user()->organization_id;
        $user  = User::where('organization_id', $orgId)->findOrFail($id);

        if ($user->hasRole('super_admin')) {
            return back()->with('error', 'Cannot delete Super Admin.');
        }
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Cannot delete your own account.');
        }

        $userName = $user->name;
        Task::where('assigned_to', $user->id)->update(['assigned_to' => null]);
        User::where('reporting_manager_id', $user->id)->update(['reporting_manager_id' => null]);
        $user->delete();

        return redirect()->route('admin.users')
            ->with('success', "{$userName} has been deleted.");
    }

    /* ===== BULK MANAGER ASSIGN ===== */

    public function bulkAssignManager(Request $request)
    {
        $orgId = auth()->user()->organization_id;

        $request->validate([
            'user_ids'   => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
        ]);

        User::where('organization_id', $orgId)
            ->whereIn('id', $request->user_ids)
            ->update(['reporting_manager_id' => $request->manager_id]);

        $count = count($request->user_ids);

        return response()->json(['success' => true, 'message' => "{$count} user(s) updated."]);
    }

    /* ===== DESIGNATION MANAGEMENT ===== */

    public function designations()
    {
        $orgId        = auth()->user()->organization_id;
        $designations = Designation::where(function ($q) use ($orgId) {
            $q->where('organization_id', $orgId)->orWhereNull('organization_id');
        })
        ->orderBy('category')
        ->orderBy('sort_order')
        ->get()
        ->groupBy('category');

        return view('admin.designations.index', compact('designations'));
    }

    public function createDesignation()
    {
        return view('admin.designations.create');
    }

    public function storeDesignation(Request $request)
    {
        $orgId = auth()->user()->organization_id;
        $request->validate([
            'title'           => 'required|string|max:255',
            'category'        => 'required|string',
            'seniority_level' => 'nullable|string',
            'requires_github' => 'boolean',
        ]);

        $slug = Str::slug($request->title);

        Designation::create([
            'organization_id' => $orgId,
            'title'           => $request->title,
            'slug'            => $slug,
            'category'        => $request->category,
            'seniority_level' => $request->seniority_level,
            'requires_github' => $request->boolean('requires_github'),
            'is_template'     => false,
        ]);

        return redirect()->route('admin.designations')
            ->with('success', 'Designation created successfully.');
    }

    public function editDesignation(int $id)
    {
        $orgId       = auth()->user()->organization_id;
        $designation = Designation::where(function ($q) use ($orgId) {
            $q->where('organization_id', $orgId)->orWhereNull('organization_id');
        })->findOrFail($id);

        return view('admin.designations.edit', compact('designation'));
    }

    public function updateDesignation(Request $request, int $id)
    {
        $orgId       = auth()->user()->organization_id;
        $designation = Designation::where('organization_id', $orgId)->findOrFail($id);

        $request->validate([
            'title'           => 'required|string|max:255',
            'category'        => 'required|string',
            'seniority_level' => 'nullable|string',
            'requires_github' => 'boolean',
            'is_active'       => 'boolean',
        ]);

        $designation->update([
            'title'           => $request->title,
            'category'        => $request->category,
            'seniority_level' => $request->seniority_level,
            'requires_github' => $request->boolean('requires_github'),
            'is_active'       => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.designations')->with('success', 'Designation updated.');
    }

    public function deleteDesignation(int $id)
    {
        $orgId       = auth()->user()->organization_id;
        $designation = Designation::where('organization_id', $orgId)->findOrFail($id);

        $count = User::where('designation', $designation->slug)
            ->where('organization_id', $orgId)->count();

        if ($count > 0) {
            return back()->with('error', "Cannot delete — {$count} users have this designation. Reassign them first.");
        }

        $designation->delete();
        return redirect()->route('admin.designations')->with('success', 'Designation deleted.');
    }
}

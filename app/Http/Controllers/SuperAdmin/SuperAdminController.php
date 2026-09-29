<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Blocker;
use App\Models\Designation;
use App\Models\FairnessFlag;
use App\Models\Organization;
use App\Models\User;
use App\Services\ModuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    public function index()
    {
        $stats = [
            'total_orgs'      => Organization::count(),
            'pending_orgs'    => Organization::where('status', 'pending')->count(),
            'active_orgs'     => Organization::where('status', 'active')->count(),
            'suspended_orgs'  => Organization::where('status', 'suspended')->count(),
            'total_users'     => User::count(),
            'total_activities'=> Activity::count(),
            'total_flags'     => FairnessFlag::count(),
            'total_blockers'  => Blocker::count(),
        ];

        $pendingOrgs = Organization::where('status', 'pending')
            ->with('users')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($org) => $this->mapOrg($org));

        $recentOrgs = Organization::with('users')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(fn ($org) => $this->mapOrg($org));

        $planData = Organization::selectRaw('plan, COUNT(*) as count')
            ->groupBy('plan')
            ->pluck('count', 'plan');

        $last6Months = collect(range(5, 0))->map(fn ($m) => now()->subMonths($m));

        $orgGrowthRaw = Organization::selectRaw(
            'YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count'
        )->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(fn ($r) => $r->year . '-' . $r->month);

        $growthLabels = $last6Months->map(fn ($d) => $d->format('M Y'));
        $growthCounts = $last6Months->map(
            fn ($d) => $orgGrowthRaw->get($d->year . '-' . $d->month)?->count ?? 0
        );

        return view('superadmin.index', compact(
            'stats', 'pendingOrgs', 'recentOrgs', 'planData', 'growthLabels', 'growthCounts'
        ));
    }

    public function organizations(Request $request)
    {
        $query = Organization::with('users')->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->ajax()) {
            $perPage = (int) $request->get('per_page', 15);
            $orgs    = $query->paginate($perPage)->through(fn ($org) => $this->mapOrg($org));
            return response()->json($orgs);
        }

        $organizations = $query->get()->map(fn ($org) => $this->mapOrg($org));
        $currentStatus = $request->status ?? 'all';
        $search        = $request->search ?? '';

        return view('superadmin.organizations', compact('organizations', 'currentStatus', 'search'));
    }

    public function showOrganization(int $id)
    {
        $org = Organization::with('users')->findOrFail($id);

        $members = $org->users()->with('roles')->get()->map(function ($user) {
            return [
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'avatar'            => strtoupper(substr($user->name, 0, 2)),
                'role'              => $user->role,
                'roles'             => $user->getRoleNames(),
                'github'            => $user->github_username,
                'is_active'         => $user->is_active,
                'onboarding_status' => $user->onboarding_status,
                'joined'            => $user->created_at,
            ];
        });

        $orgStats = [
            'members'    => $members->count(),
            'activities' => Activity::where('organization_id', $id)->count(),
            'flags'      => FairnessFlag::where('organization_id', $id)->count(),
            'blockers'   => Blocker::where('organization_id', $id)->count(),
        ];

        return view('superadmin.organization-detail', compact('org', 'members', 'orgStats'));
    }

    public function approveOrganization(int $id)
    {
        $org = Organization::findOrFail($id);
        $org->update([
            'status'      => 'active',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        $owner = $org->users()->where('role', 'owner')->first();
        if ($owner) {
            $owner->update([
                'onboarding_status' => 'active',
                'approved_at'       => now(),
                'approved_by'       => auth()->id(),
            ]);
        }

        return back()->with('success', $org->name . ' approved successfully!');
    }

    public function suspendOrganization(Request $request, int $id)
    {
        $request->validate(['reason' => 'required|min:5']);

        $org = Organization::findOrFail($id);
        $org->update([
            'status' => 'suspended',
            'notes'  => $request->reason,
        ]);

        return back()->with('success', $org->name . ' has been suspended.');
    }

    public function reactivateOrganization(int $id)
    {
        $org = Organization::findOrFail($id);
        $org->update(['status' => 'active', 'notes' => null]);

        return back()->with('success', $org->name . ' has been reactivated.');
    }

    public function changePlan(Request $request, int $id)
    {
        $request->validate(['plan' => 'required|in:free,pro,enterprise']);

        $org = Organization::findOrFail($id);
        $org->update(['plan' => $request->plan]);

        return back()->with('success', $org->name . ' plan changed to ' . $request->plan . '.');
    }

    public function users(Request $request)
    {
        $query = User::with('organization', 'roles')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->ajax()) {
            $perPage = (int) $request->get('per_page', 15);
            $users   = $query->paginate($perPage)->through(function ($user) {
                return [
                    'id'                => $user->id,
                    'name'              => $user->name,
                    'email'             => $user->email,
                    'avatar'            => strtoupper(substr($user->name, 0, 2)),
                    'organization'      => $user->organization?->name ?? 'No Organization',
                    'roles'             => $user->getRoleNames(),
                    'onboarding_status' => $user->onboarding_status,
                    'is_active'         => $user->is_active,
                    'is_super_admin'    => $user->hasRole('super_admin'),
                    'created_at'        => optional($user->created_at)->toDateString(),
                ];
            });
            return response()->json($users);
        }

        $users = $query->get()->map(function ($user) {
            return [
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'avatar'            => strtoupper(substr($user->name, 0, 2)),
                'organization'      => $user->organization?->name ?? 'No Organization',
                'roles'             => $user->getRoleNames(),
                'onboarding_status' => $user->onboarding_status,
                'is_active'         => $user->is_active,
                'created_at'        => $user->created_at,
            ];
        });

        $search = $request->search ?? '';

        return view('superadmin.users', compact('users', 'search'));
    }

    public function impersonate(int $userId)
    {
        $user = User::findOrFail($userId);
        session(['impersonating_from' => auth()->id()]);
        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('warning', 'Impersonating ' . $user->name . '. Visit /superadmin/stop to return.');
    }

    public function stopImpersonate()
    {
        $originalId = session('impersonating_from');
        if ($originalId) {
            session()->forget('impersonating_from');
            Auth::login(User::find($originalId));
            return redirect()->route('superadmin.index')
                ->with('success', 'Returned to Super Admin.');
        }

        return redirect()->route('dashboard');
    }

    public function switchOrganization(int $orgId)
    {
        $org = Organization::findOrFail($orgId);
        session(['original_org_id' => auth()->user()->organization_id]);
        session(['switched_org' => $org->name]);
        auth()->user()->update(['organization_id' => $orgId]);

        return redirect()->route('dashboard')
            ->with('info', 'Viewing as: ' . $org->name . ' organization.');
    }

    public function switchBack()
    {
        $originalOrgId = session('original_org_id');
        if ($originalOrgId !== null) {
            auth()->user()->update(['organization_id' => $originalOrgId]);
            session()->forget(['original_org_id', 'switched_org']);
        }
        return redirect()->route('superadmin.index');
    }

    public function orgModules(int $id)
    {
        $org     = Organization::findOrFail($id);
        $service = new ModuleService();
        $modules = $service->getOrgModules($org->id);
        $plan    = $org->plan ?? 'free';

        return view('superadmin.org-modules', compact('org', 'modules', 'plan'));
    }

    public function toggleOrgModule(Request $request, int $id, string $moduleName)
    {
        $request->validate(['action' => 'required|in:enable,disable']);

        $org     = Organization::findOrFail($id);
        $service = new ModuleService();

        if ($request->action === 'enable') {
            $service->enableModule($org->id, $moduleName, auth()->id());
        } else {
            $service->disableModule($org->id, $moduleName, auth()->id());
        }

        $label = ucwords(str_replace('_', ' ', $moduleName));
        return back()->with('success', "{$label} has been " . ($request->action === 'enable' ? 'enabled' : 'disabled') . " for {$org->name}.");
    }

    /* ===== SUSPEND USER ===== */
    public function suspendUser(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Cannot suspend a Super Admin account.'], 403);
        }

        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Cannot suspend your own account.'], 403);
        }

        $user->update(['is_active' => false]);

        \Log::info('SuperAdmin suspended user', ['by' => auth()->user()->email, 'target' => $user->email, 'time' => now()]);

        return response()->json(['success' => true, 'message' => "{$user->name} has been suspended."]);
    }

    /* ===== ACTIVATE USER ===== */
    public function activateUser(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => true]);

        \Log::info('SuperAdmin activated user', ['by' => auth()->user()->email, 'target' => $user->email, 'time' => now()]);

        return response()->json(['success' => true, 'message' => "{$user->name} has been activated."]);
    }

    /* ===== DELETE USER ===== */
    public function deleteUser(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Cannot delete a Super Admin account.'], 403);
        }

        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Cannot delete your own account.'], 403);
        }

        $org = Organization::where('owner_id', $user->id)->first();
        if ($org) {
            return response()->json(['success' => false, 'message' => 'Cannot delete org owner. Transfer ownership first.'], 403);
        }

        $userName  = $user->name;
        $userEmail = $user->email;

        \App\Models\WorkLog::where('user_id', $user->id)->delete();
        \App\Models\Task::where('assigned_to', $user->id)->update(['assigned_to' => null]);

        $user->delete();

        \Log::info('SuperAdmin deleted user', ['by' => auth()->user()->email, 'deleted_user' => $userEmail, 'time' => now()]);

        return response()->json(['success' => true, 'message' => "{$userName} has been permanently deleted."]);
    }

    /* ===== EDIT USER ===== */
    public function editUser(Request $request, int $id)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
        ]);

        $user = User::findOrFail($id);

        if ($user->hasRole('super_admin') && $user->id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Cannot edit another Super Admin.'], 403);
        }

        $user->update(['name' => $request->name, 'email' => $request->email]);

        return response()->json(['success' => true, 'message' => 'User updated successfully.']);
    }

    /* ===== CHANGE ROLE ===== */
    public function changeRole(Request $request, int $id)
    {
        $request->validate(['role' => 'required|string']);

        $user = User::findOrFail($id);

        if ($user->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Cannot change Super Admin role.'], 403);
        }

        if ($request->role === 'super_admin') {
            return response()->json(['success' => false, 'message' => 'Cannot assign Super Admin role from here.'], 403);
        }

        $user->syncRoles([$request->role]);

        return response()->json(['success' => true, 'message' => "Role updated to {$request->role}."]);
    }

    /* ===== SUSPEND ORGANIZATION (AJAX) ===== */
    public function suspendOrg(Request $request, int $id)
    {
        $org = Organization::findOrFail($id);
        $org->update(['status' => 'suspended']);

        User::where('organization_id', $id)
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))
            ->update(['is_active' => false]);

        \Log::info('SuperAdmin suspended organization', ['by' => auth()->user()->email, 'org' => $org->name, 'time' => now()]);

        return response()->json(['success' => true, 'message' => "{$org->name} has been suspended."]);
    }

    /* ===== ACTIVATE ORGANIZATION (AJAX) ===== */
    public function activateOrg(Request $request, int $id)
    {
        $org = Organization::findOrFail($id);
        $org->update(['status' => 'active']);

        User::where('organization_id', $id)->update(['is_active' => true]);

        return response()->json(['success' => true, 'message' => "{$org->name} has been activated."]);
    }

    /* ===== DELETE ORGANIZATION ===== */
    public function deleteOrg(Request $request, int $id)
    {
        $org     = Organization::findOrFail($id);
        $orgName = $org->name;

        \App\Models\WorkLog::where('organization_id', $id)->delete();
        \App\Models\Sprint::where('organization_id', $id)->delete();
        \App\Models\IncrementReview::where('organization_id', $id)->delete();
        \App\Models\IncrementScore::where('organization_id', $id)->delete();
        \App\Models\FairnessFlag::where('organization_id', $id)->delete();
        \App\Models\Blocker::where('organization_id', $id)->delete();
        \App\Models\Department::where('organization_id', $id)->delete();

        // Delete tasks via projects (tasks have no direct organization_id)
        $projectIds = \App\Models\Project::where('organization_id', $id)->pluck('id');
        if ($projectIds->isNotEmpty()) {
            \App\Models\Task::whereIn('project_id', $projectIds)->delete();
            \App\Models\Project::whereIn('id', $projectIds)->delete();
        }

        User::where('organization_id', $id)
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))
            ->delete();

        $org->delete();

        \Log::info('SuperAdmin deleted organization', ['by' => auth()->user()->email, 'org' => $orgName, 'time' => now()]);

        return response()->json(['success' => true, 'message' => "{$orgName} and all its data have been deleted."]);
    }

    /* ===== PLATFORM DESIGNATION MANAGEMENT ===== */

    public function designations()
    {
        $designations = Designation::whereNull('organization_id')
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        $totalCount = Designation::whereNull('organization_id')->count();
        $orgCount   = Designation::whereNotNull('organization_id')->count();

        return view('superadmin.designations', compact('designations', 'totalCount', 'orgCount'));
    }

    public function storeDesignation(Request $request)
    {
        $request->validate([
            'title'           => 'required|string|max:255',
            'category'        => 'required|string',
            'seniority_level' => 'nullable|string',
            'requires_github' => 'boolean',
        ]);

        $slug = Str::slug($request->title);

        Designation::create([
            'organization_id' => null,
            'title'           => $request->title,
            'slug'            => $slug,
            'category'        => $request->category,
            'seniority_level' => $request->seniority_level,
            'requires_github' => $request->boolean('requires_github'),
            'is_template'     => true,
            'is_active'       => true,
        ]);

        return redirect()->route('superadmin.designations')
            ->with('success', "Platform designation '{$request->title}' added.");
    }

    public function deleteDesignation(int $id)
    {
        $designation = Designation::whereNull('organization_id')->findOrFail($id);

        $userCount = User::where('designation', $designation->slug)->count();
        if ($userCount > 0) {
            return back()->with('error', "Cannot delete — {$userCount} users have this designation across all orgs.");
        }

        $title = $designation->title;
        $designation->delete();

        return redirect()->route('superadmin.designations')
            ->with('success', "Platform designation '{$title}' deleted.");
    }

    private function mapOrg(Organization $org): array
    {
        $owner = $org->users()->where('role', 'owner')->first();

        $status = $org->status ?? 'active';

        return [
            'id'          => $org->id,
            'name'        => $org->name,
            'slug'        => $org->slug,
            'plan'        => $org->plan ?? 'free',
            'status'      => $status,
            'is_active'   => $status !== 'suspended',
            'members'     => $org->users->count(),
            'owner_name'  => $owner?->name ?? 'No owner',
            'owner_email' => $owner?->email ?? '',
            'created_at'  => $org->created_at,
            'approved_at' => $org->approved_at,
            'notes'       => $org->notes,
        ];
    }
}

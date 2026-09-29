<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class HrManagementController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        abort_if(!$user->hasAnyRole(['admin', 'owner', 'super_admin']) && !$user->hasPermissionTo('manage_hr_roles'), 403);

        $orgId = $user->organization_id;

        $all = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'))
            ->with(['roles', 'department', 'employeeProfile'])
            ->orderBy('name')
            ->get();

        $hrMembers = $all->filter(fn($u) => $u->hasRole('hr'))->values();
        $employees = $all->filter(fn($u) => !$u->hasRole('hr'))->values();

        return view('hr-management.index', compact('hrMembers', 'employees'));
    }

    public function assignHr(Request $request)
    {
        $auth = auth()->user();
        abort_if(!$auth->hasAnyRole(['admin', 'owner', 'super_admin']) && !$auth->hasPermissionTo('manage_hr_roles'), 403);

        $request->validate([
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'required|integer|exists:users,id',
        ]);

        $orgId   = $auth->organization_id;
        $count   = 0;

        foreach ($request->user_ids as $userId) {
            $user = User::where('id', $userId)
                ->where('organization_id', $orgId)
                ->first();

            if (!$user) continue;
            if ($user->hasRole('super_admin')) continue;
            if ($user->hasRole('hr')) continue;

            $user->assignRole('hr');
            \Log::info("HR role assigned to {$user->name} by {$auth->name}");
            $count++;
        }

        return redirect()->route('hr.index')
            ->with('success', "HR access granted to {$count} employee(s).");
    }

    public function revokeHr(Request $request)
    {
        $auth = auth()->user();
        abort_if(!$auth->hasAnyRole(['admin', 'owner', 'super_admin']) && !$auth->hasPermissionTo('manage_hr_roles'), 403);

        $request->validate(['user_id' => 'required|integer|exists:users,id']);

        $orgId = $auth->organization_id;
        $user  = User::where('id', $request->user_id)
            ->where('organization_id', $orgId)
            ->firstOrFail();

        $hrCount = User::where('organization_id', $orgId)
            ->whereHas('roles', fn($q) => $q->where('name', 'hr'))
            ->count();

        $orgSize = User::where('organization_id', $orgId)->where('is_active', true)->count();

        $warning = null;
        if ($hrCount <= 1 && $orgSize > 5) {
            $warning = 'Warning: This is the last HR member. HR tools will be unavailable until a new HR is assigned.';
        }

        $user->removeRole('hr');
        \Log::info("HR role revoked from {$user->name} by {$auth->name}");

        $msg = "HR access revoked from {$user->name}.";
        if ($warning) $msg .= " {$warning}";

        return redirect()->route('hr.index')->with('success', $msg);
    }
}

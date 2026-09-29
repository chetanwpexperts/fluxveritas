<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Department;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\EmployeeProfile;

class HrReportsController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        if (!$user->hasAnyRole(['hr','admin','owner','super_admin'])) {
            abort(403);
        }

        $year  = now()->year;
        $month = now()->month;

        $excludeRoles = fn($q) => $q->whereIn('name', ['super_admin', 'owner']);

        // HEADCOUNT
        $totalEmployees = User::where('organization_id', $orgId)
            ->whereDoesntHave('roles', $excludeRoles)
            ->count();

        $newThisMonth = User::where('organization_id', $orgId)
            ->whereDoesntHave('roles', $excludeRoles)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->count();

        $newThisQuarter = User::where('organization_id', $orgId)
            ->whereDoesntHave('roles', $excludeRoles)
            ->whereYear('created_at', $year)
            ->whereRaw('QUARTER(created_at) = QUARTER(NOW())')
            ->count();

        $totalDepartments = Department::where('organization_id', $orgId)
            ->count();

        // BY DEPARTMENT
        $byDepartment = Department::where('organization_id', $orgId)
            ->withCount(['users' => fn($q) =>
                $q->whereDoesntHave('roles', $excludeRoles)
            ])
            ->orderByDesc('users_count')
            ->get();

        // BY ROLE
        $byRole = [];
        foreach(['admin','hr','team_lead','employee','owner'] as $role) {
            $byRole[$role] = User::where('organization_id', $orgId)
                ->whereHas('roles', fn($q) => $q->where('name', $role))
                ->count();
        }

        // LEAVE SUMMARY
        $leaveTypes = LeaveType::where('organization_id', $orgId)
            ->where('is_active', true)->get();

        $totalLeavesTaken = LeaveApplication::where('organization_id', $orgId)
            ->where('status','approved')
            ->whereYear('from_date', $year)
            ->whereMonth('from_date', $month)
            ->sum('days');

        $pendingApprovals = LeaveApplication::where('organization_id', $orgId)
            ->where('status','pending')
            ->count();

        $leaveByType = [];
        foreach($leaveTypes as $type) {
            $leaveByType[$type->name] = LeaveApplication::where('organization_id', $orgId)
                ->where('leave_type_id', $type->id)
                ->where('status','approved')
                ->whereYear('from_date', $year)
                ->whereMonth('from_date', $month)
                ->sum('days');
        }

        $leaveByDept = Department::where('organization_id', $orgId)
            ->get()->map(function($dept) use ($month, $year) {
                $taken = LeaveApplication::whereHas('user',
                    fn($q) => $q->where('department_id', $dept->id))
                    ->where('status','approved')
                    ->whereYear('from_date', $year)
                    ->whereMonth('from_date', $month)
                    ->sum('days');
                return ['name' => $dept->name, 'taken' => $taken];
            })->sortByDesc('taken')->values();

        // PROFILE COMPLETENESS
        $profilesComplete = User::where('organization_id', $orgId)
            ->whereDoesntHave('roles', $excludeRoles)
            ->whereHas('employeeProfile', fn($q) =>
                $q->whereNotNull('designation')
                  ->whereNotNull('skills')
                  ->whereNotNull('bio'))
            ->count();

        $profilesIncomplete = User::where('organization_id', $orgId)
            ->whereDoesntHave('roles', $excludeRoles)
            ->where(function($q) {
                $q->whereDoesntHave('employeeProfile')
                  ->orWhereHas('employeeProfile', fn($p) =>
                      $p->whereNull('designation')
                        ->orWhereNull('skills')
                        ->orWhereNull('bio'));
            })
            ->with(['employeeProfile', 'department'])
            ->get();

        $noProfile = User::where('organization_id', $orgId)
            ->whereDoesntHave('roles', $excludeRoles)
            ->whereDoesntHave('employeeProfile')
            ->count();

        return view('hr.reports', compact(
            'totalEmployees','newThisMonth','newThisQuarter',
            'totalDepartments','byDepartment','byRole',
            'leaveTypes','totalLeavesTaken','pendingApprovals',
            'leaveByType','leaveByDept',
            'profilesComplete','profilesIncomplete','noProfile',
            'month','year'
        ));
    }
}

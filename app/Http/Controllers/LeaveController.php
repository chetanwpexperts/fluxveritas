<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Team;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        if ($user->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin', 'hr', 'manager'])) {
            return $this->adminIndex($user, $orgId);
        }

        if ($user->hasRole('team_lead')) {
            return $this->managerIndex($user, $orgId);
        }

        return $this->employeeIndex($user, $orgId);
    }

    private function employeeIndex(User $user, int $orgId)
    {
        $year = now()->year;

        if (LeaveType::where('organization_id', $orgId)->count() === 0) {
            $this->createDefaultLeaveTypes($orgId);
        }

        $leaveTypes = LeaveType::where('organization_id', $orgId)->where('is_active', true)->get();

        $this->ensureBalances($user, $leaveTypes, $year);

        $balances = LeaveBalance::where('user_id', $user->id)
            ->where('year', $year)
            ->with('leaveType')
            ->get();

        $applications = LeaveApplication::where('user_id', $user->id)
            ->with('leaveType', 'reviewer')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('leaves.index', compact('leaveTypes', 'balances', 'applications', 'year'));
    }

    private function managerIndex(User $user, int $orgId)
    {
        if (LeaveType::where('organization_id', $orgId)->count() === 0) {
            $this->createDefaultLeaveTypes($orgId);
        }

        $teamId = Team::where('team_lead_id', $user->id)->value('id');

        $teamMemberIds = $teamId
            ? User::where('team_id', $teamId)->where('is_active', true)->pluck('id')
            : collect();

        $pendingLeaves = LeaveApplication::whereIn('user_id', $teamMemberIds)
            ->where('status', 'pending')
            ->with('user', 'leaveType')
            ->orderBy('from_date')
            ->get();

        $allLeaves = LeaveApplication::whereIn('user_id', $teamMemberIds)
            ->with('user', 'leaveType', 'reviewer')
            ->orderByDesc('created_at')
            ->paginate(15);

        $leaveTypes = LeaveType::forOrg($orgId)->active()->get();
        $year       = now()->year;

        $teamBalances = LeaveBalance::whereIn('user_id', $teamMemberIds)
            ->where('year', $year)
            ->with('user', 'leaveType')
            ->get();

        return view('leaves.manager', compact(
            'pendingLeaves', 'allLeaves', 'leaveTypes', 'teamBalances', 'year'
        ));
    }

    private function adminIndex(User $user, int $orgId)
    {
        $year = now()->year;

        if (LeaveType::where('organization_id', $orgId)->count() === 0) {
            $this->createDefaultLeaveTypes($orgId);
        }

        $pendingLeaves = LeaveApplication::forOrg($orgId)
            ->where('status', 'pending')
            ->with('user', 'leaveType')
            ->orderBy('from_date')
            ->get();

        $allLeaves = LeaveApplication::forOrg($orgId)
            ->with('user', 'leaveType', 'reviewer')
            ->orderByDesc('created_at')
            ->paginate(20);

        $leaveTypes = LeaveType::forOrg($orgId)->get();

        $stats = [
            'pending'   => LeaveApplication::forOrg($orgId)->where('status', 'pending')->count(),
            'approved'  => LeaveApplication::forOrg($orgId)->where('status', 'approved')
                ->whereYear('from_date', $year)->count(),
            'rejected'  => LeaveApplication::forOrg($orgId)->where('status', 'rejected')
                ->whereYear('from_date', $year)->count(),
            'on_leave'  => LeaveApplication::forOrg($orgId)->where('status', 'approved')
                ->where('from_date', '<=', now()->toDateString())
                ->where('to_date', '>=', now()->toDateString())
                ->count(),
        ];

        return view('leaves.admin', compact('pendingLeaves', 'allLeaves', 'leaveTypes', 'stats', 'year'));
    }

    public function apply(Request $request)
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'from_date'     => 'required|date|after_or_equal:today',
            'to_date'       => 'required|date|after_or_equal:from_date',
            'reason'        => 'required|string|min:5|max:500',
            'half_day'      => 'sometimes|in:none,morning,afternoon',
        ]);

        $leaveType = LeaveType::where('id', $data['leave_type_id'])
            ->where('organization_id', $orgId)
            ->firstOrFail();

        $halfDayVal = $data['half_day'] ?? 'none';
        $isHalfDay  = $halfDayVal !== 'none';
        $days       = LeaveApplication::calculateWorkingDays($data['from_date'], $data['to_date'], $isHalfDay);

        if ($days <= 0) {
            return back()->withErrors(['from_date' => 'Selected dates fall on non-working days.']);
        }

        $year    = now()->year;
        $balance = LeaveBalance::firstOrCreate(
            ['user_id' => $user->id, 'leave_type_id' => $leaveType->id, 'year' => $year],
            ['organization_id' => $orgId, 'allocated' => $leaveType->days_per_year, 'used' => 0, 'pending' => 0, 'carried_forward' => 0]
        );

        if ($balance->available < $days) {
            return back()->withErrors(['leave_type_id' => "Insufficient leave balance. Available: {$balance->available} days."]);
        }

        $application = LeaveApplication::create([
            'user_id'          => $user->id,
            'leave_type_id'    => $leaveType->id,
            'organization_id'  => $orgId,
            'from_date'        => $data['from_date'],
            'to_date'          => $data['to_date'],
            'days'             => $days,
            'reason'           => $data['reason'],
            'status'           => $leaveType->requires_approval ? 'pending' : 'approved',
            'is_half_day'      => $isHalfDay,
            'half_day_period'  => $isHalfDay ? $halfDayVal : null,
        ]);

        $balance->increment('pending', $days);

        if (!$leaveType->requires_approval) {
            $balance->increment('used', $days);
            $balance->decrement('pending', $days);
        } else {
            $this->notifyApprovers($user, $orgId, $application);
        }

        return back()->with('success', 'Leave application submitted successfully.');
    }

    public function approve(Request $request, int $id)
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        $application = $this->resolveApprovableLeave($id, $user, $orgId);

        if (!$application) {
            return back()->withErrors(['error' => 'Unauthorized or leave not found.']);
        }

        if ($application->status !== 'pending') {
            return back()->withErrors(['error' => 'This leave is no longer pending.']);
        }

        $data = $request->validate(['reviewer_note' => 'nullable|string|max:300']);

        $application->update([
            'status'        => 'approved',
            'reviewed_by'   => $user->id,
            'reviewer_note' => $data['reviewer_note'] ?? null,
            'reviewed_at'   => now(),
        ]);

        $balance = LeaveBalance::where('user_id', $application->user_id)
            ->where('leave_type_id', $application->leave_type_id)
            ->where('year', $application->from_date->year)
            ->first();

        if ($balance) {
            $balance->increment('used', $application->days);
            $balance->decrement('pending', $application->days);
        }

        NotificationService::send(
            $application->user_id,
            $orgId,
            'leave_approved',
            'Leave Approved',
            "Your {$application->leaveType->name} from {$application->from_date->format('M d')} to {$application->to_date->format('M d')} has been approved.",
            route('leaves.index')
        );

        return back()->with('success', 'Leave approved.');
    }

    public function reject(Request $request, int $id)
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        $application = $this->resolveApprovableLeave($id, $user, $orgId);

        if (!$application) {
            return back()->withErrors(['error' => 'Unauthorized or leave not found.']);
        }

        if ($application->status !== 'pending') {
            return back()->withErrors(['error' => 'This leave is no longer pending.']);
        }

        $data = $request->validate(['reviewer_note' => 'nullable|string|max:300']);

        $application->update([
            'status'        => 'rejected',
            'reviewed_by'   => $user->id,
            'reviewer_note' => $data['reviewer_note'] ?? null,
            'reviewed_at'   => now(),
        ]);

        $balance = LeaveBalance::where('user_id', $application->user_id)
            ->where('leave_type_id', $application->leave_type_id)
            ->where('year', $application->from_date->year)
            ->first();

        if ($balance) {
            $balance->decrement('pending', $application->days);
        }

        NotificationService::send(
            $application->user_id,
            $orgId,
            'leave_rejected',
            'Leave Rejected',
            "Your {$application->leaveType->name} from {$application->from_date->format('M d')} to {$application->to_date->format('M d')} was not approved.",
            route('leaves.index')
        );

        return back()->with('success', 'Leave rejected.');
    }

    public function cancel(int $id)
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        $application = LeaveApplication::where('id', $id)
            ->where('user_id', $user->id)
            ->where('organization_id', $orgId)
            ->firstOrFail();

        if (!in_array($application->status, ['pending', 'approved'])) {
            return back()->withErrors(['error' => 'This leave cannot be cancelled.']);
        }

        if ($application->from_date->isPast() && $application->status === 'approved') {
            return back()->withErrors(['error' => 'Cannot cancel a leave that has already started.']);
        }

        $prevStatus = $application->status;
        $application->update(['status' => 'cancelled']);

        $balance = LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $application->leave_type_id)
            ->where('year', $application->from_date->year)
            ->first();

        if ($balance) {
            if ($prevStatus === 'pending') {
                $balance->decrement('pending', $application->days);
            } elseif ($prevStatus === 'approved') {
                $balance->decrement('used', $application->days);
            }
        }

        return back()->with('success', 'Leave cancelled.');
    }

    public function settings()
    {
        $user       = auth()->user();
        $orgId      = $user->organization_id;
        $leaveTypes = LeaveType::forOrg($orgId)->orderBy('name')->get();

        return view('leaves.settings', compact('leaveTypes'));
    }

    public function storeLeaveType(Request $request)
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        $data = $request->validate([
            'name'              => 'required|string|max:100',
            'code'              => 'required|string|max:20',
            'description'       => 'nullable|string|max:300',
            'days_per_year'     => 'required|integer|min:0|max:365',
            'is_paid'           => 'sometimes|boolean',
            'carry_forward'     => 'sometimes|boolean',
            'max_carry_forward' => 'nullable|integer|min:0',
            'requires_approval' => 'sometimes|boolean',
        ]);

        $exists = LeaveType::where('organization_id', $orgId)
            ->where('code', strtoupper($data['code']))
            ->exists();

        if ($exists) {
            return back()->withErrors(['code' => 'A leave type with this code already exists.']);
        }

        LeaveType::create([
            'organization_id'   => $orgId,
            'name'              => $data['name'],
            'code'              => strtoupper($data['code']),
            'description'       => $data['description'] ?? null,
            'days_per_year'     => $data['days_per_year'],
            'is_paid'           => (bool) ($data['is_paid'] ?? true),
            'carry_forward'     => (bool) ($data['carry_forward'] ?? false),
            'max_carry_forward' => $data['max_carry_forward'] ?? 0,
            'requires_approval' => (bool) ($data['requires_approval'] ?? true),
            'is_active'         => true,
        ]);

        return back()->with('success', 'Leave type created.');
    }

    public function updateLeaveType(Request $request, int $id)
    {
        $user      = auth()->user();
        $orgId     = $user->organization_id;
        $leaveType = LeaveType::where('id', $id)->where('organization_id', $orgId)->firstOrFail();

        $data = $request->validate([
            'name'              => 'required|string|max:100',
            'days_per_year'     => 'required|integer|min:0|max:365',
            'is_paid'           => 'sometimes|boolean',
            'carry_forward'     => 'sometimes|boolean',
            'max_carry_forward' => 'nullable|integer|min:0',
            'requires_approval' => 'sometimes|boolean',
            'is_active'         => 'sometimes|boolean',
            'description'       => 'nullable|string|max:300',
        ]);

        $leaveType->update([
            'name'              => $data['name'],
            'description'       => $data['description'] ?? $leaveType->description,
            'days_per_year'     => $data['days_per_year'],
            'is_paid'           => (bool) ($data['is_paid'] ?? $leaveType->is_paid),
            'carry_forward'     => (bool) ($data['carry_forward'] ?? $leaveType->carry_forward),
            'max_carry_forward' => $data['max_carry_forward'] ?? $leaveType->max_carry_forward,
            'requires_approval' => (bool) ($data['requires_approval'] ?? $leaveType->requires_approval),
            'is_active'         => (bool) ($data['is_active'] ?? $leaveType->is_active),
        ]);

        return back()->with('success', 'Leave type updated.');
    }

    public function allocateAll(Request $request)
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'year'          => 'required|integer|min:2020|max:2100',
        ]);

        $leaveType = LeaveType::where('id', $data['leave_type_id'])
            ->where('organization_id', $orgId)
            ->firstOrFail();

        $employees = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->get();

        $count = 0;
        foreach ($employees as $employee) {
            $balance = LeaveBalance::firstOrCreate(
                [
                    'user_id'       => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year'          => $data['year'],
                ],
                [
                    'organization_id' => $orgId,
                    'allocated'       => $leaveType->days_per_year,
                    'used'            => 0,
                    'pending'         => 0,
                    'carried_forward' => 0,
                ]
            );

            if ($balance->wasRecentlyCreated) {
                $count++;
            }
        }

        return back()->with('success', "Allocated {$leaveType->name} to {$count} employee(s) for {$data['year']}.");
    }

    public function hrIndex(Request $request)
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;
        $year  = $request->integer('year', now()->year);

        $query = LeaveApplication::forOrg($orgId)
            ->with('user', 'leaveType', 'reviewer')
            ->whereYear('from_date', $year);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $applications = $query->orderByDesc('created_at')->paginate(25)->withQueryString();
        $leaveTypes   = LeaveType::forOrg($orgId)->active()->get();

        $stats = [
            'pending'  => LeaveApplication::forOrg($orgId)->where('status', 'pending')->count(),
            'approved' => LeaveApplication::forOrg($orgId)->where('status', 'approved')->whereYear('from_date', $year)->count(),
            'rejected' => LeaveApplication::forOrg($orgId)->where('status', 'rejected')->whereYear('from_date', $year)->count(),
            'on_leave' => LeaveApplication::forOrg($orgId)->where('status', 'approved')
                ->where('from_date', '<=', now()->toDateString())
                ->where('to_date', '>=', now()->toDateString())
                ->count(),
        ];

        return view('leaves.hr-index', compact('applications', 'leaveTypes', 'stats', 'year'));
    }

    public function exportCsv(Request $request)
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;
        $year  = $request->integer('year', now()->year);

        $applications = LeaveApplication::forOrg($orgId)
            ->with('user', 'leaveType', 'reviewer')
            ->whereYear('from_date', $year)
            ->orderBy('from_date')
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"leaves_{$year}.csv\"",
        ];

        $callback = function () use ($applications) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Employee', 'Leave Type', 'From', 'To', 'Days', 'Status', 'Reason', 'Reviewed By', 'Note']);

            foreach ($applications as $app) {
                fputcsv($out, [
                    $app->user->name,
                    $app->leaveType->name,
                    $app->from_date->format('Y-m-d'),
                    $app->to_date->format('Y-m-d'),
                    $app->days,
                    $app->status,
                    $app->reason,
                    $app->reviewer?->name ?? '',
                    $app->reviewer_note ?? '',
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function createDefaultLeaveTypes(int $orgId): void
    {
        $defaults = [
            ['name' => 'Annual Leave',   'code' => 'AL', 'days_per_year' => 21, 'is_paid' => true,  'carry_forward' => true,  'max_carry_forward' => 5,  'requires_approval' => true],
            ['name' => 'Sick Leave',     'code' => 'SL', 'days_per_year' => 10, 'is_paid' => true,  'carry_forward' => false, 'max_carry_forward' => 0,  'requires_approval' => false],
            ['name' => 'Casual Leave',   'code' => 'CL', 'days_per_year' => 7,  'is_paid' => true,  'carry_forward' => false, 'max_carry_forward' => 0,  'requires_approval' => true],
            ['name' => 'Unpaid Leave',   'code' => 'UL', 'days_per_year' => 0,  'is_paid' => false, 'carry_forward' => false, 'max_carry_forward' => 0,  'requires_approval' => true],
            ['name' => 'Maternity Leave','code' => 'ML', 'days_per_year' => 90, 'is_paid' => true,  'carry_forward' => false, 'max_carry_forward' => 0,  'requires_approval' => true],
        ];

        foreach ($defaults as $d) {
            LeaveType::create(array_merge($d, ['organization_id' => $orgId, 'is_active' => true]));
        }
    }

    private function ensureBalances(User $user, $leaveTypes, int $year): void
    {
        foreach ($leaveTypes as $type) {
            LeaveBalance::firstOrCreate(
                ['user_id' => $user->id, 'leave_type_id' => $type->id, 'year' => $year],
                ['organization_id' => $user->organization_id, 'allocated' => $type->days_per_year, 'used' => 0, 'pending' => 0, 'carried_forward' => 0]
            );
        }
    }

    private function resolveApprovableLeave(int $id, User $reviewer, int $orgId): ?LeaveApplication
    {
        $application = LeaveApplication::where('id', $id)
            ->where('organization_id', $orgId)
            ->with('leaveType', 'user')
            ->first();

        if (!$application) {
            return null;
        }

        if ($reviewer->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin', 'hr', 'manager'])) {
            return $application;
        }

        if ($reviewer->hasRole('team_lead')) {
            $teamId = Team::where('team_lead_id', $reviewer->id)->value('id');
            if ($teamId && $application->user->team_id === $teamId) {
                return $application;
            }
        }

        return null;
    }

    private function notifyApprovers(User $applicant, int $orgId, LeaveApplication $application): void
    {
        $approverIds = [];

        if ($applicant->team_id) {
            $teamLead = Team::where('id', $applicant->team_id)->value('team_lead_id');
            if ($teamLead) {
                $approverIds[] = $teamLead;
            }
        }

        $admins = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'owner', 'ceo']))
            ->pluck('id')
            ->toArray();

        $approverIds = array_unique(array_merge($approverIds, $admins));

        if (!empty($approverIds)) {
            NotificationService::sendToMany(
                $approverIds,
                $orgId,
                'leave_request',
                'New Leave Request',
                "{$applicant->name} has applied for {$application->leaveType->name} from {$application->from_date->format('M d')} to {$application->to_date->format('M d')}.",
                route('leaves.index')
            );
        }
    }
}

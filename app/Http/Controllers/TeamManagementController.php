<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkLog;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TeamManagementController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        if ($user->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin'])) {
            $teams = Team::forOrg($orgId)
                ->with(['department', 'teamLead', 'members'])
                ->orderBy('department_id')
                ->orderBy('name')
                ->get();

            $departments = Department::where('organization_id', $orgId)->get();

            return view('teams.index', compact('teams', 'departments'))
                ->with('viewMode', 'all');
        }

        if ($user->hasRole('team_lead')) {
            $team = Team::forOrg($orgId)
                ->where('team_lead_id', $user->id)
                ->with(['department', 'teamLead', 'members'])
                ->first();

            if (!$team) {
                return view('teams.no-team');
            }

            return redirect()->route('teams.show', $team->id);
        }

        if ($this->isManager($user, $orgId)) {
            $deptIds = Department::where('organization_id', $orgId)
                ->whereHas('users', fn($q) => $q->where('id', $user->id))
                ->pluck('id');

            $teams = Team::forOrg($orgId)
                ->whereIn('department_id', $deptIds)
                ->with(['department', 'teamLead', 'members'])
                ->orderBy('name')
                ->get();

            $departments = Department::whereIn('id', $deptIds)->get();

            return view('teams.index', compact('teams', 'departments'))
                ->with('viewMode', 'department');
        }

        if ($user->team_id) {
            return redirect()->route('teams.show', $user->team_id);
        }

        return view('teams.no-team');
    }

    public function show(int $id)
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        $team = Team::forOrg($orgId)
            ->with(['department', 'teamLead', 'members.roles'])
            ->findOrFail($id);

        if (!$user->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin'])) {
            if ($user->hasRole('manager')) {
                $manageableIds = $user->manageableUserIds();
                abort_if(
                    !$manageableIds->contains($team->team_lead_id),
                    403,
                    'You can only view teams within your reporting chain.'
                );
            } elseif ($user->hasRole('team_lead')) {
                abort_if($team->team_lead_id !== $user->id, 403);
            } else {
                abort_if($user->team_id !== $team->id, 403);
            }
        }

        $today     = today()->toDateString();
        $memberIds = $team->members->pluck('id');

        $loggedToday = WorkLog::whereIn('user_id', $memberIds)
            ->where('log_date', $today)
            ->distinct('user_id')
            ->count('user_id');

        $activeTasks = \App\Models\Task::whereIn('assigned_to', $memberIds)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count();

        $openBlockers = \App\Models\Blocker::whereIn('blocked_user_id', $memberIds)
            ->where('status', 'open')
            ->count();

        $activeSprint = \App\Models\Sprint::where('organization_id', $orgId)
            ->where('status', 'active')
            ->whereHas('tasks', fn($q) => $q->whereIn('assigned_to', $memberIds))
            ->first();

        $memberStats = $team->members->map(function ($m) use ($today) {
            return [
                'user'         => $m,
                'logged_today' => WorkLog::where('user_id', $m->id)->where('log_date', $today)->exists(),
                'active_tasks' => \App\Models\Task::where('assigned_to', $m->id)->whereNotIn('status', ['done', 'cancelled'])->count(),
                'role'         => $m->getRoleNames()->first(),
            ];
        });

        $canManage = $user->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin', 'team_lead', 'manager']);

        return view('teams.show', compact(
            'team', 'loggedToday', 'activeTasks',
            'openBlockers', 'activeSprint', 'memberStats', 'canManage'
        ));
    }

    public function create()
    {
        abort_if(!auth()->user()->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin', 'manager']), 403);

        $orgId = auth()->user()->organization_id;

        $departments = Department::where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($departments->isEmpty()) {
            foreach (['Engineering', 'Design', 'Marketing', 'Human Resources', 'Operations'] as $deptName) {
                Department::create([
                    'organization_id' => $orgId,
                    'name'            => $deptName,
                    'slug'            => \Illuminate\Support\Str::slug($deptName),
                    'is_active'       => true,
                ]);
            }
            $departments = Department::where('organization_id', $orgId)->orderBy('name')->get();
        }

        $potentialLeads = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('teams.create', compact('departments', 'potentialLeads'));
    }

    public function store(Request $request)
    {
        abort_if(!auth()->user()->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin', 'manager']), 403);

        $request->validate([
            'name'          => 'required|string|max:100',
            'department_id' => 'required|exists:departments,id',
            'team_lead_id'  => 'nullable|exists:users,id',
            'description'   => 'nullable|string|max:500',
        ]);

        $orgId = auth()->user()->organization_id;

        $team = Team::create([
            'organization_id' => $orgId,
            'department_id'   => $request->department_id,
            'team_lead_id'    => $request->team_lead_id,
            'name'            => $request->name,
            'slug'            => Str::slug($request->name),
            'description'     => $request->description,
            'is_active'       => true,
        ]);

        if ($request->team_lead_id) {
            User::where('id', $request->team_lead_id)->update(['team_id' => $team->id]);
        }

        NotificationService::sendToManagers(
            $orgId,
            'team_created',
            '👥 New Team Created',
            "Team \"{$team->name}\" has been created in {$team->department->name} department.",
            route('teams.show', $team->id)
        );

        return redirect()->route('teams.show', $team->id)
            ->with('success', "Team \"{$team->name}\" created successfully!");
    }

    public function edit(int $id)
    {
        abort_if(!auth()->user()->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin', 'manager']), 403);

        $orgId = auth()->user()->organization_id;
        $team  = Team::forOrg($orgId)->findOrFail($id);

        $u = auth()->user();
        if ($u->hasRole('manager') && !$u->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin'])) {
            abort_if(!$u->manageableUserIds()->contains($team->team_lead_id), 403, 'You can only manage teams in your reporting chain.');
        }

        $departments = Department::where('organization_id', $orgId)->get();

        $potentialLeads = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $availableMembers = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->where(fn($q) => $q->whereNull('team_id')->orWhere('team_id', $id))
            ->orderBy('name')
            ->get();

        return view('teams.edit', compact('team', 'departments', 'potentialLeads', 'availableMembers'));
    }

    public function update(Request $request, int $id)
    {
        abort_if(!auth()->user()->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin', 'manager']), 403);

        $orgId = auth()->user()->organization_id;
        $team  = Team::forOrg($orgId)->findOrFail($id);

        $u = auth()->user();
        if ($u->hasRole('manager') && !$u->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin'])) {
            abort_if(!$u->manageableUserIds()->contains($team->team_lead_id), 403, 'You can only manage teams in your reporting chain.');
        }

        $request->validate([
            'name'          => 'required|string|max:100',
            'department_id' => 'required|exists:departments,id',
            'team_lead_id'  => 'nullable|exists:users,id',
            'description'   => 'nullable|string|max:500',
        ]);

        if ($team->team_lead_id && $team->team_lead_id !== (int) $request->team_lead_id) {
            User::where('id', $team->team_lead_id)
                ->where('team_id', $team->id)
                ->update(['team_id' => null]);
        }

        $team->update([
            'department_id' => $request->department_id,
            'team_lead_id'  => $request->team_lead_id,
            'name'          => $request->name,
            'description'   => $request->description,
        ]);

        if ($request->team_lead_id) {
            User::where('id', $request->team_lead_id)->update(['team_id' => $team->id]);
        }

        return redirect()->route('teams.show', $team->id)
            ->with('success', 'Team updated successfully!');
    }

    public function addMember(Request $request, int $id)
    {
        abort_if(!auth()->user()->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin', 'team_lead', 'manager']), 403);

        $orgId = auth()->user()->organization_id;
        $team  = Team::forOrg($orgId)->withCount('members')->findOrFail($id);

        $u = auth()->user();
        if ($u->hasRole('manager') && !$u->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin'])) {
            abort_if(!$u->manageableUserIds()->contains($team->team_lead_id), 403, 'You can only manage teams in your reporting chain.');
        }

        if ($team->members_count >= 10) {
            return back()->with('error', 'Team is at maximum capacity (10 members).');
        }

        $request->validate(['user_id' => 'required|exists:users,id']);

        $member = User::where('organization_id', $orgId)->findOrFail($request->user_id);

        if ($member->team_id && $member->team_id !== $id) {
            return back()->with('error', "{$member->name} is already in another team. Remove them first.");
        }

        $member->update([
            'team_id'              => $team->id,
            'reporting_manager_id' => $team->team_lead_id,
        ]);

        NotificationService::send(
            $member->id, $orgId,
            'team_assigned',
            '👥 Added to Team',
            "You have been added to the \"{$team->name}\" team.",
            route('teams.show', $team->id)
        );

        return back()->with('success', "{$member->name} added to {$team->name}!");
    }

    public function removeMember(int $teamId, int $userId)
    {
        abort_if(!auth()->user()->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin', 'team_lead', 'manager']), 403);

        $orgId = auth()->user()->organization_id;
        $team  = Team::forOrg($orgId)->findOrFail($teamId);

        $u = auth()->user();
        if ($u->hasRole('manager') && !$u->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin'])) {
            abort_if(!$u->manageableUserIds()->contains($team->team_lead_id), 403, 'You can only manage teams in your reporting chain.');
        }

        if ($team->team_lead_id === $userId) {
            return back()->with('error', 'Cannot remove the team lead. Change team lead first.');
        }

        User::where('id', $userId)
            ->where('organization_id', $orgId)
            ->update(['team_id' => null, 'reporting_manager_id' => null]);

        return back()->with('success', 'Member removed from team.');
    }

    public function destroy(int $id)
    {
        abort_if(!auth()->user()->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin']), 403);

        $orgId = auth()->user()->organization_id;
        $team  = Team::forOrg($orgId)->findOrFail($id);

        User::where('team_id', $team->id)->update(['team_id' => null]);

        $name = $team->name;
        $team->delete();

        return redirect()->route('teams.index')
            ->with('success', "Team \"{$name}\" deleted.");
    }

    public function availableMembers(int $teamId)
    {
        $orgId = auth()->user()->organization_id;
        $team  = Team::forOrg($orgId)->findOrFail($teamId);

        $members = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->where(fn($q) => $q->whereNull('team_id')->orWhere('team_id', $teamId))
            ->whereNotIn('id', [$team->team_lead_id ?? 0])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'job_title', 'team_id']);

        return response()->json($members);
    }

    private function isManager(User $user, int $orgId): bool
    {
        return $user->directReports()->where('organization_id', $orgId)->exists();
    }
}

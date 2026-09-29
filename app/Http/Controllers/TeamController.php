<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\EmailService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->organization_id) {
            return redirect()->route('organization.create')
                ->with('warning', 'Create an organization before managing your team.');
        }

        if ($request->ajax()) {
            $perPage = (int) $request->get('per_page', 20);
            $members = User::where('organization_id', $user->organization_id)
                ->with('roles')
                ->orderBy('name')
                ->paginate($perPage)
                ->through(function (User $member) {
                    return [
                        'id'          => $member->id,
                        'name'        => $member->name,
                        'email'       => $member->email,
                        'avatar'      => strtoupper(substr($member->name, 0, 2)),
                        'roles'       => $member->getRoleNames(),
                        'is_active'   => $member->is_active,
                        'github'      => $member->github_username,
                        'stat_commits'=> Activity::where('user_id', $member->id)->where('event_type', 'commit')->count(),
                        'stat_prs'    => Activity::where('user_id', $member->id)->where('event_type', 'pull_request')->count(),
                        'last_active' => Activity::where('user_id', $member->id)->max('occurred_at'),
                    ];
                });
            return response()->json($members);
        }

        $members = User::where('organization_id', $user->organization_id)
            ->with(['roles', 'reportingManager'])
            ->get()
            ->map(function (User $member) {
                $member->stat_commits = Activity::where('user_id', $member->id)
                    ->where('event_type', 'commit')->count();

                $member->stat_prs = Activity::where('user_id', $member->id)
                    ->where('event_type', 'pull_request')->count();

                $member->last_active = Activity::where('user_id', $member->id)
                    ->max('occurred_at');

                return $member;
            });

        $pendingInvitations = TeamInvitation::where('organization_id', $user->organization_id)
            ->pending()
            ->latest()
            ->get();

        $myDirectReports = User::where('organization_id', $user->organization_id)
            ->where('reporting_manager_id', $user->id)
            ->where('is_active', true)
            ->with(['department', 'workLogs' => function ($q) {
                $q->where('log_date', today());
            }])
            ->get();

        return view('team.index', compact('members', 'pendingInvitations', 'myDirectReports'));
    }

    public function invite(Request $request): View|RedirectResponse
    {
        if (!$request->user()->organization_id) {
            return redirect()->route('organization.create');
        }

        return view('team.invite');
    }

    public function sendInvite(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role'  => ['required', 'in:admin,manager,team_lead,hr,employee'],
        ]);

        // Check not already a member
        $alreadyMember = User::where('organization_id', $user->organization_id)
            ->where('email', $request->email)
            ->exists();

        if ($alreadyMember) {
            return back()->withErrors(['email' => 'This person is already a member of your organization.'])->withInput();
        }

        // Cancel any existing pending invite for same email in this org
        TeamInvitation::where('organization_id', $user->organization_id)
            ->where('email', $request->email)
            ->whereNull('accepted_at')
            ->delete();

        $invitation = TeamInvitation::create([
            'organization_id' => $user->organization_id,
            'invited_by'      => $user->id,
            'email'           => $request->email,
            'role'            => $request->role,
            'token'           => Str::random(32),
            'expires_at'      => now()->addDays(7),
        ]);

        $inviteLink = route('team.accept', $invitation->token);

        $emailService = new EmailService();
        $sent = $emailService->sendInvitation($invitation, $user->organization, $user->name);

        return redirect()->route('team.invite')
            ->with('invite_link', $inviteLink)
            ->with('invite_email', $invitation->email)
            ->with($sent ? 'success' : 'warning', $sent
                ? "Invitation sent to {$request->email}!"
                : "Invitation created but email could not be sent. Share the invite link manually.");
    }

    public function bulkInvite(Request $request): View|RedirectResponse
    {
        if (!$request->user()->organization_id) {
            return redirect()->route('organization.create');
        }

        return view('team.bulk-invite');
    }

    public function sendBulkInvite(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'emails' => ['required', 'string'],
            'role'   => ['required', 'in:admin,manager,team_lead,hr,employee'],
        ]);

        $raw    = preg_split('/[\s,;]+/', trim($request->emails));
        $emails = array_values(array_filter($raw, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)));

        if (empty($emails)) {
            return back()->withErrors(['emails' => 'No valid email addresses found.'])->withInput();
        }

        $orgId        = $user->organization_id;
        $emailService = new EmailService();

        $existingMembers = User::where('organization_id', $orgId)
            ->whereIn('email', $emails)
            ->pluck('email')
            ->map(fn($e) => strtolower($e))
            ->all();

        $sent    = 0;
        $skipped = 0;
        $failed  = 0;

        foreach ($emails as $email) {
            if (in_array(strtolower($email), $existingMembers)) {
                $skipped++;
                continue;
            }

            // Cancel any existing pending invite
            TeamInvitation::where('organization_id', $orgId)
                ->where('email', $email)
                ->whereNull('accepted_at')
                ->delete();

            $invitation = TeamInvitation::create([
                'organization_id' => $orgId,
                'invited_by'      => $user->id,
                'email'           => $email,
                'role'            => $request->role,
                'token'           => Str::random(32),
                'expires_at'      => now()->addDays(7),
            ]);

            $ok = $emailService->sendInvitation($invitation, $user->organization, $user->name);
            $ok ? $sent++ : $failed++;
        }

        $parts = [];
        if ($sent)    $parts[] = "{$sent} invitation" . ($sent > 1 ? 's' : '') . ' sent';
        if ($failed)  $parts[] = "{$failed} could not be emailed (invite links created)";
        if ($skipped) $parts[] = "{$skipped} already a member";

        $message = implode(', ', $parts) . '.';
        $type    = $failed > 0 && $sent === 0 ? 'warning' : 'success';

        return redirect()->route('team.bulk-invite')->with($type, $message);
    }

    public function acceptInvite(string $token): View|RedirectResponse
    {
        $invitation = TeamInvitation::where('token', $token)->firstOrFail();

        if ($invitation->isAccepted()) {
            return redirect()->route('dashboard')
                ->with('warning', 'This invitation has already been accepted.');
        }

        if ($invitation->isExpired()) {
            return redirect()->route('login')
                ->with('warning', 'This invitation has expired. Please ask for a new one.');
        }

        $existingUser = User::where('email', $invitation->email)->first();

        if ($existingUser) {
            $existingUser->update([
                'organization_id' => $invitation->organization_id,
                'role'            => $invitation->role,
            ]);
            $existingUser->syncRoles([$invitation->role]);

            $invitation->update(['accepted_at' => now()]);

            NotificationService::sendToManagers(
                $invitation->organization_id,
                'team_member_joined',
                '👋 New Team Member Joined',
                $existingUser->name . ' has joined the organization.',
                '/team',
                'low',
                ['user_id' => $existingUser->id],
                $existingUser->id
            );

            return redirect()->route('dashboard')
                ->with('success', 'Welcome! You have joined the organization.');
        }

        // New user — send to register with email pre-filled
        $invitation->update(['accepted_at' => now()]);

        return redirect()->route('register', ['email' => $invitation->email])
            ->with('success', 'Create your account to join the organization.');
    }

    public function removeUser(Request $request, int $userId): RedirectResponse
    {
        $authUser = $request->user();

        if (!in_array($authUser->role, ['owner', 'admin'])) {
            return back()->with('error', 'You do not have permission to remove team members.');
        }

        if ($authUser->id === $userId) {
            return back()->with('error', 'You cannot remove yourself from the team.');
        }

        $target = User::where('id', $userId)
            ->where('organization_id', $authUser->organization_id)
            ->firstOrFail();

        $target->update(['organization_id' => null]);

        return back()->with('success', "{$target->name} has been removed from the team.");
    }

    public function updateRole(Request $request, int $userId): RedirectResponse
    {
        $authUser = $request->user();

        if ($authUser->role !== 'owner') {
            return back()->with('error', 'Only the organization owner can change roles.');
        }

        $request->validate([
            'role' => ['required', 'in:owner,admin,employee'],
        ]);

        $target = User::where('id', $userId)
            ->where('organization_id', $authUser->organization_id)
            ->firstOrFail();

        $target->update(['role' => $request->role]);

        return back()->with('success', "{$target->name}'s role updated to {$request->role}.");
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Blocker;
use App\Models\BlockerResponse;
use App\Models\EmployeeStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskDependency;
use App\Models\User;
use App\Exceptions\WorkflowException;
use App\Services\BlockerService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DependencyController extends Controller
{
    public function index(Request $request)
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        if ($request->ajax()) {
            $perPage      = (int) $request->get('per_page', 15);
            $openBlockers = Blocker::where('organization_id', $orgId)
                ->with(['reportedBy', 'blockedUser', 'blockingUser', 'project', 'responses'])
                ->open()
                ->orderByDesc('priority')
                ->orderBy('created_at')
                ->paginate($perPage)
                ->through(fn($b) => [
                    'id'             => $b->id,
                    'title'          => $b->title,
                    'priority'       => $b->priority,
                    'blocker_type'   => $b->blocker_type,
                    'status'         => $b->status,
                    'reported_by'    => $b->reportedBy  ? ['name' => $b->reportedBy->name]  : null,
                    'blocked_user'   => $b->blockedUser ? ['name' => $b->blockedUser->name] : null,
                    'blocking_user'  => $b->blockingUser? ['name' => $b->blockingUser->name]: null,
                    'project'        => $b->project     ? ['name' => $b->project->name]     : null,
                    'response_count' => $b->responses?->count() ?? 0,
                    'days_open'      => $b->created_at ? (int) $b->created_at->diffInDays(now()) : 0,
                    'created_at'     => $b->created_at?->diffForHumans(),
                ]);
            return response()->json($openBlockers);
        }

        $openBlockers = Blocker::where('organization_id', $orgId)
            ->with(['reportedBy', 'blockedUser', 'blockingUser', 'project', 'responses'])
            ->open()
            ->orderByDesc('priority')
            ->orderBy('created_at')
            ->get();

        $resolvedBlockers = Blocker::where('organization_id', $orgId)
            ->where('status', 'resolved')
            ->with(['blockedUser', 'resolvedBy'])
            ->orderByDesc('resolved_at')
            ->limit(20)
            ->get();

        $disputedBlockers = Blocker::where('organization_id', $orgId)
            ->where('ownership_disputed', true)
            ->whereIn('status', ['open', 'escalated'])
            ->with(['blockedUser', 'blockingUser', 'project'])
            ->orderByDesc('dispute_raised_at')
            ->get();

        $waitingDeps = TaskDependency::where('organization_id', $orgId)
            ->with(['task', 'dependsOnTask', 'dependsOnUser'])
            ->waiting()
            ->orderBy('created_at')
            ->get();

        $employeeStatuses = EmployeeStatus::where('organization_id', $orgId)
            ->active()
            ->with('user')
            ->get();

        $crossTeamBlockers = Blocker::where('organization_id', $orgId)
            ->where('blocker_type', 'internal_other_team')
            ->where('status', 'open')
            ->with(['blockedUser', 'project'])
            ->orderBy('created_at')
            ->get();

        $projects = Project::where('organization_id', $orgId)->get(['id', 'name']);
        $members  = User::where('organization_id', $orgId)->get(['id', 'name', 'email', 'role']);
        $myTasks  = Task::where('assigned_to', auth()->id())
            ->whereNotIn('status', ['done', 'cancelled'])
            ->get(['id', 'ticket_number', 'title']);

        // Dispute pattern: users with 3+ disputes against them
        $disputePatternWarnings = Blocker::where('organization_id', $orgId)
            ->where('ownership_disputed', true)
            ->whereNotNull('blocking_user_id')
            ->with('blockingUser')
            ->get()
            ->groupBy('blocking_user_id')
            ->filter(fn($group) => $group->count() >= 3)
            ->map(fn($group) => [
                'user'  => $group->first()->blockingUser,
                'count' => $group->count(),
            ])
            ->values();

        return view('dependency.index', compact(
            'openBlockers',
            'resolvedBlockers',
            'disputedBlockers',
            'crossTeamBlockers',
            'waitingDeps',
            'employeeStatuses',
            'projects',
            'members',
            'myTasks',
            'disputePatternWarnings',
        ));
    }

    public function reportBlocker(Request $request, BlockerService $blockers): RedirectResponse
    {
        $validated = $request->validate([
            'project_id'              => 'required|integer',
            'blocker_type'            => 'required|string',
            'title'                   => 'required|string|max:255',
            'description'             => 'required|string',
            'priority'                => 'required|in:low,medium,high,critical',
            'blocking_user_id'        => 'nullable|integer',
            'task_id'                 => 'nullable|integer',
            'impact_level'            => 'required|string|in:just_me,my_team,cross_team,critical',
            'due_date'                => 'nullable|date',
            'external_person_name'    => 'nullable|string|max:100',
            'external_person_company' => 'nullable|string|max:100',
            'external_person_contact' => 'nullable|string|max:100',
            'evidence_notes'          => 'nullable|string|max:1000',
        ]);

        try {
            $blockers->report(auth()->user(), $validated);
        } catch (WorkflowException $e) {
            return back()->withErrors([$e->field => $e->getMessage()])->withInput();
        }

        return redirect()->route('dependency.index')->with('success', 'Blocker reported.');
    }

    public function resolveBlocker(Request $request, int $blockerId): RedirectResponse
    {
        $request->validate([
            'resolution_notes' => 'nullable|string',
            'resolution_proof' => 'nullable|string|max:500',
        ]);

        $blocker  = Blocker::where('organization_id', auth()->user()->organization_id)->findOrFail($blockerId);
        $daysOpen = $blocker->daysOpen();

        $blocker->update([
            'status'           => 'resolved',
            'resolved_at'      => now(),
            'resolved_by'      => auth()->id(),
            'resolution_notes' => $request->resolution_notes,
            'resolution_proof' => $request->resolution_proof,
            'days_to_resolve'  => $daysOpen,
        ]);

        BlockerResponse::create([
            'blocker_id'    => $blockerId,
            'user_id'       => auth()->id(),
            'response_type' => 'resolved',
            'message'       => 'Blocker resolved after ' . $daysOpen . ' days. ' . ($request->resolution_proof ?? ''),
        ]);

        NotificationService::send(
            $blocker->blocked_user_id,
            auth()->user()->organization_id,
            'blocker_resolved',
            '🎉 Blocker Resolved!',
            auth()->user()->name . ' has resolved your blocker "' . $blocker->title . '". You can now proceed!',
            '/dependencies/blocker/' . $blockerId,
            'View Details',
            'normal',
            ['blocker_id' => $blockerId],
            auth()->id()
        );

        NotificationService::sendToManagers(
            auth()->user()->organization_id,
            'blocker_resolved',
            '✅ Blocker Resolved',
            auth()->user()->name . ' resolved blocker: "' . $blocker->title . '"',
            '/dependencies/blocker/' . $blockerId,
            'low',
            ['blocker_id' => $blockerId],
            auth()->id()
        );

        return redirect()->route('dependency.index')->with('success', 'Blocker resolved! Took ' . $daysOpen . ' day(s).');
    }

    public function escalateBlocker(Request $request, int $blockerId): RedirectResponse
    {
        $blocker = Blocker::where('organization_id', auth()->user()->organization_id)->findOrFail($blockerId);
        $blocker->update(['status' => 'escalated']);

        BlockerResponse::create([
            'blocker_id'    => $blockerId,
            'user_id'       => auth()->id(),
            'response_type' => 'escalated',
            'message'       => 'Blocker escalated to leadership.',
        ]);

        NotificationService::sendToManagers(
            auth()->user()->organization_id,
            'blocker_escalated',
            '🚨 Blocker Escalated — Needs Immediate Attention',
            'Blocker "' . $blocker->title . '" has been escalated by ' . auth()->user()->name . '. Immediate action required.',
            '/dependencies/blocker/' . $blockerId,
            'critical',
            ['blocker_id' => $blockerId],
            auth()->id()
        );

        if ($blocker->blocking_user_id) {
            NotificationService::send(
                $blocker->blocking_user_id,
                auth()->user()->organization_id,
                'blocker_escalated',
                '🚨 Blocker Escalated to Management',
                'The blocker "' . $blocker->title . '" has been escalated to management because it was not resolved in time.',
                '/dependencies/blocker/' . $blockerId,
                'View Blocker',
                'critical',
                ['blocker_id' => $blockerId],
                auth()->id()
            );
        }

        return redirect()->route('dependency.index')->with('success', 'Blocker escalated.');
    }

    public function raiseDispute(Request $request, int $blockerId): RedirectResponse
    {
        $request->validate([
            'dispute_reason' => 'required|min:10',
        ]);

        $blocker = Blocker::where('organization_id', auth()->user()->organization_id)->findOrFail($blockerId);

        $blocker->update([
            'ownership_disputed' => true,
            'dispute_reason'     => $request->dispute_reason,
            'dispute_raised_at'  => now(),
            'status'             => 'escalated',
        ]);

        BlockerResponse::create([
            'blocker_id'    => $blockerId,
            'user_id'       => auth()->id(),
            'response_type' => 'disputed',
            'message'       => 'Ownership disputed: ' . $request->dispute_reason,
        ]);

        NotificationService::send(
            $blocker->blocked_user_id,
            auth()->user()->organization_id,
            'blocker_disputed',
            '⚠️ Blocker Ownership Disputed',
            auth()->user()->name . ' has disputed ownership of blocker "' . $blocker->title . '". Management will review.',
            '/dependencies/blocker/' . $blockerId,
            'View Dispute',
            'high',
            ['blocker_id' => $blockerId],
            auth()->id()
        );

        NotificationService::sendToManagers(
            auth()->user()->organization_id,
            'dispute_raised',
            '⚖️ Ownership Dispute Raised',
            auth()->user()->name . ' disputed ownership of: "' . $blocker->title . '". Review required.',
            '/dependencies/blocker/' . $blockerId,
            'high',
            ['blocker_id' => $blockerId],
            auth()->id()
        );

        return back()->with('warning', 'Ownership dispute recorded. This has been escalated to leadership for review. The data will speak.');
    }

    public function acknowledgeBlocker(Request $request, int $blockerId): RedirectResponse
    {
        $blocker = Blocker::where('organization_id', auth()->user()->organization_id)->findOrFail($blockerId);

        BlockerResponse::create([
            'blocker_id'    => $blockerId,
            'user_id'       => auth()->id(),
            'response_type' => 'acknowledged',
            'message'       => $request->message ?? 'Acknowledged responsibility.',
        ]);

        NotificationService::send(
            $blocker->blocked_user_id,
            auth()->user()->organization_id,
            'blocker_acknowledged',
            '✅ Blocker Acknowledged',
            auth()->user()->name . ' has acknowledged your blocker "' . $blocker->title . '" and will resolve it.',
            '/dependencies/blocker/' . $blockerId,
            'View Blocker',
            'normal',
            ['blocker_id' => $blockerId],
            auth()->id()
        );

        NotificationService::sendToManager(
            auth()->user(),
            'blocker_acknowledged',
            '✅ Blocker Acknowledged by ' . auth()->user()->name,
            auth()->user()->name . ' acknowledged blocker: "' . $blocker->title . '"',
            '/dependencies/blocker/' . $blockerId,
            'low',
            ['blocker_id' => $blockerId]
        );

        return back()->with('success', 'You have acknowledged this blocker. Please resolve it as soon as possible.');
    }

    public function commentOnBlocker(Request $request, int $blockerId): RedirectResponse
    {
        $request->validate([
            'message' => 'required|min:5|max:500',
        ]);

        $blocker = Blocker::where('organization_id', auth()->user()->organization_id)->findOrFail($blockerId);

        BlockerResponse::create([
            'blocker_id'    => $blockerId,
            'user_id'       => auth()->id(),
            'response_type' => 'commented',
            'message'       => $request->message,
        ]);

        $notifyIds = array_unique(array_filter([
            $blocker->blocked_user_id,
            $blocker->blocking_user_id,
        ]));

        NotificationService::sendToMany(
            $notifyIds,
            auth()->user()->organization_id,
            'blocker_comment',
            '💬 New Comment on Blocker',
            auth()->user()->name . ' commented on blocker "' . $blocker->title . '"',
            '/dependencies/blocker/' . $blockerId,
            'View Comment',
            'low',
            ['blocker_id' => $blockerId],
            auth()->id()
        );

        return back()->with('success', 'Comment added.');
    }

    public function showBlocker(int $blockerId): View
    {
        $blocker = Blocker::where('organization_id', auth()->user()->organization_id)
            ->with([
                'blockedUser',
                'blockingUser',
                'project',
                'resolvedBy',
                'responses.user',
                'reportedBy',
            ])
            ->findOrFail($blockerId);

        return view('dependency.blocker-detail', compact('blocker'));
    }

    public function disputePatterns(): JsonResponse
    {
        $orgId = auth()->user()->organization_id;

        $patterns = Blocker::where('organization_id', $orgId)
            ->where('ownership_disputed', true)
            ->with('blockingUser')
            ->get()
            ->groupBy('blocking_user_id')
            ->map(function ($blockers, $userId) {
                $user = $blockers->first()->blockingUser;
                return [
                    'user'                => $user,
                    'dispute_count'       => $blockers->count(),
                    'avg_days_to_resolve' => round($blockers->avg('days_to_resolve'), 1),
                    'total_days_blocked'  => $blockers->sum('days_to_resolve'),
                ];
            })
            ->sortByDesc('dispute_count')
            ->values();

        return response()->json($patterns);
    }

    public function setEmployeeStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id'   => 'required|exists:users,id',
            'status'    => 'required|in:on_leave,resigned,inactive',
            'reason'    => 'nullable|string',
            'starts_at' => 'required|date',
            'ends_at'   => 'nullable|date|after:starts_at',
        ]);

        $authUser = auth()->user();
        $orgId    = $authUser->organization_id;
        $isSelf   = (int) $validated['user_id'] === $authUser->id;
        $canManage = in_array($authUser->role, ['owner', 'admin']);

        if (!$isSelf && !$canManage) {
            return back()->withErrors(['user_id' => 'You can only set your own status.']);
        }

        EmployeeStatus::where('organization_id', $orgId)
            ->where('user_id', $validated['user_id'])
            ->whereNull('ends_at')
            ->update(['ends_at' => now()]);

        EmployeeStatus::create([
            'organization_id' => $orgId,
            'user_id'         => $validated['user_id'],
            'status'          => $validated['status'],
            'reason'          => $validated['reason'] ?? null,
            'starts_at'       => $validated['starts_at'],
            'ends_at'         => $validated['ends_at'] ?? null,
            'created_by'      => $authUser->id,
        ]);

        return redirect()->route('dependency.index')->with('success', 'Employee status updated.');
    }

    public function clearEmployeeStatus(Request $request, int $userId): RedirectResponse
    {
        $authUser  = auth()->user();
        $orgId     = $authUser->organization_id;
        $isSelf    = $userId === $authUser->id;
        $canManage = in_array($authUser->role, ['owner', 'admin']);

        if (!$isSelf && !$canManage) {
            abort(403);
        }

        EmployeeStatus::where('organization_id', $orgId)
            ->where('user_id', $userId)
            ->whereNull('ends_at')
            ->update(['ends_at' => now()]);

        return redirect()->route('dependency.index')->with('success', 'Status cleared.');
    }
}

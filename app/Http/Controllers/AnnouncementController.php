<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $announcements = Announcement::forUser($user)
            ->with(['author', 'department', 'team'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($a) => $this->formatAnnouncement($a, $user));

        $canPost = $user->hasAnyRole(['admin', 'owner', 'ceo', 'team_lead', 'super_admin', 'manager']);

        $departments = Department::where('organization_id', $user->organization_id)->get();
        $teams       = Team::where('organization_id', $user->organization_id)->get();

        return view('announcements.index', compact('announcements', 'canPost', 'departments', 'teams'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        abort_if(!$user->hasAnyRole(['admin', 'owner', 'ceo', 'team_lead', 'super_admin', 'manager']), 403);

        $request->validate([
            'title'         => 'required|string|max:200',
            'message'       => 'required|string|max:2000',
            'priority'      => 'required|in:normal,urgent',
            'audience'      => 'required|in:org,department,team',
            'department_id' => 'nullable|exists:departments,id',
            'team_id'       => 'nullable|exists:teams,id',
            'is_pinned'     => 'boolean',
            'expires_at'    => 'nullable|date|after:now',
        ]);

        // Team leads and managers can only post to their own team (not org-wide)
        $teamId   = $request->team_id;
        $audience = $request->audience;
        if ($user->hasAnyRole(['team_lead', 'manager']) && !$user->hasAnyRole(['admin', 'owner', 'ceo'])) {
            $audience = 'team';
            if ($user->hasRole('team_lead')) {
                $teamId = Team::where('team_lead_id', $user->id)->value('id');
            }
            // manager: team_id comes from the request (they choose which of their teams to post to)
        }

        $announcement = Announcement::create([
            'organization_id' => $user->organization_id,
            'posted_by'       => $user->id,
            'title'           => $request->title,
            'message'         => $request->message,
            'priority'        => $request->priority,
            'audience'        => $audience,
            'department_id'   => $request->department_id,
            'team_id'         => $teamId,
            'is_pinned'       => $request->boolean('is_pinned'),
            'expires_at'      => $request->expires_at,
        ]);

        $this->notifyUsers($announcement, $user);

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement posted successfully!');
    }

    public function markRead(int $id)
    {
        $user = auth()->user();

        AnnouncementRead::firstOrCreate(
            ['announcement_id' => $id, 'user_id' => $user->id],
            ['read_at' => now()]
        );

        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        $user = auth()->user();

        Announcement::forUser($user)
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))
            ->get()
            ->each(fn ($a) => AnnouncementRead::firstOrCreate(
                ['announcement_id' => $a->id, 'user_id' => $user->id],
                ['read_at' => now()]
            ));

        return back()->with('success', 'All announcements marked as read.');
    }

    public function destroy(int $id)
    {
        $user         = auth()->user();
        $announcement = Announcement::where('organization_id', $user->organization_id)->findOrFail($id);

        abort_if(
            $announcement->posted_by !== $user->id && !$user->hasAnyRole(['admin', 'owner', 'ceo']),
            403
        );

        $announcement->delete();

        return back()->with('success', 'Announcement deleted.');
    }

    public function togglePin(int $id)
    {
        $user = auth()->user();
        abort_if(!$user->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin']), 403);

        $announcement = Announcement::where('organization_id', $user->organization_id)->findOrFail($id);
        $announcement->update(['is_pinned' => !$announcement->is_pinned]);

        return back()->with('success', $announcement->is_pinned ? 'Announcement pinned.' : 'Announcement unpinned.');
    }

    public function unreadCount()
    {
        $user  = auth()->user();
        $count = Announcement::forUser($user)
            ->whereDoesntHave('reads', fn ($q) => $q->where('user_id', $user->id))
            ->count();

        return response()->json(['count' => $count]);
    }

    private function formatAnnouncement(Announcement $a, User $user): array
    {
        return [
            'id'             => $a->id,
            'title'          => $a->title,
            'message'        => $a->message,
            'priority'       => $a->priority,
            'audience'       => $a->audience,
            'audience_label' => $a->audience_label,
            'is_pinned'      => $a->is_pinned,
            'is_read'        => $a->isReadBy($user->id),
            'is_urgent'      => $a->priority === 'urgent',
            'author_name'    => $a->author?->name ?? '—',
            'author_role'    => $a->author?->getRoleNames()->first() ?? '',
            'avatar'         => strtoupper(substr($a->author?->name ?? 'A', 0, 1)),
            'posted_at'      => $a->created_at->diffForHumans(),
            'posted_at_full' => $a->created_at->format('M j, Y g:i A'),
            'expires_at'     => $a->expires_at?->format('M j, Y'),
            'can_delete'     => $a->posted_by === $user->id || $user->hasAnyRole(['admin', 'owner', 'ceo']),
            'can_pin'        => $user->hasAnyRole(['admin', 'owner', 'ceo', 'super_admin']),
        ];
    }

    private function notifyUsers(Announcement $a, User $poster): void
    {
        $orgId = $a->organization_id;
        $title = $a->priority === 'urgent' ? "🚨 Urgent: {$a->title}" : "📢 {$a->title}";
        $body  = \Str::limit($a->message, 100);
        $url   = route('announcements.index');

        $query = User::where('organization_id', $orgId)->where('id', '!=', $poster->id);

        if ($a->audience === 'department' && $a->department_id) {
            $query->where('department_id', $a->department_id);
        } elseif ($a->audience === 'team' && $a->team_id) {
            $query->where('team_id', $a->team_id);
        }

        $ids = $query->pluck('id')->toArray();
        if (!empty($ids)) {
            NotificationService::sendToMany($ids, $orgId, 'announcement', $title, $body, $url);
        }
    }
}

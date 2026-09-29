<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use App\Exceptions\WorkflowException;
use App\Services\AnnouncementService;
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

    public function store(Request $request, AnnouncementService $announcements)
    {
        abort_if(!$announcements->canPost(auth()->user()), 403);

        $data = $request->validate([
            'title'         => 'required|string|max:200',
            'message'       => 'required|string|max:2000',
            'priority'      => 'required|in:normal,urgent',
            'audience'      => 'required|in:org,department,team',
            'department_id' => 'nullable|integer',
            'team_id'       => 'nullable|integer',
            'is_pinned'     => 'boolean',
            'expires_at'    => 'nullable|date|after:now',
        ]);

        try {
            $announcements->post(auth()->user(), array_merge($data, ['is_pinned' => $request->boolean('is_pinned')]));
        } catch (WorkflowException $e) {
            return back()->withErrors([$e->field => $e->getMessage()])->withInput();
        }

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
}

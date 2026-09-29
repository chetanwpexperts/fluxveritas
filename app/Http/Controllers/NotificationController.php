<?php

namespace App\Http\Controllers;

use App\Models\AgentNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();

        if ($request->ajax()) {
            $perPage = (int) $request->get('per_page', 15);
            $items   = AgentNotification::forUser($userId)
                ->active()
                ->orderBy('created_at', 'desc')
                ->paginate($perPage)
                ->through(fn($n) => [
                    'id'           => $n->id,
                    'title'        => $n->title,
                    'message'      => $n->message,
                    'priority'     => $n->priority,
                    'is_read'      => $n->is_read,
                    'read_at'      => $n->read_at,
                    'action_url'   => $n->action_url,
                    'action_label' => $n->action_label,
                    'created_at'   => $n->created_at->diffForHumans(),
                ]);
            return response()->json($items);
        }

        $notifications = AgentNotification::forUser($userId)
            ->active()
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get()
            ->groupBy(function ($n) {
                $date = $n->created_at->toDateString();
                if ($date === today()->toDateString()) return 'Today';
                if ($date === today()->subDay()->toDateString()) return 'Yesterday';
                return $n->created_at->format('M j, Y');
            });

        $unreadCount = AgentNotification::forUser($userId)->active()->unread()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function unreadCount(): JsonResponse
    {
        if (!auth()->check()) {
            return response()->json(['count' => 0, 'unread_count' => 0]);
        }

        $count = AgentNotification::forUser(auth()->id())->active()->unread()->count();
        return response()->json(['count' => $count, 'unread_count' => $count]);
    }

    public function markRead(int $id): JsonResponse
    {
        $notification = AgentNotification::where('user_id', auth()->id())->findOrFail($id);
        $notification->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['success' => true]);
    }

    public function markAllRead(): JsonResponse
    {
        AgentNotification::forUser(auth()->id())
            ->active()
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function dismiss(int $id): JsonResponse
    {
        $notification = AgentNotification::where('user_id', auth()->id())->findOrFail($id);
        $notification->update(['is_dismissed' => true]);
        return response()->json(['success' => true]);
    }

    public function getLatest(): JsonResponse
    {
        $notifications = AgentNotification::forUser(auth()->id())
            ->active()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(fn($n) => [
                'id'           => $n->id,
                'title'        => $n->title,
                'message'      => $n->message,
                'action_url'   => $n->action_url,
                'action_label' => $n->action_label,
                'priority'     => $n->priority,
                'is_read'      => $n->is_read,
                'time_ago'     => $n->created_at->diffForHumans(),
            ]);

        return response()->json($notifications);
    }
}

<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::with('sender:id,username,avatar_url')
            ->where('recipient_id', $request->user()->id)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json($notifications);
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $notification = Notification::where('id', $id)
            ->where('recipient_id', $request->user()->id)
            ->firstOrFail();

        $notification->update(['is_read' => DB::raw('true')]);

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        Notification::where('recipient_id', $request->user()->id)
            ->whereRaw('is_read = false')
            ->update(['is_read' => DB::raw('true')]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = Notification::where('recipient_id', $request->user()->id)
            ->whereRaw('is_read = false')
            ->count();

        return response()->json(['count' => $count]);
    }
}

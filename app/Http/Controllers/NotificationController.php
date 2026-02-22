<?php

namespace App\Http\Controllers;

use App\Http\Requests\BroadcastNotificationRequest;
use App\Models\Lead;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();

        $dueLeads = Lead::query()
            ->where('assigned_user_id', $user->id)
            ->whereNotIn('status', ['converted', 'lost'])
            ->whereNotNull('follow_up_date')
            ->whereDate('follow_up_date', '<=', Carbon::today())
            ->get();

        foreach ($dueLeads as $lead) {
            $this->notificationService->notifyFollowUpReminder($lead, $user);
        }

        $notifications = $user->notifications()->latest()->limit(12)->get()->map(function ($notification) {
            return [
                'id' => $notification->id,
                'title' => data_get($notification->data, 'title', 'Notification'),
                'message' => data_get($notification->data, 'message', ''),
                'route' => data_get($notification->data, 'route', '#'),
                'type' => data_get($notification->data, 'type', 'general'),
                'read_at' => optional($notification->read_at)?->toDateTimeString(),
                'created_at' => optional($notification->created_at)?->diffForHumans(),
            ];
        });

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $notifications,
        ]);
    }

    public function markRead(Request $request, string $notificationId): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $notificationId)->firstOrFail();
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    public function broadcast(BroadcastNotificationRequest $request): JsonResponse
    {
        $this->notificationService->broadcast($request->string('message')->toString(), $request->user());

        return response()->json(['message' => 'Broadcast sent successfully.']);
    }
}

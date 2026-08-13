<?php

namespace App\Http\Controllers\Api;

use App\Models\PushToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class NotificationController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        return $this->success([
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'items' => $request->user()->notifications()->latest()->paginate(30),
        ]);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        $item->markAsRead();

        return $this->success($item->fresh());
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => Carbon::now()]);

        return response()->json(['message' => 'Notifications marked as read.']);
    }

    public function registerPushToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['required', Rule::in(['android', 'ios', 'web', 'unknown'])],
        ]);

        $pushToken = PushToken::updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $validated['platform'],
                'last_seen_at' => now(),
            ],
        );

        return $this->success($pushToken, 201);
    }
}

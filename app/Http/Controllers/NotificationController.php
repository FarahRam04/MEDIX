<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use App\Models\Notification;


class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $unread = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->get();

        $read = Notification::where('user_id', $user->id)
            ->where('is_read', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'Unread Notifications' => NotificationResource::collection($unread),
            'Read Notifications'   => NotificationResource::collection($read),
        ]);
    }
    //لما يفتح مستخدم اشعار
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'تم وضع الإشعار كمقروء']);
    }

}

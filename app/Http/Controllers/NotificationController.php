<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Support\AppNotificationCategory;
use App\Support\NavbarNotifications;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (! $user) {
            abort(401);
        }

        $category = $request->query('category');
        if (is_string($category) && AppNotificationCategory::isValid($category)) {
            $payload = NavbarNotifications::payloadForCategory($user, $category);
        } else {
            $payload = NavbarNotifications::payloadFor($user);
        }

        return response()->json([
            'notifications' => $payload['notifications'],
            'unread_count' => $payload['unread_count'],
            'category' => is_string($category) && AppNotificationCategory::isValid($category) ? $category : null,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function markAsRead(AppNotification $appNotification)
    {
        if ($appNotification->user_id !== auth()->id()) {
            abort(403);
        }

        $appNotification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(Request $request)
    {
        $query = AppNotification::query()
            ->where('user_id', auth()->id())
            ->whereNull('read_at');

        $category = $request->input('category');
        if (is_string($category) && AppNotificationCategory::isValid($category)) {
            AppNotificationCategory::applyScope($query, $category);
        }

        $query->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function unreadSummary()
    {
        $user = auth()->user();
        if (! $user) {
            abort(401);
        }

        $categories = [];
        $total = 0;

        foreach (AppNotificationCategory::keysForUser($user) as $category) {
            $query = AppNotification::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at');
            AppNotificationCategory::applyScope($query, $category);
            $count = $query->count();
            $categories[$category] = $count;
            $total += $count;
        }

        return response()->json([
            'categories' => $categories,
            'total' => $total,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}

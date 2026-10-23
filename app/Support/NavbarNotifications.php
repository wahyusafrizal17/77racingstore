<?php

namespace App\Support;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Collection;

class NavbarNotifications
{
    /**
     * @return array<string, array{notifications: Collection<int, AppNotification>, unread_count: int}>
     */
    public static function groupedPayloadFor(User $user): array
    {
        $groups = [];

        foreach (AppNotificationCategory::keysForUser($user) as $category) {
            $groups[$category] = self::payloadForCategory($user, $category);
        }

        return $groups;
    }

    /**
     * @return array{notifications: Collection<int, AppNotification>, unread_count: int}
     */
    public static function payloadFor(User $user): array
    {
        return self::payloadForCategory($user, AppNotificationCategory::VERIFIKASI);
    }

    /**
     * @return array{notifications: Collection<int, AppNotification>, unread_count: int}
     */
    public static function payloadForCategory(User $user, string $category): array
    {
        $baseQuery = AppNotification::query()->where('user_id', $user->id);
        AppNotificationCategory::applyScope($baseQuery, $category);

        $notifications = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(function (AppNotification $notif) use ($user) {
                $notif->link = NotificationLinkResolver::resolve($notif, $user);

                return $notif;
            });

        $unreadCount = (clone $baseQuery)
            ->whereNull('read_at')
            ->count();

        return [
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ];
    }

    /**
     * @return Collection<int, AppNotification>
     */
    public static function notificationsFor(User $user): Collection
    {
        return self::payloadForCategory($user, AppNotificationCategory::VERIFIKASI)['notifications'];
    }
}

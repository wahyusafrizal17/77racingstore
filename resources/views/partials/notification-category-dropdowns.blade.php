@php
    $groups = $navbarNotificationGroups ?? [];
    $categories = $navbarNotificationCategories ?? [];
    $showNewBadge = (bool) ($notificationShowNewBadge ?? false);
@endphp

<div class="flex items-center gap-1 sm:gap-1.5">
    @foreach($categories as $categoryKey => $categoryMeta)
        @php
            $group = $groups[$categoryKey] ?? ['notifications' => [], 'unread_count' => 0];
            $notifications = $group['notifications'] ?? [];
            $unreadCount = (int) ($group['unread_count'] ?? 0);
        @endphp
        @include('partials.notification-category-dropdown-item', [
            'category' => $categoryKey,
            'label' => $categoryMeta['label'],
            'title' => $categoryMeta['title'],
            'icon' => $categoryMeta['icon'],
            'badgeClass' => $categoryMeta['badge_class'],
            'buttonClass' => $categoryMeta['button_class'],
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'showNewBadge' => $showNewBadge,
        ])
    @endforeach
</div>

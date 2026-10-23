<?php
    $groups = $navbarNotificationGroups ?? [];
    $categories = $navbarNotificationCategories ?? [];
    $showNewBadge = (bool) ($notificationShowNewBadge ?? false);
?>

<div class="flex items-center gap-1 sm:gap-1.5">
    <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $categoryKey => $categoryMeta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $group = $groups[$categoryKey] ?? ['notifications' => [], 'unread_count' => 0];
            $notifications = $group['notifications'] ?? [];
            $unreadCount = (int) ($group['unread_count'] ?? 0);
        ?>
        <?php echo $__env->make('partials.notification-category-dropdown-item', [
            'category' => $categoryKey,
            'label' => $categoryMeta['label'],
            'title' => $categoryMeta['title'],
            'icon' => $categoryMeta['icon'],
            'badgeClass' => $categoryMeta['badge_class'],
            'buttonClass' => $categoryMeta['button_class'],
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'showNewBadge' => $showNewBadge,
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/notification-category-dropdowns.blade.php ENDPATH**/ ?>
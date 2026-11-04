<div class="relative" x-data="notifDropdown(<?php echo \Illuminate\Support\Js::from($notifications)->toHtml() ?>, <?php echo e((int) $unreadCount); ?>, <?php echo \Illuminate\Support\Js::from($category)->toHtml() ?>, <?php echo \Illuminate\Support\Js::from($title)->toHtml() ?>)">
    <button type="button"
            @click="toggle()"
            class="relative rounded-xl border border-gray-200 p-2.5 text-gray-500 transition hover:bg-gray-50 <?php echo e($buttonClass); ?>"
            :title="panelTitle"
            :aria-label="panelTitle">
        <?php if($icon === 'shield'): ?>
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <?php elseif($icon === 'arrows'): ?>
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
        <?php elseif($icon === 'package'): ?>
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        <?php elseif($icon === 'chat'): ?>
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        <?php else: ?>
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <?php endif; ?>
        <span x-show="unreadCount > 0"
              x-cloak
              class="absolute right-1.5 top-1.5 flex h-4 w-4 items-center justify-center rounded-full text-[9px] font-bold text-white ring-2 ring-white <?php echo e($badgeClass); ?>"
              x-text="unreadCount > 9 ? '9+' : unreadCount"></span>
    </button>

    <div x-show="open"
         @click.away="open = false"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-1"
         class="absolute right-0 mt-2 w-[420px] min-w-[380px] max-w-[95vw] rounded-xl border border-gray-100 bg-white shadow-xl z-50">
        <div class="flex items-center justify-between gap-2 border-b border-gray-100 px-4 py-3">
            <div class="min-w-0">
                <p class="text-sm font-bold text-gray-900" x-text="panelTitle"></p>
                <p class="text-[10px] font-medium uppercase tracking-wide text-gray-400"><?php echo e($label); ?></p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <?php if($showNewBadge): ?>
                    <span x-show="unreadCount > 0" x-cloak class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-600" x-text="unreadCount + ' Baru'"></span>
                <?php endif; ?>
                <button x-show="unreadCount > 0" x-cloak @click="markAllRead()" class="text-xs font-medium text-red-500 hover:text-red-600">Tandai semua dibaca</button>
            </div>
        </div>
        <div class="max-h-72 overflow-y-auto">
            <template x-if="notifications.length === 0">
                <div class="flex flex-col items-center justify-center py-10 px-4">
                    <svg class="h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <p class="mt-3 text-sm font-medium text-gray-400">Belum ada notifikasi</p>
                </div>
            </template>
            <template x-for="notif in notifications" :key="notif.id">
                <a :href="notif.link || '#'"
                   @click.prevent="openNotification(notif, $event)"
                   class="flex gap-3 border-b border-gray-50 px-4 py-3 transition hover:bg-gray-50"
                   :class="!notif.read_at && 'bg-red-50/50'">
                    <div class="mt-0.5 flex-shrink-0">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full" :class="itemIconClass(notif.type)">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-gray-900" x-text="notif.title"></p>
                        <p class="mt-0.5 line-clamp-3 break-words text-xs text-gray-500" x-text="notif.message"></p>
                        <p class="mt-1 text-[10px] text-gray-400" x-text="timeAgo(notif.created_at)"></p>
                    </div>
                    <div x-show="!notif.read_at" class="mt-2 flex-shrink-0">
                        <span class="block h-2 w-2 rounded-full bg-brand"></span>
                    </div>
                </a>
            </template>
        </div>
    </div>
</div>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/notification-category-dropdown-item.blade.php ENDPATH**/ ?>
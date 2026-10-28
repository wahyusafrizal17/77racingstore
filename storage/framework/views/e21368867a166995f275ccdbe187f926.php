<?php if(auth()->check()): ?>
<script>
(function () {
    if (window.NotificationPoll) {
        return;
    }

    window.NotificationPoll = {
        timer: null,

        csrfHeaders() {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            return {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            };
        },

        async refresh() {
            if (document.hidden) {
                return;
            }

            try {
                const res = await fetch('/notifications/unread-summary?_=' + Date.now(), {
                    headers: this.csrfHeaders(),
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                if (!res.ok) {
                    return;
                }
                const data = await res.json();
                window.dispatchEvent(new CustomEvent('notifications:summary', {
                    detail: {
                        categories: data.categories || {},
                        total: Number(data.total) || 0,
                    },
                }));
            } catch (e) {
                // Abaikan error jaringan sementara.
            }
        },

        start() {
            if (this.timer) {
                return;
            }

            this.refresh();
            this.timer = window.setInterval(() => this.refresh(), 30000);

            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) {
                    this.refresh();
                }
            });

            window.addEventListener('pageshow', (event) => {
                if (event.persisted) {
                    this.refresh();
                }
            });
        },
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => window.NotificationPoll.start());
    } else {
        window.NotificationPoll.start();
    }
})();

function notifDropdown(initialNotifications, initialUnreadCount, category, panelTitle) {
    return {
        open: false,
        category: category || null,
        panelTitle: panelTitle || 'Notifikasi',
        notifications: Array.isArray(initialNotifications) ? initialNotifications : [],
        unreadCount: Number(initialUnreadCount) || 0,
        summaryHandler: null,

        init() {
            this.summaryHandler = (event) => {
                const categories = event.detail?.categories || {};
                if (this.category && Object.prototype.hasOwnProperty.call(categories, this.category)) {
                    this.unreadCount = Number(categories[this.category]) || 0;
                    if (this.open) {
                        this.fetchNotifications();
                    }
                }
            };
            window.addEventListener('notifications:summary', this.summaryHandler);

            window.addEventListener('pageshow', (event) => {
                if (event.persisted) {
                    this.fetchNotifications();
                }
            });
        },

        destroy() {
            if (this.summaryHandler) {
                window.removeEventListener('notifications:summary', this.summaryHandler);
            }
        },

        csrfHeaders() {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            return {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            };
        },

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.fetchNotifications();
            }
        },

        notificationsUrl() {
            let url = '/notifications?_=' + Date.now();
            if (this.category) {
                url += '&category=' + encodeURIComponent(this.category);
            }
            return url;
        },

        async fetchNotifications() {
            try {
                const res = await fetch(this.notificationsUrl(), {
                    headers: this.csrfHeaders(),
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                if (!res.ok) {
                    return;
                }
                const text = await res.text();
                if (!text) {
                    return;
                }
                const data = JSON.parse(text);
                this.notifications = data.notifications || [];
                this.unreadCount = data.unread_count || 0;
            } catch (e) {
                // Pertahankan data server-render jika fetch gagal.
            }
        },

        async markRead(notif) {
            if (notif.read_at) {
                return;
            }
            const res = await fetch('/notifications/' + notif.id + '/read', {
                method: 'POST',
                headers: this.csrfHeaders(),
                credentials: 'same-origin',
            });
            if (!res.ok) {
                return;
            }
            notif.read_at = new Date().toISOString();
            this.unreadCount = Math.max(0, this.unreadCount - 1);
        },

        async openNotification(notif, event) {
            event.preventDefault();
            let target = notif.link;
            if (notif.type === 'speedshop_diskon_verifikasi_pending') {
                const soId = notif.data?.service_order_id;
                if (soId) {
                    target = '/speedshop/verifikasi/diskon-work-order/' + soId;
                } else {
                    target = '/speedshop/verifikasi/diskon-work-order';
                }
            }
            await this.markRead(notif);
            if (target && target !== '#') {
                window.location.assign(target);
            }
        },

        async markAllRead() {
            const headers = {
                ...this.csrfHeaders(),
                'Content-Type': 'application/json',
            };
            const body = this.category ? JSON.stringify({ category: this.category }) : '{}';
            const res = await fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers,
                body,
                credentials: 'same-origin',
            });
            if (!res.ok) {
                return;
            }
            this.notifications.forEach(function (n) {
                n.read_at = new Date().toISOString();
            });
            this.unreadCount = 0;
            window.NotificationPoll?.refresh();
        },

        timeAgo(dateStr) {
            const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
            if (diff < 60) return 'Baru saja';
            if (diff < 3600) return Math.floor(diff / 60) + ' menit lalu';
            if (diff < 86400) return Math.floor(diff / 3600) + ' jam lalu';
            return Math.floor(diff / 86400) + ' hari lalu';
        },

        itemIconClass(type) {
            if (type.includes('approved') || type.includes('diterima') || type.includes('confirmed') || type.includes('completed')) {
                return 'bg-green-100 text-green-600';
            }
            if (type.includes('rejected') || type.includes('ditolak')) {
                return 'bg-red-100 text-red-600';
            }
            if (type.includes('mutasi')) {
                return 'bg-blue-100 text-blue-600';
            }
            if (type.includes('chat')) {
                return 'bg-violet-100 text-violet-600';
            }
            return 'bg-amber-100 text-amber-600';
        },
    };
}
</script>
<?php endif; ?>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/notification-dropdown-head.blade.php ENDPATH**/ ?>
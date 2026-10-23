<script>
window.ChatPremium = window.ChatPremium || {
    initialsFrom(name) {
        const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
        if (!parts.length) return 'ME';
        const first = parts[0].charAt(0);
        const second = parts[1] ? parts[1].charAt(0) : (parts[0].charAt(1) || '');
        return (first + second).toUpperCase();
    },
    parseDate(msg) {
        if (!msg || !msg.created_at) return null;
        const d = new Date(msg.created_at);
        return Number.isNaN(d.getTime()) ? null : d;
    },
    sameDay(a, b) {
        return a.getFullYear() === b.getFullYear()
            && a.getMonth() === b.getMonth()
            && a.getDate() === b.getDate();
    },
    dayLabel(date) {
        const today = new Date();
        const yesterday = new Date();
        yesterday.setDate(today.getDate() - 1);
        if (this.sameDay(date, today)) return 'Hari ini';
        if (this.sameDay(date, yesterday)) return 'Kemarin';
        return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    },
    timeLabel(msg) {
        const d = this.parseDate(msg);
        if (d) {
            return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false });
        }
        return (msg && msg.created_at_label) || '';
    },
    ticksSvg() {
        return '<svg class="chat-ticks" viewBox="0 0 16 10" fill="none" aria-hidden="true">' +
            '<path d="M1.5 5.5l2.2 2.2L8.2 2.4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' +
            '<path d="M6.2 5.5l2.2 2.2L13.8 2.4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>' +
        '</svg>';
    },
    formatBytes(bytes) {
        const n = Number(bytes) || 0;
        if (n < 1024) return n + ' B';
        if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' KB';
        return (n / (1024 * 1024)).toFixed(1) + ' MB';
    },
    fillStandardBody(bodyEl, msg, ctx) {
        ctx = ctx || {};
        if (!bodyEl || !msg) return;
        if (msg.order) {
            const card = document.createElement('a');
            card.className = 'r-chat-order-card';
            card.href = typeof ctx.orderUrl === 'function' ? ctx.orderUrl(msg.order.id) : '#';
            if (msg.order.product_image_url) {
                const img = document.createElement('img');
                img.className = 'r-chat-order-img';
                img.alt = '';
                img.src = msg.order.product_image_url;
                card.appendChild(img);
            } else {
                const empty = document.createElement('span');
                empty.className = 'r-chat-order-img is-empty';
                empty.textContent = '#';
                card.appendChild(empty);
            }
            const body = document.createElement('span');
            body.className = 'r-chat-order-body';
            const ref = document.createElement('span');
            ref.className = 'r-chat-order-ref';
            ref.textContent = 'Pesanan #' + (msg.order.reference || msg.order.id);
            const product = document.createElement('span');
            product.className = 'r-chat-order-product';
            product.textContent = msg.order.no_resi
                ? ((msg.order.product_name || 'Produk pesanan') + ' · Resi: ' + msg.order.no_resi)
                : (msg.order.product_name || 'Produk pesanan');
            const meta = document.createElement('span');
            meta.className = 'r-chat-order-meta';
            const status = document.createElement('span');
            status.className = 'r-chat-order-status';
            status.textContent = msg.order.status_label || '';
            const total = document.createElement('span');
            total.className = 'r-chat-order-total';
            total.textContent = msg.order.total_label || '';
            meta.appendChild(status);
            meta.appendChild(total);
            body.appendChild(ref);
            body.appendChild(product);
            body.appendChild(meta);
            card.appendChild(body);
            bodyEl.appendChild(card);
        }
        if (msg.attachment_url && msg.attachment_is_image) {
            const a = document.createElement('a');
            a.href = msg.attachment_url;
            a.target = '_blank';
            a.rel = 'noopener';
            const img = document.createElement('img');
            img.className = 'r-chat-bubble-image';
            img.src = msg.attachment_url;
            img.alt = msg.attachment_name || 'Gambar';
            a.appendChild(img);
            bodyEl.appendChild(a);
        } else if (msg.attachment_url) {
            const a = document.createElement('a');
            a.className = 'r-chat-bubble-file';
            a.href = msg.attachment_url;
            a.target = '_blank';
            a.rel = 'noopener';
            a.setAttribute('download', '');
            a.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>';
            const meta = document.createElement('span');
            const name = document.createElement('span');
            name.className = 'r-chat-bubble-file-name';
            name.textContent = msg.attachment_name || 'File';
            const size = document.createElement('span');
            size.className = 'r-chat-bubble-file-size';
            size.textContent = msg.attachment_size ? this.formatBytes(msg.attachment_size) : '';
            meta.appendChild(name);
            meta.appendChild(size);
            a.appendChild(meta);
            bodyEl.appendChild(a);
        }
        if (msg.body || (!msg.attachment_url && !msg.order)) {
            const p = document.createElement('p');
            p.className = 'chat-text';
            p.textContent = msg.body || '';
            bodyEl.appendChild(p);
        }
    },
    helpers() {
        const api = this;
        return {
            threadItems() {
                return api.threadItems(this.messages);
            },
            rowClass(item) {
                if (!item || item.type !== 'msg' || !item.msg) return '';
                return (item.msg.mine ? 'is-mine' : '') + (item.isContinue ? ' is-continue' : '');
            },
            avatarFor(msg) {
                if (!msg) return { hasPhoto: false, url: '', initials: '', name: '', online: false };
                return api.avatarState(msg, {
                    selfProfile: this.selfProfile,
                    peer: this.activeChat || this.peer,
                });
            },
            timeFor(msg) {
                return api.timeLabel(msg || {});
            },
            ticksHtml() {
                return api.ticksSvg();
            },
            formatBytes(bytes) {
                return api.formatBytes(bytes);
            },
            orderUrl(orderId) {
                const tpl = this.orderShowTemplate
                    || this.orderShowUrlTemplate
                    || this.resellerOrderShowUrlTemplate
                    || '';
                if (tpl) return String(tpl).replace('__ID__', String(orderId));
                return '#';
            },
        };
    },
    avatarState(msg, ctx) {
        ctx = ctx || {};
        const self = ctx.selfProfile || {};
        const peer = ctx.peer || {};
        const mine = !!(msg && msg.mine);
        const name = mine
            ? (self.name || 'Saya')
            : ((msg && msg.sender_name) || peer.name || peer.title || peer.reseller_name || 'Pengguna');
        const hasPhoto = mine ? !!self.has_avatar : !!(msg && msg.sender_has_avatar);
        const url = mine
            ? (self.avatar_url || '')
            : ((msg && msg.sender_avatar_url) || peer.avatar_url || '');
        return {
            mine,
            name,
            hasPhoto: hasPhoto && !!url,
            url,
            online: !mine && !!(peer.online || (msg && msg.sender_online)),
            initials: this.initialsFrom(name),
        };
    },
    threadItems(messages) {
        const items = [];
        (messages || []).forEach((msg, index) => {
            const prev = index > 0 ? messages[index - 1] : null;
            const date = this.parseDate(msg);
            const prevDate = this.parseDate(prev);
            const newDay = !!(date && (!prevDate || !this.sameDay(date, prevDate)));
            if (newDay) {
                items.push({ type: 'day', key: 'day-' + msg.id, label: this.dayLabel(date) });
            }
            items.push({
                type: 'msg',
                key: 'msg-' + msg.id,
                msg,
                isContinue: !!(prev && prev.mine === msg.mine && !newDay),
            });
        });
        return items;
    },
    renderThread(container, messages, ctx) {
        ctx = ctx || {};
        if (!container) return;
        container.querySelectorAll('.chat-row, .chat-day').forEach((el) => el.remove());
        const rows = messages || [];
        if (ctx.emptyEl) ctx.emptyEl.hidden = rows.length > 0;
        if (!rows.length) return;
        this.threadItems(rows).forEach((item) => {
            if (item.type === 'day') {
                const day = document.createElement('div');
                day.className = 'chat-day';
                day.textContent = item.label;
                container.appendChild(day);
                return;
            }
            const msg = item.msg;
            const av = this.avatarState(msg, ctx);
            const row = document.createElement('div');
            row.className = 'chat-row' + (msg.mine ? ' is-mine' : '') + (item.isContinue ? ' is-continue' : '');
            row.innerHTML =
                '<span class="chat-avatar-wrap">' +
                    '<span class="chat-avatar' + (av.hasPhoto ? '' : ' is-initials') + '">' +
                        (av.hasPhoto ? '<img alt="">' : '<span></span>') +
                    '</span>' +
                    (av.online && !item.isContinue ? '<span class="chat-online" title="Online"></span>' : '') +
                '</span>' +
                '<div class="chat-col">' +
                    '<span class="chat-name"></span>' +
                    '<div class="chat-inner"><div class="chat-body"></div><span class="chat-time"></span></div>' +
                '</div>';
            const img = row.querySelector('.chat-avatar img');
            if (img) img.src = av.url;
            const initialsEl = row.querySelector('.chat-avatar.is-initials span');
            if (initialsEl) initialsEl.textContent = av.initials;
            row.querySelector('.chat-name').textContent = av.name;
            if (typeof ctx.fillBody === 'function') {
                ctx.fillBody(row.querySelector('.chat-body'), msg);
            } else {
                this.fillStandardBody(row.querySelector('.chat-body'), msg, ctx);
            }
            const timeEl = row.querySelector('.chat-time');
            timeEl.textContent = this.timeLabel(msg);
            if (msg.mine) timeEl.insertAdjacentHTML('beforeend', this.ticksSvg());
            container.appendChild(row);
        });
        if (ctx.scroll !== false) {
            container.scrollTop = container.scrollHeight;
        }
    },
};
</script>

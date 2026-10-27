@php
    $dmUser = auth()->user();
    $showDirectChat = (bool) $dmUser;
    $directChatUnread = 0;
    if ($showDirectChat) {
        $directChatUnread = app(\App\Services\AppDirectChatService::class)->unreadTotal($dmUser);
    }
    $directChatFabOffset = $directChatFabOffset ?? '20px';
    // Route warehouse chat/reseller sudah dihapus dari 77 Racing Store.
    $isWarehouseDm = ($dmUser?->canHandleExternalCustomerChats() ?? false)
        && \Illuminate\Support\Facades\Route::has('warehouse.chat-reseller.messages');
@endphp

@if($showDirectChat)
<style>
.dm-chat {
  position: fixed;
  right: 20px;
  bottom: {{ $directChatFabOffset }};
  z-index: 80;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 12px;
  font-family: Inter, system-ui, sans-serif;
}
.dm-chat-fab {
  position: relative;
  width: 56px;
  height: 56px;
  border: none;
  border-radius: 50%;
  background: #dc2626;
  color: #fff;
  box-shadow: 0 10px 28px rgba(220, 38, 38, 0.35);
  cursor: pointer;
  display: grid;
  place-items: center;
  transition: transform .15s ease, background .15s ease;
}
.dm-chat-fab:hover { background: #b91c1c; transform: translateY(-1px); }
.dm-chat-fab.is-open { background: #111827; box-shadow: 0 8px 20px rgba(0,0,0,.25); }
.dm-chat-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  min-width: 20px;
  height: 20px;
  padding: 0 5px;
  border-radius: 999px;
  background: #111827;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  display: none;
  place-items: center;
  border: 2px solid #fff;
  line-height: 1;
  z-index: 2;
  pointer-events: none;
  box-sizing: border-box;
}
.dm-chat-badge.is-visible { display: grid !important; }
.dm-chat-badge[hidden] { display: none !important; }
.dm-chat-fab.is-open .dm-chat-badge { background: #dc2626; }
.dm-chat-panel {
  width: min(380px, calc(100vw - 32px));
  height: min(560px, calc(100vh - 110px));
  background: #fff;
  border-radius: 18px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 18px 50px rgba(15, 23, 42, .18);
  display: none;
  flex-direction: column;
  overflow: hidden;
}
.dm-chat-panel.is-open { display: flex; }
.dm-chat-head {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 14px;
  background: linear-gradient(135deg, #111827, #1f2937);
  color: #fff;
  flex-shrink: 0;
}
.dm-chat-head-main { min-width: 0; flex: 1; }
.dm-chat-title { margin: 0; font-size: 14px; font-weight: 700; }
.dm-chat-sub { margin: 2px 0 0; font-size: 11px; color: rgba(255,255,255,.65); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dm-chat-icon-btn {
  border: none;
  background: rgba(255,255,255,.1);
  color: #fff;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  cursor: pointer;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}
.dm-chat-icon-btn:hover { background: rgba(255,255,255,.18); }
.dm-chat-list, .dm-chat-messages, .dm-chat-peers {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  background: #f8fafc;
}
.dm-chat-list, .dm-chat-peers { padding: 8px; }
.dm-chat-search {
  padding: 8px 8px 0;
  background: #f8fafc;
  flex-shrink: 0;
}
.dm-chat-search input {
  width: 100%;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 8px 12px;
  font: inherit;
  font-size: 13px;
  outline: none;
  background: #fff;
}
.dm-chat-search input:focus {
  border-color: #fca5a5;
  box-shadow: 0 0 0 3px rgba(220, 38, 38, .12);
}
.dm-chat-empty {
  margin: 40px auto;
  text-align: center;
  font-size: 13px;
  color: #94a3b8;
  max-width: 240px;
  line-height: 1.45;
}
.dm-chat-item {
  width: 100%;
  text-align: left;
  border: 1px solid transparent;
  background: #fff;
  border-radius: 12px;
  padding: 10px 12px;
  margin-bottom: 6px;
  cursor: pointer;
  display: flex;
  gap: 10px;
  align-items: flex-start;
}
.dm-chat-item:hover { border-color: #fecaca; background: #fff5f5; }
.dm-chat-item.is-unread { background: #fef2f2; border-color: #fecaca; }
.dm-chat-avatar {
  width: 36px;
  height: 36px;
  border-radius: 999px;
  background: #fee2e2;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  overflow: hidden;
  border: 1px solid #fecaca;
}
.dm-chat-avatar-wrap {
  position: relative;
  flex-shrink: 0;
  width: 36px;
  height: 36px;
}
.dm-chat-online-dot {
  position: absolute;
  right: -1px;
  bottom: -1px;
  width: 11px;
  height: 11px;
  border-radius: 999px;
  background: #22c55e;
  border: 2px solid #fff;
  box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.35);
  pointer-events: none;
}
.dm-chat-online-label {
  color: #16a34a;
  font-weight: 600;
}
.dm-chat-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.dm-chat-avatar.is-logo img {
  object-fit: contain;
  padding: 6px;
  background: #fff;
}
.dm-chat-item-body { min-width: 0; flex: 1; }
.dm-chat-item-top { display: flex; justify-content: space-between; gap: 8px; }
.dm-chat-item-name { font-size: 13px; font-weight: 600; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dm-chat-item-time { font-size: 10px; color: #94a3b8; flex-shrink: 0; }
.dm-chat-item-preview { margin-top: 2px; font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.dm-chat-item-badge {
  margin-top: 2px;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: 999px;
  background: #dc2626;
  color: #fff;
  font-size: 10px;
  font-weight: 700;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}
.dm-chat-toolbar {
  padding: 8px 10px 0;
  flex-shrink: 0;
  background: #f8fafc;
}
.dm-chat-new-btn {
  width: 100%;
  border: 1px dashed #fca5a5;
  background: #fff;
  color: #b91c1c;
  border-radius: 10px;
  padding: 8px 12px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}
.dm-chat-new-btn:hover { background: #fef2f2; }
.dm-chat-messages { padding: 14px; display: flex; flex-direction: column; gap: 10px; }
.dm-chat-bubble { display: flex; }
.dm-chat-bubble.is-mine { justify-content: flex-end; }
.dm-chat-bubble-inner {
  max-width: 82%;
  padding: 9px 12px;
  border-radius: 14px;
  background: #fff;
  border: 1px solid #e5e7eb;
  box-shadow: 0 1px 2px rgba(0,0,0,.04);
}
.dm-chat-bubble.is-mine .dm-chat-bubble-inner {
  background: #dc2626;
  border-color: #dc2626;
  color: #fff;
  border-bottom-right-radius: 5px;
}
.dm-chat-bubble:not(.is-mine) .dm-chat-bubble-inner { border-bottom-left-radius: 5px; }
.dm-chat-bubble-meta { display: block; margin-bottom: 2px; font-size: 10px; font-weight: 600; opacity: .75; }
.dm-chat-bubble-text { margin: 0; font-size: 13px; line-height: 1.45; white-space: pre-wrap; word-break: break-word; }
.dm-chat-bubble-file {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 0 0 6px;
  padding: 8px 10px;
  border-radius: 10px;
  background: rgba(0,0,0,.06);
  text-decoration: none;
  color: inherit;
  max-width: 100%;
}
.dm-chat-bubble.is-mine .dm-chat-bubble-file { background: rgba(255,255,255,.18); color: #fff; }
.dm-chat-bubble-file:hover { opacity: .92; }
.dm-chat-bubble-file svg { flex-shrink: 0; }
.dm-chat-bubble-file-meta { min-width: 0; flex: 1; }
.dm-chat-bubble-file-name {
  display: block;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dm-chat-bubble-file-size { display: block; font-size: 10px; opacity: .7; margin-top: 1px; }
.dm-chat-bubble-image {
  display: block;
  max-width: 220px;
  max-height: 180px;
  width: auto;
  height: auto;
  border-radius: 10px;
  margin: 0 0 6px;
  object-fit: cover;
  cursor: pointer;
}
.dm-chat-compose {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 8px;
  padding: 10px 12px;
  border-top: 1px solid #e5e7eb;
  background: #fff;
  flex-shrink: 0;
}
.dm-chat-attach-preview {
  flex: 1 1 100%;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 10px;
  border-radius: 10px;
  background: #f3f4f6;
  font-size: 12px;
  color: #374151;
}
.dm-chat-attach-preview span {
  flex: 1;
  min-width: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.dm-chat-attach-preview button {
  border: none;
  background: transparent;
  color: #b91c1c;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
}
.dm-chat-attach-btn {
  width: 40px;
  height: 40px;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  background: #fff;
  color: #4b5563;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
}
.dm-chat-attach-btn:hover { background: #f9fafb; color: #111827; }
.dm-chat-compose textarea {
  flex: 1;
  min-height: 40px;
  max-height: 96px;
  resize: none;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 10px 12px;
  font: inherit;
  font-size: 13px;
  outline: none;
}
.dm-chat-compose textarea:focus {
  border-color: #fca5a5;
  box-shadow: 0 0 0 3px rgba(220, 38, 38, .12);
}
.dm-chat-send {
  width: 40px;
  height: 40px;
  border: none;
  border-radius: 12px;
  background: #dc2626;
  color: #fff;
  cursor: pointer;
  display: grid;
  place-items: center;
  flex-shrink: 0;
}
.dm-chat-send:hover { background: #b91c1c; }
.dm-chat-send:disabled { opacity: .5; cursor: not-allowed; }
.dm-chat-order-card {
  display: flex; align-items: center; gap: 10px; margin-bottom: 6px;
  padding: 8px; border-radius: 12px; text-decoration: none; color: inherit;
  background: rgba(15, 23, 42, 0.05); border: 1px solid rgba(15, 23, 42, 0.08);
  max-width: 260px;
}
.dm-chat-bubble.is-mine .dm-chat-order-card {
  background: rgba(255,255,255,.16); border-color: rgba(255,255,255,.22);
}
.dm-chat-order-card:hover { border-color: #fca5a5; }
.dm-chat-order-img {
  width: 48px; height: 48px; border-radius: 8px; object-fit: cover;
  background: #fff; flex-shrink: 0; border: 1px solid rgba(15,23,42,.08);
}
.dm-chat-order-img.is-empty {
  display: grid; place-items: center; color: #94a3b8; font-size: 11px; font-weight: 700;
}
.dm-chat-order-body { min-width: 0; flex: 1; }
.dm-chat-order-ref { display: block; font-size: 11px; font-weight: 700; opacity: .85; }
.dm-chat-order-product {
  display: block; margin-top: 2px; font-size: 12px; font-weight: 600;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.dm-chat-order-meta {
  display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: 4px;
}
.dm-chat-order-status { font-size: 10px; opacity: .8; }
.dm-chat-order-total { font-size: 11px; font-weight: 700; }
@media (max-width: 480px) {
  .dm-chat { right: 12px; bottom: 12px; }
  .dm-chat-panel { width: calc(100vw - 24px); height: min(72vh, 560px); }
}
</style>

<div class="dm-chat" id="appDirectChatRoot" x-data="appDirectFloatingChat(@js([
    'inboxUrl' => route('app-chat.index'),
    'unreadUrl' => route('app-chat.unread'),
    'presenceUrl' => route('app-chat.presence'),
    'peersUrl' => route('app-chat.peers'),
    'startUrl' => route('app-chat.start'),
    'messagesUrlTemplate' => route('app-chat.messages', ['chat' => '__ID__']),
    'storeUrlTemplate' => route('app-chat.store', ['chat' => '__ID__']),
    'showUrlTemplate' => route('app-chat.show', ['chat' => '__ID__']),
    'resellerMessagesUrlTemplate' => $isWarehouseDm ? route('warehouse.chat-reseller.messages', ['chat' => '__ID__']) : null,
    'resellerStoreUrlTemplate' => $isWarehouseDm ? route('warehouse.chat-reseller.store', ['chat' => '__ID__']) : null,
    'resellerShowUrlTemplate' => $isWarehouseDm ? route('warehouse.chat-reseller.show', ['chat' => '__ID__']) : null,
    'resellerOrderShowUrlTemplate' => $isWarehouseDm ? str_replace('999999999', '__ID__', route('warehouse.pesanan-reseller.show', ['order' => 999999999])) : null,
    'marketplaceMessagesUrlTemplate' => $isWarehouseDm ? route('warehouse.chat-marketplace.messages', ['chat' => '__ID__']) : null,
    'marketplaceStoreUrlTemplate' => $isWarehouseDm ? route('warehouse.chat-marketplace.store', ['chat' => '__ID__']) : null,
    'marketplaceShowUrlTemplate' => $isWarehouseDm ? route('warehouse.chat-marketplace.show', ['chat' => '__ID__']) : null,
    'csrf' => csrf_token(),
    'openChatId' => request()->integer('open_dm') ?: null,
    'openResellerChatId' => $isWarehouseDm ? (request()->integer('open_chat') ?: null) : null,
    'openMarketplaceChatId' => $isWarehouseDm ? (request()->integer('open_marketplace_chat') ?: null) : null,
    'initialUnread' => $directChatUnread,
    'selfProfile' => [
        'name' => $dmUser?->name ?? 'Saya',
        'avatar_url' => $dmUser?->fotoProfilUrl(),
        'has_avatar' => (bool) ($dmUser?->hasCustomFotoProfil() ?? false),
    ],
]))" x-init="boot()">
    <div class="dm-chat-panel" :class="open ? 'is-open' : ''">
        <div class="dm-chat-head">
            <button type="button" class="dm-chat-icon-btn" x-show="view !== 'list'" @click="backToList()" title="Kembali">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 19l-7-7 7-7"/></svg>
            </button>
            <div class="dm-chat-head-main">
                <p class="dm-chat-title" x-text="headerTitle()"></p>
                <p class="dm-chat-sub" x-text="headerSub()"></p>
            </div>
            <button type="button" class="dm-chat-icon-btn" @click="closePanel()" aria-label="Tutup">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="dm-chat-toolbar" x-show="view === 'list'" x-cloak>
            <button type="button" class="dm-chat-new-btn" @click="openPeers()">+ Mulai percakapan</button>
        </div>

        <div class="dm-chat-list" x-show="view === 'list'" x-cloak>
            <template x-for="chat in chats" :key="chat.list_key || (chat.type + ':' + chat.id)">
                <button type="button" class="dm-chat-item" :class="chat.unread > 0 ? 'is-unread' : ''" @click="openThread(chat)">
                    <div class="dm-chat-avatar-wrap">
                        <div class="dm-chat-avatar" :class="chat.has_avatar ? '' : 'is-logo'">
                            <img :src="chat.avatar_url || @js(asset('images/logo.png'))" alt="" loading="lazy">
                        </div>
                        <span class="dm-chat-online-dot" x-show="chat.online" title="Online"></span>
                    </div>
                    <div class="dm-chat-item-body">
                        <div class="dm-chat-item-top">
                            <span class="dm-chat-item-name" x-text="chat.title"></span>
                            <span class="dm-chat-item-time" x-text="chat.last_message_label"></span>
                        </div>
                        <p class="dm-chat-item-preview" x-text="chat.preview || chat.subtitle || '—'"></p>
                    </div>
                    <span class="dm-chat-item-badge" x-show="chat.unread > 0" x-text="chat.unread > 9 ? '9+' : chat.unread"></span>
                </button>
            </template>
            <p class="dm-chat-empty" x-show="!loadingList && chats.length === 0">Belum ada percakapan. Mulai chat dengan akun lain di aplikasi.</p>
            <p class="dm-chat-empty" x-show="loadingList">Memuat…</p>
        </div>

        <div class="dm-chat-search" x-show="view === 'peers'" x-cloak>
            <input type="search" x-model="peerQuery" @input.debounce.300ms="loadPeers()" placeholder="Cari nama, role, atau section…" autocomplete="off">
        </div>

        <div class="dm-chat-peers" x-show="view === 'peers'" x-cloak>
            <template x-for="peer in peers" :key="peer.key">
                <button type="button" class="dm-chat-item" @click="startPeer(peer)">
                    <div class="dm-chat-avatar-wrap">
                        <div class="dm-chat-avatar" :class="peer.has_avatar ? '' : 'is-logo'">
                            <img :src="peer.avatar_url || @js(asset('images/logo.png'))" alt="" loading="lazy">
                        </div>
                        <span class="dm-chat-online-dot" x-show="peer.online" title="Online"></span>
                    </div>
                    <div class="dm-chat-item-body">
                        <div class="dm-chat-item-top">
                            <span class="dm-chat-item-name" x-text="peer.title"></span>
                        </div>
                        <p class="dm-chat-item-preview">
                            <span x-text="peer.subtitle"></span>
                            <span class="dm-chat-online-label" x-show="peer.online"> · Online</span>
                        </p>
                    </div>
                </button>
            </template>
            <p class="dm-chat-empty" x-show="!loadingPeers && peers.length === 0">Semua kontak sudah ada di list chat, atau tidak ada hasil pencarian.</p>
            <p class="dm-chat-empty" x-show="loadingPeers">Memuat…</p>
        </div>

        <div class="dm-chat-messages chat-premium" x-show="view === 'thread'" x-ref="scroller" x-cloak>
            @include('partials.shared.chat-premium-thread')
            <p class="dm-chat-empty" x-show="!loadingThread && messages.length === 0">Belum ada pesan. Kirim yang pertama.</p>
        </div>

        <form class="dm-chat-compose" x-show="view === 'thread'" x-cloak @submit.prevent="send()">
            <div class="dm-chat-attach-preview" x-show="pendingFileName" x-cloak>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                <span x-text="pendingFileName"></span>
                <button type="button" @click="clearAttachment()">Hapus</button>
            </div>
            <input type="file" style="display:none" x-ref="fileInput" @change="onFilePicked($event)" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar,.csv,image/*">
            <button
                type="button"
                class="dm-chat-attach-btn"
                x-show="canAttachFile()"
                @click="$refs.fileInput?.click()"
                title="Lampirkan file"
                aria-label="Lampirkan file"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
            </button>
            <textarea x-model="draft" rows="1" maxlength="2000" placeholder="Tulis pesan…" enterkeyhint="send" autocomplete="off" @keydown="onComposeKeydown($event)"></textarea>
            <button type="submit" class="dm-chat-send" :disabled="sending || (!draft.trim() && !pendingFile)" aria-label="Kirim">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/></svg>
            </button>
        </form>
    </div>

    <button type="button" class="dm-chat-fab" :class="open ? 'is-open' : ''" @click="togglePanel()" aria-label="Chat" title="Chat">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        <span
            class="dm-chat-badge"
            :class="unread > 0 ? 'is-visible' : ''"
            @if($directChatUnread < 1) hidden @endif
            :hidden="unread < 1"
            x-text="unread > 9 ? '9+' : String(unread)"
        >{{ $directChatUnread > 9 ? '9+' : max(0, (int) $directChatUnread) }}</span>
    </button>
</div>

@include('partials.shared.chat-premium-style')
@include('partials.shared.chat-incoming-sound')
@include('partials.shared.chat-compose-enter')
@include('partials.shared.chat-premium-script')
<script>
function appDirectFloatingChat(cfg) {
    return {
        open: false,
        view: 'list',
        chats: [],
        peers: [],
        messages: [],
        activeChat: null,
        draft: '',
        pendingFile: null,
        pendingFileName: '',
        peerQuery: '',
        loadingList: false,
        loadingPeers: false,
        loadingThread: false,
        sending: false,
        pollTimer: null,
        presenceTimer: null,
        selfProfile: cfg.selfProfile || {},
        inboxUrl: cfg.inboxUrl,
        unreadUrl: cfg.unreadUrl,
        presenceUrl: cfg.presenceUrl,
        peersUrl: cfg.peersUrl,
        startUrl: cfg.startUrl,
        messagesUrlTemplate: cfg.messagesUrlTemplate,
        storeUrlTemplate: cfg.storeUrlTemplate,
        showUrlTemplate: cfg.showUrlTemplate,
        resellerMessagesUrlTemplate: cfg.resellerMessagesUrlTemplate,
        resellerStoreUrlTemplate: cfg.resellerStoreUrlTemplate,
        resellerShowUrlTemplate: cfg.resellerShowUrlTemplate,
        resellerOrderShowUrlTemplate: cfg.resellerOrderShowUrlTemplate,
        marketplaceMessagesUrlTemplate: cfg.marketplaceMessagesUrlTemplate,
        marketplaceStoreUrlTemplate: cfg.marketplaceStoreUrlTemplate,
        marketplaceShowUrlTemplate: cfg.marketplaceShowUrlTemplate,
        csrf: cfg.csrf,
        openChatId: cfg.openChatId,
        openResellerChatId: cfg.openResellerChatId,
        openMarketplaceChatId: cfg.openMarketplaceChatId,
        unread: Number(cfg.initialUnread || 0),

        boot() {
            if (window.ChatIncomingSound) {
                window.ChatIncomingSound.preload();
                window.ChatIncomingSound.syncUnread(this.unread);
            }
            this.touchPresence();
            this.presenceTimer = setInterval(() => this.touchPresence(), 45000);
            if (typeof document !== 'undefined') {
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) this.touchPresence();
                });
            }
            this.refreshUnread();
            this.pollTimer = setInterval(() => this.poll(), 5000);
            if (this.openMarketplaceChatId) {
                this.open = true;
                this.openThreadById(this.openMarketplaceChatId, 'marketplace_room');
            } else if (this.openResellerChatId) {
                this.open = true;
                this.openThreadById(this.openResellerChatId, 'reseller_room');
            } else if (this.openChatId) {
                this.open = true;
                this.openThreadById(this.openChatId, 'direct');
            }
        },

        isResellerRoom(chat) {
            return (chat && chat.type) === 'reseller_room';
        },

        isMarketplaceRoom(chat) {
            return (chat && chat.type) === 'marketplace_room';
        },

        isDedicatedRoom(chat) {
            return this.isResellerRoom(chat) || this.isMarketplaceRoom(chat);
        },

        messagesTemplate(chat) {
            if (this.isResellerRoom(chat)) return this.resellerMessagesUrlTemplate;
            if (this.isMarketplaceRoom(chat)) return this.marketplaceMessagesUrlTemplate;
            return this.messagesUrlTemplate;
        },

        storeTemplate(chat) {
            if (this.isResellerRoom(chat)) return this.resellerStoreUrlTemplate;
            if (this.isMarketplaceRoom(chat)) return this.marketplaceStoreUrlTemplate;
            return this.storeUrlTemplate;
        },

        showTemplate(chatType) {
            if (chatType === 'reseller_room') return this.resellerShowUrlTemplate;
            if (chatType === 'marketplace_room') return this.marketplaceShowUrlTemplate;
            return this.showUrlTemplate;
        },

        headerTitle() {
            if (this.view === 'thread') return this.activeChat?.title || 'Chat';
            if (this.view === 'peers') return 'Kontak';
            return 'Chat';
        },

        onComposeKeydown(event) {
            window.ChatComposeEnter?.handle(event, () => this.send());
        },

        headerSub() {
            if (this.view === 'thread') {
                const sub = this.activeChat?.subtitle || 'Percakapan';
                if (this.activeChat?.online) {
                    return sub + ' · Online';
                }
                return sub;
            }
            if (this.view === 'peers') return 'Semua kontak · hijau = sedang online';
            return 'Pesan antar akun';
        },

        async touchPresence() {
            if (!this.presenceUrl) return;
            try {
                await fetch(this.presenceUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
            } catch (e) {}
        },

        sortPeers(rows) {
            return (rows || []).slice().sort((a, b) => {
                const ao = a.online ? 1 : 0;
                const bo = b.online ? 1 : 0;
                if (bo !== ao) return bo - ao;
                return String(a.title || '').localeCompare(String(b.title || ''), 'id');
            });
        },

        urlFor(template, id) {
            return String(template).replace('__ID__', String(id));
        },

        togglePanel() {
            if (this.open) this.closePanel();
            else this.openPanel();
        },

        openPanel() {
            this.open = true;
            this.view = 'list';
            this.activeChat = null;
            this.loadInbox();
        },

        closePanel() {
            this.open = false;
            this.view = 'list';
            this.activeChat = null;
            this.refreshUnread();
        },

        backToList() {
            this.view = 'list';
            this.activeChat = null;
            this.messages = [];
            this.peerQuery = '';
            this.clearAttachment();
            this.loadInbox();
        },

        canAttachFile() {
            const type = this.activeChat?.type || 'direct';
            return type === 'direct' || type === 'reseller_room';
        },

        formatBytes(bytes) {
            const n = Number(bytes) || 0;
            if (n < 1024) return n + ' B';
            if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' KB';
            return (n / (1024 * 1024)).toFixed(1) + ' MB';
        },

        onFilePicked(event) {
            const file = event?.target?.files?.[0] || null;
            if (!file) {
                this.clearAttachment();
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                alert('Ukuran file maksimal 10 MB.');
                this.clearAttachment();
                return;
            }
            this.pendingFile = file;
            this.pendingFileName = file.name;
        },

        clearAttachment() {
            this.pendingFile = null;
            this.pendingFileName = '';
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';
        },

        noteUnread(next) {
            const value = window.ChatIncomingSound
                ? window.ChatIncomingSound.noteUnread(next)
                : Math.max(0, Number(next || 0));
            this.unread = value;
        },

        async refreshUnread() {
            try {
                const res = await fetch(this.unreadUrl + '?_=' + Date.now(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                if (!res.ok) return;
                const data = await res.json();
                this.noteUnread(Number(data.unread || 0));
            } catch (e) {}
        },

        async loadInbox() {
            this.loadingList = true;
            try {
                const res = await fetch(this.inboxUrl + '?_=' + Date.now(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                if (!res.ok) return;
                const data = await res.json();
                this.chats = data.chats || [];
                this.noteUnread(this.chats.reduce((sum, c) => sum + (Number(c.unread) || 0), 0));
            } catch (e) {
            } finally {
                this.loadingList = false;
            }
        },

        async openPeers() {
            this.view = 'peers';
            this.peerQuery = '';
            await this.loadPeers();
        },

        async loadPeers() {
            this.loadingPeers = true;
            try {
                const url = this.peersUrl + '?q=' + encodeURIComponent(this.peerQuery || '') + '&_=' + Date.now();
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                if (!res.ok) return;
                const data = await res.json();
                this.peers = this.sortPeers(data.peers || []);
            } catch (e) {
            } finally {
                this.loadingPeers = false;
            }
        },

        async startPeer(peer) {
            this.loadingThread = true;
            try {
                const res = await fetch(this.startUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ user_id: peer.user_id }),
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || 'Gagal membuka chat.');
                this.activeChat = data.chat || null;
                if (this.activeChat && peer.online !== undefined) {
                    this.activeChat.online = !!peer.online;
                }
                this.messages = this.normalizeMessages(data.messages || []);
                this.view = 'thread';
                this.draft = '';
                this.clearAttachment();
                this.$nextTick(() => this.scrollBottom());
            } catch (err) {
                alert(err.message || 'Gagal membuka chat.');
            } finally {
                this.loadingThread = false;
            }
        },

        async openThread(chat) {
            this.activeChat = chat;
            this.view = 'thread';
            this.draft = '';
            this.clearAttachment();
            await this.loadThread(true);
        },

        async openThreadById(id, type) {
            this.loadingThread = true;
            const template = this.showTemplate(type || 'direct');
            if (!template) {
                this.view = 'list';
                await this.loadInbox();
                this.loadingThread = false;
                return;
            }
            try {
                const res = await fetch(this.urlFor(template, id) + (type === 'reseller_room' || type === 'marketplace_room' ? '?json=1&_=' : '?_=') + Date.now(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                if (!res.ok) {
                    this.view = 'list';
                    await this.loadInbox();
                    return;
                }
                const data = await res.json();
                if (type === 'reseller_room') {
                    this.activeChat = {
                        id: Number(id),
                        type: 'reseller_room',
                        list_key: 'reseller_room:' + id,
                        title: data.chat?.reseller_name || 'Reseller',
                        subtitle: 'Reseller',
                        unread: 0,
                        avatar_url: data.chat?.avatar_url,
                        has_avatar: data.chat?.has_avatar,
                        online: !!data.chat?.online,
                    };
                } else if (type === 'marketplace_room') {
                    this.activeChat = {
                        id: Number(id),
                        type: 'marketplace_room',
                        list_key: 'marketplace_room:' + id,
                        title: data.chat?.customer_name || 'Customer',
                        subtitle: 'Marketplace',
                        unread: 0,
                        avatar_url: data.chat?.avatar_url,
                        has_avatar: data.chat?.has_avatar,
                        online: !!data.chat?.online,
                    };
                } else {
                    this.activeChat = data.chat || { id: Number(id), type: 'direct', title: 'Chat' };
                }
                if (this.activeChat && data.chat?.online !== undefined) {
                    this.activeChat.online = !!data.chat.online;
                }
                this.messages = this.normalizeMessages(data.messages || []);
                this.view = 'thread';
                this.$nextTick(() => this.scrollBottom());
                this.refreshUnread();
            } catch (e) {
                this.view = 'list';
            } finally {
                this.loadingThread = false;
            }
        },

        lastId() {
            return this.messages.length ? this.messages[this.messages.length - 1].id : 0;
        },

        scrollBottom() {
            const el = this.$refs.scroller;
            if (el) el.scrollTop = el.scrollHeight;
        },

        normalizeMessages(rows) {
            return (rows || []).map((m) => ({
                id: m.id,
                body: m.body || '',
                sender_name: m.sender_name || '',
                sender_avatar_url: m.sender_avatar_url || null,
                sender_has_avatar: !!m.sender_has_avatar,
                mine: !!m.mine,
                created_at: m.created_at || '',
                created_at_label: m.created_at_label || '',
                attachment_url: m.attachment_url || null,
                attachment_name: m.attachment_name || null,
                attachment_mime: m.attachment_mime || null,
                attachment_size: m.attachment_size || null,
                attachment_is_image: !!m.attachment_is_image,
                order: m.order || null,
            }));
        },

        threadItems() {
            return window.ChatPremium ? window.ChatPremium.threadItems(this.messages) : [];
        },

        rowClass(item) {
            if (!item || item.type !== 'msg' || !item.msg) return '';
            return (item.msg.mine ? 'is-mine' : '') + (item.isContinue ? ' is-continue' : '');
        },

        avatarFor(msg) {
            if (!msg || !window.ChatPremium) {
                return { hasPhoto: false, url: '', initials: '', name: '', online: false };
            }
            return window.ChatPremium.avatarState(msg, {
                selfProfile: this.selfProfile,
                peer: this.activeChat,
            });
        },

        timeFor(msg) {
            return window.ChatPremium ? window.ChatPremium.timeLabel(msg || {}) : '';
        },

        ticksHtml() {
            return window.ChatPremium ? window.ChatPremium.ticksSvg() : '';
        },

        orderUrl(orderId) {
            const template = this.resellerOrderShowUrlTemplate;
            if (!template) return '#';
            return String(template).replace('__ID__', String(orderId));
        },

        async loadThread(full) {
            if (!this.activeChat) return;
            const template = this.messagesTemplate(this.activeChat);
            if (!template) return;
            this.loadingThread = !!full;
            try {
                const after = full ? 0 : this.lastId();
                let url = this.urlFor(template, this.activeChat.id) + '?after_id=' + after + '&_=' + Date.now();
                if (full) url += '&mark_read=1';
                const res = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                if (!res.ok) return;
                const data = await res.json();
                const rows = this.normalizeMessages(data.messages || []);
                if (full) {
                    this.messages = rows;
                    const nextUnread = Math.max(0, this.unread - (Number(this.activeChat.unread) || 0));
                    if (this.activeChat) this.activeChat.unread = 0;
                    this.unread = nextUnread;
                    if (window.ChatIncomingSound) window.ChatIncomingSound.syncUnread(nextUnread);
                } else {
                    let hasIncoming = false;
                    rows.forEach((m) => {
                        if (!this.messages.some((x) => x.id === m.id)) {
                            this.messages.push(m);
                            if (!m.mine) hasIncoming = true;
                        }
                    });
                    if (hasIncoming && window.ChatIncomingSound) window.ChatIncomingSound.play();
                }
                this.$nextTick(() => this.scrollBottom());
            } catch (e) {
            } finally {
                this.loadingThread = false;
            }
        },

        async send() {
            const body = (this.draft || '').trim();
            const file = this.pendingFile;
            if ((!body && !file) || !this.activeChat || this.sending) return;
            const template = this.storeTemplate(this.activeChat);
            if (!template) return;

            const canAttach = this.canAttachFile();
            if (file && !canAttach) {
                alert('Lampiran file tidak tersedia untuk chat ini.');
                return;
            }

            this.sending = true;
            try {
                let res;
                if (file && canAttach) {
                    const fd = new FormData();
                    if (body) fd.append('body', body);
                    fd.append('attachment', file);
                    res = await fetch(this.urlFor(template, this.activeChat.id), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: fd,
                        credentials: 'same-origin',
                    });
                } else {
                    res = await fetch(this.urlFor(template, this.activeChat.id), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ body }),
                        credentials: 'same-origin',
                    });
                }
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const msg = data.message
                        || (data.errors ? Object.values(data.errors).flat().join(' ') : null)
                        || 'Gagal mengirim.';
                    throw new Error(msg);
                }
                if (data.message) {
                    const rows = this.normalizeMessages([data.message]);
                    if (rows[0]) this.messages.push(rows[0]);
                }
                this.draft = '';
                this.clearAttachment();
                this.$nextTick(() => this.scrollBottom());
            } catch (err) {
                alert(err.message || 'Gagal mengirim pesan.');
            } finally {
                this.sending = false;
            }
        },

        async poll() {
            if (!this.open) {
                await this.refreshUnread();
                return;
            }
            if (this.view === 'list') {
                await this.loadInbox();
                return;
            }
            if (this.view === 'peers') {
                await this.loadPeers();
                return;
            }
            if (this.view === 'thread') {
                await this.loadThread(false);
            }
        },
    };
}
</script>
@endif

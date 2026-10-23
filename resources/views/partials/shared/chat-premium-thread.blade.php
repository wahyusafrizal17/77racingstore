<template x-for="item in threadItems()" :key="item.key">
    <div class="chat-item">
        <div class="chat-day" x-show="item.type === 'day'" x-text="item.label"></div>
        <div class="chat-row" x-show="item.type === 'msg'" :class="rowClass(item)">
            <span class="chat-avatar-wrap">
                <span class="chat-avatar" :class="avatarFor(item.msg).hasPhoto ? '' : 'is-initials'">
                    <template x-if="item.type === 'msg' && avatarFor(item.msg).hasPhoto">
                        <img :src="avatarFor(item.msg).url" alt="">
                    </template>
                    <template x-if="item.type === 'msg' && !avatarFor(item.msg).hasPhoto">
                        <span x-text="avatarFor(item.msg).initials"></span>
                    </template>
                </span>
                <span class="chat-online" x-show="item.type === 'msg' && avatarFor(item.msg).online && !item.isContinue" title="Online"></span>
            </span>
            <div class="chat-col">
                <span class="chat-name" x-text="item.type === 'msg' ? avatarFor(item.msg).name : ''"></span>
                <div class="chat-inner">
                    <template x-if="item.type === 'msg' && item.msg.order">
                        <a class="dm-chat-order-card" :href="orderUrl(item.msg.order.id)">
                            <template x-if="item.msg.order.product_image_url">
                                <img class="dm-chat-order-img" :src="item.msg.order.product_image_url" alt="">
                            </template>
                            <template x-if="!item.msg.order.product_image_url">
                                <span class="dm-chat-order-img is-empty">#</span>
                            </template>
                            <span class="dm-chat-order-body">
                                <span class="dm-chat-order-ref" x-text="'Pesanan #' + (item.msg.order.reference || item.msg.order.id)"></span>
                                <span class="dm-chat-order-product" x-text="item.msg.order.no_resi ? ((item.msg.order.product_name || 'Produk pesanan') + ' · Resi: ' + item.msg.order.no_resi) : (item.msg.order.product_name || 'Produk pesanan')"></span>
                                <span class="dm-chat-order-meta">
                                    <span class="dm-chat-order-status" x-text="item.msg.order.status_label || ''"></span>
                                    <span class="dm-chat-order-total" x-text="item.msg.order.total_label || ''"></span>
                                </span>
                            </span>
                        </a>
                    </template>
                    <template x-if="item.type === 'msg' && item.msg.attachment_url && item.msg.attachment_is_image">
                        <a :href="item.msg.attachment_url" target="_blank" rel="noopener">
                            <img class="dm-chat-bubble-image" :src="item.msg.attachment_url" :alt="item.msg.attachment_name || 'Gambar'">
                        </a>
                    </template>
                    <template x-if="item.type === 'msg' && item.msg.attachment_url && !item.msg.attachment_is_image">
                        <a class="dm-chat-bubble-file" :href="item.msg.attachment_url" target="_blank" rel="noopener" download>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                            <span class="dm-chat-bubble-file-meta">
                                <span class="dm-chat-bubble-file-name" x-text="item.msg.attachment_name || 'File'"></span>
                                <span class="dm-chat-bubble-file-size" x-show="item.msg.attachment_size" x-text="formatBytes(item.msg.attachment_size)"></span>
                            </span>
                        </a>
                    </template>
                    <p class="chat-text" x-show="item.type === 'msg' && item.msg.body" x-text="item.type === 'msg' ? item.msg.body : ''"></p>
                    <span class="chat-time">
                        <span x-text="item.type === 'msg' ? timeFor(item.msg) : ''"></span>
                        <span x-show="item.type === 'msg' && item.msg.mine" x-html="ticksHtml()"></span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\ResellerOrder;
use App\Models\User;
use App\Models\WarehouseReseller;
use App\Models\WarehouseResellerChat;
use App\Models\WarehouseResellerChatMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarehouseResellerChatService
{
    /** @return Collection<int, User> */
    public function handlersForReseller(WarehouseReseller $reseller): Collection
    {
        return User::query()
            ->whereIn('role', [
                User::ROLE_MODIFIKASI_HEAD_MARKETING,
                User::ROLE_MODIFIKASI_STAF_MARKETING,
                User::ROLE_MODIFIKASI_STAF_PROCESSING_WAREHOUSE,
            ])
            ->orderByRaw('CASE role WHEN ? THEN 1 WHEN ? THEN 2 ELSE 3 END', [
                User::ROLE_MODIFIKASI_HEAD_MARKETING,
                User::ROLE_MODIFIKASI_STAF_MARKETING,
            ])
            ->orderBy('name')
            ->get()
            ->filter(
                fn (User $user) => (int) $user->activeWarehouseId() === (int) $reseller->warehouse_id
            )
            ->values();
    }

    public function getOrCreateForReseller(
        WarehouseReseller $reseller,
        ?User $handler = null,
    ): WarehouseResellerChat
    {
        $handler ??= $this->handlersForReseller($reseller)->first();
        if (
            ! $handler
            || ! $handler->canHandleExternalCustomerChats()
            || (int) $handler->activeWarehouseId() !== (int) $reseller->warehouse_id
        ) {
            throw new \InvalidArgumentException('Penerima chat tidak tersedia untuk reseller ini.');
        }

        $existing = WarehouseResellerChat::query()
            ->where('warehouse_reseller_id', $reseller->id)
            ->where('handler_user_id', $handler->id)
            ->first();
        if ($existing) {
            return $existing;
        }

        $legacy = WarehouseResellerChat::query()
            ->where('warehouse_reseller_id', $reseller->id)
            ->whereNull('handler_user_id')
            ->first();
        if ($legacy) {
            $legacy->update(['handler_user_id' => $handler->id]);

            return $legacy->fresh();
        }

        return WarehouseResellerChat::query()->firstOrCreate(
            [
                'warehouse_reseller_id' => $reseller->id,
                'handler_user_id' => $handler->id,
            ],
            [
                'warehouse_id' => (int) $reseller->warehouse_id,
                'reseller_last_read_at' => now(),
                'warehouse_last_read_at' => now(),
            ],
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function serializeMessages(WarehouseResellerChat $chat, string $viewerSide, ?int $afterId = null, int $limit = 80): Collection
    {
        $chat->loadMissing([
            'handler:id,name,foto_profil',
            'warehouseReseller.user:id,name,foto_profil,warehouse_reseller_id',
        ]);

        $query = $chat->messages()
            ->with([
                'user:id,name,foto_profil',
                'order.items.product:id,nama_produk,gambar_path',
            ])
            ->orderByDesc('id')
            ->limit($limit);

        if ($afterId !== null && $afterId > 0) {
            $query->where('id', '>', $afterId)->reorder('id');
        }

        $rows = $query->get();
        if ($afterId === null || $afterId < 1) {
            $rows = $rows->sortBy('id')->values();
        }

        return $rows->map(fn (WarehouseResellerChatMessage $m) => $this->serializeMessage($m, $viewerSide, $chat));
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeMessage(WarehouseResellerChatMessage $message, string $viewerSide, ?WarehouseResellerChat $chat = null): array
    {
        $chat ??= $message->chat;
        $chat?->loadMissing([
            'handler:id,name,foto_profil',
            'warehouseReseller.user:id,name,foto_profil,warehouse_reseller_id',
        ]);

        $sender = $message->user;
        if (! $sender) {
            $sender = $message->sender_side === WarehouseResellerChatMessage::SIDE_RESELLER
                ? $chat?->warehouseReseller?->user
                : $chat?->handler;
        }

        $name = (string) ($sender?->name ?: (
            $message->sender_side === WarehouseResellerChatMessage::SIDE_RESELLER
                ? 'Reseller'
                : 'Warehouse'
        ));

        $orderPayload = null;
        if ($message->reseller_order_id) {
            $order = $message->relationLoaded('order') ? $message->order : null;
            if ($order) {
                $orderPayload = $this->serializeOrderCard($order);
            }
        }

        return [
            'id' => (int) $message->id,
            'side' => (string) $message->sender_side,
            'body' => (string) $message->body,
            'message_type' => (string) ($message->message_type ?: WarehouseResellerChatMessage::TYPE_TEXT),
            'sender_name' => $name,
            'sender_avatar_url' => $sender?->fotoProfilUrl(),
            'sender_has_avatar' => (bool) ($sender?->hasCustomFotoProfil() ?? false),
            'mine' => $message->sender_side === $viewerSide,
            'created_at' => $message->created_at?->toIso8601String() ?? '',
            'created_at_label' => $message->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?? '',
            'attachment_url' => $message->attachmentUrl(),
            'attachment_name' => $message->attachment_name,
            'attachment_mime' => $message->attachment_mime,
            'attachment_size' => $message->attachment_size !== null ? (int) $message->attachment_size : null,
            'attachment_is_image' => $message->attachmentIsImage(),
            'order' => $orderPayload,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function ordersForPicker(WarehouseReseller $reseller, ?string $search = null, int $limit = 30): Collection
    {
        $q = trim((string) $search);

        return ResellerOrder::query()
            ->with(['items.product:id,nama_produk,gambar_path'])
            ->where('warehouse_reseller_id', $reseller->id)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('kode_pesanan', 'like', '%'.$q.'%')
                        ->orWhere('no_urut_harian', 'like', '%'.$q.'%')
                        ->orWhere('no_resi', 'like', '%'.$q.'%')
                        ->orWhere('no_pesanan_marketplace', 'like', '%'.$q.'%')
                        ->orWhereHas('items.product', fn ($pq) => $pq->where('nama_produk', 'like', '%'.$q.'%'));
                });
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (ResellerOrder $order) => $this->serializeOrderCard($order))
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeOrderCard(ResellerOrder $order): array
    {
        $order->loadMissing(['items.product:id,nama_produk,gambar_path']);
        $firstItem = $order->items->first();
        $product = $firstItem?->product;
        $itemCount = $order->items->count();
        $productName = (string) ($product?->nama_produk ?? 'Produk pesanan');
        if ($itemCount > 1) {
            $productName .= ' +'.($itemCount - 1).' lainnya';
        }

        $total = (int) $order->total_nominal;
        $noResi = trim((string) ($order->no_resi ?? ''));

        return [
            'id' => (int) $order->id,
            'kode_pesanan' => (string) $order->kode_pesanan,
            'reference' => $order->displayOrderReference(),
            'no_urut_harian' => $order->no_urut_harian !== null ? (int) $order->no_urut_harian : null,
            'no_resi' => $noResi !== '' ? $noResi : null,
            'status' => (string) $order->status,
            'status_label' => $order->statusLabel(),
            'marketplace' => (string) ($order->marketplace ?? ''),
            'marketplace_label' => $order->marketplaceLabel(),
            'total_nominal' => $total,
            'total_label' => 'Rp '.number_format($total, 0, ',', '.'),
            'product_name' => $productName,
            'product_image_url' => $product?->gambarUrl(),
            'created_at_label' => $order->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?? '',
        ];
    }

    public function send(
        WarehouseResellerChat $chat,
        User $user,
        string $senderSide,
        string $body,
        ?UploadedFile $attachment = null,
        ?ResellerOrder $order = null,
    ): WarehouseResellerChatMessage {
        $body = trim($body);
        if ($body === '' && $attachment === null && $order === null) {
            throw new \InvalidArgumentException('Pesan, file, atau pesanan wajib diisi.');
        }
        if (mb_strlen($body) > 2000) {
            throw new \InvalidArgumentException('Pesan maksimal 2000 karakter.');
        }

        if ($order !== null) {
            if ((int) $order->warehouse_reseller_id !== (int) $chat->warehouse_reseller_id) {
                throw new \InvalidArgumentException('Pesanan tidak valid untuk chat ini.');
            }
        }

        $stored = null;
        if ($attachment !== null) {
            $stored = $this->storeAttachment($attachment, (int) $chat->id);
        }

        try {
            return DB::transaction(function () use ($chat, $user, $senderSide, $body, $stored, $order) {
                /** @var WarehouseResellerChat $locked */
                $locked = WarehouseResellerChat::query()->whereKey($chat->id)->lockForUpdate()->firstOrFail();

                $messageType = $order
                    ? WarehouseResellerChatMessage::TYPE_ORDER
                    : WarehouseResellerChatMessage::TYPE_TEXT;

                $message = WarehouseResellerChatMessage::query()->create([
                    'chat_id' => $locked->id,
                    'user_id' => $user->id,
                    'sender_side' => $senderSide,
                    'body' => $body !== '' ? $body : '',
                    'message_type' => $messageType,
                    'reseller_order_id' => $order?->id,
                    'attachment_path' => $stored['path'] ?? null,
                    'attachment_name' => $stored['name'] ?? null,
                    'attachment_mime' => $stored['mime'] ?? null,
                    'attachment_size' => $stored['size'] ?? null,
                ]);

                if ($order) {
                    $previewSource = '🛒 Pesanan #'.$order->displayOrderReference()
                        .($body !== '' ? ' · '.$body : '');
                } elseif ($body !== '') {
                    $previewSource = $body;
                } else {
                    $previewSource = '📎 '.($stored['name'] ?? 'File');
                }
                $preview = Str::limit(preg_replace('/\s+/u', ' ', $previewSource) ?? $previewSource, 120, '…');
                $locked->update([
                    'last_message_at' => $message->created_at,
                    'last_message_preview' => $preview,
                ]);

                if ($senderSide === WarehouseResellerChatMessage::SIDE_RESELLER) {
                    $locked->update(['reseller_last_read_at' => $message->created_at]);
                    $this->notifyWarehouse($locked, $preview);
                } else {
                    $locked->update(['warehouse_last_read_at' => $message->created_at]);
                    $this->notifyReseller($locked, $preview);
                }

                return $message->load([
                    'user:id,name',
                    'order.items.product:id,nama_produk,gambar_path',
                ]);
            });
        } catch (\Throwable $e) {
            if ($stored && ! empty($stored['path'])) {
                \App\Support\UploadStorage::delete($stored['path']);
            }
            throw $e;
        }
    }

    /**
     * @return array{path: string, name: string, mime: string, size: int}
     */
    private function storeAttachment(UploadedFile $file, int $chatId): array
    {
        if (! $file->isValid()) {
            throw new \InvalidArgumentException('File tidak valid atau gagal diunggah.');
        }

        $dir = 'reseller-chat/'.$chatId;
        try {
            $path = \App\Support\UploadStorage::store($file, $dir);
        } catch (\League\Flysystem\UnableToWriteFile) {
            throw new \InvalidArgumentException('Gagal menyimpan file.');
        }

        return [
            'path' => $path,
            'name' => Str::limit((string) ($file->getClientOriginalName() ?: 'file'), 240, ''),
            'mime' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
            'size' => (int) $file->getSize(),
        ];
    }

    public function markRead(WarehouseResellerChat $chat, string $side): void
    {
        $column = $side === WarehouseResellerChatMessage::SIDE_RESELLER
            ? 'reseller_last_read_at'
            : 'warehouse_last_read_at';

        $chat->update([$column => now()]);
    }

    private function notifyWarehouse(WarehouseResellerChat $chat, string $preview): void
    {
        $chat->loadMissing(['warehouseReseller', 'handler']);
        $resellerName = $chat->warehouseReseller?->nama ?? 'Reseller';

        $users = $chat->handler
            ? collect([$chat->handler])
            : $this->handlersForReseller($chat->warehouseReseller);

        foreach ($users as $user) {
            AppNotification::query()->create([
                'user_id' => $user->id,
                'type' => 'warehouse_reseller_chat',
                'title' => 'Chat dari '.$resellerName,
                'message' => $preview,
                'data' => [
                    'chat_id' => $chat->id,
                    'warehouse_reseller_id' => $chat->warehouse_reseller_id,
                ],
                'link' => route($user->dashboardRoute(), ['open_chat' => $chat->id]),
            ]);
        }
    }

    private function notifyReseller(WarehouseResellerChat $chat, string $preview): void
    {
        $chat->loadMissing('warehouseReseller:id,nama');

        $recipientIds = User::query()
            ->where(function ($query) use ($chat) {
                $query->where(function ($q) use ($chat) {
                    $q->where('section', User::SECTION_RESELLER)
                        ->where('warehouse_reseller_id', $chat->warehouse_reseller_id);
                })->orWhere(function ($q) use ($chat) {
                    $q->where('section', User::SECTION_MO_OPERATOR)
                        ->whereHas('moOperatorChannels', fn ($mq) => $mq->where('warehouse_reseller_id', $chat->warehouse_reseller_id));
                });
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($recipientIds->isEmpty()) {
            return;
        }

        foreach ($recipientIds as $userId) {
            $user = User::query()->find($userId);
            if (! $user) {
                continue;
            }

            $link = $user->isMoOperator()
                ? route('mo-operator.katalog.index', ['chat' => 1])
                : route('reseller.katalog.index', ['chat' => 1]);

            AppNotification::query()->create([
                'user_id' => $userId,
                'type' => 'reseller_chat',
                'title' => 'Balasan dari Warehouse',
                'message' => $preview,
                'data' => ['chat_id' => $chat->id],
                'link' => $link,
            ]);
        }
    }
}

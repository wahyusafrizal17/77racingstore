<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use App\Models\WarehouseMarketplaceChat;
use App\Models\WarehouseMarketplaceChatMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarehouseMarketplaceChatService
{
    /** @return Collection<int, User> */
    public function handlersForCustomer(User $customer, int $warehouseId): Collection
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
                fn (User $handler) => (int) $handler->activeWarehouseId() === $warehouseId
                    && $handler->canHandleExternalCustomerChats()
            )
            ->values();
    }

    public function getOrCreateForCustomer(
        User $customer,
        int $warehouseId,
        ?User $handler = null,
    ): WarehouseMarketplaceChat {
        $handler ??= $this->handlersForCustomer($customer, $warehouseId)->first();
        if (
            ! $handler
            || ! $handler->canHandleExternalCustomerChats()
            || (int) $handler->activeWarehouseId() !== $warehouseId
        ) {
            throw new \InvalidArgumentException('Penerima chat tidak tersedia untuk customer ini.');
        }

        $existing = WarehouseMarketplaceChat::query()
            ->where('user_id', $customer->id)
            ->where('handler_user_id', $handler->id)
            ->first();
        if ($existing) {
            return $existing;
        }

        $legacy = WarehouseMarketplaceChat::query()
            ->where('user_id', $customer->id)
            ->whereNull('handler_user_id')
            ->first();
        if ($legacy) {
            $legacy->update(['handler_user_id' => $handler->id]);

            return $legacy->fresh();
        }

        return WarehouseMarketplaceChat::query()->firstOrCreate(
            [
                'user_id' => $customer->id,
                'handler_user_id' => $handler->id,
            ],
            [
                'warehouse_id' => $warehouseId,
                'customer_last_read_at' => now(),
                'warehouse_last_read_at' => now(),
            ],
        );
    }

    /**
     * @return Collection<int, array{id: int, side: string, body: string, sender_name: string, mine: bool, created_at: string, created_at_label: string}>
     */
    public function serializeMessages(WarehouseMarketplaceChat $chat, string $viewerSide, ?int $afterId = null, int $limit = 80): Collection
    {
        $chat->loadMissing([
            'handler:id,name,foto_profil',
            'customer:id,name,foto_profil',
        ]);

        $query = $chat->messages()
            ->with('user:id,name,foto_profil')
            ->orderByDesc('id')
            ->limit($limit);

        if ($afterId !== null && $afterId > 0) {
            $query->where('id', '>', $afterId)->reorder('id');
        }

        $rows = $query->get();
        if ($afterId === null || $afterId < 1) {
            $rows = $rows->sortBy('id')->values();
        }

        return $rows->map(fn (WarehouseMarketplaceChatMessage $m) => $this->serializeMessage($m, $viewerSide, $chat));
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeMessage(WarehouseMarketplaceChatMessage $message, string $viewerSide, ?WarehouseMarketplaceChat $chat = null): array
    {
        $chat ??= $message->chat;
        $chat?->loadMissing([
            'handler:id,name,foto_profil',
            'customer:id,name,foto_profil',
        ]);

        $sender = $message->user;
        if (! $sender) {
            $sender = $message->sender_side === WarehouseMarketplaceChatMessage::SIDE_CUSTOMER
                ? $chat?->customer
                : $chat?->handler;
        }

        $name = (string) ($sender?->name ?: (
            $message->sender_side === WarehouseMarketplaceChatMessage::SIDE_CUSTOMER
                ? 'Customer'
                : 'Warehouse'
        ));

        return [
            'id' => (int) $message->id,
            'side' => (string) $message->sender_side,
            'body' => (string) $message->body,
            'sender_name' => $name,
            'sender_avatar_url' => $sender?->fotoProfilUrl(),
            'sender_has_avatar' => (bool) ($sender?->hasCustomFotoProfil() ?? false),
            'mine' => $message->sender_side === $viewerSide,
            'created_at' => $message->created_at?->toIso8601String() ?? '',
            'created_at_label' => $message->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?? '',
        ];
    }

    public function send(
        WarehouseMarketplaceChat $chat,
        User $user,
        string $senderSide,
        string $body,
    ): WarehouseMarketplaceChatMessage {
        $body = trim($body);
        if ($body === '') {
            throw new \InvalidArgumentException('Pesan tidak boleh kosong.');
        }
        if (mb_strlen($body) > 2000) {
            throw new \InvalidArgumentException('Pesan maksimal 2000 karakter.');
        }

        return DB::transaction(function () use ($chat, $user, $senderSide, $body) {
            /** @var WarehouseMarketplaceChat $locked */
            $locked = WarehouseMarketplaceChat::query()->whereKey($chat->id)->lockForUpdate()->firstOrFail();

            $message = WarehouseMarketplaceChatMessage::query()->create([
                'chat_id' => $locked->id,
                'user_id' => $user->id,
                'sender_side' => $senderSide,
                'body' => $body,
            ]);

            $preview = Str::limit(preg_replace('/\s+/u', ' ', $body) ?? $body, 120, '…');
            $locked->update([
                'last_message_at' => $message->created_at,
                'last_message_preview' => $preview,
            ]);

            if ($senderSide === WarehouseMarketplaceChatMessage::SIDE_CUSTOMER) {
                $locked->update(['customer_last_read_at' => $message->created_at]);
                $this->notifyWarehouse($locked, $preview);
            } else {
                $locked->update(['warehouse_last_read_at' => $message->created_at]);
                $this->notifyCustomer($locked, $preview);
            }

            return $message->load('user:id,name');
        });
    }

    public function markRead(WarehouseMarketplaceChat $chat, string $side): void
    {
        $column = $side === WarehouseMarketplaceChatMessage::SIDE_CUSTOMER
            ? 'customer_last_read_at'
            : 'warehouse_last_read_at';

        $chat->update([$column => now()]);
    }

    private function notifyWarehouse(WarehouseMarketplaceChat $chat, string $preview): void
    {
        $chat->loadMissing(['customer:id,name', 'handler']);
        $customerName = $chat->customer?->name ?? 'Customer Marketplace';

        $users = $chat->handler
            ? collect([$chat->handler])
            : $this->handlersForCustomer($chat->customer, (int) $chat->warehouse_id);

        foreach ($users as $user) {
            AppNotification::query()->create([
                'user_id' => $user->id,
                'type' => 'warehouse_marketplace_chat',
                'title' => 'Chat marketplace: '.$customerName,
                'message' => $preview,
                'data' => [
                    'chat_id' => $chat->id,
                    'user_id' => $chat->user_id,
                ],
                'link' => route($user->dashboardRoute(), ['open_marketplace_chat' => $chat->id]),
            ]);
        }
    }

    private function notifyCustomer(WarehouseMarketplaceChat $chat, string $preview): void
    {
        AppNotification::query()->create([
            'user_id' => (int) $chat->user_id,
            'type' => 'marketplace_chat',
            'title' => 'Balasan dari Warehouse',
            'message' => $preview,
            'data' => ['chat_id' => $chat->id],
            'link' => route('marketplace.katalog.index', ['chat' => 1]),
        ]);
    }
}

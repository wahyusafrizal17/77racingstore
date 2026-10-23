<?php

namespace App\Services;

use App\Models\AppDirectChat;
use App\Models\AppDirectChatMessage;
use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AppDirectChatService
{
    public function __construct(
        private readonly UserPresenceService $presence,
    ) {}
    public function getOrCreateBetween(User $a, User $b): AppDirectChat
    {
        if ((int) $a->id === (int) $b->id) {
            throw new \InvalidArgumentException('Tidak bisa chat dengan diri sendiri.');
        }

        if (! $this->canChatWith($a, $b)) {
            throw new \InvalidArgumentException('Anda tidak dapat memulai chat dengan akun ini.');
        }

        $low = min((int) $a->id, (int) $b->id);
        $high = max((int) $a->id, (int) $b->id);

        return AppDirectChat::query()->firstOrCreate(
            ['pair_key' => 'dm:'.$low.':'.$high],
            [
                'user_low_id' => $low,
                'user_high_id' => $high,
            ],
        );
    }

    public function assertCanAccess(User $user, AppDirectChat $chat): void
    {
        abort_unless($chat->involvesUser((int) $user->id), 403);

        $chat->loadMissing(['userLow:id,section', 'userHigh:id,section']);
        $peer = (int) $chat->user_low_id === (int) $user->id
            ? $chat->userHigh
            : $chat->userLow;

        abort_unless($peer !== null && $this->canChatWith($user, $peer), 403);
    }

    /**
     * Section yang boleh dikontak oleh user ini.
     * Chat dibuka untuk semua karyawan (tanpa batasan section).
     *
     * @return list<string>
     */
    public function allowedPeerSections(User $user): array
    {
        return [
            'speedshop',
            'warehouse',
            'produksi',
            'reseller',
            'owner',
            'admin',
            User::SECTION_MODIFIKASI_ORI,
            User::SECTION_MARKETPLACE,
        ];
    }

    public function canChatWith(User $viewer, User $peer): bool
    {
        if ((int) $viewer->id === (int) $peer->id) {
            return false;
        }

        // Chat terbuka: siapa pun boleh chat dengan akun lain.
        return true;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function inboxFor(User $user): Collection
    {
        $uid = (int) $user->id;

        $items = AppDirectChat::query()
            ->with(['userLow:id,name,section,role,foto_profil', 'userHigh:id,name,section,role,foto_profil'])
            ->where(function ($q) use ($uid) {
                $q->where('user_low_id', $uid)->orWhere('user_high_id', $uid);
            })
            ->whereNotNull('last_message_at')
            ->orderByDesc('last_message_at')
            ->limit(80)
            ->get()
            ->filter(function (AppDirectChat $chat) use ($user) {
                $peer = (int) $chat->user_low_id === (int) $user->id
                    ? $chat->userHigh
                    : $chat->userLow;

                return $peer !== null && $this->canChatWith($user, $peer);
            })
            ->map(fn (AppDirectChat $chat) => $this->serializeInboxItem($chat, $user));

        if ($user->canHandleExternalCustomerChats()) {
            $items = $items
                ->concat($this->warehouseResellerInbox($user))
                ->concat($this->warehouseMarketplaceInbox($user));
        }

        return $items
            ->sortByDesc(fn (array $row) => $row['last_message_at'] ?? '')
            ->values();
    }

    private function warehouseResellerInbox(User $user): Collection
    {
        return collect();
    }

    private function warehouseMarketplaceInbox(User $user): Collection
    {
        return collect();
    }

    public function warehouseResellerUnread(User $user): int
    {
        return 0;
    }

    public function warehouseMarketplaceUnread(User $user): int
    {
        return 0;
    }

    /**
     * User IDs that already appear in the chat inbox.
     *
     * @return list<int>
     */
    public function inboxPeerIds(User $user): array
    {
        $uid = (int) $user->id;

        $ids = AppDirectChat::query()
            ->where(function ($q) use ($uid) {
                $q->where('user_low_id', $uid)->orWhere('user_high_id', $uid);
            })
            ->whereNotNull('last_message_at')
            ->get(['user_low_id', 'user_high_id'])
            ->map(function (AppDirectChat $chat) use ($uid) {
                return $chat->otherUserId($uid);
            })
            ->all();

        return array_values(array_unique($ids));
    }

    /**
     * Semua kontak karyawan (minus diri sendiri dan yang sudah ada di inbox).
     *
     * @return list<array<string, mixed>>
     */
    public function peersFor(User $user, string $q = ''): array
    {
        $excludeIds = array_values(array_unique(array_merge(
            [(int) $user->id],
            $this->inboxPeerIds($user),
        )));

        $query = User::query()
            ->when(
                ! $user->canHandleExternalCustomerChats(),
                fn ($q) => $q->adminKaryawan(),
            )
            ->whereNotIn('id', $excludeIds)
            ->orderBy('name')
            ->limit(200);

        $q = trim($q);
        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhere('role', 'like', '%'.$q.'%')
                    ->orWhere('section', 'like', '%'.$q.'%');
            });
        }

        return $query
            ->get(['id', 'name', 'email', 'section', 'role', 'foto_profil'])
            ->pipe(function ($users) {
                $onlineMap = $this->presence->onlineMapFor($users->pluck('id')->all());

                return $users->map(fn (User $u) => $this->serializePeer($u, (bool) ($onlineMap[(int) $u->id] ?? false)));
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function serializePeer(User $user, ?bool $online = null): array
    {
        if ($online === null) {
            $online = $this->presence->isOnline((int) $user->id);
        }

        return [
            'key' => 'user:'.$user->id,
            'type' => 'direct',
            'user_id' => (int) $user->id,
            'title' => (string) $user->name,
            'subtitle' => $this->sectionLabel($user),
            'avatar_url' => $user->fotoProfilUrl(),
            'has_avatar' => $user->hasCustomFotoProfil(),
            'online' => $online,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeInboxItem(AppDirectChat $chat, User $viewer): array
    {
        $chat->loadMissing(['userLow:id,name,section,role,foto_profil', 'userHigh:id,name,section,role,foto_profil']);
        $other = (int) $chat->user_low_id === (int) $viewer->id
            ? $chat->userHigh
            : $chat->userLow;

        return [
            'id' => (int) $chat->id,
            'type' => 'direct',
            'list_key' => 'direct:'.$chat->id,
            'peer_user_id' => (int) ($other?->id ?? 0),
            'title' => (string) ($other?->name ?? 'Pengguna'),
            'subtitle' => $other ? $this->sectionLabel($other) : '',
            'preview' => (string) ($chat->last_message_preview ?? ''),
            'last_message_at' => $chat->last_message_at?->toIso8601String(),
            'last_message_label' => $chat->last_message_at?->timezone(config('app.timezone'))->format('d M H:i') ?? '',
            'unread' => $chat->unreadFor((int) $viewer->id),
            'avatar_url' => $other?->fotoProfilUrl() ?? asset('images/logo.png'),
            'has_avatar' => (bool) ($other?->hasCustomFotoProfil() ?? false),
            'online' => $other ? $this->presence->isOnline((int) $other->id) : false,
        ];
    }

    public function sectionLabel(User $user): string
    {
        $section = match ($user->section) {
            'speedshop' => 'Speedshop',
            'warehouse' => 'Warehouse',
            'produksi' => 'Produksi',
            'admin' => 'Admin',
            'reseller' => 'Reseller',
            'owner' => 'Owner',
            User::SECTION_MODIFIKASI_ORI, 'modifikasi_ori' => '77racing',
            User::SECTION_MARKETPLACE => 'Marketplace',
            default => ucfirst((string) $user->section),
        };

        $role = (string) ($user->role ?? '');

        return $role !== '' ? $section.' · '.$role : $section;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function serializeMessages(AppDirectChat $chat, User $viewer, ?int $afterId = null, int $limit = 80): Collection
    {
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

        return $rows->map(fn (AppDirectChatMessage $m) => $this->serializeMessage($m, $viewer));
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeMessage(AppDirectChatMessage $message, User $viewer): array
    {
        return [
            'id' => (int) $message->id,
            'body' => (string) ($message->body ?? ''),
            'sender_name' => (string) ($message->user?->name ?? 'Pengguna'),
            'sender_avatar_url' => $message->user?->fotoProfilUrl(),
            'sender_has_avatar' => (bool) ($message->user?->hasCustomFotoProfil() ?? false),
            'mine' => (int) ($message->user_id ?? 0) === (int) $viewer->id,
            'created_at' => $message->created_at?->toIso8601String() ?? '',
            'created_at_label' => $message->created_at?->timezone(config('app.timezone'))->format('d M Y H:i') ?? '',
            'attachment_url' => $message->attachmentUrl(),
            'attachment_name' => $message->attachment_name,
            'attachment_mime' => $message->attachment_mime,
            'attachment_size' => $message->attachment_size,
            'attachment_is_image' => $message->attachmentIsImage(),
        ];
    }

    public function send(
        AppDirectChat $chat,
        User $sender,
        string $body,
        ?\Illuminate\Http\UploadedFile $attachment = null,
    ): AppDirectChatMessage {
        $this->assertCanAccess($sender, $chat);

        $body = trim($body);
        if ($body === '' && $attachment === null) {
            throw new \InvalidArgumentException('Pesan atau file wajib diisi.');
        }
        if (mb_strlen($body) > 2000) {
            throw new \InvalidArgumentException('Pesan maksimal 2000 karakter.');
        }

        $stored = null;
        if ($attachment !== null) {
            $stored = $this->storeAttachment($attachment, (int) $chat->id);
        }

        try {
            return DB::transaction(function () use ($chat, $sender, $body, $stored) {
                /** @var AppDirectChat $locked */
                $locked = AppDirectChat::query()->whereKey($chat->id)->lockForUpdate()->firstOrFail();

                $message = AppDirectChatMessage::query()->create([
                    'chat_id' => $locked->id,
                    'user_id' => $sender->id,
                    'body' => $body !== '' ? $body : '',
                    'attachment_path' => $stored['path'] ?? null,
                    'attachment_name' => $stored['name'] ?? null,
                    'attachment_mime' => $stored['mime'] ?? null,
                    'attachment_size' => $stored['size'] ?? null,
                ]);

                $previewSource = $body !== ''
                    ? $body
                    : ('📎 '.($stored['name'] ?? 'File'));
                $preview = Str::limit(preg_replace('/\s+/u', ' ', $previewSource) ?? $previewSource, 120, '…');
                $locked->update([
                    'last_message_at' => $message->created_at,
                    'last_message_preview' => $preview,
                ]);

                $this->markRead($locked, $sender);
                $this->notifyPeer($locked, $sender, $preview);

                return $message->load('user:id,name');
            });
        } catch (\Throwable $e) {
            if ($stored && ! empty($stored['path'])) {
                \Illuminate\Support\Facades\Storage::disk((string) config('filesystems.upload_disk', 'public'))
                    ->delete($stored['path']);
            }
            throw $e;
        }
    }

    /**
     * @return array{path: string, name: string, mime: string, size: int}
     */
    private function storeAttachment(\Illuminate\Http\UploadedFile $file, int $chatId): array
    {
        if (! $file->isValid()) {
            throw new \InvalidArgumentException('File tidak valid atau gagal diunggah.');
        }

        $disk = (string) config('filesystems.upload_disk', 'public');
        $dir = 'direct-chat/'.$chatId;
        $path = $file->store($dir, $disk);
        if (! $path) {
            throw new \InvalidArgumentException('Gagal menyimpan file.');
        }

        return [
            'path' => $path,
            'name' => Str::limit((string) ($file->getClientOriginalName() ?: 'file'), 240, ''),
            'mime' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
            'size' => (int) $file->getSize(),
        ];
    }

    public function markRead(AppDirectChat $chat, User $user): void
    {
        $this->assertCanAccess($user, $chat);

        if ((int) $chat->user_low_id === (int) $user->id) {
            $chat->update(['low_last_read_at' => now()]);
        } else {
            $chat->update(['high_last_read_at' => now()]);
        }
    }

    public function unreadTotal(User $user): int
    {
        return (int) $this->inboxFor($user)->sum(fn (array $row) => (int) ($row['unread'] ?? 0));
    }

    private function notifyPeer(AppDirectChat $chat, User $sender, string $preview): void
    {
        $peerId = $chat->otherUserId((int) $sender->id);
        $peer = User::query()->find($peerId);
        if (! $peer) {
            return;
        }

        $routeName = $peer->dashboardRoute();
        $link = route($routeName, ['open_dm' => $chat->id]);

        AppNotification::query()->create([
            'user_id' => $peer->id,
            'type' => 'app_direct_chat',
            'title' => 'Chat dari '.$sender->name,
            'message' => $preview,
            'data' => [
                'chat_id' => $chat->id,
                'from_user_id' => $sender->id,
            ],
            'link' => $link,
        ]);
    }
}

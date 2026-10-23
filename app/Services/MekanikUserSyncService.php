<?php

namespace App\Services;

use App\Models\Mekanik;
use App\Models\MekanikLevel;
use App\Models\User;

class MekanikUserSyncService
{
    public static function syncFromUser(User $user, ?int $mekanikLevelId, ?string $noHp = null): ?Mekanik
    {
        if (! $user->isSpeedshopMekanik()) {
            self::detachUser($user);

            return null;
        }

        if (! $mekanikLevelId) {
            return null;
        }

        $level = MekanikLevel::query()->find($mekanikLevelId);
        $attrs = [
            'nama' => $user->name,
            'no_hp' => $noHp !== '' ? $noHp : null,
            'mekanik_level_id' => $mekanikLevelId,
            'spesialisasi' => $level?->nama,
            'speedshop_id' => $user->speedshop_id,
            'status' => 'aktif',
            'user_id' => $user->id,
        ];

        // Pertahankan riwayat WO: pakai baris mekanik lama (nama + cabang) bila ada.
        $legacy = Mekanik::query()
            ->where('speedshop_id', $user->speedshop_id)
            ->whereRaw('LOWER(nama) = ?', [mb_strtolower(trim((string) $user->name))])
            ->orderByRaw('CASE WHEN user_id = ? THEN 0 WHEN user_id IS NULL THEN 1 ELSE 2 END', [$user->id])
            ->orderBy('id')
            ->first();

        if ($legacy) {
            // Hapus/lepas duplikat mekanik yang terlanjur dibuat untuk user baru.
            Mekanik::query()
                ->where('user_id', $user->id)
                ->where('id', '!=', $legacy->id)
                ->get()
                ->each(function (Mekanik $dup) {
                    if ($dup->serviceOrders()->exists()) {
                        $dup->update([
                            'user_id' => null,
                            'status' => 'nonaktif',
                        ]);
                    } else {
                        $dup->delete();
                    }
                });

            $legacy->update($attrs);

            return $legacy->fresh();
        }

        return Mekanik::query()->updateOrCreate(
            ['user_id' => $user->id],
            $attrs,
        );
    }

    public static function detachUser(User $user): void
    {
        $mekanik = Mekanik::query()->where('user_id', $user->id)->first();

        if (! $mekanik) {
            return;
        }

        if ($mekanik->serviceOrders()->exists()) {
            $mekanik->update([
                'status' => 'nonaktif',
                'user_id' => null,
            ]);

            return;
        }

        $mekanik->delete();
    }

    /**
     * Lepas link login agar user bisa dihapus saat sinkron import,
     * tanpa menghapus baris mekanik yang masih punya riwayat/WO.
     */
    public static function releaseUserForSyncDeletion(User $user): void
    {
        self::detachUser($user);
    }

    public static function deleteForUser(User $user): ?string
    {
        $mekanik = Mekanik::query()->where('user_id', $user->id)->first();

        if (! $mekanik) {
            return null;
        }

        if ($mekanik->serviceOrders()->whereIn('status', ['antri', 'proses'])->exists()) {
            return 'Mekanik sedang dipakai pada work order aktif, tidak bisa dihapus.';
        }

        $mekanik->delete();

        return null;
    }
}

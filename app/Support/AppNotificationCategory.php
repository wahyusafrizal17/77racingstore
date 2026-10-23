<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AppNotificationCategory
{
    public const VERIFIKASI = 'verifikasi';

    public const UMUM = 'umum';

    /** @deprecated Use UMUM */
    public const MUTASI = 'mutasi';

    /** @deprecated Use UMUM */
    public const PO_STOK = 'po_stok';

    /** @deprecated Use UMUM */
    public const CHAT = 'chat';

    public const RESELLER = 'reseller';

    public const MARKETPLACE = 'marketplace';

    /** @var list<string> */
    private const MUTASI_TYPES = [
        'mutasi_produk',
        'mutasi_diterima',
        'mutasi_ditolak',
    ];

    /** @var list<string> */
    private const PO_STOK_TYPES = [
        'speedshop_purchase_order_pending',
        'warehouse_po_review_rejected',
        'warehouse_po_cancelled',
        'transaksi_offline_rejected',
        'transaksi_offline_speedshop',
        'claim_produk_cacat',
    ];

    /** @var list<string> */
    private const CHAT_TYPES = [
        'warehouse_reseller_chat',
        'warehouse_marketplace_chat',
        'app_direct_chat',
        'speedshop_chat',
    ];

    /**
     * @return list<string>
     */
    public static function keysForUser(User $user): array
    {
        if ($user->isReseller() || $user->section === 'reseller') {
            return [self::RESELLER];
        }

        if ($user->isMarketplaceCustomer() || $user->section === User::SECTION_MARKETPLACE) {
            return [self::MARKETPLACE];
        }

        return [self::VERIFIKASI, self::UMUM];
    }

    /**
     * @return array<string, array{label: string, title: string, icon: string, badge_class: string, button_class: string}>
     */
    public static function definitionsForUser(User $user): array
    {
        if ($user->isReseller() || $user->section === 'reseller') {
            return [
                self::RESELLER => [
                    'label' => 'Pesanan',
                    'title' => 'Notifikasi Pesanan',
                    'icon' => 'bell',
                    'badge_class' => 'bg-red-500',
                    'button_class' => 'hover:text-red-600',
                ],
            ];
        }

        if ($user->isMarketplaceCustomer() || $user->section === User::SECTION_MARKETPLACE) {
            return [
                self::MARKETPLACE => [
                    'label' => 'Pesanan',
                    'title' => 'Notifikasi Pesanan',
                    'icon' => 'bell',
                    'badge_class' => 'bg-red-500',
                    'button_class' => 'hover:text-red-600',
                ],
            ];
        }

        return [
            self::VERIFIKASI => [
                'label' => 'Verifikasi',
                'title' => 'Notifikasi Verifikasi',
                'icon' => 'shield',
                'badge_class' => 'bg-amber-500',
                'button_class' => 'hover:text-amber-600',
            ],
            self::UMUM => [
                'label' => 'Umum',
                'title' => 'Notifikasi Umum',
                'icon' => 'bell',
                'badge_class' => 'bg-blue-500',
                'button_class' => 'hover:text-blue-600',
            ],
        ];
    }

    public static function isValid(string $category): bool
    {
        return in_array($category, [
            self::VERIFIKASI,
            self::UMUM,
            self::MUTASI,
            self::PO_STOK,
            self::CHAT,
            self::RESELLER,
            self::MARKETPLACE,
        ], true);
    }

    /**
     * @return list<string>
     */
    private static function umumTypes(): array
    {
        return array_merge(self::MUTASI_TYPES, self::PO_STOK_TYPES, self::CHAT_TYPES);
    }

    /**
     * @param  Builder<\App\Models\AppNotification>  $query
     */
    public static function applyScope(Builder $query, string $category): void
    {
        if ($category === self::UMUM) {
            $query->whereIn('type', self::umumTypes());

            return;
        }

        if ($category === self::MUTASI) {
            $query->whereIn('type', self::MUTASI_TYPES);

            return;
        }

        if ($category === self::PO_STOK) {
            $query->whereIn('type', self::PO_STOK_TYPES);

            return;
        }

        if ($category === self::CHAT) {
            $query->whereIn('type', self::CHAT_TYPES);

            return;
        }

        if ($category === self::RESELLER) {
            $query->where(function (Builder $inner) {
                $inner->where('type', 'like', 'reseller_%')
                    ->orWhere('type', 'app_direct_chat');
            });

            return;
        }

        if ($category === self::MARKETPLACE) {
            $query->where('type', 'like', 'marketplace_%');

            return;
        }

        $query->where(function (Builder $inner) {
            $inner->whereNotIn('type', self::umumTypes())
                ->where('type', 'not like', 'reseller_%')
                ->where('type', 'not like', 'marketplace_%');
        });
    }
}

<?php

namespace App\Support;

use App\Models\AppNotification;
use App\Models\User;

class NotificationLinkResolver
{
    public static function resolve(AppNotification $notif, User $user): ?string
    {
        $link = self::normalizeToRelativePath($notif->link);

        if ($link === null) {
            $link = self::fallbackLink($notif);
        }

        if ($link === null) {
            return null;
        }

        $isWarehouseContext = $user->section === 'warehouse' || session('admin_section') === 'warehouse';
        $isSpeedshopContext = $user->section === 'speedshop' || session('admin_section') === 'speedshop';
        $isResellerContext = $user->section === 'reseller' || session('admin_section') === 'reseller';

        if ($isWarehouseContext) {
            $link = self::resolveForWarehouse($notif, $link);
        }

        if ($isSpeedshopContext) {
            $link = self::resolveForSpeedshop($notif, $link) ?? $link;
        }

        if ($isResellerContext) {
            $link = self::resolveForReseller($notif, $link) ?? $link;
        }

        return $link;
    }

    public static function normalizeToRelativePath(?string $link): ?string
    {
        if ($link === null || $link === '' || $link === '#') {
            return null;
        }

        if (! str_starts_with($link, 'http://') && ! str_starts_with($link, 'https://')) {
            return $link;
        }

        $path = parse_url($link, PHP_URL_PATH) ?? '/';
        $query = parse_url($link, PHP_URL_QUERY);

        return $query ? $path.'?'.$query : $path;
    }

    private static function fallbackLink(AppNotification $notif): ?string
    {
        if ($notif->type !== 'speedshop_diskon_verifikasi_pending') {
            return null;
        }

        $soId = is_array($notif->data) ? ($notif->data['service_order_id'] ?? null) : null;
        if ($soId) {
            return route('speedshop.verifikasi.diskon-work-order.show', (int) $soId);
        }

        return route('speedshop.verifikasi.diskon-work-order.index');
    }

    private static function resolveForSpeedshop(AppNotification $notif, string $link): ?string
    {
        if ($notif->type === 'speedshop_diskon_verifikasi_pending') {
            $soId = is_array($notif->data) ? ($notif->data['service_order_id'] ?? null) : null;
            if ($soId) {
                return route('speedshop.verifikasi.diskon-work-order.show', (int) $soId);
            }

            return route('speedshop.verifikasi.diskon-work-order.index');
        }

        return $link;
    }

    private static function resolveForReseller(AppNotification $notif, ?string $link): ?string
    {
        if (! str_starts_with((string) $notif->type, 'reseller_')) {
            return $link;
        }

        $orderId = is_array($notif->data) ? ($notif->data['reseller_order_id'] ?? null) : null;
        if ($orderId) {
            return route('reseller.pesanan.show', (int) $orderId);
        }

        return $link;
    }

    private static function resolveForWarehouse(AppNotification $notif, string $link): string
    {
        if ($notif->type === 'speedshop_purchase_order_pending') {
            return route('warehouse.transaksi-speedshop');
        }

        if (in_array($notif->type, [
            'warehouse_mutasi_produksi_pending',
            'warehouse_mutasi_produksi_approved',
            'warehouse_mutasi_produksi_rejected',
            'warehouse_reseller_order_edited',
            'warehouse_reseller_order_cancelled',
            'warehouse_reseller_cancel_pending',
            'mutasi_diterima',
            'mutasi_ditolak',
        ], true)) {
            $mutasiId = is_array($notif->data) ? ($notif->data['mutasi_id'] ?? null) : null;
            if ($mutasiId) {
                return route('warehouse.mutasi-ke-produksi.show', (int) $mutasiId);
            }

            $orderId = is_array($notif->data) ? ($notif->data['reseller_order_id'] ?? null) : null;
            if ($orderId && in_array($notif->type, [
                'warehouse_reseller_order_edited',
                'warehouse_reseller_order_cancelled',
            ], true)) {
                return route('warehouse.pesanan-reseller.show', (int) $orderId);
            }
        }

        if ($notif->type === 'mutasi_produk') {
            $link = preg_replace(
                '#/warehouse/transaksi-speedshop/(\d+)#',
                '/warehouse/mutasi-masuk/$1',
                $link
            ) ?? $link;

            $link = preg_replace(
                '#/speedshop/stock-in/transaksi-offline/(\d+)#',
                '/warehouse/transaksi-offline/$1',
                $link
            ) ?? $link;

            $mutasiId = is_array($notif->data) ? ($notif->data['mutasi_id'] ?? null) : null;
            if ($mutasiId && str_contains($link, '/produksi/mutasi-masuk/')) {
                return route('warehouse.mutasi-ke-produksi.show', (int) $mutasiId);
            }
        }

        return $link;
    }
}

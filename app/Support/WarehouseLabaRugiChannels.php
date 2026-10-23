<?php

namespace App\Support;

/**
 * Struktur akun & saluran penjualan laporan laba rugi warehouse (selaras template Excel).
 */
final class WarehouseLabaRugiChannels
{
    /** @var list<array{key: string, suffix: string, label: string, patterns: list<string>}> */
    public const SALES = [
        ['key' => 'shopee_mod', 'suffix' => '1', 'label' => 'SHOPEE 77racing', 'patterns' => ['shopee modifikasi ori', 'shopee modifikasiori', 'shopee 77racing', 'shopee modif', 'shopee']],
        ['key' => 'tiktok_racing', 'suffix' => '3', 'label' => 'TIKTOK MODIF ORI RACING', 'patterns' => ['tiktok modif ori racing', 'tiktok mod or ori racing', 'tiktok racing', 'tt m.o.r', 'm.o - tt m.o.r']],
        ['key' => 'tiktok_mod', 'suffix' => '2', 'label' => 'TIKTOK 77racing', 'patterns' => ['tiktok modifikasi ori', 'tiktok modifikasiori', 'tiktok 77racing', 'tiktok modif', 'm.o - tt m.o', 'tt m.o', 'tiktok']],
        ['key' => 'lazada_racing', 'suffix' => '5', 'label' => 'LAZADA MODIF ORI RACING', 'patterns' => ['lazada modif ori racing', 'lazada mod or ori racing', 'lazada racing', 'lazada m.o.r', 'm.o - lazada m.o.r']],
        ['key' => 'lazada_mod', 'suffix' => '4', 'label' => 'LAZADA 77racing', 'patterns' => ['lazada modifikasi ori', 'lazada modifikasiori', 'lazada 77racing', 'lazada modif', 'lazada']],
        ['key' => 'blibli_mod', 'suffix' => '6', 'label' => 'BLIBLI 77racing', 'patterns' => ['blibli modifikasi ori', 'blibli modifikasiori', 'blibli 77racing', 'blibli']],
        ['key' => 'reseller_offline', 'suffix' => '7', 'label' => 'RESELLER OFFLINE 77racing', 'patterns' => []],
        ['key' => 'reseller_canvassing', 'suffix' => '8', 'label' => 'RESELLER CANVASING 77racing', 'patterns' => []],
        ['key' => 'reseller_online', 'suffix' => '9', 'label' => 'RESELLER ONLINE 77racing', 'patterns' => []],
        ['key' => 'speedshop', 'suffix' => '10', 'label' => 'SPEEDSHOP', 'patterns' => []],
    ];

    /** @var list<array{no: string, label: string}> */
    public const HPP_RETURNS = [
        ['no' => '5.1.11', 'label' => 'RETURN MARKETPLACE'],
        ['no' => '5.1.12', 'label' => 'RETURN RESELLER ONLINE'],
        ['no' => '5.1.13', 'label' => 'RETURN SPEEDSHOP'],
    ];

    /** @var list<array{no: string, label: string}> */
    public const BIAYA_OPERASIONAL = [
        ['no' => '5.4.1', 'label' => 'GAJI KARYAWAN'],
        ['no' => '5.4.2', 'label' => 'THR KARYAWAN'],
        ['no' => '5.4.3', 'label' => 'BIAYA OPERASIONAL KANTOR'],
        ['no' => '5.4.4', 'label' => 'BIAYA PROMOSI & MARKETING'],
        ['no' => '5.4.5', 'label' => 'BIAYA OPERASIONAL PRODUKSI'],
        ['no' => '5.4.6', 'label' => 'MERCHANDISE'],
        ['no' => '5.4.7', 'label' => 'SEWA BANGUNAN'],
        ['no' => '5.4.8', 'label' => 'PEMBELIAN & PERAWATAN INVENTARIS KANTOR'],
        ['no' => '5.4.9', 'label' => 'RENOVASI BANGUNAN'],
        ['no' => '5.4.10', 'label' => 'BIAYA ADMIN BANK'],
        ['no' => '5.4.11', 'label' => 'BIAYA PACKING ONLINE'],
    ];

    /** @var list<array{no: string, label: string}> */
    public const BIAYA_NON_OPERASIONAL = [
        ['no' => '5.5.1', 'label' => 'PENGEMBALIAN DANA MARKETPLACE'],
        ['no' => '5.5.2', 'label' => 'ONGKIR KIRIM ULANG & COD'],
        ['no' => '5.5.3', 'label' => 'BIAYA KERUGIAN'],
        ['no' => '5.5.4', 'label' => 'BONUS OWNER RESELLER'],
        ['no' => '5.5.5', 'label' => 'PROGRAM THR / REWARDS RESELLER 2026'],
        ['no' => '5.5.6', 'label' => 'PROGRAM INFAQ 2026 (SUMUR BOR)'],
        ['no' => '5.5.7', 'label' => 'PROGRAM KARYAWAN 2026'],
        ['no' => '5.5.8', 'label' => 'PROGRAM LIBURAN TIM ADMIN & OWNER RESELLER 2026'],
        ['no' => '5.5.9', 'label' => 'BONUS BULANAN TEAM MANAGAMENT (MEI 2026)'],
    ];

    /** Parent no akun 5.4.x yang punya sub rincian di form biaya operasional. */
    public const BIAYA_OPERASIONAL_SUB_PARENTS = ['5.4.3', '5.4.4', '5.4.5', '5.4.11'];

    /** @var array<string, list<array{no: string, label: string}>> */
    public const BIAYA_OPERASIONAL_SUB = [
        '5.4.3' => [
            ['no' => '5.4.3.1', 'label' => 'Atk Packing'],
            ['no' => '5.4.3.2', 'label' => 'Atk Accounting'],
            ['no' => '5.4.3.3', 'label' => 'Isi Bahan Bakar (BBM)'],
            ['no' => '5.4.3.4', 'label' => 'Biaya Tol (E-Toll)'],
            ['no' => '5.4.3.5', 'label' => 'Internet / Kuota Tim Packing'],
            ['no' => '5.4.3.6', 'label' => 'Internet / Kuota Tim Accounting'],
            ['no' => '5.4.3.7', 'label' => 'Kartu Halo / Pulsa Operasional'],
            ['no' => '5.4.3.8', 'label' => 'Token Listrik Kantor'],
            ['no' => '5.4.3.9', 'label' => 'Iuran Kebersihan'],
            ['no' => '5.4.3.10', 'label' => 'Perlengkapan Kebersihan Kantor'],
            ['no' => '5.4.3.11', 'label' => 'Konsumsi Lembur'],
            ['no' => '5.4.3.12', 'label' => 'Konsumsi Kantor'],
            ['no' => '5.4.3.13', 'label' => 'Trashbag'],
            ['no' => '5.4.3.14', 'label' => 'Kantong Plastik'],
            ['no' => '5.4.3.15', 'label' => 'Tambah Angin / Service'],
            ['no' => '5.4.3.16', 'label' => 'Pdam'],
            ['no' => '5.4.3.17', 'label' => 'Lainnya'],
            ['no' => '5.4.3.18', 'label' => 'P3K Kantor'],
            ['no' => '5.4.3.19', 'label' => 'Web Server'],
            ['no' => '5.4.3.20', 'label' => 'Akomodasi Pengiriman Barang'],
        ],
        '5.4.4' => [
            ['no' => '5.4.4.1', 'label' => 'Sponsor / Event'],
            ['no' => '5.4.4.2', 'label' => 'Biaya Promo / Flashsale'],
            ['no' => '5.4.4.3', 'label' => 'Wifi / Kuota Internet'],
            ['no' => '5.4.4.4', 'label' => 'Marketing / Sosial Media'],
            ['no' => '5.4.4.5', 'label' => 'Perlengkapan Team Konten'],
            ['no' => '5.4.4.6', 'label' => 'Aplikasi Berlangganan'],
            ['no' => '5.4.4.7', 'label' => 'Perlengkapan Team Design'],
            ['no' => '5.4.4.8', 'label' => 'Program Komisi Affiliate Internal'],
            ['no' => '5.4.4.9', 'label' => 'Subsidi Iklan Reseller'],
            ['no' => '5.4.4.10', 'label' => 'Lainnya'],
        ],
        '5.4.5' => [
            ['no' => '5.4.5.1', 'label' => 'Tools / Perlengkapan Produksi'],
            ['no' => '5.4.5.2', 'label' => 'Apd & Perlengkapan Kerja'],
            ['no' => '5.4.5.3', 'label' => 'Perawatan & Service Mesin'],
            ['no' => '5.4.5.4', 'label' => 'Token Listrik'],
            ['no' => '5.4.5.5', 'label' => 'Konsumsi'],
            ['no' => '5.4.5.6', 'label' => 'Perlengkapan Kebersihan'],
            ['no' => '5.4.5.7', 'label' => 'Wifi / Kuota Internet'],
            ['no' => '5.4.5.8', 'label' => 'Atk Produksi'],
            ['no' => '5.4.5.9', 'label' => 'Lainnya'],
            ['no' => '5.4.5.10', 'label' => 'Plastik Wrapping'],
            ['no' => '5.4.5.11', 'label' => 'Kertas Thermal'],
            ['no' => '5.4.5.12', 'label' => 'P3K'],
        ],
        '5.4.11' => [
            ['no' => '5.4.11.1', 'label' => 'Dus Packing'],
            ['no' => '5.4.11.2', 'label' => 'Kertas Thermal'],
            ['no' => '5.4.11.3', 'label' => 'Lakban Coklat / Fragile / Bubble Wrap'],
            ['no' => '5.4.11.4', 'label' => 'Lainnya'],
        ],
    ];

    /** Kunci saluran marketplace (untuk matching transaksi online). */
    public const MARKETPLACE_KEYS = [
        'shopee_mod',
        'tiktok_mod',
        'tiktok_racing',
        'lazada_mod',
        'lazada_racing',
        'blibli_mod',
    ];

    /** Urutan & label tampilan laporan Rekap Penjualan marketplace. */
    public const REKAP_PENJUALAN = [
        ['key' => 'shopee_mod', 'label' => 'SHOPEE'],
        ['key' => 'lazada_mod', 'label' => 'LAZADA 77racing'],
        ['key' => 'lazada_racing', 'label' => 'LAZADA MODIF ORI RACING'],
        ['key' => 'tiktok_mod', 'label' => 'TIKTOK 77racing'],
        ['key' => 'tiktok_racing', 'label' => 'TIKTOK MODIF ORI RACING'],
        ['key' => 'blibli_mod', 'label' => 'BLIBLI'],
    ];

    public static function salesChannelByKey(string $key): ?array
    {
        foreach (self::SALES as $channel) {
            if ($channel['key'] === $key) {
                return $channel;
            }
        }

        return null;
    }

    public static function accountNoForSalesSuffix(string $suffix, string $section): string
    {
        return match ($section) {
            'pendapatan' => '4.1.'.$suffix,
            'hpp' => '5.1.'.$suffix,
            'marketplace' => '5.2.'.$suffix,
            'iklan' => '5.3.'.$suffix,
            default => '',
        };
    }

    public static function resolveOnlineChannelKey(?string $resellerName): string
    {
        $normalized = self::normalizeName($resellerName);
        if ($normalized === '') {
            return 'reseller_online';
        }

        foreach (self::SALES as $channel) {
            if (! in_array($channel['key'], self::MARKETPLACE_KEYS, true)) {
                continue;
            }
            foreach ($channel['patterns'] as $pattern) {
                if ($pattern !== '' && str_contains($normalized, $pattern)) {
                    return $channel['key'];
                }
            }
        }

        return 'reseller_online';
    }

    /**
     * SQL CASE untuk channel marketplace — selaras resolveOnlineChannelKey().
     */
    public static function sqlOnlineChannelKeyExpr(string $namaColumn = 'warehouse_resellers.nama'): string
    {
        $normalized = 'LOWER(TRIM('.$namaColumn.'))';
        $whens = [];

        foreach (self::SALES as $channel) {
            if (! in_array($channel['key'], self::MARKETPLACE_KEYS, true)) {
                continue;
            }
            foreach ($channel['patterns'] as $pattern) {
                if ($pattern === '') {
                    continue;
                }
                $escaped = str_replace(['\\', '%', '_', "'"], ['\\\\', '\\%', '\\_', "''"], $pattern);
                $whens[] = "WHEN {$normalized} LIKE '%{$escaped}%' THEN '{$channel['key']}'";
            }
        }

        if ($whens === []) {
            return "'reseller_online'";
        }

        return 'CASE '.implode(' ', $whens)." ELSE 'reseller_online' END";
    }

    public static function normalizeName(?string $name): string
    {
        $text = mb_strtolower(trim((string) $name));

        return preg_replace('/\s+/', ' ', $text) ?? '';
    }

    public static function isOperasionalSubAccount(string $noAkun): bool
    {
        return (bool) preg_match('/^5\.4\.(3|4|5|11)\.\d+/', $noAkun);
    }

    /** @return list<string> */
    public static function operasionalParentNosWithSubs(): array
    {
        return self::BIAYA_OPERASIONAL_SUB_PARENTS;
    }

    /** @return list<array{no: string, nama_akun: string}> */
    public static function seederAccounts(): array
    {
        $rows = [];

        foreach (self::SALES as $channel) {
            $suffix = $channel['suffix'];
            $label = $channel['label'];
            $rows[] = [self::accountNoForSalesSuffix($suffix, 'marketplace'), 'BIAYA MARKETPLACE '.$label];
            $rows[] = [self::accountNoForSalesSuffix($suffix, 'iklan'), 'BIAYA IKLAN '.$label];
        }

        foreach (self::HPP_RETURNS as $row) {
            $rows[] = [$row['no'], $row['label']];
        }

        foreach (self::BIAYA_OPERASIONAL as $row) {
            $rows[] = [$row['no'], $row['label']];
        }

        foreach (self::BIAYA_OPERASIONAL_SUB as $subs) {
            foreach ($subs as $sub) {
                $rows[] = [$sub['no'], $sub['label']];
            }
        }

        foreach (self::BIAYA_NON_OPERASIONAL as $row) {
            $rows[] = [$row['no'], $row['label']];
        }

        return $rows;
    }
}

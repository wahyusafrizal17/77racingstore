<?php

namespace App\Support\Warehouse;

class ProduksiMutasiTipe
{
    public const BAHAN_BAKU = 'bahan_baku';

    public const PERLENGKAPAN_PRODUKSI = 'perlengkapan_produksi';

    public const KEMASAN = 'kemasan';

    public const BAHAN_SIAP_PRODUKSI = 'bahan_siap_produksi';

    public static function labels(): array
    {
        return [
            self::BAHAN_BAKU => 'Bahan Baku',
            self::PERLENGKAPAN_PRODUKSI => 'Perlengkapan Produksi',
            self::KEMASAN => 'Kemasan',
            self::BAHAN_SIAP_PRODUKSI => 'Bahan Setengah Jadi',
        ];
    }

    public static function values(): array
    {
        return array_keys(self::labels());
    }

    public static function label(?string $tipe): string
    {
        if ($tipe === null || $tipe === '') {
            return 'Produk Produksi';
        }

        return self::labels()[$tipe] ?? $tipe;
    }

    public static function isValid(?string $tipe): bool
    {
        return $tipe !== null && array_key_exists($tipe, self::labels());
    }
}

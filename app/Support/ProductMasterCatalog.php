<?php

namespace App\Support;

use App\Models\Product;

/**
 * Lima katalog master produk speedshop / warehouse.
 */
final class ProductMasterCatalog
{
    public const MO = 'mo';

    public const SPEEDSHOP_SUPPLIER = 'speedshop_supplier';

    public const PART_LUAR = 'part_luar';

    public const MERCHANDISE = 'merchandise';

    public const CONSUMABLE = 'consumable';

    /** @return list<string> */
    public static function kinds(): array
    {
        return [
            self::MO,
            self::SPEEDSHOP_SUPPLIER,
            self::PART_LUAR,
            self::MERCHANDISE,
            self::CONSUMABLE,
        ];
    }

    /** @return list<string> */
    public static function warehouseSharedKinds(): array
    {
        return [self::MO, self::MERCHANDISE, self::CONSUMABLE];
    }

    public static function isWarehouseSharedKind(string $kind): bool
    {
        return in_array($kind, self::warehouseSharedKinds(), true);
    }

    /**
     * Ekspresi SQL HPP speedshop per produk, selaras kolom HPP di Master Produk:
     * katalog MO / merchandise / consumable memakai harga_jual_speedshop ("HPP Speedshop"), lainnya products.hpp.
     */
    public static function speedshopHppSql(string $alias = 'products'): string
    {
        $partLuar = Product::JENIS_PART_LUAR;
        $merchandise = Product::JENIS_MERCHANDISE;
        $consumable = Product::JENIS_CONSUMABLE;
        $isShared = "({$alias}.jenis_produk IN ('{$merchandise}', '{$consumable}')"
            ." OR (COALESCE({$alias}.jenis_produk, '') <> '{$partLuar}' AND COALESCE({$alias}.speedshop_scope_id, 0) = 0))";

        return "(CASE WHEN {$isShared}"
            ." THEN COALESCE(NULLIF({$alias}.harga_jual_speedshop, 0), NULLIF({$alias}.hpp, 0), {$alias}.harga_pembelian, 0)"
            ." ELSE COALESCE(NULLIF({$alias}.hpp, 0), {$alias}.harga_pembelian, 0) END)";
    }

    /**
     * Klasifikasi baris produk ke salah satu katalog master.
     */
    public static function kindForProduct(Product $product): string
    {
        $jenis = $product->jenis_produk ?? Product::JENIS_ORI;

        return match ($jenis) {
            Product::JENIS_PART_LUAR => self::PART_LUAR,
            Product::JENIS_MERCHANDISE => self::MERCHANDISE,
            Product::JENIS_CONSUMABLE => self::CONSUMABLE,
            default => ((int) ($product->speedshop_scope_id ?? 0)) > 0
                ? self::SPEEDSHOP_SUPPLIER
                : self::MO,
        };
    }

    public static function jenisProdukFor(string $kind): string
    {
        return match ($kind) {
            self::PART_LUAR => Product::JENIS_PART_LUAR,
            self::MERCHANDISE => Product::JENIS_MERCHANDISE,
            self::CONSUMABLE => Product::JENIS_CONSUMABLE,
            default => Product::JENIS_ORI,
        };
    }

    public static function scopeIdForKind(string $kind, int $speedshopId): int
    {
        return self::isWarehouseSharedKind($kind) ? 0 : (int) $speedshopId;
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::MO => 'Sparepart Modifikasi Ori',
            self::SPEEDSHOP_SUPPLIER => 'Sparepart Speedshop',
            self::PART_LUAR => 'Sparepart Luar',
            self::MERCHANDISE => 'Merchandise',
            self::CONSUMABLE => 'Consumable',
        ];
    }

    public static function label(string $kind): string
    {
        return self::labels()[$kind] ?? $kind;
    }

    public static function pickerBadge(string $kind): string
    {
        return match ($kind) {
            self::MO => 'Modifikasi Ori',
            self::SPEEDSHOP_SUPPLIER => 'Speedshop',
            self::PART_LUAR => 'Part Luar',
            self::MERCHANDISE => 'Merch',
            self::CONSUMABLE => 'Consumable',
            default => $kind,
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function pickerOptgroups(): array
    {
        return array_map(
            fn (string $kind) => ['value' => $kind, 'label' => self::label($kind)],
            self::kinds(),
        );
    }

    public static function subtitle(string $kind): string
    {
        return match ($kind) {
            self::MO => 'Sparepart yang dipesan ke Modifikasi Ori.',
            self::SPEEDSHOP_SUPPLIER => 'Sparepart outlet dari Stok In Supplier.',
            self::PART_LUAR => 'Sparepart pembelian luar',
            self::MERCHANDISE => 'Merchandise',
            self::CONSUMABLE => 'Consumable',
            default => '',
        };
    }

    public static function menuIconPath(string $kind): string
    {
        return match ($kind) {
            self::SPEEDSHOP_SUPPLIER => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
            self::MERCHANDISE => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
            self::CONSUMABLE => 'M9 3h6l1 4H8l1-4zM8 7h8v13a2 2 0 01-2 2H10a2 2 0 01-2-2V7z',
            default => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        };
    }

    public static function stokRouteBase(string $kind): string
    {
        return match ($kind) {
            self::SPEEDSHOP_SUPPLIER => 'speedshop.stok-produk-speedshop',
            self::PART_LUAR => 'speedshop.stok-part-luar',
            self::MERCHANDISE => 'speedshop.stok-merchandise',
            self::CONSUMABLE => 'speedshop.stok-consumable',
            default => 'speedshop.stok-produk-mo',
        };
    }

    /** @return list<string> */
    public static function stokRoutePatterns(): array
    {
        return array_merge(
            array_map(fn (string $kind) => self::stokRouteBase($kind).'*', self::kinds()),
            ['speedshop.stok-produk'],
        );
    }

    public static function stokExportFilenamePrefix(string $kind): string
    {
        return match ($kind) {
            self::SPEEDSHOP_SUPPLIER => 'stok-produk-speedshop-supplier',
            self::PART_LUAR => 'stok-part-luar',
            self::MERCHANDISE => 'stok-merchandise',
            self::CONSUMABLE => 'stok-consumable',
            default => 'stok-produk-mo',
        };
    }

    public static function kindFromStokRouteName(?string $routeName): string
    {
        $routeName ??= '';

        return match (true) {
            str_contains($routeName, 'stok-merchandise') || str_contains($routeName, 'inventori-merchandise') => self::MERCHANDISE,
            str_contains($routeName, 'stok-consumable') || str_contains($routeName, 'inventori-consumable') => self::CONSUMABLE,
            str_contains($routeName, 'stok-part-luar') || str_contains($routeName, 'inventori-part-luar') => self::PART_LUAR,
            str_contains($routeName, 'stok-produk-speedshop') || str_contains($routeName, 'inventori-produk-speedshop') => self::SPEEDSHOP_SUPPLIER,
            default => self::MO,
        };
    }

    public static function warehouseInventoriRouteBase(string $kind): string
    {
        return match ($kind) {
            self::SPEEDSHOP_SUPPLIER => 'warehouse.inventori-produk-speedshop',
            self::PART_LUAR => 'warehouse.inventori-part-luar',
            self::MERCHANDISE => 'warehouse.inventori-merchandise',
            self::CONSUMABLE => 'warehouse.inventori-consumable',
            default => 'warehouse.inventori-produk-mo',
        };
    }

    /** @return list<string> */
    public static function warehouseInventoriRoutePatterns(): array
    {
        return array_map(
            fn (string $kind) => self::warehouseInventoriRouteBase($kind).'*',
            self::kinds(),
        );
    }

    public static function speedshopMasterRouteBase(string $kind): string
    {
        return match ($kind) {
            self::SPEEDSHOP_SUPPLIER => 'speedshop.master.produk-speedshop',
            self::PART_LUAR => 'speedshop.master.part-luar',
            self::MERCHANDISE => 'speedshop.master.merchandise',
            self::CONSUMABLE => 'speedshop.master.consumable',
            default => 'speedshop.master.produk-mo',
        };
    }

    /** @return list<string> */
    public static function speedshopMasterRoutePatterns(): array
    {
        return array_map(
            fn (string $kind) => self::speedshopMasterRouteBase($kind).'*',
            self::kinds(),
        );
    }

    public static function warehouseMasterRouteBase(string $kind): string
    {
        return match ($kind) {
            self::SPEEDSHOP_SUPPLIER => 'warehouse.head-catalog.speedshop-produk',
            self::PART_LUAR => 'warehouse.head-catalog.part-luar',
            self::MERCHANDISE => 'warehouse.head-catalog.merchandise',
            self::CONSUMABLE => 'warehouse.head-catalog.consumable',
            default => 'warehouse.master.produk',
        };
    }

    /** @return list<string> */
    public static function warehouseMasterRoutePatterns(): array
    {
        return array_map(
            fn (string $kind) => self::warehouseMasterRouteBase($kind).'*',
            self::kinds(),
        );
    }
}

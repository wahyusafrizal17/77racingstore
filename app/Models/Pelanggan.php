<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pelanggan extends Model
{
    protected $table = 'pelanggan';

    protected static function booted(): void
    {
        static::saving(function (Pelanggan $pelanggan): void {
            if ($pelanggan->section === self::SECTION_SPEEDSHOP) {
                $pelanggan->speedshop_id = null;
                $pelanggan->warehouse_id = null;
            }
        });
    }

    protected $fillable = [
        'warehouse_id',
        'section',
        'tujuan',
        'nama',
        'alamat',
        'no_hp',
        'kota_id',
        'speedshop_id',
    ];

    public function speedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class);
    }

    /** Section: warehouse = pelanggan transaksi offline warehouse, speedshop = pelanggan work order/transaksi speedshop */
    public const SECTION_WAREHOUSE = 'warehouse';

    public const SECTION_SPEEDSHOP = 'speedshop';

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function kota(): BelongsTo
    {
        return $this->belongsTo(Kota::class);
    }

    public function kendaraans(): HasMany
    {
        return $this->hasMany(Kendaraan::class);
    }

    public function transaksiOffline(): HasMany
    {
        return $this->hasMany(TransaksiOffline::class);
    }

    public static function labelTujuan(?string $tujuan): string
    {
        if ($tujuan === null) {
            return '-';
        }

        return match ($tujuan) {
            'speedshop' => 'Speedshop',
            'reseller' => 'Reseller',
            'umum' => 'Umum',
            default => $tujuan,
        };
    }

    public static function labelSection(?string $section): string
    {
        if ($section === null) {
            return '-';
        }

        return match ($section) {
            self::SECTION_WAREHOUSE => 'Warehouse',
            self::SECTION_SPEEDSHOP => 'Speedshop',
            default => $section,
        };
    }

    /** Scope: pelanggan yang dipakai di modul warehouse */
    public function scopeForWarehouse($query, ?int $warehouseId): void
    {
        $query->where(function ($q) {
            $q->whereNull('section')->orWhere('section', self::SECTION_WAREHOUSE);
        });
        if ($warehouseId !== null) {
            $query->where(function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            });
        }
    }

    /**
     * Scope: pelanggan yang dipakai di modul speedshop (satu pool untuk semua cabang; tidak difilter speedshop_id).
     */
    public function scopeForSpeedshop($query): void
    {
        $query->where(function ($q) {
            $q->whereNull('section')->orWhere('section', self::SECTION_SPEEDSHOP);
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseReturn extends Model
{
    public const SUMBER_MARKETPLACE = 'marketplace';

    public const SUMBER_RESELLER_ONLINE = 'reseller_online';

    public const SUMBER_SPEEDSHOP = 'speedshop';

    protected $fillable = [
        'kode',
        'warehouse_id',
        'user_id',
        'sumber',
        'warehouse_reseller_id',
        'transaksi_online_id',
        'no_resi',
        'no_referensi',
        'tanggal_return',
        'catatan',
        'total_qty',
        'total_nominal',
    ];

    protected $casts = [
        'tanggal_return' => 'date',
        'total_qty' => 'integer',
        'total_nominal' => 'integer',
    ];

    public static function sumberLabels(): array
    {
        return [
            self::SUMBER_MARKETPLACE => 'Return Marketplace',
            self::SUMBER_RESELLER_ONLINE => 'Return Reseller Online',
            self::SUMBER_SPEEDSHOP => 'Return Speedshop',
        ];
    }

    public function sumberLabel(): string
    {
        return self::sumberLabels()[$this->sumber] ?? ucfirst((string) $this->sumber);
    }

    public static function generateKode(): string
    {
        $prefix = 'RT'.now()->format('ymd');

        do {
            $suffix = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $kode = $prefix.$suffix;
        } while (self::query()->where('kode', $kode)->exists());

        return $kode;
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseReturnItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseReseller(): BelongsTo
    {
        return $this->belongsTo(WarehouseReseller::class, 'warehouse_reseller_id');
    }

    public function transaksiOnline(): BelongsTo
    {
        return $this->belongsTo(TransaksiOnline::class, 'transaksi_online_id');
    }
}

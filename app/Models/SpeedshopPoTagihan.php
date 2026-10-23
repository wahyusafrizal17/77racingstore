<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpeedshopPoTagihan extends Model
{
    public const STATUS_BELUM_LUNAS = 'belum_lunas';

    public const STATUS_DIBAYAR = 'dibayar';

    public const STATUS_LUNAS = 'lunas';

    protected $table = 'speedshop_po_tagihans';

    protected $fillable = [
        'kode',
        'speedshop_purchase_order_id',
        'transaksi_offline_id',
        'speedshop_id',
        'warehouse_id',
        'total_nominal',
        'tanggal_tagihan',
        'status',
        'dibayar_at',
        'dibayar_by',
        'catatan_pembayaran',
        'bukti_bayar_path',
        'lunas_at',
        'lunas_by',
        'catatan_konfirmasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_tagihan' => 'date',
            'dibayar_at' => 'datetime',
            'lunas_at' => 'datetime',
            'total_nominal' => 'integer',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(SpeedshopPurchaseOrder::class, 'speedshop_purchase_order_id');
    }

    public function transaksiOffline(): BelongsTo
    {
        return $this->belongsTo(TransaksiOffline::class);
    }

    public function speedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SpeedshopPoTagihanItem::class, 'tagihan_id');
    }

    public function dibayarByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibayar_by');
    }

    public function lunasByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lunas_by');
    }

    public function isBelumLunas(): bool
    {
        return $this->status === self::STATUS_BELUM_LUNAS;
    }

    public function isDibayar(): bool
    {
        return $this->status === self::STATUS_DIBAYAR;
    }

    public function isLunas(): bool
    {
        return $this->status === self::STATUS_LUNAS;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_DIBAYAR => 'Menunggu konfirmasi',
            self::STATUS_LUNAS => 'Lunas',
            default => 'Belum lunas',
        };
    }

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_DIBAYAR => 'bg-amber-100 text-amber-900 ring-amber-200',
            self::STATUS_LUNAS => 'bg-emerald-100 text-emerald-900 ring-emerald-200',
            default => 'bg-red-100 text-red-900 ring-red-200',
        };
    }
}

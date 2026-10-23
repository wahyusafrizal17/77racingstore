<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResellerOrderPayment extends Model
{
    public const STATUS_MENUNGGU_KONFIRMASI = 'menunggu_konfirmasi';

    public const STATUS_DIKONFIRMASI = 'dikonfirmasi';

    public const STATUS_DITOLAK = 'ditolak';

    protected $fillable = [
        'reseller_order_id',
        'warehouse_reseller_id',
        'user_id',
        'nominal',
        'bukti_path',
        'catatan',
        'status',
        'dikonfirmasi_by',
        'dikonfirmasi_at',
        'catatan_penolakan',
    ];

    protected function casts(): array
    {
        return [
            'dikonfirmasi_at' => 'datetime',
        ];
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_MENUNGGU_KONFIRMASI => 'Menunggu konfirmasi',
            self::STATUS_DIKONFIRMASI => 'Dikonfirmasi',
            self::STATUS_DITOLAK => 'Ditolak',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function buktiUrl(): ?string
    {
        if (! $this->bukti_path) {
            return null;
        }

        if (str_starts_with((string) $this->bukti_path, 'midtrans://')) {
            return null;
        }

        return \App\Support\UploadStorage::url($this->bukti_path);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ResellerOrder::class, 'reseller_order_id');
    }

    public function warehouseReseller(): BelongsTo
    {
        return $this->belongsTo(WarehouseReseller::class, 'warehouse_reseller_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dikonfirmasiBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikonfirmasi_by');
    }
}

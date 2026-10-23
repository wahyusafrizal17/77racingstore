<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehousePiutang extends Model
{
    public const STATUS_BELUM_LUNAS = 'belum_lunas';

    public const STATUS_LUNAS = 'lunas';

    protected $table = 'warehouse_piutangs';

    protected $fillable = [
        'warehouse_id',
        'tanggal',
        'rincian',
        'kategori',
        'pic',
        'debet',
        'status',
        'speedshop_keuangan_rekening_id',
        'bukti_bayar_path',
        'catatan_pembayaran',
        'lunas_at',
        'lunas_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'debet' => 'float',
            'lunas_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function keuanganRekening(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'speedshop_keuangan_rekening_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lunasBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lunas_by');
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(SpeedshopFinanceLedger::class, 'warehouse_piutang_id');
    }

    public function isBelumLunas(): bool
    {
        return ($this->status ?? self::STATUS_BELUM_LUNAS) === self::STATUS_BELUM_LUNAS;
    }

    public function isLunas(): bool
    {
        return $this->status === self::STATUS_LUNAS;
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_LUNAS => 'Lunas',
            default => 'Belum lunas',
        };
    }

    public function scopeForWarehouse($query, ?int $warehouseId)
    {
        if ($warehouseId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('warehouse_id', $warehouseId);
    }
}

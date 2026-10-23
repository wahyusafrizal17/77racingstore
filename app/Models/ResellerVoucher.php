<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResellerVoucher extends Model
{
    public const TIPE_NOMINAL = 'nominal';

    public const TIPE_PERSEN = 'persen';

    protected $fillable = [
        'warehouse_id',
        'kode',
        'nama',
        'deskripsi',
        'tipe',
        'nilai',
        'max_diskon',
        'min_pembelian',
        'kuota_global',
        'kuota_per_reseller',
        'terpakai_global',
        'mulai_at',
        'berakhir_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'nilai' => 'integer',
            'max_diskon' => 'integer',
            'min_pembelian' => 'integer',
            'kuota_global' => 'integer',
            'kuota_per_reseller' => 'integer',
            'terpakai_global' => 'integer',
            'mulai_at' => 'datetime',
            'berakhir_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(ResellerVoucherRedemption::class);
    }

    public function scopeForWarehouse(Builder $query, int $warehouseId): Builder
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeActiveNow(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('mulai_at')->orWhere('mulai_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('berakhir_at')->orWhere('berakhir_at', '>=', $now);
            });
    }

    public function isPersen(): bool
    {
        return $this->tipe === self::TIPE_PERSEN;
    }

    public function labelNilai(): string
    {
        if ($this->isPersen()) {
            return rtrim(rtrim(number_format((float) $this->nilai, 2, ',', '.'), '0'), ',').'%';
        }

        return 'Rp '.number_format((int) $this->nilai, 0, ',', '.');
    }
}

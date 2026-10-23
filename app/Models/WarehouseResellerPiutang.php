<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseResellerPiutang extends Model
{
    public const STATUS_BELUM_LUNAS = 'belum_lunas';

    public const STATUS_LUNAS = 'lunas';

    protected $table = 'warehouse_reseller_piutangs';

    protected $fillable = [
        'warehouse_id',
        'warehouse_reseller_id',
        'week_start',
        'week_end',
        'total_nominal',
        'total_dibayar',
        'status',
        'closed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end' => 'date',
            'total_nominal' => 'integer',
            'total_dibayar' => 'integer',
            'closed_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseReseller(): BelongsTo
    {
        return $this->belongsTo(WarehouseReseller::class, 'warehouse_reseller_id');
    }

    public function tagihans(): HasMany
    {
        return $this->hasMany(WarehouseResellerTagihan::class, 'reseller_piutang_id');
    }

    public function pembayarans(): HasMany
    {
        return $this->hasMany(WarehouseResellerPiutangPembayaran::class, 'piutang_id')
            ->orderByDesc('dibayar_at')
            ->orderByDesc('id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sisaPiutang(): int
    {
        return max(0, (int) $this->total_nominal - (int) ($this->total_dibayar ?? 0));
    }

    public function isLunas(): bool
    {
        if (($this->status ?? self::STATUS_BELUM_LUNAS) === self::STATUS_LUNAS) {
            return true;
        }

        return $this->sisaPiutang() <= 0;
    }

    public function scopeAktifBelumLunas(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_BELUM_LUNAS)
            ->where('total_nominal', '>', 0)
            ->whereColumn('total_dibayar', '<', 'total_nominal');
    }

    public static function sqlSisaPiutang(): string
    {
        return 'GREATEST(CAST(total_nominal AS SIGNED) - CAST(total_dibayar AS SIGNED), 0)';
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_LUNAS => 'Lunas',
            default => 'Belum lunas',
        };
    }

    /** @return array{label: string, tone: string} */
    public static function statusPembayaran(int $totalNominal, int $totalDibayar, int $sisa): array
    {
        if ($sisa <= 0 || $totalDibayar >= $totalNominal) {
            return ['label' => 'LUNAS', 'tone' => 'emerald'];
        }
        if ($totalDibayar <= 0) {
            return ['label' => 'BELUM BAYAR', 'tone' => 'red'];
        }

        return ['label' => 'BELUM TUNTAS', 'tone' => 'amber'];
    }
}

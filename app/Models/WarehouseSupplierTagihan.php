<?php

namespace App\Models;

use App\Support\RoleAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class WarehouseSupplierTagihan extends Model
{
    public const STATUS_BELUM_LUNAS = 'belum_lunas';

    public const STATUS_LUNAS = 'lunas';

    protected $table = 'warehouse_supplier_tagihans';

    protected $fillable = [
        'kode',
        'warehouse_transaksi_id',
        'stock_in_id',
        'warehouse_id',
        'speedshop_id',
        'supplier_id',
        'total_nominal',
        'total_dibayar',
        'tanggal_tagihan',
        'tanggal_jatuh_tempo',
        'status',
        'dibayar_at',
        'dibayar_by',
        'catatan_pembayaran',
        'bukti_bayar_path',
        'speedshop_keuangan_rekening_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_tagihan' => 'date',
            'tanggal_jatuh_tempo' => 'date',
            'dibayar_at' => 'datetime',
            'total_nominal' => 'integer',
            'total_dibayar' => 'integer',
        ];
    }

    public function warehouseTransaksi(): BelongsTo
    {
        return $this->belongsTo(WarehouseTransaksi::class);
    }

    public function stockIn(): BelongsTo
    {
        return $this->belongsTo(StockIn::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function speedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseSupplierTagihanItem::class, 'tagihan_id');
    }

    public function pembayarans(): HasMany
    {
        return $this->hasMany(WarehouseSupplierTagihanPembayaran::class, 'tagihan_id')
            ->orderByDesc('dibayar_at')
            ->orderByDesc('id');
    }

    public function dibayarByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibayar_by');
    }

    public function sumberRekening(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'speedshop_keuangan_rekening_id');
    }

    public function isBelumLunas(): bool
    {
        return ! $this->isLunas();
    }

    public function isLunas(): bool
    {
        if (($this->status ?? self::STATUS_BELUM_LUNAS) === self::STATUS_LUNAS) {
            return true;
        }

        return $this->sisaHutang() <= 0;
    }

    public function sisaHutang(): int
    {
        return max(0, (int) $this->total_nominal - (int) ($this->total_dibayar ?? 0));
    }

    /** Jatuh tempo efektif: kolom tagihan, fallback transaksi pembelian, lalu tanggal tagihan. */
    public function effectiveJatuhTempo(): ?\Illuminate\Support\Carbon
    {
        if ($this->tanggal_jatuh_tempo) {
            return $this->tanggal_jatuh_tempo->copy();
        }

        if (! $this->relationLoaded('warehouseTransaksi')) {
            $this->loadMissing('warehouseTransaksi:id,tanggal_jatuh_tempo');
        }

        $fromTransaksi = $this->warehouseTransaksi?->tanggal_jatuh_tempo;
        if ($fromTransaksi) {
            return $fromTransaksi->copy();
        }

        return $this->tanggal_tagihan?->copy();
    }

    public static function sqlEffectiveJatuhTempo(string $alias = 'wst'): string
    {
        return "COALESCE(
            {$alias}.tanggal_jatuh_tempo,
            (SELECT wt.tanggal_jatuh_tempo FROM warehouse_transaksis wt WHERE wt.id = {$alias}.warehouse_transaksi_id LIMIT 1),
            {$alias}.tanggal_tagihan
        )";
    }

    /** Invoice dari data pembelian warehouse atau stock-in produksi. */
    public function scopeForWarehouseAccountingHutang(Builder $query): Builder
    {
        $query->whereRaw(self::sqlScopeForWarehouseAccountingHutang());
        if (Schema::hasColumn('warehouse_supplier_tagihans', 'speedshop_id')) {
            $query->whereNull('speedshop_id');
        }

        return $query;
    }

    public function scopeForSpeedshopHutang(Builder $query, int $speedshopId): Builder
    {
        return $query->where('speedshop_id', (int) $speedshopId);
    }

    public static function sqlScopeForWarehouseAccountingHutang(string $alias = 'warehouse_supplier_tagihans'): string
    {
        $t = $alias;
        $moSection = User::SECTION_MODIFIKASI_ORI;
        $roles = implode(',', array_map(
            static fn (string $role) => "'".str_replace("'", "''", $role)."'",
            RoleAccess::modifikasiWarehouseRoles(),
        ));

        return "(
            {$t}.stock_in_id IS NOT NULL
            OR ({$t}.warehouse_transaksi_id IS NULL AND {$t}.stock_in_id IS NULL)
            OR EXISTS (
                SELECT 1 FROM warehouse_transaksis wt
                INNER JOIN users u ON u.id = wt.user_id
                WHERE wt.id = {$t}.warehouse_transaksi_id
                AND (wt.tipe IS NULL OR wt.tipe = '')
                AND (
                    u.section = 'warehouse'
                    OR (u.section = '{$moSection}' AND u.role IN ({$roles}))
                )
            )
        )";
    }

    public function sumberKode(): string
    {
        if ($this->relationLoaded('warehouseTransaksi') && $this->warehouseTransaksi?->kode) {
            return (string) $this->warehouseTransaksi->kode;
        }

        if ($this->relationLoaded('stockIn') && $this->stockIn?->kode) {
            return (string) $this->stockIn->kode;
        }

        return (string) ($this->kode ?? '');
    }

    public function isManualEntry(): bool
    {
        return ! $this->warehouse_transaksi_id && ! $this->stock_in_id;
    }

    /** Invoice yang masih perlu dibayar (sisa > 0). */
    public function scopeAktifBelumLunas(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_BELUM_LUNAS)
            ->where('total_nominal', '>', 0)
            ->whereColumn('total_dibayar', '<', 'total_nominal');
    }

    public static function sqlSisaHutang(): string
    {
        return 'GREATEST(CAST(total_nominal AS SIGNED) - CAST(total_dibayar AS SIGNED), 0)';
    }

    public function totalDibayar(): int
    {
        return (int) ($this->total_dibayar ?? 0);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_LUNAS => 'Lunas',
            default => 'Belum lunas',
        };
    }

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_LUNAS => 'bg-emerald-100 text-emerald-900 ring-emerald-200',
            default => 'bg-red-100 text-red-900 ring-red-200',
        };
    }
}

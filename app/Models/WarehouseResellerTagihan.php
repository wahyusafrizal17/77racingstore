<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class WarehouseResellerTagihan extends Model
{
    public const STATUS_BELUM_LUNAS = 'belum_lunas';

    public const STATUS_DIBAYAR = 'dibayar';

    public const STATUS_LUNAS = 'lunas';

    public const STATUS_PIUTANG = 'piutang';

    public const STATUS_BATAL = 'batal';

    protected $table = 'warehouse_reseller_tagihans';

    protected $fillable = [
        'kode',
        'transaksi_online_id',
        'warehouse_id',
        'warehouse_reseller_id',
        'total_nominal',
        'total_dibayar',
        'tanggal_tagihan',
        'jatuh_tempo_at',
        'status',
        'dibayar_at',
        'dibayar_by',
        'catatan_pembayaran',
        'bukti_bayar_path',
        'speedshop_keuangan_rekening_id',
        'reseller_piutang_id',
        'lunas_at',
        'lunas_by',
        'catatan_konfirmasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_tagihan' => 'date',
            'jatuh_tempo_at' => 'datetime',
            'dibayar_at' => 'datetime',
            'lunas_at' => 'datetime',
            'total_nominal' => 'integer',
            'total_dibayar' => 'integer',
        ];
    }

    public function transaksiOnline(): BelongsTo
    {
        return $this->belongsTo(TransaksiOnline::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseReseller(): BelongsTo
    {
        return $this->belongsTo(WarehouseReseller::class, 'warehouse_reseller_id');
    }

    public function resellerPiutang(): BelongsTo
    {
        return $this->belongsTo(WarehouseResellerPiutang::class, 'reseller_piutang_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseResellerTagihanItem::class, 'tagihan_id');
    }

    public function pembayarans(): HasMany
    {
        return $this->hasMany(WarehouseResellerTagihanPembayaran::class, 'tagihan_id')
            ->orderByDesc('dibayar_at')
            ->orderByDesc('id');
    }

    public function midtransPayments(): HasMany
    {
        return $this->hasMany(WarehouseResellerTagihanMidtransPayment::class, 'tagihan_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(WarehouseResellerTagihanRefund::class, 'tagihan_id')
            ->orderByDesc('direfund_at')
            ->orderByDesc('id');
    }

    public function dibayarByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibayar_by');
    }

    public function lunasByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'lunas_by');
    }

    public function sumberRekening(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'speedshop_keuangan_rekening_id');
    }

    public function isBelumLunas(): bool
    {
        return $this->status === self::STATUS_BELUM_LUNAS && ! $this->isLunas();
    }

    public function isDibayar(): bool
    {
        return $this->status === self::STATUS_DIBAYAR;
    }

    public function isPiutang(): bool
    {
        return $this->status === self::STATUS_PIUTANG;
    }

    public function isManualEntry(): bool
    {
        return $this->transaksi_online_id === null;
    }

    public function isBatal(): bool
    {
        return $this->status === self::STATUS_BATAL;
    }

    public function isLunas(): bool
    {
        if (($this->status ?? self::STATUS_BELUM_LUNAS) === self::STATUS_LUNAS) {
            return true;
        }

        if ($this->isPiutang()) {
            return $this->outstandingTagihan() <= 0;
        }

        return $this->sisaTagihan() <= 0;
    }

    public function outstandingTagihan(): int
    {
        return max(0, (int) $this->total_nominal - (int) ($this->total_dibayar ?? 0));
    }

    public function sisaTagihan(): int
    {
        if ($this->isPiutang()) {
            return 0;
        }

        return max(0, (int) $this->total_nominal - (int) ($this->total_dibayar ?? 0));
    }

    public function totalDibayar(): int
    {
        return (int) ($this->total_dibayar ?? 0);
    }

    public function totalDirefund(): int
    {
        if ($this->relationLoaded('refunds')) {
            return (int) $this->refunds->sum('nominal');
        }

        return (int) $this->refunds()->sum('nominal');
    }

    /** Kelebihan bayar setelah revisi tagihan (return / penyesuaian). */
    public function refundEligible(): int
    {
        if ($this->isPiutang()) {
            return 0;
        }

        return max(0, $this->totalDibayar() - (int) $this->total_nominal - $this->totalDirefund());
    }

    public function needsRefund(): bool
    {
        return $this->refundEligible() > 0;
    }

    /** Tagihan dengan kelebihan bayar setelah revisi yang belum direfund penuh. */
    public function scopeNeedsRefund(Builder $query): Builder
    {
        $query->where('status', '!=', self::STATUS_PIUTANG)
            ->whereNull('reseller_piutang_id')
            ->whereRaw('CAST(total_dibayar AS SIGNED) > CAST(total_nominal AS SIGNED)');

        if (Schema::hasTable('warehouse_reseller_tagihan_refunds')) {
            $query->whereRaw(
                'CAST(total_dibayar AS SIGNED) > CAST(total_nominal AS SIGNED) + COALESCE((SELECT SUM(r.nominal) FROM warehouse_reseller_tagihan_refunds r WHERE r.tagihan_id = warehouse_reseller_tagihans.id), 0)'
            );
        }

        return $query;
    }

    /** Tagihan minggu berjalan yang masih perlu dibayar. */
    public function scopeAktifMingguIni(Builder $query, string $senin, string $sabtu): Builder
    {
        return $query
            ->whereNull('reseller_piutang_id')
            ->whereDate('tanggal_tagihan', '>=', $senin)
            ->whereDate('tanggal_tagihan', '<=', $sabtu)
            ->whereIn('status', [self::STATUS_BELUM_LUNAS, self::STATUS_DIBAYAR])
            ->where('total_nominal', '>', 0)
            ->whereColumn('total_dibayar', '<', 'total_nominal');
    }

    public static function sqlSisaTagihan(): string
    {
        return 'GREATEST(CAST(total_nominal AS SIGNED) - CAST(total_dibayar AS SIGNED), 0)';
    }

    /** Tagihan yang masih menjadi hutang (belum_lunas / dibayar / piutang). */
    public function scopeOpenDebt(Builder $query): Builder
    {
        return $query
            ->where('status', '!=', self::STATUS_BATAL)
            ->where('status', '!=', self::STATUS_LUNAS)
            ->whereColumn('total_nominal', '>', 'total_dibayar');
    }

    /** Total sisa hutang reseller — sumber tunggal untuk dashboard & halaman tagihan. */
    public static function outstandingNominalForReseller(int $warehouseResellerId): int
    {
        if ($warehouseResellerId <= 0 || ! Schema::hasTable((new static)->getTable())) {
            return 0;
        }

        return (int) static::query()
            ->where('warehouse_reseller_id', $warehouseResellerId)
            ->openDebt()
            ->selectRaw('COALESCE(SUM('.self::sqlSisaTagihan().'), 0) as sisa')
            ->value('sisa');
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_DIBAYAR => 'Menunggu konfirmasi',
            self::STATUS_LUNAS => 'Lunas',
            self::STATUS_PIUTANG => 'Piutang',
            self::STATUS_BATAL => 'Dibatalkan',
            default => 'Belum lunas',
        };
    }

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_DIBAYAR => 'bg-amber-100 text-amber-900 ring-amber-200',
            self::STATUS_LUNAS => 'bg-emerald-100 text-emerald-900 ring-emerald-200',
            self::STATUS_PIUTANG => 'bg-violet-100 text-violet-900 ring-violet-200',
            self::STATUS_BATAL => 'bg-gray-100 text-gray-700 ring-gray-200',
            default => 'bg-red-100 text-red-900 ring-red-200',
        };
    }
}

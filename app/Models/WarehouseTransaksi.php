<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseTransaksi extends Model
{
    public const JENIS_PEMBAYARAN_TUNAI = 'tunai';

    public const JENIS_PEMBAYARAN_HUTANG = 'hutang';

    protected $table = 'warehouse_transaksis';

    protected $fillable = [
        'kode', 'tipe', 'user_id', 'warehouse_id', 'supplier_id', 'status',
        'approved_by', 'approved_at', 'rejected_reason', 'catatan', 'jenis_pembayaran', 'ppn', 'ongkir', 'tanggal_jatuh_tempo', 'invoice_path',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'tanggal_jatuh_tempo' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WarehouseTransaksiItem::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supplierTagihan(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WarehouseSupplierTagihan::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isPembayaranTunai(): bool
    {
        return $this->resolvedJenisPembayaran() === self::JENIS_PEMBAYARAN_TUNAI;
    }

    public function isPembayaranHutang(): bool
    {
        return ! $this->isPembayaranTunai();
    }

    public function resolvedJenisPembayaran(): string
    {
        $jenis = strtolower(trim((string) ($this->jenis_pembayaran ?? '')));
        if ($jenis === self::JENIS_PEMBAYARAN_TUNAI) {
            return self::JENIS_PEMBAYARAN_TUNAI;
        }

        return self::JENIS_PEMBAYARAN_HUTANG;
    }

    /** Data pembelian dari menu warehouse/transaksi (bukan stok-in speedshop / pembelian luar). */
    public function scopeWarehousePembelian(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $q) {
                $q->whereNull('tipe')->orWhere('tipe', '');
            })
            ->whereHas('user', fn (Builder $uq) => $uq->warehousePembelianSubmitters());
    }

    public function isWarehousePembelian(): bool
    {
        $tipe = $this->tipe;
        if ($tipe !== null && $tipe !== '') {
            return false;
        }

        if ($this->relationLoaded('user')) {
            return (bool) $this->user?->isWarehousePembelianSubmitter();
        }

        return $this->user()->warehousePembelianSubmitters()->exists();
    }

    public static function generateKode(): string
    {
        $prefix = 'WHT-'.now()->format('Ymd');
        $last = static::where('kode', 'like', $prefix.'%')->orderBy('kode', 'desc')->first();
        $seq = $last ? (int) substr($last->kode, -4) + 1 : 1;

        return $prefix.'-'.str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /** Nomor invoice unik untuk form Data Pembelian (format INV-ddmmyyyy-####). */
    public static function suggestInvoiceKode(): string
    {
        $prefix = 'INV-'.now()->format('dmY').'-';
        $last = static::where('kode', 'like', $prefix.'%')
            ->orderBy('kode', 'desc')
            ->first();
        $seq = 1;
        if ($last && preg_match('/-(\d{4})$/', (string) $last->kode, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}

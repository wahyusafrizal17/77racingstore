<?php

namespace App\Models;

use App\Services\SpeedshopPoTagihanService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransaksiOffline extends Model
{
    protected $table = 'transaksi_offline';

    protected $fillable = [
        'no_transaksi',
        'tujuan',
        'pelanggan_id',
        'warehouse_reseller_id',
        'no_urut_harian',
        'speedshop_id',
        'nama_toko',
        'alamat',
        'no_hp',
        'petugas_id',
        'jenis_pembayaran',
        'pembayaran_split',
        'ongkir',
        'tanggal_jatuh_tempo',
        'user_id',
        'warehouse_id',
        'speedshop_purchase_order_id',
        'warehouse_po_reviewed_at',
        'warehouse_po_reviewed_by',
        'warehouse_po_review_rejected_at',
        'warehouse_po_review_rejected_by',
        'warehouse_po_review_rejected_reason',
        'warehouse_po_closed_at',
        'warehouse_po_closed_by',
        'warehouse_po_closed_reason',
        'claim_number',
        'verified_at',
        'rejected_at',
        'rejected_reason',
        'rejected_by',
        'manager_approval_at',
        'manager_approval_by',
        'manager_rejection_at',
        'manager_rejection_by',
        'manager_rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'pembayaran_split' => 'array',
            'ongkir' => 'integer',
            'tanggal_jatuh_tempo' => 'date',
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
            'warehouse_po_reviewed_at' => 'datetime',
            'warehouse_po_review_rejected_at' => 'datetime',
            'warehouse_po_closed_at' => 'datetime',
            'manager_approval_at' => 'datetime',
            'manager_rejection_at' => 'datetime',
        ];
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    public function warehouseReseller(): BelongsTo
    {
        return $this->belongsTo(WarehouseReseller::class, 'warehouse_reseller_id');
    }

    public function speedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function speedshopPurchaseOrder(): BelongsTo
    {
        return $this->belongsTo(SpeedshopPurchaseOrder::class);
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function warehousePoReviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'warehouse_po_reviewed_by');
    }

    public function warehousePoReviewRejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'warehouse_po_review_rejected_by');
    }

    public function warehousePoClosedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'warehouse_po_closed_by');
    }

    public function managerApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_approval_by');
    }

    public function managerRejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_rejection_by');
    }

    /** Penjualan offline Reseller/Umum: menunggu Manager sebelum stok dikurangi. */
    public function isOfflineSalePendingManagerApproval(): bool
    {
        if ($this->speedshop_purchase_order_id !== null) {
            return false;
        }
        if (! in_array($this->tujuan, ['reseller', 'umum'], true)) {
            return false;
        }

        return $this->petugas_id === null
            && $this->manager_approval_at === null
            && $this->manager_rejection_at === null;
    }

    public function isOfflineSaleManagerRejected(): bool
    {
        return $this->manager_rejection_at !== null;
    }

    public function isOfflineSaleManagerApproved(): bool
    {
        if ($this->speedshop_purchase_order_id !== null) {
            return false;
        }
        if (! in_array($this->tujuan, ['reseller', 'umum'], true)) {
            return false;
        }
        if ($this->manager_rejection_at !== null) {
            return false;
        }

        return $this->petugas_id !== null;
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransaksiOfflineItem::class);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isRejected(): bool
    {
        return $this->rejected_at !== null;
    }

    /** Menunggu keputusan Speedshop (belum verifikasi & belum tolak). */
    public function isPendingSpeedshopDecision(): bool
    {
        return ! $this->isVerified() && ! $this->isRejected();
    }

    /**
     * Transaksi mirror dari PO Speedshop: Speedshop baru boleh verifikasi/tolak
     * setelah Warehouse memproses (mutasi dibuat, petugas terisi).
     */
    public function isAwaitingWarehousePoProcessing(): bool
    {
        if ($this->speedshop_purchase_order_id === null) {
            return false;
        }
        if ($this->warehouse_po_review_rejected_at !== null) {
            return false;
        }

        return $this->petugas_id === null;
    }

    /** Pengajuan PO ditolak di tahap verifikasi warehouse (sebelum mutasi). */
    public function isWarehousePoReviewRejected(): bool
    {
        return $this->warehouse_po_review_rejected_at !== null;
    }

    /** Harga per unit baris untuk PDF / ringkasan (PO Speedshop vs penjualan Reseller/Umum). */
    public function itemUnitPriceRp(?Product $product): int
    {
        if (! $product) {
            return 0;
        }

        if ($this->speedshop_purchase_order_id !== null || $this->tujuan === 'speedshop') {
            return SpeedshopPoTagihanService::productHargaPoRp($product);
        }

        if ($this->tujuan === 'reseller' && $this->isCanvassingResellerTransaction()) {
            return max(0, (int) ($product->harga_jual_reseler ?? 0));
        }

        $jual = (int) ($product->harga_jual ?? 0);

        return $jual > 0 ? $jual : max(0, (int) ($product->harga_eceran_terendah ?? 0));
    }

    public function itemLineUnitPriceRp(TransaksiOfflineItem $item): int
    {
        $stored = (int) ($item->harga ?? 0);
        if ($stored > 0) {
            return $stored;
        }

        return $this->itemUnitPriceRp($item->product);
    }

    public function isCanvassingResellerTransaction(): bool
    {
        return false;
    }

    public function itemsGrandTotalRp(): int
    {
        return (int) $this->items->sum(
            fn (TransaksiOfflineItem $item) => $this->itemLineUnitPriceRp($item) * (int) $item->qty
        );
    }

    /**
     * Isi harga baris lama yang masih 0 (transaksi sebelum kolom harga ditambahkan).
     */
    public function backfillItemHargaIfEmpty(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('transaksi_offline_items', 'harga')) {
            return;
        }

        $this->loadMissing(['items.product', 'warehouseReseller']);

        foreach ($this->items as $item) {
            if ((int) ($item->harga ?? 0) > 0) {
                continue;
            }

            $computed = $this->itemUnitPriceRp($item->product);
            if ($computed <= 0) {
                continue;
            }

            $item->update(['harga' => $computed]);
        }
    }

    public static function generateNoTransaksi(): string
    {
        $prefix = 'TO-'.now()->format('Ymd');
        $last = static::where('no_transaksi', 'like', $prefix.'%')->orderBy('no_transaksi', 'desc')->first();
        $seq = $last ? (int) substr($last->no_transaksi, -4) + 1 : 1;

        return $prefix.'-'.str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}

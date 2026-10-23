<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResellerOrder extends Model
{
    public const STATUS_MENUNGGU_VERIFIKASI = 'menunggu_verifikasi';

    public const STATUS_DIPROSES = 'diproses';

    /** WhatsApp: isi resi & ongkir warehouse dulu, baru packing. */
    public const STATUS_MENUNGGU_RESI = 'menunggu_resi';

    public const STATUS_DIKIRIM = 'dikirim';

    public const STATUS_VERIFIKASI_RESELLER = 'verifikasi_reseller';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_LUNAS = 'lunas';

    public const STATUS_DIRETUR = 'diretur';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const SOURCE_RESELLER = 'reseller';

    public const SOURCE_MO_OPERATOR = 'mo_operator';

    public const CANCEL_VERIFIKASI_PENDING = 'pending';

    public const CANCEL_VERIFIKASI_APPROVED = 'approved';

    public const CANCEL_VERIFIKASI_REJECTED = 'rejected';

    public const TIPE_PENGIRIMAN_INSTAN = 'instan';

    public const TIPE_PENGIRIMAN_REGULER = 'reguler';

    public const MARKETPLACE_SHOPEE = 'shopee';

    public const MARKETPLACE_LAZADA = 'lazada';

    public const MARKETPLACE_TIKTOK = 'tiktok';

    public const MARKETPLACE_WHATSAPP = 'whatsapp';

    /** @deprecated Gunakan STATUS_MENUNGGU_VERIFIKASI */
    public const STATUS_MENUNGGU = self::STATUS_MENUNGGU_VERIFIKASI;

    /** @deprecated Gunakan STATUS_DIPROSES */
    public const STATUS_PACKING = self::STATUS_DIPROSES;

    /** @deprecated Gunakan STATUS_DIKIRIM */
    public const STATUS_SUDAH_PACKING = self::STATUS_DIKIRIM;

    /** @deprecated Gunakan STATUS_SELESAI */
    public const STATUS_MENUNGGU_BAYAR = self::STATUS_SELESAI;

    protected $fillable = [
        'kode_pesanan',
        'no_urut_harian',
        'warehouse_id',
        'warehouse_reseller_id',
        'user_id',
        'order_source',
        'status',
        'no_pesanan_marketplace',
        'marketplace',
        'no_resi',
        'tipe_pengiriman',
        'bukti_resi_path',
        'catatan',
        'alamat_pengiriman',
        'ongkir',
        'nama_penerima',
        'no_hp_penerima',
        'reseller_voucher_id',
        'kode_voucher',
        'subtotal_bruto',
        'diskon_promo',
        'diskon_voucher',
        'total_nominal',
        'total_bayar',
        'submitted_at',
        'jatuh_tempo_at',
        'blokir_order_at',
        'packed_at',
        'packing_scanned_at',
        'dikirim_at',
        'selesai_at',
        'diretur_at',
        'return_alasan',
        'return_bukti_path',
        'lunas_at',
        'reseller_edited_at',
        'reseller_cancelled_at',
        'cancel_alasan',
        'warehouse_reseller_change_seen_at',
        'cancel_verifikasi_status',
        'cancel_verifikasi_requested_by',
        'cancel_verifikasi_requested_at',
        'cancel_verifikasi_verified_by',
        'cancel_verifikasi_verified_at',
        'cancel_verifikasi_rejected_by',
        'cancel_verifikasi_rejected_at',
        'cancel_verifikasi_rejected_reason',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'jatuh_tempo_at' => 'datetime',
            'blokir_order_at' => 'datetime',
            'packed_at' => 'datetime',
            'packing_scanned_at' => 'datetime',
            'dikirim_at' => 'datetime',
            'selesai_at' => 'datetime',
            'diretur_at' => 'datetime',
            'lunas_at' => 'datetime',
            'reseller_edited_at' => 'datetime',
            'reseller_cancelled_at' => 'datetime',
            'warehouse_reseller_change_seen_at' => 'datetime',
            'cancel_verifikasi_requested_at' => 'datetime',
            'cancel_verifikasi_verified_at' => 'datetime',
            'cancel_verifikasi_rejected_at' => 'datetime',
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     */
    public function scopeSearchPortal($query, ?string $term): void
    {
        $term = trim((string) $term);
        if ($term === '') {
            return;
        }

        $like = '%'.$term.'%';

        $query->where(function ($q) use ($term, $like) {
            $q->where('kode_pesanan', 'like', $like)
                ->orWhere('no_resi', 'like', $like)
                ->orWhere('no_pesanan_marketplace', 'like', $like);

            if (ctype_digit($term)) {
                $q->orWhere('no_urut_harian', (int) $term);
            }

            $q->orWhereHas('items.product', function ($pq) use ($like) {
                $pq->where('nama_produk', 'like', $like)
                    ->orWhere('kode_produk', 'like', $like);
            });
        });
    }

    public function returnBuktiUrl(): ?string
    {
        if (! $this->return_bukti_path) {
            return null;
        }

        return \App\Support\UploadStorage::url($this->return_bukti_path);
    }

    public function buktiResiUrl(): ?string
    {
        if (! $this->bukti_resi_path) {
            return null;
        }

        return \App\Support\UploadStorage::url($this->bukti_resi_path);
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_MENUNGGU_VERIFIKASI => 'Menunggu Verifikasi',
            self::STATUS_MENUNGGU_RESI => 'Menunggu Resi',
            self::STATUS_DIPROSES => 'Diproses',
            self::STATUS_DIKIRIM => 'Dikirim',
            self::STATUS_VERIFIKASI_RESELLER => 'Verifikasi Reseller',
            self::STATUS_SELESAI => 'Selesai',
            self::STATUS_LUNAS => 'Lunas',
            self::STATUS_DIRETUR => 'Diretur',
            self::STATUS_DIBATALKAN => 'Dibatalkan',
        ];
    }

    /** @return list<array{key: string, label: string}> */
    public static function flowSteps(): array
    {
        return [
            ['key' => self::STATUS_MENUNGGU_VERIFIKASI, 'label' => 'Menunggu Verifikasi'],
            ['key' => self::STATUS_MENUNGGU_RESI, 'label' => 'Menunggu Resi'],
            ['key' => self::STATUS_DIPROSES, 'label' => 'Diproses'],
            ['key' => self::STATUS_DIKIRIM, 'label' => 'Dikirim'],
            ['key' => self::STATUS_VERIFIKASI_RESELLER, 'label' => 'Verifikasi Reseller'],
            ['key' => self::STATUS_SELESAI, 'label' => 'Selesai'],
        ];
    }

    public function statusLabel(): string
    {
        if ($this->hasPendingCancelVerifikasi()) {
            return 'Menunggu Verifikasi Batal';
        }

        return self::statusLabels()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function hasPendingCancelVerifikasi(): bool
    {
        return ($this->cancel_verifikasi_status ?? null) === self::CANCEL_VERIFIKASI_PENDING;
    }

    public function wasCancelVerifikasiRejected(): bool
    {
        return ($this->cancel_verifikasi_status ?? null) === self::CANCEL_VERIFIKASI_REJECTED;
    }

    /** @return array<string, string> */
    public static function marketplaceLabels(): array
    {
        return [
            self::MARKETPLACE_SHOPEE => 'Shopee',
            self::MARKETPLACE_LAZADA => 'Lazada',
            self::MARKETPLACE_TIKTOK => 'Tiktok',
            self::MARKETPLACE_WHATSAPP => 'Whatsapp',
        ];
    }

    /** @return list<string> */
    public static function marketplaceValues(): array
    {
        return array_keys(self::marketplaceLabels());
    }

    public function marketplaceLabel(): ?string
    {
        $key = (string) ($this->marketplace ?? '');

        return self::marketplaceLabels()[$key] ?? null;
    }

    /** @return array<string, string> */
    public static function tipePengirimanLabels(): array
    {
        return [
            self::TIPE_PENGIRIMAN_INSTAN => 'Instan',
            self::TIPE_PENGIRIMAN_REGULER => 'Reguler',
        ];
    }

    public function tipePengirimanLabel(): ?string
    {
        $key = (string) ($this->tipe_pengiriman ?? '');

        return self::tipePengirimanLabels()[$key] ?? null;
    }

    public function isPengirimanInstan(): bool
    {
        return ($this->tipe_pengiriman ?? null) === self::TIPE_PENGIRIMAN_INSTAN;
    }

    public function isPengirimanReguler(): bool
    {
        return ($this->tipe_pengiriman ?? null) === self::TIPE_PENGIRIMAN_REGULER;
    }

    public function dailyOrderNumberLabel(): ?string
    {
        return $this->no_urut_harian !== null
            ? (string) $this->no_urut_harian
            : null;
    }

    public function displayOrderReference(): string
    {
        if ($this->no_urut_harian !== null) {
            return (string) $this->no_urut_harian;
        }

        return (string) $this->kode_pesanan;
    }

    public function sisaTagihan(): int
    {
        return max(0, (int) $this->total_nominal - (int) $this->total_bayar);
    }

    public function isLunas(): bool
    {
        return $this->status === self::STATUS_LUNAS || $this->sisaTagihan() === 0;
    }

    public function items(): HasMany
    {
        return $this->hasMany(ResellerOrderItem::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(ResellerVoucher::class, 'reseller_voucher_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseReseller(): BelongsTo
    {
        return $this->belongsTo(WarehouseReseller::class, 'warehouse_reseller_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cancelVerifikasiRequestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancel_verifikasi_requested_by');
    }

    public function cancelVerifikasiVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancel_verifikasi_verified_by');
    }

    public function cancelVerifikasiRejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancel_verifikasi_rejected_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ResellerOrderPayment::class);
    }

    public function midtransPayments(): HasMany
    {
        return $this->hasMany(ResellerOrderMidtransPayment::class);
    }

    public function isJatuhTempo(): bool
    {
        return $this->jatuh_tempo_at !== null
            && now()->greaterThanOrEqualTo($this->jatuh_tempo_at)
            && ! $this->isLunas();
    }

    public function isBlokirOrderAktif(): bool
    {
        return $this->blokir_order_at !== null
            && now()->greaterThanOrEqualTo($this->blokir_order_at)
            && $this->sisaTagihan() > 0
            && ! in_array($this->status, [
                self::STATUS_LUNAS,
                self::STATUS_DIBATALKAN,
                self::STATUS_DIRETUR,
            ], true);
    }

    public function isAwaitingProcess(): bool
    {
        return $this->status === self::STATUS_MENUNGGU_VERIFIKASI;
    }

    public function canResellerEdit(): bool
    {
        return $this->isAwaitingProcess()
            && ! $this->isMoOperatorOrder()
            && ! $this->hasPendingCancelVerifikasi();
    }

    public function canMoOperatorEdit(): bool
    {
        return $this->isMoOperatorOrder()
            && $this->status === self::STATUS_MENUNGGU_VERIFIKASI
            && ! $this->hasPendingCancelVerifikasi();
    }

    public function canMoOperatorCancel(): bool
    {
        return $this->canMoOperatorEdit();
    }

    public function canResellerCancel(): bool
    {
        return $this->canResellerEdit();
    }

    public function resellerChangeAt(): ?\Illuminate\Support\Carbon
    {
        if ($this->hasPendingCancelVerifikasi() && $this->cancel_verifikasi_requested_at) {
            return $this->cancel_verifikasi_requested_at;
        }

        if ($this->reseller_cancelled_at) {
            return $this->reseller_cancelled_at;
        }

        return $this->reseller_edited_at;
    }

    public function hasUnseenResellerChange(): bool
    {
        $changeAt = $this->resellerChangeAt();
        if ($changeAt === null) {
            return false;
        }

        $seenAt = $this->warehouse_reseller_change_seen_at;

        return $seenAt === null || $seenAt->lt($changeAt);
    }

    public function resellerChangeKind(): ?string
    {
        if ($this->hasPendingCancelVerifikasi()) {
            return 'cancel_pending';
        }

        if ($this->reseller_cancelled_at !== null) {
            return 'cancel';
        }

        if ($this->reseller_edited_at !== null) {
            return 'edit';
        }

        return null;
    }

    public function markResellerChangeSeen(): void
    {
        if (! $this->hasUnseenResellerChange()) {
            return;
        }

        $this->forceFill(['warehouse_reseller_change_seen_at' => now()])->save();
    }

    public function canWarehouseProses(): bool
    {
        return $this->status === self::STATUS_MENUNGGU_VERIFIKASI
            && ! $this->hasPendingCancelVerifikasi();
    }

    public function canWarehouseVerifyCancel(): bool
    {
        return $this->hasPendingCancelVerifikasi()
            && $this->status === self::STATUS_MENUNGGU_VERIFIKASI;
    }

    public function canWarehouseKirim(): bool
    {
        if (! $this->isPackingScanComplete()) {
            return false;
        }

        if ($this->marketplace === self::MARKETPLACE_WHATSAPP) {
            if (! $this->hasWhatsappResiReady()) {
                return false;
            }

            // Baru: kirim dari Diproses setelah scan. Lama: masih Menunggu Resi setelah scan.
            return in_array($this->status, [
                self::STATUS_DIPROSES,
                self::STATUS_MENUNGGU_RESI,
            ], true);
        }

        return $this->status === self::STATUS_DIPROSES;
    }

    public function canWarehouseScan(): bool
    {
        return $this->status === self::STATUS_DIPROSES;
    }

    public function canWarehouseResetScan(): bool
    {
        return $this->status === self::STATUS_DIPROSES;
    }

    public function canWarehouseEditWhatsappResi(): bool
    {
        if ($this->marketplace !== self::MARKETPLACE_WHATSAPP) {
            return false;
        }

        return in_array($this->status, [
            self::STATUS_MENUNGGU_RESI,
            self::STATUS_DIPROSES,
            self::STATUS_VERIFIKASI_RESELLER,
            self::STATUS_DIKIRIM,
        ], true);
    }

    public function isWhatsappAwaitingResiBeforePacking(): bool
    {
        return $this->marketplace === self::MARKETPLACE_WHATSAPP
            && $this->status === self::STATUS_MENUNGGU_RESI
            && ! $this->isPackingScanComplete();
    }

    public function hasWhatsappResiReady(): bool
    {
        return $this->marketplace === self::MARKETPLACE_WHATSAPP
            && filled($this->no_resi)
            && filled($this->bukti_resi_path)
            && $this->ongkir !== null;
    }

    public function isPackingScanComplete(): bool
    {
        $this->loadMissing('items');

        if ($this->items->isEmpty()) {
            return false;
        }

        return $this->items->every(fn (ResellerOrderItem $item) => $item->isScanComplete());
    }

    public function packingScanProgress(): array
    {
        $this->loadMissing('items');
        $needed = (int) $this->items->sum('qty');
        $scanned = (int) $this->items->sum('qty_scanned');

        return [
            'needed' => $needed,
            'scanned' => min($scanned, $needed),
            'complete' => $needed > 0 && $scanned >= $needed,
        ];
    }

    public function canResellerVerify(): bool
    {
        return in_array($this->status, [
            self::STATUS_DIKIRIM,
            self::STATUS_VERIFIKASI_RESELLER,
        ], true);
    }

    public function canPortalResendClaim(): bool
    {
        if (in_array($this->status, [self::STATUS_DIBATALKAN, self::STATUS_DIRETUR], true)) {
            return false;
        }

        return in_array($this->status, [
            self::STATUS_DIKIRIM,
            self::STATUS_VERIFIKASI_RESELLER,
            self::STATUS_SELESAI,
            self::STATUS_LUNAS,
        ], true);
    }

    public function openResendRequest(): ?ResellerOrderResendRequest
    {
        return $this->resendRequests()
            ->whereIn('status', [
                ResellerOrderResendRequest::STATUS_MENUNGGU,
                ResellerOrderResendRequest::STATUS_DIPROSES,
            ])
            ->latest('id')
            ->first();
    }

    /** @return HasMany<ResellerOrderResendRequest> */
    public function resendRequests(): HasMany
    {
        return $this->hasMany(ResellerOrderResendRequest::class, 'reseller_order_id');
    }

    /** Pembayaran hanya setelah reseller menyelesaikan pesanan. */
    public function canUploadPayment(): bool
    {
        if ($this->isMoOperatorOrder()) {
            return false;
        }

        return $this->sisaTagihan() > 0
            && in_array($this->status, [self::STATUS_SELESAI], true);
    }

    public function isMoOperatorOrder(): bool
    {
        return ($this->order_source ?? self::SOURCE_RESELLER) === self::SOURCE_MO_OPERATOR;
    }

    public function flowStepIndex(): int|false
    {
        $keys = array_column(self::flowSteps(), 'key');

        if ($this->status === self::STATUS_LUNAS) {
            return count($keys) - 1;
        }

        if (in_array($this->status, [self::STATUS_DIBATALKAN, self::STATUS_DIRETUR], true)) {
            return false;
        }

        // Dikirim & verifikasi_reseller: tampilkan step sesuai status aktual
        $idx = array_search($this->status, $keys, true);

        return $idx === false ? false : (int) $idx;
    }

    public static function generateKode(?string $source = null): string
    {
        $prefix = ($source ?? self::SOURCE_RESELLER) === self::SOURCE_MO_OPERATOR
            ? 'MO'.now()->format('ymd')
            : 'RS'.now()->format('ymd');

        do {
            $suffix = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $kode = $prefix.$suffix;
        } while (self::query()->where('kode_pesanan', $kode)->exists());

        return $kode;
    }
}

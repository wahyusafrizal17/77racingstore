<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketplaceOrder extends Model
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_MENUNGGU_VERIFIKASI = 'menunggu_verifikasi';

    public const STATUS_DIPROSES = 'diproses';

    public const STATUS_MENUNGGU_DIKIRIM = 'menunggu_dikirim';

    public const STATUS_DIKIRIM = 'dikirim';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    protected $fillable = [
        'kode_pesanan',
        'warehouse_id',
        'user_id',
        'status',
        'customer_nama',
        'customer_phone',
        'customer_email',
        'alamat_lengkap',
        'provinsi_id',
        'provinsi_nama',
        'province_code',
        'kota_id',
        'kota_nama',
        'regency_code',
        'kecamatan',
        'district_code',
        'village_code',
        'kelurahan',
        'kode_pos',
        'shipping_city_id',
        'courier',
        'shipping_service',
        'shipping_service_desc',
        'shipping_etd',
        'ongkir',
        'subtotal',
        'diskon_promo',
        'diskon_voucher',
        'total_nominal',
        'total_bayar',
        'catatan',
        'marketplace_voucher_id',
        'kode_voucher',
        'no_resi',
        'paid_at',
        'submitted_at',
        'packed_at',
        'packing_scanned_at',
        'dikirim_at',
        'selesai_at',
        'dibatalkan_at',
    ];

    protected function casts(): array
    {
        return [
            'ongkir' => 'integer',
            'subtotal' => 'integer',
            'diskon_promo' => 'integer',
            'diskon_voucher' => 'integer',
            'total_nominal' => 'integer',
            'total_bayar' => 'integer',
            'paid_at' => 'datetime',
            'submitted_at' => 'datetime',
            'packed_at' => 'datetime',
            'packing_scanned_at' => 'datetime',
            'dikirim_at' => 'datetime',
            'selesai_at' => 'datetime',
            'dibatalkan_at' => 'datetime',
        ];
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING_PAYMENT => 'Menunggu Pembayaran',
            self::STATUS_MENUNGGU_VERIFIKASI => 'Menunggu Verifikasi',
            self::STATUS_DIPROSES => 'Diproses',
            self::STATUS_MENUNGGU_DIKIRIM => 'Menunggu Dikirim',
            self::STATUS_DIKIRIM => 'Sedang Dikirim',
            self::STATUS_SELESAI => 'Selesai',
            self::STATUS_DIBATALKAN => 'Dibatalkan',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? ucfirst((string) $this->status);
    }

    /** @return array<string, string> */
    public static function warehouseStatusLabels(): array
    {
        return array_replace(self::statusLabels(), [
            self::STATUS_MENUNGGU_DIKIRIM => 'Menunggu Resi',
        ]);
    }

    public function warehouseStatusLabel(): string
    {
        return self::warehouseStatusLabels()[$this->status] ?? $this->statusLabel();
    }

    /** @return list<array{key: string, label: string}> */
    public static function flowSteps(): array
    {
        return [
            ['key' => self::STATUS_PENDING_PAYMENT, 'label' => 'Menunggu Pembayaran'],
            ['key' => self::STATUS_DIPROSES, 'label' => 'Diproses'],
            ['key' => self::STATUS_MENUNGGU_DIKIRIM, 'label' => 'Menunggu Dikirim'],
            ['key' => self::STATUS_DIKIRIM, 'label' => 'Sedang Dikirim'],
            ['key' => self::STATUS_SELESAI, 'label' => 'Selesai'],
        ];
    }

    /** @return list<array{key: string, label: string}> */
    public static function warehouseFlowSteps(): array
    {
        return array_map(
            fn (array $step) => $step['key'] === self::STATUS_MENUNGGU_DIKIRIM
                ? [...$step, 'label' => 'Menunggu Resi']
                : $step,
            self::flowSteps(),
        );
    }

    public function flowStepIndex(): int|false
    {
        $keys = array_column(self::flowSteps(), 'key');
        $status = $this->status === self::STATUS_MENUNGGU_VERIFIKASI
            ? self::STATUS_DIPROSES
            : $this->status;

        return array_search($status, $keys, true);
    }

    public static function generateKode(): string
    {
        $prefix = 'MP-'.now()->format('Ymd').'-';

        do {
            $suffix = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $kode = $prefix.$suffix;
        } while (self::query()->where('kode_pesanan', $kode)->exists());

        return $kode;
    }

    public function isPendingPayment(): bool
    {
        return $this->status === self::STATUS_PENDING_PAYMENT;
    }

    /** Order already paid and eligible for warehouse queue. */
    public function isPaidForWarehouse(): bool
    {
        return in_array($this->status, [
            self::STATUS_MENUNGGU_VERIFIKASI,
            self::STATUS_DIPROSES,
            self::STATUS_MENUNGGU_DIKIRIM,
            self::STATUS_DIKIRIM,
            self::STATUS_SELESAI,
        ], true);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class);
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

        $query->where(function ($q) use ($like) {
            $q->where('kode_pesanan', 'like', $like)
                ->orWhere('no_resi', 'like', $like)
                ->orWhere('customer_nama', 'like', $like)
                ->orWhere('customer_phone', 'like', $like);

            $q->orWhereHas('items.product', function ($pq) use ($like) {
                $pq->where('nama_produk', 'like', $like)
                    ->orWhere('kode_produk', 'like', $like);
            });
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(MarketplaceVoucher::class, 'marketplace_voucher_id');
    }

    public function midtransPayments(): HasMany
    {
        return $this->hasMany(MarketplaceOrderMidtransPayment::class);
    }

    public function canWarehouseProses(): bool
    {
        return $this->status === self::STATUS_MENUNGGU_VERIFIKASI;
    }

    public function canWarehouseScan(): bool
    {
        return $this->status === self::STATUS_DIPROSES;
    }

    public function isPackingScanComplete(): bool
    {
        if (! $this->relationLoaded('items')) {
            $this->load('items');
        }

        if ($this->items->isEmpty()) {
            return false;
        }

        return $this->items->every(fn (MarketplaceOrderItem $item) => $item->isScanComplete());
    }

    /** @return array{needed: int, scanned: int, complete: bool} */
    public function packingScanProgress(): array
    {
        if (! $this->relationLoaded('items')) {
            $this->load('items');
        }
        $needed = (int) $this->items->sum('qty');
        $scanned = (int) $this->items->sum('qty_scanned');

        return [
            'needed' => $needed,
            'scanned' => min($scanned, $needed),
            'complete' => $needed > 0 && $scanned >= $needed,
        ];
    }

    public function canWarehouseSubmitPacking(): bool
    {
        return $this->status === self::STATUS_DIPROSES
            && $this->isPackingScanComplete();
    }

    public function canWarehouseKirim(): bool
    {
        return $this->status === self::STATUS_MENUNGGU_DIKIRIM;
    }

    public function canWarehouseSelesai(): bool
    {
        return $this->status === self::STATUS_DIKIRIM;
    }
}

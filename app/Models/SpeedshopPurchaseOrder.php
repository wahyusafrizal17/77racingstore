<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SpeedshopPurchaseOrder extends Model
{
    protected $table = 'speedshop_purchase_orders';

    protected $fillable = [
        'kode',
        'invoice_no',
        'speedshop_id',
        'warehouse_id',
        'tipe',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejected_reason',
        'catatan',
        'mo_status',
        'mo_kode',
        'mo_no_transaksi',
        'mo_reason',
        'mo_tagihan',
        'mo_synced_at',
        'mo_sync_error',
        'received_at',
        'received_by',
    ];

    /** Belum berhasil terkirim ke API Modifikasi Ori (bisa dikirim ulang, idempoten). */
    public const MO_STATUS_SEND_FAILED = 'send_failed';

    public const MO_STATUS_SUBMITTED = 'submitted';

    public const MO_STATUS_APPROVED = 'approved';

    public const MO_STATUS_REJECTED_BY_WAREHOUSE = 'rejected_by_warehouse';

    public const MO_STATUS_CANCELLED = 'cancelled';

    public const MO_STATUS_SHIPPED = 'shipped';

    public const MO_STATUS_RECEIVED = 'received';

    public const MO_STATUS_REJECTED_BY_PARTNER = 'rejected_by_partner';

    /** Status final: tidak perlu disinkronkan lagi. */
    public const MO_FINAL_STATUSES = [
        self::MO_STATUS_REJECTED_BY_WAREHOUSE,
        self::MO_STATUS_CANCELLED,
        self::MO_STATUS_RECEIVED,
        self::MO_STATUS_REJECTED_BY_PARTNER,
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'mo_tagihan' => 'array',
            'mo_synced_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    /** PO yang dikirim ke warehouse Modifikasi Ori via API (bukan PO lama lokal). */
    public function isModifikasiOriPo(): bool
    {
        return $this->mo_status !== null;
    }

    public function isMoFinal(): bool
    {
        return in_array($this->mo_status, self::MO_FINAL_STATUSES, true);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function moStatusLabel(): string
    {
        return match ($this->mo_status) {
            self::MO_STATUS_SEND_FAILED => 'Gagal terkirim',
            self::MO_STATUS_SUBMITTED => 'Menunggu diterima warehouse',
            self::MO_STATUS_APPROVED => 'Diterima warehouse, menunggu pengiriman',
            self::MO_STATUS_SHIPPED => 'Dikirim — menunggu konfirmasi penerimaan',
            self::MO_STATUS_RECEIVED => 'Selesai — barang diterima',
            self::MO_STATUS_REJECTED_BY_WAREHOUSE => 'Ditolak warehouse',
            self::MO_STATUS_CANCELLED => 'Dibatalkan',
            self::MO_STATUS_REJECTED_BY_PARTNER => 'Penerimaan ditolak',
            default => '-',
        };
    }

    public function speedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SpeedshopPurchaseOrderItem::class, 'purchase_order_id');
    }

    /** Draft transaksi offline yang dibuat bersamaan dengan PO (verifikasi stok Speedshop). */
    public function mirroredTransaksiOffline(): HasOne
    {
        return $this->hasOne(TransaksiOffline::class);
    }

    public function tagihan(): HasOne
    {
        return $this->hasOne(SpeedshopPoTagihan::class, 'speedshop_purchase_order_id');
    }

    public static function generateKode(): string
    {
        $prefix = 'PO-'.now()->format('Ymd');
        $last = static::where('kode', 'like', $prefix.'%')->orderBy('kode', 'desc')->first();
        $seq = $last ? (int) substr((string) $last->kode, -4) + 1 : 1;

        return $prefix.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Format: PO/SS/KTP/04/0001
     * Reset nomor urut setiap pergantian tahun.
     */
    public static function generateKodeSpeedshop(int $speedshopId, ?Carbon $tanggal = null): string
    {
        $tanggal = $tanggal ?: now();
        $year = (int) $tanggal->format('Y');
        $month = $tanggal->format('m');

        $speedshop = Speedshop::query()->find($speedshopId);
        $outletCode = strtoupper($speedshop?->invoiceOutletCode() ?: 'UNK');

        $base = "PO/SS/{$outletCode}/{$month}/";
        $like = $base.'%';

        $last = static::query()
            ->where('kode', 'like', $like)
            ->whereYear('created_at', $year)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->first();

        $next = 1;
        if ($last !== null) {
            $tail = substr((string) $last->kode, strlen($base));
            $next = max(1, (int) $tail) + 1;
        }

        return $base.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}

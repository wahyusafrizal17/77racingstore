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
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class WarehouseResellerTagihanPembayaran extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $table = 'warehouse_reseller_tagihan_pembayarans';

    protected $fillable = [
        'tagihan_id',
        'nominal',
        'status',
        'batch_key',
        'speedshop_keuangan_rekening_id',
        'bukti_bayar_path',
        'catatan_pembayaran',
        'dibayar_at',
        'dibayar_by',
        'submitted_by',
        'approved_by',
        'approved_at',
        'rejected_at',
        'catatan_penolakan',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
            'dibayar_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(WarehouseResellerTagihan::class, 'tagihan_id');
    }

    public function sumberRekening(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'speedshop_keuangan_rekening_id');
    }

    public function dibayarByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibayar_by');
    }

    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return ($this->status ?? self::STATUS_APPROVED) === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function scopeApproved(Builder $query): Builder
    {
        if (! Schema::hasColumn($this->getTable(), 'status')) {
            return $query;
        }

        return $query->where(function (Builder $q) {
            $q->where('status', self::STATUS_APPROVED)
                ->orWhereNull('status');
        });
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function buktiUrl(): ?string
    {
        if (! filled($this->bukti_bayar_path) || str_starts_with((string) $this->bukti_bayar_path, 'midtrans://')) {
            return null;
        }

        return \App\Support\UploadStorage::url($this->bukti_bayar_path);
    }

    public static function pendingNominalForTagihan(int $tagihanId): int
    {
        if ($tagihanId <= 0) {
            return 0;
        }

        return (int) (static::pendingNominalMapByTagihanIds([$tagihanId])[$tagihanId] ?? 0);
    }

    /**
     * @param  list<int>  $tagihanIds
     * @return array<int, int> tagihan_id => pending nominal
     */
    public static function pendingNominalMapByTagihanIds(array $tagihanIds): array
    {
        $tagihanIds = array_values(array_unique(array_filter(array_map('intval', $tagihanIds))));
        if ($tagihanIds === [] || ! Schema::hasTable((new static)->getTable())) {
            return [];
        }

        if (! Schema::hasColumn((new static)->getTable(), 'status')) {
            return [];
        }

        return static::query()
            ->pending()
            ->whereIn('tagihan_id', $tagihanIds)
            ->groupBy('tagihan_id')
            ->selectRaw('tagihan_id, COALESCE(SUM(nominal), 0) as pending_nominal')
            ->pluck('pending_nominal', 'tagihan_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    public static function pendingNominalForReseller(int $warehouseResellerId): int
    {
        if ($warehouseResellerId <= 0 || ! Schema::hasTable((new static)->getTable())) {
            return 0;
        }

        if (! Schema::hasColumn((new static)->getTable(), 'status')) {
            return 0;
        }

        return (int) static::query()
            ->pending()
            ->whereHas('tagihan', fn (Builder $q) => $q->where('warehouse_reseller_id', $warehouseResellerId))
            ->sum('nominal');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TransaksiOnline extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_BATAL = 'batal';

    protected $table = 'transaksi_online';

    protected $fillable = [
        'no_resi',
        'catatan',
        'user_id',
        'warehouse_id',
        'warehouse_reseller_id',
        'warehouse_taker_id',
        'warehouse_packer_id',
        'no_urut_harian',
        'status',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'voided_at' => 'datetime',
        ];
    }

    public function isDraft(): bool
    {
        return ($this->status ?? self::STATUS_SELESAI) === self::STATUS_DRAFT;
    }

    public function isBatal(): bool
    {
        return ($this->status ?? '') === self::STATUS_BATAL;
    }

    public function isSelesai(): bool
    {
        return ($this->status ?? self::STATUS_SELESAI) === self::STATUS_SELESAI;
    }

    public function scopeSelesai(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SELESAI);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function items()
    {
        return $this->hasMany(TransaksiOnlineItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseReseller()
    {
        return $this->belongsTo(WarehouseReseller::class, 'warehouse_reseller_id');
    }

    public function warehouseTaker()
    {
        return $this->belongsTo(WarehouseTaker::class, 'warehouse_taker_id');
    }

    public function warehousePacker()
    {
        return $this->belongsTo(WarehousePacker::class, 'warehouse_packer_id');
    }

    public function resellerTagihan()
    {
        return $this->hasOne(WarehouseResellerTagihan::class, 'transaksi_online_id');
    }
}

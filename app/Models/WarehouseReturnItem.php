<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseReturnItem extends Model
{
    public const KONDISI_BAIK = 'baik';

    public const KONDISI_CACAT = 'cacat';

    protected $fillable = [
        'warehouse_return_id',
        'product_id',
        'qty',
        'harga_satuan',
        'subtotal',
        'kondisi',
        'catatan',
    ];

    protected $casts = [
        'qty' => 'integer',
        'harga_satuan' => 'integer',
        'subtotal' => 'integer',
    ];

    public static function kondisiLabels(): array
    {
        return [
            self::KONDISI_BAIK => 'Layak',
            self::KONDISI_CACAT => 'Tidak layak',
        ];
    }

    public function kondisiLabel(): string
    {
        return self::kondisiLabels()[$this->kondisi] ?? ucfirst((string) $this->kondisi);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouseReturn(): BelongsTo
    {
        return $this->belongsTo(WarehouseReturn::class);
    }
}

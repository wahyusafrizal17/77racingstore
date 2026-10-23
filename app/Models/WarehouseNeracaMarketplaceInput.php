<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseNeracaMarketplaceInput extends Model
{
    public const FIELD_SALDO = 'saldo';

    public const FIELD_BISA_DITARIK = 'bisa_ditarik';

    public const FIELD_PENDING = 'pending';

    /** @var list<string> */
    public const MARKETPLACE_KEYS = [
        'shopee_mod',
        'tiktok',
        'lazada',
        'blibli_mod',
    ];

    /** @var list<string> */
    public const FIELDS = [
        self::FIELD_SALDO,
        self::FIELD_BISA_DITARIK,
        self::FIELD_PENDING,
    ];

    protected $fillable = [
        'warehouse_id',
        'periode_bulan',
        'marketplace_key',
        'field',
        'nominal',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'decimal:2',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function fieldLabel(string $field): string
    {
        return match ($field) {
            self::FIELD_SALDO => 'Total Saldo',
            self::FIELD_BISA_DITARIK => 'Dana Bisa Ditarik',
            self::FIELD_PENDING => 'Dana Pending',
            default => $field,
        };
    }

    public static function marketplaceLabel(string $key): string
    {
        return match ($key) {
            'shopee_mod' => 'Shopee',
            'tiktok' => 'TikTok',
            'lazada' => 'Lazada',
            'blibli_mod' => 'Blibli',
            default => $key,
        };
    }
}

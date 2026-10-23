<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseResellerChat extends Model
{
    protected $table = 'warehouse_reseller_chats';

    protected $fillable = [
        'warehouse_id',
        'warehouse_reseller_id',
        'handler_user_id',
        'last_message_at',
        'last_message_preview',
        'reseller_last_read_at',
        'warehouse_last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'reseller_last_read_at' => 'datetime',
            'warehouse_last_read_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseReseller(): BelongsTo
    {
        return $this->belongsTo(WarehouseReseller::class, 'warehouse_reseller_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handler_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WarehouseResellerChatMessage::class, 'chat_id');
    }

    public function unreadForReseller(): int
    {
        $q = $this->messages()->where('sender_side', WarehouseResellerChatMessage::SIDE_WAREHOUSE);
        if ($this->reseller_last_read_at) {
            $q->where('created_at', '>', $this->reseller_last_read_at);
        }

        return (int) $q->count();
    }

    public function unreadForWarehouse(): int
    {
        $q = $this->messages()->where('sender_side', WarehouseResellerChatMessage::SIDE_RESELLER);
        if ($this->warehouse_last_read_at) {
            $q->where('created_at', '>', $this->warehouse_last_read_at);
        }

        return (int) $q->count();
    }
}

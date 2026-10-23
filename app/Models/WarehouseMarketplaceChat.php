<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseMarketplaceChat extends Model
{
    protected $table = 'warehouse_marketplace_chats';

    protected $fillable = [
        'warehouse_id',
        'user_id',
        'handler_user_id',
        'last_message_at',
        'last_message_preview',
        'customer_last_read_at',
        'warehouse_last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'customer_last_read_at' => 'datetime',
            'warehouse_last_read_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handler_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WarehouseMarketplaceChatMessage::class, 'chat_id');
    }

    public function unreadForCustomer(): int
    {
        $q = $this->messages()->where('sender_side', WarehouseMarketplaceChatMessage::SIDE_WAREHOUSE);
        if ($this->customer_last_read_at) {
            $q->where('created_at', '>', $this->customer_last_read_at);
        }

        return (int) $q->count();
    }

    public function unreadForWarehouse(): int
    {
        $q = $this->messages()->where('sender_side', WarehouseMarketplaceChatMessage::SIDE_CUSTOMER);
        if ($this->warehouse_last_read_at) {
            $q->where('created_at', '>', $this->warehouse_last_read_at);
        }

        return (int) $q->count();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseMarketplaceChatMessage extends Model
{
    public const SIDE_CUSTOMER = 'customer';

    public const SIDE_WAREHOUSE = 'warehouse';

    protected $table = 'warehouse_marketplace_chat_messages';

    protected $fillable = [
        'chat_id',
        'user_id',
        'sender_side',
        'body',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(WarehouseMarketplaceChat::class, 'chat_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isFromCustomer(): bool
    {
        return $this->sender_side === self::SIDE_CUSTOMER;
    }
}

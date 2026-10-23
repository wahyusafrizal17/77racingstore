<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseResellerChatMessage extends Model
{
    public const SIDE_RESELLER = 'reseller';

    public const SIDE_WAREHOUSE = 'warehouse';

    public const TYPE_TEXT = 'text';

    public const TYPE_ORDER = 'order';

    protected $table = 'warehouse_reseller_chat_messages';

    protected $fillable = [
        'chat_id',
        'user_id',
        'sender_side',
        'body',
        'message_type',
        'reseller_order_id',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(WarehouseResellerChat::class, 'chat_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ResellerOrder::class, 'reseller_order_id');
    }

    public function isFromReseller(): bool
    {
        return $this->sender_side === self::SIDE_RESELLER;
    }

    public function isOrderMessage(): bool
    {
        return $this->message_type === self::TYPE_ORDER || $this->reseller_order_id !== null;
    }

    public function hasAttachment(): bool
    {
        return filled($this->attachment_path);
    }

    public function attachmentUrl(): ?string
    {
        if (! $this->hasAttachment()) {
            return null;
        }

        return \App\Support\UploadStorage::url((string) $this->attachment_path);
    }

    public function attachmentIsImage(): bool
    {
        $mime = (string) ($this->attachment_mime ?? '');

        return str_starts_with($mime, 'image/');
    }
}

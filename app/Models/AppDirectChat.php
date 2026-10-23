<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppDirectChat extends Model
{
    protected $table = 'app_direct_chats';

    protected $fillable = [
        'pair_key',
        'user_low_id',
        'user_high_id',
        'last_message_at',
        'last_message_preview',
        'low_last_read_at',
        'high_last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'low_last_read_at' => 'datetime',
            'high_last_read_at' => 'datetime',
        ];
    }

    public function userLow(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_low_id');
    }

    public function userHigh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_high_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AppDirectChatMessage::class, 'chat_id');
    }

    public function otherUserId(int $viewerId): int
    {
        return (int) $this->user_low_id === $viewerId
            ? (int) $this->user_high_id
            : (int) $this->user_low_id;
    }

    public function involvesUser(int $userId): bool
    {
        return in_array($userId, [(int) $this->user_low_id, (int) $this->user_high_id], true);
    }

    public function unreadFor(int $viewerId): int
    {
        $isLow = (int) $this->user_low_id === $viewerId;
        $readAt = $isLow ? $this->low_last_read_at : $this->high_last_read_at;

        $q = $this->messages()->where('user_id', '!=', $viewerId);
        if ($readAt) {
            $q->where('created_at', '>', $readAt);
        }

        return (int) $q->count();
    }
}

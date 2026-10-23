<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppDirectChatMessage extends Model
{
    protected $table = 'app_direct_chat_messages';

    protected $fillable = [
        'chat_id',
        'user_id',
        'body',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
    ];

    public function hasAttachment(): bool
    {
        return filled($this->attachment_path);
    }

    public function attachmentUrl(): ?string
    {
        if (! $this->hasAttachment()) {
            return null;
        }

        $disk = (string) config('filesystems.upload_disk', 'public');

        return \Illuminate\Support\Facades\Storage::disk($disk)->url((string) $this->attachment_path);
    }

    public function attachmentIsImage(): bool
    {
        $mime = (string) ($this->attachment_mime ?? '');

        return str_starts_with($mime, 'image/');
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(AppDirectChat::class, 'chat_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

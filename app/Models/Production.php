<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Production extends Model
{
    protected $fillable = [
        'kode', 'tanggal', 'user_id', 'status',
        'reported_by', 'reported_at', 'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'reported_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ProductionOutput::class);
    }

    public function anggotas(): BelongsToMany
    {
        return $this->belongsToMany(Anggota::class, 'production_anggota')->withTimestamps();
    }

    public function isProses(): bool
    {
        return $this->status === 'proses';
    }

    public function isSelesai(): bool
    {
        return $this->status === 'selesai';
    }

    /**
     * Nomor urut 4 digit reset setiap bulan. Format: PRD-YYYYMM-0001.
     */
    public static function generateKode(): string
    {
        $ym = now()->format('Ym');
        $prefix = "PRD-{$ym}-";

        $maxSeq = static::query()
            ->where('kode', 'like', $prefix.'%')
            ->pluck('kode')
            ->map(function (string $kode) use ($prefix) {
                if (! str_starts_with($kode, $prefix)) {
                    return 0;
                }
                $suffix = substr($kode, strlen($prefix));

                return preg_match('/^\d{4}$/', $suffix) ? (int) $suffix : 0;
            })
            ->max() ?? 0;

        $next = $maxSeq + 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}

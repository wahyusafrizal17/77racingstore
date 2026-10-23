<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mekanik extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mekanik_level_id',
        'speedshop_id',
        'nama',
        'no_hp',
        'spesialisasi',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mekanikLevel()
    {
        return $this->belongsTo(MekanikLevel::class);
    }

    public function speedshop()
    {
        return $this->belongsTo(Speedshop::class);
    }

    public function serviceOrders()
    {
        return $this->hasMany(ServiceOrder::class);
    }

    public function levelName(): ?string
    {
        return $this->mekanikLevel?->nama ?? $this->spesialisasi;
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif');
    }

    public function scopeForSpeedshop($query, ?int $speedshopId)
    {
        if (! $speedshopId) {
            return $query;
        }

        return $query->where(function ($q) use ($speedshopId) {
            $q->where('speedshop_id', $speedshopId)
                ->orWhereNull('speedshop_id');
        });
    }

    public static function aktifForSpeedshop(?int $speedshopId)
    {
        return static::query()
            ->with('mekanikLevel')
            ->aktif()
            ->forSpeedshop($speedshopId)
            ->orderBy('nama');
    }
}

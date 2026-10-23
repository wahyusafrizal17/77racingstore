<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kendaraan extends Model
{
    use HasFactory;

    protected $fillable = ['pelanggan_id', 'nomor_polisi', 'merk', 'tipe', 'tahun'];

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class);
    }

    public function serviceOrders()
    {
        return $this->hasMany(ServiceOrder::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->nomor_polisi} - {$this->merk} {$this->tipe}";
    }
}

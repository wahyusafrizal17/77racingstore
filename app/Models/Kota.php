<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kota extends Model
{
    use HasFactory;

    protected $fillable = ['nama'];

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function pelanggans()
    {
        return $this->hasMany(Pelanggan::class);
    }
}

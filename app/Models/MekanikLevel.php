<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MekanikLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
    ];

    public function mekaniks()
    {
        return $this->hasMany(Mekanik::class);
    }
}


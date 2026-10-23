<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JasaServis extends Model
{
    use HasFactory;

    protected $table = 'jasa_servis';

    protected $fillable = ['nama', 'biaya'];

    protected function casts(): array
    {
        return [
            'biaya' => 'integer',
        ];
    }
}

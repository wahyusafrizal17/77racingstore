<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Anggota extends Model
{
    protected $table = 'anggotas';

    protected $fillable = ['nama', 'email', 'no_hp'];

    public function productions(): BelongsToMany
    {
        return $this->belongsToMany(Production::class, 'production_anggota')->withTimestamps();
    }
}

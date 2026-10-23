<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeedshopBranchTarget extends Model
{
    protected $fillable = [
        'speedshop_id',
        'periode_bulan',
        'target_bulanan',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'target_bulanan' => 'integer',
        ];
    }

    public function speedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class);
    }
}

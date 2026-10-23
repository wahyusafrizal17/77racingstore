<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeedshopFinanceSetoranItem extends Model
{
    protected $table = 'speedshop_finance_setoran_items';

    protected $fillable = [
        'setoran_id',
        'sumber',
        'nominal',
        'sumber_kas_besar_id',
        'sumber_rekening_id',
        'tujuan_type',
        'tujuan_rekening_id',
        'bukti_path',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'setoran_id' => 'integer',
            'nominal' => 'integer',
            'sumber_kas_besar_id' => 'integer',
            'sumber_rekening_id' => 'integer',
            'tujuan_rekening_id' => 'integer',
        ];
    }

    public function setoran(): BelongsTo
    {
        return $this->belongsTo(SpeedshopFinanceSetoran::class, 'setoran_id');
    }

    public function sumberKasBesar(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'sumber_kas_besar_id');
    }

    public function sumberRekeningUtama(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'sumber_rekening_id');
    }

    public function tujuanRekening(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'tujuan_rekening_id');
    }
}


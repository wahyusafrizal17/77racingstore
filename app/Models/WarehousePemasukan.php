<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehousePemasukan extends Model
{
    public const TIPE_TUNAI = 'tunai';

    public const TIPE_REKENING = 'rekening';

    public const TIPE_PIHAK_LAIN = 'pihak_lain';

    protected $table = 'warehouse_pemasukans';

    protected $fillable = [
        'warehouse_id',
        'tanggal',
        'keterangan',
        'sumber_tipe',
        'sumber_rekening_id',
        'tujuan_tipe',
        'tujuan_rekening_id',
        'nominal',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'float',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function sumberRekening(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'sumber_rekening_id');
    }

    public function tujuanRekening(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'tujuan_rekening_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(SpeedshopFinanceLedger::class, 'warehouse_pemasukan_id');
    }

    public function sumberLabel(): string
    {
        if (($this->sumber_tipe ?? '') === self::TIPE_PIHAK_LAIN) {
            return 'Pihak lain';
        }

        if (($this->sumber_tipe ?? '') === self::TIPE_TUNAI) {
            return 'Tunai';
        }

        $rek = $this->sumberRekening;
        if (! $rek) {
            return 'Rekening';
        }

        return SpeedshopKeuanganRekening::jenisLabel($rek->jenis).' — '.$rek->nama;
    }

    public function tujuanLabel(): string
    {
        if (($this->tujuan_tipe ?? '') === self::TIPE_TUNAI) {
            return 'Tunai';
        }

        $rek = $this->tujuanRekening;
        if (! $rek) {
            return 'Rekening';
        }

        return SpeedshopKeuanganRekening::jenisLabel($rek->jenis).' — '.$rek->nama;
    }

    public function scopeForWarehouse($query, ?int $warehouseId)
    {
        return $warehouseId ? $query->where('warehouse_id', $warehouseId) : $query->whereRaw('1 = 0');
    }
}

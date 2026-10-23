<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseSupplierTagihanPembayaran extends Model
{
    public const JENIS_TUNAI = 'tunai';

    public const JENIS_TRANSFER = 'transfer';

    protected $table = 'warehouse_supplier_tagihan_pembayarans';

    protected $fillable = [
        'tagihan_id',
        'nominal',
        'jenis_pembayaran',
        'speedshop_keuangan_rekening_id',
        'bukti_bayar_path',
        'catatan_pembayaran',
        'dibayar_at',
        'dibayar_by',
    ];

    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
            'dibayar_at' => 'datetime',
        ];
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(WarehouseSupplierTagihan::class, 'tagihan_id');
    }

    public function sumberRekening(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'speedshop_keuangan_rekening_id');
    }

    public function dibayarByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibayar_by');
    }

    /** @return array<string, string> */
    public static function jenisPembayaranOptions(): array
    {
        return [
            self::JENIS_TUNAI => 'Tunai',
            self::JENIS_TRANSFER => 'Transfer',
        ];
    }

    public static function jenisPembayaranLabel(?string $jenis): string
    {
        $jenis = strtolower(trim((string) $jenis));

        return self::jenisPembayaranOptions()[$jenis] ?? ($jenis !== '' ? $jenis : '—');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpeedshopFinanceLedger extends Model
{
    public $timestamps = false;

    protected $table = 'speedshop_finance_ledgers';

    protected $fillable = [
        'speedshop_id',
        'warehouse_id',
        'owner_user_id',
        'tanggal',
        'pocket',
        'amount',
        'reason',
        'invoice_id',
        'setoran_id',
        'owner_modal_setoran_id',
        'speedshop_rincian_biaya_entry_id',
        'warehouse_piutang_id',
        'warehouse_pemasukan_id',
        'warehouse_supplier_tagihan_id',
        'warehouse_supplier_tagihan_pembayaran_id',
        'speedshop_keuangan_rekening_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'amount' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function speedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function setoran(): BelongsTo
    {
        return $this->belongsTo(SpeedshopFinanceSetoran::class, 'setoran_id');
    }

    public function ownerModalSetoran(): BelongsTo
    {
        return $this->belongsTo(SpeedshopOwnerModalSetoran::class, 'owner_modal_setoran_id');
    }

    public function rincianBiayaEntry(): BelongsTo
    {
        return $this->belongsTo(SpeedshopRincianBiayaEntry::class, 'speedshop_rincian_biaya_entry_id');
    }

    public function warehousePiutang(): BelongsTo
    {
        return $this->belongsTo(WarehousePiutang::class, 'warehouse_piutang_id');
    }

    public function warehousePemasukan(): BelongsTo
    {
        return $this->belongsTo(WarehousePemasukan::class, 'warehouse_pemasukan_id');
    }

    public function warehouseSupplierTagihan(): BelongsTo
    {
        return $this->belongsTo(WarehouseSupplierTagihan::class, 'warehouse_supplier_tagihan_id');
    }

    public function warehouseSupplierTagihanPembayaran(): BelongsTo
    {
        return $this->belongsTo(WarehouseSupplierTagihanPembayaran::class, 'warehouse_supplier_tagihan_pembayaran_id');
    }

    public function keuanganRekening(): BelongsTo
    {
        return $this->belongsTo(SpeedshopKeuanganRekening::class, 'speedshop_keuangan_rekening_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

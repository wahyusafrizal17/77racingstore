<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockIn extends Model
{
    use HasFactory;

    protected $fillable = ['kode', 'supplier_id', 'user_id', 'status', 'approved_by', 'approved_at', 'rejected_reason', 'catatan'];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(StockInItem::class);
    }

    public function supplierTagihan(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(WarehouseSupplierTagihan::class, 'stock_in_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public static function generateKode(): string
    {
        $prefix = 'SI-' . now()->format('Ymd');
        $last = static::where('kode', 'like', $prefix . '%')->orderBy('kode', 'desc')->first();
        $seq = $last ? (int) substr($last->kode, -4) + 1 : 1;
        return $prefix . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}

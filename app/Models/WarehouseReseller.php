<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseReseller extends Model
{
    public const KATEGORI_CANVASSING = 'CANVASSING';

    public const KATEGORI_OFFLINE = 'OFFLINE';

    public const KATEGORI_ONLINE = 'ONLINE';

    /** Kategori tampilan dana talang untuk nama ketikan (bukan baris master reseller). */
    public const KATEGORI_UMUM = 'UMUM';

    protected $table = 'warehouse_resellers';

    protected $fillable = [
        'warehouse_id',
        'nama',
        'email',
        'kategori',
        'no_hp',
        'alamat',
        'is_active',
        'is_mo_channel',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_mo_channel' => 'boolean',
        ];
    }

    /** @return list<string> */
    public static function kategoriOptions(): array
    {
        return [
            self::KATEGORI_CANVASSING,
            self::KATEGORI_OFFLINE,
            self::KATEGORI_ONLINE,
        ];
    }

    /** Kategori reseller yang dipakai di transaksi offline (bukan penjualan online). */
    public static function kategoriOptionsTransaksiOffline(): array
    {
        return [
            self::KATEGORI_CANVASSING,
            self::KATEGORI_OFFLINE,
        ];
    }

    /** @return non-empty-string */
    public static function danaTalangKategoriFromNama(string $nama): string
    {
        return trim($nama).' ('.self::KATEGORI_UMUM.')';
    }

    public static function danaTalangNamaFromKategori(?string $kategori): ?string
    {
        $kategori = trim((string) $kategori);
        if ($kategori === '') {
            return null;
        }

        $suffix = ' ('.self::KATEGORI_UMUM.')';
        if (! str_ends_with($kategori, $suffix)) {
            return null;
        }

        $nama = trim(substr($kategori, 0, -strlen($suffix)));

        return $nama !== '' ? $nama : null;
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function transaksiOffline(): HasMany
    {
        return $this->hasMany(TransaksiOffline::class, 'warehouse_reseller_id');
    }

    public function transaksiOnline(): HasMany
    {
        return $this->hasMany(TransaksiOnline::class, 'warehouse_reseller_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'warehouse_reseller_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ResellerOrder::class, 'warehouse_reseller_id');
    }

    public function moOperatorChannels(): HasMany
    {
        return $this->hasMany(MoOperatorChannel::class, 'warehouse_reseller_id');
    }

    public function scopeMoChannels($query)
    {
        return $query->where('is_mo_channel', true);
    }

    public function scopeForWarehouse($query, ?int $warehouseId)
    {
        if ($warehouseId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('warehouse_id', $warehouseId);
    }

    /** Reseller yang boleh dipilih di Stock Out (semua kategori). */
    public function scopeForStockOut($query)
    {
        return $query->whereIn('kategori', self::kategoriOptions());
    }
}

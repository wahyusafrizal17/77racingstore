<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class SpeedshopMasterProductArchive extends Model
{
    protected $fillable = [
        'speedshop_id',
        'product_id',
        'original_kode_produk',
        'archived_at',
        'purged_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
        'purged_at' => 'datetime',
    ];

    public function speedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function isArchivedForOutlet(int $speedshopId, int $productId): bool
    {
        $query = static::query()
            ->where('speedshop_id', $speedshopId)
            ->where('product_id', $productId);

        if (self::supportsPurgedAtColumn()) {
            $query->whereNull('purged_at');
        }

        return $query->exists();
    }

    /**
     * Bebaskan unique (warehouse + scope + kode) agar upload kode yang sama
     * membuat baris produk baru, terpisah dari arsip outlet.
     */
    public static function releaseOutletScopedKode(Product $product, int $speedshopId): void
    {
        if ((int) $product->speedshop_scope_id !== $speedshopId) {
            return;
        }

        $marker = self::archivedKodePlaceholder((int) $product->id);
        if ((string) $product->kode_produk === $marker) {
            return;
        }

        $product->forceFill(['kode_produk' => $marker])->save();
    }

    public static function archivedKodePlaceholder(int $productId): string
    {
        return '_A'.$productId;
    }

    /**
     * Kembalikan kode asli setelah un-archive, jika belum dipakai produk aktif lain.
     *
     * @return bool false jika kode sudah dipakai produk lain
     */
    public static function restoreOutletScopedKode(Product $product, ?string $originalKode): bool
    {
        $originalKode = trim((string) $originalKode);
        if ($originalKode === '') {
            return true;
        }

        $taken = Product::query()
            ->where('warehouse_id', $product->warehouse_id)
            ->where('speedshop_scope_id', (int) $product->speedshop_scope_id)
            ->where('kode_produk', $originalKode)
            ->whereKeyNot($product->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($taken) {
            return false;
        }

        if ((string) $product->kode_produk !== $originalKode) {
            $product->forceFill(['kode_produk' => $originalKode])->save();
        }

        return true;
    }

    /**
     * @param  int|list<int>  $productIds
     */
    public static function restoreForOutlet(int $speedshopId, int|array $productIds): int
    {
        $ids = array_values(array_filter(array_map('intval', is_array($productIds) ? $productIds : [$productIds])));

        if ($ids === []) {
            return 0;
        }

        $archives = static::query()
            ->where('speedshop_id', $speedshopId)
            ->whereIn('product_id', $ids)
            ->whereNull('purged_at')
            ->get();

        $restored = 0;
        foreach ($archives as $archive) {
            $product = Product::query()->find($archive->product_id);
            if ($product) {
                $original = $archive->original_kode_produk;
                if ($original === null || $original === '') {
                    $original = null;
                }
                if (! self::restoreOutletScopedKode($product, $original)) {
                    continue;
                }
            }

            $archive->delete();
            $restored++;
        }

        return $restored;
    }

    public static function supportsOriginalKodeColumn(): bool
    {
        return Schema::hasColumn((new static)->getTable(), 'original_kode_produk');
    }

    public static function supportsPurgedAtColumn(): bool
    {
        return Schema::hasColumn((new static)->getTable(), 'purged_at');
    }
}

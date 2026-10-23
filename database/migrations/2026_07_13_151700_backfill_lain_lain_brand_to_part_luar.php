<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('brands') || ! Schema::hasColumn('products', 'jenis_produk')) {
            return;
        }

        $brandIds = DB::table('brands')
            ->where(function ($query) {
                $query->whereRaw('LOWER(TRIM(nama)) = ?', ['part luar'])
                    ->orWhereRaw('LOWER(TRIM(nama)) = ?', ['lain-lain']);
            })
            ->pluck('id');

        if ($brandIds->isEmpty()) {
            return;
        }

        DB::table('products')
            ->whereIn('brand_id', $brandIds->all())
            ->update(['jenis_produk' => 'part_luar']);
    }

    public function down(): void
    {
        // Tidak di-revert: pemisahan katalog part luar vs produk ori.
    }
};

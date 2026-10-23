<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'jenis_produk')) {
                $table->string('jenis_produk', 20)->default('ori')->after('speedshop_scope_id');
                $table->index('jenis_produk');
            }
        });

        // Backfill: brand Part Luar & LAIN-LAIN → part_luar
        if (Schema::hasTable('brands') && Schema::hasColumn('products', 'jenis_produk')) {
            $brandIds = DB::table('brands')
                ->where(function ($query) {
                    $query->whereRaw('LOWER(TRIM(nama)) = ?', ['part luar'])
                        ->orWhereRaw('LOWER(TRIM(nama)) = ?', ['lain-lain']);
                })
                ->pluck('id');

            if ($brandIds->isNotEmpty()) {
                DB::table('products')
                    ->whereIn('brand_id', $brandIds->all())
                    ->update(['jenis_produk' => 'part_luar']);
            }
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'jenis_produk')) {
                $table->dropIndex(['jenis_produk']);
                $table->dropColumn('jenis_produk');
            }
        });
    }
};

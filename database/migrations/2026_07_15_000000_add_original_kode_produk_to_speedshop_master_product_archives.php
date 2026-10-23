<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('speedshop_master_product_archives')) {
            return;
        }

        Schema::table('speedshop_master_product_archives', function (Blueprint $table) {
            if (! Schema::hasColumn('speedshop_master_product_archives', 'original_kode_produk')) {
                $table->string('original_kode_produk', 50)->nullable()->after('product_id');
            }
        });

        // Bebaskan unik kode untuk arsip outlet-scoped yang sudah ada, supaya upload ulang kode sama tidak bentrok.
        $archives = DB::table('speedshop_master_product_archives as a')
            ->join('products as p', 'p.id', '=', 'a.product_id')
            ->whereColumn('p.speedshop_scope_id', 'a.speedshop_id')
            ->whereNull('p.deleted_at')
            ->select([
                'a.id as archive_id',
                'a.product_id',
                'a.original_kode_produk',
                'p.kode_produk',
            ])
            ->get();

        foreach ($archives as $row) {
            $placeholder = '_A'.$row->product_id;
            $current = (string) $row->kode_produk;

            if (($row->original_kode_produk === null || $row->original_kode_produk === '')
                && $current !== ''
                && $current !== $placeholder
            ) {
                DB::table('speedshop_master_product_archives')
                    ->where('id', $row->archive_id)
                    ->update(['original_kode_produk' => $current]);
            }

            if ($current !== $placeholder) {
                DB::table('products')
                    ->where('id', $row->product_id)
                    ->update(['kode_produk' => $placeholder]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('speedshop_master_product_archives')) {
            return;
        }

        Schema::table('speedshop_master_product_archives', function (Blueprint $table) {
            if (Schema::hasColumn('speedshop_master_product_archives', 'original_kode_produk')) {
                $table->dropColumn('original_kode_produk');
            }
        });
    }
};

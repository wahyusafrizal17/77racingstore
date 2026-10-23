<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $referenced = [
            'warehouses',
            'warehouse_transaksis',
            'warehouse_transaksi_items',
            'warehouse_supplier_tagihans',
            'warehouse_supplier_tagihan_items',
            'warehouse_supplier_tagihan_pembayarans',
        ];

        $foreignKeys = DB::select(
            'SELECT TABLE_NAME, CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IN ('.implode(',', array_fill(0, count($referenced), '?')).')',
            $referenced
        );

        $seen = [];
        foreach ($foreignKeys as $fk) {
            $key = $fk->TABLE_NAME.'.'.$fk->CONSTRAINT_NAME;
            if (isset($seen[$key]) || ! Schema::hasTable($fk->TABLE_NAME)) {
                continue;
            }
            $seen[$key] = true;
            Schema::table($fk->TABLE_NAME, function (Blueprint $table) use ($fk) {
                $table->dropForeign($fk->CONSTRAINT_NAME);
            });
        }

        foreach (['mutasis', 'speedshop_claims'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'warehouse_id')) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `warehouse_id` BIGINT UNSIGNED NULL");
            }
        }

        if (Schema::hasTable('speedshops')) {
            DB::table('speedshops')->update([
                'warehouse_id' => null,
                'mutasi_warehouse_id' => null,
            ]);
        }

        if (Schema::hasTable('akun_operasionals') && Schema::hasColumn('akun_operasionals', 'warehouse_id')) {
            DB::statement('
                DELETE a FROM akun_operasionals a
                INNER JOIN akun_operasionals b ON a.no_akun = b.no_akun AND a.id <> b.id
                WHERE a.warehouse_id IS NOT NULL AND b.warehouse_id IS NULL
            ');
            DB::table('akun_operasionals')->whereNotNull('warehouse_id')->update(['warehouse_id' => null]);
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'warehouse_id')) {
            DB::table('users')->whereNotNull('warehouse_id')->update(['warehouse_id' => null]);
        }

        Schema::disableForeignKeyConstraints();
        foreach ([
            'warehouse_supplier_tagihan_items',
            'warehouse_supplier_tagihan_pembayarans',
            'warehouse_supplier_tagihans',
            'warehouse_transaksi_items',
            'warehouse_transaksis',
            'warehouses',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
    }
};

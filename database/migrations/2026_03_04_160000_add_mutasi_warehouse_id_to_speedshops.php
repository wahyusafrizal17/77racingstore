<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('speedshops', function (Blueprint $table) {
            $table->unsignedBigInteger('mutasi_warehouse_id')->nullable()->after('warehouse_id');
        });

        foreach (DB::table('speedshops')->get() as $speedshop) {
            $wh = DB::table('warehouses')->where('nama', $speedshop->nama)->first();
            if ($wh) {
                DB::table('speedshops')->where('id', $speedshop->id)->update(['mutasi_warehouse_id' => $wh->id]);
            }
        }

        Schema::table('speedshops', function (Blueprint $table) {
            $table->foreign('mutasi_warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('speedshops', function (Blueprint $table) {
            $table->dropForeign(['mutasi_warehouse_id']);
            $table->dropColumn('mutasi_warehouse_id');
        });
    }
};

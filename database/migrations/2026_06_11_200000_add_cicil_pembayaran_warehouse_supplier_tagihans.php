<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('warehouse_supplier_tagihans')
            && ! Schema::hasColumn('warehouse_supplier_tagihans', 'total_dibayar')) {
            Schema::table('warehouse_supplier_tagihans', function (Blueprint $table) {
                $table->unsignedBigInteger('total_dibayar')->default(0)->after('total_nominal');
            });

            DB::table('warehouse_supplier_tagihans')
                ->where('status', 'lunas')
                ->update(['total_dibayar' => DB::raw('total_nominal')]);
        }

        if (! Schema::hasTable('warehouse_supplier_tagihan_pembayarans')) {
            Schema::create('warehouse_supplier_tagihan_pembayarans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tagihan_id');
                $table->unsignedBigInteger('nominal');
                $table->unsignedBigInteger('speedshop_keuangan_rekening_id');
                $table->string('bukti_bayar_path', 500)->nullable();
                $table->string('catatan_pembayaran', 1000)->nullable();
                $table->timestamp('dibayar_at');
                $table->unsignedBigInteger('dibayar_by')->nullable();
                $table->timestamps();

                $table->foreign('tagihan_id', 'wstp_tagihan_fk')
                    ->references('id')->on('warehouse_supplier_tagihans')->cascadeOnDelete();
                $table->foreign('speedshop_keuangan_rekening_id', 'wstp_rekening_fk')
                    ->references('id')->on('speedshop_keuangan_rekenings')->restrictOnDelete();
                $table->foreign('dibayar_by', 'wstp_dibayar_by_fk')
                    ->references('id')->on('users')->nullOnDelete();

                $table->index(['tagihan_id', 'dibayar_at'], 'wstp_tagihan_at_idx');
            });
        }

        if (Schema::hasTable('speedshop_finance_ledgers')
            && ! Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_supplier_tagihan_pembayaran_id')) {
            Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
                $table->unsignedBigInteger('warehouse_supplier_tagihan_pembayaran_id')->nullable()->after('warehouse_supplier_tagihan_id');
                $table->foreign('warehouse_supplier_tagihan_pembayaran_id', 'sfl_wstp_pembayaran_fk')
                    ->references('id')->on('warehouse_supplier_tagihan_pembayarans')
                    ->nullOnDelete();
                $table->unique('warehouse_supplier_tagihan_pembayaran_id', 'sfl_wstp_pembayaran_uniq');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('speedshop_finance_ledgers')
            && Schema::hasColumn('speedshop_finance_ledgers', 'warehouse_supplier_tagihan_pembayaran_id')) {
            Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
                $table->dropUnique('sfl_wstp_pembayaran_uniq');
                $table->dropForeign('sfl_wstp_pembayaran_fk');
                $table->dropColumn('warehouse_supplier_tagihan_pembayaran_id');
            });
        }

        Schema::dropIfExists('warehouse_supplier_tagihan_pembayarans');

        if (Schema::hasTable('warehouse_supplier_tagihans')
            && Schema::hasColumn('warehouse_supplier_tagihans', 'total_dibayar')) {
            Schema::table('warehouse_supplier_tagihans', function (Blueprint $table) {
                $table->dropColumn('total_dibayar');
            });
        }
    }
};

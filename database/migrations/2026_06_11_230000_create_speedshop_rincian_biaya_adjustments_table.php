<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('speedshop_rincian_biaya_adjustments')) {
            return;
        }

        Schema::create('speedshop_rincian_biaya_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('speedshop_id');
            $table->unsignedBigInteger('rincian_biaya_entry_id');
            $table->date('tanggal_pengembalian');
            $table->unsignedBigInteger('nominal_pengurangan');
            $table->unsignedBigInteger('biaya_admin')->default(0);
            $table->text('catatan')->nullable();
            $table->string('bukti_path')->nullable();
            $table->unsignedBigInteger('finance_ledger_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['speedshop_id', 'tanggal_pengembalian'], 'ss_rb_adj_shop_tgl_idx');
            $table->index('rincian_biaya_entry_id', 'ss_rb_adj_entry_idx');
            $table->foreign('rincian_biaya_entry_id', 'ss_rb_adj_entry_fk')
                ->references('id')
                ->on('speedshop_rincian_biaya_entries')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speedshop_rincian_biaya_adjustments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('speedshop_owner_modal_setorans')) {
            return;
        }

        Schema::create('speedshop_owner_modal_setorans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('speedshop_id')->constrained('speedshops')->cascadeOnDelete();
            $table->unsignedBigInteger('sumber_owner_rekening_id');
            $table->unsignedBigInteger('tujuan_speedshop_rekening_id');
            $table->foreign('sumber_owner_rekening_id', 'owner_modal_setoran_sumber_fk')
                ->references('id')->on('speedshop_keuangan_rekenings')->cascadeOnDelete();
            $table->foreign('tujuan_speedshop_rekening_id', 'owner_modal_setoran_tujuan_fk')
                ->references('id')->on('speedshop_keuangan_rekenings')->cascadeOnDelete();
            $table->date('tanggal');
            $table->unsignedBigInteger('nominal');
            $table->text('catatan')->nullable();
            $table->string('bukti_path')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['owner_user_id', 'tanggal']);
            $table->index(['speedshop_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speedshop_owner_modal_setorans');
    }
};

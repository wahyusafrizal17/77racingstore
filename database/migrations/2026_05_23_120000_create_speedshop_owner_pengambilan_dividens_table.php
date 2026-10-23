<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('speedshop_owner_pengambilan_dividens')) {
            return;
        }

        Schema::create('speedshop_owner_pengambilan_dividens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('speedshop_id')->constrained('speedshops')->cascadeOnDelete();
            $table->unsignedBigInteger('speedshop_rekening_id');
            $table->unsignedBigInteger('owner_rekening_id');
            $table->foreign('speedshop_rekening_id', 'owner_div_sp_rek_fk')
                ->references('id')->on('speedshop_keuangan_rekenings')->cascadeOnDelete();
            $table->foreign('owner_rekening_id', 'owner_div_own_rek_fk')
                ->references('id')->on('speedshop_keuangan_rekenings')->cascadeOnDelete();
            $table->date('tanggal');
            $table->unsignedBigInteger('nominal');
            $table->text('catatan')->nullable();
            $table->string('bukti_pengajuan_path')->nullable();
            $table->string('bukti_transfer_path')->nullable();
            $table->string('status', 24)->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();

            $table->index(['speedshop_id', 'status']);
            $table->index(['owner_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speedshop_owner_pengambilan_dividens');
    }
};

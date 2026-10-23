<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('speedshop_finance_permintaan_kas_kecil')) {
            return;
        }

        Schema::create('speedshop_finance_permintaan_kas_kecil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('speedshop_id')->constrained('speedshops')->cascadeOnDelete();
            $table->date('tanggal');
            $table->unsignedBigInteger('nominal');
            $table->string('status', 24)->default('pending'); // pending|completed|rejected
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->foreignId('ledger_id')->nullable()->constrained('speedshop_finance_ledgers')->nullOnDelete();
            $table->timestamps();

            $table->index(['speedshop_id', 'status'], 'sfpkk_ssid_status_idx');
            $table->index(['speedshop_id', 'tanggal'], 'sfpkk_ssid_tanggal_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speedshop_finance_permintaan_kas_kecil');
    }
};

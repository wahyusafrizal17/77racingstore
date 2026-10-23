<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speedshop_finance_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('speedshop_id')->constrained('speedshops')->cascadeOnDelete();
            $table->unsignedBigInteger('modal_laci_per_hari')->default(100_000);
            $table->timestamps();
            $table->unique('speedshop_id');
        });

        Schema::create('speedshop_finance_setorans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('speedshop_id')->constrained('speedshops')->cascadeOnDelete();
            $table->date('tanggal_setor');
            $table->unsignedBigInteger('pendapatan_tunai_hari')->default(0);
            $table->unsignedBigInteger('pendapatan_digital_hari')->default(0);
            $table->unsignedBigInteger('modal_laci_snapshot')->default(0);
            $table->unsignedBigInteger('wajib_setor_tunai')->default(0);
            $table->unsignedBigInteger('wajib_setor_digital')->default(0);
            $table->unsignedBigInteger('setor_tunai_actual')->default(0);
            $table->unsignedBigInteger('setor_digital_actual')->default(0);
            $table->string('status', 24)->default('completed');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['speedshop_id', 'tanggal_setor']);
        });

        Schema::create('speedshop_finance_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('speedshop_id')->constrained('speedshops')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('pocket', 32);
            $table->bigInteger('amount');
            $table->string('reason', 64);
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('setoran_id')->nullable()->constrained('speedshop_finance_setorans')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speedshop_finance_ledgers');
        Schema::dropIfExists('speedshop_finance_setorans');
        Schema::dropIfExists('speedshop_finance_settings');
    }
};

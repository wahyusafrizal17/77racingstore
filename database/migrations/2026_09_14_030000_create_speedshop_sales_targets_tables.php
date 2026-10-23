<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speedshop_branch_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('speedshop_id')->constrained('speedshops')->cascadeOnDelete();
            $table->char('periode_bulan', 7);
            $table->unsignedBigInteger('target_bulanan')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['speedshop_id', 'periode_bulan']);
        });

        Schema::create('speedshop_mekanik_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mekanik_id')->constrained('mekaniks')->cascadeOnDelete();
            $table->char('periode_bulan', 7);
            $table->unsignedBigInteger('target_bulanan')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['mekanik_id', 'periode_bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speedshop_mekanik_targets');
        Schema::dropIfExists('speedshop_branch_targets');
    }
};

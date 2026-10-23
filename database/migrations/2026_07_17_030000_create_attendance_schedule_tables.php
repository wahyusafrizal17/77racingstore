<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->time('check_in_time')->default('08:00:00');
            $table->time('check_out_time')->default('17:00:00');
            /** ISO weekday numbers: 1=Senin … 7=Minggu */
            $table->json('work_days')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('holiday_date')->unique();
            $table->string('name')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_holidays');
        Schema::dropIfExists('attendance_settings');
    }
};

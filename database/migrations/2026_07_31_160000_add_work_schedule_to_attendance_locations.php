<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_locations', function (Blueprint $table) {
            $table->time('check_in_time')->nullable()->after('is_active');
            $table->time('check_out_time')->nullable()->after('check_in_time');
            $table->time('break_start_time')->nullable()->after('check_out_time');
            $table->time('break_end_time')->nullable()->after('break_start_time');
        });

        if (! Schema::hasTable('attendance_locations')) {
            return;
        }

        $global = Schema::hasTable('attendance_settings')
            ? DB::table('attendance_settings')->orderBy('id')->first()
            : null;

        $checkIn = $global->check_in_time ?? '08:00:00';
        $checkOut = $global->check_out_time ?? '17:00:00';

        DB::table('attendance_locations')
            ->whereNull('check_in_time')
            ->update([
                'check_in_time' => $checkIn,
                'check_out_time' => $checkOut,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('attendance_locations', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_time',
                'check_out_time',
                'break_start_time',
                'break_end_time',
            ]);
        });
    }
};

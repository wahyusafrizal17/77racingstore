<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_location_weekday_hours', function (Blueprint $table) {
            $table->time('check_in_window_start')->nullable()->after('check_out_time');
            $table->time('check_in_window_end')->nullable()->after('check_in_window_start');
            $table->time('check_out_window_start')->nullable()->after('check_in_window_end');
            $table->time('check_out_window_end')->nullable()->after('check_out_window_start');
        });

        $rows = DB::table('attendance_location_weekday_hours')->get(['id', 'check_in_time', 'check_out_time']);
        foreach ($rows as $row) {
            $checkIn = $row->check_in_time ?: '08:00:00';
            $checkOut = $row->check_out_time ?: '17:00:00';
            DB::table('attendance_location_weekday_hours')->where('id', $row->id)->update([
                'check_in_window_start' => '07:00:00',
                'check_in_window_end' => $checkIn,
                'check_out_window_start' => $checkOut,
                'check_out_window_end' => '23:59:00',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('attendance_location_weekday_hours', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_window_start',
                'check_in_window_end',
                'check_out_window_start',
                'check_out_window_end',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_location_weekday_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_location_id')->constrained('attendance_locations')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1=Senin … 7=Minggu (ISO)
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->timestamps();

            $table->unique(['attendance_location_id', 'day_of_week'], 'att_loc_weekday_hours_unique');
        });

        $now = now();
        $locations = DB::table('attendance_locations')->select(['id', 'check_in_time', 'check_out_time'])->get();
        $rows = [];
        foreach ($locations as $location) {
            $checkIn = $location->check_in_time ?: '08:00:00';
            $checkOut = $location->check_out_time ?: '17:00:00';
            for ($day = 1; $day <= 7; $day++) {
                $rows[] = [
                    'attendance_location_id' => $location->id,
                    'day_of_week' => $day,
                    'check_in_time' => $checkIn,
                    'check_out_time' => $checkOut,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('attendance_location_weekday_hours')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_location_weekday_hours');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_holidays', function (Blueprint $table) {
            $table->dropUnique(['holiday_date']);
        });

        Schema::table('attendance_holidays', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->cascadeOnDelete();
        });

        // Libur lama bersifat global — hapus agar diganti per karyawan.
        DB::table('attendance_holidays')->whereNull('user_id')->delete();

        Schema::table('attendance_holidays', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->unique(['user_id', 'holiday_date']);
        });
    }

    public function down(): void
    {
        Schema::table('attendance_holidays', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'holiday_date']);
            $table->dropConstrainedForeignId('user_id');
            $table->unique('holiday_date');
        });
    }
};

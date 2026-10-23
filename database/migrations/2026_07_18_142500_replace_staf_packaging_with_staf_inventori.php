<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('section', 'modifikasi_ori')
            ->where('role', 'Staf Packaging')
            ->update([
                'role' => 'Staf Inventori',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('section', 'modifikasi_ori')
            ->where('role', 'Staf Inventori')
            ->update([
                'role' => 'Staf Packaging',
                'updated_at' => now(),
            ]);
    }
};

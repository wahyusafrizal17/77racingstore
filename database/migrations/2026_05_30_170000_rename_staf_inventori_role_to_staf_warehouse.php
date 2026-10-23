<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('section', 'warehouse')
            ->where('role', 'Staf Inventori')
            ->update(['role' => 'Staf Warehouse']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('section', 'warehouse')
            ->where('role', 'Staf Warehouse')
            ->update(['role' => 'Staf Inventori']);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE mutasis MODIFY COLUMN status ENUM('dikirim', 'diterima', 'ditolak') DEFAULT 'dikirim'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE mutasis MODIFY COLUMN status ENUM('dikirim', 'diterima') DEFAULT 'dikirim'");
    }
};

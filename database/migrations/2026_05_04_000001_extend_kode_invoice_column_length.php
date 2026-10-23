<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE invoices MODIFY kode_invoice VARCHAR(64) NOT NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE invoices MODIFY kode_invoice VARCHAR(30) NOT NULL');
        }
    }
};

<?php

use App\Support\ProductionAnggotaAttribution;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ponytail: kolom anggota_id baru ditambah migrasi 2026_06_13; DB kosong tidak perlu backfill.
        if (! Schema::hasColumn('production_outputs', 'anggota_id')) {
            return;
        }

        ProductionAnggotaAttribution::backfillMissingOutputAnggotaIds();
    }

    public function down(): void
    {
        // Tidak memulihkan data historis yang sudah di-backfill.
    }
};

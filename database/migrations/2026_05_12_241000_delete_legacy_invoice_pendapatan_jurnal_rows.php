<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hapus baris jurnal otomatis dari invoice (sebelum sinkron invoice ke jurnal dinonaktifkan).
 * Pendapatan tetap tercatat di ledger; baris ini hanya duplikat historis di tabel jurnal.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['speedshop_jurnal_saqu_entries', 'speedshop_jurnal_blu_entries'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            DB::table($table)
                ->whereRaw('UPPER(TRIM(akun)) = ?', ['PENDAPATAN TRANSAKSI'])
                ->where('keterangan', 'like', 'Transaksi Work Order No Invoice%')
                ->delete();
        }
    }

    public function down(): void
    {
        // Irreversible: data historis yang memang sengaja dibuang.
    }
};

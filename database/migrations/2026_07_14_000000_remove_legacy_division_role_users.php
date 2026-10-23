<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Migration ini sengaja NO-OP di production.
 *
 * Pembersihan akun legacy (bukan penghapusan transaksi/stok) hanya boleh
 * dilakukan manual atau via seeder di environment local/staging — lihat README.
 *
 * JANGAN hapus user massal di server production: beberapa tabel (mis. transaksi_offline,
 * warehouse_transaksis) memakai FK cascadeOnDelete ke users.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Tidak menghapus data apapun.
    }

    public function down(): void
    {
        //
    }
};

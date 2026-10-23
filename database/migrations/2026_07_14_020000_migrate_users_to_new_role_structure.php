<?php

use App\Support\UserRoleMigration;
use Illuminate\Database\Migrations\Migration;

/**
 * Migration data user & mekanik — aman untuk production.
 *
 * Hanya UPDATE/INSERT pada users & mekaniks:
 * - produksi/warehouse → modifikasi_ori + role baru
 * - speedshop Outlet → Staf, Manager regional → Head Area
 * - admin SuperAdmin → Head HR
 * - link mekanik ↔ user login
 *
 * TIDAK menghapus user, transaksi, stok, work order, atau invoice.
 * Idempotent: aman dijalankan ulang jika ada user yang masih legacy.
 */
return new class extends Migration
{
    public function up(): void
    {
        UserRoleMigration::run();
    }

    public function down(): void
    {
        // Tidak di-rollback otomatis — pemetaan role tidak bisa dipulihkan
        // tanpa risiko salah assign. Restore dari backup jika perlu.
    }
};

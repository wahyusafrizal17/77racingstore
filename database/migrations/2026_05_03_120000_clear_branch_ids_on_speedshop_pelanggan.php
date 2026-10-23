<?php

use App\Models\Pelanggan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pelanggan modul speedshop dipakai bersama semua cabang; cabang transaksi mengikuti outlet aktif user / WO, bukan kolom pelanggan.
     */
    public function up(): void
    {
        DB::table('pelanggan')
            ->where('section', Pelanggan::SECTION_SPEEDSHOP)
            ->update([
                'speedshop_id' => null,
                'warehouse_id' => null,
            ]);
    }

    public function down(): void
    {
        // Data cabang tidak dipulihkan otomatis.
    }
};

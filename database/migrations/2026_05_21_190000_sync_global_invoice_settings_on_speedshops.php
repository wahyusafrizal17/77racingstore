<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('speedshops')) {
            return;
        }

        $source = DB::table('speedshops')->orderBy('id')->first();
        if (! $source) {
            return;
        }

        DB::table('speedshops')->update([
            'invoice_charge_mode' => $source->invoice_charge_mode ?? 'percent',
            'invoice_charge_percent' => $source->invoice_charge_percent ?? 0,
            'invoice_charge_rupiah' => $source->invoice_charge_rupiah ?? 0,
            'invoice_ppn_mode' => $source->invoice_ppn_mode ?? 'percent',
            'invoice_ppn_percent' => $source->invoice_ppn_percent ?? 0,
            'invoice_ppn_rupiah' => $source->invoice_ppn_rupiah ?? 0,
        ]);
    }

    public function down(): void
    {
        // Tidak mengembalikan nilai per-outlet yang berbeda.
    }
};

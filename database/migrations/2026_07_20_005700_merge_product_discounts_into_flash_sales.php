<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('marketplace_promotions')
            ->where('type', 'discount')
            ->update(['type' => 'flash_sale']);
    }

    public function down(): void
    {
        // Tidak dapat dibalik karena promo flash sale lama dan hasil konversi tidak bisa dibedakan.
    }
};

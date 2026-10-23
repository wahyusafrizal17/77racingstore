<?php

use App\Services\SpeedshopFinanceService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $default = SpeedshopFinanceService::DEFAULT_MODAL_LACI_PER_HARI;

        if (Schema::hasTable('speedshop_finance_settings')) {
            Schema::table('speedshop_finance_settings', function (Blueprint $table) use ($default) {
                $table->unsignedBigInteger('modal_laci_per_hari')->default($default)->change();
            });

            // Naikkan modal laci lama (default 100rb) ke target operasional harian 2jt.
            DB::table('speedshop_finance_settings')
                ->where('modal_laci_per_hari', 100_000)
                ->update(['modal_laci_per_hari' => $default]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('speedshop_finance_settings')) {
            return;
        }

        Schema::table('speedshop_finance_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('modal_laci_per_hari')->default(100_000)->change();
        });

        DB::table('speedshop_finance_settings')
            ->where('modal_laci_per_hari', SpeedshopFinanceService::DEFAULT_MODAL_LACI_PER_HARI)
            ->update(['modal_laci_per_hari' => 100_000]);
    }
};

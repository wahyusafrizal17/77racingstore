<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('speedshop_master_product_archives')) {
            return;
        }

        Schema::table('speedshop_master_product_archives', function (Blueprint $table) {
            if (! Schema::hasColumn('speedshop_master_product_archives', 'purged_at')) {
                $table->timestamp('purged_at')->nullable()->after('archived_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('speedshop_master_product_archives')) {
            return;
        }

        Schema::table('speedshop_master_product_archives', function (Blueprint $table) {
            if (Schema::hasColumn('speedshop_master_product_archives', 'purged_at')) {
                $table->dropColumn('purged_at');
            }
        });
    }
};

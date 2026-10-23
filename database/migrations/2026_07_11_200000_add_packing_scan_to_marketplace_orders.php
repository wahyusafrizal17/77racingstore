<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('marketplace_order_items', 'qty_scanned')) {
                $table->unsignedInteger('qty_scanned')->default(0)->after('qty');
            }
            if (! Schema::hasColumn('marketplace_order_items', 'scanned_at')) {
                $table->timestamp('scanned_at')->nullable()->after('qty_scanned');
            }
            if (! Schema::hasColumn('marketplace_order_items', 'scanned_by')) {
                $table->foreignId('scanned_by')->nullable()->after('scanned_at')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('marketplace_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('marketplace_orders', 'packing_scanned_at')) {
                $table->timestamp('packing_scanned_at')->nullable()->after('packed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('marketplace_order_items', 'scanned_by')) {
                $table->dropConstrainedForeignId('scanned_by');
            }
            if (Schema::hasColumn('marketplace_order_items', 'scanned_at')) {
                $table->dropColumn('scanned_at');
            }
            if (Schema::hasColumn('marketplace_order_items', 'qty_scanned')) {
                $table->dropColumn('qty_scanned');
            }
        });

        Schema::table('marketplace_orders', function (Blueprint $table) {
            if (Schema::hasColumn('marketplace_orders', 'packing_scanned_at')) {
                $table->dropColumn('packing_scanned_at');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketplace_vouchers')) {
            Schema::create('marketplace_vouchers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->string('kode', 64);
                $table->string('nama');
                $table->text('deskripsi')->nullable();
                $table->string('tipe', 16)->default('nominal');
                $table->unsignedBigInteger('nilai')->default(0);
                $table->unsignedBigInteger('max_diskon')->nullable();
                $table->unsignedBigInteger('min_pembelian')->default(0);
                $table->unsignedInteger('kuota_global')->nullable();
                $table->unsignedInteger('kuota_per_user')->nullable();
                $table->unsignedInteger('terpakai_global')->default(0);
                $table->timestamp('mulai_at')->nullable();
                $table->timestamp('berakhir_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['warehouse_id', 'kode'], 'marketplace_vouchers_wh_kode_uq');
                $table->index(['warehouse_id', 'is_active'], 'marketplace_vouchers_wh_active_idx');
            });
        }

        if (! Schema::hasTable('marketplace_voucher_redemptions')) {
            Schema::create('marketplace_voucher_redemptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marketplace_voucher_id')->constrained('marketplace_vouchers')->cascadeOnDelete();
                $table->foreignId('marketplace_order_id')->constrained('marketplace_orders')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('subtotal_bruto')->default(0);
                $table->unsignedBigInteger('diskon_nominal')->default(0);
                $table->timestamps();

                $table->unique('marketplace_order_id', 'marketplace_voucher_redeem_order_uq');
                $table->index(['marketplace_voucher_id', 'user_id'], 'marketplace_voucher_redeem_usage_idx');
            });
        }

        Schema::table('marketplace_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('marketplace_orders', 'marketplace_voucher_id')) {
                $table->foreignId('marketplace_voucher_id')->nullable()->after('catatan')->constrained('marketplace_vouchers')->nullOnDelete();
            }
            if (! Schema::hasColumn('marketplace_orders', 'kode_voucher')) {
                $table->string('kode_voucher', 64)->nullable()->after('marketplace_voucher_id');
            }
            if (! Schema::hasColumn('marketplace_orders', 'diskon_voucher')) {
                $table->unsignedBigInteger('diskon_voucher')->default(0)->after('subtotal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            if (Schema::hasColumn('marketplace_orders', 'marketplace_voucher_id')) {
                $table->dropConstrainedForeignId('marketplace_voucher_id');
            }
            foreach (['kode_voucher', 'diskon_voucher'] as $col) {
                if (Schema::hasColumn('marketplace_orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('marketplace_voucher_redemptions');
        Schema::dropIfExists('marketplace_vouchers');
    }
};

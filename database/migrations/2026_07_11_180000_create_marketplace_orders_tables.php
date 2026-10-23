<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketplace_orders')) {
            Schema::create('marketplace_orders', function (Blueprint $table) {
                $table->id();
                $table->string('kode_pesanan')->unique();
                $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                $table->string('status', 32)->default('pending_payment');
                $table->string('customer_nama');
                $table->string('customer_phone', 32)->nullable();
                $table->string('customer_email')->nullable();
                $table->text('alamat_lengkap');
                $table->string('provinsi_id', 16)->nullable();
                $table->string('provinsi_nama')->nullable();
                $table->string('kota_id', 16)->nullable();
                $table->string('kota_nama')->nullable();
                $table->string('kecamatan')->nullable();
                $table->string('kode_pos', 16)->nullable();
                $table->string('courier', 32)->default('jne');
                $table->string('shipping_service', 64)->nullable();
                $table->string('shipping_service_desc')->nullable();
                $table->string('shipping_etd', 64)->nullable();
                $table->unsignedBigInteger('ongkir')->default(0);
                $table->unsignedBigInteger('subtotal')->default(0);
                $table->unsignedBigInteger('total_nominal')->default(0);
                $table->unsignedBigInteger('total_bayar')->default(0);
                $table->text('catatan')->nullable();
                $table->string('no_resi', 120)->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('packed_at')->nullable();
                $table->timestamp('dikirim_at')->nullable();
                $table->timestamp('selesai_at')->nullable();
                $table->timestamp('dibatalkan_at')->nullable();
                $table->timestamps();

                $table->index(['warehouse_id', 'status']);
                $table->index(['user_id', 'status']);
            });
        }

        if (! Schema::hasTable('marketplace_order_items')) {
            Schema::create('marketplace_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('marketplace_order_id')
                    ->constrained('marketplace_orders')
                    ->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
                $table->unsignedInteger('qty');
                $table->unsignedBigInteger('harga_satuan');
                $table->unsignedBigInteger('subtotal');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('marketplace_order_midtrans_payments')) {
            Schema::create('marketplace_order_midtrans_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('marketplace_order_id');
                $table->string('order_id', 64)->unique();
                $table->string('snap_token', 128)->nullable();
                $table->unsignedBigInteger('gross_amount');
                $table->string('transaction_status', 32)->default('pending');
                $table->string('fraud_status', 32)->nullable();
                $table->string('payment_type', 64)->nullable();
                $table->string('midtrans_transaction_id', 64)->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('settled_at')->nullable();
                $table->json('raw_notification')->nullable();
                $table->timestamps();

                $table->foreign('marketplace_order_id', 'momp_order_fk')
                    ->references('id')->on('marketplace_orders')->cascadeOnDelete();
                $table->foreign('created_by', 'momp_created_by_fk')
                    ->references('id')->on('users')->nullOnDelete();

                $table->index(['marketplace_order_id', 'transaction_status'], 'momp_order_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_order_midtrans_payments');
        Schema::dropIfExists('marketplace_order_items');
        Schema::dropIfExists('marketplace_orders');
    }
};

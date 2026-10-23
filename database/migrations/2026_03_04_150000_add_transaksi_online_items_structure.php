<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_online_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_online_id')->constrained('transaksi_online')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('qty');
            $table->timestamps();
        });

        foreach (\DB::table('transaksi_online')->get() as $row) {
            \DB::table('transaksi_online_items')->insert([
                'transaksi_online_id' => $row->id,
                'product_id' => $row->product_id,
                'qty' => $row->qty,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::table('transaksi_online', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn(['product_id', 'qty']);
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_online', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('no_resi')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('qty')->nullable()->after('product_id');
        });

        $firstItem = \DB::table('transaksi_online_items')->first();
        if ($firstItem) {
            \DB::table('transaksi_online')->update([
                'product_id' => $firstItem->product_id,
                'qty' => $firstItem->qty,
            ]);
        }
        Schema::dropIfExists('transaksi_online_items');

        Schema::table('transaksi_online', function (Blueprint $table) {
            $table->dropNullable(['product_id', 'qty']);
        });
    }
};

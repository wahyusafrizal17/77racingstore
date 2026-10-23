<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transaksi_offline', function (Blueprint $table) {
            $table->foreignId('petugas_id')->nullable()->after('no_hp')->constrained('users')->nullOnDelete();
            $table->dropColumn('petugas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_offline', function (Blueprint $table) {
            $table->dropConstrainedForeignId('petugas_id');
            $table->string('petugas')->nullable()->after('no_hp');
        });
    }
};

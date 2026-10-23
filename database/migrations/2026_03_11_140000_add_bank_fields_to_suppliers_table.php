<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('email')->nullable()->after('no_hp');
            $table->string('jenis_rekening', 50)->nullable()->after('email');
            $table->string('nama_rekening')->nullable()->after('jenis_rekening');
            $table->string('nomor_rekening', 50)->nullable()->after('nama_rekening');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn(['email', 'jenis_rekening', 'nama_rekening', 'nomor_rekening']);
        });
    }
};

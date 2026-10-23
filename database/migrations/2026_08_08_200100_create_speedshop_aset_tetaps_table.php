<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speedshop_aset_tetaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('speedshop_id')->constrained('speedshops')->cascadeOnDelete();
            $table->enum('kategori', ['rak', 'inventaris']);
            $table->string('kode', 50);
            $table->string('nama');
            $table->date('tanggal_perolehan')->nullable();
            $table->decimal('nilai_perolehan', 15, 2)->default(0);
            $table->decimal('nilai_buku', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['speedshop_id', 'kode']);
            $table->index(['speedshop_id', 'kategori']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('speedshop_aset_tetaps');
    }
};

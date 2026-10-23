<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE mutasis MODIFY COLUMN status ENUM('dikirim', 'menunggu_manager', 'diterima', 'ditolak') DEFAULT 'dikirim'");

        Schema::table('mutasis', function (Blueprint $table) {
            if (! Schema::hasColumn('mutasis', 'inventory_verified_at')) {
                $table->timestamp('inventory_verified_at')->nullable()->after('verified_by');
            }
            if (! Schema::hasColumn('mutasis', 'inventory_verified_by')) {
                $table->foreignId('inventory_verified_by')->nullable()->after('inventory_verified_at')->constrained('users')->nullOnDelete();
            }
        });

        // Mutasi produksi yang masih "dikirim" sebelum fitur ini: anggap verifikasi inventori sudah lewat
        // agar Manager tetap bisa menyelesaikan tanpa mengunci data lama.
        DB::table('mutasis')
            ->where('sumber', 'produksi')
            ->where('status', 'dikirim')
            ->update([
                'status' => 'menunggu_manager',
                'inventory_verified_at' => now(),
                'inventory_verified_by' => null,
            ]);
    }

    public function down(): void
    {
        DB::table('mutasis')->where('status', 'menunggu_manager')->update(['status' => 'dikirim']);

        Schema::table('mutasis', function (Blueprint $table) {
            if (Schema::hasColumn('mutasis', 'inventory_verified_by')) {
                $table->dropConstrainedForeignId('inventory_verified_by');
            }
            if (Schema::hasColumn('mutasis', 'inventory_verified_at')) {
                $table->dropColumn('inventory_verified_at');
            }
        });

        DB::statement("ALTER TABLE mutasis MODIFY COLUMN status ENUM('dikirim', 'diterima', 'ditolak') DEFAULT 'dikirim'");
    }
};

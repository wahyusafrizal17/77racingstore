<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mekaniks', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('mekanik_level_id')->nullable()->after('user_id')->constrained('mekanik_levels')->nullOnDelete();
            $table->foreignId('speedshop_id')->nullable()->after('mekanik_level_id')->constrained('speedshops')->nullOnDelete();
        });

        Schema::table('mekaniks', function (Blueprint $table) {
            $table->unique('user_id');
        });

        $levelsByName = DB::table('mekanik_levels')->pluck('id', 'nama');

        foreach (DB::table('mekaniks')->get() as $row) {
            $levelId = $levelsByName[$row->spesialisasi] ?? null;

            DB::table('mekaniks')->where('id', $row->id)->update([
                'mekanik_level_id' => $levelId,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('mekaniks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('mekanik_level_id');
            $table->dropConstrainedForeignId('speedshop_id');
        });
    }
};

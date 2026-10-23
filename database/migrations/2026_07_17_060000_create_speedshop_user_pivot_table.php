<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speedshop_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('speedshop_id')->constrained('speedshops')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'speedshop_id']);
        });

        // Backfill: Accounting/FLP/dll yang sudah punya 1 cabang.
        if (Schema::hasColumn('users', 'speedshop_id')) {
            $rows = DB::table('users')
                ->whereNotNull('speedshop_id')
                ->where('section', 'speedshop')
                ->get(['id', 'speedshop_id']);

            $now = now();
            foreach ($rows as $row) {
                DB::table('speedshop_user')->insertOrIgnore([
                    'user_id' => $row->id,
                    'speedshop_id' => $row->speedshop_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('speedshop_user');
    }
};

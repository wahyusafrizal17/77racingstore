<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('speedshop_jurnal_blu_entries')) {
            Schema::table('speedshop_jurnal_blu_entries', function (Blueprint $table) {
                if (! Schema::hasColumn('speedshop_jurnal_blu_entries', 'speedshop_rincian_biaya_entry_id')) {
                    $table->unsignedBigInteger('speedshop_rincian_biaya_entry_id')->nullable()->after('speedshop_id');
                }
            });
            if (Schema::hasColumn('speedshop_jurnal_blu_entries', 'speedshop_rincian_biaya_entry_id')) {
                Schema::table('speedshop_jurnal_blu_entries', function (Blueprint $table) {
                    try {
                        $table->foreign('speedshop_rincian_biaya_entry_id', 'fk_jurnal_blu_rincian_biaya')
                            ->references('id')
                            ->on('speedshop_rincian_biaya_entries')
                            ->nullOnDelete();
                    } catch (\Throwable) {
                        //
                    }
                });
            }
        }

        if (Schema::hasTable('speedshop_jurnal_saqu_entries')) {
            Schema::table('speedshop_jurnal_saqu_entries', function (Blueprint $table) {
                if (! Schema::hasColumn('speedshop_jurnal_saqu_entries', 'speedshop_rincian_biaya_entry_id')) {
                    $table->unsignedBigInteger('speedshop_rincian_biaya_entry_id')->nullable()->after('speedshop_id');
                }
            });
            if (Schema::hasColumn('speedshop_jurnal_saqu_entries', 'speedshop_rincian_biaya_entry_id')) {
                Schema::table('speedshop_jurnal_saqu_entries', function (Blueprint $table) {
                    try {
                        $table->foreign('speedshop_rincian_biaya_entry_id', 'fk_jurnal_saqu_rincian_biaya')
                            ->references('id')
                            ->on('speedshop_rincian_biaya_entries')
                            ->nullOnDelete();
                    } catch (\Throwable) {
                        //
                    }
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('speedshop_jurnal_blu_entries')) {
            Schema::table('speedshop_jurnal_blu_entries', function (Blueprint $table) {
                if (Schema::hasColumn('speedshop_jurnal_blu_entries', 'speedshop_rincian_biaya_entry_id')) {
                    try {
                        $table->dropForeign('fk_jurnal_blu_rincian_biaya');
                    } catch (\Throwable) {
                        //
                    }
                    $table->dropColumn('speedshop_rincian_biaya_entry_id');
                }
            });
        }

        if (Schema::hasTable('speedshop_jurnal_saqu_entries')) {
            Schema::table('speedshop_jurnal_saqu_entries', function (Blueprint $table) {
                if (Schema::hasColumn('speedshop_jurnal_saqu_entries', 'speedshop_rincian_biaya_entry_id')) {
                    try {
                        $table->dropForeign('fk_jurnal_saqu_rincian_biaya');
                    } catch (\Throwable) {
                        //
                    }
                    $table->dropColumn('speedshop_rincian_biaya_entry_id');
                }
            });
        }
    }
};

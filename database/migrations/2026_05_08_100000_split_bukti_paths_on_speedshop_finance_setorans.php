<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('speedshop_finance_setorans')) {
            return;
        }

        if (! Schema::hasColumn('speedshop_finance_setorans', 'bukti_kas_kecil_path')) {
            Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
                $table->string('bukti_kas_kecil_path', 512)->nullable()->after('catatan');
                $table->string('bukti_kas_besar_path', 512)->nullable()->after('bukti_kas_kecil_path');
            });
        }

        if (Schema::hasColumn('speedshop_finance_setorans', 'bukti_path')) {
            foreach (DB::table('speedshop_finance_setorans')->whereNotNull('bukti_path')->orderBy('id')->cursor() as $row) {
                DB::table('speedshop_finance_setorans')
                    ->where('id', $row->id)
                    ->update([
                        'bukti_kas_kecil_path' => $row->bukti_path,
                        'bukti_kas_besar_path' => $row->bukti_path,
                    ]);
            }

            Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
                $table->dropColumn('bukti_path');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('speedshop_finance_setorans')) {
            return;
        }

        if (! Schema::hasColumn('speedshop_finance_setorans', 'bukti_path')) {
            Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
                $table->string('bukti_path', 512)->nullable()->after('catatan');
            });
        }

        if (Schema::hasColumn('speedshop_finance_setorans', 'bukti_kas_kecil_path')) {
            foreach (DB::table('speedshop_finance_setorans')->orderBy('id')->cursor() as $row) {
                $legacy = $row->bukti_kas_kecil_path ?? $row->bukti_kas_besar_path ?? null;
                if ($legacy !== null) {
                    DB::table('speedshop_finance_setorans')
                        ->where('id', $row->id)
                        ->update(['bukti_path' => $legacy]);
                }
            }
        }

        if (Schema::hasColumn('speedshop_finance_setorans', 'bukti_kas_kecil_path')) {
            Schema::table('speedshop_finance_setorans', function (Blueprint $table) {
                $table->dropColumn(['bukti_kas_kecil_path', 'bukti_kas_besar_path']);
            });
        }
    }
};

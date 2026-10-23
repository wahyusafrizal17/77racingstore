<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasIndex(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT COUNT(1) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $indexName]
        );

        return ((int) ($row->c ?? 0)) > 0;
    }

    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'speedshop_id')) {
                $table->foreignId('speedshop_id')->nullable()->after('section')->constrained('speedshops')->nullOnDelete();
                $table->index(['speedshop_id', 'section']);
            }
            if (! Schema::hasColumn('suppliers', 'supplier_scope')) {
                $table->string('supplier_scope', 32)->nullable()->after('speedshop_id');
                $table->index(['supplier_scope', 'kode_supplier'], 'suppliers_scope_kode_idx');
            }
        });

        // Backfill scope: non-speedshop -> section, speedshop legacy -> speedshop:0
        if (Schema::hasColumn('suppliers', 'supplier_scope') && Schema::hasColumn('suppliers', 'section')) {
            DB::table('suppliers')
                ->whereNull('supplier_scope')
                ->update(['supplier_scope' => DB::raw('section')]);

            DB::table('suppliers')
                ->where('section', 'speedshop')
                ->where(function ($q) {
                    $q->whereNull('speedshop_id')->orWhere('speedshop_id', 0);
                })
                ->update(['supplier_scope' => 'speedshop:0']);

            DB::table('suppliers')
                ->where('section', 'speedshop')
                ->whereNotNull('speedshop_id')
                ->update(['supplier_scope' => DB::raw("CONCAT('speedshop:', speedshop_id)")]);
        }

        // Replace global unique(kode_supplier) with unique(scope,kode_supplier)
        if (Schema::hasColumn('suppliers', 'kode_supplier')) {
            // Laravel default name: suppliers_kode_supplier_unique
            if ($this->hasIndex('suppliers', 'suppliers_kode_supplier_unique')) {
                Schema::table('suppliers', function (Blueprint $table) {
                    $table->dropUnique('suppliers_kode_supplier_unique');
                });
            }
            // In case the unique index name differs (legacy), try common pattern.
            if ($this->hasIndex('suppliers', 'suppliers_kode_supplier_index')) {
                // noop: it's a normal index, keep it
            }
        }

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'supplier_scope') && Schema::hasColumn('suppliers', 'kode_supplier')) {
                $table->unique(['supplier_scope', 'kode_supplier'], 'suppliers_scope_kode_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            try {
                $table->dropUnique('suppliers_scope_kode_unique');
            } catch (\Throwable $e) {
                // ignore
            }
            try {
                $table->dropIndex('suppliers_scope_kode_idx');
            } catch (\Throwable $e) {
                // ignore
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'supplier_scope')) {
                $table->dropColumn('supplier_scope');
            }
            if (Schema::hasColumn('suppliers', 'speedshop_id')) {
                try {
                    $table->dropIndex(['speedshop_id', 'section']);
                } catch (\Throwable $e) {
                    // ignore
                }
                $table->dropForeign(['speedshop_id']);
                $table->dropColumn('speedshop_id');
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'kode_supplier')) {
                try {
                    $table->unique('kode_supplier');
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        });
    }
};


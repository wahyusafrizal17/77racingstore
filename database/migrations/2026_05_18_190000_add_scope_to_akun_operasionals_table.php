<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('akun_operasionals')) {
            return;
        }

        if (! Schema::hasColumn('akun_operasionals', 'speedshop_id')) {
            Schema::table('akun_operasionals', function (Blueprint $table) {
                $table->foreignId('speedshop_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('speedshops')
                    ->cascadeOnDelete();
            });
        }

        if (! Schema::hasColumn('akun_operasionals', 'warehouse_id')) {
            Schema::table('akun_operasionals', function (Blueprint $table) {
                $table->foreignId('warehouse_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('warehouses')
                    ->cascadeOnDelete();
            });
        }

        if ($this->indexExists('akun_operasionals', 'akun_operasionals_no_akun_unique')) {
            Schema::table('akun_operasionals', function (Blueprint $table) {
                $table->dropUnique('akun_operasionals_no_akun_unique');
            });
        }

        $this->addUniqueIfMissing(
            'akun_operasionals',
            'akun_operasionals_speedshop_no_akun_unique',
            ['speedshop_id', 'no_akun'],
        );
        $this->addUniqueIfMissing(
            'akun_operasionals',
            'akun_operasionals_warehouse_no_akun_unique',
            ['warehouse_id', 'no_akun'],
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('akun_operasionals')) {
            return;
        }

        $this->dropUniqueIfExists('akun_operasionals', 'akun_operasionals_warehouse_no_akun_unique');
        $this->dropUniqueIfExists('akun_operasionals', 'akun_operasionals_speedshop_no_akun_unique');

        if (! $this->indexExists('akun_operasionals', 'akun_operasionals_no_akun_unique')) {
            Schema::table('akun_operasionals', function (Blueprint $table) {
                $table->unique('no_akun');
            });
        }

        if (Schema::hasColumn('akun_operasionals', 'warehouse_id')) {
            Schema::table('akun_operasionals', function (Blueprint $table) {
                $table->dropConstrainedForeignId('warehouse_id');
            });
        }

        if (Schema::hasColumn('akun_operasionals', 'speedshop_id')) {
            Schema::table('akun_operasionals', function (Blueprint $table) {
                $table->dropConstrainedForeignId('speedshop_id');
            });
        }
    }

    /** @param  list<string>  $columns */
    private function addUniqueIfMissing(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->unique($columns, $indexName);
        });
    }

    private function dropUniqueIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropUnique($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$indexName]) !== [];
    }
};

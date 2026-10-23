<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'speedshop_jurnal_blu_entries',
        'speedshop_jurnal_saqu_entries',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $this->makeSpeedshopIdNullable($table);

            if (! Schema::hasColumn($table, 'warehouse_id')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $blueprint->unsignedBigInteger('warehouse_id')->nullable()->after('speedshop_id');
                });
                $this->addForeignIfMissing($table, 'fk_'.$this->shortTableKey($table).'_warehouse', 'warehouse_id', 'warehouses', 'id', 'cascade');
            }

            if (! Schema::hasColumn($table, 'speedshop_finance_ledger_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->unsignedBigInteger('speedshop_finance_ledger_id')->nullable()->after('warehouse_id');
                });
            }

            $ledgerFk = 'fk_'.$this->shortTableKey($table).'_ledger';
            $this->addForeignIfMissing($table, $ledgerFk, 'speedshop_finance_ledger_id', 'speedshop_finance_ledgers', 'id', 'cascade');

            if ($table === 'speedshop_jurnal_saqu_entries'
                && ! Schema::hasColumn($table, 'speedshop_keuangan_rekening_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->unsignedBigInteger('speedshop_keuangan_rekening_id')->nullable()->after('speedshop_finance_ledger_id');
                });
                $this->addForeignIfMissing(
                    $table,
                    'fk_jurnal_saqu_rekening',
                    'speedshop_keuangan_rekening_id',
                    'speedshop_keuangan_rekenings',
                    'id',
                    'set null',
                );
            }

            $this->addUniqueIfMissing($table, $table.'_ledger_id_unique', ['speedshop_finance_ledger_id']);
            $this->addIndexIfMissing($table, $table.'_warehouse_tanggal_index', ['warehouse_id', 'tanggal']);
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if ($table === 'speedshop_jurnal_saqu_entries' && Schema::hasColumn($table, 'speedshop_keuangan_rekening_id')) {
                $this->dropForeignIfExists($table, 'fk_jurnal_saqu_rekening');
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('speedshop_keuangan_rekening_id');
                });
            }

            if (Schema::hasColumn($table, 'speedshop_finance_ledger_id')) {
                $this->dropUniqueIfExists($table, $table.'_ledger_id_unique');
                $this->dropForeignIfExists($table, 'fk_'.$this->shortTableKey($table).'_ledger');
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('speedshop_finance_ledger_id');
                });
            }

            if (Schema::hasColumn($table, 'warehouse_id')) {
                $this->dropForeignIfExists($table, 'fk_'.$this->shortTableKey($table).'_warehouse');
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('warehouse_id');
                });
            }
        }
    }

    private function shortTableKey(string $table): string
    {
        return $table === 'speedshop_jurnal_blu_entries' ? 'jurnal_blu' : 'jurnal_saqu';
    }

    private function makeSpeedshopIdNullable(string $table): void
    {
        if (! Schema::hasColumn($table, 'speedshop_id')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('speedshop_id')->nullable()->change();
            });

            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['speedshop_id']);
            });
        } catch (\Throwable) {
            //
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `speedshop_id` BIGINT UNSIGNED NULL");

        $this->addForeignIfMissing($table, $table.'_speedshop_id_foreign', 'speedshop_id', 'speedshops', 'id', 'set null');
    }

    private function addForeignIfMissing(
        string $table,
        string $constraintName,
        string $column,
        string $refTable,
        string $refColumn,
        string $onDelete,
    ): void {
        if ($this->foreignExists($table, $constraintName)) {
            return;
        }

        $onDeleteSql = match ($onDelete) {
            'cascade' => 'ON DELETE CASCADE',
            'set null' => 'ON DELETE SET NULL',
            default => '',
        };

        DB::statement(
            "ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraintName}` FOREIGN KEY (`{$column}`) REFERENCES `{$refTable}` (`{$refColumn}`) {$onDeleteSql}",
        );
    }

    private function dropForeignIfExists(string $table, string $constraintName): void
    {
        if (! $this->foreignExists($table, $constraintName)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraintName}`");
    }

    private function foreignExists(string $table, string $constraintName): bool
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return false;
        }

        $db = Schema::getConnection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT 1 FROM information_schema.table_constraints WHERE table_schema = ? AND table_name = ? AND constraint_name = ? LIMIT 1',
            [$db, $table, $constraintName],
        );

        return $row !== null;
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

    /** @param  list<string>  $columns */
    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
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
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $rows = Schema::getConnection()->select("PRAGMA index_list('{$table}')");

            return collect($rows)->contains(fn ($row) => ($row->name ?? '') === $indexName);
        }

        if ($driver !== 'mysql') {
            return false;
        }

        $indexes = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$indexName]);

        return $indexes !== [];
    }
};

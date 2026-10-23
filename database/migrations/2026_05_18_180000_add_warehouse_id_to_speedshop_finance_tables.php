<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'speedshop_finance_settings',
        'speedshop_finance_setorans',
        'speedshop_finance_ledgers',
        'speedshop_keuangan_rekenings',
        'speedshop_finance_permintaan_kas_kecil',
        'speedshop_rincian_biaya_entries',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $this->makeSpeedshopIdNullable($table);

            if (! Schema::hasColumn($table, 'warehouse_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->foreignId('warehouse_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('warehouses')
                        ->cascadeOnDelete();
                });
            }
        }

        if (Schema::hasTable('speedshop_finance_settings')
            && Schema::hasColumn('speedshop_finance_settings', 'warehouse_id')) {
            $this->addUniqueIfMissing('speedshop_finance_settings', 'speedshop_finance_settings_warehouse_id_unique', ['warehouse_id']);
        }

        if (Schema::hasTable('speedshop_finance_setorans')
            && Schema::hasColumn('speedshop_finance_setorans', 'warehouse_id')) {
            $this->addUniqueIfMissing(
                'speedshop_finance_setorans',
                'speedshop_finance_setorans_warehouse_id_tanggal_setor_unique',
                ['warehouse_id', 'tanggal_setor'],
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('speedshop_finance_setorans')) {
            $this->dropUniqueIfExists('speedshop_finance_setorans', 'speedshop_finance_setorans_warehouse_id_tanggal_setor_unique');
        }

        if (Schema::hasTable('speedshop_finance_settings')) {
            $this->dropUniqueIfExists('speedshop_finance_settings', 'speedshop_finance_settings_warehouse_id_unique');
        }

        foreach (array_reverse($this->tables) as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (Schema::hasColumn($table, 'warehouse_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropConstrainedForeignId('warehouse_id');
                });
            }
        }
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
            // FK name may differ; column change below still applies when no FK.
        }

        DB::statement("ALTER TABLE `{$table}` MODIFY `speedshop_id` BIGINT UNSIGNED NULL");

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->foreign('speedshop_id')->references('id')->on('speedshops')->cascadeOnDelete();
        });
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
        $indexes = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$indexName]);

        return $indexes !== [];
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('speedshop_rincian_biaya_entries')) {
            return;
        }

        Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('speedshop_rincian_biaya_entries', 'speedshop_keuangan_rekening_tujuan_id')) {
                $table->unsignedBigInteger('speedshop_keuangan_rekening_tujuan_id')
                    ->nullable()
                    ->after('speedshop_keuangan_rekening_id');
            }
            if (! Schema::hasColumn('speedshop_rincian_biaya_entries', 'finance_ledger_tujuan_id')) {
                $table->unsignedBigInteger('finance_ledger_tujuan_id')
                    ->nullable()
                    ->after('finance_ledger_id');
            }
        });

        if (Schema::hasColumn('speedshop_rincian_biaya_entries', 'speedshop_keuangan_rekening_tujuan_id')) {
            Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) {
                $fkName = 'srbe_rekening_tujuan_fk';
                if (! $this->foreignKeyExists('speedshop_rincian_biaya_entries', $fkName)) {
                    $table->foreign('speedshop_keuangan_rekening_tujuan_id', $fkName)
                        ->references('id')
                        ->on('speedshop_keuangan_rekenings')
                        ->nullOnDelete();
                }
            });
        }

        if (Schema::hasColumn('speedshop_rincian_biaya_entries', 'finance_ledger_tujuan_id')
            && Schema::hasTable('speedshop_finance_ledgers')) {
            Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) {
                $fkName = 'srbe_finance_ledger_tujuan_fk';
                if (! $this->foreignKeyExists('speedshop_rincian_biaya_entries', $fkName)) {
                    $table->foreign('finance_ledger_tujuan_id', $fkName)
                        ->references('id')
                        ->on('speedshop_finance_ledgers')
                        ->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('speedshop_rincian_biaya_entries')) {
            return;
        }

        Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) {
            if (Schema::hasColumn('speedshop_rincian_biaya_entries', 'finance_ledger_tujuan_id')) {
                $fkName = 'srbe_finance_ledger_tujuan_fk';
                if ($this->foreignKeyExists('speedshop_rincian_biaya_entries', $fkName)) {
                    $table->dropForeign($fkName);
                }
                $table->dropColumn('finance_ledger_tujuan_id');
            }
            if (Schema::hasColumn('speedshop_rincian_biaya_entries', 'speedshop_keuangan_rekening_tujuan_id')) {
                $fkName = 'srbe_rekening_tujuan_fk';
                if ($this->foreignKeyExists('speedshop_rincian_biaya_entries', $fkName)) {
                    $table->dropForeign($fkName);
                }
                $table->dropColumn('speedshop_keuangan_rekening_tujuan_id');
            }
        });
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $row = $connection->selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ? LIMIT 1',
            [$database, $table, $name, 'FOREIGN KEY']
        );

        return $row !== null;
    }
};

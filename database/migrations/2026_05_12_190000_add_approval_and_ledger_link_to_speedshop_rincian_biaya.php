<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('speedshop_finance_ledgers')
            && ! Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_rincian_biaya_entry_id')) {
            Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
                $table->unsignedBigInteger('speedshop_rincian_biaya_entry_id')->nullable()->after('setoran_id');
            });
        }

        if (Schema::hasTable('speedshop_finance_ledgers')
            && Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_rincian_biaya_entry_id')) {
            $this->ensureLedgerForeignKeyAndUnique();
        }

        if (! Schema::hasTable('speedshop_rincian_biaya_entries')) {
            return;
        }

        if (! Schema::hasColumn('speedshop_rincian_biaya_entries', 'approval_status')) {
            Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) {
                $table->string('approval_status', 24)->nullable()->after('nominal');
            });
        }
        if (! Schema::hasColumn('speedshop_rincian_biaya_entries', 'submitted_by')) {
            Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) {
                $table->foreignId('submitted_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            });
        }
        if (! Schema::hasColumn('speedshop_rincian_biaya_entries', 'approved_by')) {
            Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) {
                $table->foreignId('approved_by')->nullable()->after('submitted_by')->constrained('users')->nullOnDelete();
            });
        }
        if (! Schema::hasColumn('speedshop_rincian_biaya_entries', 'approved_at')) {
            Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            });
        }
        if (! Schema::hasColumn('speedshop_rincian_biaya_entries', 'rejection_note')) {
            Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) {
                $table->text('rejection_note')->nullable()->after('approved_at');
            });
        }
        if (Schema::hasTable('speedshop_finance_ledgers')
            && ! Schema::hasColumn('speedshop_rincian_biaya_entries', 'finance_ledger_id')) {
            Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) {
                $table->unsignedBigInteger('finance_ledger_id')->nullable()->after('rejection_note');
            });
            Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) {
                $table->foreign('finance_ledger_id', 'fk_srbe_finance_ledger')
                    ->references('id')
                    ->on('speedshop_finance_ledgers')
                    ->nullOnDelete();
            });
        }
    }

    private function ensureLedgerForeignKeyAndUnique(): void
    {
        $fkName = 'fk_sf_led_rbea';
        $uqName = 'uq_sf_led_rbea';

        $fkNames = collect(Schema::getForeignKeys('speedshop_finance_ledgers'))->pluck('name')->all();
        if (! in_array($fkName, $fkNames, true)) {
            try {
                Schema::table('speedshop_finance_ledgers', function (Blueprint $table) use ($fkName) {
                    $table->foreign('speedshop_rincian_biaya_entry_id', $fkName)
                        ->references('id')
                        ->on('speedshop_rincian_biaya_entries')
                        ->nullOnDelete();
                });
            } catch (\Throwable) {
                // Sudah terpasang dengan nama lain / lingkungan tanpa dukungan FK penuh.
            }
        }

        $indexNames = collect(Schema::getIndexes('speedshop_finance_ledgers'))->pluck('name')->all();
        if (! in_array($uqName, $indexNames, true)) {
            try {
                Schema::table('speedshop_finance_ledgers', function (Blueprint $table) use ($uqName) {
                    $table->unique('speedshop_rincian_biaya_entry_id', $uqName);
                });
            } catch (\Throwable) {
                //
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('speedshop_rincian_biaya_entries')) {
            if (Schema::hasColumn('speedshop_rincian_biaya_entries', 'finance_ledger_id')) {
                Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) {
                    $table->dropForeign('fk_srbe_finance_ledger');
                    $table->dropColumn('finance_ledger_id');
                });
            }
            foreach (['rejection_note', 'approved_at'] as $col) {
                if (Schema::hasColumn('speedshop_rincian_biaya_entries', $col)) {
                    Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) use ($col) {
                        $table->dropColumn($col);
                    });
                }
            }
            foreach (['approved_by', 'submitted_by'] as $col) {
                if (Schema::hasColumn('speedshop_rincian_biaya_entries', $col)) {
                    Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) use ($col) {
                        $table->dropConstrainedForeignId($col);
                    });
                }
            }
            if (Schema::hasColumn('speedshop_rincian_biaya_entries', 'approval_status')) {
                Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) {
                    $table->dropColumn('approval_status');
                });
            }
        }

        if (Schema::hasTable('speedshop_finance_ledgers')
            && Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_rincian_biaya_entry_id')) {
            Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
                $table->dropForeign('fk_sf_led_rbea');
            });
            Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
                $table->dropUnique('uq_sf_led_rbea');
            });
            Schema::table('speedshop_finance_ledgers', function (Blueprint $table) {
                $table->dropColumn('speedshop_rincian_biaya_entry_id');
            });
        }
    }
};

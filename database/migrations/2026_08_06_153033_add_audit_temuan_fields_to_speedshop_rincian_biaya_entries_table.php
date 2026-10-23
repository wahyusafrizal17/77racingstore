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
            if (! Schema::hasColumn('speedshop_rincian_biaya_entries', 'audit_has_temuan')) {
                $table->boolean('audit_has_temuan')->default(false)->after('rejection_note');
            }
            if (! Schema::hasColumn('speedshop_rincian_biaya_entries', 'audit_deskripsi')) {
                $table->text('audit_deskripsi')->nullable()->after('audit_has_temuan');
            }
            if (! Schema::hasColumn('speedshop_rincian_biaya_entries', 'audit_penyelesaian')) {
                $table->text('audit_penyelesaian')->nullable()->after('audit_deskripsi');
            }
            if (! Schema::hasColumn('speedshop_rincian_biaya_entries', 'audit_by')) {
                $table->unsignedBigInteger('audit_by')->nullable()->after('audit_penyelesaian');
            }
            if (! Schema::hasColumn('speedshop_rincian_biaya_entries', 'audit_at')) {
                $table->timestamp('audit_at')->nullable()->after('audit_by');
            }
        });

        if (Schema::hasColumn('speedshop_rincian_biaya_entries', 'audit_by')
            && Schema::hasTable('users')) {
            Schema::table('speedshop_rincian_biaya_entries', function (Blueprint $table) {
                if (! $this->foreignKeyExists('speedshop_rincian_biaya_entries', 'ss_rb_audit_by_fk')) {
                    $table->foreign('audit_by', 'ss_rb_audit_by_fk')
                        ->references('id')
                        ->on('users')
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
            if ($this->foreignKeyExists('speedshop_rincian_biaya_entries', 'ss_rb_audit_by_fk')) {
                $table->dropForeign('ss_rb_audit_by_fk');
            }
            foreach (['audit_at', 'audit_by', 'audit_penyelesaian', 'audit_deskripsi', 'audit_has_temuan'] as $col) {
                if (Schema::hasColumn('speedshop_rincian_biaya_entries', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        $db = Schema::getConnection()->getDatabaseName();
        $row = Schema::getConnection()->selectOne(
            'select CONSTRAINT_NAME from information_schema.TABLE_CONSTRAINTS
             where CONSTRAINT_SCHEMA = ? and TABLE_NAME = ? and CONSTRAINT_NAME = ? and CONSTRAINT_TYPE = ?',
            [$db, $table, $name, 'FOREIGN KEY']
        );

        return $row !== null;
    }
};

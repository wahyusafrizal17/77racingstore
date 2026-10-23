<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hapus kolom invoice_id dari jurnal (pendapatan invoice hanya lewat ledger keuangan).
 * Aman untuk DB yang sudah migrasi lama dengan invoice_id, maupun instal baru tanpa kolom tersebut.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('speedshop_jurnal_blu_entries') && Schema::hasColumn('speedshop_jurnal_blu_entries', 'invoice_id')) {
            Schema::table('speedshop_jurnal_blu_entries', function (Blueprint $table) {
                try {
                    $table->dropForeign(['invoice_id']);
                } catch (\Throwable) {
                    //
                }
                $table->dropColumn('invoice_id');
            });
        }

        if (Schema::hasTable('speedshop_jurnal_saqu_entries') && Schema::hasColumn('speedshop_jurnal_saqu_entries', 'invoice_id')) {
            Schema::table('speedshop_jurnal_saqu_entries', function (Blueprint $table) {
                try {
                    $table->dropForeign('fk_jurnal_saqu_invoice');
                } catch (\Throwable) {
                    try {
                        $table->dropForeign(['invoice_id']);
                    } catch (\Throwable) {
                        //
                    }
                }
                $table->dropColumn('invoice_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('speedshop_jurnal_blu_entries') && ! Schema::hasColumn('speedshop_jurnal_blu_entries', 'invoice_id')) {
            Schema::table('speedshop_jurnal_blu_entries', function (Blueprint $table) {
                $table->unsignedBigInteger('invoice_id')->nullable()->after('speedshop_id');
            });
            Schema::table('speedshop_jurnal_blu_entries', function (Blueprint $table) {
                try {
                    $table->foreign('invoice_id')->references('id')->on('invoices')->nullOnDelete();
                } catch (\Throwable) {
                    //
                }
            });
        }

        if (Schema::hasTable('speedshop_jurnal_saqu_entries') && ! Schema::hasColumn('speedshop_jurnal_saqu_entries', 'invoice_id')) {
            Schema::table('speedshop_jurnal_saqu_entries', function (Blueprint $table) {
                $table->unsignedBigInteger('invoice_id')->nullable()->after('speedshop_id');
            });
            Schema::table('speedshop_jurnal_saqu_entries', function (Blueprint $table) {
                try {
                    $table->foreign('invoice_id', 'fk_jurnal_saqu_invoice')->references('id')->on('invoices')->nullOnDelete();
                } catch (\Throwable) {
                    //
                }
            });
        }
    }
};

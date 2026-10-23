<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $foreignKeys = [
            ['transaksi_online', 'transaksi_online_warehouse_packer_id_foreign'],
            ['transaksi_online', 'transaksi_online_warehouse_reseller_id_foreign'],
            ['transaksi_online', 'transaksi_online_warehouse_taker_id_foreign'],
            ['transaksi_offline', 'transaksi_offline_warehouse_reseller_id_foreign'],
            ['speedshop_finance_ledgers', 'speedshop_finance_ledgers_warehouse_pemasukan_id_foreign'],
            ['speedshop_finance_ledgers', 'speedshop_finance_ledgers_warehouse_piutang_id_foreign'],
            ['speedshop_finance_ledgers', 'sfl_wrpp_pembayaran_fk'],
            ['speedshop_finance_ledgers', 'sfl_wrtp_pembayaran_fk'],
            ['speedshop_finance_ledgers', 'sfl_wrt_refund_fk'],
            ['mo_operator_channels', 'mo_operator_channels_warehouse_reseller_id_foreign'],
            ['portal_catalog_bundles', 'portal_catalog_bundles_warehouse_reseller_id_foreign'],
            ['users', 'users_warehouse_reseller_id_foreign'],
        ];

        foreach ($foreignKeys as [$table, $name]) {
            $this->dropForeignKey($table, $name);
        }

        $tables = [
            'reseller_order_resend_request_items',
            'reseller_order_midtrans_payments',
            'reseller_voucher_redemptions',
            'reseller_order_items',
            'reseller_order_payments',
            'reseller_order_resend_requests',
            'warehouse_reseller_chat_messages',
            'reseller_orders',
            'reseller_vouchers',
            'warehouse_marketplace_chat_messages',
            'warehouse_marketplace_chats',
            'warehouse_reseller_tagihan_items',
            'warehouse_reseller_tagihan_midtrans_payments',
            'warehouse_reseller_tagihan_refunds',
            'warehouse_reseller_tagihan_pembayarans',
            'warehouse_reseller_piutang_pembayarans',
            'warehouse_reseller_tagihans',
            'warehouse_reseller_piutangs',
            'warehouse_reseller_chats',
            'warehouse_return_items',
            'warehouse_returns',
            'warehouse_rekap_penjualan_monthly_adjustments',
            'warehouse_resellers',
            'warehouse_aset_tetaps',
            'warehouse_neraca_marketplace_inputs',
            'warehouse_packers',
            'warehouse_pemasukans',
            'warehouse_piutangs',
            'warehouse_takers',
        ];

        Schema::disableForeignKeyConstraints();
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
    }

    private function dropForeignKey(string $table, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $exists = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$table, $name, 'FOREIGN KEY']
        );

        if (! $exists) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name) {
            $blueprint->dropForeign($name);
        });
    }
};

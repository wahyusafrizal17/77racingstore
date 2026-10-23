<?php

use App\Support\AdminUserPayload;
use App\Support\UserRoleMigration;
use Illuminate\Database\Migrations\Migration;

/**
 * Pastikan user role gudang Modifikasi Ori (termasuk Staf Packaging) punya warehouse_id.
 * Idempotent — aman di production.
 */
return new class extends Migration
{
    public function up(): void
    {
        UserRoleMigration::fixModifikasiOriLegacyRoles();
    }

    public function down(): void
    {
        //
    }
};

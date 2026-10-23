<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\AdminUserPayload;
use App\Support\RoleAccess;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'no_hp',
        'tanggal_lahir',
        'foto_profil',
        'password',
        'role',
        'section',
        'warehouse_id',
        'warehouse_reseller_id',
        'speedshop_id',
        'attendance_validator_id',
        'attendance_location_id',
    ];

    public const SECTION_RESELLER = 'reseller';

    public const ROLE_RESELLER = 'Reseller';

    public const SECTION_MO_OPERATOR = 'mo_operator';

    public const ROLE_MO_OPERATOR = 'Operator MO';

    public const SECTION_MARKETPLACE = 'marketplace';

    public const ROLE_CUSTOMER = 'Customer';

    /** Role staf divisi Warehouse (hak operasional setara Staf). */
    public const ROLE_WAREHOUSE_STAF_PACKING = 'Staf Packing';

    public const ROLE_WAREHOUSE_STAF_WAREHOUSE = 'Staf Warehouse';

    /** @deprecated Gunakan ROLE_WAREHOUSE_STAF_WAREHOUSE */
    public const ROLE_WAREHOUSE_STAF_INVENTORI = self::ROLE_WAREHOUSE_STAF_WAREHOUSE;

    /** Accounting khusus alur reseller (pesanan, tagihan, piutang, return, rekap). */
    public const ROLE_WAREHOUSE_STAFF_ACCOUNTING_RESELLER = 'Staff Accounting Reseller';

    public const SECTION_OWNER = 'owner';

    public const SECTION_MODIFIKASI_ORI = 'modifikasi_ori';

    public const ROLE_OWNER = 'Owner';

    public const ROLE_SPEEDSHOP_HEAD_AREA = 'Head Area';

    public const ROLE_SPEEDSHOP_MEKANIK = 'Mekanik';

    public const ROLE_SPEEDSHOP_FLP = 'FLP';

    public const ROLE_MODIFIKASI_HEAD_PRODUKSI = 'Head Produksi';

    public const ROLE_MODIFIKASI_STAF_PRODUKSI = 'Staf Produksi';

    public const ROLE_MODIFIKASI_STAF_INVENTORI = 'Staf Inventori';

    /** @deprecated Role tidak digunakan lagi; akun dimigrasikan ke Staf Inventori. */
    public const ROLE_MODIFIKASI_STAF_PACKAGING = 'Staf Packaging';

    public const ROLE_MODIFIKASI_HEAD_WAREHOUSE = 'Head Warehouse';

    public const ROLE_MODIFIKASI_STAF_PROCESSING_WAREHOUSE = 'Staf Processing Warehouse';

    public const ROLE_MODIFIKASI_HEAD_MARKETING = 'Head Marketing';

    public const ROLE_MODIFIKASI_STAF_MARKETING = 'Staf Marketing';

    /** Staf Marketing yang tetap boleh mengakses menu Marketplace. */
    public const MARKETING_MARKETPLACE_STAFF_EMAIL = 'najlaahasnaa@modifikasiori.com';

    public const ROLE_MODIFIKASI_HEAD_ACCOUNTING = 'Head Accounting';

    public const ROLE_MODIFIKASI_STAF_ACCOUNTING = 'Staf Accounting';

    public const ROLE_MODIFIKASI_HEAD_HR = 'Head HR';

    public const ROLE_MODIFIKASI_STAF_HR = 'Staf HR';

    public const ROLE_MODIFIKASI_HEAD_AUDIT = 'Head Audit';

    public const HEAD_AUDIT_SCOPE_SESSION_KEY = 'head_audit_scope';

    /** @return list<string> */
    public static function modifikasiOriHrAdminRoles(): array
    {
        return [
            self::ROLE_MODIFIKASI_HEAD_HR,
            self::ROLE_MODIFIKASI_STAF_HR,
        ];
    }

    public static function isModifikasiOriHrAdminRole(?string $section, ?string $role): bool
    {
        return $section === self::SECTION_MODIFIKASI_ORI
            && in_array($role, self::modifikasiOriHrAdminRoles(), true);
    }

    public static function grantsAdminPanelAccess(?string $section, ?string $role): bool
    {
        return ($section === 'admin' && $role === 'SuperAdmin')
            || self::isModifikasiOriHrAdminRole($section, $role)
            || ($section === 'speedshop' && $role === 'Manager');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function speedshop()
    {
        return $this->belongsTo(Speedshop::class);
    }

    /** Cabang speedshop yang di-assign (Accounting bisa lebih dari satu). */
    public function assignedSpeedshops()
    {
        return $this->belongsToMany(Speedshop::class, 'speedshop_user')
            ->withTimestamps()
            ->orderBy('speedshops.id');
    }

    public function mekanik()
    {
        return $this->hasOne(Mekanik::class);
    }

    public function warehouseReseller()
    {
        return $this->belongsTo(WarehouseReseller::class, 'warehouse_reseller_id');
    }

    public function moOperatorChannels()
    {
        return $this->hasMany(MoOperatorChannel::class);
    }

    public function attendanceValidator()
    {
        return $this->belongsTo(self::class, 'attendance_validator_id');
    }

    public function attendanceLocation()
    {
        return $this->belongsTo(AttendanceLocation::class, 'attendance_location_id');
    }

    /** @return \Illuminate\Database\Eloquent\Relations\HasMany<User> */
    public function employeesToValidate()
    {
        return $this->hasMany(self::class, 'attendance_validator_id');
    }

    public function isAttendanceValidator(): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        if ($this->isModifikasiOriHrAdmin()) {
            return true;
        }

        if ($this->isSpeedshopHeadArea() || $this->isManager()) {
            return true;
        }

        if (str_starts_with((string) $this->role, 'Head ')) {
            return true;
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('leave_requests')) {
            return self::query()
                ->where('attendance_validator_id', $this->id)
                ->exists();
        }

        $hasLegacy = self::query()
            ->where('attendance_validator_id', $this->id)
            ->exists();

        if ($hasLegacy) {
            return true;
        }

        $reviewerId = (int) $this->id;

        return LeaveRequest::query()
            ->where(function ($query) use ($reviewerId) {
                $query->where('head_approver_id', $reviewerId)
                    ->orWhere('manager_approver_id', $reviewerId)
                    ->orWhere('owner_approver_id', $reviewerId)
                    ->orWhere('reviewed_by', $reviewerId);
            })
            ->where(function ($query) {
                $query->where('status', LeaveRequest::STATUS_PENDING)
                    ->orWhere(function ($approved) {
                        $approved->where('status', LeaveRequest::STATUS_APPROVED)
                            ->where('reviewed_at', '>=', now()->subDays(90));
                    });
            })
            ->exists();
    }

    public function pendingLeaveValidationCount(): int
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('leave_requests')) {
            return 0;
        }

        return (int) LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->where(function ($query) {
                $query->where(function ($head) {
                    $head->where('head_approver_id', $this->id)
                        ->whereNull('head_signed_at');
                })->orWhere(function ($manager) {
                    $manager->where('manager_approver_id', $this->id)
                        ->whereNull('manager_signed_at')
                        ->where(function ($ready) {
                            $ready->whereNull('head_approver_id')
                                ->orWhereNotNull('head_signed_at');
                        });
                })->orWhere(function ($legacy) {
                    $legacy->whereNull('head_approver_id')
                        ->whereNull('manager_approver_id')
                        ->where(function ($inner) {
                            $inner->where('attendance_validator_id', $this->id)
                                ->orWhereHas('user', fn ($userQuery) => $userQuery->where('attendance_validator_id', $this->id));
                        });
                });
            })
            ->count();
    }

    public function canValidateAttendance(): bool
    {
        return app(\App\Services\AttendanceValidationService::class)->canReview($this);
    }

    public function canSeeAttendanceRecap(): bool
    {
        return $this->isSpeedshopAccounting() || $this->canValidateAttendance();
    }

    public function pendingAttendanceValidationCount(): int
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('attendance_records')) {
            return 0;
        }

        if (! \Illuminate\Support\Facades\Schema::hasColumn('attendance_records', 'validator_user_id')) {
            return 0;
        }

        $query = AttendanceRecord::query()
            ->where('status', AttendanceRecord::STATUS_PENDING);

        if (! $this->isOwner()) {
            $query->where('validator_user_id', $this->id);
        }

        return (int) $query->count();
    }

    public function hasCustomFotoProfil(): bool
    {
        return filled($this->foto_profil);
    }

    public function fotoProfilUrl(): string
    {
        if (! $this->hasCustomFotoProfil()) {
            return asset('images/logo.png');
        }

        return \App\Support\UploadStorage::url($this->foto_profil) ?: asset('images/logo.png');
    }

    public function isReseller(): bool
    {
        return $this->section === self::SECTION_RESELLER && $this->role === self::ROLE_RESELLER;
    }

    public function isMoOperator(): bool
    {
        return $this->section === self::SECTION_MO_OPERATOR && $this->role === self::ROLE_MO_OPERATOR;
    }

    public function isMarketplaceCustomer(): bool
    {
        return $this->section === self::SECTION_MARKETPLACE && $this->role === self::ROLE_CUSTOMER;
    }

    public function isOwner(): bool
    {
        return $this->section === self::SECTION_OWNER && $this->role === self::ROLE_OWNER;
    }

    public function isManager(): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        if ($this->role === 'Manager') {
            return true;
        }

        if ($this->section === 'speedshop' && $this->role === self::ROLE_SPEEDSHOP_HEAD_AREA) {
            return true;
        }

        if ($this->section === self::SECTION_MODIFIKASI_ORI) {
            return in_array($this->role, RoleAccess::modifikasiManagerRoles(), true);
        }

        return false;
    }

    public function canAccessOperationalSection(string $section): bool
    {
        if ($this->usesModifikasiOriWelcomePortal() || $this->isModifikasiOriHrAdmin()) {
            return false;
        }

        if ($this->isModifikasiOriHeadAudit()) {
            return match ($section) {
                'warehouse' => ! $this->isHeadAuditSpeedshopContext(),
                'speedshop' => $this->isHeadAuditSpeedshopContext(),
                default => RoleAccess::canAccessOperationalSection($this, $section),
            };
        }

        return RoleAccess::canAccessOperationalSection($this, $section);
    }

    /**
     * Role efektif untuk middleware `role:` — alias legacy / role baru.
     *
     * @return list<string>
     */
    public function routeRoleAliases(): array
    {
        return RoleAccess::routeRoleAliases($this);
    }

    public function isStaf(): bool
    {
        if ($this->role === 'Staf') {
            return true;
        }

        if ($this->section === self::SECTION_MODIFIKASI_ORI) {
            return in_array($this->role, [
                self::ROLE_MODIFIKASI_STAF_PRODUKSI,
                self::ROLE_MODIFIKASI_STAF_MARKETING,
                self::ROLE_MODIFIKASI_STAF_HR,
                self::ROLE_MODIFIKASI_STAF_PROCESSING_WAREHOUSE,
                self::ROLE_WAREHOUSE_STAF_WAREHOUSE,
                self::ROLE_MODIFIKASI_STAF_INVENTORI,
            ], true);
        }

        return $this->isWarehouse()
            && in_array($this->role, [self::ROLE_WAREHOUSE_STAF_PACKING, self::ROLE_WAREHOUSE_STAF_WAREHOUSE, 'Staf Inventori'], true);
    }

    /** Staf operasional warehouse (bukan packing). Termasuk role legacy Staf Inventori. */
    public function isStafWarehouse(): bool
    {
        if ($this->section === self::SECTION_MODIFIKASI_ORI) {
            return in_array($this->role, [
                self::ROLE_MODIFIKASI_STAF_PROCESSING_WAREHOUSE,
                self::ROLE_WAREHOUSE_STAF_WAREHOUSE,
                self::ROLE_MODIFIKASI_STAF_INVENTORI,
            ], true);
        }

        if (! $this->isWarehouse()) {
            return false;
        }

        return $this->role === 'Staf'
            || $this->role === self::ROLE_WAREHOUSE_STAF_WAREHOUSE
            || $this->role === 'Staf Inventori';
    }

    public function isWarehouseManager(): bool
    {
        if ($this->section === self::SECTION_MODIFIKASI_ORI) {
            return in_array($this->role, ['Manager', self::ROLE_MODIFIKASI_HEAD_WAREHOUSE], true);
        }

        return $this->isWarehouse() && $this->role === 'Manager';
    }

    /** Pengaju data pembelian warehouse/transaksi (section warehouse atau staf MO warehouse). */
    public function isWarehousePembelianSubmitter(): bool
    {
        if ($this->section === 'warehouse') {
            return true;
        }

        return $this->section === self::SECTION_MODIFIKASI_ORI
            && in_array($this->role, RoleAccess::modifikasiWarehouseRoles(), true);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<User>  $query
     * @return \Illuminate\Database\Eloquent\Builder<User>
     */
    public function scopeWarehousePembelianSubmitters($query)
    {
        return $query->where(function ($q) {
            $q->where('section', 'warehouse')
                ->orWhere(function ($mo) {
                    $mo->where('section', self::SECTION_MODIFIKASI_ORI)
                        ->whereIn('role', RoleAccess::modifikasiWarehouseRoles());
                });
        });
    }

    public function isModifikasiOriHeadWarehouse(): bool
    {
        return $this->section === self::SECTION_MODIFIKASI_ORI
            && $this->role === self::ROLE_MODIFIKASI_HEAD_WAREHOUSE;
    }

    public function isSpeedshopHeadArea(): bool
    {
        return $this->section === 'speedshop'
            && $this->role === self::ROLE_SPEEDSHOP_HEAD_AREA;
    }

    /** Head Warehouse / Manager 77racing & manager speedshop lintas cabang — filter outlet di navbar. */
    public function canSelectSpeedshopScope(): bool
    {
        if ($this->isModifikasiOriHeadAudit()) {
            return $this->isHeadAuditSpeedshopContext();
        }

        return $this->isSpeedshop()
            || $this->isModifikasiOriHeadWarehouse()
            || $this->isModifikasiOriManager();
    }

    public function isModifikasiOriHeadAudit(): bool
    {
        return $this->section === self::SECTION_MODIFIKASI_ORI
            && $this->role === self::ROLE_MODIFIKASI_HEAD_AUDIT;
    }

    /** Nilai session filter Head Audit: modifikasi | all | {speedshop_id}. */
    public function headAuditScopeSelection(): string
    {
        if (! $this->isModifikasiOriHeadAudit()) {
            return 'modifikasi';
        }

        $scope = session(self::HEAD_AUDIT_SCOPE_SESSION_KEY, 'modifikasi');

        return is_string($scope) && $scope !== '' ? $scope : 'modifikasi';
    }

    public function isHeadAuditSpeedshopContext(): bool
    {
        return $this->isModifikasiOriHeadAudit()
            && $this->headAuditScopeSelection() !== 'modifikasi';
    }

    /** Terima/tolak mutasi masuk dari Produksi: hanya Manager Warehouse & Staf Warehouse (bukan packing/accounting). */
    public function canVerifyWarehouseMutasi(): bool
    {
        if (! $this->isWarehouse() || $this->isStafPacking() || $this->isWarehouseAccounting() || $this->isWarehouseStaffAccountingReseller()) {
            return false;
        }

        return $this->isWarehouseManager() || $this->isStafWarehouse();
    }

    /**
     * Staf inventori terbatas (menu/route dipangkas).
     * Termasuk role "Staf Warehouse" (rename dari Staf Inventori) dan Staf Processing Warehouse.
     */
    public function isStafInventori(): bool
    {
        if ($this->section === self::SECTION_MODIFIKASI_ORI) {
            return in_array($this->role, [
                self::ROLE_MODIFIKASI_STAF_PROCESSING_WAREHOUSE,
                self::ROLE_WAREHOUSE_STAF_WAREHOUSE,
                self::ROLE_MODIFIKASI_STAF_INVENTORI,
            ], true);
        }

        return $this->isWarehouse()
            && in_array($this->role, [self::ROLE_WAREHOUSE_STAF_WAREHOUSE, self::ROLE_MODIFIKASI_STAF_INVENTORI], true);
    }

    /** Staf Processing Warehouse — operasional gudang + proses pesanan customer/reseller. */
    public function isStafProcessingWarehouse(): bool
    {
        return $this->section === self::SECTION_MODIFIKASI_ORI
            && $this->role === self::ROLE_MODIFIKASI_STAF_PROCESSING_WAREHOUSE;
    }

    /** Proses/scan/kirim pesanan marketplace & reseller di warehouse. */
    public function canProcessWarehouseMarketplaceOrders(): bool
    {
        return $this->isWarehouseManager()
            || $this->isWarehouseStaffAccountingReseller()
            || $this->isStafProcessingWarehouse();
    }

    public function isStafPacking(): bool
    {
        if ($this->section === self::SECTION_MODIFIKASI_ORI) {
            return $this->role === self::ROLE_MODIFIKASI_STAF_PACKAGING;
        }

        return $this->isWarehouse() && $this->role === self::ROLE_WAREHOUSE_STAF_PACKING;
    }

    /** UI Stock Out / Status Resi (staf packing & staf inventori warehouse). */
    public function isWarehouseStockOutOperator(): bool
    {
        return $this->isStafPacking() || $this->isStafInventori();
    }

    public function isOutlet(): bool
    {
        if ($this->section === 'speedshop') {
            return in_array($this->role, [
                'Outlet',
                'Staf',
                self::ROLE_SPEEDSHOP_MEKANIK,
                self::ROLE_SPEEDSHOP_FLP,
            ], true);
        }

        return $this->role === 'Outlet';
    }

    /**
     * Operator cabang speedshop (Staf/Outlet/Mekanik/FLP):
     * operasional + master data jasa servis, mekanik, pelanggan (kategori/brand hanya Manager);
     * laporan keuangan tetap Manager/Accounting.
     */
    public function isSpeedshopStaf(): bool
    {
        return $this->isSpeedshop() && ($this->isStaf() || $this->isOutlet());
    }

    public function isAccounting(): bool
    {
        if ($this->role === 'Accounting') {
            return true;
        }

        // Staf Accounting = legacy Staff Accounting Reseller (bukan Accounting penuh).
        if ($this->section === self::SECTION_MODIFIKASI_ORI) {
            if ($this->role === self::ROLE_MODIFIKASI_HEAD_ACCOUNTING) {
                return true;
            }

            return $this->isModifikasiOriHeadAudit() && $this->isHeadAuditSpeedshopContext();
        }

        return false;
    }

    public function isWarehouseAccounting(): bool
    {
        if ($this->section === self::SECTION_MODIFIKASI_ORI) {
            if ($this->role === self::ROLE_MODIFIKASI_HEAD_ACCOUNTING) {
                return true;
            }

            return $this->isModifikasiOriHeadAudit() && ! $this->isHeadAuditSpeedshopContext();
        }

        return $this->isWarehouse() && $this->role === 'Accounting';
    }

    public function isWarehouseStaffAccountingReseller(): bool
    {
        if ($this->section === self::SECTION_MODIFIKASI_ORI) {
            return in_array($this->role, [
                self::ROLE_MODIFIKASI_STAF_ACCOUNTING,
                self::ROLE_WAREHOUSE_STAFF_ACCOUNTING_RESELLER,
            ], true);
        }

        return $this->isWarehouse() && $this->role === self::ROLE_WAREHOUSE_STAFF_ACCOUNTING_RESELLER;
    }

    /** Tagihan/piutang reseller: Staff Accounting Reseller, Accounting warehouse, atau Manager warehouse. */
    public function canManageWarehouseResellerFinance(): bool
    {
        return $this->isWarehouseStaffAccountingReseller()
            || $this->isWarehouseAccounting()
            || $this->canVerifyWarehouseFinancial();
    }

    /** Manager Warehouse — verifikasi pembelian & transaksi offline. */
    public function canVerifyWarehouseFinancial(): bool
    {
        return $this->isWarehouseManager();
    }

    /** Master Produk 77racing / Speedshop / Part Luar — Manager Speedshop, Head Warehouse & Manager 77racing. */
    public function canManageProductMasterCatalog(): bool
    {
        return $this->isModifikasiOriHeadWarehouse()
            || $this->isModifikasiOriManager()
            || ($this->isSpeedshop() && $this->isManager());
    }

    public function isModifikasiOriHeadMarketing(): bool
    {
        return $this->section === self::SECTION_MODIFIKASI_ORI
            && $this->role === self::ROLE_MODIFIKASI_HEAD_MARKETING;
    }

    public function isModifikasiOriStafMarketing(): bool
    {
        return $this->section === self::SECTION_MODIFIKASI_ORI
            && $this->role === self::ROLE_MODIFIKASI_STAF_MARKETING;
    }

    /** Head/Staf Marketing boleh melihat Master Produk MO dan mengelola gambar katalog. */
    public function canUploadMoProductImages(): bool
    {
        return $this->canManageProductMasterCatalog()
            || $this->isModifikasiOriHeadMarketing()
            || $this->isModifikasiOriStafMarketing();
    }

    /** Tim yang menerima dan menangani chat reseller/customer marketplace. */
    public function canHandleExternalCustomerChats(): bool
    {
        return in_array($this->role, [
            self::ROLE_MODIFIKASI_HEAD_MARKETING,
            self::ROLE_MODIFIKASI_STAF_MARKETING,
            self::ROLE_MODIFIKASI_STAF_PROCESSING_WAREHOUSE,
        ], true);
    }

    /**
     * Marketplace sales: Head Marketing, atau Staf Marketing dengan email khusus.
     * Staf Marketing lain tidak mendapat akses menu/route Marketplace.
     */
    public function canViewMarketplaceSales(): bool
    {
        if ($this->isModifikasiOriHeadMarketing()) {
            return true;
        }

        return $this->isMarketingMarketplaceStaff();
    }

    /** Staf Marketing dengan email khusus (akses Marketplace + dashboard reseller online). */
    public function isMarketingMarketplaceStaff(): bool
    {
        return $this->isModifikasiOriStafMarketing()
            && strcasecmp((string) $this->email, self::MARKETING_MARKETPLACE_STAFF_EMAIL) === 0;
    }

    /** Monitor TV pesanan reseller — Marketing (sesuai marketplace) + Staf Processing Warehouse. */
    public function canViewResellerOrderMonitorTv(): bool
    {
        return $this->canViewMarketplaceSales() || $this->isStafProcessingWarehouse();
    }

    /**
     * Head/Staf Marketing (2 shift): bebas absen kapan saja, tanpa window jam, tanpa flag telat,
     * dan tanpa syarat radius lokasi (boleh di luar kantor / event).
     * Tetap 1× masuk + 1× pulang per hari, selfie, dan validasi.
     */
    public function hasFlexibleAttendanceSchedule(): bool
    {
        return $this->isModifikasiOriHeadMarketing() || $this->isModifikasiOriStafMarketing();
    }

    /** Hanya Head Marketing yang mengelola master reseller & operator MO. */
    public function canManageMarketingResellers(): bool
    {
        return $this->isModifikasiOriHeadMarketing();
    }

    public function canManageMoOperators(): bool
    {
        return $this->canManageMarketingResellers();
    }

    /** Head Marketing dan Manager Warehouse dapat membuat akun login reseller. */
    public function canCreateResellerLogin(): bool
    {
        return $this->canManageMarketingResellers() || $this->isWarehouseManager();
    }

    /** Hanya Head Marketing yang dapat melihat monitoring kunjungan website. */
    public function canViewWebsiteMonitoring(): bool
    {
        return $this->isModifikasiOriHeadMarketing();
    }

    /** Dashboard omset marketing — hanya Head Marketing. */
    public function canAccessMarketingOmsetDashboard(): bool
    {
        return $this->isModifikasiOriHeadMarketing();
    }

    /**
     * Dashboard ringkas reseller online + ranking + produk terlaris
     * (Staf Marketing email khusus).
     */
    public function canAccessMarketingStaffResellerDashboard(): bool
    {
        return $this->isMarketingMarketplaceStaff();
    }

    public function isSpeedshopAccounting(): bool
    {
        return $this->isSpeedshop() && $this->isAccounting();
    }

    public function isProduksi(): bool
    {
        return $this->section === 'produksi'
            || $this->canAccessOperationalSection('produksi');
    }

    public function isWarehouse(): bool
    {
        if ($this->isModifikasiOriHeadAudit() && $this->isHeadAuditSpeedshopContext()) {
            return false;
        }

        return $this->section === 'warehouse'
            || $this->canAccessOperationalSection('warehouse');
    }

    public function isSpeedshop(): bool
    {
        if ($this->isModifikasiOriHeadAudit() && $this->isHeadAuditSpeedshopContext()) {
            return true;
        }

        return $this->section === 'speedshop' || $this->isOwner();
    }

    public function isSpeedshopMekanik(): bool
    {
        return $this->section === 'speedshop' && $this->role === self::ROLE_SPEEDSHOP_MEKANIK;
    }

    /**
     * Manager Speedshop lintas cabang: satu akun untuk semua outlet (atau outlet per gudang jika warehouse_id diset).
     * Head Area dengan cabang ter-assign tidak dianggap regional.
     */
    public function isSpeedshopRegionalManager(): bool
    {
        if (! $this->isSpeedshop() || $this->speedshop_id !== null) {
            return false;
        }

        if ($this->role === self::ROLE_SPEEDSHOP_HEAD_AREA) {
            return false;
        }

        return $this->isManager();
    }

    /**
     * Manager Speedshop untuk satu cabang: notifikasi verifikasi / persetujuan ke manager cabang itu
     * dan ke manager lintas cabang (satu akun, speedshop_id null, warehouse_id sama dengan gudang outlet).
     *
     * @return EloquentCollection<int, User>
     */
    public static function speedshopManagersForOutlet(?int $speedshopId): EloquentCollection
    {
        if (! $speedshopId) {
            return new EloquentCollection;
        }

        $speedshop = Speedshop::query()->find($speedshopId);
        if (! $speedshop) {
            return new EloquentCollection;
        }

        $warehouseId = $speedshop->warehouse_id;

        $q = static::query()
            ->where('section', 'speedshop')
            ->whereIn('role', ['Manager', self::ROLE_SPEEDSHOP_HEAD_AREA])
            ->where(function ($outer) use ($speedshopId, $warehouseId) {
                $outer->where('speedshop_id', $speedshopId)
                    ->orWhereHas('assignedSpeedshops', fn ($assigned) => $assigned->where('speedshops.id', $speedshopId));
                if ($warehouseId) {
                    $outer->orWhere(function ($inner) use ($warehouseId) {
                        $inner->whereNull('speedshop_id')
                            ->whereIn('role', ['Manager', self::ROLE_SPEEDSHOP_HEAD_AREA])
                            ->whereDoesntHave('assignedSpeedshops')
                            ->where('warehouse_id', $warehouseId);
                    });
                }
            });

        $ids = $q->pluck('id')->unique()->values()->all();

        if ($ids === []) {
            return new EloquentCollection;
        }

        return static::query()->whereIn('id', $ids)->get();
    }

    /**
     * Approver PO/stock-in Speedshop untuk satu cabang:
     * Manager + operator cabang (Staf/Outlet), plus regional manager (speedshop_id null, warehouse_id sama).
     *
     * @return EloquentCollection<int, User>
     */
    public static function speedshopApproversForOutlet(?int $speedshopId): EloquentCollection
    {
        if (! $speedshopId) {
            return new EloquentCollection;
        }

        $speedshop = Speedshop::query()->find($speedshopId);
        if (! $speedshop) {
            return new EloquentCollection;
        }

        $warehouseId = $speedshop->warehouse_id;

        $q = static::query()
            ->where('section', 'speedshop')
            ->whereIn('role', ['Manager', 'Staf', 'Outlet'])
            ->where(function ($outer) use ($speedshopId, $warehouseId) {
                $outer->where('speedshop_id', $speedshopId);
                if ($warehouseId) {
                    $outer->orWhere(function ($inner) use ($warehouseId) {
                        $inner->whereNull('speedshop_id')
                            ->whereIn('role', ['Manager', self::ROLE_SPEEDSHOP_HEAD_AREA])
                            ->where('warehouse_id', $warehouseId);
                    });
                }
            });

        $ids = $q->pluck('id')->unique()->values()->all();

        if ($ids === []) {
            return new EloquentCollection;
        }

        return static::query()->whereIn('id', $ids)->get();
    }

    /**
     * Akun Accounting speedshop untuk satu cabang (verifikasi setoran, permintaan kas kecil, dll.).
     *
     * @return EloquentCollection<int, User>
     */
    public static function accountingUsersForSpeedshop(int $speedshopId): EloquentCollection
    {
        if ($speedshopId <= 0) {
            return new EloquentCollection;
        }

        return static::query()
            ->where('section', 'speedshop')
            ->where('role', 'Accounting')
            ->where(function ($q) use ($speedshopId) {
                $q->where('speedshop_id', $speedshopId)
                    ->orWhereHas('assignedSpeedshops', fn ($aq) => $aq->where('speedshops.id', $speedshopId));
            })
            ->get();
    }

    /**
     * Manager Warehouse untuk satu gudang (verifikasi mutasi masuk, persetujuan transaksi offline Reseller/Umum).
     *
     * @return EloquentCollection<int, User>
     */
    public static function warehouseManagersForWarehouse(?int $warehouseId): EloquentCollection
    {
        if (! $warehouseId) {
            return new EloquentCollection;
        }

        return static::query()
            ->where('section', 'warehouse')
            ->where('role', 'Manager')
            ->where('warehouse_id', $warehouseId)
            ->get();
    }

    public function isHeadHrAdmin(): bool
    {
        return $this->section === self::SECTION_MODIFIKASI_ORI
            && $this->role === self::ROLE_MODIFIKASI_HEAD_HR;
    }

    /** Manager cabang speedshop (bukan Head Area / Owner). */
    public function isSpeedshopOutletManager(): bool
    {
        return $this->section === 'speedshop'
            && $this->role === 'Manager';
    }

    /** Sementara: ubah pengaturan panel HR (absensi, karyawan, cabang). */
    public function canManageHrAdminSettings(): bool
    {
        return $this->canAccessAdminPanel();
    }

    /** Head HR & Staf HR 77racing — akses panel /admin. */
    public function isModifikasiOriHrAdmin(): bool
    {
        return self::isModifikasiOriHrAdminRole($this->section, $this->role);
    }

    /** Manager puncak divisi 77racing — akses penuh menu warehouse (setara Head Warehouse). */
    public function isModifikasiOriManager(): bool
    {
        return $this->section === self::SECTION_MODIFIKASI_ORI && $this->role === 'Manager';
    }

    /**
     * Role 77racing yang sementara hanya melihat portal dashboard (belum ada menu operasional).
     */
    public function usesModifikasiOriWelcomePortal(): bool
    {
        if ($this->section !== self::SECTION_MODIFIKASI_ORI) {
            return false;
        }

        return in_array($this->role, [
            self::ROLE_MODIFIKASI_HEAD_MARKETING,
            self::ROLE_MODIFIKASI_STAF_MARKETING,
        ], true);
    }

    /** Akses panel /admin (cabang & master pengguna): SuperAdmin, Head/Staf HR, Manager Speedshop outlet. */
    public function canAccessAdminPanel(): bool
    {
        return self::grantsAdminPanelAccess($this->section, $this->role);
    }

    /** @deprecated Gunakan canAccessAdminPanel() atau isHeadHrAdmin(). */
    public function isSuperAdmin(): bool
    {
        return $this->canAccessAdminPanel();
    }

    public static function adminPanelUserQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return static::query()
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('section', self::SECTION_MODIFIKASI_ORI)
                        ->whereIn('role', self::modifikasiOriHrAdminRoles());
                })->orWhere(function ($q) {
                    $q->where('section', 'admin')
                        ->where('role', 'SuperAdmin');
                })->orWhere(function ($q) {
                    $q->where('section', 'speedshop')
                        ->where('role', 'Manager');
                });
            });
    }

    /** Reseller & customer marketplace tidak ditampilkan di /admin/users. */
    public function scopeAdminKaryawan($query)
    {
        return $query->whereNotIn('section', [
            self::SECTION_RESELLER,
            self::SECTION_MARKETPLACE,
            self::SECTION_MO_OPERATOR,
        ]);
    }

    public function isVisibleInAdminKaryawan(): bool
    {
        return ! in_array($this->section, [self::SECTION_RESELLER, self::SECTION_MARKETPLACE, self::SECTION_MO_OPERATOR], true);
    }

    public function hasSection(string $section): bool
    {
        return $this->section === $section;
    }

    public function isBirthdayToday(?\DateTimeInterface $today = null): bool
    {
        if ($this->tanggal_lahir === null) {
            return false;
        }

        $today = \Illuminate\Support\Carbon::parse($today ?? now())->startOfDay();
        $birth = $this->tanggal_lahir->copy()->startOfDay();

        // 29 Feb: rayakan 28 Feb jika tahun ini bukan kabisat.
        if ((int) $birth->format('m') === 2 && (int) $birth->format('d') === 29) {
            if (! $today->isLeapYear() && $today->month === 2 && $today->day === 28) {
                return true;
            }
        }

        return $today->month === (int) $birth->format('m')
            && $today->day === (int) $birth->format('d');
    }

    public function dashboardRoute(): string
    {
        return match ($this->section) {
            'admin' => 'admin.dashboard',
            self::SECTION_MODIFIKASI_ORI => match (true) {
                $this->canAccessAdminPanel() => 'admin.dashboard',
                $this->isModifikasiOriHeadAudit() => $this->isHeadAuditSpeedshopContext()
                    ? 'speedshop.dashboard'
                    : 'warehouse.dashboard',
                $this->canAccessMarketingStaffResellerDashboard() => 'modifikasi-ori.dashboard',
                $this->isModifikasiOriStafMarketing() => 'absensi.index',
                $this->usesModifikasiOriWelcomePortal() => 'modifikasi-ori.dashboard',
                $this->isModifikasiOriManager() => 'warehouse.dashboard',
                $this->canAccessOperationalSection('warehouse')
                    && ! $this->canAccessOperationalSection('produksi') => 'warehouse.dashboard',
                $this->canAccessOperationalSection('produksi') => 'produksi.dashboard',
                $this->canAccessOperationalSection('warehouse') => 'warehouse.dashboard',
                default => 'modifikasi-ori.dashboard',
            },
            'produksi' => 'produksi.dashboard',
            'warehouse' => 'warehouse.dashboard',
            self::SECTION_RESELLER => 'reseller.home',
            self::SECTION_MO_OPERATOR => 'mo-operator.katalog.index',
            self::SECTION_MARKETPLACE => 'marketplace.home',
            'owner' => 'owner.dashboard',
            'speedshop' => 'speedshop.dashboard',
            default => 'speedshop.dashboard',
        };
    }

    public function activeWarehouseResellerId(): ?int
    {
        if (! $this->isReseller()) {
            return null;
        }

        return $this->warehouse_reseller_id ? (int) $this->warehouse_reseller_id : null;
    }

    /** @return list<int> */
    public function moOperatorChannelIds(): array
    {
        if (! $this->isMoOperator()) {
            return [];
        }

        return $this->moOperatorChannels()
            ->pluck('warehouse_reseller_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function activeWarehouseId(): ?int
    {
        if ($this->isMoOperator()) {
            return $this->warehouse_id ? (int) $this->warehouse_id : null;
        }

        if ($this->isReseller()) {
            return $this->warehouseReseller?->warehouse_id
                ? (int) $this->warehouseReseller->warehouse_id
                : $this->warehouse_id;
        }

        if ($this->isSpeedshop() && $this->speedshop_id) {
            return $this->speedshop?->mutasi_warehouse_id ?? $this->speedshop?->warehouse_id;
        }

        if ($this->isSpeedshop() && ! $this->speedshop_id) {
            $scopeId = $this->speedshopScopeId();
            if ($scopeId) {
                $scoped = Speedshop::query()->find($scopeId);

                return $scoped?->mutasi_warehouse_id ?? $scoped?->warehouse_id;
            }

            // Manager lintas cabang: navbar "Semua Speedshop" / session kosong
            // sebelumnya membuat activeWarehouseId() = null, lalu form Tambah Produk
            // menyimpan warehouse_id NULL dan SKU hilang dari katalog MO.
            $fallbackId = $this->activeSpeedshopIds()[0] ?? null;
            if ($fallbackId) {
                $shop = Speedshop::query()->find((int) $fallbackId);
                $wid = $shop?->mutasi_warehouse_id ?? $shop?->warehouse_id;
                if ($wid) {
                    return (int) $wid;
                }
            }
        }

        if ($this->warehouse_id) {
            return (int) $this->warehouse_id;
        }

        if (
            $this->section === self::SECTION_MODIFIKASI_ORI
            && (
                $this->isModifikasiOriManager()
                || $this->canUploadMoProductImages()
                || in_array($this->role, AdminUserPayload::modifikasiOriWarehouseRoles(), true)
            )
        ) {
            return null;
        }

        if ($this->isOwner()) {
            return null;
        }

        return null;
    }

    public function activeSpeedshopId(): ?int
    {
        if ($this->isModifikasiOriHeadAudit() && $this->isHeadAuditSpeedshopContext()) {
            $selectedId = $this->speedshopScopeId();
            if ($selectedId) {
                return $selectedId;
            }

            return $this->activeSpeedshopIds()[0] ?? null;
        }

        if ($this->isModifikasiOriHeadWarehouse() || $this->isModifikasiOriManager()) {
            $selectedId = $this->speedshopScopeId();
            if ($selectedId) {
                return $selectedId;
            }

            return $this->activeSpeedshopIds()[0] ?? null;
        }

        if (! $this->isSpeedshop()) {
            return null;
        }

        $assigned = $this->assignedSpeedshopIds();
        if (count($assigned) > 1) {
            $selectedId = $this->speedshopScopeId();
            if ($selectedId) {
                return $selectedId;
            }

            return $assigned[0] ?? null;
        }

        if ($this->speedshop_id) {
            return (int) $this->speedshop_id;
        }

        if ($assigned !== []) {
            return $assigned[0];
        }

        $selectedId = $this->speedshopScopeId();
        if ($selectedId) {
            return $selectedId;
        }

        return $this->activeSpeedshopIds()[0] ?? null;
    }

    public function activeSpeedshopIds(): array
    {
        if ($this->isOwner()) {
            return Speedshop::query()
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($this->isModifikasiOriHeadAudit()) {
            return Speedshop::query()
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($this->isModifikasiOriHeadWarehouse() || $this->isModifikasiOriManager()) {
            return $this->managedSpeedshopIds();
        }

        if (! $this->isSpeedshop()) {
            return [];
        }

        $assigned = $this->assignedSpeedshopIds();
        if ($assigned !== []) {
            return Speedshop::resolveCanonicalOutletIds($assigned);
        }

        if ($this->isSpeedshopHeadArea()) {
            return $this->speedshop_id ? [(int) $this->speedshop_id] : [];
        }

        if ($this->speedshop_id) {
            return [(int) $this->speedshop_id];
        }

        if ($this->isSpeedshopRegionalManager()) {
            return $this->managedSpeedshopIds();
        }

        if (! $this->warehouse_id) {
            return [];
        }

        return $this->managedSpeedshopIds();
    }

    /** @return list<int> */
    public function assignedSpeedshopIds(): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('speedshop_user')) {
            return $this->speedshop_id ? [(int) $this->speedshop_id] : [];
        }

        if ($this->relationLoaded('assignedSpeedshops')) {
            return $this->assignedSpeedshops
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        return $this->assignedSpeedshops()
            ->pluck('speedshops.id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Sinkron pivot cabang + set speedshop_id (null jika multi-cabang agar switcher navbar aktif).
     *
     * @param  list<int|string>|null  $ids
     */
    public function syncAssignedSpeedshops(?array $ids): void
    {
        $ids = collect($ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (\Illuminate\Support\Facades\Schema::hasTable('speedshop_user')) {
            $this->assignedSpeedshops()->sync($ids);
        }

        $primary = count($ids) === 1 ? $ids[0] : null;
        if ((int) ($this->speedshop_id ?? 0) !== (int) ($primary ?? 0)) {
            $this->forceFill(['speedshop_id' => $primary])->saveQuietly();
        }
    }

    public function managedSpeedshopIds(): array
    {
        $query = Speedshop::query()->orderBy('id');

        if ($this->warehouse_id) {
            $warehouseId = (int) $this->warehouse_id;
            $query->where(function ($q) use ($warehouseId) {
                $q->where('mutasi_warehouse_id', $warehouseId)
                    ->orWhere('warehouse_id', $warehouseId);
            });
        }

        return Speedshop::resolveCanonicalOutletIds(
            $query
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all(),
        );
    }

    public function speedshopScopeId(): ?int
    {
        if ($this->isModifikasiOriHeadAudit() && $this->isHeadAuditSpeedshopContext()) {
            $selection = $this->headAuditScopeSelection();
            if ($selection === 'all') {
                return null;
            }

            $speedshopId = (int) $selection;

            return in_array($speedshopId, $this->activeSpeedshopIds(), true) ? $speedshopId : null;
        }

        if (! $this->canSelectSpeedshopScope()) {
            return null;
        }

        // Satu cabang tetap: ikut kolom users.speedshop_id.
        // Multi-cabang (Accounting): ikuti session agar switcher navbar bekerja.
        if ($this->speedshop_id && count($this->assignedSpeedshopIds()) <= 1) {
            return (int) $this->speedshop_id;
        }

        $scopeRaw = session('speedshop_scope_id');
        if ($scopeRaw === null || $scopeRaw === '') {
            return null; // null means "all outlet" for multi-outlet speedshop user.
        }

        $scopeId = (int) $scopeRaw;

        return in_array($scopeId, $this->activeSpeedshopIds(), true) ? $scopeId : null;
    }

    /** Apakah filter navbar memakai gabungan semua outlet (bukan satu cabang). */
    public function isSpeedshopAllScopeSelected(): bool
    {
        if ($this->speedshopScopeId() !== null || count($this->activeSpeedshopIds()) <= 1) {
            return false;
        }

        if ($this->isModifikasiOriHeadWarehouse() || $this->isModifikasiOriManager()) {
            return true;
        }

        // Navbar "Semua Speedshop" (Manager / Accounting), atau user multi-outlet tanpa speedshop_id tetap.
        return $this->isSpeedshop()
            && ($this->isManager() || $this->isSpeedshopAccounting() || ! $this->speedshop_id);
    }

    /**
     * ID speedshop untuk laporan/dashboard: satu cabang jika dipilih di navbar, atau semua outlet jika "Semua Speedshop".
     *
     * @return list<int>
     */
    public function reportSpeedshopFilterIds(): array
    {
        if ($this->isModifikasiOriHeadWarehouse() || $this->isModifikasiOriManager()) {
            $scopeId = $this->speedshopScopeId();
            if ($scopeId !== null) {
                return [$scopeId];
            }

            return $this->activeSpeedshopIds();
        }

        if (! $this->isSpeedshop()) {
            return [];
        }

        $scopeId = $this->speedshopScopeId();
        if ($scopeId !== null) {
            return [$scopeId];
        }

        if ($this->isSpeedshopAllScopeSelected()) {
            return $this->activeSpeedshopIds();
        }

        if ($this->speedshop_id) {
            return [(int) $this->speedshop_id];
        }

        return $this->activeSpeedshopIds();
    }

    public function activeWarehouse(): ?Warehouse
    {
        return null;
    }

    /** @return list<int> */
    public function warehouseLinkedSpeedshopIds(): array
    {
        $warehouseId = $this->activeWarehouseId();
        if (! $warehouseId) {
            return [];
        }

        return Speedshop::resolveCanonicalOutletIds(
            Speedshop::query()
                ->where(function ($q) use ($warehouseId) {
                    $q->where('mutasi_warehouse_id', $warehouseId)
                        ->orWhere('warehouse_id', $warehouseId);
                })
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all(),
        );
    }

    /**
     * Speedshop ID untuk modul keuangan speedshop (per cabang).
     * Warehouse accounting memakai {@see activeWarehouseId()} + scope warehouse_id, bukan speedshop terhubung.
     */
    public function activeKeuanganSpeedshopId(): ?int
    {
        if ($this->isWarehouse()) {
            return null;
        }

        return $this->activeSpeedshopId();
    }

    /**
     * @return EloquentCollection<int, User>
     */
    public static function accountingUsersForWarehouse(int $warehouseId): EloquentCollection
    {
        if ($warehouseId <= 0) {
            return new EloquentCollection;
        }

        return static::query()
            ->where('section', 'warehouse')
            ->where('role', 'Accounting')
            ->where('warehouse_id', $warehouseId)
            ->get();
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'tanggal_lahir' => 'date',
            'password' => 'hashed',
        ];
    }
}

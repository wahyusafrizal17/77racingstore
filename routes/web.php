<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\Admin\AttendanceLocationController as AdminAttendanceLocationController;
use App\Http\Controllers\Admin\AttendanceRecapController as AdminAttendanceRecapController;
use App\Http\Controllers\Admin\AttendanceScheduleController as AdminAttendanceScheduleController;
use App\Http\Controllers\Admin\BranchController as AdminBranchController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\RoleCatalogController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductStockSearchController;
use App\Http\Controllers\Speedshop\AkunOperasionalController;
use App\Http\Controllers\Speedshop\ClaimController as SpeedshopClaimController;
use App\Http\Controllers\Speedshop\JasaServisController;
use App\Http\Controllers\Speedshop\KeuanganAsetController;
use App\Http\Controllers\Speedshop\KeuanganPermintaanKasKecilController;
use App\Http\Controllers\Speedshop\KeuanganRekeningController;
use App\Http\Controllers\Speedshop\KeuanganSetoranController;
use App\Http\Controllers\Speedshop\LaporanController;
use App\Http\Controllers\Speedshop\MasterBrandController;
use App\Http\Controllers\Speedshop\MasterCategoryController;
use App\Http\Controllers\Speedshop\MasterPartLuarController as SpeedshopMasterPartLuarController;
use App\Http\Controllers\Speedshop\MasterProdukConsumableController as SpeedshopMasterProdukConsumableController;
use App\Http\Controllers\Speedshop\MasterProdukMerchandiseController as SpeedshopMasterProdukMerchandiseController;
use App\Http\Controllers\Speedshop\MasterProdukMoController as SpeedshopMasterProdukMoController;
use App\Http\Controllers\Speedshop\MasterProdukSpeedshopController as SpeedshopMasterProdukSpeedshopController;
use App\Http\Controllers\Speedshop\MekanikController;
use App\Http\Controllers\Speedshop\MutasiController as SpeedshopMutasiController;
use App\Http\Controllers\Speedshop\OwnerModalSetoranController;
use App\Http\Controllers\Speedshop\OwnerPengambilanDividenController;
use App\Http\Controllers\Speedshop\PelangganController;
use App\Http\Controllers\Speedshop\PembelianLuarController;
use App\Http\Controllers\Speedshop\PembelianMOController;
use App\Http\Controllers\Speedshop\SettingsController as SpeedshopSettingsController;
use App\Http\Controllers\Speedshop\SpeedshopDashboardController;
use App\Http\Controllers\Speedshop\SpeedshopScopeController;
use App\Http\Controllers\Speedshop\SpeedshopTargetController;
use App\Http\Controllers\Speedshop\StokInSupplierController;
use App\Http\Controllers\Speedshop\VerifikasiPengambilanDividenController;
use App\Http\Controllers\Speedshop\WorkOrderController;
use App\Http\Controllers\SupplierController;

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/management/login');

Route::get('/session/csrf', [\App\Http\Controllers\SessionCsrfController::class, 'show'])
    ->name('session.csrf');

Route::middleware(['auth'])->prefix('app-chat')->name('app-chat.')->group(function () {
    Route::get('/', [\App\Http\Controllers\AppDirectChatController::class, 'index'])->name('index');
    Route::get('/unread', [\App\Http\Controllers\AppDirectChatController::class, 'unread'])->name('unread');
    Route::post('/presence', [\App\Http\Controllers\AppDirectChatController::class, 'presence'])->name('presence');
    Route::get('/peers', [\App\Http\Controllers\AppDirectChatController::class, 'peers'])->name('peers');
    Route::post('/start', [\App\Http\Controllers\AppDirectChatController::class, 'start'])->name('start');
    Route::get('/{chat}', [\App\Http\Controllers\AppDirectChatController::class, 'show'])->name('show');
    Route::get('/{chat}/messages', [\App\Http\Controllers\AppDirectChatController::class, 'messages'])->name('messages');
    Route::post('/{chat}/messages', [\App\Http\Controllers\AppDirectChatController::class, 'store'])->name('store');
});

/** PDF invoice A4 sementara (signed) — diunduh server WACLOUD saat kirim WhatsApp. */
Route::get('/s/invoice/{service_order}/a4.pdf', [WorkOrderController::class, 'invoicePdfA4Signed'])
    ->middleware('signed')
    ->name('speedshop.invoice.a4.signed');


Route::redirect('/login', '/management/login');

Route::prefix('management')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Notifications & global product stock search (shared across all sections)
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-summary', [NotificationController::class, 'unreadSummary']);
    Route::post('/notifications/{appNotification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::get('/product-stock-search', ProductStockSearchController::class)->name('product-stock-search');

    Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi.index');
    Route::post('/absensi', [AbsensiController::class, 'store'])->name('absensi.store');
    Route::get('/absensi/validasi', [\App\Http\Controllers\AbsensiValidationController::class, 'index'])->name('absensi.validasi.index');
    Route::post('/absensi/validasi/approve-hari', [\App\Http\Controllers\AbsensiValidationController::class, 'approveDay'])->name('absensi.validasi.approve-hari');
    Route::post('/absensi/validasi/reject-hari', [\App\Http\Controllers\AbsensiValidationController::class, 'rejectDay'])->name('absensi.validasi.reject-hari');
    Route::post('/absensi/validasi/{attendanceRecord}/approve', [\App\Http\Controllers\AbsensiValidationController::class, 'approve'])->name('absensi.validasi.approve');
    Route::post('/absensi/validasi/{attendanceRecord}/reject', [\App\Http\Controllers\AbsensiValidationController::class, 'reject'])->name('absensi.validasi.reject');
    Route::get('/absensi/rekap', [\App\Http\Controllers\AbsensiRecapController::class, 'index'])->name('absensi.rekap.index');
    Route::get('/absensi/rekap/export-excel', [\App\Http\Controllers\AbsensiRecapController::class, 'exportExcel'])->name('absensi.rekap.export-excel');
    Route::get('/absensi/rekap/export-pdf', [\App\Http\Controllers\AbsensiRecapController::class, 'exportPdf'])->name('absensi.rekap.export-pdf');

    Route::get('/izin/verify/{token}', \App\Http\Controllers\LeaveVerifyController::class)->name('izin.verify');
    Route::get('/izin', [\App\Http\Controllers\LeaveRequestController::class, 'index'])->name('izin.index');
    Route::post('/izin', [\App\Http\Controllers\LeaveRequestController::class, 'store'])->name('izin.store');
    Route::get('/izin/validasi', [\App\Http\Controllers\LeaveValidationController::class, 'index'])->name('izin.validasi.index');
    Route::post('/izin/validasi/{leaveRequest}/approve', [\App\Http\Controllers\LeaveValidationController::class, 'approve'])->name('izin.validasi.approve');
    Route::post('/izin/validasi/{leaveRequest}/reject', [\App\Http\Controllers\LeaveValidationController::class, 'reject'])->name('izin.validasi.reject');
    Route::get('/izin/{leaveRequest}', [\App\Http\Controllers\LeaveRequestController::class, 'show'])->name('izin.show');
    Route::post('/izin/{leaveRequest}/sign', [\App\Http\Controllers\LeaveRequestController::class, 'sign'])->name('izin.sign');
    Route::delete('/izin/{leaveRequest}', [\App\Http\Controllers\LeaveRequestController::class, 'cancel'])->name('izin.cancel');

    Route::get('/profil', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profil.index');
    Route::put('/profil', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profil.update');
    Route::post('/profil/foto', [\App\Http\Controllers\ProfileController::class, 'updateFoto'])->name('profil.foto');
    Route::delete('/profil/foto', [\App\Http\Controllers\ProfileController::class, 'hapusFoto'])->name('profil.foto.hapus');
});

// Panel HR yang menempel di sidebar Manager Speedshop.
Route::middleware(['auth', 'admin.panel'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('branches', AdminBranchController::class)->parameters(['branches' => 'speedshop'])->except(['create', 'edit']);
    Route::get('attendance-locations', [AdminAttendanceLocationController::class, 'index'])->name('attendance-locations.index');
    Route::post('attendance-locations/sync', [AdminAttendanceLocationController::class, 'sync'])->name('attendance-locations.sync');
    Route::put('attendance-locations/{attendanceLocation}', [AdminAttendanceLocationController::class, 'update'])->name('attendance-locations.update');
    Route::get('attendance-schedule', [AdminAttendanceScheduleController::class, 'index'])->name('attendance-schedule.index');
    Route::get('attendance-schedule/holidays', [AdminAttendanceScheduleController::class, 'holidays'])->name('attendance-schedule.holidays');
    Route::get('attendance-schedule/work-hours', [AdminAttendanceScheduleController::class, 'workHours'])->name('attendance-schedule.work-hours');
    Route::put('attendance-schedule/locations/{attendanceLocation}', [AdminAttendanceScheduleController::class, 'updateLocation'])->name('attendance-schedule.locations.update');
    Route::put('attendance-schedule', [AdminAttendanceScheduleController::class, 'update'])->name('attendance-schedule.update');
    Route::post('attendance-schedule/holidays', [AdminAttendanceScheduleController::class, 'storeHoliday'])->name('attendance-schedule.holidays.store');
    Route::delete('attendance-schedule/holidays/{attendanceHoliday}', [AdminAttendanceScheduleController::class, 'destroyHoliday'])->name('attendance-schedule.holidays.destroy');
    Route::get('attendance-validators', [\App\Http\Controllers\Admin\AttendanceValidatorController::class, 'index'])->name('attendance-validators.index');
    Route::put('attendance-validators', [\App\Http\Controllers\Admin\AttendanceValidatorController::class, 'update'])->name('attendance-validators.update');
    Route::get('attendance-recap', [AdminAttendanceRecapController::class, 'index'])->name('attendance-recap.index');
    Route::get('roles/export', [RoleCatalogController::class, 'export'])->name('roles.export');
    Route::get('roles/pdf', [RoleCatalogController::class, 'exportPdf'])->name('roles.pdf');
    Route::get('roles', [RoleCatalogController::class, 'index'])->name('roles.index');
    Route::get('users/export', [UserManagementController::class, 'export'])->name('users.export');
    Route::get('users/pdf', [UserManagementController::class, 'exportPdf'])->name('users.pdf');
    Route::get('users/template', [UserManagementController::class, 'template'])->name('users.template');
    Route::post('users/import', [UserManagementController::class, 'import'])->name('users.import');
    Route::resource('users', UserManagementController::class)->except(['create', 'edit']);
});

// ============================================================
// SPEEDSHOP SECTION (Manager & Outlet Speedshop)
// ============================================================
Route::middleware(['auth', 'section:speedshop', 'head-audit.finance-readonly', 'speedshop.no-product-master', 'speedshop.head-area'])->prefix('speedshop')->name('speedshop.')->group(function () {
    Route::post('/scope', [SpeedshopScopeController::class, 'update'])->name('scope.update');

    Route::get('/keuangan/setoran-modal', [OwnerModalSetoranController::class, 'index'])->name('keuangan.setoran-modal.index');
    Route::post('/keuangan/setoran-modal', [OwnerModalSetoranController::class, 'store'])->name('keuangan.setoran-modal.store');
    Route::get('/keuangan/setoran-modal/saldos', [OwnerModalSetoranController::class, 'saldosPreview'])->name('keuangan.setoran-modal.saldos');
    Route::get('/keuangan/setoran-modal/speedshops/{speedshop}/rekenings', [OwnerModalSetoranController::class, 'rekeningsForSpeedshop'])->name('keuangan.setoran-modal.rekenings');

    Route::get('/keuangan/pengambilan-dividen', [OwnerPengambilanDividenController::class, 'index'])->name('keuangan.pengambilan-dividen.index');
    Route::post('/keuangan/pengambilan-dividen', [OwnerPengambilanDividenController::class, 'store'])->name('keuangan.pengambilan-dividen.store');
    Route::get('/keuangan/pengambilan-dividen/saldos', [OwnerPengambilanDividenController::class, 'saldosPreview'])->name('keuangan.pengambilan-dividen.saldos');
    Route::get('/keuangan/pengambilan-dividen/speedshops/{speedshop}/rekenings', [OwnerPengambilanDividenController::class, 'rekeningsForSpeedshop'])->name('keuangan.pengambilan-dividen.rekenings');

    Route::middleware('role:Manager,Outlet,Accounting')->group(function () {
        Route::get('/dashboard', [SpeedshopDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/omset-detail', [SpeedshopDashboardController::class, 'omsetDetail'])->name('dashboard.omset-detail');
        Route::get('/dashboard/export-excel', [SpeedshopDashboardController::class, 'exportExcel'])->name('dashboard.export-excel');
        Route::get('/dashboard/export-excel-detail', [SpeedshopDashboardController::class, 'exportExcelDetail'])->name('dashboard.export-excel-detail');
        Route::get('/dashboard/export-pdf', [SpeedshopDashboardController::class, 'exportPdf'])->name('dashboard.export-pdf');
    });

    Route::middleware('role:Manager')->group(function () {
        Route::get('/targets', [SpeedshopTargetController::class, 'index'])->name('targets.index');
        Route::put('/targets', [SpeedshopTargetController::class, 'update'])->name('targets.update');
        Route::get('/settings', [SpeedshopSettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SpeedshopSettingsController::class, 'update'])->name('settings.update');
        Route::put('/settings/stok-audit', [SpeedshopSettingsController::class, 'updateStokAudit'])->name('settings.stok-audit.update');
        Route::get('/verifikasi/diskon-work-order', [WorkOrderController::class, 'selesaikanIndexDiskonVerifikasi'])->name('verifikasi.diskon-work-order.index');
        Route::get('/verifikasi/diskon-work-order/{service_order}', [WorkOrderController::class, 'diskonVerifikasiShow'])->name('verifikasi.diskon-work-order.show');
        Route::post('/verifikasi/diskon-work-order/{service_order}/approve', [WorkOrderController::class, 'selesaikanApprove'])->name('verifikasi.diskon-work-order.approve');
        Route::post('/verifikasi/diskon-work-order/{service_order}/reject', [WorkOrderController::class, 'selesaikanReject'])->name('verifikasi.diskon-work-order.reject');
    });

    Route::middleware('role:Manager,Accounting')->group(function () {
        Route::get('/verifikasi/pembelian-luar', [PembelianLuarController::class, 'indexVerifikasiPending'])->name('verifikasi.pembelian-luar.index');
        Route::get('/verifikasi/pembelian-luar/{warehouse_transaksi}', [PembelianLuarController::class, 'pembelianLuarVerifikasiShow'])
            ->whereNumber('warehouse_transaksi')
            ->name('verifikasi.pembelian-luar.show');
    });

    Route::middleware('role:Manager,Outlet,Accounting')->group(function () {
        Route::get('/stock-in/supplier/{warehouse_transaksi}/pdf', [StokInSupplierController::class, 'pdf'])->name('stock-in.supplier.pdf');
    });

    Route::middleware('role:Manager,Outlet')->group(function () {
        Route::middleware('speedshop.setoran')->group(function () {
            Route::post('work-orders/{service_order}/start', [WorkOrderController::class, 'start'])->name('work-orders.start');
            Route::post('work-orders/{service_order}/complete', [WorkOrderController::class, 'complete'])->name('work-orders.complete');
            Route::get('work-orders/products/search', [WorkOrderController::class, 'searchSparepartProducts'])->name('work-orders.products.search');
            Route::resource('work-orders', WorkOrderController::class)->only(['create', 'store', 'edit', 'update', 'destroy'])->parameters(['work_order' => 'service_order']);
            Route::post('/selesaikan-transaksi/{service_order}', [WorkOrderController::class, 'selesaikanStore'])->name('selesaikan-transaksi.store');
            Route::post('/selesaikan-transaksi/{service_order}/approve', [WorkOrderController::class, 'selesaikanApprove'])->name('selesaikan-transaksi.approve');
            Route::post('/selesaikan-transaksi/{service_order}/reject', [WorkOrderController::class, 'selesaikanReject'])->name('selesaikan-transaksi.reject');
            Route::post('/selesaikan-transaksi/{service_order}/sparepart-stock', [WorkOrderController::class, 'selesaikanSparepartStock'])->name('selesaikan-transaksi.sparepart-stock');
            Route::post('/selesaikan-transaksi/{service_order}/jasa-options', [WorkOrderController::class, 'selesaikanJasaOptions'])->name('selesaikan-transaksi.jasa-options');
        });
        Route::get('/transaksi', fn () => redirect()->route('speedshop.dashboard'))->name('transaksi');
        Route::get('/transaksi/create', fn () => redirect()->route('speedshop.work-orders.create'));
        Route::get('/mutasi', [SpeedshopMutasiController::class, 'index'])->name('mutasi.index');
        Route::get('/mutasi/create', [SpeedshopMutasiController::class, 'create'])->name('mutasi.create');
        Route::post('/mutasi', [SpeedshopMutasiController::class, 'store'])->name('mutasi.store');
        Route::get('/mutasi/{mutasi}', [SpeedshopMutasiController::class, 'show'])->name('mutasi.show');
        Route::get('/mutasi/{mutasi}/pdf', [SpeedshopMutasiController::class, 'pdf'])->name('mutasi.pdf');
        Route::post('/mutasi/{mutasi}/verify', [SpeedshopMutasiController::class, 'verify'])->name('mutasi.verify');
        Route::post('/mutasi/{mutasi}/reject', [SpeedshopMutasiController::class, 'reject'])->name('mutasi.reject');
        Route::get('/wip', [WorkOrderController::class, 'index'])->name('wip');
        Route::get('/wip/{service_order}', [WorkOrderController::class, 'show'])->name('wip.show');
        Route::post('/wip/{service_order}/items', [WorkOrderController::class, 'wipUpdateItems'])->name('wip.items.update');
        Route::post('/wip/{service_order}/cancel-request', [WorkOrderController::class, 'requestCancelWorkOrder'])->name('wip.cancel.request');
        Route::get('/monitor-tv/antrian', [WorkOrderController::class, 'monitorTvAntrian'])->name('monitor-tv.antrian');
        Route::get('/monitor-tv/antrian/data', [WorkOrderController::class, 'monitorTvAntrianData'])->name('monitor-tv.antrian.data');
        Route::post('/monitor-tv/antrian/tts', [WorkOrderController::class, 'monitorTvAntrianTts'])
            ->middleware('throttle:24,1')
            ->name('monitor-tv.antrian.tts');
        Route::get('/work-orders/{service_order}/invoice', [WorkOrderController::class, 'invoice'])->name('work-orders.invoice');
        Route::get('/work-orders/{service_order}/invoice/thermal', [WorkOrderController::class, 'invoiceThermal'])->name('work-orders.invoice.thermal');
        Route::get('/work-orders/{service_order}/sheet/a4', [WorkOrderController::class, 'workOrderSheetA4Html'])->name('work-orders.sheet.a4');
        // Backward-compat: URL lama "pdf-a4" diarahkan ke HTML (lebih cepat).
        Route::get('/work-orders/{service_order}/sheet/pdf-a4', [WorkOrderController::class, 'workOrderSheetA4Html'])->name('work-orders.sheet.pdf-a4');
        // PDF asli (kalau sewaktu-waktu tetap dibutuhkan)
        Route::get('/work-orders/{service_order}/sheet/pdf-a4/download', [WorkOrderController::class, 'workOrderSheetPdfA4'])->name('work-orders.sheet.pdf-a4.download');
        Route::get('/work-orders/{service_order}/invoice/pdf-a4', [WorkOrderController::class, 'invoicePdfA4'])->name('work-orders.invoice.pdf-a4');
        Route::post('/work-orders/{service_order}/invoice/whatsapp', [WorkOrderController::class, 'sendInvoiceWhatsApp'])->name('work-orders.invoice.whatsapp');
        Route::get('/selesaikan-transaksi', [WorkOrderController::class, 'selesaikanIndex'])->name('selesaikan-transaksi.index');
        Route::get('/selesaikan-transaksi/{service_order}', [WorkOrderController::class, 'selesaikanShow'])->name('selesaikan-transaksi.show');
        Route::post('/selesaikan-transaksi/{service_order}/panggil-antrian', [WorkOrderController::class, 'selesaikanPanggilAntrianMonitor'])
            ->middleware('throttle:30,1')
            ->name('selesaikan-transaksi.panggil-antrian');
        Route::get('/history', [WorkOrderController::class, 'history'])->name('history');
        Route::get('/service-record', [WorkOrderController::class, 'serviceRecord'])->name('service-record');
        Route::get('/service-record/{pelanggan}/pdf', [WorkOrderController::class, 'serviceRecordPdf'])->name('service-record.pdf');
        Route::get('/mekanik-record', [WorkOrderController::class, 'mekanikRecord'])->name('mekanik-record');
        Route::get('/estimasi', [WorkOrderController::class, 'estimasi'])->name('estimasi');
        Route::get('/stock-in', fn () => redirect()->route('speedshop.stock-in.pembelian-mo.index'))->name('stock-in');
        Route::get('/stock-in/pembelian-mo', [PembelianMOController::class, 'index'])->name('stock-in.pembelian-mo.index');
        Route::get('/stock-in/pembelian-mo/create', [PembelianMOController::class, 'create'])->name('stock-in.pembelian-mo.create');
        Route::get('/stock-in/pembelian-mo/lookup', [PembelianMOController::class, 'lookup'])->name('stock-in.pembelian-mo.lookup');
        Route::post('/stock-in/pembelian-mo/purchase-order', [PembelianMOController::class, 'storePurchaseOrder'])->name('stock-in.pembelian-mo.purchase-order.store');
        Route::get('/stock-in/pembelian-mo/purchase-order/{purchaseOrder}', [PembelianMOController::class, 'showPurchaseOrder'])->name('stock-in.pembelian-mo.purchase-order.show');
        Route::get('/stock-in/pembelian-mo/mutasi/{mutasi}', [PembelianMOController::class, 'showMutasi'])->name('stock-in.pembelian-mo.show-mutasi');
        Route::post('/stock-in/pembelian-mo/mutasi/{mutasi}/verify', [PembelianMOController::class, 'verifyMutasi'])->name('stock-in.pembelian-mo.verify-mutasi');
        Route::post('/stock-in/pembelian-mo/mutasi/{mutasi}/reject', [PembelianMOController::class, 'rejectMutasi'])->name('stock-in.pembelian-mo.reject-mutasi');
        Route::get('/stock-in/transaksi-offline', [PembelianMOController::class, 'indexTransaksiOffline'])->name('stock-in.transaksi-offline.index');
        Route::get('/stock-in/transaksi-offline/{transaksiOffline}/pdf', [PembelianMOController::class, 'pdfTransaksiOffline'])->name('stock-in.transaksi-offline.pdf');
        Route::get('/stock-in/transaksi-offline/{transaksiOffline}', [PembelianMOController::class, 'showTransaksiOffline'])->name('stock-in.transaksi-offline.show');
        Route::post('/stock-in/transaksi-offline/{transaksiOffline}/verify', [PembelianMOController::class, 'verifyTransaksiOffline'])->name('stock-in.transaksi-offline.verify');
        Route::post('/stock-in/transaksi-offline/{transaksiOffline}/reject', [PembelianMOController::class, 'rejectTransaksiOffline'])->name('stock-in.transaksi-offline.reject');
        Route::get('/stock-in/pembelian-luar', [PembelianLuarController::class, 'index'])->name('stock-in.pembelian-luar.index');
        Route::get('/stock-in/pembelian-luar/create', [PembelianLuarController::class, 'create'])->name('stock-in.pembelian-luar.create');
        Route::post('/stock-in/pembelian-luar', [PembelianLuarController::class, 'store'])->name('stock-in.pembelian-luar.store');
        Route::get('/stock-in/pembelian-luar/{warehouse_transaksi}/pdf', [PembelianLuarController::class, 'pdf'])
            ->whereNumber('warehouse_transaksi')
            ->name('stock-in.pembelian-luar.pdf');
        Route::get('/stock-in/pembelian-luar/{warehouse_transaksi}', [PembelianLuarController::class, 'show'])
            ->whereNumber('warehouse_transaksi')
            ->name('stock-in.pembelian-luar.show');

        // Stock IN Supplier (verifikasi Accounting)
        Route::get('/stock-in/supplier', [StokInSupplierController::class, 'index'])->name('stock-in.supplier.index');
        Route::get('/stock-in/supplier/create', [StokInSupplierController::class, 'create'])->name('stock-in.supplier.create');
        Route::post('/stock-in/supplier/products/quick-store', [StokInSupplierController::class, 'storeProductQuick'])->name('stock-in.supplier.products.quick-store');
        Route::post('/stock-in/supplier', [StokInSupplierController::class, 'store'])->name('stock-in.supplier.store');
        Route::get('/stock-in/supplier/{warehouse_transaksi}', [StokInSupplierController::class, 'show'])->name('stock-in.supplier.show');
        Route::get('/claim', [SpeedshopClaimController::class, 'index'])->name('claim.index');
        Route::get('/claim/create', [SpeedshopClaimController::class, 'create'])->name('claim.create');
        Route::post('/claim', [SpeedshopClaimController::class, 'store'])->name('claim.store');
        Route::get('/claim/{speedshop_claim}', [SpeedshopClaimController::class, 'show'])->name('claim.show');
        // Master CRUD produk Speedshop dihapus; lihat stok & produk di Inventori > Produk. Tetap sediakan histori stok untuk modal inventori.
        Route::get('products/{product}/stock-history', [ProductController::class, 'stockHistory'])->name('products.stock-history');
        Route::get('stok-produk', fn () => redirect()->route('speedshop.stok-produk-mo'))->name('stok-produk');

        $registerStokProdukRoutes = function (string $uri, string $name): void {
            Route::get($uri, [ProductController::class, 'stokProdukSpeedshop'])->name($name);
            Route::get("{$uri}/audit", [ProductController::class, 'stokProdukAudit'])->name("{$name}.audit");
            Route::post("{$uri}/audit", [ProductController::class, 'stokProdukAuditApply'])->name("{$name}.audit.apply");
            Route::get("{$uri}/export", [ProductController::class, 'exportSpeedshopStock'])->name("{$name}.export");
            Route::get("{$uri}/pdf", [ProductController::class, 'exportSpeedshopStockPdf'])->name("{$name}.pdf");
            Route::get("{$uri}/template", [ProductController::class, 'templateSpeedshopStock'])->name("{$name}.template");
            Route::post("{$uri}/import", [ProductController::class, 'importSpeedshopStock'])->name("{$name}.import");
            Route::put("{$uri}/{product}/update-stock", [ProductController::class, 'updateSpeedshopInventoriStock'])->name("{$name}.update-stock");
        };

        $registerStokProdukRoutes('stok-produk-mo', 'stok-produk-mo');
        $registerStokProdukRoutes('stok-produk-speedshop', 'stok-produk-speedshop');
        $registerStokProdukRoutes('stok-part-luar', 'stok-part-luar');
        $registerStokProdukRoutes('stok-merchandise', 'stok-merchandise');
        $registerStokProdukRoutes('stok-consumable', 'stok-consumable');

        // Master Data: Level Mekanik (Manager only)
        Route::middleware('role:Manager')->group(function () {
            Route::get('/mekanik-levels', [\App\Http\Controllers\Speedshop\MekanikLevelController::class, 'index'])->name('mekanik-levels.index');
            Route::post('/mekanik-levels', [\App\Http\Controllers\Speedshop\MekanikLevelController::class, 'store'])->name('mekanik-levels.store');
            Route::get('/mekanik-levels/{mekanik_level}', [\App\Http\Controllers\Speedshop\MekanikLevelController::class, 'show'])->name('mekanik-levels.show');
            Route::put('/mekanik-levels/{mekanik_level}', [\App\Http\Controllers\Speedshop\MekanikLevelController::class, 'update'])->name('mekanik-levels.update');
            Route::delete('/mekanik-levels/{mekanik_level}', [\App\Http\Controllers\Speedshop\MekanikLevelController::class, 'destroy'])->name('mekanik-levels.destroy');
        });

        // (dipindah) Keuangan > Rekening: akses khusus Manager/Accounting
    });

    // Master Data operasional: Manager, Outlet, dan Accounting (sidebar Master Data)
    Route::middleware('role:Manager,Outlet,Accounting')->group(function () {
        Route::post('pelanggans/quick-add', [PelangganController::class, 'quickAdd'])->name('pelanggans.quick-add');
        Route::post('kendaraans/quick-add', [PelangganController::class, 'quickAddKendaraan'])->name('kendaraans.quick-add');
        Route::resource('pelanggans', PelangganController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        Route::get('/pelanggans/export', [PelangganController::class, 'export'])->name('pelanggans.export');
        Route::get('/pelanggans/pdf', [PelangganController::class, 'exportPdf'])->name('pelanggans.pdf');
        Route::get('/pelanggans/template', [PelangganController::class, 'template'])->name('pelanggans.template');
        Route::post('/pelanggans/import', [PelangganController::class, 'import'])->name('pelanggans.import');
        Route::get('/suppliers/export', [SupplierController::class, 'export'])->name('suppliers.export');
        Route::get('/suppliers/template', [SupplierController::class, 'template'])->name('suppliers.template');
        Route::post('/suppliers/import', [SupplierController::class, 'import'])->name('suppliers.import');
        Route::get('/suppliers/pdf', [SupplierController::class, 'exportPdf'])->name('suppliers.pdf');
        Route::get('/suppliers', [SupplierController::class, 'indexForSpeedshop'])->name('suppliers.index');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->name('suppliers.show');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
        Route::get('/jasa-servis/export', [JasaServisController::class, 'export'])->name('jasa-servis.export');
        Route::get('/jasa-servis/pdf', [JasaServisController::class, 'exportPdf'])->name('jasa-servis.pdf');
        Route::get('/jasa-servis/template', [JasaServisController::class, 'template'])->name('jasa-servis.template');
        Route::post('/jasa-servis/import', [JasaServisController::class, 'import'])->name('jasa-servis.import');
        Route::resource('jasa-servis', JasaServisController::class)->except(['create', 'edit']);
        Route::get('/mekaniks/export', [MekanikController::class, 'export'])->name('mekaniks.export');
        Route::get('/mekaniks/pdf', [MekanikController::class, 'exportPdf'])->name('mekaniks.pdf');
        Route::get('/mekaniks/template', [MekanikController::class, 'template'])->name('mekaniks.template');
        Route::post('/mekaniks/import', [MekanikController::class, 'import'])->name('mekaniks.import');
        Route::resource('mekaniks', MekanikController::class)->except(['create', 'edit']);
    });

    Route::middleware('role:Manager,Accounting')->group(function () {
        Route::get('/keuangan/rekening', [KeuanganRekeningController::class, 'index'])->name('keuangan.rekening');
        Route::get('/keuangan/rekening/create', [KeuanganRekeningController::class, 'create'])->name('keuangan.rekening.create');
        Route::post('/keuangan/rekening', [KeuanganRekeningController::class, 'store'])->name('keuangan.rekening.store');
        Route::get('/keuangan/rekening/{rekening}/edit', [KeuanganRekeningController::class, 'edit'])->name('keuangan.rekening.edit');
        Route::put('/keuangan/rekening/{rekening}', [KeuanganRekeningController::class, 'update'])->name('keuangan.rekening.update');
        Route::delete('/keuangan/rekening/{rekening}', [KeuanganRekeningController::class, 'destroy'])->name('keuangan.rekening.destroy');
        Route::post('/keuangan/rekening/{rekening}/saldo-buku', [KeuanganRekeningController::class, 'adjustSaldoBuku'])->middleware('role:Accounting')->name('keuangan.rekening.saldo-buku');
        Route::post('/keuangan/modal-laci', [KeuanganRekeningController::class, 'updateModal'])->middleware('role:Accounting')->name('keuangan.modal-laci');

        Route::get('/keuangan/akun-operasional', [AkunOperasionalController::class, 'index'])->name('keuangan.akun-operasional.index');
        Route::post('/keuangan/akun-operasional', [AkunOperasionalController::class, 'store'])->name('keuangan.akun-operasional.store');
        Route::get('/keuangan/akun-operasional/{akun_operasional}', [AkunOperasionalController::class, 'show'])->name('keuangan.akun-operasional.show');
        Route::put('/keuangan/akun-operasional/{akun_operasional}', [AkunOperasionalController::class, 'update'])->name('keuangan.akun-operasional.update');
        Route::delete('/keuangan/akun-operasional/{akun_operasional}', [AkunOperasionalController::class, 'destroy'])->name('keuangan.akun-operasional.destroy');

        Route::get('/keuangan/aset', [KeuanganAsetController::class, 'index'])->name('keuangan.aset.index');
        Route::post('/keuangan/aset', [KeuanganAsetController::class, 'store'])->name('keuangan.aset.store');
        Route::get('/keuangan/aset/{aset}', [KeuanganAsetController::class, 'show'])->name('keuangan.aset.show');
        Route::put('/keuangan/aset/{aset}', [KeuanganAsetController::class, 'update'])->name('keuangan.aset.update');
        Route::delete('/keuangan/aset/{aset}', [KeuanganAsetController::class, 'destroy'])->name('keuangan.aset.destroy');

        Route::get('/master/akun-operasional', fn () => redirect()->route('speedshop.keuangan.akun-operasional.index', [], 301));
    });

    Route::middleware('role:Manager,Outlet,Staf,Accounting')->group(function () {
        Route::get('/keuangan/permintaan-kas-kecil', [KeuanganPermintaanKasKecilController::class, 'index'])->name('keuangan.permintaan-kas-kecil.index');
        Route::get('/keuangan/permintaan-kas-kecil/export-excel', [KeuanganPermintaanKasKecilController::class, 'exportExcel'])->name('keuangan.permintaan-kas-kecil.export-excel');
        Route::get('/keuangan/permintaan-kas-kecil/export-pdf', [KeuanganPermintaanKasKecilController::class, 'exportPdf'])->name('keuangan.permintaan-kas-kecil.export-pdf');
        Route::post('/keuangan/permintaan-kas-kecil', [KeuanganPermintaanKasKecilController::class, 'store'])->middleware('role:Manager,Outlet,Staf')->name('keuangan.permintaan-kas-kecil.store');
        Route::post('/keuangan/permintaan-kas-kecil/{permintaan}/approve', [KeuanganPermintaanKasKecilController::class, 'approve'])->middleware('role:Accounting')->name('keuangan.permintaan-kas-kecil.approve');
        Route::post('/keuangan/permintaan-kas-kecil/{permintaan}/reject', [KeuanganPermintaanKasKecilController::class, 'reject'])->middleware('role:Accounting')->name('keuangan.permintaan-kas-kecil.reject');
    });

    Route::middleware('role:Manager,Outlet,Accounting')->group(function () {
        Route::get('/keuangan/setoran', [KeuanganSetoranController::class, 'index'])->name('keuangan.setoran.index');
        Route::get('/keuangan/setoran/create', [KeuanganSetoranController::class, 'create'])->name('keuangan.setoran.create');
        Route::get('/keuangan/setoran/snapshot', [KeuanganSetoranController::class, 'snapshot'])->name('keuangan.setoran.snapshot');
        Route::get('/keuangan/setoran/export-excel', [KeuanganSetoranController::class, 'exportExcel'])->name('keuangan.setoran.export-excel');
        Route::get('/keuangan/setoran/export-pdf', [KeuanganSetoranController::class, 'exportPdf'])->name('keuangan.setoran.export-pdf');
        Route::get('/keuangan/setoran/{setoran}', [KeuanganSetoranController::class, 'show'])->name('keuangan.setoran.show');
        Route::post('/keuangan/setoran', [KeuanganSetoranController::class, 'store'])->name('keuangan.setoran.store');
        Route::post('/keuangan/setoran/{setoran}/approve', [KeuanganSetoranController::class, 'approve'])->middleware('role:Accounting')->name('keuangan.setoran.approve');
        Route::post('/keuangan/setoran/{setoran}/reject', [KeuanganSetoranController::class, 'reject'])->middleware('role:Accounting')->name('keuangan.setoran.reject');
    });

    Route::middleware('role:Manager,Accounting')->group(function () {
        Route::get('/keuangan/tagihan', [\App\Http\Controllers\Speedshop\KeuanganTagihanPoController::class, 'index'])->name('keuangan.tagihan.index');
        Route::get('/keuangan/tagihan/export-excel', [\App\Http\Controllers\Speedshop\KeuanganTagihanPoController::class, 'exportExcel'])->name('keuangan.tagihan.export-excel');
        Route::get('/keuangan/tagihan/export-pdf', [\App\Http\Controllers\Speedshop\KeuanganTagihanPoController::class, 'exportPdf'])->name('keuangan.tagihan.export-pdf');
        Route::get('/keuangan/tagihan/{tagihan}', [\App\Http\Controllers\Speedshop\KeuanganTagihanPoController::class, 'show'])->name('keuangan.tagihan.show');
        Route::post('/keuangan/tagihan/{tagihan}/bayar', [\App\Http\Controllers\Speedshop\KeuanganTagihanPoController::class, 'markPaid'])->name('keuangan.tagihan.bayar');
    });

    // Keuangan: Biaya Operasional (pindahan dari Laporan > Rincian Biaya)
    Route::middleware('role:Manager,Outlet,Staf,FLP,Accounting')->group(function () {
        Route::get('/keuangan/biaya-operasional', [LaporanController::class, 'rincianBiaya'])->name('keuangan.biaya-operasional');
        Route::get('/keuangan/biaya-operasional/export', [LaporanController::class, 'exportRincianBiaya'])->name('keuangan.biaya-operasional.export');
        Route::get('/keuangan/biaya-operasional/export-pdf', [LaporanController::class, 'exportRincianBiayaPdf'])->name('keuangan.biaya-operasional.export-pdf');
        Route::post('/keuangan/biaya-operasional/{entry}/audit-temuan', [LaporanController::class, 'updateRincianBiayaAuditTemuan'])
            ->name('keuangan.biaya-operasional.audit-temuan');
    });
    Route::middleware('role:Manager,Outlet,Staf,FLP,Accounting')->group(function () {
        Route::post('/keuangan/biaya-operasional', [LaporanController::class, 'storeRincianBiaya'])->name('keuangan.biaya-operasional.store');
        Route::delete('/keuangan/biaya-operasional/{entry}', [LaporanController::class, 'destroyRincianBiaya'])->name('keuangan.biaya-operasional.destroy');
    });

    Route::middleware('role:Accounting')->group(function () {
        Route::get('/keuangan/hutang', [\App\Http\Controllers\Speedshop\KeuanganHutangController::class, 'index'])->name('keuangan.hutang.index');
        Route::get('/keuangan/hutang/supplier/{supplier}', [\App\Http\Controllers\Speedshop\KeuanganHutangController::class, 'showSupplier'])
            ->whereNumber('supplier')
            ->name('keuangan.hutang.supplier');
        Route::get('/keuangan/hutang/{tagihan}', [\App\Http\Controllers\Speedshop\KeuanganHutangController::class, 'show'])
            ->whereNumber('tagihan')
            ->name('keuangan.hutang.show');
        Route::post('/keuangan/hutang/{tagihan}/bayar', [\App\Http\Controllers\Speedshop\KeuanganHutangController::class, 'bayar'])
            ->whereNumber('tagihan')
            ->name('keuangan.hutang.bayar');

        Route::get('/keuangan/bop-pengembalian', [\App\Http\Controllers\Speedshop\KeuanganBopPengembalianController::class, 'index'])->name('keuangan.bop-pengembalian.index');
        Route::get('/keuangan/bop-pengembalian/export-excel', [\App\Http\Controllers\Speedshop\KeuanganBopPengembalianController::class, 'exportExcel'])->name('keuangan.bop-pengembalian.export-excel');
        Route::get('/keuangan/bop-pengembalian/export-pdf', [\App\Http\Controllers\Speedshop\KeuanganBopPengembalianController::class, 'exportPdf'])->name('keuangan.bop-pengembalian.export-pdf');
        Route::get('/keuangan/bop-pengembalian/entries', [\App\Http\Controllers\Speedshop\KeuanganBopPengembalianController::class, 'entries'])->name('keuangan.bop-pengembalian.entries');
        Route::post('/keuangan/bop-pengembalian', [\App\Http\Controllers\Speedshop\KeuanganBopPengembalianController::class, 'store'])->name('keuangan.bop-pengembalian.store');
    });

    // Verifikasi (Accounting): setoran, permintaan kas, stok in supplier, batal WO
    Route::middleware('role:Accounting')->group(function () {
        Route::get('/verifikasi/setoran', [KeuanganSetoranController::class, 'index'])->name('verifikasi.setoran.index');
        Route::get('/verifikasi/setoran/{setoran}', [KeuanganSetoranController::class, 'show'])->name('verifikasi.setoran.show');
        Route::get('/verifikasi/permintaan-kas-kecil', [KeuanganPermintaanKasKecilController::class, 'index'])->name('verifikasi.permintaan-kas-kecil.index');
        Route::get('/verifikasi/pengambilan-dividen', [VerifikasiPengambilanDividenController::class, 'index'])->name('verifikasi.pengambilan-dividen.index');
        Route::get('/verifikasi/pengambilan-dividen/{pengambilan}', [VerifikasiPengambilanDividenController::class, 'show'])->name('verifikasi.pengambilan-dividen.show');
        Route::post('/verifikasi/pengambilan-dividen/{pengambilan}/approve', [VerifikasiPengambilanDividenController::class, 'approve'])->name('verifikasi.pengambilan-dividen.approve');
        Route::post('/verifikasi/pengambilan-dividen/{pengambilan}/reject', [VerifikasiPengambilanDividenController::class, 'reject'])->name('verifikasi.pengambilan-dividen.reject');
        Route::get('/verifikasi/stok-in-supplier', [StokInSupplierController::class, 'indexVerifikasiPending'])->name('verifikasi.stok-in-supplier.index');
        Route::get('/verifikasi/stok-in-supplier/{warehouse_transaksi}', [StokInSupplierController::class, 'show'])->name('verifikasi.stok-in-supplier.show');
        Route::post('/stock-in/supplier/{warehouse_transaksi}/approve', [StokInSupplierController::class, 'approve'])->name('stock-in.supplier.approve');
        Route::post('/stock-in/supplier/{warehouse_transaksi}/reject', [StokInSupplierController::class, 'reject'])->name('stock-in.supplier.reject');
        Route::get('/verifikasi/batal-work-order', [WorkOrderController::class, 'cancelVerifikasiIndex'])->name('verifikasi.batal-work-order.index');
        Route::get('/verifikasi/batal-work-order/{service_order}', [WorkOrderController::class, 'cancelVerifikasiShow'])->name('verifikasi.batal-work-order.show');
        Route::post('/wip/{service_order}/cancel-approve', [WorkOrderController::class, 'approveCancelWorkOrder'])->name('wip.cancel.approve');
        Route::post('/wip/{service_order}/cancel-reject', [WorkOrderController::class, 'rejectCancelWorkOrder'])->name('wip.cancel.reject');
    });

    Route::middleware('role:Manager,Accounting')->group(function () {
        Route::post('/stock-in/pembelian-luar/{warehouse_transaksi}/approve', [PembelianLuarController::class, 'approve'])
            ->whereNumber('warehouse_transaksi')
            ->name('stock-in.pembelian-luar.approve');
        Route::post('/stock-in/pembelian-luar/{warehouse_transaksi}/reject', [PembelianLuarController::class, 'reject'])
            ->whereNumber('warehouse_transaksi')
            ->name('stock-in.pembelian-luar.reject');
    });

    // Verifikasi biaya operasional (Kas Kecil): ≤2jt Accounting, >2jt Manager — keduanya akses daftar & aksi
    Route::middleware('role:Manager,Accounting')->group(function () {
        Route::get('/verifikasi/biaya-operasional', [LaporanController::class, 'rincianBiaya'])->name('verifikasi.biaya-operasional.index');
        Route::post('/keuangan/biaya-operasional/{entry}/approve', [LaporanController::class, 'approveRincianBiaya'])->name('keuangan.biaya-operasional.approve');
        Route::post('/keuangan/biaya-operasional/{entry}/reject', [LaporanController::class, 'rejectRincianBiaya'])->name('keuangan.biaya-operasional.reject');
    });

    Route::middleware('role:Manager,Accounting')->group(function () {
        Route::get('/laporan', fn () => redirect()->route('speedshop.laporan.biaya'))->name('laporan');
        Route::get('/laporan/biaya', [LaporanController::class, 'biaya'])->name('laporan.biaya');
        Route::get('/laporan/biaya/export', [LaporanController::class, 'exportBiaya'])->name('laporan.biaya.export');
        Route::get('/laporan/biaya/export-pdf', [LaporanController::class, 'exportBiayaPdf'])->name('laporan.biaya.export-pdf');
        Route::get('/laporan/laba-rugi', [LaporanController::class, 'labaRugi'])->name('laporan.laba-rugi');
        Route::get('/laporan/laba-rugi/export', [LaporanController::class, 'exportLabaRugi'])->name('laporan.laba-rugi.export');
        Route::get('/laporan/laba-rugi/export-pdf', [LaporanController::class, 'exportLabaRugiPdf'])->name('laporan.laba-rugi.export-pdf');
        Route::get('/laporan/summary', [LaporanController::class, 'summary'])->name('laporan.summary');
        Route::get('/laporan/summary/export', [LaporanController::class, 'exportSummary'])->name('laporan.summary.export');
        Route::get('/laporan/summary/export-pdf', [LaporanController::class, 'exportSummaryPdf'])->name('laporan.summary.export-pdf');
        Route::get('/laporan/rekap-pemisahan-dana', [LaporanController::class, 'rekapPemisahanDana'])->name('laporan.rekap-pemisahan-dana');
        Route::get('/laporan/rekap-pemisahan-dana/export', [LaporanController::class, 'exportRekapPemisahanDana'])->name('laporan.rekap-pemisahan-dana.export');
        Route::get('/laporan/rekap-pemisahan-dana/export-pdf', [LaporanController::class, 'exportRekapPemisahanDanaPdf'])->name('laporan.rekap-pemisahan-dana.export-pdf');
        Route::get('/laporan/rincian-biaya', fn (\Illuminate\Http\Request $request) => redirect()->route('speedshop.keuangan.biaya-operasional', $request->only(['dari', 'sampai', 'mode', 'tanggal', 'bulan', 'tahun'])))->name('laporan.rincian-biaya');
        Route::get('/laporan/rincian-biaya/export', fn (\Illuminate\Http\Request $request) => redirect()->route('speedshop.keuangan.biaya-operasional.export', $request->only(['dari', 'sampai', 'mode', 'tanggal', 'bulan', 'tahun'])))->name('laporan.rincian-biaya.export');
        Route::get('/laporan/penjualan-part-oli', [LaporanController::class, 'penjualanPartOli'])->name('laporan.penjualan-part-oli');
        Route::get('/laporan/penjualan-part-oli/export', [LaporanController::class, 'exportPenjualanPartOli'])->name('laporan.penjualan-part-oli.export');
        Route::get('/laporan/mekanik-performance', [LaporanController::class, 'mekanikPerformance'])->name('laporan.mekanik-performance');
        Route::get('/laporan/mekanik-performance/export', [LaporanController::class, 'exportMekanikPerformance'])->name('laporan.mekanik-performance.export');
        Route::get('/laporan/jurnal', [LaporanController::class, 'jurnal'])->name('laporan.jurnal');
        Route::get('/laporan/jurnal/export', [LaporanController::class, 'exportJurnal'])->name('laporan.jurnal.export');
        Route::get('/laporan/jurnal/export-pdf', [LaporanController::class, 'exportJurnalPdf'])->name('laporan.jurnal.export-pdf');
        Route::get('/laporan/jurnal-blu', fn (\Illuminate\Http\Request $r) => redirect()->route('speedshop.laporan.jurnal', array_merge($r->query(), ['sumber' => 'kas_kecil']), 301))->name('laporan.jurnal-blu');
        Route::get('/laporan/jurnal-blu/export', fn (\Illuminate\Http\Request $r) => redirect()->route('speedshop.laporan.jurnal.export', array_merge($r->query(), ['sumber' => 'kas_kecil']), 301))->name('laporan.jurnal-blu.export');
        Route::get('/laporan/jurnal-blu/export-pdf', fn (\Illuminate\Http\Request $r) => redirect()->route('speedshop.laporan.jurnal.export-pdf', array_merge($r->query(), ['sumber' => 'kas_kecil']), 301))->name('laporan.jurnal-blu.export-pdf');
        Route::get('/laporan/jurnal-saqu', fn (\Illuminate\Http\Request $r) => redirect()->route('speedshop.laporan.jurnal', array_merge($r->query(), ['sumber' => 'digital']), 301))->name('laporan.jurnal-saqu');
        Route::get('/laporan/jurnal-saqu/export', fn (\Illuminate\Http\Request $r) => redirect()->route('speedshop.laporan.jurnal.export', array_merge($r->query(), ['sumber' => 'digital']), 301))->name('laporan.jurnal-saqu.export');
        Route::get('/laporan/jurnal-saqu/export-pdf', fn (\Illuminate\Http\Request $r) => redirect()->route('speedshop.laporan.jurnal.export-pdf', array_merge($r->query(), ['sumber' => 'digital']), 301))->name('laporan.jurnal-saqu.export-pdf');
    });
    Route::middleware(['role:Manager', 'product-master.catalog'])->group(function () {
        Route::get('/master/produk-mo/export', [SpeedshopMasterProdukMoController::class, 'export'])->name('master.produk-mo.export');
        Route::get('/master/produk-mo/pdf', [SpeedshopMasterProdukMoController::class, 'exportPdf'])->name('master.produk-mo.pdf');
        Route::get('/master/produk-mo/template', [SpeedshopMasterProdukMoController::class, 'template'])->name('master.produk-mo.template');
        Route::post('/master/produk-mo/import', [SpeedshopMasterProdukMoController::class, 'import'])->name('master.produk-mo.import');
        Route::post('/master/produk-mo/bulk-archive', [SpeedshopMasterProdukMoController::class, 'bulkArchive'])->name('master.produk-mo.bulk-archive');
        Route::get('/master/produk-mo/trashed', [SpeedshopMasterProdukMoController::class, 'trashedIndex'])->name('master.produk-mo.trashed');
        Route::get('/master/produk-mo/trashed/unused', [SpeedshopMasterProdukMoController::class, 'unusedProducts'])->name('master.produk-mo.trashed.unused');
        Route::post('/master/produk-mo/trashed/unused-bulk-delete', [SpeedshopMasterProdukMoController::class, 'bulkForceDeleteUnused'])->name('master.produk-mo.trashed.unused-bulk-delete');
        Route::post('/master/produk-mo/trashed/restore-all', [SpeedshopMasterProdukMoController::class, 'restoreAllTrashed'])->name('master.produk-mo.restore-all-trashed');
        Route::post('/master/produk-mo/trashed/{id}/restore', [SpeedshopMasterProdukMoController::class, 'restoreTrashed'])->name('master.produk-mo.restore-trashed');
        Route::get('/master/produk-mo/tanpa-brand', [SpeedshopMasterProdukMoController::class, 'unbranded'])->name('master.produk-mo.unbranded');
        Route::get('/master/produk-mo/brand/{brand}', [SpeedshopMasterProdukMoController::class, 'byBrand'])->name('master.produk-mo.brand');
        Route::post('/master/produk-mo/brand/{brand}/logo', [SpeedshopMasterProdukMoController::class, 'uploadBrandLogo'])->name('master.produk-mo.brand.logo');
        Route::post('/master/produk-mo/bulk-price-percent', [SpeedshopMasterProdukMoController::class, 'applyPricePercent'])->name('master.produk-mo.bulk-price-percent');
        Route::get('/master/produk-mo', [SpeedshopMasterProdukMoController::class, 'index'])->name('master.produk-mo');
        Route::post('/master/produk-mo', [SpeedshopMasterProdukMoController::class, 'store'])->name('master.produk-mo.store');
        Route::get('/master/produk-mo/{product}', [SpeedshopMasterProdukMoController::class, 'show'])->name('master.produk-mo.show');
        Route::put('/master/produk-mo/{product}', [SpeedshopMasterProdukMoController::class, 'update'])->name('master.produk-mo.update');
        Route::post('/master/produk-mo/{product}/move-catalog', [SpeedshopMasterProdukMoController::class, 'moveCatalog'])->name('master.produk-mo.move-catalog');
        Route::delete('/master/produk-mo/{product}', [SpeedshopMasterProdukMoController::class, 'destroy'])->name('master.produk-mo.destroy');

        Route::get('/master/produk-speedshop/export', [SpeedshopMasterProdukSpeedshopController::class, 'export'])->name('master.produk-speedshop.export');
        Route::get('/master/produk-speedshop/pdf', [SpeedshopMasterProdukSpeedshopController::class, 'exportPdf'])->name('master.produk-speedshop.pdf');
        Route::get('/master/produk-speedshop/template', [SpeedshopMasterProdukSpeedshopController::class, 'template'])->name('master.produk-speedshop.template');
        Route::post('/master/produk-speedshop/import', [SpeedshopMasterProdukSpeedshopController::class, 'import'])->name('master.produk-speedshop.import');
        Route::post('/master/produk-speedshop/bulk-archive', [SpeedshopMasterProdukSpeedshopController::class, 'bulkArchive'])->name('master.produk-speedshop.bulk-archive');
        Route::get('/master/produk-speedshop/trashed', [SpeedshopMasterProdukSpeedshopController::class, 'trashedIndex'])->name('master.produk-speedshop.trashed');
        Route::get('/master/produk-speedshop/trashed/unused', [SpeedshopMasterProdukSpeedshopController::class, 'unusedProducts'])->name('master.produk-speedshop.trashed.unused');
        Route::post('/master/produk-speedshop/trashed/unused-bulk-delete', [SpeedshopMasterProdukSpeedshopController::class, 'bulkForceDeleteUnused'])->name('master.produk-speedshop.trashed.unused-bulk-delete');
        Route::post('/master/produk-speedshop/trashed/restore-all', [SpeedshopMasterProdukSpeedshopController::class, 'restoreAllTrashed'])->name('master.produk-speedshop.restore-all-trashed');
        Route::post('/master/produk-speedshop/trashed/{id}/restore', [SpeedshopMasterProdukSpeedshopController::class, 'restoreTrashed'])->name('master.produk-speedshop.restore-trashed');
        Route::get('/master/produk-speedshop/tanpa-brand', [SpeedshopMasterProdukSpeedshopController::class, 'unbranded'])->name('master.produk-speedshop.unbranded');
        Route::get('/master/produk-speedshop/brand/{brand}', [SpeedshopMasterProdukSpeedshopController::class, 'byBrand'])->name('master.produk-speedshop.brand');
        Route::post('/master/produk-speedshop/brand/{brand}/logo', [SpeedshopMasterProdukSpeedshopController::class, 'uploadBrandLogo'])->name('master.produk-speedshop.brand.logo');
        Route::post('/master/produk-speedshop/bulk-price-percent', [SpeedshopMasterProdukSpeedshopController::class, 'applyPricePercent'])->name('master.produk-speedshop.bulk-price-percent');
        Route::get('/master/produk-speedshop', [SpeedshopMasterProdukSpeedshopController::class, 'index'])->name('master.produk-speedshop');
        Route::post('/master/produk-speedshop', [SpeedshopMasterProdukSpeedshopController::class, 'store'])->name('master.produk-speedshop.store');
        Route::get('/master/produk-speedshop/{product}', [SpeedshopMasterProdukSpeedshopController::class, 'show'])->name('master.produk-speedshop.show');
        Route::put('/master/produk-speedshop/{product}', [SpeedshopMasterProdukSpeedshopController::class, 'update'])->name('master.produk-speedshop.update');
        Route::post('/master/produk-speedshop/{product}/move-catalog', [SpeedshopMasterProdukSpeedshopController::class, 'moveCatalog'])->name('master.produk-speedshop.move-catalog');
        Route::delete('/master/produk-speedshop/{product}', [SpeedshopMasterProdukSpeedshopController::class, 'destroy'])->name('master.produk-speedshop.destroy');

        Route::get('/master/part-luar/export', [SpeedshopMasterPartLuarController::class, 'export'])->name('master.part-luar.export');
        Route::get('/master/part-luar/pdf', [SpeedshopMasterPartLuarController::class, 'exportPdf'])->name('master.part-luar.pdf');
        Route::get('/master/part-luar/template', [SpeedshopMasterPartLuarController::class, 'template'])->name('master.part-luar.template');
        Route::post('/master/part-luar/import', [SpeedshopMasterPartLuarController::class, 'import'])->name('master.part-luar.import');
        Route::post('/master/part-luar/bulk-archive', [SpeedshopMasterPartLuarController::class, 'bulkArchive'])->name('master.part-luar.bulk-archive');
        Route::get('/master/part-luar/trashed', [SpeedshopMasterPartLuarController::class, 'trashedIndex'])->name('master.part-luar.trashed');
        Route::get('/master/part-luar/trashed/unused', [SpeedshopMasterPartLuarController::class, 'unusedProducts'])->name('master.part-luar.trashed.unused');
        Route::post('/master/part-luar/trashed/unused-bulk-delete', [SpeedshopMasterPartLuarController::class, 'bulkForceDeleteUnused'])->name('master.part-luar.trashed.unused-bulk-delete');
        Route::post('/master/part-luar/trashed/restore-all', [SpeedshopMasterPartLuarController::class, 'restoreAllTrashed'])->name('master.part-luar.restore-all-trashed');
        Route::post('/master/part-luar/trashed/{id}/restore', [SpeedshopMasterPartLuarController::class, 'restoreTrashed'])->name('master.part-luar.restore-trashed');
        Route::get('/master/part-luar', [SpeedshopMasterPartLuarController::class, 'index'])->name('master.part-luar');
        Route::post('/master/part-luar', [SpeedshopMasterPartLuarController::class, 'store'])->name('master.part-luar.store');
        Route::get('/master/part-luar/{product}', [SpeedshopMasterPartLuarController::class, 'show'])->name('master.part-luar.show');
        Route::put('/master/part-luar/{product}', [SpeedshopMasterPartLuarController::class, 'update'])->name('master.part-luar.update');
        Route::post('/master/part-luar/{product}/move-catalog', [SpeedshopMasterPartLuarController::class, 'moveCatalog'])->name('master.part-luar.move-catalog');
        Route::delete('/master/part-luar/{product}', [SpeedshopMasterPartLuarController::class, 'destroy'])->name('master.part-luar.destroy');

        foreach ([
            'merchandise' => SpeedshopMasterProdukMerchandiseController::class,
            'consumable' => SpeedshopMasterProdukConsumableController::class,
        ] as $slug => $controller) {
            Route::get("/master/{$slug}/export", [$controller, 'export'])->name("master.{$slug}.export");
            Route::get("/master/{$slug}/pdf", [$controller, 'exportPdf'])->name("master.{$slug}.pdf");
            Route::get("/master/{$slug}/template", [$controller, 'template'])->name("master.{$slug}.template");
            Route::post("/master/{$slug}/import", [$controller, 'import'])->name("master.{$slug}.import");
            Route::post("/master/{$slug}/bulk-archive", [$controller, 'bulkArchive'])->name("master.{$slug}.bulk-archive");
            Route::get("/master/{$slug}/trashed", [$controller, 'trashedIndex'])->name("master.{$slug}.trashed");
            Route::get("/master/{$slug}/trashed/unused", [$controller, 'unusedProducts'])->name("master.{$slug}.trashed.unused");
            Route::post("/master/{$slug}/trashed/unused-bulk-delete", [$controller, 'bulkForceDeleteUnused'])->name("master.{$slug}.trashed.unused-bulk-delete");
            Route::post("/master/{$slug}/trashed/restore-all", [$controller, 'restoreAllTrashed'])->name("master.{$slug}.restore-all-trashed");
            Route::post("/master/{$slug}/trashed/{id}/restore", [$controller, 'restoreTrashed'])->name("master.{$slug}.restore-trashed");
            Route::get("/master/{$slug}", [$controller, 'index'])->name("master.{$slug}");
            Route::post("/master/{$slug}", [$controller, 'store'])->name("master.{$slug}.store");
            Route::get("/master/{$slug}/{product}", [$controller, 'show'])->name("master.{$slug}.show");
            Route::put("/master/{$slug}/{product}", [$controller, 'update'])->name("master.{$slug}.update");
            Route::post("/master/{$slug}/{product}/move-catalog", [$controller, 'moveCatalog'])->name("master.{$slug}.move-catalog");
            Route::delete("/master/{$slug}/{product}", [$controller, 'destroy'])->name("master.{$slug}.destroy");
        }
    });
    Route::redirect('/master/produk', '/speedshop/master/produk-mo', 301)->name('master.produk');
    Route::middleware('role:Manager,Accounting')->group(function () {
        Route::get('/categories/export', [MasterCategoryController::class, 'export'])->name('categories.export');
        Route::get('/categories/pdf', [MasterCategoryController::class, 'exportPdf'])->name('categories.pdf');
        Route::get('/categories/template', [MasterCategoryController::class, 'template'])->name('categories.template');
        Route::post('/categories/import', [MasterCategoryController::class, 'import'])->name('categories.import');
        Route::resource('categories', MasterCategoryController::class)->except(['create', 'edit']);
        Route::get('/brands/export', [MasterBrandController::class, 'export'])->name('brands.export');
        Route::get('/brands/pdf', [MasterBrandController::class, 'exportPdf'])->name('brands.pdf');
        Route::get('/brands/template', [MasterBrandController::class, 'template'])->name('brands.template');
        Route::post('/brands/import', [MasterBrandController::class, 'import'])->name('brands.import');
        Route::resource('brands', MasterBrandController::class)->except(['create', 'edit']);
        Route::post('/laporan/rincian-biaya', fn () => abort(410))->name('laporan.rincian-biaya.store');
        Route::delete('/laporan/rincian-biaya/{entry}', fn () => abort(410))->name('laporan.rincian-biaya.destroy');
        Route::post('/laporan/jurnal', [LaporanController::class, 'storeJurnal'])->name('laporan.jurnal.store');
        Route::delete('/laporan/jurnal/{sumber}/{entry}', [LaporanController::class, 'destroyJurnalEntry'])
            ->where(['sumber' => 'kas_kecil|digital', 'entry' => '[0-9]+'])
            ->name('laporan.jurnal.destroy');
        Route::post('/laporan/jurnal-blu', fn () => abort(410))->name('laporan.jurnal-blu.store');
        Route::delete('/laporan/jurnal-blu/{entry}', fn () => abort(410))->name('laporan.jurnal-blu.destroy');
        Route::post('/laporan/jurnal-saqu', fn () => abort(410))->name('laporan.jurnal-saqu.store');
        Route::delete('/laporan/jurnal-saqu/{entry}', fn () => abort(410))->name('laporan.jurnal-saqu.destroy');
    });
});

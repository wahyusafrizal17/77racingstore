<?php $__env->startSection('title', 'Dashboard Speedshop'); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('partials.speedshop.dashboard-load-animations', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.dashboard-manager-template-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>
<?php if(!empty($managerDashboardCharts)): ?>
    <?php echo $__env->make('partials.speedshop.dashboard-manager-template', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php else: ?>
<?php
    $omsetDetailUrl = route('speedshop.dashboard.omset-detail', request()->only(['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai', 'compare', 'compare_dari', 'compare_sampai']));
?>
<div
    x-data="dashboardTransaksiView(<?php echo \Illuminate\Support\Js::from($omsetDetailUrl)->toHtml() ?>)"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    :class="loading && 'is-loading'"
    class="ss-dashboard ss-dash-page"
>
    <div
        x-show="loading"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="ss-dash-loading"
        aria-live="polite"
        aria-busy="true"
    >
        <div class="ss-dash-loading-panel">
            <div class="ss-dash-spinner" aria-hidden="true"></div>
            <p class="ss-dash-loading-text">Memuat dashboard…</p>
            <p class="ss-dash-loading-sub">Menyiapkan data transaksi</p>
        </div>
    </div>

    <div class="ss-dash-content">
    <?php echo $__env->make('partials.speedshop.dashboard-filter-strip', [
        'dashboardFilterExports' => ['excel' => true, 'excel_detail' => true],
        'hideSpeedshopFilter' => true,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php echo $__env->make('partials.speedshop.dashboard-compare-banner', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php echo $__env->make('partials.speedshop.dashboard-summary-section', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="panel-card ss-dash-enter" style="--ss-delay: 380ms">
        <div class="panel-card-hd">
            <div>
                <h2 class="panel-card-title">List Transaksi Per Invoice</h2>
                <p class="panel-card-sub">Klik detail untuk lihat item jasa, sparepart, dan rincian omset (charge &amp; PPN).</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Order ID</th>
                        <?php if(!empty($dashboardAllSpeedshops)): ?>
                        <th>Speedshop</th>
                        <?php endif; ?>
                        <th>No. Invoice</th>
                        <th>Tgl. Invoice</th>
                        <th>Jenis Order</th>
                        <th>Kategori Service</th>
                        <th>Sumber Kedatangan</th>
                        <th>Nama Customer</th>
                        <th>Nama mekanik</th>
                        <th>No. HP</th>
                        <th>No. Polisi</th>
                        <th>Jenis Kendaraan</th>
                        <th>KM Kendaraan</th>
                        <th>Tahun Kendaraan</th>
                        <th>Metode Pembayaran</th>
                        <th class="text-right">Pendapatan Omset</th>
                        <th class="text-right">Charge</th>
                        <th class="text-right">PPN</th>
                        <th class="text-right">Total Tagihan</th>
                        <th class="text-center w-14"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $serviceOrders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $so): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $lineItemsForModal = [];
                        foreach ($so->jasaServis as $j) {
                            $q = (int) ($j->pivot->qty ?? 1);
                            $b = (int) ($j->pivot->biaya ?? 0);
                            $d = (int) ($j->pivot->diskon ?? 0);
                            $lineItemsForModal[] = [
                                'kode' => '-',
                                'nama' => $j->nama,
                                'harga_satuan' => $b,
                                'qty' => $q,
                                'diskon' => $d,
                                'subtotal' => ($q * $b) - $d,
                            ];
                        }
                        foreach ($so->products as $p) {
                            $q = (int) ($p->pivot->qty ?? 0);
                            $h = (int) ($p->pivot->harga ?? 0);
                            $d = (int) ($p->pivot->diskon ?? 0);
                            $lineItemsForModal[] = [
                                'kode' => $p->kode_produk !== null && $p->kode_produk !== '' ? (string) $p->kode_produk : '-',
                                'nama' => $p->nama_produk,
                                'harga_satuan' => $h,
                                'qty' => $q,
                                'diskon' => $d,
                                'subtotal' => ($q * $h) - $d,
                            ];
                        }
                        $diskonInvoice = (int) ($so->invoice?->diskon ?? 0);
                        $catatanInvoice = (string) ($so->invoice?->catatan ?? '');
                        $invoiceUrl = route('speedshop.work-orders.invoice', $so);
                        $reorderUrl = route('speedshop.work-orders.create');
                        $jenisOrder = $so->jenisOrderDisplay();
                        $kategoriServiceLabel = $so->kategoriServiceDisplay();
                        $metodeLabel = $so->pembayaranDisplayLabel();
                        $k = $so?->kendaraan;
                        $jenisKendaraan = $k ? trim(implode(' ', array_filter([(string) ($k->merk ?? ''), (string) ($k->tipe ?? '')]))) : '';
                        $jenisKendaraan = $jenisKendaraan !== '' ? $jenisKendaraan : '-';
                        $tglInvoice = optional($so?->tanggal_selesai ?? $so?->invoice?->tanggal ?? $so?->tanggal_masuk);
                        $noInvoice = $so?->invoice?->kode_invoice ?? ('WO-'.$so->kode_servis);
                        $omsetRow = $so->invoiceOmsetBreakdown();
                        $sk = $so->sumber_kedatangan;
                        $sumberKedatanganLabel = ($sk !== null && $sk !== '' && isset(\App\Models\ServiceOrder::SUMBER_KEDATANGAN[$sk]))
                            ? \App\Models\ServiceOrder::SUMBER_KEDATANGAN[$sk]
                            : '-';
                    ?>
                    <tr>
                        <td><?php echo e($serviceOrders->firstItem() + $i); ?></td>
                        <td><strong><?php echo e($so->id); ?></strong></td>
                        <?php if(!empty($dashboardAllSpeedshops)): ?>
                        <td style="white-space:nowrap"><?php echo e($so->speedshop?->nama ?? '-'); ?></td>
                        <?php endif; ?>
                        <td><strong><?php echo e($noInvoice); ?></strong></td>
                        <td style="white-space:nowrap"><?php echo e($tglInvoice ? $tglInvoice->format('d/m/Y') : '-'); ?></td>
                        <td><?php echo e($jenisOrder); ?></td>
                        <td><?php echo e($kategoriServiceLabel); ?></td>
                        <td><?php echo e($sumberKedatanganLabel); ?></td>
                        <td><?php echo e($so?->pelanggan?->nama ?? '-'); ?></td>
                        <td><?php echo e($so?->mekanik?->nama ?? '-'); ?></td>
                        <td style="white-space:nowrap"><?php echo e($so?->pelanggan?->no_hp ?? '-'); ?></td>
                        <td style="white-space:nowrap"><?php echo e($so?->kendaraan?->nomor_polisi ?? '-'); ?></td>
                        <td><?php echo e($jenisKendaraan); ?></td>
                        <td class="text-right"><?php echo e($so->kilometer !== null && $so->kilometer !== '' ? number_format((int) $so->kilometer, 0, ',', '.') : '-'); ?></td>
                        <td><?php echo e($so?->kendaraan?->tahun ?? '-'); ?></td>
                        <td style="white-space:nowrap"><?php echo e($metodeLabel); ?></td>
                        <td class="text-right"><?php echo e(number_format($omsetRow['pendapatan_omset'], 0, ',', '.')); ?></td>
                        <td class="text-right amount-charge"><?php echo e(number_format($omsetRow['charge'], 0, ',', '.')); ?></td>
                        <td class="text-right amount-ppn"><?php echo e(number_format($omsetRow['ppn'], 0, ',', '.')); ?></td>
                        <td class="text-right amount-total"><?php echo e(number_format($omsetRow['total_tagihan'], 0, ',', '.')); ?></td>
                        <td class="text-center">
                            <button
                                type="button"
                                class="btn-detail"
                                title="Detail"
                                @click="openDetail({
                                    orderId: <?php echo \Illuminate\Support\Js::from($so->id)->toHtml() ?>,
                                    invoice: <?php echo \Illuminate\Support\Js::from($noInvoice)->toHtml() ?>,
                                    tanggal: <?php echo \Illuminate\Support\Js::from($tglInvoice ? $tglInvoice->format('d/m/Y') : '-')->toHtml() ?>,
                                    metode: <?php echo \Illuminate\Support\Js::from($metodeLabel)->toHtml() ?>,
                                    jenisOrder: <?php echo \Illuminate\Support\Js::from($jenisOrder)->toHtml() ?>,
                                    kategoriService: <?php echo \Illuminate\Support\Js::from($kategoriServiceLabel)->toHtml() ?>,
                                    nama: <?php echo \Illuminate\Support\Js::from($so?->pelanggan?->nama ?? '-')->toHtml() ?>,
                                    noHp: <?php echo \Illuminate\Support\Js::from($so?->pelanggan?->no_hp ?? '-')->toHtml() ?>,
                                    alamat: <?php echo \Illuminate\Support\Js::from($so?->pelanggan?->alamat ?? '-')->toHtml() ?>,
                                    polisi: <?php echo \Illuminate\Support\Js::from($so?->kendaraan?->nomor_polisi ?? '-')->toHtml() ?>,
                                    jenisKendaraan: <?php echo \Illuminate\Support\Js::from($jenisKendaraan)->toHtml() ?>,
                                    km: <?php echo \Illuminate\Support\Js::from($so->kilometer !== null && $so->kilometer !== '' ? number_format((int) $so->kilometer, 0, ',', '.') : '-')->toHtml() ?>,
                                    tahun: <?php echo \Illuminate\Support\Js::from($so?->kendaraan?->tahun ?? '-')->toHtml() ?>,
                                    mekanik: <?php echo \Illuminate\Support\Js::from($so?->mekanik?->nama ?? '-')->toHtml() ?>,
                                    catatan: <?php echo \Illuminate\Support\Js::from(trim($catatanInvoice) !== '' ? $catatanInvoice : '')->toHtml() ?>,
                                    lineItems: <?php echo \Illuminate\Support\Js::from($lineItemsForModal)->toHtml() ?>,
                                    diskon: <?php echo \Illuminate\Support\Js::from($diskonInvoice)->toHtml() ?>,
                                    pendapatanOmset: <?php echo \Illuminate\Support\Js::from($omsetRow['pendapatan_omset'])->toHtml() ?>,
                                    charge: <?php echo \Illuminate\Support\Js::from($omsetRow['charge'])->toHtml() ?>,
                                    ppn: <?php echo \Illuminate\Support\Js::from($omsetRow['ppn'])->toHtml() ?>,
                                    totalTagihan: <?php echo \Illuminate\Support\Js::from($omsetRow['total_tagihan'])->toHtml() ?>,
                                    invoiceUrl: <?php echo \Illuminate\Support\Js::from($invoiceUrl)->toHtml() ?>,
                                    reorderUrl: <?php echo \Illuminate\Support\Js::from($reorderUrl)->toHtml() ?>
                                })"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="<?php echo e(!empty($dashboardAllSpeedshops) ? 21 : 20); ?>" class="table-empty">Belum ada transaksi invoice pada periode ini.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($serviceOrders->hasPages()): ?>
        <div class="pagination-wrap"><?php echo e($serviceOrders->links()); ?></div>
        <?php endif; ?>
    </div>

    <div x-show="showDetail" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="closeDetail()">
        <div x-show="showDetail" x-transition.opacity class="fixed inset-0 bg-black/50" @click="closeDetail()"></div>
        <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
            <div x-show="showDetail" x-transition class="relative flex max-h-[min(92vh,900px)] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl" @click.stop>
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Ringkasan order</p>
                        <h3 class="mt-0.5 text-xl font-bold tracking-tight text-gray-900">Order <span class="text-red-600">#<span x-text="detail.orderId"></span></span></h3>
                        <p class="mt-1 text-sm text-gray-500">Invoice <span class="font-medium text-gray-700" x-text="detail.invoice"></span></p>
                    </div>
                    <button type="button" @click="closeDetail()" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-gray-200 text-gray-500 transition hover:bg-gray-50 hover:text-gray-800" aria-label="Tutup">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                    <div class="grid grid-cols-1 gap-x-10 gap-y-3 sm:grid-cols-2">
                        <template x-for="row in metaRows()" :key="row.key">
                            <div class="sm:col-span-1">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500" x-text="row.label"></p>
                                <p class="mt-1 whitespace-pre-wrap break-words rounded-xl border border-gray-100 bg-gray-50/80 px-3 py-2 text-sm text-gray-900" x-text="row.value"></p>
                            </div>
                        </template>
                    </div>

                    <div class="mt-6">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Item order</p>
                        <div class="mt-2 overflow-x-auto rounded-xl border border-gray-200">
                            <table class="w-full min-w-[720px] text-left text-sm">
                                <thead>
                                    <tr class="bg-[#f7f3f4]">
                                        <th class="whitespace-nowrap px-3 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                                        <th class="whitespace-nowrap px-3 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode Produk</th>
                                        <th class="px-3 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Produk</th>
                                        <th class="whitespace-nowrap px-3 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Harga Satuan (Rp)</th>
                                        <th class="whitespace-nowrap px-3 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Jumlah</th>
                                        <th class="whitespace-nowrap px-3 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Diskon (Rp)</th>
                                        <th class="whitespace-nowrap px-3 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Sub Total (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <template x-for="(row, idx) in detail.lineItems" :key="'line-' + idx">
                                        <tr class="hover:bg-gray-50/60">
                                            <td class="px-3 py-2.5 text-gray-500" x-text="idx + 1"></td>
                                            <td class="whitespace-nowrap px-3 py-2.5 font-mono text-xs text-gray-700" x-text="row.kode"></td>
                                            <td class="px-3 py-2.5 text-gray-800" x-text="row.nama"></td>
                                            <td class="whitespace-nowrap px-3 py-2.5 text-right text-gray-800" x-text="rupiah(row.harga_satuan)"></td>
                                            <td class="whitespace-nowrap px-3 py-2.5 text-right text-gray-700" x-text="Number(row.qty || 0).toLocaleString('id-ID')"></td>
                                            <td class="whitespace-nowrap px-3 py-2.5 text-right text-gray-700" x-text="rupiah(row.diskon)"></td>
                                            <td class="whitespace-nowrap px-3 py-2.5 text-right font-medium text-gray-900" x-text="rupiah(lineSubtotal(row))"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="!detail.lineItems || detail.lineItems.length === 0">
                                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">Tidak ada item pada order ini.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 flex flex-col items-end gap-1 border-t border-gray-100 pt-3 text-sm">
                            <div class="flex w-full max-w-xs justify-between gap-6 text-gray-600">
                                <span>Diskon</span>
                                <span class="font-medium text-gray-900" x-text="rupiah(detail.diskon)"></span>
                            </div>
                            <div class="flex w-full max-w-xs justify-between gap-6 text-gray-600">
                                <span>Pendapatan omset</span>
                                <span class="font-medium text-gray-900" x-text="rupiah(detail.pendapatanOmset)"></span>
                            </div>
                            <div class="flex w-full max-w-xs justify-between gap-6 text-gray-600">
                                <span>Charge</span>
                                <span class="font-medium text-violet-700" x-text="rupiah(detail.charge)"></span>
                            </div>
                            <div class="flex w-full max-w-xs justify-between gap-6 text-gray-600">
                                <span>PPN</span>
                                <span class="font-medium text-purple-700" x-text="rupiah(detail.ppn)"></span>
                            </div>
                            <div class="flex w-full max-w-xs justify-between gap-6 text-base font-bold text-gray-900">
                                <span>Total tagihan</span>
                                <span class="text-emerald-700" x-text="rupiah(detail.totalTagihan)"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 flex-col gap-3 border-t border-gray-100 bg-gray-50/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div class="flex flex-wrap gap-2">
                        <a
                            :href="detail.invoiceUrl || '#'"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                        >
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            Buka invoice (cetak / PDF)
                        </a>
                    </div>
                    <div class="flex flex-wrap gap-2 sm:justify-end">
                        <a
                            :href="detail.reorderUrl || '#'"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                        >
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Order baru
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <?php echo $__env->make('partials.speedshop.dashboard-omset-detail-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function omsetDetailModalState(baseUrl) {
    const esc = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    return {
        openModal: false,
        loading: false,
        error: '',
        title: '',
        subtitle: '',
        tableHtml: '',
        total: 0,
        totalLabel: 'Total',
        compareEnabled: false,
        comparePeriodLabel: '',
        compareTotal: 0,
        compareDeltaPercent: 0,
        openOmsetDetail(type) {
            this.open(type);
        },
        async open(type) {
            this.openModal = true;
            this.loading = true;
            this.error = '';
            this.tableHtml = '';
            this.total = 0;
            this.compareEnabled = false;
            this.comparePeriodLabel = '';
            this.compareTotal = 0;
            this.compareDeltaPercent = 0;
            this.title = 'Memuat…';
            this.subtitle = '';
            document.body.classList.add('overflow-hidden');
            try {
                const url = new URL(baseUrl, window.location.origin);
                url.searchParams.set('type', type);
                const res = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || 'Gagal memuat rincian.');
                }
                this.title = data.title || 'Rincian';
                this.subtitle = data.subtitle || '';
                const columns = Array.isArray(data.columns) ? data.columns : [];
                const rows = Array.isArray(data.rows) ? data.rows : [];
                this.tableHtml = this.buildTableHtml(columns, rows);
                this.total = Number(data.total || 0);
                this.totalLabel = data.total_label || 'Total';
                this.compareEnabled = Boolean(data.compare_enabled);
                this.comparePeriodLabel = data.compare_period_label || '';
                this.compareTotal = Number(data.compare_total || 0);
                this.compareDeltaPercent = Number(data.compare_delta_percent || 0);
            } catch (e) {
                this.error = e?.message || 'Gagal memuat rincian.';
                this.title = 'Rincian';
            } finally {
                this.loading = false;
            }
        },
        close() {
            this.openModal = false;
            document.body.classList.remove('overflow-hidden');
        },
        rupiah(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        },
        formatPercent(value) {
            const num = Number(value || 0);
            return (num >= 0 ? '+' : '') + num.toLocaleString('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
        },
        formatCell(value, col) {
            if (col?.format === 'rupiah') {
                return this.rupiah(value);
            }
            if (col?.format === 'percent') {
                return this.formatPercent(value);
            }
            if (typeof value === 'number') {
                return Number(value).toLocaleString('id-ID');
            }
            return value ?? '-';
        },
        buildTableHtml(columns, rows) {
            if (!columns.length || !rows.length) {
                return '';
            }

            let html = '<table class="omset-detail-native-table w-full min-w-[560px] text-left text-sm"><thead><tr class="bg-[#f7f3f4]">';
            html += '<th class="whitespace-nowrap px-3 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>';
            for (const col of columns) {
                const align = col.align === 'right' ? ' text-right' : '';
                html += `<th class="whitespace-nowrap px-3 py-2.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500${align}">${esc(col.label)}</th>`;
            }
            html += '</tr></thead><tbody class="divide-y divide-gray-100">';

            rows.forEach((row, idx) => {
                html += '<tr class="hover:bg-gray-50/60">';
                html += `<td class="px-3 py-2.5 text-gray-500">${idx + 1}</td>`;
                for (const col of columns) {
                    const align = col.align === 'right' ? ' text-right whitespace-nowrap' : '';
                    let extraClass = '';
                    if (col.format === 'percent') {
                        const pct = Number(row[col.key] || 0);
                        extraClass = pct >= 0 ? ' text-emerald-700 font-semibold' : ' text-red-600 font-semibold';
                    }
                    const text = esc(this.formatCell(row[col.key], col));
                    html += `<td class="px-3 py-2.5 text-gray-800${align}${extraClass}">${text}</td>`;
                }
                html += '</tr>';
            });

            html += '</tbody></table>';
            return html;
        },
    };
}

function resolveDashboardCaptureScale(node, maxWidth = 1600, maxHeight = 2260) {
    const width = Math.max(1, node.offsetWidth || node.scrollWidth);
    const height = Math.max(1, node.scrollHeight || node.offsetHeight);
    const fitScale = Math.min(1, maxWidth / width, maxHeight / height);

    return Math.max(0.5, Math.min(1.25, fitScale));
}

function suspendExternalStylesheetsForCapture() {
    const backup = [];

    document.querySelectorAll('link[rel="stylesheet"]').forEach((link) => {
        backup.push({ el: link, disabled: link.disabled });
        link.disabled = true;
    });

    return backup;
}

function restoreExternalStylesheetsForCapture(backup) {
    backup.forEach(({ el, disabled }) => {
        el.disabled = disabled;
    });
}

const DASHBOARD_CAPTURE_RESET_STYLE = `
.ss-dashboard-capture-exporting,
.ss-dashboard-capture-exporting * {
    animation: none !important;
    transition: none !important;
}
.ss-dashboard-capture-exporting .ss-dash-enter,
.ss-dashboard-capture-exporting .ss-dash-enter-child {
    opacity: 1 !important;
    transform: none !important;
    filter: none !important;
}
.ss-dashboard-capture-exporting .branch-bar {
    width: var(--bar-width, 0%) !important;
}
`;

function prepareDashboardCaptureVisibility(root) {
    const backups = [];

    root.querySelectorAll('.ss-dash-enter, .ss-dash-enter-child').forEach((el) => {
        backups.push({
            el,
            opacity: el.style.opacity,
            transform: el.style.transform,
            animation: el.style.animation,
            transition: el.style.transition,
        });
        el.style.opacity = '1';
        el.style.transform = 'none';
        el.style.animation = 'none';
        el.style.transition = 'none';
    });

    root.querySelectorAll('.branch-bar').forEach((el) => {
        backups.push({
            el,
            width: el.style.width,
            transition: el.style.transition,
        });
        const width = el.style.getPropertyValue('--bar-width') || window.getComputedStyle(el).getPropertyValue('--bar-width');
        if (width) {
            el.style.width = width;
        }
        el.style.transition = 'none';
    });

    return backups;
}

function restoreDashboardCaptureVisibility(backups) {
    backups.forEach((entry) => {
        if ('opacity' in entry) {
            entry.el.style.opacity = entry.opacity;
            entry.el.style.transform = entry.transform;
            entry.el.style.animation = entry.animation;
            entry.el.style.transition = entry.transition;
            return;
        }
        entry.el.style.width = entry.width;
        entry.el.style.transition = entry.transition;
    });
}

function prepareDashboardCloneForCapture(sourceRoot, clonedDoc) {
    const clonedRoot = clonedDoc.getElementById('ss-dashboard-capture');
    if (!clonedRoot) {
        return;
    }

    clonedDoc.querySelectorAll('link[rel="stylesheet"]').forEach((el) => el.remove());

    const resetStyle = clonedDoc.createElement('style');
    resetStyle.textContent = DASHBOARD_CAPTURE_RESET_STYLE;
    clonedDoc.head.appendChild(resetStyle);

    const sourceCanvases = sourceRoot.querySelectorAll('canvas');
    clonedRoot.querySelectorAll('canvas').forEach((clonedCanvas, index) => {
        const sourceCanvas = sourceCanvases[index];
        if (!sourceCanvas) {
            return;
        }
        clonedCanvas.width = sourceCanvas.width;
        clonedCanvas.height = sourceCanvas.height;
        const ctx = clonedCanvas.getContext('2d');
        if (ctx) {
            ctx.drawImage(sourceCanvas, 0, 0);
        }
    });

    clonedRoot.querySelectorAll('img').forEach((img) => {
        const src = img.getAttribute('src') || '';
        if (src.startsWith('http') && !src.startsWith(window.location.origin)) {
            img.remove();
        }
    });

    clonedRoot.classList.add('ss-dashboard-capture-exporting');
}

function speedshopManagerDashboard(baseUrl) {
    return {
        loading: false,
        exportingImage: false,
        ...omsetDetailModalState(baseUrl),
        async exportDashboardImage() {
            if (this.exportingImage || this.loading) {
                return;
            }
            if (typeof html2canvas === 'undefined') {
                window.alert('Fitur cetak gambar belum siap. Muat ulang halaman lalu coba lagi.');
                return;
            }

            const node = document.getElementById('ss-dashboard-capture');
            if (!node) {
                return;
            }

            node.classList.add('ss-dashboard-capture-exporting');
            this.exportingImage = true;

            let stylesheetBackup = null;
            let visibilityBackup = [];

            try {
                await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));

                stylesheetBackup = suspendExternalStylesheetsForCapture();
                visibilityBackup = prepareDashboardCaptureVisibility(node);
                await new Promise((resolve) => requestAnimationFrame(resolve));

                const captureScale = resolveDashboardCaptureScale(node);

                const canvas = await html2canvas(node, {
                    backgroundColor: '#f4f5f7',
                    scale: captureScale,
                    useCORS: true,
                    allowTaint: true,
                    logging: false,
                    imageTimeout: 8000,
                    onclone: (doc) => {
                        prepareDashboardCloneForCapture(node, doc);
                    },
                });

                const fittedCanvas = this.fitDashboardCanvasToSinglePage(canvas, 1600, 2260);
                const dataUrl = fittedCanvas.toDataURL('image/png');
                const params = new URLSearchParams(window.location.search);
                const periodParts = [
                    params.get('mode') || 'bulanan',
                    params.get('bulan') || params.get('tanggal') || params.get('tahun') || params.get('dari'),
                ].filter(Boolean);
                const periodSlug = periodParts.join('-')
                    .replace(/[^\w\-]+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '')
                    .slice(0, 48);
                const stamp = new Date().toISOString().slice(0, 10);

                const link = document.createElement('a');
                link.download = `dashboard-speedshop-${periodSlug || stamp}.png`;
                link.href = dataUrl;
                document.body.appendChild(link);
                link.click();
                link.remove();
            } catch (error) {
                console.error(error);
                window.alert('Gagal membuat gambar dashboard. Coba lagi.');
            } finally {
                if (stylesheetBackup) {
                    restoreExternalStylesheetsForCapture(stylesheetBackup);
                }
                if (visibilityBackup.length) {
                    restoreDashboardCaptureVisibility(visibilityBackup);
                }
                node.classList.remove('ss-dashboard-capture-exporting');
                this.exportingImage = false;
            }
        },
        fitDashboardCanvasToSinglePage(sourceCanvas, maxWidth, maxHeight) {
            const scale = Math.min(1, maxWidth / sourceCanvas.width, maxHeight / sourceCanvas.height);
            const width = Math.max(1, Math.round(sourceCanvas.width * scale));
            const height = Math.max(1, Math.round(sourceCanvas.height * scale));
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            if (!ctx) {
                return sourceCanvas;
            }
            ctx.fillStyle = '#f4f5f7';
            ctx.fillRect(0, 0, width, height);
            ctx.drawImage(sourceCanvas, 0, 0, width, height);
            return canvas;
        },
    };
}
</script>
<?php if(empty($managerDashboardCharts)): ?>
<script>
function dashboardTransaksiView(omsetDetailUrl) {
    return {
        loading: false,
        showDetail: false,
        detail: {
            orderId: '',
            invoice: '',
            tanggal: '',
            metode: '',
            jenisOrder: '',
            kategoriService: '',
            nama: '',
            noHp: '',
            alamat: '',
            polisi: '',
            jenisKendaraan: '',
            km: '',
            tahun: '',
            mekanik: '',
            catatan: '',
            lineItems: [],
            diskon: 0,
            pendapatanOmset: 0,
            charge: 0,
            ppn: 0,
            totalTagihan: 0,
            invoiceUrl: '',
            reorderUrl: '',
        },
        metaRows() {
            const d = this.detail;
            return [
                { key: 'tgl', label: 'Tgl. Invoice', value: d.tanggal || '-' },
                { key: 'inv', label: 'No. Invoice', value: d.invoice || '-' },
                { key: 'metode', label: 'Metode Pembayaran', value: d.metode || '-' },
                { key: 'jenis', label: 'Jenis Order', value: d.jenisOrder || '-' },
                { key: 'kat', label: 'Kategori Service', value: d.kategoriService || '-' },
                { key: 'nama', label: 'Nama', value: d.nama || '-' },
                { key: 'hp', label: 'No. HP', value: d.noHp || '-' },
                { key: 'alamat', label: 'Alamat', value: d.alamat || '-' },
                { key: 'pol', label: 'No. Polisi', value: d.polisi || '-' },
                { key: 'jk', label: 'Jenis Kendaraan', value: d.jenisKendaraan || '-' },
                { key: 'km', label: 'KM Kendaraan', value: d.km || '-' },
                { key: 'th', label: 'Tahun Kendaraan', value: d.tahun != null && d.tahun !== '' ? String(d.tahun) : '-' },
                { key: 'mek', label: 'Nama Mekanik', value: d.mekanik || '-' },
                { key: 'cat', label: 'Catatan', value: (d.catatan && String(d.catatan).trim()) ? d.catatan : '—' },
            ];
        },
        openDetail(payload) {
            this.detail = {
                orderId: payload.orderId ?? '',
                invoice: payload.invoice || '',
                tanggal: payload.tanggal || '',
                metode: payload.metode || '',
                jenisOrder: payload.jenisOrder || '',
                kategoriService: payload.kategoriService || '',
                nama: payload.nama || '',
                noHp: payload.noHp || '',
                alamat: payload.alamat || '',
                polisi: payload.polisi || '',
                jenisKendaraan: payload.jenisKendaraan || '',
                km: payload.km || '',
                tahun: payload.tahun ?? '',
                mekanik: payload.mekanik || '',
                catatan: payload.catatan || '',
                lineItems: Array.isArray(payload.lineItems) ? payload.lineItems : [],
                diskon: Number(payload.diskon ?? 0),
                pendapatanOmset: Number(payload.pendapatanOmset ?? 0),
                charge: Number(payload.charge ?? 0),
                ppn: Number(payload.ppn ?? 0),
                totalTagihan: Number(payload.totalTagihan ?? 0),
                invoiceUrl: payload.invoiceUrl || '',
                reorderUrl: payload.reorderUrl || '',
            };
            this.showDetail = true;
        },
        closeDetail() {
            this.showDetail = false;
        },
        rupiah(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        },
        /** Sub total baris: harga satuan × jumlah − diskon (sama dengan perhitungan di server) */
        lineSubtotal(row) {
            const h = Number(row?.harga_satuan ?? 0);
            const q = Number(row?.qty ?? 0);
            const d = Number(row?.diskon ?? 0);
            return h * q - d;
        },
        ...omsetDetailModalState(omsetDetailUrl),
    };
}
</script>
<?php endif; ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.speedshop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/dashboard.blade.php ENDPATH**/ ?>
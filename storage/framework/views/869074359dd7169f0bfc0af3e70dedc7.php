<?php $__env->startSection('title', 'Pengembalian Dana BOP'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $fmt = fn ($v) => number_format((int) $v, 0, ',', '.');
    $stats = $stats ?? ['count' => 0, 'total_pengurangan' => 0];
    $filters = $filters ?? [];
    $fv = fn (string $k) => old($k, $filters[$k] ?? request($k, ''));
    $tableFilterClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $columnFilterKeys = [
        'filter_tgl_refund', 'filter_tgl_bop', 'filter_bop', 'filter_pengurangan',
        'filter_admin', 'filter_bop_efektif', 'filter_oleh',
    ];
    $bopFilterQueryKeys = array_merge(['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai'], $columnFilterKeys);
    $mode = $mode ?? 'bulanan';
    $periodeLabel = match ($mode) {
        'harian' => \Carbon\Carbon::parse($selectedDate ?? now())->translatedFormat('d M Y'),
        'bulanan' => \Carbon\Carbon::parse(($selectedMonth ?? now()->format('Y-m')).'-01')->translatedFormat('F Y'),
        'tahunan' => (string) ($selectedYear ?? now()->year),
        'range' => ($dari ?? now())->translatedFormat('d M Y').' – '.($sampai ?? now())->translatedFormat('d M Y'),
        default => now()->translatedFormat('F Y'),
    };
    $periodQuery = request()->only(['mode', 'tanggal', 'bulan', 'tahun', 'dari', 'sampai']);
    $exportQuery = $exportQuery ?? array_filter(array_merge($periodQuery, request()->only($columnFilterKeys)), fn ($v) => $v !== null && $v !== '');
    $hasActiveColumnFilters = collect(request()->only($columnFilterKeys))->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
    $clearColumnFilterUrl = route('speedshop.keuangan.bop-pengembalian.index', $periodQuery);
    $indexRoute = 'speedshop.keuangan.bop-pengembalian.index';
    $modalOnError = $errors->any();
    $bopCfg = [
        'showModal' => (bool) $modalOnError,
        'entriesUrl' => route('speedshop.keuangan.bop-pengembalian.entries'),
        'tanggalBop' => old('tanggal_bop', ''),
        'selectedEntryId' => old('rincian_biaya_entry_id', ''),
        'pengurangan' => (int) old('nominal_pengurangan', 0),
        'biayaAdmin' => (int) old('biaya_admin', 0),
    ];
?>

<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('partials.speedshop.dashboard-load-animations', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.dashboard-manager-template-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.work-order-create-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.ss-dashboard .bop-refund-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 700px) {
  .ss-dashboard .bop-refund-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.violet { background: rgba(139,92,246,0.10); color: #7C3AED; }
.ss-dashboard .pay-icon.teal { background: rgba(20,184,166,0.10); color: #14B8A6; }
.ss-wo-create .wo-file-input {
  width: 100%;
  border: 1px solid var(--ss-border, #e5e7eb);
  border-radius: 9px;
  background: #fff;
  padding: 8px 12px;
  font-size: 12px;
}
.ss-wo-create .wo-file-input::file-selector-button {
  margin-right: 12px;
  border: 0;
  border-radius: 8px;
  background: #f3f4f6;
  padding: 6px 12px;
  font-size: 11px;
  font-weight: 600;
  color: #374151;
  cursor: pointer;
}
.ss-wo-create .select2-container { width: 100% !important; }
.ss-wo-create .select2-container .select2-selection--single {
  height: 42px;
  border: 1px solid var(--ss-border, #e5e7eb);
  border-radius: 9px;
  display: flex;
  align-items: center;
  padding: 0 10px;
  font-size: 0.875rem;
  background: #fff;
}
.ss-wo-create .select2-container--default .select2-selection--single .select2-selection__rendered {
  line-height: 42px;
  padding-left: 6px;
  color: #111827;
}
.ss-wo-create .select2-container--default .select2-selection--single .select2-selection__placeholder {
  color: #9ca3af;
}
.ss-wo-create .select2-container--default .select2-selection--single .select2-selection__arrow {
  height: 42px;
  right: 10px;
}
.ss-wo-create .select2-container--default.select2-container--focus .select2-selection--single,
.ss-wo-create .select2-container--default.select2-container--open .select2-selection--single {
  border-color: #fca5a5;
  box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.1);
}
.ss-wo-create .select2-container--default .select2-dropdown {
  border: 1px solid var(--ss-border, #e5e7eb);
  border-radius: 9px;
  overflow: hidden;
  z-index: 60;
}
.ss-wo-create .select2-container--default .select2-search--dropdown .select2-search__field {
  border: 1px solid var(--ss-border, #e5e7eb);
  border-radius: 8px;
  padding: 6px 10px;
  font-size: 0.875rem;
}
.ss-wo-create .select2-container--default .select2-results__option {
  font-size: 0.8125rem;
  padding: 8px 12px;
}
.ss-wo-create .select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
  background-color: #fef2f2;
  color: #dc2626;
}
.ss-wo-create .select2-container--default.select2-container--disabled .select2-selection--single {
  background: #f9fafb;
  cursor: not-allowed;
}
</style>
<?php $__env->stopPush(); ?>

<script type="application/json" id="bop-pengembalian-cfg"><?php echo json_encode($bopCfg, 15, 512) ?></script>
<script>
function bopPengembalianForm(initial) {
    initial = initial || {};

    return {
        loading: false,
        showModal: !!initial.showModal,
        entriesUrl: initial.entriesUrl || '',
        tanggalBop: initial.tanggalBop || '',
        selectedEntryId: initial.selectedEntryId ? String(initial.selectedEntryId) : '',
        bopOptions: [],
        loadingBop: false,
        bopSelectReady: false,
        nominalAsli: 0,
        sisaDapatDikurangi: 0,
        pengurangan: Number(initial.pengurangan) || 0,
        biayaAdmin: Number(initial.biayaAdmin) || 0,
        init() {
            this.$nextTick(function () {
                requestAnimationFrame(function () {
                    this.$el.classList.add('is-ready');
                }.bind(this));
            }.bind(this));
            if (this.showModal && this.tanggalBop) {
                this.loadBopOptions();
            }
            if (this.showModal) {
                this.$nextTick(function () {
                    this.initBopSelect2();
                }.bind(this));
            }
        },
        openModal() {
            this.showModal = true;
            var self = this;
            this.$nextTick(function () {
                if (!self.bopSelectReady) {
                    self.initBopSelect2();
                }
                if (self.tanggalBop && self.bopOptions.length === 0) {
                    self.loadBopOptions();
                } else {
                    self.refreshBopSelectOptions();
                }
            });
        },
        initBopSelect2() {
            var self = this;
            this.$nextTick(function () {
                var el = document.getElementById('bop-entry-select');
                if (!el || typeof window.jQuery === 'undefined' || !window.jQuery.fn.select2) {
                    return;
                }
                var $el = window.jQuery(el);
                if ($el.data('select2')) {
                    $el.off('change.bopSelect');
                    $el.select2('destroy');
                }
                $el.select2({
                    width: '100%',
                    placeholder: '— Pilih entri BOP —',
                    allowClear: true,
                    dropdownParent: $el.closest('.wo-modal-panel'),
                });
                $el.on('change.bopSelect', function () {
                    self.selectedEntryId = String($el.val() || '');
                    self.onSelectEntry();
                });
                self.bopSelectReady = true;
                self.refreshBopSelectOptions();
            });
        },
        refreshBopSelectOptions() {
            var el = document.getElementById('bop-entry-select');
            if (!el) {
                return;
            }
            var current = this.selectedEntryId || '';
            el.innerHTML = '';
            var placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = '— Pilih entri BOP —';
            el.appendChild(placeholder);
            this.bopOptions.forEach(function (opt) {
                var option = document.createElement('option');
                option.value = String(opt.id);
                option.textContent = opt.label;
                el.appendChild(option);
            });
            if (current && !this.bopOptions.some(function (o) {
                return String(o.id) === String(current);
            })) {
                current = '';
                this.selectedEntryId = '';
                this.onSelectEntry();
            }
            if (typeof window.jQuery !== 'undefined') {
                var $el = window.jQuery(el);
                if ($el.data('select2')) {
                    $el.prop('disabled', this.loadingBop || this.bopOptions.length === 0);
                    $el.val(current).trigger('change.select2');
                    return;
                }
            }
            el.value = current;
            el.disabled = this.loadingBop || this.bopOptions.length === 0;
        },
        loadBopOptions: async function () {
            if (!this.tanggalBop) {
                this.bopOptions = [];
                this.selectedEntryId = '';
                this.onSelectEntry();
                this.refreshBopSelectOptions();
                return;
            }

            this.loadingBop = true;
            this.refreshBopSelectOptions();
            try {
                var url = new URL(this.entriesUrl, window.location.origin);
                url.searchParams.set('tanggal_bop', this.tanggalBop);
                var res = await fetch(url.toString(), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    this.bopOptions = [];
                    this.selectedEntryId = '';
                    this.onSelectEntry();
                    return;
                }
                var data = await res.json();
                this.bopOptions = data.entries || [];
                if (this.selectedEntryId && !this.bopOptions.some(function (o) {
                    return String(o.id) === String(this.selectedEntryId);
                }.bind(this))) {
                    this.selectedEntryId = '';
                }
                this.onSelectEntry();
            } catch (e) {
                this.bopOptions = [];
                this.selectedEntryId = '';
                this.onSelectEntry();
            } finally {
                this.loadingBop = false;
                this.refreshBopSelectOptions();
            }
        },
        onSelectEntry() {
            var selected = this.bopOptions.find(function (o) {
                return String(o.id) === String(this.selectedEntryId);
            }.bind(this));
            this.nominalAsli = selected ? Number(selected.nominal_asli) || 0 : 0;
            this.sisaDapatDikurangi = selected ? Number(selected.sisa_dapat_dikurangi) || 0 : 0;
        },
        totalSebenarnya() {
            if (this.nominalAsli <= 0) {
                return 0;
            }
            var kurang = (Number(this.pengurangan) || 0) + (Number(this.biayaAdmin) || 0);
            return Math.max(0, this.nominalAsli - kurang);
        },
        formatRp(n) {
            return new Intl.NumberFormat('id-ID').format(Math.max(0, Math.floor(Number(n) || 0)));
        },
    };
}
</script>

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="bopPengembalianForm(JSON.parse(document.getElementById('bop-pengembalian-cfg').textContent))"
    @keydown.escape.window="showModal = false"
>
<div class="ss-dash-content space-y-5">

    <?php echo $__env->make('partials.flash', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php echo $__env->make('partials.speedshop.dashboard-filter-strip', [
        'filterAction' => route($indexRoute),
        'dashboardFilterQueryKeys' => $bopFilterQueryKeys,
        'filterHiddenInputs' => array_filter(request()->only($columnFilterKeys), fn ($v) => $v !== null && $v !== ''),
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="bop-refund-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Penyesuaian BOP</span>
                <span class="pay-icon violet">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e($fmt($stats['count'])); ?></div>
            <p class="pay-period">Periode · <?php echo e($periodeLabel); ?></p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Pengurangan</span>
                <span class="pay-icon teal">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">Rp <?php echo e($fmt($stats['total_pengurangan'])); ?></div>
            <p class="pay-period">Pengurangan + biaya admin</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="section-hd" style="margin-bottom:0">
                    <h2>Riwayat Penyesuaian</h2>
                    <p>
                        Daftar pengembalian dana BOP · <?php echo e($periodeLabel); ?>

                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="<?php echo e(route('speedshop.keuangan.bop-pengembalian.export-excel', $exportQuery)); ?>" class="btn-export">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </a>
                    <a href="<?php echo e(route('speedshop.keuangan.bop-pengembalian.export-pdf', $exportQuery)); ?>" target="_blank" rel="noopener noreferrer" class="btn-export-pdf">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/><path d="M9 13h6M9 17h4M9 9h1"/></svg>
                        Export PDF
                    </a>
                    <button type="button" @click="openModal()" class="btn-primary inline-flex h-10 shrink-0 items-center gap-2 px-5">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Catat Pengembalian Dana BOP
                    </button>
                </div>
            </div>
        </div>
        <div class="overflow-x-auto">
            <form method="GET" action="<?php echo e(route($indexRoute)); ?>" @submit="loading = true">
                <input type="hidden" name="mode" value="<?php echo e($mode); ?>">
                <?php if($mode === 'harian'): ?>
                <input type="hidden" name="tanggal" value="<?php echo e($selectedDate); ?>">
                <?php elseif($mode === 'bulanan'): ?>
                <input type="hidden" name="bulan" value="<?php echo e($selectedMonth); ?>">
                <?php elseif($mode === 'tahunan'): ?>
                <input type="hidden" name="tahun" value="<?php echo e($selectedYear); ?>">
                <?php elseif($mode === 'range'): ?>
                <input type="hidden" name="dari" value="<?php echo e($selectedDari ?? $dari->format('Y-m-d')); ?>">
                <input type="hidden" name="sampai" value="<?php echo e($selectedSampai ?? $sampai->format('Y-m-d')); ?>">
                <?php endif; ?>
            <table class="w-full min-w-[960px] text-left text-sm">
                <thead>
                    <tr class="bg-[#f7f3f4]">
                        <th class="w-16 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                        <th class="min-w-[120px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tgl refund</th>
                        <th class="min-w-[110px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tgl BOP</th>
                        <th class="min-w-[160px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">BOP</th>
                        <th class="min-w-[120px] px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Pengurangan</th>
                        <th class="min-w-[100px] px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Admin</th>
                        <th class="min-w-[120px] px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">BOP efektif</th>
                        <th class="min-w-[120px] px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Oleh</th>
                    </tr>
                    <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                        <th class="px-5 py-2"></th>
                        <th class="px-5 py-2">
                            <input type="date" name="filter_tgl_refund" value="<?php echo e($fv('filter_tgl_refund')); ?>" class="<?php echo e($tableFilterClass); ?>" onchange="this.form.submit()" title="Tgl refund">
                        </th>
                        <th class="px-5 py-2">
                            <input type="date" name="filter_tgl_bop" value="<?php echo e($fv('filter_tgl_bop')); ?>" class="<?php echo e($tableFilterClass); ?>" onchange="this.form.submit()" title="Tgl BOP">
                        </th>
                        <th class="px-5 py-2">
                            <input type="text" name="filter_bop" value="<?php echo e($fv('filter_bop')); ?>" placeholder="No. akun / nama / ket…" class="<?php echo e($tableFilterClass); ?>" onchange="this.form.submit()">
                        </th>
                        <th class="px-5 py-2">
                            <input type="text" name="filter_pengurangan" value="<?php echo e($fv('filter_pengurangan')); ?>" placeholder="Cari nominal…" class="<?php echo e($tableFilterClass); ?> text-right" onchange="this.form.submit()">
                        </th>
                        <th class="px-5 py-2">
                            <input type="text" name="filter_admin" value="<?php echo e($fv('filter_admin')); ?>" placeholder="Cari nominal…" class="<?php echo e($tableFilterClass); ?> text-right" onchange="this.form.submit()">
                        </th>
                        <th class="px-5 py-2">
                            <input type="text" name="filter_bop_efektif" value="<?php echo e($fv('filter_bop_efektif')); ?>" placeholder="Cari nominal…" class="<?php echo e($tableFilterClass); ?> text-right" onchange="this.form.submit()">
                        </th>
                        <th class="px-5 py-2">
                            <input type="text" name="filter_oleh" value="<?php echo e($fv('filter_oleh')); ?>" placeholder="Cari nama…" class="<?php echo e($tableFilterClass); ?>" onchange="this.form.submit()">
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $adjustments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $adj): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $entry = $adj->rincianBiayaEntry;
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-4 tabular-nums text-gray-500"><?php echo e($adjustments->firstItem() + $i); ?></td>
                        <td class="px-5 py-4 text-gray-600"><?php echo e($adj->tanggal_pengembalian?->format('d/m/Y')); ?></td>
                        <td class="px-5 py-4 text-gray-600"><?php echo e($entry?->tanggal?->format('d/m/Y') ?? '—'); ?></td>
                        <td class="px-5 py-4">
                            <div class="font-mono text-xs font-semibold text-gray-800"><?php echo e($entry?->no_akun ?? '—'); ?></div>
                            <div class="text-sm text-gray-700"><?php echo e($entry?->nama_akun ?? '—'); ?></div>
                            <?php if($entry?->keterangan): ?>
                            <div class="text-xs text-gray-500"><?php echo e($entry->keterangan); ?></div>
                            <?php endif; ?>
                            <?php if($adj->buktiPublicUrl()): ?>
                            <a href="<?php echo e($adj->buktiPublicUrl()); ?>" target="_blank" rel="noopener" class="mt-1 inline-block text-xs font-medium text-red-700 underline">Bukti</a>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4 text-right font-semibold tabular-nums text-violet-800">Rp <?php echo e($fmt($adj->nominal_pengurangan)); ?></td>
                        <td class="px-5 py-4 text-right tabular-nums text-gray-700"><?php echo e($adj->biaya_admin > 0 ? 'Rp '.$fmt($adj->biaya_admin) : '—'); ?></td>
                        <td class="px-5 py-4 text-right font-semibold tabular-nums text-emerald-800">Rp <?php echo e($fmt($entry ? $entry->nominalEfektif() : 0)); ?></td>
                        <td class="px-5 py-4 text-sm text-gray-600"><?php echo e($adj->createdByUser?->name ?? '—'); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center text-gray-400">
                            <p class="text-sm font-medium text-gray-700">Belum ada penyesuaian BOP pada periode ini</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </form>
        </div>
        <?php if($adjustments->hasPages()): ?>
        <div class="border-t border-gray-100 px-5 py-4"><?php echo e($adjustments->links()); ?></div>
        <?php endif; ?>
    </div>
</div>

<div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
    <div class="absolute inset-0 bg-black/40" @click="showModal = false"></div>
    <div class="wo-modal-panel relative max-h-[90vh] w-full max-w-3xl overflow-y-auto !p-6 sm:!p-8" @click.stop>
        <div class="flex items-start justify-between gap-3 border-b border-gray-100 pb-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Catat Pengembalian Dana BOP</h2>
                <p class="mt-1 text-sm text-gray-500">Kurangi nominal BOP yang sudah tercatat apabila ada refund marketplace, kelebihan bayar gaji, dll.</p>
            </div>
            <button type="button" @click="showModal = false" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-700" aria-label="Tutup">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="<?php echo e(route('speedshop.keuangan.bop-pengembalian.store')); ?>" enctype="multipart/form-data" class="mt-4 grid gap-4 md:grid-cols-2">
            <?php echo csrf_field(); ?>
            <?php $__currentLoopData = array_merge($periodQuery, request()->only($columnFilterKeys)); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if($v !== null && $v !== ''): ?>
            <input type="hidden" name="<?php echo e($k); ?>" value="<?php echo e($v); ?>">
            <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Tanggal BOP <span class="text-red-500">*</span></label>
                <input type="date" name="tanggal_bop" x-model="tanggalBop" @change="loadBopOptions()" required class="wo-input wo-native">
                <?php $__errorArgs = ['tanggal_bop'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Pilih BOP <span class="text-red-500">*</span></label>
                <select id="bop-entry-select" name="rincian_biaya_entry_id" required class="wo-input wo-native">
                    <option value="">— Pilih entri BOP —</option>
                </select>
                <?php $__errorArgs = ['rincian_biaya_entry_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <p x-show="loadingBop" class="mt-1 text-xs text-gray-500">Memuat daftar BOP…</p>
            </div>

            <div x-show="tanggalBop && !loadingBop && bopOptions.length === 0" x-cloak class="md:col-span-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Tidak ada BOP tercatat pada tanggal tersebut yang masih bisa disesuaikan.
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Tanggal pengembalian dana <span class="text-red-500">*</span></label>
                <input type="date" name="tanggal_pengembalian" value="<?php echo e(old('tanggal_pengembalian', now()->toDateString())); ?>" required class="wo-input wo-native">
                <?php $__errorArgs = ['tanggal_pengembalian'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Nominal pengurangan <span class="text-red-500">*</span></label>
                <input type="number" name="nominal_pengurangan" min="1" x-model.number="pengurangan" required class="wo-input wo-native" placeholder="Refund / kelebihan bayar">
                <?php $__errorArgs = ['nominal_pengurangan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Biaya admin</label>
                <input type="number" name="biaya_admin" min="0" x-model.number="biayaAdmin" class="wo-input wo-native" placeholder="0">
                <?php $__errorArgs = ['biaya_admin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-gray-600">Total BOP sebenarnya</label>
                <div class="flex h-11 items-center rounded-xl border border-violet-200 bg-violet-50 px-3 text-sm font-bold tabular-nums text-violet-900">
                    Rp <span x-text="formatRp(totalSebenarnya())"></span>
                </div>
                <p class="mt-1 text-[11px] text-gray-500" x-show="nominalAsli > 0">
                    Nominal asli Rp <span x-text="formatRp(nominalAsli)"></span>
                    · Sisa bisa dikurangi Rp <span x-text="formatRp(sisaDapatDikurangi)"></span>
                </p>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-gray-600">Catatan</label>
                <textarea name="catatan" rows="2" maxlength="1000" class="wo-input wo-native min-h-[4.5rem] resize-y" placeholder="Contoh: Refund Shopee Pay barang kurang"><?php echo e(old('catatan')); ?></textarea>
            </div>
            <div class="md:col-span-2">
                <label class="wo-label">Bukti pengembalian <span class="req">*</span></label>
                <input type="file"
                       name="bukti"
                       required
                       accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
                       class="wo-file-input">
                <p class="mt-1 text-[11px] text-gray-500">Format JPG, PNG, WEBP, atau PDF · maks. 5 MB</p>
                <?php $__errorArgs = ['bukti'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-xs text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="md:col-span-2 flex justify-end gap-2 border-t border-gray-100 pt-4">
                <button type="button" @click="showModal = false" class="btn-secondary px-4 py-2.5">Batal</button>
                <button type="submit" class="btn-primary inline-flex items-center gap-2 px-5 py-2.5" :disabled="!selectedEntryId || bopOptions.length === 0">
                    Simpan penyesuaian
                </button>
            </div>
        </form>
    </div>
</div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.speedshop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/keuangan/bop-pengembalian/index.blade.php ENDPATH**/ ?>
<?php $__env->startSection('title', 'Rekening'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $tableFilterClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $asOfLabel = \Carbon\Carbon::parse($asOfYmd)->format('d/m/Y');
    $editRekeningForForm = ($errors->any() && old('rekening_modal') === 'edit' && old('rekening_edit_id'))
        ? ($rekenings->firstWhere('id', (int) old('rekening_edit_id')) ?? new \App\Models\SpeedshopKeuanganRekening())
        : new \App\Models\SpeedshopKeuanganRekening();
    $rekeningCreateStub = new \App\Models\SpeedshopKeuanganRekening();
    $ledgerRekeningCol = \Illuminate\Support\Facades\Schema::hasColumn('speedshop_finance_ledgers', 'speedshop_keuangan_rekening_id');
    $hasKeteranganCol = \Illuminate\Support\Facades\Schema::hasColumn('speedshop_keuangan_rekenings', 'keterangan');
    $rekeningTableColCount = 1
        + (($hasRekeningJenisColumn ?? false) ? 1 : 0)
        + 4
        + (($hasKeteranganCol ?? false) ? 1 : 0)
        + 1;
    $hasActiveFilters = request()->hasAny(['nama', 'nama_bank', 'nomor', 'jenis']);
    $rekeningCrudCfg = [
        'errorsCreate' => (bool) ($errors->any() && old('rekening_modal') === 'create'),
        'errorsEdit' => (bool) ($errors->any() && old('rekening_modal') === 'edit'),
        'rekenings' => $rekeningsJson ?? [],
        'openTambah' => (bool) ($bukaModalTambah ?? false),
        'openUbahId' => $bukaModalUbahId,
        'hasJenis' => (bool) ($hasRekeningJenisColumn ?? false),
        'saldoKoreksiOpenId' => ($ledgerRekeningCol && auth()->user()->isAccounting() && ($errors->has('saldo_buku_target') || $errors->has('sampai_tanggal')) && old('koreksi_for_rekening'))
            ? (int) old('koreksi_for_rekening')
            : null,
    ];
?>
<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('partials.speedshop.dashboard-load-animations', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.dashboard-manager-template-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php echo $__env->make('partials.speedshop.work-order-create-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
.ss-dashboard .rek-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 900px) {
  .ss-dashboard .rek-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.cyan { background: rgba(6,182,212,0.10); color: #0891B2; }
.ss-dashboard .pay-icon.violet { background: rgba(139,92,246,0.10); color: #7C3AED; }
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: #D97706; }
.ss-dashboard .pay-icon.teal { background: rgba(20,184,166,0.10); color: #14B8A6; }
</style>
<?php $__env->stopPush(); ?>

<script type="application/json" id="rekening-crud-config"><?php echo json_encode($rekeningCrudCfg, 15, 512) ?></script>
<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="speedshopRekeningCrud(JSON.parse(document.getElementById('rekening-crud-config').textContent))"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    @keydown.escape.window="modalCreate = false; modalEdit = false; modalSaldo = false"
>
<div class="ss-dash-content space-y-5">

    <?php if (! ($isOwnerKeuangan ?? false)): ?>
    <div class="rek-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Rekening Utama</span>
                <span class="pay-icon cyan">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </span>
            </div>
            <div class="pay-amount">Rp <?php echo e(number_format($balances['rekening_utama'], 0, ',', '.')); ?></div>
            <p class="pay-period">Kumulatif sampai <?php echo e($asOfLabel); ?></p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Kas Besar</span>
                <span class="pay-icon violet">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </span>
            </div>
            <div class="pay-amount">Rp <?php echo e(number_format($balances['cash_besar'], 0, ',', '.')); ?></div>
            <p class="pay-period">Kumulatif sampai <?php echo e($asOfLabel); ?></p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Kas Kecil</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2h-1"/></svg>
                </span>
            </div>
            <div class="pay-amount">Rp <?php echo e(number_format($balances['laci'], 0, ',', '.')); ?></div>
            <p class="pay-period">Kumulatif sampai <?php echo e($asOfLabel); ?></p>
        </div>
    </div>
    <?php else: ?>
    <div class="rek-summary-grid ss-dash-enter" style="--ss-delay: 80ms; grid-template-columns: minmax(0, 1fr); max-width: 320px;">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Rekening</span>
                <span class="pay-icon teal">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </span>
            </div>
            <div class="pay-amount"><?php echo e(number_format($rekenings->count(), 0, ',', '.')); ?></div>
            <p class="pay-period">Rekening owner terdaftar</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">
                    <?php if($isOwnerKeuangan ?? false): ?>
                    Data rekening owner
                    <?php else: ?>
                    Data rekening cabang
                    <?php endif; ?>
                </h2>
                <?php if($rekeningBankTableReady ?? false): ?>
                <?php if (! (auth()->user()?->isModifikasiOriHeadAudit())): ?>
                <button type="button" @click="openCreate()" class="btn-primary inline-flex h-10 shrink-0 items-center gap-2 px-5 finance-mutate">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Tambah rekening
                </button>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <p class="mt-1 text-[13px] text-gray-500">
                <?php if($isOwnerKeuangan ?? false): ?>
                Rekening modal owner untuk pemberian modal ke speedshop
                <?php else: ?>
                Saldo kumulatif per <?php echo e($asOfLabel); ?>

                <?php endif; ?>
                <?php if($hasActiveFilters): ?>
                · <a href="<?php echo e(route('speedshop.keuangan.rekening')); ?>" class="font-medium text-red-600 transition hover:text-red-700">Hapus filter</a>
                <?php endif; ?>
            </p>
        </div>

        <?php if(! ($rekeningBankTableReady ?? false)): ?>
        <div class="mx-5 mb-5 mt-4 rounded-xl border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm text-amber-950">
            Tabel daftar rekening bank belum ada. Jalankan <code class="rounded-md border border-amber-200 bg-white px-1.5 py-0.5 text-xs">php artisan migrate</code> untuk mengaktifkan CRUD rekening.
        </div>
        <?php endif; ?>

        <?php if($rekeningBankTableReady ?? false): ?>
        <?php $rekeningFilterFormId = 'speedshop-rekening-filters'; ?>
        <form id="<?php echo e($rekeningFilterFormId); ?>" method="GET" action="<?php echo e(route('speedshop.keuangan.rekening')); ?>" class="hidden" aria-hidden="true"></form>
        <div class="overflow-x-auto">
            <table class="w-full min-w-max text-left text-sm">
                <thead>
                    <tr class="bg-[#f7f3f4]">
                        <th class="w-16 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                        <?php if($hasRekeningJenisColumn ?? false): ?>
                        <th class="w-40 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Jenis</th>
                        <?php endif; ?>
                        <th class="w-48 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama rekening</th>
                        <th class="w-40 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama bank</th>
                        <th class="w-44 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nomor rekening</th>
                        <?php if($hasKeteranganCol): ?>
                        <th class="min-w-[10rem] max-w-xs px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Keterangan</th>
                        <?php endif; ?>
                        <th class="w-40 px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Saldo (buku)</th>
                        <th class="w-28 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500"></th>
                    </tr>
                    <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                        <th class="px-5 py-2"></th>
                        <?php if($hasRekeningJenisColumn ?? false): ?>
                        <th class="px-5 py-2">
                            <select name="jenis" form="<?php echo e($rekeningFilterFormId); ?>" class="<?php echo e($tableFilterClass); ?>" onchange="document.getElementById('<?php echo e($rekeningFilterFormId); ?>').submit()">
                                <option value="" <?php if(request('jenis', '') === ''): echo 'selected'; endif; ?>>Semua jenis</option>
                                <option value="<?php echo e(\App\Models\SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA); ?>" <?php if(request('jenis') === \App\Models\SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA): echo 'selected'; endif; ?>>Rekening Utama</option>
                                <option value="<?php echo e(\App\Models\SpeedshopKeuanganRekening::JENIS_KAS_BESAR); ?>" <?php if(request('jenis') === \App\Models\SpeedshopKeuanganRekening::JENIS_KAS_BESAR): echo 'selected'; endif; ?>>Kas Besar</option>
                            </select>
                        </th>
                        <?php endif; ?>
                        <th class="px-5 py-2">
                            <input type="text" name="nama" form="<?php echo e($rekeningFilterFormId); ?>" value="<?php echo e(request('nama')); ?>" placeholder="Cari nama…" class="<?php echo e($tableFilterClass); ?>" onchange="document.getElementById('<?php echo e($rekeningFilterFormId); ?>').submit()">
                        </th>
                        <th class="px-5 py-2">
                            <input type="text" name="nama_bank" form="<?php echo e($rekeningFilterFormId); ?>" value="<?php echo e(request('nama_bank')); ?>" placeholder="Cari bank…" class="<?php echo e($tableFilterClass); ?>" onchange="document.getElementById('<?php echo e($rekeningFilterFormId); ?>').submit()">
                        </th>
                        <th class="px-5 py-2">
                            <input type="text" name="nomor" form="<?php echo e($rekeningFilterFormId); ?>" value="<?php echo e(request('nomor')); ?>" placeholder="Cari nomor…" class="<?php echo e($tableFilterClass); ?> font-mono tabular-nums" onchange="document.getElementById('<?php echo e($rekeningFilterFormId); ?>').submit()">
                        </th>
                        <?php if($hasKeteranganCol): ?>
                        <th class="px-5 py-2"></th>
                        <?php endif; ?>
                        <th class="px-5 py-2"></th>
                        <th class="px-5 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $rekenings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 tabular-nums text-gray-500"><?php echo e($i + 1); ?></td>
                        <?php if($hasRekeningJenisColumn ?? false): ?>
                        <td class="px-5 py-4 align-top">
                            <?php $j = $r->jenis ?? \App\Models\SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA; ?>
                            <?php if($j === \App\Models\SpeedshopKeuanganRekening::JENIS_KAS_BESAR): ?>
                            <span class="inline-flex rounded-lg border border-violet-200 bg-violet-50 px-2 py-0.5 text-xs font-semibold text-violet-700">Kas Besar</span>
                            <?php else: ?>
                            <span class="inline-flex rounded-lg border border-sky-200 bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700">Rekening Utama</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td class="px-5 py-4 align-top font-semibold text-gray-900"><?php echo e($r->nama); ?></td>
                        <td class="px-5 py-4 align-top text-gray-600"><?php echo e($r->nama_bank ?: '—'); ?></td>
                        <td class="px-5 py-4 align-top font-mono text-gray-800 tabular-nums"><?php echo e($r->nomor_rekening); ?></td>
                        <?php if($hasKeteranganCol): ?>
                        <td class="max-w-xs px-5 py-4 align-top text-sm text-gray-600">
                            <?php if(filled($r->keterangan)): ?>
                            <p class="line-clamp-3 whitespace-pre-wrap break-words" title="<?php echo e(e($r->keterangan)); ?>"><?php echo e($r->keterangan); ?></p>
                            <?php else: ?>
                            <span class="text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td class="px-5 py-4 align-top text-right">
                            <?php
                                $j = $r->jenis ?? \App\Models\SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA;
                                $saldoAngka = (int) ($saldoBukuPerRekening[$r->id] ?? 0);
                                $saldoWarna = $j === \App\Models\SpeedshopKeuanganRekening::JENIS_KAS_BESAR ? 'text-violet-950' : 'text-sky-900';
                                $bolehKoreksiSaldo = auth()->user()->isAccounting() && $ledgerRekeningCol;
                            ?>
                            <?php if($bolehKoreksiSaldo): ?>
                            <button
                                type="button"
                                id="saldo-trigger-<?php echo e($r->id); ?>"
                                @click.stop="openSaldoKoreksiById(<?php echo e((int) $r->id); ?>)"
                                class="group w-full rounded-lg py-0.5 text-right focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-2"
                                title="Koreksi saldo buku"
                            >
                                <span class="font-semibold tabular-nums <?php echo e($saldoWarna); ?> group-hover:underline decoration-dotted underline-offset-2">Rp <?php echo e(number_format($saldoAngka, 0, ',', '.')); ?></span>
                            </button>
                            <?php else: ?>
                            <span class="font-semibold tabular-nums <?php echo e($saldoWarna); ?>">Rp <?php echo e(number_format($saldoAngka, 0, ',', '.')); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <?php if (! (auth()->user()?->isModifikasiOriHeadAudit())): ?>
                                <button type="button" @click.prevent="openEditById(<?php echo e((int) $r->id); ?>)" class="wo-icon-btn finance-mutate bg-gray-600 text-white hover:bg-gray-700" title="Edit">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                                <button type="button" @click.prevent="hapusRekeningById(<?php echo e((int) $r->id); ?>)" class="wo-icon-btn finance-mutate bg-red-600 text-white hover:bg-red-700" title="Hapus">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="<?php echo e($rekeningTableColCount); ?>" class="px-5 py-12 text-center text-sm text-gray-500">
                            <p class="font-medium text-gray-700">Belum ada rekening<?php echo e($hasActiveFilters ? ' yang cocok dengan filter' : ''); ?>.</p>
                            <p class="mt-1 text-xs text-gray-400">
                                <?php if($isOwnerKeuangan ?? false): ?>
                                Tambah rekening owner dengan tombol di atas untuk persiapan pemberian modal ke speedshop.
                                <?php else: ?>
                                Tambah rekening dengan tombol di atas atau ubah filter kolom.
                                <?php endif; ?>
                            </p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php $__currentLoopData = $rekenings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <form method="POST" action="<?php echo e(route('speedshop.keuangan.rekening.destroy', $r)); ?>" id="form-hapus-rekening-<?php echo e($r->id); ?>" class="hidden"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?></form>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
    </div>
</div>


<div x-show="modalCreate" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="modalCreate = false">
    <div x-show="modalCreate" x-transition.opacity class="fixed inset-0 bg-black/50" @click="modalCreate = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="modalCreate" x-transition class="wo-modal-panel relative max-h-[90vh] w-full max-w-lg overflow-y-auto !p-6 sm:!p-8" @click.stop>
            <h3 class="text-lg font-bold text-gray-900">Tambah rekening</h3>
            <form x-ref="formCreate" method="POST" action="<?php echo e(route('speedshop.keuangan.rekening.store')); ?>" class="mt-6 space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="rekening_modal" value="create">
                <?php echo $__env->make('speedshop.keuangan.rekening._form', ['rekening' => $rekeningCreateStub, 'idPrefix' => 'create-'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
                    <button type="button" class="btn-secondary px-5 py-2.5" @click="modalCreate = false">Batal</button>
                    <button type="submit" class="btn-primary px-5 py-2.5">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div x-show="modalEdit" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="modalEdit = false">
    <div x-show="modalEdit" x-transition.opacity class="fixed inset-0 bg-black/50" @click="modalEdit = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="modalEdit" x-transition class="wo-modal-panel relative max-h-[90vh] w-full max-w-lg overflow-y-auto !p-6 sm:!p-8" @click.stop>
            <h3 class="text-lg font-bold text-gray-900">Ubah rekening</h3>
            <form
                x-ref="formEdit"
                method="POST"
                action="<?php echo e(($errors->any() && old('rekening_modal') === 'edit' && old('rekening_edit_id')) ? route('speedshop.keuangan.rekening.update', old('rekening_edit_id')) : '#'); ?>"
                class="mt-6 space-y-4"
            >
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                <input type="hidden" name="rekening_modal" value="edit">
                <input type="hidden" name="rekening_edit_id" value="<?php echo e(old('rekening_edit_id', '')); ?>">
                <?php echo $__env->make('speedshop.keuangan.rekening._form', ['rekening' => $editRekeningForForm, 'idPrefix' => 'edit-'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 pt-6">
                    <button type="button" class="wo-icon-btn bg-red-600 text-white hover:bg-red-700" title="Hapus rekening" @click="confirmHapus()">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                    <button type="button" class="btn-secondary px-5 py-2.5" @click="modalEdit = false">Batal</button>
                    <button type="submit" class="btn-primary px-5 py-2.5">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if($ledgerRekeningCol && auth()->user()->isAccounting()): ?>

<div x-show="modalSaldo" x-cloak class="fixed inset-0 z-[60] overflow-y-auto" @keydown.escape.window="modalSaldo = false">
    <div x-show="modalSaldo" x-transition.opacity class="fixed inset-0 bg-black/50" @click="modalSaldo = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="modalSaldo" x-transition class="wo-modal-panel relative w-full max-w-md !p-6 sm:!p-8" @click.stop>
            <h3 class="text-lg font-bold text-gray-900">Koreksi saldo buku</h3>
            <p class="mt-1 text-sm text-gray-500" x-text="saldoModalSubtitle"></p>
            <form x-ref="formSaldoKoreksi" method="POST" action="#" class="mt-6 space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="sampai_tanggal" value="<?php echo e($asOfYmd); ?>">
                <input type="hidden" name="koreksi_for_rekening" :value="saldoModalRekeningId">
                <div>
                    <input
                        id="saldo-buku-target-modal"
                        x-ref="saldoBukuTargetInput"
                        type="number"
                        name="saldo_buku_target"
                        min="0"
                        step="1"
                        value="<?php echo e(old('saldo_buku_target')); ?>"
                        required
                        placeholder="Saldo buku target (Rp)"
                        aria-label="Saldo buku target (Rp)"
                        class="wo-input wo-native tabular-nums"
                    >
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
                    <button type="button" class="btn-secondary px-5 py-2.5" @click="modalSaldo = false">Batal</button>
                    <button type="submit" class="btn-primary px-5 py-2.5">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function speedshopRekeningCrud(cfg) {
    return {
        modalCreate: false,
        modalEdit: false,
        modalSaldo: false,
        saldoModalRekeningId: null,
        saldoModalSubtitle: '',
        rekenings: cfg.rekenings || [],
        editingId: null,
        init() {
            if (cfg.errorsCreate) this.modalCreate = true;
            else if (cfg.openTambah) this.openCreateFresh();
            if (cfg.errorsEdit) this.modalEdit = true;
            else if (cfg.openUbahId) {
                const row = this.rekenings.find((r) => Number(r.id) === Number(cfg.openUbahId));
                if (row) this.openEdit(row);
            }
            if (cfg.saldoKoreksiOpenId != null && cfg.saldoKoreksiOpenId > 0) {
                this.$nextTick(() => this.openSaldoKoreksiById(cfg.saldoKoreksiOpenId, { preserveInput: true }));
            }
        },
        openSaldoKoreksiById(id, opts = {}) {
            const preserveInput = opts && opts.preserveInput === true;
            const row = this.rekenings.find((r) => Number(r.id) === Number(id));
            if (!row || !row.saldoBukuUrl) {
                return;
            }
            this.modalCreate = false;
            this.modalEdit = false;
            this.saldoModalRekeningId = Number(id);
            const parts = [row.nama || 'Rekening'];
            if (row.nomor_rekening) {
                parts.push(String(row.nomor_rekening));
            }
            if (row.nama_bank) {
                parts.push(String(row.nama_bank));
            }
            this.saldoModalSubtitle = parts.join(' · ');
            this.$nextTick(() => {
                if (this.$refs.formSaldoKoreksi) {
                    this.$refs.formSaldoKoreksi.action = row.saldoBukuUrl;
                }
                const inp = this.$refs.saldoBukuTargetInput;
                if (inp) {
                    inp.placeholder = String(row.saldoBuku ?? 0);
                    if (!preserveInput) {
                        inp.value = '';
                    }
                }
            });
            this.modalSaldo = true;
        },
        openCreateFresh() {
            this.modalSaldo = false;
            this.modalEdit = false;
            this.modalCreate = true;
            if (!cfg.errorsCreate && this.$refs.formCreate) {
                this.$refs.formCreate.reset();
                const m = this.$refs.formCreate.querySelector('input[name="rekening_modal"]');
                if (m) m.value = 'create';
            }
        },
        openCreate() {
            this.openCreateFresh();
        },
        openEditById(id) {
            const row = this.rekenings.find((r) => Number(r.id) === Number(id));
            if (row) this.openEdit(row);
        },
        openEdit(row) {
            if (!row || !this.$refs.formEdit) return;
            this.modalSaldo = false;
            this.modalCreate = false;
            this.editingId = row.id;
            this.$refs.formEdit.action = row.updateUrl;
            const hid = this.$refs.formEdit.querySelector('input[name="rekening_edit_id"]');
            if (hid) hid.value = String(row.id);
            const names = ['nama', 'nama_bank', 'nomor_rekening', 'keterangan'];
            names.forEach((k) => {
                const el = this.$refs.formEdit.querySelector(`[name="${k}"]`);
                if (el) el.value = row[k] != null ? String(row[k]) : '';
            });
            const je = this.$refs.formEdit.querySelector('[name="jenis"]');
            if (je && row.jenis) je.value = row.jenis;
            this.modalEdit = true;
        },
        confirmHapus() {
            if (!this.editingId) return;
            this.hapusRekeningById(this.editingId);
        },
        async hapusRekeningById(id) {
            if (!id) return;
            let ok = false;
            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: 'Hapus rekening?',
                    text: 'Data yang dihapus tidak dapat dikembalikan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal',
                });
                ok = result.isConfirmed;
            } else {
                ok = confirm('Hapus rekening ini? Tindakan ini tidak dapat dibatalkan.');
            }
            if (!ok) return;
            const f = document.getElementById('form-hapus-rekening-' + id);
            if (f) f.submit();
        },
    };
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.speedshop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/keuangan/rekening/index.blade.php ENDPATH**/ ?>
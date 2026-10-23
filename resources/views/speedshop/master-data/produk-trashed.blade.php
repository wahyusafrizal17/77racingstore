@extends($mp['layout'] ?? 'layouts.speedshop')

@php
    $mp = array_merge([
        'is_part_luar' => false,
        'route_index' => 'speedshop.master.produk',
        'route_trashed' => 'speedshop.master.produk.trashed',
        'route_trashed_unused' => 'speedshop.master.produk.trashed.unused',
        'route_trashed_unused_bulk_delete' => 'speedshop.master.produk.trashed.unused-bulk-delete',
        'route_restore_all' => 'speedshop.master.produk.restore-all-trashed',
        'trashed_base_url' => url('speedshop/master/produk/trashed'),
        'trashed_title' => 'Arsip Produk',
        'trashed_subtitle' => 'Produk yang diarsipkan dari master outlet — data tidak dihapus dari database',
        'trashed_back_label' => '← Database Produk',
        'trashed_period_label' => 'Produk diarsipkan',
    ], $mp ?? []);
    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $hasActiveFilters = request()->filled('kode') || request()->filled('nama') || request()->filled('category_id') || request()->filled('brand_id');
@endphp

@section('title', $mp['trashed_title'])

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .pt-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 900px) {
  .ss-dashboard .pt-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: #D97706; }
.ss-dashboard .pay-icon.teal { background: rgba(20,184,166,0.10); color: #14B8A6; }
.ss-dashboard .pay-icon.violet { background: rgba(139,92,246,0.10); color: #7C3AED; }
</style>
@endpush

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="produkTrashed()"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
>
<div class="ss-dash-content space-y-5">
    <div class="flex flex-col gap-3 ss-dash-enter sm:flex-row sm:items-start sm:justify-between" style="--ss-delay: 0ms">
        <div>
            <h1 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">{{ $mp['trashed_title'] }}</h1>
            <p class="mt-1 text-[13px] text-gray-500">{{ $mp['trashed_subtitle'] }}</p>
        </div>
        <a href="{{ route($mp['route_index']) }}" class="btn-secondary shrink-0">{{ $mp['trashed_back_label'] }}</a>
    </div>


    <div class="pt-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Arsip</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($totalTrashedCount ?? 0, 0, ',', '.') }}</div>
            <p class="pay-period">{{ $mp['trashed_period_label'] }}</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Hasil Filter</span>
                <span class="pay-icon teal">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($items->total(), 0, ',', '.') }}</div>
            <p class="pay-period">{{ $hasActiveFilters ? 'Sesuai filter aktif' : 'Semua arsip' }}</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Kategori</span>
                <span class="pay-icon violet">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($categories->count(), 0, ',', '.') }}</div>
            <p class="pay-period">Filter kategori</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Daftar Arsip</h2>
                <p class="mt-1 text-[13px] text-gray-500">
                    Pulihkan {{ $mp['is_part_luar'] ? 'part luar' : 'produk' }} untuk mengembalikannya ke master outlet.
                </p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                @if($canManageUnusedCleanup ?? false)
                <button type="button" @click="openUnusedModal()"
                        class="btn-secondary inline-flex h-10 items-center gap-2 border-rose-200 bg-rose-50 px-4 text-rose-800 hover:bg-rose-100"
                        title="Lihat produk arsip yang tidak dipakai di outlet ini dan boleh dibersihkan">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Produk tidak terpakai
                </button>
                @endif
                <button type="button" @click.prevent="restoreAll()"
                        @if(($totalTrashedCount ?? 0) === 0) disabled @endif
                        class="btn-secondary inline-flex h-10 items-center gap-2 border-emerald-300 bg-emerald-600 px-4 text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Pulihkan semua ({{ number_format((int) ($totalTrashedCount ?? 0), 0, ',', '.') }})
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <form method="GET" action="{{ route($mp['route_trashed']) }}">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="w-16 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Produk</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kategori</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Brand</th>
                            <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Stok outlet</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tanggal arsip</th>
                            <th class="w-36 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500"></th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="kode" value="{{ request('kode') }}" placeholder="Cari kode..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input name="nama" value="{{ request('nama') }}" placeholder="Cari nama..." class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <select name="category_id" class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}" @selected((string) request('category_id') === (string) $c->id)>{{ $c->nama }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-5 py-2">
                                <select name="brand_id" class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                                    <option value="">Semua</option>
                                    @foreach($brands as $b)
                                        <option value="{{ $b->id }}" @selected((string) request('brand_id') === (string) $b->id)>{{ $b->nama }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($items as $i => $p)
                    @php
                        $archivedAt = $p->speedshopMasterArchives->first()?->archived_at ?? $p->hidden_from_warehouse_master_at;
                    @endphp
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 tabular-nums text-gray-500">{{ $items->firstItem() + $i }}</td>
                        <td class="px-5 py-4 font-mono text-sm font-semibold text-gray-700">{{ $p->speedshopMasterArchives->first()?->original_kode_produk ?: $p->kode_produk }}</td>
                        <td class="px-5 py-4 font-semibold text-gray-900">{{ $p->nama_produk }}</td>
                        <td class="px-5 py-4 text-gray-700">{{ $p->category?->nama ?? '—' }}</td>
                        <td class="px-5 py-4 text-gray-700">{{ $p->brand?->nama ?? '—' }}</td>
                        <td class="px-5 py-4 text-right tabular-nums text-gray-700">
                            @if($ssid ?? null)
                                {{ number_format((int) ($p->speedshopStocks->first()->jumlah ?? 0), 0, ',', '.') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-5 py-4 text-gray-600">
                            {{ $archivedAt ? $archivedAt->timezone(config('app.timezone'))->format('d/m/Y H:i') : '—' }}
                        </td>
                        <td class="px-5 py-4">
                            <button type="button" @click.prevent="restore({{ $p->id }}, {{ json_encode(($p->speedshopMasterArchives->first()?->original_kode_produk ?: $p->kode_produk).' — '.$p->nama_produk) }})"
                                    class="btn-secondary inline-flex h-8 items-center gap-1.5 border-emerald-300 bg-emerald-600 px-3 text-xs text-white hover:bg-emerald-700">
                                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Pulihkan
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center text-gray-400">
                            @if($hasActiveFilters)
                            Tidak ada arsip sesuai filter.
                            @else
                            Tidak ada entri arsip.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </form>
        </div>

        @if($items->hasPages())
        <div class="border-t border-gray-100 px-5 py-4">{{ $items->links() }}</div>
        @endif
    </div>
</div>

    @if($canManageUnusedCleanup ?? false)
        @include('speedshop.master-data.partials.produk-trashed-unused-modal')
    @endif
</div>
@endsection

@push('scripts')
<script>
function produkTrashed() {
    const restoreUrl = (id) => @json(rtrim($mp['trashed_base_url'], '/')) + '/' + id + '/restore';
    const restoreAllUrl = @json(route($mp['route_restore_all']));
    const unusedListUrl = @json(route($mp['route_trashed_unused']));
    const unusedBulkDeleteUrl = @json(route($mp['route_trashed_unused_bulk_delete']));
    const itemLabel = @json($mp['is_part_luar'] ? 'part luar' : 'produk');
    const totalTrashed = {{ (int) ($totalTrashedCount ?? 0) }};
    const canManageUnused = @json($canManageUnusedCleanup ?? false);
    return {
        showUnusedModal: false,
        unusedLoading: false,
        unusedItems: [],
        unusedTotal: 0,
        unusedPreviewTruncated: false,
        bulkDeletingUnused: false,
        async openUnusedModal() {
            if (!canManageUnused) return;
            this.showUnusedModal = true;
            this.unusedLoading = true;
            this.unusedItems = [];
            this.unusedTotal = 0;
            this.unusedPreviewTruncated = false;
            try {
                const res = await fetch(unusedListUrl, { headers: { Accept: 'application/json' } });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    Swal.fire({ title: 'Gagal', text: data.message || 'Tidak dapat memuat daftar produk.', icon: 'error' });
                    this.showUnusedModal = false;
                    return;
                }
                this.unusedItems = data.items || [];
                this.unusedTotal = data.total ?? this.unusedItems.length;
                this.unusedPreviewTruncated = !!data.preview_truncated;
            } catch (e) {
                Swal.fire({ title: 'Gagal', text: 'Tidak dapat memuat daftar produk.', icon: 'error' });
                this.showUnusedModal = false;
            } finally {
                this.unusedLoading = false;
            }
        },
        async bulkDeleteUnusedAll() {
            if (this.unusedTotal === 0) {
                Swal.fire({ title: 'Kosong', text: 'Tidak ada produk yang bisa dibersihkan.', icon: 'info' });
                return;
            }
            const r1 = await Swal.fire({
                title: 'Bersihkan produk tidak terpakai?',
                html: `Akan memproses <strong>${this.unusedTotal}</strong> produk arsip (bukan hanya pratinjau). Produk milik outlet dihapus permanen; produk katalog gudang hilang dari master & arsip outlet (tidak aktif lagi).<br><br>Tindakan ini <strong>tidak dapat dibatalkan</strong>.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Lanjut',
                cancelButtonText: 'Batal',
            });
            if (!r1.isConfirmed) return;
            const r2 = await Swal.fire({
                title: 'Konfirmasi terakhir',
                input: 'checkbox',
                inputValue: 0,
                inputPlaceholder: 'Saya mengerti produk akan dibersihkan dari outlet ini',
                showCancelButton: true,
                confirmButtonColor: '#b91c1c',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, bersihkan semua',
                cancelButtonText: 'Batal',
                inputValidator: (v) => (v ? null : 'Centang kotak konfirmasi'),
            });
            if (!r2.isConfirmed) return;
            this.bulkDeletingUnused = true;
            const targetTotal = this.unusedTotal;
            let totalDeleted = 0;
            let totalSkipped = 0;
            const skipNotes = [];
            Swal.fire({
                title: 'Membersihkan…',
                html: 'Memproses batch produk. Mohon tunggu.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading(),
            });
            try {
                let guard = 0;
                while (guard < 500) {
                    guard++;
                    const res = await fetch(unusedBulkDeleteUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            Accept: 'application/json',
                        },
                        body: JSON.stringify({ confirm: true, batch_size: 80 }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok && !(data.done && data.deleted === 0 && totalDeleted > 0)) {
                        let msg = data.message || 'Tidak dapat membersihkan produk.';
                        if (Array.isArray(data.skip_notes) && data.skip_notes.length) {
                            msg += '\n\n' + data.skip_notes.slice(0, 5).join('\n');
                        }
                        Swal.fire({ title: 'Gagal', text: msg, icon: 'error' });
                        return;
                    }
                    totalDeleted += Number(data.deleted || 0);
                    totalSkipped += Number(data.skipped || 0);
                    if (Array.isArray(data.skip_notes)) {
                        skipNotes.push(...data.skip_notes);
                    }
                    const remaining = Number(data.remaining ?? 0);
                    const progress = Math.min(100, Math.round((totalDeleted / Math.max(targetTotal, 1)) * 100));
                    Swal.update({
                        html: `Terbersihkan <strong>${totalDeleted.toLocaleString('id-ID')}</strong> / ${targetTotal.toLocaleString('id-ID')}`
                            + (remaining > 0 ? `<br>Sisa ${remaining.toLocaleString('id-ID')}…` : '')
                            + `<br><span class="text-sm text-gray-500">${progress}%</span>`,
                    });
                    if (data.done || remaining <= 0 || Number(data.deleted || 0) === 0) {
                        break;
                    }
                }
                let msg = totalDeleted > 0
                    ? `Berhasil membersihkan ${totalDeleted.toLocaleString('id-ID')} produk arsip tidak terpakai.`
                    : 'Tidak ada produk yang dibersihkan.';
                if (totalSkipped > 0) {
                    msg += ` (${totalSkipped} dilewati)`;
                }
                await Swal.fire({ title: 'Berhasil', text: msg, icon: totalDeleted > 0 ? 'success' : 'info', timer: 2400, showConfirmButton: false });
                window.location.reload();
            } catch (e) {
                Swal.fire({ title: 'Gagal', text: 'Koneksi terputus atau server timeout. Coba lagi — batch yang sudah berhasil tidak perlu dikembalikan.', icon: 'error' });
            } finally {
                this.bulkDeletingUnused = false;
            }
        },
        async restoreAll() {
            if (totalTrashed <= 0) {
                Swal.fire('Perhatian', 'Tidak ada entri arsip untuk dipulihkan.', 'info');
                return;
            }
            const r1 = await Swal.fire({
                title: 'Pulihkan semua dari arsip?',
                html: 'Semua <strong>' + totalTrashed.toLocaleString('id-ID') + '</strong> ' + itemLabel + ' arsip outlet ini akan dikembalikan ke daftar master.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Lanjut',
                cancelButtonText: 'Batal',
            });
            if (!r1.isConfirmed) return;
            const r2 = await Swal.fire({
                title: 'Konfirmasi terakhir',
                input: 'checkbox',
                inputValue: 0,
                inputPlaceholder: 'Saya mengerti dan ingin memulihkan semua entri arsip outlet ini',
                showCancelButton: true,
                confirmButtonColor: '#047857',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Pulihkan semua',
                cancelButtonText: 'Batal',
                inputValidator: (v) => (v ? null : 'Centang kotak konfirmasi'),
            });
            if (!r2.isConfirmed) return;
            const res = await fetch(restoreAllUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ confirm: true }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                Swal.fire({ title: 'Gagal', text: data.message || 'Gagal memulihkan.', icon: 'error' });
                return;
            }
            await Swal.fire({ title: 'Berhasil', text: data.message || 'Selesai.', icon: 'success' });
            window.location.reload();
        },
        async restore(id, label) {
            const r = await Swal.fire({
                title: 'Kembalikan ke master outlet?',
                text: itemLabel.charAt(0).toUpperCase() + itemLabel.slice(1) + ' "' + String(label || '') + '" akan muncul lagi di daftar master speedshop outlet ini.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#059669',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, pulihkan',
                cancelButtonText: 'Batal',
            });
            if (!r.isConfirmed) return;
            const res = await fetch(restoreUrl(id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json',
                },
                body: JSON.stringify({}),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                Swal.fire({ title: 'Gagal', text: data.message || ('Tidak dapat memulihkan ' + itemLabel + '.'), icon: 'error' });
                return;
            }
            await Swal.fire({ title: 'Berhasil', text: data.message || (itemLabel.charAt(0).toUpperCase() + itemLabel.slice(1) + ' telah dipulihkan.'), icon: 'success', timer: 1800, showConfirmButton: false });
            window.location.reload();
        },
    };
}
</script>
@endpush

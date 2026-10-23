@extends($mp['layout'] ?? 'layouts.speedshop')

@php
    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $exportQuery = request()->only(['nama', 'biaya']);
    $jasaStats = $jasaStats ?? ['total' => 0, 'avg_biaya' => 0, 'max_biaya' => 0];
    $hasActiveFilters = request()->filled('nama') || request()->filled('biaya');
@endphp

@section('title', 'Master Data — Jasa Servis')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .js-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 900px) {
  .ss-dashboard .js-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.cyan { background: rgba(6,182,212,0.10); color: #0891B2; }
.ss-dashboard .pay-icon.teal { background: rgba(20,184,166,0.10); color: #14B8A6; }
.ss-dashboard .pay-icon.red { background: rgba(239,68,68,0.10); color: #DC2626; }
</style>
@endpush

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="jasaServisCrud()"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
>
<div class="ss-dash-content space-y-5">

    <div class="js-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Jasa</span>
                <span class="pay-icon cyan">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($jasaStats['total'], 0, ',', '.') }}</div>
            <p class="pay-period">Item terdaftar</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Rata-rata Biaya</span>
                <span class="pay-icon teal">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp {{ number_format($jasaStats['avg_biaya'], 0, ',', '.') }}</div>
            <p class="pay-period">Per jasa servis</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Biaya Tertinggi</span>
                <span class="pay-icon red">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp {{ number_format($jasaStats['max_biaya'], 0, ',', '.') }}</div>
            <p class="pay-period">Nominal maksimum</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Daftar Jasa Servis</h2>
                <p class="mt-1 text-[13px] text-gray-500">
                    Filter nama atau biaya memuat ulang otomatis saat nilai diubah.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route($mp['route_export'], $exportQuery) }}" class="btn-export">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Export Excel
                </a>
                <a href="{{ route($mp['route_pdf'], $exportQuery) }}" target="_blank" rel="noopener noreferrer" class="btn-export-pdf">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Export PDF
                </a>
                <button type="button" @click="showImportModal = true" class="btn-secondary inline-flex h-10 items-center gap-2 border-indigo-200 bg-indigo-50 px-4 text-indigo-700 hover:bg-indigo-100">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Import Excel
                </button>
                <button type="button" @click="openCreate()" class="btn-primary inline-flex h-10 items-center gap-2 px-5">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Tambah Jasa
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <form method="GET">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="w-16 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Jasa</th>
                            <th class="w-44 px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Biaya</th>
                            <th class="w-28 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500"></th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="nama" value="{{ request('nama') }}" placeholder="Cari nama jasa…" class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2">
                                <input type="number" min="0" step="1" name="biaya" value="{{ request('biaya') }}" placeholder="Cari biaya…" class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                            </th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($items as $i => $item)
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 tabular-nums text-gray-500">{{ $items->firstItem() + $i }}</td>
                        <td class="px-5 py-4 font-semibold text-gray-900">{{ $item->nama }}</td>
                        <td class="px-5 py-4 text-right font-medium tabular-nums text-gray-800">Rp {{ number_format((float) $item->biaya, 0, ',', '.') }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" @click.prevent="openEdit({{ $item->id }})" class="wo-icon-btn bg-gray-600 text-white hover:bg-gray-700" title="Edit">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                                <button type="button" @click.prevent="destroy({{ $item->id }})" class="wo-icon-btn bg-red-600 text-white hover:bg-red-700" title="Hapus">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-5 py-12 text-center text-gray-400">
                            @if($hasActiveFilters)
                                Tidak ada jasa servis sesuai filter.
                            @else
                                <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                <p class="mt-3 text-sm font-medium text-gray-700">Belum ada data jasa servis</p>
                                <p class="mt-1 text-xs">Klik Tambah Jasa untuk memulai</p>
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

{{-- Import Modal --}}
<div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showImportModal = false">
    <div x-show="showImportModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showImportModal = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showImportModal" x-transition class="wo-modal-panel relative w-full max-w-2xl !p-6 sm:!p-8" @click.stop>
            <h3 class="text-lg font-bold text-gray-900">Import Jasa Servis</h3>
            <p class="mt-1 text-sm text-gray-500">Download template Excel, isi data, lalu upload file untuk import.</p>
            <div class="mt-6">
                <p class="wo-label mb-3">Upload File Excel</p>
                <form action="{{ route($mp['route_import']) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <label class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50/50 px-8 py-14 transition hover:border-red-300 hover:bg-red-50/30">
                        <svg class="h-14 w-14 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <span class="mt-3 text-sm font-medium text-gray-600" x-text="importFileName || 'Klik untuk memilih file .xlsx atau .xls'"></span>
                        <input type="file" name="file" accept=".xlsx,.xls" class="hidden" @change="importFileName = $event.target.files[0]?.name || ''">
                    </label>
                    <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 pt-6">
                        <a href="{{ route($mp['route_template']) }}" target="_blank" rel="noopener" class="btn-secondary inline-flex items-center gap-2 border-emerald-200 bg-emerald-50 px-4 py-2.5 text-emerald-700 hover:bg-emerald-100">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download Template
                        </a>
                        <button type="submit" class="btn-primary px-5 py-2.5">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal CRUD --}}
<div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showModal = false">
    <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showModal" x-transition class="wo-modal-panel relative w-full max-w-md !p-6 sm:!p-8" @click.stop>
            <h3 class="text-lg font-bold text-gray-900" x-text="isEdit ? 'Edit Jasa Servis' : 'Tambah Jasa Servis'"></h3>
            <div class="mt-6 space-y-4">
                <div>
                    <label class="wo-label">Nama Jasa <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.nama" class="wo-input wo-native" :class="errors.nama && 'border-red-400'">
                    <template x-if="errors.nama"><p class="mt-1 text-xs text-red-500" x-text="errors.nama[0]"></p></template>
                </div>
                <div>
                    <label class="wo-label">Biaya <span class="text-red-500">*</span></label>
                    <input type="number" min="0" step="1" x-model="form.biaya" class="wo-input wo-native tabular-nums" :class="errors.biaya && 'border-red-400'">
                    <template x-if="errors.biaya"><p class="mt-1 text-xs text-red-500" x-text="errors.biaya[0]"></p></template>
                </div>
            </div>
            <div class="mt-8 flex items-center justify-end gap-3">
                <button type="button" @click="showModal = false" class="btn-secondary px-6 py-2.5">Batal</button>
                <button type="button" @click="save()" :disabled="loading" class="btn-primary px-6 py-2.5 disabled:opacity-50">
                    <span x-show="!loading" x-text="isEdit ? 'Update' : 'Simpan'"></span>
                    <span x-show="loading">Menyimpan...</span>
                </button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
function jasaServisCrud() {
    return {
        showImportModal: false,
        importFileName: '',
        showModal: false, isEdit: false, editId: null, loading: false, errors: {},
        form: { nama: '', biaya: 0 },
        openCreate() {
            this.isEdit = false;
            this.editId = null;
            this.errors = {};
            this.form = { nama: '', biaya: 0 };
            this.showModal = true;
        },
        async openEdit(id) {
            this.isEdit = true;
            this.editId = id;
            this.errors = {};
            this.loading = true;
            this.showModal = true;
            const res = await fetch(`{{ $mp['base_url'] }}/${id}`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.form = { nama: data.nama || '', biaya: data.biaya ?? 0 };
            this.loading = false;
        },
        async save() {
            this.loading = true;
            this.errors = {};
            const url = this.isEdit ? `{{ $mp['base_url'] }}/${this.editId}` : '{{ route($mp['route_store']) }}';
            const res = await fetch(url, {
                method: this.isEdit ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ nama: this.form.nama, biaya: this.form.biaya })
            });
            if (!res.ok) {
                const d = await res.json().catch(() => ({}));
                this.errors = d.errors || {};
                this.loading = false;
                return;
            }
            window.location.reload();
        },
        async destroy(id) {
            const result = await Swal.fire({
                title: 'Hapus jasa?',
                text: 'Data yang dihapus tidak dapat dikembalikan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            });
            if (!result.isConfirmed) return;
            await fetch(`{{ $mp['base_url'] }}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            window.location.reload();
        }
    }
}
</script>
@endpush

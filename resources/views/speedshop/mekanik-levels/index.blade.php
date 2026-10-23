@extends('layouts.speedshop')

@php
    $levelNames = $items->pluck('nama');
    $totalLevels = $items->count();
    $levelsUsedCount = $levelNames->isNotEmpty()
        ? \App\Models\Mekanik::query()->whereIn('spesialisasi', $levelNames)->distinct()->count('spesialisasi')
        : 0;
    $mekanikWithLevel = $levelNames->isNotEmpty()
        ? \App\Models\Mekanik::query()->whereIn('spesialisasi', $levelNames)->count()
        : 0;
@endphp

@section('title', 'Master Data — Level Mekanik')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .ml-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 900px) {
  .ss-dashboard .ml-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.cyan { background: rgba(6,182,212,0.10); color: #0891B2; }
.ss-dashboard .pay-icon.teal { background: rgba(20,184,166,0.10); color: #14B8A6; }
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: #D97706; }
</style>
@endpush

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="mekanikLevelCrud()"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
>
<div class="ss-dash-content space-y-5">
    <div class="flex flex-col gap-3 ss-dash-enter sm:flex-row sm:items-start sm:justify-between" style="--ss-delay: 0ms">
        <div>
            <h1 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Level Mekanik</h1>
            <p class="mt-1 text-[13px] text-gray-500">Daftar level yang dipakai untuk dropdown spesialisasi mekanik</p>
        </div>
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            <a href="{{ route('speedshop.mekaniks.index') }}" class="btn-secondary inline-flex h-10 items-center gap-2 px-4">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Kembali ke Mekanik
            </a>
            <button type="button" @click="openCreate()" class="btn-primary inline-flex h-10 items-center gap-2 px-5">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Tambah Level
            </button>
        </div>
    </div>


    <div class="ml-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Level</span>
                <span class="pay-icon amber">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($totalLevels, 0, ',', '.') }}</div>
            <p class="pay-period">Level terdaftar</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Level Dipakai</span>
                <span class="pay-icon teal">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($levelsUsedCount, 0, ',', '.') }}</div>
            <p class="pay-period">Ada mekanik terassign</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Mekanik Terassign</span>
                <span class="pay-icon cyan">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($mekanikWithLevel, 0, ',', '.') }}</div>
            <p class="pay-period">Pakai level ini</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Daftar Level</h2>
            <p class="mt-1 text-[13px] text-gray-500">Level yang masih dipakai mekanik tidak bisa dihapus</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-[#f7f3f4]">
                        <th class="w-16 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Level</th>
                        <th class="w-28 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($items as $i => $row)
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 tabular-nums text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-5 py-4">
                            <span class="font-semibold text-gray-900">{{ $row->nama }}</span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" @click.prevent="openEdit({{ $row->id }})" class="wo-icon-btn bg-gray-600 text-white hover:bg-gray-700" title="Edit">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                                <button type="button" @click.prevent="destroy({{ $row->id }})" class="wo-icon-btn bg-red-600 text-white hover:bg-red-700" title="Hapus">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-5 py-12 text-center text-gray-400">
                            <p class="font-medium text-gray-700">Belum ada level mekanik</p>
                            <p class="mt-1 text-xs">Klik Tambah Level untuk memulai</p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showModal = false">
    <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="showModal" x-transition class="wo-modal-panel relative w-full max-w-md !p-6 sm:!p-8" @click.stop>
            <h3 class="text-lg font-bold text-gray-900" x-text="isEdit ? 'Edit Level Mekanik' : 'Tambah Level Mekanik'"></h3>
            <div class="mt-6">
                <label class="wo-label">Nama Level <span class="text-red-500">*</span></label>
                <input type="text" x-model="form.nama" class="wo-input wo-native" :class="errors.nama && 'border-red-400'" placeholder="Contoh: Senior, Junior">
                <template x-if="errors.nama"><p class="mt-1 text-xs text-red-500" x-text="errors.nama[0]"></p></template>
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
function mekanikLevelCrud() {
    const baseUrl = '{{ url('speedshop/mekanik-levels') }}';
    return {
        showModal: false, isEdit: false, editId: null, loading: false, errors: {},
        form: { nama: '' },
        openCreate() {
            this.isEdit = false;
            this.editId = null;
            this.errors = {};
            this.form = { nama: '' };
            this.showModal = true;
        },
        async openEdit(id) {
            this.isEdit = true;
            this.editId = id;
            this.errors = {};
            this.loading = true;
            this.showModal = true;
            const res = await fetch(`${baseUrl}/${id}`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.form = { nama: data.nama || '' };
            this.loading = false;
        },
        async save() {
            this.loading = true;
            this.errors = {};
            const url = this.isEdit ? `${baseUrl}/${this.editId}` : '{{ route('speedshop.mekanik-levels.store') }}';
            const res = await fetch(url, {
                method: this.isEdit ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.form)
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
                title: 'Hapus level?',
                text: 'Level yang masih dipakai mekanik tidak bisa dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            });
            if (!result.isConfirmed) return;
            const res = await fetch(`${baseUrl}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            if (!res.ok) {
                const d = await res.json().catch(() => ({}));
                await Swal.fire('Gagal', d.message || 'Level tidak dapat dihapus.', 'warning');
                return;
            }
            window.location.reload();
        }
    }
}
</script>
@endpush

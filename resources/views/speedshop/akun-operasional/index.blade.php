@extends('layouts.speedshop')

@php
    $akunStats = [
        'total' => $items->count(),
        'operasional' => $items->filter(fn ($a) => str_starts_with((string) $a->no_akun, '5.2.'))->count(),
        'non_operasional' => $items->filter(fn ($a) => str_starts_with((string) $a->no_akun, '5.3.'))->count(),
    ];
@endphp

@section('title', 'No Akun')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .ao-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 900px) {
  .ss-dashboard .ao-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.cyan { background: rgba(6,182,212,0.10); color: #0891B2; }
.ss-dashboard .pay-icon.teal { background: rgba(20,184,166,0.10); color: #14B8A6; }
.ss-dashboard .pay-icon.violet { background: rgba(139,92,246,0.10); color: #7C3AED; }
</style>
@endpush

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="akunOperasionalCrud()"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
>
<div class="ss-dash-content space-y-5">

    <div class="ao-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Total No Akun</span>
                <span class="pay-icon cyan">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($akunStats['total'], 0, ',', '.') }}</div>
            <p class="pay-period">Akun terdaftar</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Biaya Operasional</span>
                <span class="pay-icon teal">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($akunStats['operasional'], 0, ',', '.') }}</div>
            <p class="pay-period">Kode 5.2.x</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Biaya Non Operasional</span>
                <span class="pay-icon violet">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </span>
            </div>
            <div class="pay-amount">{{ number_format($akunStats['non_operasional'], 0, ',', '.') }}</div>
            <p class="pay-period">Kode 5.3.x</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="border-b border-gray-100 px-5 py-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Daftar No Akun</h2>
                <button type="button" @click="openCreate()" class="btn-primary inline-flex h-10 shrink-0 items-center gap-2 px-5">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Tambah No Akun
                </button>
            </div>
            <p class="mt-1 text-[13px] text-gray-500">Biaya operasional 5.2.x · non operasional 5.3.x</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-[#f7f3f4]">
                        <th class="w-16 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                        <th class="w-44 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No Akun</th>
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Akun</th>
                        <th class="w-28 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($items as $i => $row)
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 tabular-nums text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-5 py-4">
                            <span class="font-mono text-[13px] font-semibold text-gray-900">{{ $row->no_akun }}</span>
                            @if(str_starts_with((string) $row->no_akun, '5.2.'))
                            <span class="ml-2 inline-flex rounded-lg border border-teal-200 bg-teal-50 px-2 py-0.5 text-[11px] font-semibold text-teal-700">Operasional</span>
                            @elseif(str_starts_with((string) $row->no_akun, '5.3.'))
                            <span class="ml-2 inline-flex rounded-lg border border-violet-200 bg-violet-50 px-2 py-0.5 text-[11px] font-semibold text-violet-700">Non Op.</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 font-semibold text-gray-900">{{ $row->nama_akun }}</td>
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
                        <td colspan="4" class="px-5 py-12 text-center text-gray-400">
                            <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <p class="mt-3 text-sm font-medium text-gray-700">Belum ada no akun</p>
                            <p class="mt-1 text-xs">Klik Tambah No Akun untuk memulai</p>
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
            <h3 class="text-lg font-bold text-gray-900" x-text="isEdit ? 'Edit No Akun' : 'Tambah No Akun'"></h3>
            <p class="mt-1 text-sm text-gray-500">Gunakan kode 5.2.x untuk biaya operasional atau 5.3.x untuk non operasional.</p>
            <div class="mt-6 space-y-4">
                <div>
                    <label class="wo-label">No Akun <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.no_akun" class="wo-input wo-native font-mono" :class="errors.no_akun && 'border-red-400'" placeholder="Contoh: 5.2.3">
                    <template x-if="errors.no_akun"><p class="mt-1 text-xs text-red-500" x-text="errors.no_akun[0]"></p></template>
                </div>
                <div>
                    <label class="wo-label">Nama Akun <span class="text-red-500">*</span></label>
                    <input type="text" x-model="form.nama_akun" class="wo-input wo-native" :class="errors.nama_akun && 'border-red-400'" placeholder="Contoh: BIAYA OPERASIONAL KANTOR">
                    <template x-if="errors.nama_akun"><p class="mt-1 text-xs text-red-500" x-text="errors.nama_akun[0]"></p></template>
                </div>
            </div>
            <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
                <button type="button" @click="showModal = false" class="btn-secondary px-5 py-2.5">Batal</button>
                <button type="button" @click="save()" :disabled="loading" class="btn-primary px-5 py-2.5 disabled:opacity-50">
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
function akunOperasionalCrud() {
    const baseUrl = @json(rtrim(route('speedshop.keuangan.akun-operasional.index'), '/'));
    return {
        showModal: false, isEdit: false, editId: null, loading: false, errors: {},
        form: { no_akun: '', nama_akun: '' },
        openCreate() {
            this.isEdit = false;
            this.editId = null;
            this.errors = {};
            this.form = { no_akun: '', nama_akun: '' };
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
            this.form = { no_akun: data.no_akun || '', nama_akun: data.nama_akun || '' };
            this.loading = false;
        },
        async save() {
            this.loading = true;
            this.errors = {};
            const url = this.isEdit ? `${baseUrl}/${this.editId}` : '{{ route('speedshop.keuangan.akun-operasional.store') }}';
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
                title: 'Hapus akun?',
                text: 'Data yang dihapus tidak dapat dikembalikan.',
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
                await Swal.fire('Gagal', d.message || 'Akun tidak dapat dihapus.', 'warning');
                return;
            }
            window.location.reload();
        }
    }
}
</script>
@endpush

@extends('layouts.speedshop')

@section('title', 'Keuangan — Aset')

@section('content')
@php
    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $hasActiveFilters = request()->filled('kategori') || request()->filled('search');
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $cabangName = $speedshop?->nama ?: 'Cabang aktif';
@endphp

@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-dashboard .ast-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}
@media (max-width: 1024px) {
  .ss-dashboard .ast-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
  .ss-dashboard .ast-summary-grid { grid-template-columns: 1fr; }
}
.ss-dashboard .pay-icon.blue { background: rgba(59,130,246,0.10); color: #2563EB; }
.ss-dashboard .pay-icon.violet { background: rgba(139,92,246,0.10); color: #7C3AED; }
.ss-dashboard .pay-icon.emerald { background: rgba(16,185,129,0.10); color: #059669; }
</style>
@endpush

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-data="speedshopAsetCrud()"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
>
<div class="ss-dash-content space-y-5">

    @include('partials.flash')

    <div class="ast-summary-grid ss-dash-enter" style="--ss-delay: 80ms">
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 0">
            <div class="pay-card-top">
                <span class="pay-card-label">Rak / Etalase</span>
                <span class="pay-icon blue">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp {{ $fmt($totals['rak'] ?? 0) }}</div>
            <p class="pay-period">Total nilai buku kategori rak</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 1">
            <div class="pay-card-top">
                <span class="pay-card-label">Inventaris / Aset Peralatan</span>
                <span class="pay-icon violet">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp {{ $fmt($totals['inventaris'] ?? 0) }}</div>
            <p class="pay-period">Total nilai buku inventaris</p>
        </div>
        <div class="pay-card ss-dash-enter-child" style="--ss-delay: 100ms; --ss-stagger: 2">
            <div class="pay-card-top">
                <span class="pay-card-label">Total Aset</span>
                <span class="pay-icon emerald">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </span>
            </div>
            <div class="pay-amount text-[22px] sm:text-[26px]">Rp {{ $fmt($totals['semua'] ?? 0) }}</div>
            <p class="pay-period">{{ $cabangName }}</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush ss-dash-enter" style="--ss-delay: 120ms">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="section-hd" style="margin-bottom:0">
                <h2>Aset Cabang</h2>
                <p>Catat aset tetap per cabang Speedshop · {{ $cabangName }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="openCreate()" class="btn-primary inline-flex h-10 items-center gap-2 px-5">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Tambah Aset
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <form method="GET">
                <table class="w-full min-w-[960px] text-left text-sm">
                    <thead>
                    <tr class="bg-[#f7f3f4]">
                        <th class="w-16 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                        <th class="w-44 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kategori</th>
                        <th class="w-36 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                        <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama</th>
                        <th class="w-36 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tgl Perolehan</th>
                        <th class="w-36 px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nilai Perolehan</th>
                        <th class="w-36 px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nilai Buku</th>
                        <th class="w-28 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
                    </tr>
                    <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                        <th class="px-5 py-2"></th>
                        <th class="px-5 py-2">
                            <select name="kategori" class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                                <option value="">Semua kategori</option>
                                <option value="rak" @selected(request('kategori') === 'rak')>Rak / Etalase</option>
                                <option value="inventaris" @selected(request('kategori') === 'inventaris')>Inventaris / Aset Peralatan</option>
                            </select>
                        </th>
                        <th class="px-5 py-2">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode / nama…" class="{{ $filterBaseClass }}" onchange="this.form.submit()">
                        </th>
                        <th class="px-5 py-2" colspan="5"></th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($items as $i => $row)
                        <tr class="transition hover:bg-[#fcfafb]">
                            <td class="px-5 py-4 tabular-nums text-gray-500">{{ $items->firstItem() + $i }}</td>
                            <td class="px-5 py-4">
                                <span class="inline-flex max-w-full items-center rounded-lg px-2.5 py-1 text-sm font-semibold {{ $row->kategori === 'rak' ? 'bg-blue-100 text-blue-800' : 'bg-violet-100 text-violet-800' }}">
                                    {{ $row->kategoriLabel() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 font-mono text-sm font-semibold text-gray-900">{{ $row->kode }}</td>
                            <td class="px-5 py-4 font-medium text-gray-900">
                                {{ $row->nama }}
                                @if($row->keterangan)
                                <span class="mt-0.5 block text-xs font-normal text-gray-400">{{ $row->keterangan }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 tabular-nums text-gray-600">{{ $row->tanggal_perolehan?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-5 py-4 text-right tabular-nums text-gray-700">Rp {{ $fmt($row->nilai_perolehan) }}</td>
                            <td class="px-5 py-4 text-right tabular-nums font-semibold text-gray-900">Rp {{ $fmt($row->nilai_buku) }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" @click.prevent="openEdit({{ $row->id }})" class="wo-icon-btn bg-gray-600 text-white hover:bg-gray-700" title="Edit">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                    <button type="button" @click.prevent="destroy({{ $row->id }})" class="wo-icon-btn bg-red-500 text-white hover:bg-red-600" title="Hapus">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-gray-400">
                                @if($hasActiveFilters)
                                    Tidak ada aset sesuai filter.
                                @else
                                    <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    <p class="mt-3 text-sm font-medium">Belum ada aset di cabang ini</p>
                                    <p class="mt-1 text-xs text-gray-500">Klik Tambah Aset untuk mencatat aset baru.</p>
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

    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showModal = false">
        <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="showModal" x-transition class="wo-modal-panel relative w-full max-w-xl !p-6 sm:!p-8" @click.stop>
                <h3 class="text-lg font-bold text-gray-900" x-text="isEdit ? 'Edit Aset' : 'Tambah Aset'"></h3>
                <p class="mt-1 text-sm text-gray-500">{{ $cabangName }}</p>
                <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="wo-label">Kategori <span class="req">*</span></label>
                        <select x-model="form.kategori" class="wo-input wo-native">
                            <option value="rak">Rak / Etalase</option>
                            <option value="inventaris">Inventaris / Aset Peralatan</option>
                        </select>
                    </div>
                    <div>
                        <label class="wo-label">Kode <span class="req">*</span></label>
                        <input type="text" x-model="form.kode" maxlength="50" class="wo-input uppercase" placeholder="AST-001">
                    </div>
                    <div>
                        <label class="wo-label">Tanggal Perolehan</label>
                        <input type="date" x-model="form.tanggal_perolehan" class="wo-input wo-native">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="wo-label">Nama Aset <span class="req">*</span></label>
                        <input type="text" x-model="form.nama" maxlength="255" class="wo-input" placeholder="Contoh: Kompressor, Lift Motor, Rak Display">
                    </div>
                    <div>
                        <label class="wo-label">Nilai Perolehan (Rp) <span class="req">*</span></label>
                        <input type="number" x-model.number="form.nilai_perolehan" min="0" step="1" class="wo-input wo-native tabular-nums">
                    </div>
                    <div>
                        <label class="wo-label">Nilai Buku (Rp)</label>
                        <input type="number" x-model.number="form.nilai_buku" min="0" step="1" class="wo-input wo-native tabular-nums" placeholder="Kosongkan = sama dengan perolehan">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="wo-label">Keterangan</label>
                        <textarea x-model="form.keterangan" rows="2" maxlength="1000" class="wo-input min-h-[5rem] resize-y" placeholder="Opsional"></textarea>
                    </div>
                </div>
                <div class="mt-8 flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
                    <button type="button" @click="showModal = false" class="btn-secondary px-6 py-2.5">Batal</button>
                    <button type="button" @click="submit()" :disabled="loading" class="btn-primary px-6 py-2.5 disabled:opacity-50">
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
function speedshopAsetCrud() {
    const baseUrl = @json(url('/speedshop/keuangan/aset'));
    return {
        showModal: false,
        isEdit: false,
        loading: false,
        editId: null,
        form: {
            kategori: 'inventaris',
            kode: '',
            nama: '',
            tanggal_perolehan: '',
            nilai_perolehan: 0,
            nilai_buku: 0,
            keterangan: '',
        },

        openCreate() {
            this.isEdit = false;
            this.editId = null;
            this.form = { kategori: 'inventaris', kode: '', nama: '', tanggal_perolehan: '', nilai_perolehan: 0, nilai_buku: 0, keterangan: '' };
            this.showModal = true;
        },

        async openEdit(id) {
            this.isEdit = true;
            this.editId = id;
            this.loading = true;
            this.showModal = true;
            const res = await fetch(`${baseUrl}/${id}`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.form = {
                kategori: data.kategori || 'inventaris',
                kode: data.kode || '',
                nama: data.nama || '',
                tanggal_perolehan: data.tanggal_perolehan || '',
                nilai_perolehan: data.nilai_perolehan || 0,
                nilai_buku: data.nilai_buku || 0,
                keterangan: data.keterangan || '',
            };
            this.loading = false;
        },

        async submit() {
            if (!String(this.form.kode || '').trim() || !String(this.form.nama || '').trim()) {
                Swal.fire('Perhatian', 'Kode dan Nama wajib diisi.', 'warning');
                return;
            }
            if (Number(this.form.nilai_perolehan) < 0) {
                Swal.fire('Perhatian', 'Nilai perolehan tidak valid.', 'warning');
                return;
            }

            this.loading = true;
            const payload = {
                kategori: this.form.kategori,
                kode: String(this.form.kode || '').toUpperCase().trim(),
                nama: String(this.form.nama || '').trim(),
                tanggal_perolehan: this.form.tanggal_perolehan || null,
                nilai_perolehan: Number(this.form.nilai_perolehan) || 0,
                nilai_buku: Number(this.form.nilai_buku) || Number(this.form.nilai_perolehan) || 0,
                keterangan: String(this.form.keterangan || '').trim() || null,
            };

            const res = await fetch(this.isEdit ? `${baseUrl}/${this.editId}` : baseUrl, {
                method: this.isEdit ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            this.loading = false;

            if (!res.ok) {
                const d = await res.json().catch(() => ({}));
                const msg = d.message
                    || (d.errors ? Object.values(d.errors).flat().join(' ') : null)
                    || 'Gagal menyimpan.';
                Swal.fire('Error', msg, 'error');
                return;
            }
            window.location.reload();
        },

        async destroy(id) {
            const result = await Swal.fire({
                title: 'Hapus aset?',
                text: 'Data aset akan dihapus permanen dari cabang ini.',
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
                Swal.fire('Gagal', d.message || 'Aset tidak dapat dihapus.', 'error');
                return;
            }
            window.location.reload();
        }
    }
}
</script>
@endpush

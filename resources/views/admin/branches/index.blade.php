@extends('layouts.admin')

@section('title', 'Master Data — Cabang Speedshop')

@section('content')
<div class="space-y-5" x-data="adminBranchesCrud()">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.dashboard') }}" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">Cabang Speedshop</span>
    </nav>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Total Cabang</p>
                    <p class="mt-1 text-[40px] font-semibold leading-none text-gray-900">{{ number_format($statTotal) }}</p>
                    <p class="mt-1 text-[11px] text-gray-400">Sesuai filter saat ini</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Pengguna terhubung</p>
                    <p class="mt-1 text-[40px] font-semibold leading-none text-emerald-700">{{ number_format($statUsersLinked) }}</p>
                    <p class="mt-1 text-[11px] text-gray-400">Akun speedshop per cabang</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <form method="GET" action="{{ route('admin.branches.index') }}">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Cabang Speedshop</h2>
                    <p class="mt-1 text-[13px] text-gray-500">Nama, nomor HP, dan alamat. Cabang baru otomatis terhubung ke gudang utama perusahaan (stok &amp; mutasi).</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" @click="openCreate()" class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand px-5 text-sm font-medium text-brand-ink shadow-lg shadow-brand/35 transition hover:bg-brand-hover active:scale-[0.98]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        Tambah
                    </button>
                </div>
            </div>

            <div class="mt-5 overflow-x-auto rounded-xl border border-gray-200">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 w-16">No.</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No. HP</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Alamat</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 w-24"></th>
                        </tr>
                        @php
                            $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
                        @endphp
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <td class="px-5 py-2"></td>
                            <td class="px-5 py-2 font-normal" colspan="2">
                                <input type="text" name="nama" value="{{ request('nama') }}" placeholder="Cari nama…" onchange="this.form.submit()" class="{{ $filterBaseClass }} max-w-md">
                            </td>
                            <td class="px-5 py-2"></td>
                            <td class="px-5 py-2"></td>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($branches as $i => $b)
                            <tr class="transition hover:bg-[#fcfafb]">
                                <td class="px-5 py-4 text-gray-500">{{ $branches->firstItem() + $i }}</td>
                                <td class="px-5 py-4 font-semibold text-gray-900">{{ $b->nama }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ $b->no_hp ?? '—' }}</td>
                                <td class="px-5 py-4 text-gray-700 max-w-md truncate" title="{{ $b->alamat }}">{{ $b->alamat ? \Illuminate\Support\Str::limit($b->alamat, 80) : '—' }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" @click="openEdit({{ $b->id }})" class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-500 text-white shadow-sm transition hover:bg-slate-600" title="Edit">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </button>
                                        <button type="button" @click="destroy({{ $b->id }})" class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-500 text-white shadow-sm transition hover:bg-red-600" title="Hapus">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-gray-400">Belum ada data cabang.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        @if($branches->hasPages())
            <div class="mt-5">{{ $branches->links() }}</div>
        @endif
    </div>

    {{-- Modal --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showModal = false">
        <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="showModal" x-transition class="relative w-full max-w-lg rounded-2xl bg-white p-8 shadow-xl" @click.stop>
                <h3 class="text-lg font-bold text-gray-900" x-text="isEdit ? 'Edit Cabang Speedshop' : 'Tambah Cabang Speedshop'"></h3>
                <p class="mt-1 text-sm text-gray-500" x-show="loading">Memuat data…</p>
                <p class="mt-1 text-xs text-gray-500" x-show="!isEdit" x-cloak>Cabang baru otomatis terhubung ke gudang utama perusahaan (sama seperti data awal sistem).</p>
                <div class="mt-6 space-y-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Nama cabang <span class="text-red-500">*</span></label>
                        <input type="text" x-model="form.nama" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" :class="errors.nama && 'border-red-400'">
                        <template x-if="errors.nama"><p class="mt-1 text-xs text-red-500" x-text="errors.nama[0]"></p></template>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">No. HP</label>
                        <input type="text" x-model="form.no_hp" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Alamat</label>
                        <textarea x-model="form.alamat" rows="3" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40"></textarea>
                    </div>
                </div>
                <div class="mt-8 flex items-center justify-end gap-3">
                    <button type="button" @click="save()" :disabled="loading" class="rounded-xl bg-brand px-6 py-2.5 text-sm font-semibold text-brand-ink shadow-sm transition hover:bg-brand-hover disabled:opacity-50">
                        <span x-show="!loading" x-text="isEdit ? 'Update' : 'Simpan'"></span>
                        <span x-show="loading">Menyimpan…</span>
                    </button>
                    <button type="button" @click="showModal = false" class="rounded-xl border border-gray-200 px-6 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50">Batal</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function adminBranchesCrud() {
    const base = @json(url('/admin/branches'));
    const csrf = () => document.querySelector('meta[name="csrf-token"]').content;
    return {
        showModal: false,
        isEdit: false,
        editId: null,
        loading: false,
        errors: {},
        form: { nama: '', no_hp: '', alamat: '' },
        openCreate() {
            this.isEdit = false;
            this.editId = null;
            this.errors = {};
            this.form = { nama: '', no_hp: '', alamat: '' };
            this.showModal = true;
        },
        async openEdit(id) {
            this.isEdit = true;
            this.editId = id;
            this.errors = {};
            this.loading = true;
            this.showModal = true;
            const res = await fetch(`${base}/${id}`, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            this.form = {
                nama: data.nama || '',
                no_hp: data.no_hp || '',
                alamat: data.alamat || '',
            };
            this.loading = false;
        },
        async save() {
            this.loading = true;
            this.errors = {};
            const payload = {
                nama: this.form.nama,
                no_hp: this.form.no_hp || null,
                alamat: this.form.alamat || null,
            };
            const url = this.isEdit ? `${base}/${this.editId}` : base;
            const method = this.isEdit ? 'PUT' : 'POST';
            const res = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });
            if (!res.ok) {
                const d = await res.json().catch(() => ({}));
                this.errors = d.errors || {};
                if (d.message && !Object.keys(this.errors).length) {
                    this.errors = { nama: [d.message] };
                }
                this.loading = false;
                return;
            }
            window.location.reload();
        },
        async destroy(id) {
            const result = await Swal.fire({
                title: 'Hapus cabang?',
                text: 'Data yang dihapus tidak dapat dikembalikan. Pastikan tidak ada pengguna yang masih terhubung ke cabang ini.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal',
            });
            if (!result.isConfirmed) {
                return;
            }
            const res = await fetch(`${base}/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const d = await res.json().catch(() => ({}));
            if (!res.ok) {
                Swal.fire('Gagal', d.message || 'Tidak dapat menghapus cabang.', 'error');
                return;
            }
            window.location.reload();
        },
    };
}
</script>
@endpush

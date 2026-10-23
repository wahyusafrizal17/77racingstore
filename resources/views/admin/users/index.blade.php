@extends('layouts.admin')

@section('title', 'Master Data — Karyawan')

@push('styles')
@include('partials.speedshop.work-order-create-styles')
@endpush

@section('content')
<div class="ss-wo-create space-y-5" x-data="adminUsersCrud({{ auth()->id() }})">
    @if(session('warning'))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ session('warning') }}</div>
    @endif
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.dashboard') }}" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">Karyawan</span>
    </nav>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Total Karyawan</p>
                    <p class="mt-1 text-[40px] font-semibold leading-none text-gray-900">{{ number_format($statTotal) }}</p>
                    <p class="mt-1 text-[11px] text-gray-400">Sesuai filter saat ini</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Head HR</p>
                    <p class="mt-1 text-[40px] font-semibold leading-none text-emerald-700">{{ number_format($statHeadHrAdmins) }}</p>
                    <p class="mt-1 text-[11px] text-gray-400">Akses panel admin (/admin)</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <form method="GET" action="{{ route('admin.users.index') }}">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Karyawan</h2>
                    <p class="mt-1 text-[13px] text-gray-500">Daftar karyawan per divisi, role, dan cabang/gudang.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.users.export', request()->only(['name', 'email', 'section', 'role'])) }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </a>
                    <a href="{{ route('admin.users.pdf', request()->only(['name', 'email', 'section', 'role'])) }}" target="_blank" rel="noopener" class="btn-export-pdf">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Export PDF
                    </a>
                    <button type="button" @click="showImportModal = true" class="btn-secondary inline-flex h-10 items-center gap-2 border-indigo-200 bg-indigo-50 px-4 text-indigo-700 hover:bg-indigo-100">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Import Excel
                    </button>
                    <button type="button" @click="openCreate()" class="btn-primary inline-flex h-10 items-center gap-2 px-5">
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
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Email</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Divisi</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Role</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Cabang / Gudang</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">PSA</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500 w-24"></th>
                        </tr>
                        @php
                            $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
                        @endphp
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <td class="px-5 py-2"></td>
                            <td class="px-5 py-2 font-normal">
                                <input type="text" name="name" value="{{ request('name') }}" placeholder="Cari nama…" onchange="this.form.submit()" class="{{ $filterBaseClass }}">
                            </td>
                            <td class="px-5 py-2 font-normal">
                                <input type="text" name="email" value="{{ request('email') }}" placeholder="Cari email…" onchange="this.form.submit()" class="{{ $filterBaseClass }}">
                            </td>
                            <td class="px-5 py-2 font-normal">
                                <select name="section" onchange="clearRoleAndSubmit(this)" class="{{ $filterBaseClass }}">
                                    <option value="">Semua divisi</option>
                                    @foreach($adminDivisiOptions as $opt)
                                        <option value="{{ $opt['value'] }}" @selected(request('section') === $opt['value'])>{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-5 py-2 font-normal">
                                <select name="role" onchange="this.form.submit()" class="{{ $filterBaseClass }}">
                                    <option value="">Semua role</option>
                                    @foreach($adminFilterRoleOptions as $roleOpt)
                                        <option value="{{ $roleOpt }}" @selected(request('role') === $roleOpt)>{{ $roleOpt }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-5 py-2"></td>
                            <td class="px-5 py-2"></td>
                            <td class="px-5 py-2"></td>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($users as $i => $u)
                            <tr class="transition hover:bg-[#fcfafb]">
                                <td class="px-5 py-4 text-gray-500">{{ $users->firstItem() + $i }}</td>
                                <td class="px-5 py-4 font-semibold text-gray-900">{{ $u->name }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ $u->email }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ \App\Support\AdminUserPayload::sectionLabel($u->section) }}</td>
                                <td class="px-5 py-4 text-gray-700">
                                    {{ $u->role }}
                                    @if($u->isSpeedshopMekanik() && $u->mekanik?->levelName())
                                        <span class="mt-0.5 block text-xs text-gray-500">{{ $u->mekanik->levelName() }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-gray-700">
                                    @if($u->relationLoaded('assignedSpeedshops') && $u->assignedSpeedshops->isNotEmpty())
                                        {{ $u->assignedSpeedshops->pluck('nama')->join(' · ') }}
                                    @elseif($u->isOwner() || $u->isSpeedshopRegionalManager())
                                        <span class="font-medium text-gray-900">Semua Cabang</span>
                                    @elseif(\App\Support\AdminUserPayload::isModifikasiOriSection($u->section))
                                        @if($u->warehouse_id)
                                            {{ $u->warehouse?->nama ?? 'Gudang' }}
                                        @else
                                            77racing
                                        @endif
                                    @elseif($u->speedshop_id)
                                        {{ $u->speedshop?->nama ?? '—' }}
                                    @elseif($u->warehouse_id)
                                        {{ $u->warehouse?->nama ?? '—' }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-gray-700">
                                    @if($u->attendanceLocation)
                                        {{ $u->attendanceLocation->displayName() }}
                                        @unless($u->attendanceLocation->is_active)
                                            <span class="mt-0.5 block text-xs text-amber-600">Lokasi nonaktif</span>
                                        @endunless
                                    @else
                                        <span class="text-gray-400" title="Mengikuti aturan divisi/cabang">Otomatis</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" @click="openEdit({{ $u->id }})" class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-500 text-white shadow-sm transition hover:bg-slate-600" title="Edit">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </button>
                                        @if($u->id !== auth()->id())
                                            <button type="button" @click="destroy({{ $u->id }})" class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-500 text-white shadow-sm transition hover:bg-red-600" title="Hapus">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center text-gray-400">Belum ada data karyawan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>

        @if($users->hasPages())
            <div class="mt-5">{{ $users->links() }}</div>
        @endif
    </div>

    {{-- Modal --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="closeModal()">
        <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="closeModal()"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="showModal" x-transition class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-8 shadow-xl" @click.stop>
                <h3 class="text-lg font-bold text-gray-900" x-text="isEdit ? 'Edit Karyawan' : 'Tambah Karyawan'"></h3>
                <p class="mt-1 text-sm text-gray-500" x-show="loading && isEdit">Memuat data…</p>
                <div class="mt-6 space-y-4" x-show="!isEdit || !loading" x-cloak>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Nama <span class="text-red-500">*</span></label>
                            <input type="text" x-model="form.name" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" :class="errors.name && 'border-red-400'">
                            <template x-if="errors.name"><p class="mt-1 text-xs text-red-500" x-text="errors.name[0]"></p></template>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Username</label>
                            <input type="text" x-model="form.username" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                            <template x-if="errors.username"><p class="mt-1 text-xs text-red-500" x-text="errors.username[0]"></p></template>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
                        <input type="email" x-model="form.email" autocomplete="username" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" :class="errors.email && 'border-red-400'">
                        <template x-if="errors.email"><p class="mt-1 text-xs text-red-500" x-text="errors.email[0]"></p></template>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Password <span class="text-red-500" x-show="!isEdit">*</span></label>
                            <p class="mb-1.5 text-xs text-gray-500" x-show="isEdit" x-cloak>Kosongkan jika tidak diubah.</p>
                            <p class="mb-1.5 text-xs text-gray-500" x-show="!isEdit">Minimal 8 karakter.</p>
                            <input type="password" x-model="form.password" autocomplete="new-password" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" :class="errors.password && 'border-red-400'">
                            <template x-if="errors.password"><p class="mt-1 text-xs text-red-500" x-text="errors.password[0]"></p></template>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Ulangi password <span class="text-red-500" x-show="!isEdit">*</span></label>
                            <p class="mb-1.5 text-xs text-gray-500" x-show="isEdit" x-cloak>Isi jika mengganti password.</p>
                            <p class="mb-1.5 text-xs text-gray-500" x-show="!isEdit">Harus sama dengan password di kiri.</p>
                            <input type="password" x-model="form.password_confirmation" autocomplete="new-password" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Divisi <span class="text-red-500">*</span></label>
                            <select x-model="form.section" :key="'section-'+formBindKey" @change="syncRole(); syncSpeedshopAssignmentFields()" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" :class="errors.section && 'border-red-400'">
                                @foreach($adminDivisiOptions as $opt)
                                    <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                @endforeach
                            </select>
                            <template x-if="errors.section"><p class="mt-1 text-xs text-red-500" x-text="errors.section[0]"></p></template>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Role <span class="text-red-500">*</span></label>
                            @php
                                $roleSelectClass = 'w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40';
                            @endphp
                            <template x-if="form.section === @js(\App\Models\User::SECTION_OWNER)">
                                <select x-model="form.role" @change="syncSpeedshopAssignmentFields()" class="{{ $roleSelectClass }}" :class="errors.role && 'border-red-400'">
                                    @foreach($adminRolesBySection[\App\Models\User::SECTION_OWNER] ?? [] as $r)
                                        <option value="{{ $r }}">{{ $r }}</option>
                                    @endforeach
                                </select>
                            </template>
                            <template x-if="form.section === 'speedshop'">
                                <select
                                    x-model="form.role"
                                    :key="'role-speedshop-'+formBindKey"
                                    @change="syncSpeedshopAssignmentFields()"
                                    class="{{ $roleSelectClass }}"
                                    :class="errors.role && 'border-red-400'"
                                >
                                    @foreach($adminRolesBySection['speedshop'] ?? [] as $r)
                                        <option value="{{ $r }}">{{ $r }}</option>
                                    @endforeach
                                </select>
                            </template>
                            <template x-if="form.section === @js(\App\Models\User::SECTION_MODIFIKASI_ORI)">
                                <select x-model="form.role" @change="syncSpeedshopAssignmentFields()" class="{{ $roleSelectClass }}" :class="errors.role && 'border-red-400'">
                                    @foreach($adminRolesBySection[\App\Models\User::SECTION_MODIFIKASI_ORI] ?? [] as $r)
                                        <option value="{{ $r }}">{{ $r }}</option>
                                    @endforeach
                                </select>
                            </template>
                            <template x-if="form.section !== @js(\App\Models\User::SECTION_OWNER) && form.section !== 'speedshop' && form.section !== @js(\App\Models\User::SECTION_MODIFIKASI_ORI)">
                                <select x-model="form.role" @change="syncSpeedshopAssignmentFields()" class="{{ $roleSelectClass }}" :class="errors.role && 'border-red-400'">
                                    <template x-for="r in roleList" :key="r">
                                        <option :value="r" x-text="r"></option>
                                    </template>
                                </select>
                            </template>
                            <template x-if="errors.role"><p class="mt-1 text-xs text-red-500" x-text="errors.role[0]"></p></template>
                        </div>
                    </div>
                    <div x-show="form.section === 'speedshop' && speedshopRolesWithMultiCabang.includes(form.role)" x-cloak>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Cabang speedshop <span class="text-red-500">*</span></label>
                        <p class="mb-2 text-xs text-gray-500" x-show="form.role === 'Accounting'" x-cloak>Accounting bisa handle lebih dari satu cabang. Centang cabang yang dikelola.</p>
                        <p class="mb-2 text-xs text-gray-500" x-show="form.role === @js(\App\Models\User::ROLE_SPEEDSHOP_HEAD_AREA)" x-cloak>Head Area bisa dibagi per wilayah. Centang cabang yang menjadi tanggung jawab akun ini.</p>
                        <div class="grid max-h-48 grid-cols-1 gap-2 overflow-y-auto rounded-xl border border-gray-200 p-3 sm:grid-cols-2">
                            @foreach($speedshops as $s)
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
                                <input type="checkbox" class="rounded border-gray-300 accent-brand text-brand focus:ring-brand"
                                       :value="@js((string) $s->id)"
                                       :checked="form.speedshop_ids.includes(@js((string) $s->id))"
                                       @change="toggleSpeedshopId(@js((string) $s->id), $event.target.checked)">
                                <span>{{ $s->nama }}</span>
                            </label>
                            @endforeach
                        </div>
                        <template x-if="errors.speedshop_ids || errors.speedshop_id"><p class="mt-1 text-xs text-red-500" x-text="(errors.speedshop_ids || errors.speedshop_id || [])[0]"></p></template>
                    </div>
                    <div x-show="form.section === 'speedshop' && speedshopRolesWithSingleCabang.includes(form.role) && !speedshopRolesWithMultiCabang.includes(form.role)" x-cloak>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Cabang speedshop <span class="text-red-500" x-show="form.role !== 'Manager'">*</span></label>
                        <p class="mb-1.5 text-xs text-gray-500" x-show="form.role === 'Manager'" x-cloak>Manager cabang: pilih satu outlet. Kosongkan untuk manager lintas cabang (semua outlet).</p>
                        <p class="mb-1.5 text-xs text-gray-500" x-show="form.role !== 'Manager'" x-cloak>Mekanik dan FLP terikat ke satu outlet.</p>
                        <select x-model="form.speedshop_id" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" :class="errors.speedshop_id && 'border-red-400'">
                            <option value="">— Pilih cabang —</option>
                            @foreach($speedshops as $s)
                                <option value="{{ $s->id }}">{{ $s->nama }}</option>
                            @endforeach
                        </select>
                        <template x-if="errors.speedshop_id"><p class="mt-1 text-xs text-red-500" x-text="errors.speedshop_id[0]"></p></template>
                    </div>
                    <div x-show="isSpeedshopMekanikForm" x-cloak class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Level mekanik <span class="text-red-500">*</span></label>
                            <select x-model="form.mekanik_level_id" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" :class="errors.mekanik_level_id && 'border-red-400'">
                                <option value="">— Pilih level —</option>
                                @foreach($mekanikLevels as $level)
                                    <option value="{{ $level->id }}">{{ $level->nama }}</option>
                                @endforeach
                            </select>
                            <template x-if="errors.mekanik_level_id"><p class="mt-1 text-xs text-red-500" x-text="errors.mekanik_level_id[0]"></p></template>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">No HP</label>
                            <input type="text" x-model="form.no_hp" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" :class="errors.no_hp && 'border-red-400'">
                            <template x-if="errors.no_hp"><p class="mt-1 text-xs text-red-500" x-text="errors.no_hp[0]"></p></template>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">PSA — Lokasi absensi</label>
                        <p class="mb-1.5 text-xs text-gray-500">Titik absensi karyawan. Kosongkan untuk mengikuti cabang/gudang seperti sebelumnya. Daftar lokasi di <a href="{{ route('admin.attendance-locations.index') }}" class="font-medium text-red-600 hover:underline" target="_blank" rel="noopener">Lokasi Absensi</a>.</p>
                        <select x-model="form.attendance_location_id" class="w-full rounded-xl border border-gray-200 px-4 py-2.5 text-sm transition focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40" :class="errors.attendance_location_id && 'border-red-400'">
                            <option value="">— Otomatis (sesuai divisi/cabang) —</option>
                            @foreach($attendanceLocations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->displayName() }}@unless($loc->is_active) (nonaktif)@endunless</option>
                            @endforeach
                        </select>
                        <template x-if="errors.attendance_location_id"><p class="mt-1 text-xs text-red-500" x-text="errors.attendance_location_id[0]"></p></template>
                    </div>
                </div>
                <div class="mt-8 flex items-center justify-end gap-3">
                    <button type="button" @click="save()" :disabled="loading" class="rounded-xl bg-brand px-6 py-2.5 text-sm font-semibold text-brand-ink shadow-sm transition hover:bg-brand-hover disabled:opacity-50">
                        <span x-show="!loading" x-text="isEdit ? 'Update' : 'Simpan'"></span>
                        <span x-show="loading">Menyimpan…</span>
                    </button>
                    <button type="button" @click="closeModal()" class="rounded-xl border border-gray-200 px-6 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50">Batal</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Import --}}
    <div x-show="showImportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" @keydown.escape.window="showImportModal = false">
        <div x-show="showImportModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="showImportModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div x-show="showImportModal" x-transition class="wo-modal-panel relative w-full max-w-2xl !p-6 sm:!p-8" @click.stop>
                <h3 class="text-lg font-bold text-gray-900">Import data karyawan</h3>
                <p class="mt-1 text-sm text-gray-500">Template: Nama, Username, Email, Divisi, Role, Gudang, Cabang, Level Mekanik, No HP, Password. Role Mekanik wajib isi Level Mekanik. Accounting boleh multi-cabang dengan pemisah & (contoh: Speedshop Bandung - KTP & Speedshop Bogor - CLK). Default: update by email. Centang sinkron penuh untuk menghapus karyawan yang tidak ada di file.</p>
                <div class="mt-6">
                    <p class="wo-label mb-3">Upload File Excel</p>
                    <form action="{{ route('admin.users.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <label class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-200 bg-gray-50/50 px-8 py-14 transition hover:border-red-300 hover:bg-red-50/30">
                            <svg class="h-14 w-14 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                            <span class="mt-3 text-sm font-medium text-gray-600" x-text="importFileName || 'Klik untuk memilih file .xlsx atau .xls'"></span>
                            <input type="file" name="file" accept=".xlsx,.xls" class="hidden" @change="importFileName = $event.target.files[0]?.name || ''">
                        </label>
                        <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-xl border border-amber-200 bg-amber-50/70 px-4 py-3">
                            <input type="checkbox" name="sync_replace" value="1" class="mt-1 h-4 w-4 rounded border-gray-300 accent-brand text-brand focus:ring-brand">
                            <span>
                                <span class="block text-sm font-semibold text-amber-950">Sinkron penuh (disarankan untuk data bersih)</span>
                                <span class="mt-0.5 block text-xs text-amber-900/80">Update/tambah dari Excel, lalu hapus karyawan yang tidak ada di file. Akun login Anda tidak ikut dihapus. Jika ada baris error, seluruh sinkron dibatalkan.</span>
                            </span>
                        </label>
                        <div class="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 pt-6">
                            <a href="{{ route('admin.users.template') }}" target="_blank" rel="noopener" class="btn-secondary inline-flex items-center gap-2 border-emerald-200 bg-emerald-50 px-4 py-2.5 text-emerald-700 hover:bg-emerald-100">
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
</div>
@endsection

@push('scripts')
<script>
function clearRoleAndSubmit(sectionSelect) {
    const form = sectionSelect.form;
    const roleSelect = form?.querySelector('select[name="role"]');
    if (roleSelect) {
        roleSelect.value = '';
    }
    form.submit();
}

function adminUsersCrud(currentUserId) {
    const base = '/admin/users';
    const roles = @json($adminRolesBySection);
    const speedshopRolesRequiringCabang = @json(\App\Support\AdminUserPayload::speedshopRolesRequiringCabang());
    const speedshopRolesWithSingleCabang = @json(\App\Support\AdminUserPayload::speedshopRolesWithSingleCabangSelect());
    const speedshopRolesWithoutCabang = @json(\App\Support\AdminUserPayload::speedshopRolesWithoutCabang());
    const speedshopRolesWithMultiCabang = @json(\App\Support\AdminUserPayload::speedshopRolesWithMultiCabang());
    const speedshopMekanikRole = @json(\App\Models\User::ROLE_SPEEDSHOP_MEKANIK);
    const defaultSection = @json($adminDivisiOptions[0]['value'] ?? 'owner');
    const defaultRole = roles[defaultSection]?.[0] ?? '';
    return {
        currentUserId,
        showImportModal: false,
        importFileName: '',
        showModal: false,
        isEdit: false,
        editId: null,
        formBindKey: 0,
        loading: false,
        errors: {},
        roles,
        speedshopRolesRequiringCabang,
        speedshopRolesWithSingleCabang,
        speedshopRolesWithoutCabang,
        speedshopRolesWithMultiCabang,
        speedshopMekanikRole,
        get isSpeedshopMekanikForm() {
            return this.form.section === 'speedshop' && this.form.role === this.speedshopMekanikRole;
        },
        form: {
            name: '',
            username: '',
            email: '',
            password: '',
            password_confirmation: '',
            section: defaultSection,
            role: defaultRole,
            warehouse_id: '',
            speedshop_id: '',
            speedshop_ids: [],
            mekanik_level_id: '',
            no_hp: '',
            attendance_location_id: '',
        },
        get roleList() {
            return this.roles[this.form.section] || [];
        },
        toggleSpeedshopId(id, checked) {
            id = String(id);
            if (checked) {
                if (!this.form.speedshop_ids.includes(id)) {
                    this.form.speedshop_ids = [...this.form.speedshop_ids, id];
                }
            } else {
                this.form.speedshop_ids = this.form.speedshop_ids.filter((x) => x !== id);
            }
        },
        syncRole() {
            const list = this.roleList;
            if (!list.length) {
                this.form.role = '';
                return;
            }
            if (!list.includes(this.form.role)) {
                this.form.role = list[0];
            }
            this.syncSpeedshopAssignmentFields();
        },
        syncSpeedshopAssignmentFields() {
            if (this.form.section === 'owner' || this.form.section === @json(\App\Models\User::SECTION_MODIFIKASI_ORI)) {
                this.form.speedshop_id = '';
                this.form.speedshop_ids = [];
                this.form.warehouse_id = '';
                this.form.mekanik_level_id = '';
                this.form.no_hp = '';
                return;
            }
            if (this.form.section !== 'speedshop') {
                this.form.mekanik_level_id = '';
                this.form.no_hp = '';
                return;
            }
            if (this.speedshopRolesWithoutCabang.includes(this.form.role)) {
                this.form.speedshop_id = '';
                this.form.speedshop_ids = [];
                this.form.warehouse_id = '';
            } else {
                this.form.warehouse_id = '';
            }
            if (! this.speedshopRolesWithMultiCabang.includes(this.form.role)) {
                this.form.speedshop_ids = [];
            }
            if (! this.isSpeedshopMekanikForm) {
                this.form.mekanik_level_id = '';
                this.form.no_hp = '';
            }
        },
        blankForm() {
            return {
                name: '',
                username: '',
                email: '',
                password: '',
                password_confirmation: '',
                section: defaultSection,
                role: defaultRole,
                warehouse_id: '',
                speedshop_id: '',
                speedshop_ids: [],
                mekanik_level_id: '',
                no_hp: '',
                attendance_location_id: '',
            };
        },
        closeModal() {
            this.showModal = false;
            this.loading = false;
            this.errors = {};
            this.isEdit = false;
            this.editId = null;
            this.form = this.blankForm();
            this.formBindKey += 1;
        },
        openCreate() {
            this.isEdit = false;
            this.editId = null;
            this.errors = {};
            this.form = this.blankForm();
            this.formBindKey += 1;
            this.showModal = true;
        },
        async openEdit(id) {
            this.isEdit = true;
            this.editId = id;
            this.errors = {};
            this.loading = true;
            this.form = this.blankForm();
            this.formBindKey += 1;
            this.showModal = true;
            try {
                const res = await fetch(`${base}/${id}`, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                const section = data.section || defaultSection;
                const list = this.roles[section] || [];
                let role = data.role || '';
                if (data.mekanik_level_id && section === 'speedshop') {
                    role = this.speedshopMekanikRole;
                }
                if (! role) {
                    role = list[0] || defaultRole;
                } else if (! list.includes(role)) {
                    role = list.includes(this.speedshopMekanikRole) && data.mekanik_level_id
                        ? this.speedshopMekanikRole
                        : (list[0] || defaultRole);
                }
                this.form = {
                    name: data.name || '',
                    username: data.username || '',
                    email: data.email || '',
                    password: '',
                    password_confirmation: '',
                    section,
                    role,
                    warehouse_id: data.warehouse_id ? String(data.warehouse_id) : '',
                    speedshop_id: data.speedshop_id ? String(data.speedshop_id) : '',
                    speedshop_ids: (data.speedshop_ids || []).map((sid) => String(sid)),
                    mekanik_level_id: data.mekanik_level_id ? String(data.mekanik_level_id) : '',
                    no_hp: data.no_hp || '',
                    attendance_location_id: data.attendance_location_id ? String(data.attendance_location_id) : '',
                };
                this.formBindKey += 1;
                await this.$nextTick();
                await this.$nextTick();
                this.syncSpeedshopAssignmentFields();
            } finally {
                this.loading = false;
            }
        },
        async save() {
            this.loading = true;
            this.errors = {};
            const payload = {
                name: this.form.name,
                username: this.form.username || null,
                email: this.form.email,
                section: this.form.section,
                role: this.form.role,
                attendance_location_id: this.form.attendance_location_id || null,
            };
            if (this.form.section === 'owner' || this.form.section === @json(\App\Models\User::SECTION_MODIFIKASI_ORI)) {
                payload.warehouse_id = null;
                payload.speedshop_id = null;
            } else if (this.form.section === 'speedshop') {
                if (this.speedshopRolesWithoutCabang.includes(this.form.role)) {
                    payload.warehouse_id = null;
                    payload.speedshop_id = null;
                    payload.speedshop_ids = [];
                } else if (this.speedshopRolesWithMultiCabang.includes(this.form.role)) {
                    payload.warehouse_id = null;
                    payload.speedshop_ids = this.form.speedshop_ids || [];
                    payload.speedshop_id = payload.speedshop_ids.length === 1 ? payload.speedshop_ids[0] : null;
                } else if (this.form.role === 'Manager') {
                    payload.warehouse_id = null;
                    payload.speedshop_id = this.form.speedshop_id || null;
                    payload.speedshop_ids = payload.speedshop_id ? [payload.speedshop_id] : [];
                } else {
                    payload.speedshop_id = this.form.speedshop_id || null;
                    payload.speedshop_ids = payload.speedshop_id ? [payload.speedshop_id] : [];
                    payload.warehouse_id = null;
                }
            }
            if (this.isSpeedshopMekanikForm) {
                payload.mekanik_level_id = this.form.mekanik_level_id || null;
                payload.no_hp = this.form.no_hp || null;
            }
            if (!this.isEdit) {
                payload.password = this.form.password;
                payload.password_confirmation = this.form.password_confirmation;
            } else if (this.form.password) {
                payload.password = this.form.password;
                payload.password_confirmation = this.form.password_confirmation;
            }
            try {
                if (this.isEdit) {
                    await window.axios.put(`${base}/${this.editId}`, payload);
                } else {
                    await window.axios.post(base, payload);
                }
                window.location.reload();
            } catch (e) {
                const d = e.response?.data || {};
                this.errors = d.errors || {};
                if (e.response?.status === 419) {
                    this.errors = { section: ['Sesi formulir kedaluwarsa. Muat ulang halaman, lalu Update lagi.'] };
                } else if (d.message && !Object.keys(this.errors).length) {
                    this.errors = { section: [d.message] };
                }
                this.loading = false;
            }
        },
        async destroy(id) {
            if (id === this.currentUserId) {
                return;
            }
            const result = await Swal.fire({
                title: 'Hapus karyawan?',
                text: 'Data yang dihapus tidak dapat dikembalikan.',
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
            try {
                await window.axios.delete(`${base}/${id}`);
                window.location.reload();
            } catch (e) {
                Swal.fire('Gagal', e.response?.data?.message || 'Tidak dapat menghapus karyawan.', 'error');
            }
        },
    };
}
</script>
@endpush

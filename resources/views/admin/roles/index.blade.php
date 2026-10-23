@extends('layouts.admin')

@section('title', 'Master Data — Role')

@push('styles')
@include('partials.speedshop.work-order-create-styles')
@endpush

@section('content')
<div class="ss-wo-create space-y-5">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('admin.dashboard') }}" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">Role</span>
    </nav>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Total Role</p>
                    <p class="mt-1 text-[40px] font-semibold leading-none text-gray-900">{{ number_format($statTotalRoles) }}</p>
                    <p class="mt-1 text-[11px] text-gray-400">Sesuai filter saat ini</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Karyawan Terpetakan</p>
                    <p class="mt-1 text-[40px] font-semibold leading-none text-blue-700">{{ number_format($statTotalKaryawan) }}</p>
                    <p class="mt-1 text-[11px] text-gray-400">Jumlah akun per kombinasi divisi & role</p>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <form method="GET" action="{{ route('admin.roles.index') }}">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Role</h2>
                    <p class="mt-1 text-[13px] text-gray-500">Daftar role per divisi dan jumlah karyawan yang memakainya.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.roles.export', request()->only(['section', 'role'])) }}" class="inline-flex h-10 items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-medium text-emerald-700 transition hover:bg-emerald-100">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </a>
                    <a href="{{ route('admin.roles.pdf', request()->only(['section', 'role'])) }}" target="_blank" rel="noopener" class="btn-export-pdf">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Export PDF
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="btn-secondary inline-flex h-10 items-center gap-2 px-4">
                        Kelola Karyawan
                    </a>
                </div>
            </div>

            @php
                $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
            @endphp

            <div class="mt-5 overflow-x-auto rounded-xl border border-gray-200">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="w-16 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Divisi</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Role</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Jumlah Karyawan</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <td class="px-5 py-2"></td>
                            <td class="px-5 py-2 font-normal">
                                <select name="section" onchange="this.form.submit()" class="{{ $filterBaseClass }}">
                                    <option value="">Semua divisi</option>
                                    @foreach($adminDivisiOptions as $opt)
                                        <option value="{{ $opt['value'] }}" @selected(request('section') === $opt['value'])>{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-5 py-2 font-normal">
                                <select name="role" onchange="this.form.submit()" class="{{ $filterBaseClass }}">
                                    <option value="">Semua role</option>
                                    @foreach($filterRoleOptions as $roleOpt)
                                        <option value="{{ $roleOpt }}" @selected(request('role') === $roleOpt)>{{ $roleOpt }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-5 py-2"></td>
                            <td class="px-5 py-2"></td>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($rows as $i => $row)
                            <tr class="transition hover:bg-[#fcfafb]">
                                <td class="px-5 py-4 text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ $row['section_label'] }}</td>
                                <td class="px-5 py-4 font-semibold text-gray-900">
                                    {{ $row['role'] }}
                                    @unless($row['in_catalog'])
                                        <span class="ml-1 rounded-md bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-800">Legacy</span>
                                    @endunless
                                </td>
                                <td class="px-5 py-4 text-gray-700">{{ number_format($row['karyawan_count']) }}</td>
                                <td class="px-5 py-4">
                                    <a href="{{ route('admin.users.index', ['section' => $row['section'], 'role' => $row['role']]) }}"
                                       class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 text-xs font-medium text-gray-700 transition hover:border-red-200 hover:bg-red-50 hover:text-red-700">
                                        Lihat karyawan
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-gray-400">Tidak ada role sesuai filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>
@endsection

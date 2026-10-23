@extends('layouts.admin')

@section('title', 'Rekap Absensi')

@section('content')
@php
    $bulanParam = $calendarMonth->format('Y-m');
    $prevMonth = $calendarMonth->copy()->subMonth()->format('Y-m');
    $nextMonth = $calendarMonth->copy()->addMonth()->format('Y-m');
    $canGoNext = $calendarMonth->copy()->addMonth()->lte(now()->startOfMonth());
@endphp

<div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Rekap Absensi</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $calendarMonth->translatedFormat('F Y') }} · telat mengacu jadwal masuk per lokasi (lihat Jadwal Absensi)
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.attendance-recap.index', array_merge($filters, ['bulan' => $prevMonth])) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">‹</a>
        <span class="min-w-[100px] text-center text-sm font-semibold text-gray-800">{{ $calendarMonth->translatedFormat('M Y') }}</span>
        @if($canGoNext)
        <a href="{{ route('admin.attendance-recap.index', array_merge($filters, ['bulan' => $nextMonth])) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">›</a>
        @else
        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-100 text-gray-300">›</span>
        @endif
    </div>
</div>

<div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Hari kerja</p>
        <p class="mt-1 text-3xl font-bold text-gray-900">{{ number_format($summary['work_days']) }}</p>
    </div>
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Hadir</p>
        <p class="mt-1 text-3xl font-bold text-emerald-800">{{ number_format($summary['present']) }}</p>
    </div>
    <div class="rounded-2xl border border-red-200 bg-red-50 p-4 shadow-sm">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-red-700">Tidak hadir</p>
        <p class="mt-1 text-3xl font-bold text-red-800">{{ number_format($summary['absent']) }}</p>
    </div>
    <div class="rounded-2xl border border-violet-200 bg-violet-50 p-4 shadow-sm">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700">Hari libur</p>
        <p class="mt-1 text-3xl font-bold text-violet-800">{{ number_format($summary['off_days']) }}</p>
    </div>
</div>

<form method="GET" action="{{ route('admin.attendance-recap.index') }}" class="mb-4 flex flex-wrap items-end gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
    <input type="hidden" name="bulan" value="{{ $bulanParam }}">
    <div class="min-w-[180px] flex-1">
        <label class="mb-1 block text-xs font-semibold text-gray-500">Nama</label>
        <input type="text" name="name" value="{{ $filters['name'] ?? '' }}" placeholder="Cari nama..."
               class="h-10 w-full rounded-xl border border-gray-200 px-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
    </div>
    <div class="min-w-[160px]">
        <label class="mb-1 block text-xs font-semibold text-gray-500">Divisi</label>
        <select name="section" class="h-10 w-full rounded-xl border border-gray-200 bg-white px-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
            <option value="">Semua</option>
            @foreach($adminDivisiOptions as $opt)
            <option value="{{ $opt['value'] }}" @selected(($filters['section'] ?? '') === $opt['value'])>{{ $opt['label'] }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="inline-flex h-10 items-center rounded-xl bg-gray-900 px-4 text-sm font-semibold text-white hover:bg-gray-800">Filter</button>
    <a href="{{ route('admin.attendance-recap.index', ['bulan' => $bulanParam]) }}" class="inline-flex h-10 items-center rounded-xl border border-gray-200 px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
</form>

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-5 py-3.5">No.</th>
                    <th class="px-5 py-3.5">Karyawan</th>
                    <th class="px-5 py-3.5">Divisi</th>
                    <th class="px-5 py-3.5 text-center">Hari kerja</th>
                    <th class="px-5 py-3.5 text-center">Hadir</th>
                    <th class="px-5 py-3.5 text-center">Tidak hadir</th>
                    <th class="px-5 py-3.5 text-center">Libur</th>
                    <th class="px-5 py-3.5 text-center">Kehadiran</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($rows as $i => $row)
                @php
                    $user = $row['user'];
                    $rate = $row['work_days'] > 0 ? round(($row['present'] / $row['work_days']) * 100) : null;
                @endphp
                <tr class="hover:bg-gray-50/60">
                    <td class="px-5 py-4 text-gray-500">{{ $i + 1 }}</td>
                    <td class="px-5 py-4">
                        <p class="font-semibold text-gray-900">{{ $user->name }}</p>
                        <p class="text-xs text-gray-500">{{ $user->email }}</p>
                    </td>
                    <td class="px-5 py-4 text-gray-700">{{ $user->section }} · {{ $user->role }}</td>
                    <td class="px-5 py-4 text-center tabular-nums">{{ $row['work_days'] }}</td>
                    <td class="px-5 py-4 text-center font-semibold tabular-nums text-emerald-700">{{ $row['present'] }}</td>
                    <td class="px-5 py-4 text-center font-semibold tabular-nums text-red-700">{{ $row['absent'] }}</td>
                    <td class="px-5 py-4 text-center tabular-nums text-violet-700">{{ $row['off_days'] }}</td>
                    <td class="px-5 py-4 text-center">
                        @if($rate !== null)
                        <span @class([
                            'inline-flex rounded-lg px-2 py-0.5 text-xs font-semibold',
                            'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' => $rate >= 90,
                            'bg-amber-50 text-amber-700 ring-1 ring-amber-200' => $rate >= 70 && $rate < 90,
                            'bg-red-50 text-red-700 ring-1 ring-red-200' => $rate < 70,
                        ])>{{ $rate }}%</span>
                        @else
                        <span class="text-gray-400">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-12 text-center text-gray-400">Tidak ada data karyawan untuk filter ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

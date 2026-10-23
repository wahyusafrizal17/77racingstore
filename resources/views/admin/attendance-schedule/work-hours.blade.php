@extends('layouts.admin')

@section('title', 'Jam Kerja')

@section('content')
@php
    $bulanParam = $calendarMonth->format('Y-m');
    $locTitle = $selectedLocation?->displayName() ?? 'Lokasi';
    $indexUrl = route('admin.attendance-schedule.index', ['bulan' => $bulanParam]);
    $holidaysUrl = route('admin.attendance-schedule.holidays', [
        'location_id' => $locationId,
        'bulan' => $bulanParam,
    ]);
    $weekdayHours = old('days')
        ? collect($locationWeekdayHours)->map(function (array $row) {
            $day = $row['day_of_week'];
            $old = old('days.'.$day, []);

            return [
                'day_of_week' => $day,
                'label' => $row['label'],
                'check_in' => $old['check_in_time'] ?? $row['check_in'],
                'check_out' => $old['check_out_time'] ?? $row['check_out'],
                'check_in_window_start' => $old['check_in_window_start'] ?? ($row['check_in_window_start'] ?? '07:00'),
                'check_in_window_end' => $old['check_in_window_end'] ?? ($row['check_in_window_end'] ?? '09:00'),
                'check_out_window_start' => $old['check_out_window_start'] ?? ($row['check_out_window_start'] ?? $row['check_out']),
                'check_out_window_end' => $old['check_out_window_end'] ?? ($row['check_out_window_end'] ?? '23:59'),
            ];
        })->all()
        : $locationWeekdayHours;
@endphp

@if($errors->any())
<div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
    {{ $errors->first() }}
</div>
@endif

@if(session('success'))
<div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif

<nav class="mb-4 flex flex-wrap items-center gap-2 text-sm text-gray-500">
    <a href="{{ route('admin.dashboard') }}" class="hover:text-gray-800">Dashboard</a>
    <span>/</span>
    <a href="{{ $indexUrl }}" class="hover:text-gray-800">Jadwal Absensi</a>
    <span>/</span>
    <span class="font-medium text-gray-900">Jam Kerja</span>
</nav>

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Jam Kerja — {{ $locTitle }}</h1>
        <p class="mt-1 text-sm text-gray-500">Atur jam masuk/pulang serta batas absen per hari (acuan telat & jendela absensi).</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ $indexUrl }}" class="inline-flex h-10 items-center rounded-xl border border-gray-200 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">← Kembali</a>
        <a href="{{ $holidaysUrl }}" class="inline-flex h-10 items-center rounded-xl bg-amber-50 px-4 text-sm font-semibold text-amber-900 ring-1 ring-amber-200 hover:bg-amber-100">Jadwal Libur</a>
    </div>
</div>

<div class="mx-auto max-w-5xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
    @if($selectedLocation && $canEdit)
    <form method="POST" action="{{ route('admin.attendance-schedule.locations.update', $selectedLocation) }}" class="px-6 py-6">
        @csrf
        @method('PUT')
        <input type="hidden" name="bulan" value="{{ $bulanParam }}">
        <input type="hidden" name="return_to" value="work-hours">

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-100 text-xs uppercase tracking-wide text-gray-500">
                        <th class="pb-3 pr-3 font-semibold">Hari</th>
                        <th class="pb-3 pr-3 font-semibold">Jam masuk</th>
                        <th class="pb-3 pr-3 font-semibold">Batas absen masuk</th>
                        <th class="pb-3 pr-3 font-semibold">Jam pulang</th>
                        <th class="pb-3 font-semibold">Batas absen pulang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($weekdayHours as $row)
                    <tr>
                        <td class="py-3 pr-3 font-medium text-gray-900">{{ $row['label'] }}</td>
                        <td class="py-3 pr-3">
                            <input type="time" name="days[{{ $row['day_of_week'] }}][check_in_time]" value="{{ $row['check_in'] }}" required
                                   class="h-10 w-full min-w-[7rem] rounded-xl border border-gray-200 px-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                        </td>
                        <td class="py-3 pr-3">
                            <div class="flex items-center gap-1.5">
                                <input type="time" name="days[{{ $row['day_of_week'] }}][check_in_window_start]" value="{{ $row['check_in_window_start'] }}" required
                                       class="h-10 w-full min-w-[6.5rem] rounded-xl border border-gray-200 px-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                                <span class="text-gray-400">–</span>
                                <input type="time" name="days[{{ $row['day_of_week'] }}][check_in_window_end]" value="{{ $row['check_in_window_end'] }}" required
                                       class="h-10 w-full min-w-[6.5rem] rounded-xl border border-gray-200 px-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                            </div>
                        </td>
                        <td class="py-3 pr-3">
                            <input type="time" name="days[{{ $row['day_of_week'] }}][check_out_time]" value="{{ $row['check_out'] }}" required
                                   class="h-10 w-full min-w-[7rem] rounded-xl border border-gray-200 px-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                        </td>
                        <td class="py-3">
                            <div class="flex items-center gap-1.5">
                                <input type="time" name="days[{{ $row['day_of_week'] }}][check_out_window_start]" value="{{ $row['check_out_window_start'] }}" required
                                       class="h-10 w-full min-w-[6.5rem] rounded-xl border border-gray-200 px-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                                <span class="text-gray-400">–</span>
                                <input type="time" name="days[{{ $row['day_of_week'] }}][check_out_window_end]" value="{{ $row['check_out_window_end'] }}" required
                                       class="h-10 w-full min-w-[6.5rem] rounded-xl border border-gray-200 px-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <button type="submit" class="mt-5 inline-flex h-10 w-full items-center justify-center rounded-xl bg-brand text-sm font-semibold text-brand-ink hover:bg-brand-hover sm:w-auto sm:px-6">
            Simpan jam kerja
        </button>
    </form>
    @elseif($selectedLocation)
    <div class="overflow-x-auto px-6 py-6">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead>
                <tr class="border-b border-gray-100 text-xs uppercase tracking-wide text-gray-500">
                    <th class="pb-3 pr-3 font-semibold">Hari</th>
                    <th class="pb-3 pr-3 font-semibold">Jam masuk</th>
                    <th class="pb-3 pr-3 font-semibold">Batas absen masuk</th>
                    <th class="pb-3 pr-3 font-semibold">Jam pulang</th>
                    <th class="pb-3 font-semibold">Batas absen pulang</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($locationWeekdayHours as $row)
                <tr>
                    <td class="py-3 pr-3 font-medium text-gray-900">{{ $row['label'] }}</td>
                    <td class="py-3 pr-3 tabular-nums text-gray-800">{{ $row['check_in'] }}</td>
                    <td class="py-3 pr-3 tabular-nums text-gray-800">{{ $row['check_in_window_start'] }}–{{ $row['check_in_window_end'] }}</td>
                    <td class="py-3 pr-3 tabular-nums text-gray-800">{{ $row['check_out'] }}</td>
                    <td class="py-3 tabular-nums text-gray-800">{{ $row['check_out_window_start'] }}–{{ $row['check_out_window_end'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="px-6 py-10 text-center text-sm text-gray-500">Tidak dapat mengubah jadwal lokasi ini.</div>
    @endif
</div>

<p class="mt-4 text-center text-sm text-gray-500">{{ $locationEmployees->count() }} karyawan terdaftar di lokasi ini.</p>
@endsection

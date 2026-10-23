@extends('layouts.admin')

@section('title', 'Jadwal Absensi')

@section('content')
@php
    $bulanParam = $calendarMonth->format('Y-m');
    $prevMonth = $calendarMonth->copy()->subMonth()->format('Y-m');
    $nextMonth = $calendarMonth->copy()->addMonth()->format('Y-m');
    $monthQuery = ['bulan' => $bulanParam];
    $workModalLocations = $locationRows->map(fn ($row) => [
        'id' => (int) $row['location']->id,
        'name' => $row['location']->displayName(),
        'hours_summary' => $row['hours_summary'],
        'weekday_hours' => collect($row['weekday_hours'])->map(fn (array $h) => [
            'day_of_week' => (int) $h['day_of_week'],
            'label' => $h['label'],
            'check_in' => $h['check_in'],
            'check_out' => $h['check_out'],
            'check_in_window_start' => $h['check_in_window_start'] ?? '07:00',
            'check_in_window_end' => $h['check_in_window_end'] ?? '09:00',
            'check_out_window_start' => $h['check_out_window_start'] ?? $h['check_out'],
            'check_out_window_end' => $h['check_out_window_end'] ?? '23:59',
        ])->values()->all(),
        'update_url' => route('admin.attendance-schedule.locations.update', $row['location']),
        'work_hours_url' => route('admin.attendance-schedule.work-hours', array_merge($monthQuery, [
            'location_id' => (int) $row['location']->id,
        ])),
    ])->values();
@endphp

@if($errors->any())
<div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
    {{ $errors->first() }}
</div>
@endif

@if(session('success'))
<div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif

<div
    x-data="attendanceScheduleIndex({
        locations: @js($workModalLocations),
        canEdit: @js((bool) $canEdit),
        bulan: @js($bulanParam),
    })"
>

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Jadwal Absensi</h1>
            <p class="mt-1 text-sm text-gray-500">Pilih lokasi PSA untuk atur jam kerja per hari atau jadwal libur karyawan.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.attendance-schedule.index', ['bulan' => $prevMonth]) }}"
               class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">‹</a>
            <span class="min-w-[120px] text-center text-sm font-semibold text-gray-800">{{ $calendarMonth->translatedFormat('F Y') }}</span>
            <a href="{{ route('admin.attendance-schedule.index', ['bulan' => $nextMonth]) }}"
               class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">›</a>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="text-lg font-bold text-gray-900">Lokasi PSA</h2>
            <p class="mt-0.5 text-sm text-gray-500">Bulan acuan jadwal libur: {{ $calendarMonth->translatedFormat('F Y') }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3.5 w-14">No</th>
                        <th class="px-5 py-3.5">Lokasi</th>
                        <th class="px-5 py-3.5 text-center">Karyawan</th>
                        <th class="px-5 py-3.5">Jam kerja</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($locationRows as $i => $row)
                    @php
                        $loc = $row['location'];
                        $locId = (int) $loc->id;
                        $holidaysHref = route('admin.attendance-schedule.holidays', array_merge($monthQuery, ['location_id' => $locId]));
                    @endphp
                    <tr class="hover:bg-gray-50/60">
                        <td class="px-5 py-4 text-gray-500">{{ $i + 1 }}</td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900">{{ $loc->displayName() }}</p>
                            @if($loc->isHeadOffice())
                            <p class="text-xs text-gray-500">Senin–Sabtu · Minggu libur mingguan</p>
                            @else
                            <p class="text-xs text-gray-500">Libur per karyawan (grid bulanan)</p>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-center tabular-nums text-gray-800">{{ $row['employee_count'] }}</td>
                        <td class="px-5 py-4 text-gray-800">
                            <span class="tabular-nums">{{ $row['hours_summary'] }}</span>
                            @if($row['hours_summary'] === 'Berbeda per hari')
                            <button type="button" @click="openWorkModal({{ $locId }})"
                                    class="mt-1 block text-xs font-semibold text-red-600 hover:text-red-700">
                                Lihat detail
                            </button>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="inline-flex flex-wrap justify-end gap-2">
                                <button type="button" @click="openWorkModal({{ $locId }})"
                                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-800 hover:bg-gray-50">
                                    Jam Kerja
                                </button>
                                <a href="{{ $holidaysHref }}"
                                   class="rounded-lg bg-brand px-3 py-1.5 text-xs font-semibold text-brand-ink hover:bg-brand-hover">
                                    Jadwal Libur
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">
                            Belum ada lokasi absensi. Sinkronkan dari menu Lokasi Absensi.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal jam kerja per hari --}}
    <div x-show="workModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-gray-900/50" @click="workModalOpen = false"></div>
        <div class="relative max-h-[90vh] w-full max-w-5xl overflow-y-auto rounded-2xl bg-white shadow-xl" @click.stop>
            <div class="border-b border-gray-100 px-6 py-4">
                <h3 class="text-lg font-bold text-gray-900">Jam Kerja — <span x-text="activeLocation?.name ?? '—'"></span></h3>
                <p class="mt-1 text-sm text-gray-500">Atur jam kerja serta batas absen masuk & pulang per hari.</p>
            </div>
            <template x-if="canEdit && activeLocation">
                <form method="POST" :action="activeLocation.update_url" class="px-6 py-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="bulan" :value="bulan">
                    <input type="hidden" name="return_to" value="index">
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
                                <template x-for="day in activeLocation.weekday_hours" :key="day.day_of_week">
                                    <tr>
                                        <td class="py-2.5 pr-3 font-medium text-gray-900" x-text="day.label"></td>
                                        <td class="py-2.5 pr-3">
                                            <input type="time" :name="'days[' + day.day_of_week + '][check_in_time]'" x-model="day.check_in" required
                                                   class="h-10 w-full min-w-[7rem] rounded-xl border border-gray-200 px-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                                        </td>
                                        <td class="py-2.5 pr-3">
                                            <div class="flex items-center gap-1.5">
                                                <input type="time" :name="'days[' + day.day_of_week + '][check_in_window_start]'" x-model="day.check_in_window_start" required
                                                       class="h-10 w-full min-w-[6.5rem] rounded-xl border border-gray-200 px-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                                                <span class="text-gray-400">–</span>
                                                <input type="time" :name="'days[' + day.day_of_week + '][check_in_window_end]'" x-model="day.check_in_window_end" required
                                                       class="h-10 w-full min-w-[6.5rem] rounded-xl border border-gray-200 px-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                                            </div>
                                        </td>
                                        <td class="py-2.5 pr-3">
                                            <input type="time" :name="'days[' + day.day_of_week + '][check_out_time]'" x-model="day.check_out" required
                                                   class="h-10 w-full min-w-[7rem] rounded-xl border border-gray-200 px-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                                        </td>
                                        <td class="py-2.5">
                                            <div class="flex items-center gap-1.5">
                                                <input type="time" :name="'days[' + day.day_of_week + '][check_out_window_start]'" x-model="day.check_out_window_start" required
                                                       class="h-10 w-full min-w-[6.5rem] rounded-xl border border-gray-200 px-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                                                <span class="text-gray-400">–</span>
                                                <input type="time" :name="'days[' + day.day_of_week + '][check_out_window_end]'" x-model="day.check_out_window_end" required
                                                       class="h-10 w-full min-w-[6.5rem] rounded-xl border border-gray-200 px-2 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 pt-4">
                        <a :href="activeLocation.work_hours_url" class="text-xs font-semibold text-gray-500 hover:text-gray-800">Buka halaman penuh</a>
                        <div class="flex gap-2">
                            <button type="button" @click="workModalOpen = false" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Batal</button>
                            <button type="submit" class="rounded-xl bg-brand px-4 py-2 text-sm font-semibold text-brand-ink hover:bg-brand-hover">Simpan</button>
                        </div>
                    </div>
                </form>
            </template>
            <template x-if="!canEdit && activeLocation">
                <div class="px-6 py-5">
                    <div class="overflow-x-auto">
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
                                <template x-for="day in activeLocation.weekday_hours" :key="day.day_of_week">
                                    <tr>
                                        <td class="py-2.5 pr-3 font-medium text-gray-900" x-text="day.label"></td>
                                        <td class="py-2.5 pr-3 tabular-nums text-gray-800" x-text="day.check_in"></td>
                                        <td class="py-2.5 pr-3 tabular-nums text-gray-800" x-text="day.check_in_window_start + '–' + day.check_in_window_end"></td>
                                        <td class="py-2.5 pr-3 tabular-nums text-gray-800" x-text="day.check_out"></td>
                                        <td class="py-2.5 tabular-nums text-gray-800" x-text="day.check_out_window_start + '–' + day.check_out_window_end"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" @click="workModalOpen = false" class="mt-4 rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Tutup</button>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function attendanceScheduleIndex(cfg) {
    const locations = (cfg.locations || []).map((loc) => ({
        ...loc,
        weekday_hours: (loc.weekday_hours || []).map((d) => ({ ...d })),
    }));

    return {
        locations,
        canEdit: !!cfg.canEdit,
        bulan: cfg.bulan || '',
        workModalOpen: false,
        activeLocation: null,
        openWorkModal(id) {
            const loc = this.locations.find((l) => Number(l.id) === Number(id));
            if (!loc) return;
            this.activeLocation = {
                ...loc,
                weekday_hours: (loc.weekday_hours || []).map((d) => ({ ...d })),
            };
            this.workModalOpen = true;
        },
    };
}
</script>
@endpush

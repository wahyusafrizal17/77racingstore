@extends('layouts.admin')

@section('title', 'Jadwal Libur')

@section('content')
@php
    $bulanParam = $calendarMonth->format('Y-m');
    $prevMonth = $calendarMonth->copy()->subMonth()->format('Y-m');
    $nextMonth = $calendarMonth->copy()->addMonth()->format('Y-m');
    $locTitle = $selectedLocation?->displayName() ?? 'Lokasi';
    $baseQuery = array_filter(['location_id' => $locationId, 'bulan' => $bulanParam]);
    $gridQuery = array_filter(array_merge($baseQuery, ['user_id' => $focusUserId ?? null]));
    $indexUrl = route('admin.attendance-schedule.index', ['bulan' => $bulanParam]);
    $workHoursUrl = route('admin.attendance-schedule.work-hours', $baseQuery);
    $holidaysUrl = fn (array $extra = []) => route('admin.attendance-schedule.holidays', array_filter(array_merge($gridQuery, $extra)));
    $dayCount = count($monthDays);
    $sundayIsWeeklyOff = (bool) ($selectedLocation?->isHeadOffice());
@endphp

@push('styles')
<style>
.jadwal-grid-wrap { overflow: auto; max-height: calc(100vh - 200px); }
.jadwal-grid {
  border-collapse: collapse;
  font-size: 11px;
  width: max-content;
  min-width: 100%;
}
.jadwal-grid th, .jadwal-grid td {
  border: 1px solid #d1d5db;
  padding: 0;
  text-align: center;
  vertical-align: middle;
}
.jadwal-grid .head-blue {
  background: #4472c4;
  color: #fff;
  font-weight: 700;
  padding: 6px 8px;
  font-size: 11px;
}
.jadwal-grid .head-day { background: #fff; font-weight: 700; padding: 3px 2px; min-width: 26px; font-size: 11px; }
.jadwal-grid .head-dow { background: #fff; font-size: 9px; font-weight: 600; padding: 2px 1px; text-transform: capitalize; }
.jadwal-grid .col-sunday { background: #ff0000 !important; color: #fff !important; }
.jadwal-grid .group-row td {
  background: #bfbfbf;
  color: #111;
  font-weight: 800;
  text-align: center;
  padding: 5px 8px;
  letter-spacing: .08em;
  font-size: 10px;
}
.jadwal-grid .name-col {
  text-align: left;
  padding: 4px 8px;
  min-width: 200px;
  max-width: 240px;
  font-weight: 700;
  background: #fff;
  white-space: nowrap;
  font-size: 10px;
  text-transform: uppercase;
}
.jadwal-grid .no-col { width: 32px; min-width: 32px; background: #fff; font-weight: 700; font-size: 10px; }
.jadwal-grid .cell-libur {
  background: #ffc000;
  color: #111;
  font-weight: 800;
  font-size: 8px;
  cursor: pointer;
  min-width: 26px;
  height: 28px;
  letter-spacing: .02em;
}
.jadwal-grid .cell-pending {
  box-shadow: inset 0 0 0 2px #e65100;
}
.jadwal-grid .cell-empty {
  background: #fff;
  min-width: 26px;
  height: 28px;
  cursor: pointer;
}
.jadwal-grid .cell-empty:hover { background: #fff8e1; }
.jadwal-grid .cell-sunday {
  background: #ff0000;
  min-width: 26px;
  height: 28px;
  cursor: default;
}
.jadwal-grid .cell-off {
  background: #f3f4f6;
  min-width: 26px;
  height: 28px;
  cursor: not-allowed;
}
.jadwal-grid .cell-dinas {
  background: #7dd3fc;
  color: #0c4a6e;
  font-weight: 800;
  font-size: 7px;
  letter-spacing: .02em;
  min-width: 26px;
  height: 28px;
  cursor: default;
}
.jadwal-grid .sticky-no { position: sticky; left: 0; z-index: 2; }
.jadwal-grid .sticky-name { position: sticky; left: 32px; z-index: 2; }
.jadwal-grid thead .sticky-no, .jadwal-grid thead .sticky-name { z-index: 3; }
</style>
@endpush

@if($errors->any())
<div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
    {{ $errors->first() }}
</div>
@endif

@if(session('success'))
<div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif

<div
    x-data="attendanceHolidayGrid(@js([
        'holidays' => $existingHolidaysPayload,
        'bulan' => $bulanParam,
        'locationId' => $locationId,
        'focusUserId' => $focusUserId,
        'canEdit' => (bool) $canEdit,
        'employeeWorkDays' => $employeeWorkDays,
        'storeUrl' => route('admin.attendance-schedule.holidays.store'),
        'csrf' => csrf_token(),
        'destroyBase' => url('/admin/attendance-schedule/holidays'),
        'sundayIsWeeklyOff' => $sundayIsWeeklyOff,
        'dinasDays' => $existingDinasPayload,
    ]))"
>

    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-lg font-bold text-gray-900">{{ $locTitle }} · {{ $calendarMonth->translatedFormat('F Y') }}</h1>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ $holidaysUrl(['bulan' => $prevMonth]) }}"
               class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">‹</a>
            <span class="text-sm font-semibold text-gray-800">{{ $calendarMonth->translatedFormat('F Y') }}</span>
            <a href="{{ $holidaysUrl(['bulan' => $nextMonth]) }}"
               class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">›</a>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ $indexUrl }}" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">← Kembali</a>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="jadwal-grid-wrap bg-white p-3 sm:p-4">
            @if($displayGroups->isEmpty())
            <p class="px-4 py-12 text-center text-sm text-gray-500">
                Belum ada karyawan di lokasi ini. Set PSA / cabang di menu Karyawan, lalu buka lagi halaman ini.
            </p>
            @else
            <table class="jadwal-grid">
                <thead>
                    <tr>
                        <th class="head-blue sticky-no">NO</th>
                        <th class="head-blue sticky-name text-left">NAMA KARYAWAN</th>
                        <th colspan="{{ $dayCount }}" class="head-blue">{{ $calendarMonth->translatedFormat('F Y') }}</th>
                    </tr>
                    <tr>
                        <th class="head-day sticky-no bg-white"></th>
                        <th class="head-day sticky-name bg-white text-left"></th>
                        @foreach($monthDays as $d)
                        <th class="head-day @if($sundayIsWeeklyOff && $d['is_sunday']) col-sunday @endif">{{ $d['day'] }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="head-dow sticky-no bg-white"></th>
                        <th class="head-dow sticky-name bg-white"></th>
                        @foreach($monthDays as $d)
                        <th class="head-dow @if($sundayIsWeeklyOff && $d['is_sunday']) col-sunday @endif">{{ $d['dow'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($displayGroups as $group)
                    <tr class="group-row">
                        <td colspan="{{ 2 + $dayCount }}">{{ $group['label'] }}</td>
                    </tr>
                    @foreach($group['employees'] as $idx => $emp)
                    @php
                        $empId = (int) $emp->id;
                        $workDays = $employeeWorkDays[$empId] ?? [1, 2, 3, 4, 5, 6, 7];
                    @endphp
                    <tr>
                        <td class="no-col sticky-no bg-white">{{ $idx + 1 }}</td>
                        <td class="name-col sticky-name bg-white">{{ $emp->name }}</td>
                        @foreach($monthDays as $d)
                        @php
                            $dateKey = $d['key'];
                            $isSunday = $d['is_sunday'];
                            $isWork = in_array($d['iso'], $workDays, true);
                            $sundayWeeklyOff = $sundayIsWeeklyOff && $isSunday;
                            $saved = $savedHolidayIndex[$empId][$dateKey] ?? null;
                            $dinas = $savedDinasIndex[$empId][$dateKey] ?? null;
                        @endphp
                        <td
                            @if($canEdit && $isWork && ! $dinas)
                            x-bind:class="cellClassDynamic({{ $empId }}, @js($dateKey), @js($isSunday), @js($isWork))"
                            @click="onCellClick({{ $empId }}, @js($dateKey), @js($isWork))"
                            @elseif($canEdit && $isWork && $dinas)
                            class="cell-dinas"
                            title="{{ $dinas }}"
                            @else
                            class="@if($sundayWeeklyOff) cell-sunday @elseif(!$isWork) cell-off @elseif($dinas) cell-dinas @elseif($saved) cell-libur @else cell-empty @endif"
                            @if($dinas) title="{{ $dinas }}" @endif
                            @endif
                        >
                            @if($canEdit && $isWork && ! $dinas)
                            <span x-show="showDinas({{ $empId }}, @js($dateKey))" x-cloak>DINAS</span>
                            <span x-show="showLibur({{ $empId }}, @js($dateKey)) && !showDinas({{ $empId }}, @js($dateKey))" x-cloak>LIBUR</span>
                            @elseif($dinas)
                            DINAS
                            @elseif($saved)
                            LIBUR
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    <p class="mt-3 text-xs text-gray-500">
        Klik sel kosong untuk tandai <strong>LIBUR</strong> (langsung tersimpan) · klik <strong>LIBUR</strong> kuning untuk hapus
        @if($sundayIsWeeklyOff)
        · kolom merah = Minggu (libur mingguan Head Office)
        @endif
        · sel biru <strong>DINAS</strong> = perjalanan dinas (otomatis dari modul Dinas, tidak perlu absen · tidak bisa ditandai LIBUR)
    </p>

</div>
@endsection

@push('scripts')
<script>
function attendanceHolidayGrid(cfg) {
    const holidays = cfg.holidays || [];
    const holidayKey = (uid, date) => uid + ':' + date;
    const savedMap = new Map();
    holidays.forEach((h) => savedMap.set(holidayKey(h.user_id, h.date), h));
    const dinasMap = new Map();
    (cfg.dinasDays || []).forEach((row) => {
        dinasMap.set(holidayKey(row.user_id, row.date), row);
    });

    return {
        canEdit: !!cfg.canEdit,
        storeUrl: cfg.storeUrl,
        csrf: cfg.csrf,
        destroyBase: cfg.destroyBase,
        bulan: cfg.bulan,
        locationId: cfg.locationId,
        focusUserId: cfg.focusUserId ? Number(cfg.focusUserId) : null,
        sundayIsWeeklyOff: !!cfg.sundayIsWeeklyOff,
        savedMap,
        dinasMap,
        busyKeys: new Set(),
        isDinas(userId, date) {
            return this.dinasMap.has(holidayKey(userId, date));
        },
        showDinas(userId, date) {
            return this.isDinas(userId, date);
        },
        isSaved(userId, date) {
            return this.savedMap.has(holidayKey(userId, date));
        },
        isBusy(userId, date) {
            return this.busyKeys.has(holidayKey(userId, date));
        },
        setBusy(userId, date, busy) {
            const key = holidayKey(userId, date);
            const next = new Set(this.busyKeys);
            if (busy) {
                next.add(key);
            } else {
                next.delete(key);
            }
            this.busyKeys = next;
        },
        showLibur(userId, date) {
            return this.isSaved(userId, date);
        },
        cellClassDynamic(userId, date, isSunday, isWork) {
            const key = holidayKey(userId, date);
            if (isSunday && this.sundayIsWeeklyOff) return 'cell-sunday';
            if (!isWork) return 'cell-off';
            if (this.dinasMap.has(key)) return 'cell-dinas';
            if (this.busyKeys.has(key)) return 'cell-libur cell-pending';
            if (this.savedMap.has(key)) return 'cell-libur';
            return 'cell-empty';
        },
        async onCellClick(userId, date, isWork) {
            if (!this.canEdit || !isWork || this.isBusy(userId, date) || this.isDinas(userId, date)) return;
            const key = holidayKey(userId, date);
            if (this.savedMap.has(key)) {
                const h = this.savedMap.get(key);
                const result = await Swal.fire({
                    title: 'Hapus LIBUR?',
                    text: (h.user_name || '') + ' · ' + h.date,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Hapus',
                    cancelButtonText: 'Batal',
                });
                if (result.isConfirmed) {
                    await this.deleteHoliday(h);
                }
                return;
            }
            await this.createHoliday(userId, date);
        },
        async createHoliday(userId, date) {
            const key = holidayKey(userId, date);
            this.setBusy(userId, date, true);
            try {
                const body = new FormData();
                body.append('_token', this.csrf);
                body.append('bulan', this.bulan);
                body.append('location_id', String(this.locationId));
                body.append('holiday_pairs', JSON.stringify([{ user_id: userId, date }]));
                body.append('name', 'Libur');
                const res = await fetch(this.storeUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(data.message || 'Gagal menyimpan');
                }
                const h = (data.holidays || []).find((row) => row.user_id === userId && row.date === date)
                    || (data.holidays || [])[0];
                if (h) {
                    const next = new Map(this.savedMap);
                    next.set(key, h);
                    this.savedMap = next;
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal menyimpan',
                    text: err.message || 'Coba lagi.',
                    confirmButtonColor: '#8CFA01',
                });
            } finally {
                this.setBusy(userId, date, false);
            }
        },
        async deleteHoliday(h) {
            const key = holidayKey(h.user_id, h.date);
            this.setBusy(h.user_id, h.date, true);
            try {
                const body = new FormData();
                body.append('_token', this.csrf);
                body.append('_method', 'DELETE');
                body.append('bulan', this.bulan);
                body.append('location_id', String(this.locationId));
                if (this.focusUserId) {
                    body.append('user_id', String(this.focusUserId));
                }
                const res = await fetch(this.destroyBase + '/' + h.id, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                });
                if (!res.ok) {
                    throw new Error('Gagal menghapus');
                }
                const next = new Map(this.savedMap);
                next.delete(key);
                this.savedMap = next;
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal menghapus',
                    text: err.message || 'Coba lagi.',
                    confirmButtonColor: '#8CFA01',
                });
            } finally {
                this.setBusy(h.user_id, h.date, false);
            }
        },
    };
}
</script>
@endpush

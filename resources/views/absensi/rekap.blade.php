@extends($layout)

@section('title', 'Rekap Absensi')

@section('content')
@php
    $bulanParam = $calendarMonth->format('Y-m');
    $prevMonth = $calendarMonth->copy()->subMonth()->format('Y-m');
    $nextMonth = $calendarMonth->copy()->addMonth()->format('Y-m');
    $canGoNext = $calendarMonth->copy()->addMonth()->lte(now()->startOfMonth());
    $dayCount = count($monthDays);
    $employeeCount = $displayGroups->sum(fn ($group) => count($group['employees'] ?? []));
    $totalCodes = \App\Services\AttendanceScheduleService::RECAP_TOTAL_CODES;
    $totalLabels = [
        'H' => 'H',
        'T' => 'T',
        'A' => 'A',
        'IZIN' => 'Izin',
        'SAKIT' => 'Sakit',
        'CUTI' => 'Cuti',
        'WFH' => 'WFH',
        'DINAS' => 'Dinas',
        'P' => 'P',
    ];
    $totalColCount = count($totalCodes);
    $gridColspan = 2 + $dayCount + $totalColCount;
    $scopeLabel = $scopeLabel ?? (($seesAllEmployees ?? $isOwner ?? false) ? 'Semua karyawan' : 'Bawahan di bawah tanggung jawab Anda');
    $cabangOptions = $cabangOptions ?? collect();
    $cabangFilter = $cabangFilter ?? null;
    $exportQuery = array_filter([
        'bulan' => $bulanParam,
        'name' => $nameFilter !== '' ? $nameFilter : null,
        'cabang' => $cabangFilter,
    ], fn ($v) => $v !== null && $v !== '');
    $rekapUrl = fn (array $extra = []) => route('absensi.rekap.index', array_filter(array_merge($exportQuery, $extra)));
@endphp

@push('styles')
<style>
.rekap-page {
  --rekap-ink: #0f172a;
  --rekap-muted: #64748b;
  --rekap-line: #e2e8f0;
  --rekap-soft: #f8fafc;
  --rekap-surface: #ffffff;
  --rekap-accent: #dc2626;
  --rekap-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 8px 24px rgba(15, 23, 42, .04);
}

.rekap-hero {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}
.rekap-kicker {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: #94a3b8;
  margin-bottom: 6px;
}
.rekap-title {
  font-size: clamp(1.35rem, 1.1rem + .8vw, 1.75rem);
  font-weight: 750;
  letter-spacing: -.03em;
  color: var(--rekap-ink);
  line-height: 1.15;
}
.rekap-sub {
  margin-top: 6px;
  font-size: 13px;
  color: var(--rekap-muted);
}
.rekap-sub strong { color: #334155; font-weight: 650; }

.rekap-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}
.rekap-month-nav {
  display: inline-flex;
  align-items: center;
  gap: 2px;
  padding: 3px;
  border: 1px solid var(--rekap-line);
  border-radius: 14px;
  background: var(--rekap-surface);
  box-shadow: var(--rekap-shadow);
}
.rekap-month-nav a,
.rekap-month-nav span.nav-disabled {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border-radius: 11px;
  color: #475569;
  transition: background .15s, color .15s;
}
.rekap-month-nav a:hover { background: #f1f5f9; color: var(--rekap-ink); }
.rekap-month-nav span.nav-disabled { color: #cbd5e1; }
.rekap-month-label {
  min-width: 132px;
  text-align: center;
  font-size: 13px;
  font-weight: 700;
  color: var(--rekap-ink);
  letter-spacing: -.01em;
}

.rekap-actions {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 8px;
  padding: 4px;
  border: 1px solid var(--rekap-line);
  border-radius: 14px;
  background: #fff;
  box-shadow: var(--rekap-shadow);
}
.rekap-export {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 36px;
  padding: 0 12px 0 8px;
  border: 0;
  border-radius: 10px;
  font-size: 12.5px;
  font-weight: 650;
  letter-spacing: -.01em;
  text-decoration: none;
  transition: background .15s, color .15s, transform .15s;
}
.rekap-export:active { transform: translateY(1px); }
.rekap-export__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 7px;
  flex-shrink: 0;
}
.rekap-export__icon svg {
  width: 14px;
  height: 14px;
}
.rekap-export--excel {
  color: #166534;
  background: transparent;
}
.rekap-export--excel:hover {
  background: #f0fdf4;
  color: #14532d;
}
.rekap-export--excel .rekap-export__icon {
  background: #dcfce7;
  color: #15803d;
}
.rekap-export--pdf {
  color: #9f1239;
  background: transparent;
}
.rekap-export--pdf:hover {
  background: #fff1f2;
  color: #881337;
}
.rekap-export--pdf .rekap-export__icon {
  background: #ffe4e6;
  color: #e11d48;
}

.rekap-filter {
  display: flex;
  flex-wrap: wrap;
  align-items: end;
  gap: 10px;
  margin-bottom: 14px;
  padding: 14px 16px;
  border: 1px solid var(--rekap-line);
  border-radius: 18px;
  background: linear-gradient(180deg, #fff 0%, #fbfdff 100%);
  box-shadow: var(--rekap-shadow);
}
.rekap-filter label {
  display: block;
  margin-bottom: 6px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .04em;
  text-transform: uppercase;
  color: #94a3b8;
}
.rekap-filter input[type="text"] {
  height: 40px;
  width: 100%;
  min-width: 220px;
  border: 1px solid var(--rekap-line);
  border-radius: 12px;
  padding: 0 12px;
  font-size: 13px;
  color: var(--rekap-ink);
  background: #fff;
  outline: none;
  transition: border-color .15s, box-shadow .15s;
}
.rekap-filter input[type="text"]:focus,
.rekap-filter select:focus {
  border-color: #fca5a5;
  box-shadow: 0 0 0 3px rgba(220, 38, 38, .08);
}
.rekap-filter select {
  height: 40px;
  width: 100%;
  min-width: 200px;
  border: 1px solid var(--rekap-line);
  border-radius: 12px;
  padding: 0 12px;
  font-size: 13px;
  color: var(--rekap-ink);
  background: #fff;
  outline: none;
}
.rekap-filter .btn-primary,
.rekap-filter .btn-ghost {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 40px;
  padding: 0 16px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 650;
  transition: background .15s, border-color .15s, color .15s;
}
.rekap-filter .btn-primary {
  background: var(--rekap-ink);
  color: #fff;
  border: 1px solid var(--rekap-ink);
}
.rekap-filter .btn-primary:hover { background: #1e293b; }
.rekap-filter .btn-ghost {
  background: #fff;
  color: #475569;
  border: 1px solid var(--rekap-line);
}
.rekap-filter .btn-ghost:hover { background: #f8fafc; }

.rekap-panel {
  overflow: hidden;
  border: 1px solid var(--rekap-line);
  border-radius: 20px;
  background: var(--rekap-surface);
  box-shadow: var(--rekap-shadow);
}
.rekap-panel-head {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 14px 18px;
  border-bottom: 1px solid var(--rekap-line);
  background: linear-gradient(180deg, #fcfcfd 0%, #fff 100%);
}
.rekap-panel-head h2 {
  font-size: 14px;
  font-weight: 700;
  color: var(--rekap-ink);
  letter-spacing: -.01em;
}
.rekap-panel-head p {
  margin-top: 2px;
  font-size: 12px;
  color: var(--rekap-muted);
}
.rekap-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  border-radius: 999px;
  background: #f1f5f9;
  color: #475569;
  font-size: 11px;
  font-weight: 650;
}
.rekap-chip b { color: var(--rekap-ink); font-weight: 750; }

.jadwal-grid-wrap {
  overflow: auto;
  max-height: calc(100vh - 250px);
  background:
    linear-gradient(90deg, #fff 30%, rgba(255,255,255,0)),
    linear-gradient(90deg, rgba(255,255,255,0), #fff 70%) 100% 0,
    linear-gradient(90deg, rgba(15,23,42,.06), transparent),
    linear-gradient(90deg, transparent, rgba(15,23,42,.06)) 100% 0;
  background-repeat: no-repeat;
  background-size: 40px 100%, 40px 100%, 14px 100%, 14px 100%;
  background-attachment: local, local, scroll, scroll;
}
.jadwal-grid {
  border-collapse: separate;
  border-spacing: 0;
  font-size: 11px;
  width: max-content;
  min-width: 100%;
}
.jadwal-grid th,
.jadwal-grid td {
  border-right: 1px solid #eef2f7;
  border-bottom: 1px solid #eef2f7;
  padding: 0;
  text-align: center;
  vertical-align: middle;
}
.jadwal-grid thead th {
  position: sticky;
  top: 0;
  z-index: 4;
}
.jadwal-grid .head-main {
  background: #0f172a;
  color: #fff;
  font-weight: 700;
  padding: 10px 12px;
  font-size: 11px;
  letter-spacing: .04em;
  text-transform: uppercase;
  border-right-color: rgba(255,255,255,.08);
  border-bottom-color: transparent;
}
.jadwal-grid .head-day {
  background: #f8fafc;
  color: #0f172a;
  font-weight: 750;
  padding: 7px 2px 2px;
  min-width: 46px;
  font-size: 12px;
  top: 38px;
}
.jadwal-grid .head-dow {
  background: #f8fafc;
  color: #94a3b8;
  font-size: 9px;
  font-weight: 650;
  padding: 0 1px 8px;
  text-transform: uppercase;
  letter-spacing: .04em;
  top: 62px;
  border-bottom: 1px solid #e2e8f0;
}
.jadwal-grid .col-sunday.head-day,
.jadwal-grid .col-sunday.head-dow {
  background: #fff1f2;
  color: #be123c;
}
.jadwal-grid .group-row td {
  background: #f1f5f9;
  color: #334155;
  font-weight: 750;
  text-align: left;
  padding: 8px 14px;
  letter-spacing: .08em;
  font-size: 10px;
  text-transform: uppercase;
  border-right: none;
}
.jadwal-grid tbody tr:hover td.no-col,
.jadwal-grid tbody tr:hover td.name-col {
  background: #f8fafc;
}
.jadwal-grid .name-col {
  text-align: left;
  padding: 8px 12px;
  min-width: 220px;
  max-width: 260px;
  font-weight: 650;
  background: #fff;
  white-space: nowrap;
  font-size: 12px;
  color: #0f172a;
  letter-spacing: -.01em;
}
.jadwal-grid .no-col {
  width: 44px;
  min-width: 44px;
  background: #fff;
  font-weight: 700;
  font-size: 11px;
  color: #94a3b8;
}
.jadwal-grid .cell-libur,
.jadwal-grid .cell-izin,
.jadwal-grid .cell-sakit,
.jadwal-grid .cell-cuti,
.jadwal-grid .cell-wfh,
.jadwal-grid .cell-pending,
.jadwal-grid .cell-hadir,
.jadwal-grid .cell-late,
.jadwal-grid .cell-alpha,
.jadwal-grid .cell-future,
.jadwal-grid .cell-sunday,
.jadwal-grid .cell-off,
.jadwal-grid .cell-dinas {
  min-width: 46px;
  height: 52px;
  padding: 3px 2px;
  line-height: 1.15;
  vertical-align: middle;
}
.jadwal-grid .cell-libur {
  background: #fff7ed;
  color: #9a3412;
  font-weight: 750;
  font-size: 8px;
  letter-spacing: .02em;
}
.jadwal-grid .cell-izin {
  background: #eef2ff;
  color: #3730a3;
  font-weight: 750;
  font-size: 8px;
  letter-spacing: .02em;
}
.jadwal-grid .cell-sakit {
  background: #fdf2f8;
  color: #9d174d;
  font-weight: 750;
  font-size: 8px;
  letter-spacing: .02em;
}
.jadwal-grid .cell-cuti {
  background: #ecfeff;
  color: #0e7490;
  font-weight: 750;
  font-size: 8px;
  letter-spacing: .02em;
}
.jadwal-grid .cell-wfh {
  background: #f5f3ff;
  color: #6d28d9;
  font-weight: 800;
  font-size: 9px;
  letter-spacing: .02em;
  box-shadow: inset 0 0 0 1.5px #c4b5fd;
}
.jadwal-grid .cell-pending {
  background: #fff7ed;
  color: #c2410c;
  font-weight: 800;
  font-size: 10px;
  box-shadow: inset 0 0 0 1.5px #fb923c;
}
.jadwal-grid .cell-hadir {
  background: #ecfdf5;
  color: #047857;
  font-weight: 800;
  font-size: 10px;
}
.jadwal-grid .cell-late {
  background: #fffbeb;
  color: #b45309;
  font-weight: 800;
  font-size: 10px;
}
.jadwal-grid .cell-alpha {
  background: #fef2f2;
  color: #b91c1c;
  font-weight: 800;
  font-size: 11px;
}
.jadwal-grid .cell-future { background: #fff; }
.jadwal-grid .cell-sunday { background: #fff1f2; }
.jadwal-grid .cell-off { background: #f8fafc; }
.jadwal-grid .cell-dinas {
  background: #f0f9ff;
  color: #0369a1;
  font-weight: 750;
  font-size: 8px;
  letter-spacing: .02em;
}
.jadwal-grid .cell-stack {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1px;
}
.jadwal-grid .cell-code { font-size: 10px; font-weight: 800; letter-spacing: .02em; }
.jadwal-grid .cell-time { font-size: 8px; font-weight: 650; white-space: nowrap; opacity: .9; }
.jadwal-grid .cell-time.is-missing { opacity: .45; }
.jadwal-grid .sticky-no { position: sticky; left: 0; z-index: 2; }
.jadwal-grid .sticky-name { position: sticky; left: 44px; z-index: 2; box-shadow: 8px 0 12px -10px rgba(15,23,42,.25); }
.jadwal-grid thead .sticky-no,
.jadwal-grid thead .sticky-name { z-index: 5; }
.jadwal-grid thead .sticky-no.head-main,
.jadwal-grid thead .sticky-name.head-main { background: #0f172a; }
.jadwal-grid thead .sticky-no.head-day,
.jadwal-grid thead .sticky-name.head-day,
.jadwal-grid thead .sticky-no.head-dow,
.jadwal-grid thead .sticky-name.head-dow { background: #f8fafc; }
.jadwal-grid .head-total {
  background: #1e293b;
  color: #fff;
  min-width: 42px;
  padding: 7px 6px;
  font-size: 10px;
  letter-spacing: .03em;
  text-transform: uppercase;
}
.jadwal-grid .head-total-sub {
  background: #f1f5f9;
  color: #334155;
  min-width: 42px;
  padding: 6px 4px;
  font-size: 10px;
  font-weight: 750;
  top: 38px;
}
.jadwal-grid .head-total-dow {
  background: #f1f5f9;
  top: 62px;
  border-bottom: 1px solid #e2e8f0;
}
.jadwal-grid .total-col {
  background: #f8fafc;
  min-width: 42px;
  font-weight: 750;
  font-size: 12px;
  color: #0f172a;
}
.jadwal-grid .total-col.is-zero { color: #cbd5e1; font-weight: 600; }
.jadwal-grid tbody tr:hover td.total-col { background: #f1f5f9; }

.rekap-empty {
  padding: 56px 24px;
  text-align: center;
}
.rekap-empty h3 {
  font-size: 15px;
  font-weight: 700;
  color: var(--rekap-ink);
}
.rekap-empty p {
  margin-top: 6px;
  font-size: 13px;
  color: var(--rekap-muted);
}

.rekap-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 14px;
}
.rekap-legend span {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 7px 10px;
  border-radius: 999px;
  border: 1px solid var(--rekap-line);
  background: #fff;
  font-size: 11px;
  font-weight: 600;
  color: #475569;
}
.rekap-legend i {
  display: inline-block;
  width: 14px;
  height: 14px;
  border-radius: 4px;
  border: 1px solid rgba(15,23,42,.06);
  flex-shrink: 0;
}
</style>
@endpush

<div class="rekap-page">
    <div class="rekap-hero">
        <div>
            <h1 class="rekap-title">Rekap Absensi</h1>
            <p class="rekap-sub">
                <strong>{{ $calendarMonth->translatedFormat('F Y') }}</strong>
                · {{ $scopeLabel }}
                · {{ number_format($employeeCount) }} karyawan
            </p>
        </div>
        <div class="rekap-toolbar">
            <div class="rekap-month-nav">
                <a href="{{ $rekapUrl(['bulan' => $prevMonth]) }}" aria-label="Bulan sebelumnya">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <span class="rekap-month-label">{{ $calendarMonth->translatedFormat('F Y') }}</span>
                @if($canGoNext)
                <a href="{{ $rekapUrl(['bulan' => $nextMonth]) }}" aria-label="Bulan berikutnya">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
                @else
                <span class="nav-disabled" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </span>
                @endif
            </div>
            <div class="rekap-actions" role="group" aria-label="Export rekap">
                <a href="{{ route('absensi.rekap.export-excel', $exportQuery) }}" class="rekap-export rekap-export--excel">
                    <span class="rekap-export__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6.5A2.5 2.5 0 016.5 4H14l6 6v7.5A2.5 2.5 0 0117.5 20h-11A2.5 2.5 0 014 17.5v-11z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 4v5a1 1 0 001 1h5M8 13h5M8 16h8"/></svg>
                    </span>
                    Excel
                </a>
                <a href="{{ route('absensi.rekap.export-pdf', $exportQuery) }}" target="_blank" rel="noopener noreferrer" class="rekap-export rekap-export--pdf">
                    <span class="rekap-export__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h7.5L19 8.25V19.5A1.75 1.75 0 0117.25 21.25H7A1.75 1.75 0 015.25 19.5V5.5A1.75 1.75 0 017 3.75z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14.25 3.75V8h4.5M9 13.5h6M9 16.5h4"/></svg>
                    </span>
                    PDF
                </a>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('absensi.rekap.index') }}" class="rekap-filter">
        <input type="hidden" name="bulan" value="{{ $bulanParam }}">
        @if($cabangOptions->count() > 1)
        <div class="min-w-[200px]">
            <label for="rekap-cabang">Cabang</label>
            <select id="rekap-cabang" name="cabang" onchange="this.form.submit()">
                <option value="all" @selected($cabangFilter === 'all' || $cabangFilter === null)>Semua cabang</option>
                @foreach($cabangOptions as $cabang)
                <option value="{{ $cabang->id }}" @selected((string) $cabangFilter === (string) $cabang->id)>{{ $cabang->nama }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="min-w-[220px] flex-1">
            <label for="rekap-name">Cari nama</label>
            <input id="rekap-name" type="text" name="name" value="{{ $nameFilter }}" placeholder="Ketik nama karyawan…">
        </div>
        <button type="submit" class="btn-primary">Terapkan</button>
        <a href="{{ route('absensi.rekap.index', ['bulan' => $bulanParam]) }}" class="btn-ghost">Reset</a>
    </form>

    <div class="rekap-panel">
        <div class="rekap-panel-head">
            <div>
                <h2>Kalender kehadiran</h2>
                <p>Scroll horizontal untuk melihat tanggal · nama tetap menempel di kiri</p>
            </div>
            <span class="rekap-chip"><b>{{ number_format($employeeCount) }}</b> karyawan · {{ $dayCount }} hari</span>
        </div>

        <div class="jadwal-grid-wrap">
            @if($displayGroups->isEmpty())
            <div class="rekap-empty">
                <h3>Belum ada data karyawan</h3>
                <p>Tidak ada karyawan pada cakupan rekap Anda{{ $nameFilter !== '' ? ' untuk pencarian ini' : '' }}.</p>
            </div>
            @else
            <table class="jadwal-grid">
                <thead>
                    <tr>
                        <th class="head-main sticky-no">No</th>
                        <th class="head-main sticky-name text-left">Nama karyawan</th>
                        <th colspan="{{ $dayCount }}" class="head-main">{{ $calendarMonth->translatedFormat('F Y') }}</th>
                        <th colspan="{{ $totalColCount }}" class="head-main head-total">Total</th>
                    </tr>
                    <tr>
                        <th class="head-day sticky-no"></th>
                        <th class="head-day sticky-name text-left"></th>
                        @foreach($monthDays as $d)
                        <th class="head-day @if($d['is_sunday']) col-sunday @endif">{{ $d['day'] }}</th>
                        @endforeach
                        @foreach($totalCodes as $code)
                        <th class="head-day head-total-sub">{{ $totalLabels[$code] }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        <th class="head-dow sticky-no"></th>
                        <th class="head-dow sticky-name"></th>
                        @foreach($monthDays as $d)
                        <th class="head-dow @if($d['is_sunday']) col-sunday @endif">{{ $d['dow'] }}</th>
                        @endforeach
                        @foreach($totalCodes as $code)
                        <th class="head-dow head-total-dow"></th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($displayGroups as $group)
                    <tr class="group-row">
                        <td colspan="{{ $gridColspan }}">{{ $group['label'] }}</td>
                    </tr>
                    @foreach($group['employees'] as $idx => $emp)
                    @php
                        $empId = (int) $emp->id;
                        $rowTotals = \App\Services\AttendanceScheduleService::recapStatusTotals($cells[$empId] ?? []);
                    @endphp
                    <tr>
                        <td class="no-col sticky-no">{{ $idx + 1 }}</td>
                        <td class="name-col sticky-name" title="{{ $emp->email }}">{{ $emp->name }}</td>
                        @foreach($monthDays as $d)
                        @php
                            $cell = $cells[$empId][$d['key']] ?? ['code' => '', 'class' => 'cell-future', 'title' => '', 'in_time' => null, 'out_time' => null];
                            $hasTimes = ! empty($cell['in_time']);
                        @endphp
                        <td class="{{ $cell['class'] }}" title="{{ $cell['title'] }}">
                            @if($hasTimes)
                            <div class="cell-stack">
                                <span class="cell-code">{{ $cell['code'] }}</span>
                                <span class="cell-time">{{ $cell['in_time'] }}</span>
                                <span class="cell-time {{ empty($cell['out_time']) ? 'is-missing' : '' }}">{{ $cell['out_time'] ?? '—' }}</span>
                            </div>
                            @else
                            {{ $cell['code'] }}
                            @endif
                        </td>
                        @endforeach
                        @foreach($totalCodes as $code)
                        <td class="total-col {{ ($rowTotals[$code] ?? 0) === 0 ? 'is-zero' : '' }}">{{ $rowTotals[$code] ?? 0 }}</td>
                        @endforeach
                    </tr>
                    @endforeach
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    <div class="rekap-legend">
        <span><i style="background:#ecfdf5; box-shadow: inset 0 0 0 1px #a7f3d0"></i> H Hadir</span>
        <span><i style="background:#fffbeb; box-shadow: inset 0 0 0 1px #fcd34d"></i> T Telat</span>
        <span><i style="background:#fef2f2; box-shadow: inset 0 0 0 1px #fca5a5"></i> A Tidak hadir</span>
        <span><i style="background:#fff7ed; box-shadow: inset 0 0 0 1.5px #fb923c"></i> P Pending</span>
        <span><i style="background:#f5f3ff; box-shadow: inset 0 0 0 1.5px #c4b5fd"></i> WFH</span>
        <span><i style="background:#eef2ff; box-shadow: inset 0 0 0 1px #a5b4fc"></i> Izin</span>
        <span><i style="background:#fdf2f8; box-shadow: inset 0 0 0 1px #f9a8d4"></i> Sakit</span>
        <span><i style="background:#ecfeff; box-shadow: inset 0 0 0 1px #67e8f9"></i> Cuti</span>
        <span><i style="background:#fff7ed; box-shadow: inset 0 0 0 1px #fdba74"></i> Libur</span>
        <span><i style="background:#f0f9ff; box-shadow: inset 0 0 0 1px #7dd3fc"></i> Dinas</span>
        <span><i style="background:#fff1f2; box-shadow: inset 0 0 0 1px #fda4af"></i> Minggu</span>
        <span><i style="background:#f8fafc; box-shadow: inset 0 0 0 1px #e2e8f0"></i> Non-kerja</span>
    </div>
</div>
@endsection

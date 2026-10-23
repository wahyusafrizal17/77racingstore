@extends($layout)

@section('title', 'Absensi')

@section('content')
@php
    $tz = \App\Models\AttendanceSetting::TIMEZONE;
    $fmtTime = fn ($dt) => $dt ? $dt->timezone($tz)->format('H:i') : '— : —';
    $user = auth()->user();
    $defaultLoc = isset($defaultLocationId)
        ? $locations->firstWhere('id', (int) $defaultLocationId)
        : null;
    $branchName = $defaultLoc?->displayName()
        ?? $locations->first()?->displayName()
        ?? $user->speedshop?->nama
        ?? $user->warehouse?->nama
        ?? 'Lokasi kerja';
    $autoPickLocation = (bool) ($autoPickLocation ?? false);
    $branchCode = trim((string) ($user->role ?? 'Karyawan'));

    $calendarMonth = ($calendarMonth ?? now($tz))->copy()->timezone($tz)->startOfMonth();
    $calendarDays = $calendarDays ?? [];
    $attendanceSettings = $attendanceSettings ?? null;
    $requiresAttendanceToday = $requiresAttendanceToday ?? true;
    $todayOffLabel = $todayOffLabel ?? null;
    $prevMonth = $calendarMonth->copy()->subMonth()->format('Y-m');
    $nextMonth = $calendarMonth->copy()->addMonth()->format('Y-m');
    $canGoNext = $calendarMonth->copy()->addMonth()->lte(now($tz)->startOfMonth()->addMonths(12));
    $selectedDate = ($selectedDate ?? now($tz)->startOfDay())->copy()->timezone($tz);
    $isSelectedToday = $isSelectedToday ?? $selectedDate->isSameDay(now($tz));
    $selectedDayCalendar = $selectedDayCalendar ?? null;
    $selectedCheckIn = $selectedCheckIn ?? null;
    $selectedCheckOut = $selectedCheckOut ?? null;
    $showDayDetail = $showDayDetail ?? false;
    $todayKey = now($tz)->toDateString();
    $scheduledWorkMinutes = $attendanceSettings?->workMinutes() ?? (9 * 60);
    $scheduledRange = $attendanceSettings
        ? \Illuminate\Support\Str::of((string) $attendanceSettings->check_in_time)->substr(0, 5)
            .'–'.\Illuminate\Support\Str::of((string) $attendanceSettings->check_out_time)->substr(0, 5)
        : '08:00–17:00';
    if (! empty($breakWindow['start'] ?? null) && ! empty($breakWindow['end'] ?? null)) {
        $scheduledRange .= ' · istirahat '
            .\Illuminate\Support\Str::of($breakWindow['start'])->substr(0, 5)
            .'–'
            .\Illuminate\Support\Str::of($breakWindow['end'])->substr(0, 5);
    }
@endphp

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.absensi-page {
  --bg: #F6F5F2;
  --surface: #FFFFFF;
  --ink: #181819;
  --ink-soft: #6E7078;
  --ink-faint: #A6A8AE;
  --line: #E9E7E2;
  --line-soft: #F1EFEA;
  --red: #8CFA01;
  --red-ink: #3d6b00;
  --red-deep: #6ed000;
  --red-tint: #f3fde0;
  --green: #1D8A5C;
  --green-tint: #E8F5EF;
  --green-bright: #38C98F;
  --amber: #B9791A;
  --amber-tint: #FBF1E2;
  --sidebar: #121214;
  --panel-dark: #17181B;
  --panel-dark-2: #1F2023;
  --shadow: 0 1px 2px rgba(20,20,20,.04), 0 8px 24px rgba(20,20,20,.05);
  --shadow-lg: 0 4px 10px rgba(20,20,20,.05), 0 20px 48px rgba(20,20,20,.08);
  color: var(--ink);
  font-family: 'Inter', sans-serif;
  -webkit-font-smoothing: antialiased;
  width: 100%;
  max-width: none;
  margin: 0;
  box-sizing: border-box;
}
.absensi-page *,
.absensi-page *::before,
.absensi-page *::after { box-sizing: border-box; }
.absensi-page .branch-card {
  background: linear-gradient(155deg, var(--sidebar), #1D1D20);
  border-radius: 16px;
  padding: 18px 20px;
  color: #fff;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  box-shadow: var(--shadow-lg);
  position: relative;
  overflow: hidden;
  margin-bottom: 20px;
}
.absensi-page .branch-card::before {
  content: '';
  position: absolute;
  top: -60px; right: -60px;
  width: 180px; height: 180px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(140,250,1,.35), transparent 70%);
}
.absensi-page .branch-card .bname {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 19px;
  font-weight: 700;
  margin-top: 0;
  letter-spacing: .01em;
  position: relative;
  z-index: 1;
}
.absensi-page .branch-card .bcode {
  font-size: 11px;
  color: #9A9BA2;
  letter-spacing: .08em;
  text-transform: uppercase;
  margin-top: 2px;
  position: relative;
  z-index: 1;
}
.absensi-page .branch-card .clock {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 26px;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  margin-top: 10px;
  position: relative;
  z-index: 1;
}
.absensi-page .absen-card {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: 22px;
  box-shadow: var(--shadow-lg);
  overflow: hidden;
  margin-bottom: 18px;
}
.absensi-page .absen-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24px 28px;
  border-bottom: 1px solid var(--line-soft);
  gap: 12px;
}
.absensi-page .absen-head h2 { font-size: 19px; font-weight: 700; margin: 0 0 3px; }
.absensi-page .absen-head p { font-size: 13.5px; color: var(--ink-soft); margin: 0; }
.absensi-page .badge-state {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--red-tint);
  color: var(--red-deep);
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
}
.absensi-page .badge-state .d { width: 6px; height: 6px; border-radius: 50%; background: var(--red); }
.absensi-page .badge-state.out { background: #EEF3FF; color: #1D4ED8; }
.absensi-page .badge-state.out .d { background: #2E6BE6; }

.absensi-page .absen-body { display: grid; grid-template-columns: 70% 30%; gap: 0; }
.absensi-page .absen-body.is-pulang { grid-template-columns: 30% 70%; }
.absensi-page .absen-body.is-pulang .absen-right { order: -1; }
.absensi-page .absen-left { padding: 26px 28px; border-right: 1px solid var(--line-soft); }
.absensi-page .absen-body.is-pulang .absen-left {
  border-right: none;
  border-left: 1px solid var(--line-soft);
}
.absensi-page .loc-select {
  width: 100%;
  height: 40px;
  border-radius: 11px;
  border: 1px solid var(--line);
  background: var(--surface);
  padding: 0 12px;
  font-size: 13.5px;
  font-weight: 600;
  color: var(--ink);
}

.absensi-page .steps { display: flex; flex-direction: column; }
.absensi-page .step {
  display: grid;
  grid-template-columns: 30px 1fr;
  gap: 12px;
  position: relative;
  padding-bottom: 22px;
}
.absensi-page .step:last-child { padding-bottom: 0; }
.absensi-page .step::before {
  content: '';
  position: absolute;
  left: 14px; top: 30px; bottom: 0;
  width: 1.5px; background: var(--line);
}
.absensi-page .step:last-child::before { display: none; }
.absensi-page .step.done::before { background: var(--green); }
.absensi-page .step-dot {
  width: 30px; height: 30px; border-radius: 50%;
  background: var(--line-soft); border: 1.5px solid var(--line);
  display: flex; align-items: center; justify-content: center;
  color: var(--ink-faint); font-size: 12px; font-weight: 700;
  z-index: 1; transition: all .2s;
}
.absensi-page .step.done .step-dot { background: var(--green); border-color: var(--green); color: #fff; }
.absensi-page .step.active .step-dot {
  background: var(--surface); border-color: var(--red-ink); color: var(--red-ink);
  box-shadow: 0 0 0 4px var(--red-tint);
}
.absensi-page .step-dot svg { width: 14px; height: 14px; }
.absensi-page .step-txt .n { font-size: 14px; font-weight: 700; color: var(--ink); }
.absensi-page .step-txt .d { font-size: 12.5px; color: var(--ink-faint); margin-top: 2px; }
.absensi-page .step.done .step-txt .d { color: var(--green); }

.absensi-page .absen-right {
  padding: 28px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 0;
  width: 100%;
}
.absensi-page .selfie-ring {
  position: relative;
  width: min(200px, 100%);
  aspect-ratio: 1;
  height: auto;
  border-radius: 50%;
  padding: 5px;
  background: conic-gradient(var(--line) 0 100%);
  display: flex; align-items: center; justify-content: center;
  transition: background .3s;
  flex-shrink: 0;
}
.absensi-page .selfie-ring.live {
  background: conic-gradient(var(--red) 0 100%);
  animation: absensiPulse 1.8s ease-in-out infinite;
}
.absensi-page .selfie-ring.captured {
  background: conic-gradient(var(--green) 0 100%);
  animation: none;
}
@keyframes absensiPulse { 0%,100%{opacity:1;} 50%{opacity:.55;} }
.absensi-page .selfie-inner {
  width: 100%; height: 100%;
  border-radius: 50%;
  overflow: hidden;
  background: var(--bg);
  position: relative;
  display: flex; align-items: center; justify-content: center;
}
.absensi-page .selfie-inner video,
.absensi-page .selfie-inner img {
  width: 100%; height: 100%;
  object-fit: cover;
  position: absolute; inset: 0;
}
.absensi-page .selfie-placeholder { text-align: center; color: var(--ink-faint); padding: 20px; }
.absensi-page .selfie-placeholder svg { width: 30px; height: 30px; margin-bottom: 6px; opacity: .5; }
.absensi-page .selfie-placeholder p { font-size: 12px; margin: 0; line-height: 1.5; }
.absensi-page .check-badge {
  position: absolute; bottom: 6px; right: 14px;
  width: 34px; height: 34px; border-radius: 50%;
  background: var(--green); color: #fff;
  display: none; align-items: center; justify-content: center;
  border: 3px solid var(--surface); z-index: 3;
}
.absensi-page .check-badge svg { width: 16px; height: 16px; }
.absensi-page .check-badge.show { display: flex; }

.absensi-page .cta {
  width: 100%;
  max-width: 320px;
  margin-top: 24px;
  padding: 16px;
  border: none;
  border-radius: 16px;
  font-family: 'Barlow Condensed', sans-serif;
  font-weight: 700;
  font-size: 17px;
  letter-spacing: .02em;
  color: #142000;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  background: linear-gradient(155deg, var(--red), var(--red-deep));
  box-shadow: 0 10px 24px rgba(140,250,1,.28);
  transition: transform .12s, box-shadow .12s, opacity .15s;
}
.absensi-page .cta:hover { box-shadow: 0 12px 30px rgba(140,250,1,.36); transform: translateY(-1px); }
.absensi-page .cta:disabled {
  background: #D8D6D1; box-shadow: none; cursor: not-allowed;
  color: #9C9A96; transform: none;
}
.absensi-page .cta svg { width: 18px; height: 18px; }
.absensi-page .cta-actions {
  width: 100%;
  max-width: 320px;
  margin-top: 24px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.absensi-page .cta-actions .cta {
  margin-top: 0;
  max-width: none;
}
.absensi-page .cta-secondary {
  background: #fff;
  color: var(--ink);
  border: 1.5px solid var(--line);
  box-shadow: none;
}
.absensi-page .cta-secondary:hover {
  background: #fafaf9;
  box-shadow: none;
  transform: none;
  border-color: #d4d2cd;
}
.absensi-page .cta-secondary:disabled {
  background: #f3f2ef;
  border-color: var(--line);
  color: #9C9A96;
}
.absensi-page .preview-hint {
  margin: 12px 0 0;
  font-size: 12px;
  font-weight: 600;
  color: var(--ink-soft);
  text-align: center;
  max-width: 320px;
  line-height: 1.4;
}

.absensi-page .history-card {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: 22px;
  box-shadow: var(--shadow);
  overflow: hidden;
}
.absensi-page .history-head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
  padding: 22px 28px 18px;
  border-bottom: 1px solid var(--line-soft);
}
.absensi-page .history-head .label {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: var(--red-ink);
  margin: 0 0 4px;
}
.absensi-page .history-head h3 {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 22px;
  font-weight: 700;
  letter-spacing: -.01em;
  color: var(--ink);
  margin: 0;
}
.absensi-page .cal-nav {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.absensi-page .cal-nav a,
.absensi-page .cal-nav span {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  border: 1px solid var(--line);
  background: var(--surface);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: var(--ink);
  text-decoration: none;
  transition: background .15s ease, border-color .15s ease;
}
.absensi-page .cal-nav a:hover {
  background: var(--bg);
  border-color: var(--ink-faint);
}
.absensi-page .cal-nav span.is-disabled {
  opacity: .35;
  pointer-events: none;
}
.absensi-page .cal-nav svg { width: 16px; height: 16px; }
.absensi-page .cal-month {
  font-size: 13.5px;
  font-weight: 600;
  color: var(--ink-soft);
  min-width: 120px;
  text-align: center;
}
.absensi-page .cal-body { padding: 18px 22px 22px; }
.absensi-page .cal-weekdays {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 6px;
  margin-bottom: 8px;
}
.absensi-page .cal-weekdays span {
  text-align: center;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: var(--ink-faint);
  padding: 4px 0;
}
.absensi-page .cal-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 6px;
}
.absensi-page .cal-day {
  min-height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 15px;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  color: var(--ink-faint);
  background: transparent;
  border: 1px solid transparent;
  position: relative;
}
.absensi-page .cal-day.out { opacity: .28; }
.absensi-page .cal-day.present {
  background: var(--green-tint);
  color: var(--green);
  border-color: #C8E6D8;
}
.absensi-page .cal-day.absent {
  background: var(--red-tint);
  color: var(--red-deep);
  border-color: #F0C9C6;
}
.absensi-page .cal-day.today {
  background: #EEF3FF;
  color: #1D4ED8;
  border-color: #C7D7FE;
  box-shadow: inset 0 0 0 1.5px #2E6BE6;
}
.absensi-page .cal-day.late {
  background: var(--amber-tint);
  color: var(--amber);
  border-color: #F0D9A8;
}
.absensi-page .cal-day.holiday {
  background: #F3E8FF;
  color: #7C3AED;
  border-color: #DDD6FE;
}
.absensi-page .cal-day.off {
  background: #F3F4F6;
  color: #6B7280;
  border-color: #E5E7EB;
}
.absensi-page .cal-day.is-clickable {
  cursor: pointer;
  text-decoration: none;
  transition: transform .12s ease, box-shadow .12s ease;
}
.absensi-page .cal-day.is-clickable:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(20, 20, 20, .08);
}
.absensi-page .cal-day.is-selected {
  box-shadow: inset 0 0 0 2px var(--ink), 0 0 0 1px var(--line);
}
.absensi-page .day-detail-back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 14px;
  font-size: 13px;
  font-weight: 600;
  color: var(--ink-soft);
  text-decoration: none;
}
.absensi-page .day-detail-back:hover { color: var(--red-deep); }
.absensi-page .cal-day.dinas {
  background: #E0F2FE;
  color: #0369A1;
  border-color: #BAE6FD;
}
.absensi-page .cal-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 14px 18px;
  margin-top: 16px;
  padding-top: 14px;
  border-top: 1px solid var(--line-soft);
}
.absensi-page .cal-legend .item {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 12.5px;
  color: var(--ink-soft);
  font-weight: 500;
}
.absensi-page .cal-legend .swatch {
  width: 14px;
  height: 14px;
  border-radius: 5px;
  border: 1px solid transparent;
  flex-shrink: 0;
}
.absensi-page .cal-legend .swatch.present { background: var(--green-tint); border-color: #C8E6D8; }
.absensi-page .cal-legend .swatch.late { background: var(--amber-tint); border-color: #F0D9A8; }
.absensi-page .cal-legend .swatch.absent { background: var(--red-tint); border-color: #F0C9C6; }
.absensi-page .cal-legend .swatch.today { background: #EEF3FF; border-color: #C7D7FE; }
.absensi-page .cal-legend .swatch.holiday { background: #F3E8FF; border-color: #DDD6FE; }
.absensi-page .cal-legend .swatch.off { background: #F3F4F6; border-color: #E5E7EB; }
.absensi-page .cal-legend .swatch.dinas { background: #E0F2FE; border-color: #BAE6FD; }

.absensi-page .late-box {
  margin: 0 0 16px;
  padding: 12px 14px;
  border-radius: 12px;
  background: var(--amber-tint);
  border: 1px solid #F0D9A8;
}
.absensi-page .late-box .lt {
  font-size: 12px;
  font-weight: 700;
  color: var(--amber);
  letter-spacing: .04em;
  text-transform: uppercase;
  margin-bottom: 4px;
}
.absensi-page .late-box .ld {
  font-size: 12.5px;
  color: #8A5A12;
  margin-bottom: 10px;
  line-height: 1.4;
}
.absensi-page .late-box textarea {
  width: 100%;
  min-height: 78px;
  border-radius: 10px;
  border: 1px solid #E8D4A8;
  background: #fff;
  padding: 10px 12px;
  font-size: 13px;
  color: var(--ink);
  outline: none;
  resize: vertical;
  font-family: inherit;
}
.absensi-page .late-box textarea:focus {
  border-color: #D4A84A;
  box-shadow: 0 0 0 3px rgba(185, 121, 26, .12);
}
.absensi-page .late-box .err {
  margin-top: 6px;
  font-size: 12px;
  color: var(--red-deep);
  font-weight: 600;
}
.absensi-page .pill-ok {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: var(--green-tint);
  color: var(--green);
  font-size: 11px;
  font-weight: 700;
  padding: 3px 9px;
  border-radius: 20px;
  width: fit-content;
}
.absensi-page .pill-ok .d {
  width: 5px; height: 5px; border-radius: 50%; background: var(--green);
}
.absensi-page .pill-late {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: var(--amber-tint);
  color: var(--amber);
  font-size: 11px;
  font-weight: 700;
  padding: 3px 9px;
  border-radius: 20px;
  width: fit-content;
}
.absensi-page .pill-late .d {
  width: 5px; height: 5px; border-radius: 50%; background: var(--amber);
}
.absensi-page .recap {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: 22px;
  box-shadow: var(--shadow-lg);
  overflow: hidden;
  margin-bottom: 18px;
  width: 100%;
}
.absensi-page .recap-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 22px;
  border-bottom: 1px solid var(--line-soft);
  gap: 12px;
}
.absensi-page .recap-head h2 {
  font-size: 17px;
  font-weight: 700;
  margin: 0 0 2px;
}
.absensi-page .recap-head p {
  font-size: 12.5px;
  color: var(--ink-soft);
  margin: 0;
}
.absensi-page .pill-complete {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: var(--green-tint);
  color: var(--green);
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
}
.absensi-page .pill-complete svg { width: 13px; height: 13px; }
.absensi-page .recap-body {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}
.absensi-page .ringkasan {
  background: linear-gradient(165deg, var(--panel-dark), var(--panel-dark-2));
  color: #fff;
  padding: 22px 22px 18px;
  position: relative;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.absensi-page .ringkasan::before {
  content: '';
  position: absolute;
  top: -80px; right: -80px;
  width: 220px; height: 220px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(56,201,143,.22), transparent 70%);
}
.absensi-page .ringkasan::after {
  content: '';
  position: absolute;
  bottom: -90px; left: -70px;
  width: 200px; height: 200px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(140,250,1,.14), transparent 70%);
}
.absensi-page .ringkasan .eyebrow {
  font-family: 'Barlow Condensed', sans-serif;
  font-weight: 700;
  letter-spacing: .12em;
  text-transform: uppercase;
  font-size: 12px;
  color: #9BE0BF;
  position: relative;
  z-index: 1;
}
.absensi-page .ringkasan .bname {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 19px;
  font-weight: 700;
  margin: 5px 0 2px;
  position: relative;
  z-index: 1;
  line-height: 1.25;
}
.absensi-page .ringkasan .bcode {
  font-size: 10.5px;
  color: #9A9BA2;
  letter-spacing: .08em;
  text-transform: uppercase;
  position: relative;
  z-index: 1;
}
.absensi-page .ring-wrap {
  position: relative;
  width: 148px;
  height: 148px;
  margin: 18px auto 14px;
  z-index: 1;
}
.absensi-page .ring-wrap svg {
  width: 100%;
  height: 100%;
  transform: rotate(-90deg);
}
.absensi-page .ring-center {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}
.absensi-page .ring-center .dur {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 26px;
  font-weight: 700;
  letter-spacing: .01em;
}
.absensi-page .ring-center .lbl {
  font-size: 9.5px;
  color: #9A9BA2;
  letter-spacing: .1em;
  text-transform: uppercase;
  margin-top: 2px;
}
.absensi-page .mini-stats {
  display: flex;
  gap: 8px;
  position: relative;
  z-index: 1;
  margin-bottom: 0;
}
.absensi-page .mini-stat {
  flex: 1;
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 11px;
  padding: 10px 11px;
}
.absensi-page .mini-stat .k {
  font-size: 9.5px;
  color: #9A9BA2;
  letter-spacing: .08em;
  text-transform: uppercase;
  font-weight: 600;
}
.absensi-page .mini-stat .v {
  font-size: 13px;
  font-weight: 700;
  margin-top: 3px;
}
.absensi-page .mini-stat .v.valid { color: var(--green-bright); }
.absensi-page .verify-list {
  display: flex;
  flex-direction: column;
  gap: 7px;
  position: relative;
  z-index: 1;
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px solid rgba(255,255,255,.08);
}
.absensi-page .verify-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #C8C9CD;
}
.absensi-page .verify-item .vi {
  width: 17px;
  height: 17px;
  border-radius: 50%;
  background: rgba(56,201,143,.16);
  color: var(--green-bright);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.absensi-page .verify-item .vi svg { width: 9px; height: 9px; }
.absensi-page .dual-col {
  padding: 18px 18px;
  display: flex;
  flex-direction: column;
  min-width: 0;
  border-left: 1px solid var(--line-soft);
}
.absensi-page .dual-col .colhead {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}
.absensi-page .dual-col .colhead .cap {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 13px;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--ink-soft);
  font-weight: 700;
}
.absensi-page .dual-split {
  display: grid;
  grid-template-columns: 120px 1fr;
  gap: 12px;
  align-items: center;
  min-width: 0;
  flex: 1;
}
.absensi-page .photo-frame {
  position: relative;
  width: 100%;
  aspect-ratio: 1 / 1;
  border-radius: 14px;
  overflow: hidden;
  background: var(--line-soft);
  border: 1px solid var(--line);
  box-shadow: var(--shadow);
  flex-shrink: 0;
}
.absensi-page .photo-frame img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.absensi-page .photo-frame .ph {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--ink-faint);
  font-size: 11px;
  text-align: center;
  padding: 8px;
}
.absensi-page .photo-badge {
  position: absolute;
  bottom: 6px;
  right: 6px;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: var(--green);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid var(--surface);
}
.absensi-page .photo-badge svg { width: 11px; height: 11px; }
.absensi-page .meta-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 0;
  min-width: 0;
}
.absensi-page .meta-item {
  display: flex;
  align-items: flex-start;
  gap: 9px;
}
.absensi-page .meta-item .mi {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: var(--line-soft);
  color: var(--ink-faint);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.absensi-page .meta-item .mi svg { width: 13px; height: 13px; }
.absensi-page .meta-item .mt { min-width: 0; }
.absensi-page .meta-item .mt .k {
  font-size: 10px;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--ink-faint);
  font-weight: 700;
}
.absensi-page .meta-item .mt .v {
  font-size: 13px;
  font-weight: 700;
  margin-top: 1px;
  color: var(--ink);
  line-height: 1.3;
  word-break: break-word;
}

.absensi-page .empty-state {
  padding: 36px 24px;
  text-align: center;
  color: var(--ink-soft);
}
.absensi-page .empty-state .t { font-size: 16px; font-weight: 700; color: var(--ink); margin-bottom: 6px; }
.absensi-page .empty-state .d { font-size: 13.5px; }

@media (max-width: 980px) {
  .absensi-page .absen-body { grid-template-columns: 1fr; }
  .absensi-page .absen-body.is-pulang { grid-template-columns: 1fr; }
  .absensi-page .absen-left { border-right: none; border-bottom: 1px solid var(--line-soft); }
  .absensi-page .absen-body.is-pulang .absen-left {
    border-left: none;
    border-bottom: none;
    border-top: 1px solid var(--line-soft);
  }
  .absensi-page .cal-body { padding: 14px 14px 18px; }
  .absensi-page .cal-day { border-radius: 10px; font-size: 14px; }
  .absensi-page .recap-body { grid-template-columns: 1fr; }
  .absensi-page .dual-col { border-left: none; border-top: 1px solid var(--line-soft); }
  .absensi-page .dual-split { grid-template-columns: 112px 1fr; gap: 12px; }
}
</style>
@endpush

<div
    class="absensi-page"
    x-data="absensiApp(@js([
        'locations' => $locations->map(fn ($l) => [
            'id' => $l->id,
            'name' => $l->displayName(),
            'lat' => $l->latitude,
            'lng' => $l->longitude,
            'radius' => $l->radius_meters,
            'ready' => $l->hasCoordinates(),
        ])->values(),
        'defaultLocationId' => $defaultLocationId,
        'nextType' => $nextType,
        'branchName' => $branchName,
        'scheduledCheckIn' => $scheduledCheckInLabel ?? '08:00',
        'autoPickLocation' => (bool) ($autoPickLocation ?? false),
        'flexibleAttendance' => (bool) ($flexibleAttendance ?? false),
        'bypassGeofence' => (bool) (($flexibleAttendance ?? false) || ($isWfhToday ?? false)),
    ]))"
>
    @if($locations->isEmpty())
    <div class="absen-card">
        <div class="empty-state">
            <div class="t">Lokasi absensi belum tersedia</div>
            <div class="d">Hubungi Head HR untuk menyiapkan titik koordinat lokasi kerja.</div>
        </div>
    </div>
    @elseif(! $requiresAttendanceToday)
    <div class="absen-card">
        <div class="empty-state">
            <div class="t">{{ $todayOffLabel ?? 'Hari libur' }}</div>
            <div class="d">Tidak perlu absen hari ini. Jam kerja standar {{ $scheduledRange }}.</div>
        </div>
    </div>
    @else
    @if(!empty($isWfhToday))
    <div class="absen-card" style="margin-bottom:12px;border-color:#fde68a;background:#fffbeb">
        <div class="empty-state" style="padding:14px 16px">
            <div class="t" style="font-size:14px;color:#92400e">Hari ini WFH</div>
            <div class="d" style="color:#78350f">Tetap wajib absen masuk &amp; pulang. Saat WFH, absen boleh di luar radius kantor.</div>
        </div>
    </div>
    @endif
    @if($nextType)
    <div class="absen-card">
        <div class="absen-head">
            <div>
                <h2>{{ $nextType === 'check_out' ? 'Absen Pulang' : 'Absen Masuk' }}</h2>
                <p>
                    @if(! empty($flexibleAttendance))
                        @if($nextType === 'check_out')
                        Jadwal fleksibel: absen pulang kapan saja setelah masuk · lokasi bebas (boleh di luar kantor/event).
                        @else
                        Jadwal fleksibel: absen masuk kapan saja · tanpa penanda telat · lokasi bebas (boleh di luar kantor/event).
                        @endif
                    @elseif(! empty($isWfhToday))
                        @if($nextType === 'check_out')
                        WFH: tetap absen pulang pukul <strong>{{ $checkOutWindowLabel ?? '17:00–23:59' }}</strong> · lokasi bebas (boleh di rumah).
                        @else
                        WFH: tetap absen masuk pukul <strong>{{ $checkInWindowLabel ?? '07:00–09:00' }}</strong> · lokasi bebas (boleh di rumah).
                        @endif
                    @elseif($nextType === 'check_out')
                    Jam absen pulang <strong>{{ $checkOutWindowLabel ?? '17:00–23:59' }}</strong>. Pastikan wajah terlihat jelas.
                    @else
                    Jam absen masuk <strong>{{ $checkInWindowLabel ?? '07:00–09:00' }}</strong>
                    · setelah jam masuk <strong>{{ $scheduledCheckInLabel ?? '08:00' }}</strong> tetap bisa absen (ditandai telat).
                    @endif
                </p>
            </div>
            <span class="badge-state {{ $nextType === 'check_out' ? 'out' : '' }}">
                <span class="d"></span>{{ $nextType === 'check_out' ? 'Pulang' : 'Masuk' }}
            </span>
        </div>

        <form method="POST" action="{{ route('absensi.store') }}" enctype="multipart/form-data" class="absen-body {{ $nextType === 'check_out' ? 'is-pulang' : '' }}" novalidate x-ref="form">
            @csrf
            <input type="hidden" name="type" value="{{ $nextType }}">
            <input type="hidden" name="latitude" :value="lat">
            <input type="hidden" name="longitude" :value="lng">
            <input type="hidden" name="accuracy_meters" :value="accuracy">

            <div class="absen-left">
                <div class="branch-card">
                    <div>
                        <div class="bname" x-text="selected?.name || @js($branchName)">{{ $branchName }}</div>
                        <div class="bcode">{{ $user->name }} · {{ $branchCode }}</div>
                        <div class="clock" x-text="clockText">--:--:--</div>
                    </div>
                </div>

                @if($locations->count() > 1 && ! $autoPickLocation)
                    <select name="attendance_location_id" x-model="locationId" class="loc-select" @change="verifyLocation()" style="margin:0 0 20px">
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}" @selected((int) $defaultLocationId === (int) $loc->id)>{{ $loc->displayName() }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="attendance_location_id" :value="locationId" value="{{ $defaultLocationId }}">
                @endif

                <div class="steps">
                    <div class="step" :class="step1Class">
                        <div class="step-dot">
                            <template x-if="geoOk === true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="geoOk !== true"><span>1</span></template>
                        </div>
                        <div class="step-txt">
                            <div class="n" x-text="bypassGeofence ? 'Lokasi tercatat' : 'Verifikasi lokasi'"></div>
                            <div class="d" x-text="step1Desc">Mendeteksi titik lokasi kamu di area cabang</div>
                        </div>
                    </div>
                    <div class="step" :class="step2Class">
                        <div class="step-dot">
                            <template x-if="cameraReady || previewUrl">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="!cameraReady && !previewUrl"><span>2</span></template>
                        </div>
                        <div class="step-txt">
                            <div class="n">Kamera siap</div>
                            <div class="d" x-text="(cameraReady || previewUrl) ? 'Wajah harus terlihat jelas di dalam bingkai' : 'Menunggu izin kamera…'"></div>
                        </div>
                    </div>
                    <div class="step" :class="step3Class">
                        <div class="step-dot">
                            <template x-if="previewUrl">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="!previewUrl"><span>3</span></template>
                        </div>
                        <div class="step-txt">
                            <div class="n" x-text="previewUrl ? 'Cek foto & kirim' : 'Ambil selfie'"></div>
                            <div class="d" x-text="previewUrl ? 'Pastikan wajah jelas, lalu kirim atau foto ulang' : 'Jepret dulu, cek preview, baru kirim'"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="absen-right">
                <div class="selfie-ring" :class="{ live: cameraReady && !previewUrl, captured: !!previewUrl }">
                    <div class="selfie-inner">
                        <div class="selfie-placeholder" x-show="!cameraReady && !previewUrl">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="7" width="18" height="13" rx="2.5"/><path d="M8 7l1.5-3h5L16 7"/><circle cx="12" cy="13.5" r="3.5"/></svg>
                            <p>Izinkan akses kamera<br>untuk absensi</p>
                        </div>
                        <video x-ref="video" x-show="cameraReady && !previewUrl" autoplay playsinline muted></video>
                        <img x-show="previewUrl" :src="previewUrl" alt="Preview selfie">
                    </div>
                    <div class="check-badge" :class="previewUrl && 'show'">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
                    </div>
                </div>

                <input type="file" name="selfie" x-ref="selfieInput" accept="image/jpeg" class="hidden" tabindex="-1" aria-hidden="true">
                <canvas x-ref="canvas" class="hidden"></canvas>

                <p class="preview-hint" x-show="previewUrl" x-cloak>Cek foto dulu. Bisa foto ulang sebelum dikirim.</p>

                <div class="cta-actions" x-show="!previewUrl">
                    <button type="button" class="cta" @click="takePhoto()" :disabled="capturing || submitting || !cameraReady">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M8 7l1.5-3h5L16 7"/>
                            <rect x="3" y="7" width="18" height="13" rx="2.5"/>
                            <circle cx="12" cy="13.5" r="3.5"/>
                        </svg>
                        <span x-text="capturing ? 'Mengambil…' : 'Ambil Selfie'"></span>
                    </button>
                </div>

                <div class="cta-actions" x-show="previewUrl" x-cloak>
                    <button type="button" class="cta" @click="confirmSubmit()" :disabled="submitting">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 10a2 2 0 0 0-2 2c0 1.02-.1 2.51-.26 4"/>
                            <path d="M14 13.12c0 2.38 0 6.38-1 8.88"/>
                            <path d="M17.29 21.02c.12-.6.43-2.3.5-3.02"/>
                            <path d="M20 4a9 9 0 0 1-2.25 6.25"/>
                            <path d="M9 13.5c.32.83.71 1.69 1 2.5a.5.5 0 0 1-.1.5"/>
                            <path d="M12 4a8 8 0 0 0-6.5 12.5"/>
                            <path d="M4.5 11a8 8 0 0 1 1.5-5"/>
                            <path d="M7.5 19.5A8.5 8.5 0 0 0 12 22"/>
                            <path d="M16.5 7a5 5 0 0 0-3.5-3"/>
                        </svg>
                        <span x-text="submitting ? 'Mengirim…' : (nextType === 'check_out' ? 'Kirim Absen Pulang' : 'Kirim Absen Masuk')"></span>
                    </button>
                    <button type="button" class="cta cta-secondary" @click="retake()" :disabled="submitting || capturing">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 12a9 9 0 1 0 3-6.7"/>
                            <path d="M3 4v5h5"/>
                        </svg>
                        <span>Foto ulang</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
    @elseif(($attendancePhase ?? '') === 'check_in')
    @php
        $beforeCheckIn = (bool) ($beforeCheckInWindow ?? false);
        $pastDeadline = (bool) ($pastCheckInDeadline ?? false);
    @endphp
    <div class="absen-card">
        <div class="empty-state" style="padding:40px 24px">
            <div class="t">
                @if($beforeCheckIn)
                Belum waktunya absen masuk
                @elseif($pastDeadline)
                Tidak hadir
                @else
                Jam absen masuk sudah berakhir
                @endif
            </div>
            <div class="d">
                @if($beforeCheckIn)
                Absen masuk dibuka pukul <strong>{{ $checkInWindowLabel ?? '07:00–08:00' }}</strong>.
                Silakan kembali pada jam tersebut.
                @elseif($pastDeadline)
                Batas absen masuk <strong>{{ $checkInWindowLabel ?? '07:00–09:00' }}</strong> sudah lewat.
                Kehadiran hari ini dihitung <strong>tidak hadir</strong>.
                @else
                Absen masuk hanya dapat dilakukan pukul <strong>{{ $checkInWindowLabel ?? '07:00–09:00' }}</strong>.
                Hubungi Head jika ada kendala.
                @endif
            </div>
            <div class="d" style="margin-top:10px; font-size:12px">Jam kerja standar {{ $scheduledRange }}.</div>
        </div>
    </div>
    @elseif(($attendancePhase ?? '') === 'check_out')
    <div class="absen-card">
        <div class="empty-state" style="padding:40px 24px">
            <div class="t">Menunggu jam absen pulang</div>
            <div class="d">
                Absen masuk sudah tercatat
                @if($checkIn)
                pukul <strong>{{ $fmtTime($checkIn->checked_at) }}</strong>.
                @endif
                Absen pulang dibuka pukul <strong>{{ $checkOutWindowLabel ?? '17:00–23:59' }}</strong>.
            </div>
            @if($checkIn && $checkIn->status === \App\Models\AttendanceRecord::STATUS_PENDING)
            <div class="d" style="margin-top:10px; font-size:12px">Status masuk: menunggu validasi Head.</div>
            @endif
        </div>
    </div>
    @endif
    @endif

    @if($showDayDetail)
        @if(! $isSelectedToday)
        <a href="{{ route('absensi.index', ['bulan' => $calendarMonth->format('Y-m')]) }}" class="day-detail-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
            Kembali ke hari ini
        </a>
        @endif

        @if($selectedCheckIn || $selectedCheckOut)
            @include('partials.absensi.day-recap', [
                'dayCheckIn' => $selectedCheckIn,
                'dayCheckOut' => $selectedCheckOut,
                'selectedDate' => $selectedDate,
                'user' => $user,
                'branchName' => $branchName,
                'attendanceSettings' => $attendanceSettings,
                'tz' => $tz,
                'fmtTime' => $fmtTime,
            ])
        @else
            @php
                $dayStatus = $selectedDayCalendar['status'] ?? 'muted';
                $dayLabel = $selectedDayCalendar['label'] ?? null;
                $dayTitles = [
                    'absent' => 'Tidak hadir',
                    'holiday' => 'Hari libur',
                    'off' => 'Bukan hari kerja',
                    'dinas' => 'Perjalanan dinas',
                    'today' => 'Hari ini',
                ];
                $dayTitle = $dayTitles[$dayStatus] ?? 'Tidak ada data absensi';
            @endphp
            <div class="absen-card" style="margin-bottom:18px">
                <div class="empty-state" style="padding:32px 24px">
                    <div class="t">{{ $dayTitle }}</div>
                    <div class="d">{{ $selectedDate->translatedFormat('l, j F Y') }}@if($dayLabel) · {{ $dayLabel }}@endif</div>
                    @if($dayStatus === 'today')
                    <div class="d" style="margin-top:8px">Absensi hari ini belum tercatat atau masih dalam proses.</div>
                    @endif
                </div>
            </div>
        @endif
    @endif

    <div class="history-card">
        <div class="history-head">
            <div>
                <div class="label">Kehadiran</div>
                <h3>{{ $calendarMonth->translatedFormat('F Y') }}</h3>
            </div>
            <div class="cal-nav">
                <a href="{{ route('absensi.index', ['bulan' => $prevMonth]) }}" title="Bulan sebelumnya" aria-label="Bulan sebelumnya">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                </a>
                <div class="cal-month">{{ $calendarMonth->format('M Y') }}</div>
                @if($canGoNext)
                <a href="{{ route('absensi.index', ['bulan' => $nextMonth]) }}" title="Bulan berikutnya" aria-label="Bulan berikutnya">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </a>
                @else
                <span class="is-disabled" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </span>
                @endif
            </div>
        </div>

        <div class="cal-body">
            <div class="cal-weekdays">
                <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span>
            </div>
            <div class="cal-grid">
                @foreach($calendarDays as $day)
                    @php
                        $dayKey = $day['key'];
                        $dayClickable = ($day['in_month'] ?? false) && $dayKey <= $todayKey;
                        $daySelected = $dayKey === $selectedDate->toDateString();
                        $dayTitle = \Carbon\Carbon::parse($dayKey)->translatedFormat('l, j F Y').(! empty($day['label']) ? ' · '.$day['label'] : '');
                        $dayClasses = trim('cal-day '.$day['status'].' '.($day['in_month'] ? '' : 'out ').($dayClickable ? 'is-clickable ' : '').($daySelected ? 'is-selected' : ''));
                    @endphp
                    @if($dayClickable)
                    <a
                        href="{{ route('absensi.index', ['bulan' => $calendarMonth->format('Y-m'), 'tanggal' => $dayKey]) }}"
                        class="{{ $dayClasses }}"
                        title="{{ $dayTitle }}"
                        aria-label="Lihat absensi {{ $dayTitle }}"
                        aria-current="{{ $daySelected ? 'date' : 'false' }}"
                    >{{ $day['day'] }}</a>
                    @else
                    <div
                        class="{{ $dayClasses }}"
                        title="{{ $dayTitle }}"
                    >{{ $day['day'] }}</div>
                    @endif
                @endforeach
            </div>
            <div class="cal-legend">
                <div class="item"><span class="swatch present"></span> Masuk</div>
                <div class="item"><span class="swatch late"></span> Telat</div>
                <div class="item"><span class="swatch absent"></span> Tidak masuk</div>
                <div class="item"><span class="swatch today"></span> Hari ini</div>
                <div class="item"><span class="swatch holiday"></span> Libur</div>
                <div class="item"><span class="swatch dinas"></span> Perjalanan dinas</div>
                <div class="item"><span class="swatch off"></span> Bukan hari kerja</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function absensiApp(cfg) {
    return {
        locations: cfg.locations || [],
        locationId: String(cfg.defaultLocationId || ''),
        nextType: cfg.nextType,
        lat: '',
        lng: '',
        accuracy: '',
        cameraReady: false,
        previewUrl: null,
        stream: null,
        capturing: false,
        submitting: false,
        clockText: '--:--:--',
        geoOk: null,
        geoDistance: null,
        clockTimer: null,
        scheduledCheckIn: cfg.scheduledCheckIn || '08:00',
        autoPickLocation: !!cfg.autoPickLocation,
        flexibleAttendance: !!cfg.flexibleAttendance,
        bypassGeofence: !!(cfg.bypassGeofence ?? cfg.flexibleAttendance),
        get selected() {
            return this.locations.find((l) => String(l.id) === String(this.locationId)) || null;
        },
        get step1Class() {
            if (this.geoOk === true) return 'done';
            if (this.geoOk === false) return 'active';
            return 'active';
        },
        get step1Desc() {
            if (this.bypassGeofence) {
                if (this.geoOk === true) {
                    return this.geoDistance != null
                        ? 'Lokasi tercatat · ' + this.geoDistance + 'm dari titik referensi (radius tidak diwajibkan)'
                        : 'Lokasi tercatat · boleh absen di mana saja';
                }
                if (this.geoOk === false) return 'GPS belum tersedia · izinkan lokasi untuk mencatat titik absen';
                return this.flexibleAttendance
                    ? 'Mendeteksi GPS · Head/Staf Marketing tidak wajib di radius kantor'
                    : 'Mendeteksi GPS · hari WFH tidak wajib di radius kantor';
            }
            if (this.geoOk === true) {
                return this.geoDistance != null
                    ? 'Lokasi terverifikasi · ' + this.geoDistance + 'm dari titik lokasi'
                    : 'Lokasi terverifikasi di area cabang';
            }
            if (this.geoOk === false) return 'Belum di dalam radius lokasi';
            return 'Mendeteksi titik lokasi kamu di area cabang';
        },
        get step2Class() {
            if (this.cameraReady || this.previewUrl) return 'done';
            if (this.geoOk === true) return 'active';
            return '';
        },
        get step3Class() {
            if (this.previewUrl) return 'active';
            return this.cameraReady ? 'active' : '';
        },
        init() {
            this.tickClock();
            this.clockTimer = setInterval(() => this.tickClock(), 1000);
            if (this.nextType) {
                this.verifyLocation();
                this.startCamera();
            }
        },
        tickClock() {
            const now = new Date();
            const pad = (n) => String(n).padStart(2, '0');
            this.clockText = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
        },
        destroy() {
            if (this.clockTimer) clearInterval(this.clockTimer);
            if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
            if (this.stream) this.stream.getTracks().forEach((t) => t.stop());
        },
        alert(title, text, icon = 'warning') {
            if (typeof Swal !== 'undefined' && Swal.fire) {
                return Swal.fire({
                    title,
                    text,
                    icon,
                    confirmButtonColor: '#8CFA01',
                    confirmButtonText: 'OK',
                });
            }
            // Fallback jika CDN Swal belum siap
            return Promise.resolve();
        },
        haversine(lat1, lon1, lat2, lon2) {
            const R = 6371000;
            const toRad = (v) => v * Math.PI / 180;
            const dLat = toRad(lat2 - lat1);
            const dLon = toRad(lon2 - lon1);
            const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
            return 2 * R * Math.asin(Math.min(1, Math.sqrt(a)));
        },
        getGps() {
            return new Promise((resolve, reject) => {
                if (!navigator.geolocation) {
                    reject(new Error('Browser tidak mendukung GPS.'));
                    return;
                }
                navigator.geolocation.getCurrentPosition(
                    (pos) => resolve(pos),
                    (err) => reject(new Error(err.message || 'Izin lokasi ditolak.')),
                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
                );
            });
        },
        async verifyLocation() {
            try {
                const pos = await this.getGps();
                this.lat = pos.coords.latitude.toFixed(7);
                this.lng = pos.coords.longitude.toFixed(7);
                this.accuracy = pos.coords.accuracy ? pos.coords.accuracy.toFixed(1) : '';

                if (this.autoPickLocation && this.locations.length > 1) {
                    this.pickNearestLocation(Number(this.lat), Number(this.lng));
                }

                const loc = this.selected;
                if (!loc || !loc.ready) {
                    this.geoOk = false;
                    this.geoDistance = null;
                    return;
                }

                const distance = this.haversine(Number(loc.lat), Number(loc.lng), Number(this.lat), Number(this.lng));
                this.geoDistance = Math.round(distance);
                const radius = Number(loc.radius || 50);
                // Marketing / WFH: GPS tetap dicatat, radius tidak diwajibkan
                this.geoOk = this.bypassGeofence ? true : distance <= radius;
            } catch (e) {
                this.geoOk = false;
                this.geoDistance = null;
            }
        },
        pickNearestLocation(lat, lng) {
            let best = null;
            let bestDist = Infinity;
            for (const loc of this.locations) {
                if (!loc.ready) continue;
                const d = this.haversine(Number(loc.lat), Number(loc.lng), lat, lng);
                if (d < bestDist) {
                    bestDist = d;
                    best = loc;
                }
            }
            if (best) {
                this.locationId = String(best.id);
            }
        },
        stopCamera() {
            if (this.stream) this.stream.getTracks().forEach((t) => t.stop());
            this.stream = null;
            this.cameraReady = false;
        },
        async startCamera() {
            try {
                if (this.stream) this.stream.getTracks().forEach((t) => t.stop());
                this.stream = null;
                if (this.previewUrl) {
                    URL.revokeObjectURL(this.previewUrl);
                    this.previewUrl = null;
                }
                if (this.$refs.selfieInput) this.$refs.selfieInput.value = '';
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: { ideal: 720 }, height: { ideal: 720 } },
                    audio: false,
                });
                await this.$nextTick();
                if (this.$refs.video) this.$refs.video.srcObject = this.stream;
                this.cameraReady = true;
            } catch (e) {
                this.cameraReady = false;
                this.alert('Kamera diperlukan', 'Izinkan akses kamera untuk mengambil selfie absensi.', 'error');
            }
        },
        captureSelfie() {
            return new Promise((resolve, reject) => {
                const video = this.$refs.video;
                const canvas = this.$refs.canvas;
                if (!video || !canvas || !this.cameraReady) {
                    reject(new Error('Kamera belum siap.'));
                    return;
                }
                const w = video.videoWidth || 640;
                const h = video.videoHeight || 480;
                if (!w || !h) {
                    reject(new Error('Preview kamera belum siap. Tunggu sebentar lalu coba lagi.'));
                    return;
                }
                canvas.width = w;
                canvas.height = h;
                canvas.getContext('2d').drawImage(video, 0, 0);
                canvas.toBlob((blob) => {
                    if (!blob) {
                        reject(new Error('Gagal mengambil selfie.'));
                        return;
                    }
                    const file = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    this.$refs.selfieInput.files = dt.files;
                    if (this.previewUrl) URL.revokeObjectURL(this.previewUrl);
                    this.previewUrl = URL.createObjectURL(blob);
                    this.stopCamera();
                    resolve(file);
                }, 'image/jpeg', 0.9);
            });
        },
        async ensureWithinRadius() {
            const loc = this.selected;
            if (!loc) {
                throw new Error('Lokasi absensi tidak ditemukan.');
            }
            if (!loc.ready) {
                throw new Error('Titik koordinat belum diset oleh Head HR.');
            }

            const pos = await this.getGps();
            this.lat = pos.coords.latitude.toFixed(7);
            this.lng = pos.coords.longitude.toFixed(7);
            this.accuracy = pos.coords.accuracy ? pos.coords.accuracy.toFixed(1) : '';

            if (this.autoPickLocation && this.locations.length > 1) {
                this.pickNearestLocation(Number(this.lat), Number(this.lng));
            }

            const activeLoc = this.selected;
            if (!activeLoc || !activeLoc.ready) {
                throw new Error('Lokasi absensi tidak ditemukan.');
            }

            const distance = this.haversine(Number(activeLoc.lat), Number(activeLoc.lng), Number(this.lat), Number(this.lng));
            this.geoDistance = Math.round(distance);
            const radius = Number(activeLoc.radius || 50);
            this.geoOk = this.bypassGeofence ? true : distance <= radius;
            if (!this.bypassGeofence && distance > radius) {
                throw new Error(
                    'Anda terlalu jauh dari lokasi absensi. Jarak saat ini ' + Math.round(distance) + ' m (maks. ' + radius + ' m).'
                );
            }
        },
        async takePhoto() {
            if (this.capturing || this.submitting || this.previewUrl) return;

            if (!this.cameraReady) {
                this.alert('Kamera diperlukan', 'Izinkan akses kamera terlebih dahulu.', 'error');
                await this.startCamera();
                return;
            }

            this.capturing = true;
            try {
                await this.ensureWithinRadius();
                await this.captureSelfie();
                if (!this.$refs.selfieInput?.files?.length) {
                    throw new Error('Selfie gagal diambil. Coba lagi.');
                }
            } catch (err) {
                this.alert('Gagal mengambil foto', err.message || 'Terjadi kesalahan. Coba lagi.', 'error');
                if (!this.cameraReady) this.startCamera();
            } finally {
                this.capturing = false;
            }
        },
        async retake() {
            if (this.submitting || this.capturing) return;
            await this.startCamera();
        },
        async confirmSubmit() {
            if (this.submitting || !this.previewUrl) return;
            if (!this.$refs.selfieInput?.files?.length) {
                this.alert('Foto belum siap', 'Ambil selfie terlebih dahulu atau foto ulang.', 'error');
                await this.retake();
                return;
            }

            this.submitting = true;
            try {
                await this.ensureWithinRadius();
                await this.$nextTick();
                this.$refs.form.submit();
            } catch (err) {
                this.alert('Gagal absen', err.message || 'Terjadi kesalahan. Coba lagi.', 'error');
                this.submitting = false;
            }
        },
    };
}
</script>
@endpush

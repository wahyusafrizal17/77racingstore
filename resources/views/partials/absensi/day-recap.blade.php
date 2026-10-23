@php
    $tz = $tz ?? config('app.timezone');
    $fmtTime = $fmtTime ?? fn ($dt) => $dt ? $dt->timezone($tz)->format('H:i') : '— : —';
    $checkIn = $dayCheckIn ?? null;
    $checkOut = $dayCheckOut ?? null;
    $user = $user ?? auth()->user();
    $branchName = $branchName ?? 'Lokasi kerja';
    $branchCode = trim((string) ($user->role ?? 'Karyawan'));
    $scheduledWorkMinutes = $attendanceSettings?->workMinutes() ?? (9 * 60);

    $workDurationShort = null;
    $workRange = null;
    $workRingOffset = 464.9;
    if ($checkIn && $checkOut) {
        $mins = (int) $checkIn->checked_at->diffInMinutes($checkOut->checked_at);
        $hours = intdiv($mins, 60);
        $rem = $mins % 60;
        $workDurationShort = $hours.'j '.str_pad((string) $rem, 2, '0', STR_PAD_LEFT).'m';
        $workRange = $checkIn->checked_at->timezone($tz)->format('H:i').'–'.$checkOut->checked_at->timezone($tz)->format('H:i');
        $progress = max(0, min(1, $mins / max(1, $scheduledWorkMinutes)));
        $workRingOffset = round(464.9 * (1 - $progress), 1);
    }

    $recapLoc = $checkIn?->location?->displayName() ?? $checkOut?->location?->displayName() ?? $branchName;
    $dateLabel = ($selectedDate ?? now($tz))->translatedFormat('l, j F Y');
    $isComplete = $checkIn && $checkOut;
@endphp

<div class="recap" style="margin-bottom:18px">
    <div class="recap-head">
        <div>
            <h2>{{ $isComplete ? 'Kehadiran Lengkap' : 'Detail Kehadiran' }}</h2>
            <p>{{ $dateLabel }}</p>
        </div>
        @if($isComplete)
        <span class="pill-complete">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
            Lengkap
        </span>
        @endif
    </div>

    <div class="recap-body">
        <div class="ringkasan">
            <div class="eyebrow">Ringkasan</div>
            <div class="bname">{{ $recapLoc }}</div>
            <div class="bcode">{{ $user->name }} · {{ $branchCode }}</div>

            <div class="ring-wrap">
                <svg viewBox="0 0 172 172">
                    <circle cx="86" cy="86" r="74" fill="none" stroke="rgba(255,255,255,.08)" stroke-width="12"/>
                    <circle cx="86" cy="86" r="74" fill="none" stroke="url(#absensiRingGradDay)" stroke-width="12" stroke-linecap="round" stroke-dasharray="464.9" stroke-dashoffset="{{ $workRingOffset }}"/>
                    <defs>
                        <linearGradient id="absensiRingGradDay" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#38C98F"/>
                            <stop offset="100%" stop-color="#1D8A5C"/>
                        </linearGradient>
                    </defs>
                </svg>
                <div class="ring-center">
                    <div class="dur">{{ $workDurationShort ?? '—' }}</div>
                    <div class="lbl">Durasi kerja</div>
                </div>
            </div>

            <div class="mini-stats">
                <div class="mini-stat">
                    <div class="k">Jam Kerja</div>
                    <div class="v">{{ $workRange ?? '—' }}</div>
                </div>
                <div class="mini-stat">
                    <div class="k">Status</div>
                    <div class="v {{ ($checkIn?->status === \App\Models\AttendanceRecord::STATUS_PENDING) ? '' : ($checkIn?->is_late ? '' : 'valid') }}">
                        @if(! $checkIn)
                            —
                        @elseif($checkIn->status === \App\Models\AttendanceRecord::STATUS_PENDING)
                            Menunggu validasi
                        @else
                            {{ $checkIn->is_late ? 'Telat' : 'Valid' }}
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @foreach([
            ['label' => 'Masuk', 'record' => $checkIn],
            ['label' => 'Pulang', 'record' => $checkOut],
        ] as $block)
            @php $rec = $block['record']; @endphp
            <div class="dual-col">
                <div class="colhead">
                    <span class="cap">{{ $block['label'] }}</span>
                    @if($rec?->status === \App\Models\AttendanceRecord::STATUS_PENDING)
                    <span class="pill-late"><span class="d"></span>Menunggu validasi</span>
                    @elseif($rec?->status === \App\Models\AttendanceRecord::STATUS_REJECTED)
                    <span class="pill-late"><span class="d"></span>Ditolak</span>
                    @elseif($block['label'] === 'Masuk' && $rec?->is_late)
                    <span class="pill-late"><span class="d"></span>Telat</span>
                    @elseif($rec)
                    <span class="pill-ok"><span class="d"></span>Tercatat</span>
                    @else
                    <span class="pill-ok"><span class="d"></span>Belum</span>
                    @endif
                </div>
                <div class="dual-split">
                    <div class="photo-frame">
                        @if($rec?->selfie_path)
                            <img src="{{ $rec->selfieUrl() }}" alt="Selfie {{ strtolower($block['label']) }}">
                            <div class="photo-badge">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
                            </div>
                        @else
                            <div class="ph">Tidak ada foto</div>
                        @endif
                    </div>
                    <div class="meta-list">
                        <div class="meta-item">
                            <div class="mi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
                            <div class="mt"><div class="k">Waktu</div><div class="v">{{ $fmtTime($rec?->checked_at) }}</div></div>
                        </div>
                        @if($block['label'] === 'Masuk' && $rec?->is_late && $rec?->late_reason)
                        <div class="meta-item">
                            <div class="mi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg></div>
                            <div class="mt"><div class="k">Alasan Telat</div><div class="v">{{ $rec->late_reason }}</div></div>
                        </div>
                        @endif
                        <div class="meta-item">
                            <div class="mi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-5.2-7-11a7 7 0 0 1 14 0c0 5.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg></div>
                            <div class="mt"><div class="k">Lokasi</div><div class="v">{{ $rec?->location?->displayName() ?? '—' }}</div></div>
                        </div>
                        <div class="meta-item">
                            <div class="mi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/></svg></div>
                            <div class="mt"><div class="k">Jarak Titik</div><div class="v">{{ $rec && $rec->distance_meters !== null ? round($rec->distance_meters).' m' : '—' }}</div></div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

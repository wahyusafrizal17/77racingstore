@extends($layout)

@section('title', 'Pengajuan Izin')

@section('content')
@include('partials.izin.portal-styles')
@include('partials.izin.data-table-styles')

<div class="izin-page">
    <div class="hero-card">
        <div class="eyebrow">HR · Time Off</div>
        <h1 class="title">Pengajuan Izin</h1>
        <p class="sub">Riwayat pengajuan izin sesuai jenis yang tersedia di divisi Anda.</p>
    </div>

    @if(! $hasValidator)
    <div class="flash error" style="margin-bottom: 16px">
        Pejabat penyetuju izin belum lengkap di sistem (Head/Manager). Hubungi Head HR agar akun Head & Manager divisi Anda tersedia.
        @if($approverSummary !== [])
        <ul style="margin-top:8px; list-style:disc; padding-left:18px">
            @foreach($approverSummary as $line)
            <li>{{ $line }}</li>
            @endforeach
        </ul>
        @endif
    </div>
    @endif

    @if(session('success'))
    <div class="flash success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="flash error">{{ session('error') }}</div>
    @endif

    <div class="stat-grid">
        <div class="stat-card pending">
            <div class="k">Menunggu</div>
            <div class="v">{{ number_format($stats['pending']) }}</div>
        </div>
        <div class="stat-card approved">
            <div class="k">Disetujui</div>
            <div class="v">{{ number_format($stats['approved']) }}</div>
        </div>
        <div class="stat-card rejected">
            <div class="k">Ditolak</div>
            <div class="v">{{ number_format($stats['rejected']) }}</div>
        </div>
    </div>

    <section class="panel">
        <div class="panel-head panel-head-row">
            <div class="panel-head-text">
                <h2>Riwayat Pengajuan</h2>
                <p>{{ $user->name }} · {{ $user->role }}</p>
            </div>
            @if($hasValidator)
            <button type="button" id="izinOpenCreateBtn" class="btn-detail btn-panel-action">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                Buat Pengajuan
            </button>
            @else
            <span class="btn-detail btn-panel-action" style="opacity:.55; cursor:not-allowed; box-shadow:none" title="Penyetuju belum lengkap">Buat Pengajuan</span>
            @endif
        </div>

        @if($requests->isEmpty())
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p>Belum ada pengajuan izin.</p>
        </div>
        @else
        <div class="validasi-table-wrap">
            <table class="validasi-table">
                <thead>
                    <tr>
                        <th>Jenis</th>
                        <th>Periode</th>
                        <th>Alasan</th>
                        <th>Status</th>
                        <th>Diajukan</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $request)
                    <tr>
                        <td>
                            <div class="cell-name">
                                <span style="margin-right:4px">{{ $request->typeIcon() }}</span>
                                {{ $request->typeLabel() }}
                            </div>
                        </td>
                        <td style="white-space:nowrap">{{ $request->dateRangeLabel() }}</td>
                        <td class="reason-cell" title="{{ $request->reason }}">{{ $request->reason }}</td>
                        <td>
                            <span class="status-pill {{ $request->statusCssClass() }}" style="display:inline-flex">
                                <span class="dot"></span>{{ $request->isPending() ? $request->pendingStageLabel() : $request->statusLabel() }}
                            </span>
                        </td>
                        <td style="white-space:nowrap">{{ $request->created_at->translatedFormat('d M Y') }}</td>
                        <td style="text-align:right">
                            <a href="{{ route('izin.show', $request) }}" class="btn-detail btn-detail-ghost">Detail</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if($requests->hasPages())
        <div style="padding: 16px 22px; border-top: 1px solid var(--line-soft)">{{ $requests->links() }}</div>
        @endif
    </section>
</div>

@if($hasValidator)
<dialog id="izinCreateDialog" class="izin-create-dialog">
    <div class="izin-modal-shell">
        <div class="izin-modal-sheet-handle" aria-hidden="true"></div>
        <header class="izin-modal-hero">
            <div class="izin-modal-hero-top">
                <div class="izin-modal-hero-main">
                    <div class="izin-modal-icon" aria-hidden="true">📋</div>
                    <div>
                        <p class="izin-modal-eyebrow">Pengajuan baru</p>
                        <h3>Buat Pengajuan Izin</h3>
                    </div>
                </div>
                <button type="button" class="izin-modal-close" aria-label="Tutup" onclick="this.closest('dialog')?.close()">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </header>
        <form method="POST" action="{{ route('izin.store') }}" id="izinCreateForm" class="izin-modal-form" enctype="multipart/form-data">
            @csrf
            <div class="izin-modal-body">
                @if($errors->any())
                <div class="izin-modal-alert">
                    Periksa kembali isian berikut:
                    <ul>
                        @foreach($errors->all() as $message)
                        <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="field">
                    <label class="field-label">Jenis pengajuan</label>
                    <div class="type-pills {{ count($typeOptions) === 7 ? 'is-rows-3-2-2' : (count($typeOptions) === 6 ? 'is-rows-2-2-2' : '') }}">
                        @php $defaultType = old('type', array_key_first($typeOptions)); @endphp
                        @foreach($typeOptions as $value => $label)
                        @php
                            $icon = match ($value) {
                                'izin' => '📋',
                                'sakit' => '🤒',
                                'cuti' => '🏖️',
                                'wfh' => '🏠',
                                'masuk_libur' => '🏢',
                                'setengah_masuk', 'setengah_pulang' => '🕛',
                                default => '📋',
                            };
                        @endphp
                        <label class="type-pill">
                            <input type="radio" name="type" value="{{ $value }}" @checked($defaultType === $value) required class="izin-type-radio">
                            <span><em>{{ $icon }}</em>{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="field">
                    <label class="field-label" id="izinDateFieldLabel">Tanggal</label>
                    <div id="izinLiburPanel" class="izin-libur-panel" hidden>
                        <p class="izin-libur-panel-title">Jadwal libur Anda (pilih tanggal yang ditandai)</p>
                        <ul id="izinLiburList" class="izin-libur-list"></ul>
                    </div>
                    <div id="izinWfhPanel" class="izin-libur-panel" hidden>
                        <p class="izin-libur-panel-title">Catatan WFH</p>
                        <ul class="izin-libur-list">
                            <li>WFH tetap wajib absen masuk &amp; pulang.</li>
                            <li>Saat WFH disetujui, absen boleh dilakukan di luar radius kantor.</li>
                        </ul>
                    </div>
                    <div class="date-row" id="izinDateRow">
                        <div class="izin-date-wrap">
                            <input type="text" id="izinStartPicker" class="field-input izin-date-picker" placeholder="Pilih tanggal" autocomplete="off" aria-label="Tanggal">
                            <input type="hidden" name="start_date" id="izinStartHidden" value="{{ old('start_date') }}">
                        </div>
                        <div id="izinEndCol" class="izin-date-wrap">
                            <input type="text" id="izinEndPicker" class="field-input izin-date-picker" placeholder="Tanggal selesai" autocomplete="off" aria-label="Tanggal selesai">
                            <input type="hidden" name="end_date" id="izinEndHidden" value="{{ old('end_date') }}">
                        </div>
                    </div>
                </div>

                <div class="field" id="izinSuratDokterField" @if($defaultType !== 'sakit') hidden @endif>
                    <label class="field-label" for="suratDokter">
                        Surat keterangan dokter <span id="suratDokterRequiredMark" style="color:var(--red,#dc2626)" @if(! \App\Models\LeaveRequest::requiresSuratDokter($defaultType, old('start_date'), old('end_date'))) hidden @endif>*</span>
                    </label>
                    <label class="izin-upload-drop" for="suratDokter">
                        <span class="izin-upload-drop__icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 12V4m0 0l-3.5 3.5M12 4l3.5 3.5"/></svg>
                        </span>
                        <span class="izin-upload-drop__copy">
                            <span class="izin-upload-drop__title">Pilih file surat dokter</span>
                            <span class="izin-upload-drop__meta" id="suratDokterMeta">Opsional jika 1 hari · wajib jika lebih dari 1 hari · JPG, PNG, WEBP, PDF · max 5MB</span>
                            <span class="izin-upload-drop__name" id="suratDokterName" hidden></span>
                        </span>
                        <input type="file" id="suratDokter" name="surat_dokter" accept=".jpg,.jpeg,.png,.webp,.pdf,image/*,application/pdf" class="izin-upload-drop__input" @if(\App\Models\LeaveRequest::requiresSuratDokter($defaultType, old('start_date'), old('end_date'))) required @endif>
                    </label>
                    @error('surat_dokter')
                    <p class="field-error" style="margin-top:6px;font-size:12px;color:#b91c1c">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label class="field-label" for="leaveReason">Alasan</label>
                    <textarea id="leaveReason" name="reason" maxlength="1000" required placeholder="Jelaskan alasan pengajuan…" class="field-textarea">{{ old('reason') }}</textarea>
                </div>
            </div>
            <footer class="izin-modal-footer">
                <button type="button" class="btn-modal-cancel" onclick="this.closest('dialog')?.close()">Batal</button>
                <button type="submit" class="btn-modal-submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Kirim Pengajuan
                </button>
            </footer>
        </form>
    </div>
</dialog>
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
<style>
    .izin-upload-drop {
        position: relative;
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 72px;
        padding: 12px 14px;
        border: 1.5px dashed #f2b8b4;
        border-radius: 12px;
        background: linear-gradient(180deg, #fffafa 0%, #fff5f5 100%);
        cursor: pointer;
        transition: border-color .15s, background .15s, box-shadow .15s;
    }
    .izin-upload-drop:hover,
    .izin-upload-drop:focus-within {
        border-color: rgba(213, 36, 28, .55);
        background: #fff7f6;
        box-shadow: 0 0 0 3px rgba(213, 36, 28, .08);
    }
    .izin-upload-drop.is-filled {
        border-style: solid;
        border-color: #86efac;
        background: #f0fdf4;
    }
    .izin-upload-drop__icon {
        flex-shrink: 0;
        display: grid;
        place-items: center;
        width: 40px;
        height: 40px;
        border-radius: 11px;
        background: #fff;
        border: 1px solid #e7e5e4;
        color: #b91c1c;
    }
    .izin-upload-drop__icon svg { width: 18px; height: 18px; }
    .izin-upload-drop__copy { min-width: 0; flex: 1; }
    .izin-upload-drop__title {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: var(--ink, #1c1917);
        line-height: 1.3;
    }
    .izin-upload-drop__meta {
        display: block;
        margin-top: 3px;
        font-size: 11px;
        color: var(--ink-faint, #78716c);
        line-height: 1.4;
    }
    .izin-upload-drop__name {
        display: block;
        margin-top: 3px;
        font-size: 11.5px;
        font-weight: 600;
        color: #166534;
    }
    .izin-upload-drop.is-filled .izin-upload-drop__meta { display: none; }
    .izin-upload-drop__input {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        font-size: 0;
    }
    .izin-create-dialog .izin-date-wrap {
        position: relative;
        z-index: 2;
    }
    .izin-create-dialog.izin-picker-open .izin-modal-body {
        overflow: visible;
        max-height: none;
    }
    .izin-create-dialog.izin-picker-open .izin-modal-footer {
        z-index: 0;
    }
    .izin-create-dialog.izin-picker-open .izin-date-wrap {
        z-index: 40;
    }
    /* static flatpickr: tempel tepat di bawah input dalam modal */
    .izin-create-dialog .izin-date-wrap .flatpickr-wrapper {
        display: block;
        width: 100%;
        position: relative;
    }
    .izin-create-dialog .izin-date-wrap .flatpickr-calendar {
        position: absolute !important;
        top: calc(100% + 6px) !important;
        left: 0 !important;
        right: auto !important;
        margin: 0 !important;
        box-shadow: 0 12px 40px rgba(15, 23, 42, 0.18);
        border-radius: 12px;
        border: 1px solid var(--line-soft, #e2e8f0);
        z-index: 50 !important;
    }
    .izin-create-dialog .izin-date-wrap .flatpickr-calendar.arrowTop,
    .izin-create-dialog .izin-date-wrap .flatpickr-calendar.arrowBottom {
        margin-top: 0 !important;
    }
    .izin-create-dialog .izin-modal-footer {
        position: relative;
        z-index: 1;
    }
    .izin-create-dialog .flatpickr-day.day-libur:not(.flatpickr-disabled) {
        background: #fef3c7;
        border-color: #fcd34d;
        font-weight: 600;
    }
    .izin-create-dialog .flatpickr-day.day-libur:not(.flatpickr-disabled):hover {
        background: #fde68a;
    }
    .izin-create-dialog .flatpickr-day.selected.day-libur,
    .izin-create-dialog .flatpickr-day.startRange.day-libur,
    .izin-create-dialog .flatpickr-day.endRange.day-libur {
        background: #dc2626;
        border-color: #dc2626;
        color: #fff;
    }
    .izin-libur-panel {
        margin-bottom: 12px;
        padding: 12px 14px;
        border-radius: 10px;
        background: #fffbeb;
        border: 1px solid #fde68a;
    }
    .izin-libur-panel-title {
        margin: 0 0 8px;
        font-size: 12px;
        font-weight: 600;
        color: #92400e;
    }
    .izin-libur-list {
        margin: 0;
        padding: 0;
        list-style: none;
        max-height: 120px;
        overflow-y: auto;
        font-size: 12px;
        line-height: 1.5;
        color: #78350f;
    }
    .izin-libur-list li {
        padding: 2px 0;
    }
    .izin-libur-list li::before {
        content: '•';
        color: #f59e0b;
        margin-right: 6px;
    }
    .izin-date-picker {
        cursor: pointer;
        background-color: #fff;
    }
    @media (max-width: 640px) {
        .izin-create-dialog.izin-picker-open .izin-modal-body {
            overflow-x: hidden;
            overflow-y: auto;
            max-height: min(70vh, 560px);
            padding-bottom: 12px;
        }
        .izin-create-dialog .izin-date-wrap .flatpickr-calendar {
            position: relative !important;
            top: 8px !important;
            left: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.12);
            z-index: 5;
        }
        .izin-create-dialog .izin-date-wrap .flatpickr-calendar.open {
            display: block !important;
        }
    }
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script>
(function () {
    var cfg = @json($leaveDatePicker);
    var offDayMap = {};
    (cfg.offDays || []).forEach(function (row) {
        offDayMap[row.date] = row.label || 'Libur';
    });
    var offDates = Object.keys(offDayMap);

    var startEl = document.getElementById('izinStartPicker');
    var endEl = document.getElementById('izinEndPicker');
    var startHidden = document.getElementById('izinStartHidden');
    var endHidden = document.getElementById('izinEndHidden');
    var endCol = document.getElementById('izinEndCol');
    var liburPanel = document.getElementById('izinLiburPanel');
    var wfhPanel = document.getElementById('izinWfhPanel');
    var liburList = document.getElementById('izinLiburList');
    var typeRadios = document.querySelectorAll('.izin-type-radio');
    var dateFieldLabel = document.getElementById('izinDateFieldLabel');
    var dateRow = document.getElementById('izinDateRow');
    var fpStart = null;
    var fpEnd = null;
    var dlg = document.getElementById('izinCreateDialog');

    function isMobileViewport() {
        return window.matchMedia('(max-width: 640px)').matches;
    }

    function setPickerOpen(open) {
        if (!dlg) return;
        dlg.classList.toggle('izin-picker-open', !!open);
        // Di HP: scroll ke input tanggal agar kalender static terlihat
        if (open && isMobileViewport() && startEl) {
            window.setTimeout(function () {
                try {
                    startEl.scrollIntoView({ block: 'center', behavior: 'smooth' });
                } catch (e) {}
            }, 50);
        }
    }

    function flatpickrBaseOptions() {
        // Selalu pakai kalender flatpickr (bukan native HP).
        // Native + disable hari libur (sakit/cuti) membuat tanggal tidak muncul di mobile.
        return {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd M Y',
            disableMobile: true,
            clickOpens: true,
            static: true,
            locale: { firstDayOfWeek: 1 },
            onDayCreate: onDayCreate,
            onOpen: function () {
                setPickerOpen(true);
            },
            onClose: function () {
                setTimeout(function () {
                    var anyOpen = (fpStart && fpStart.isOpen) || (fpEnd && fpEnd.isOpen);
                    setPickerOpen(anyOpen);
                }, 0);
            },
        };
    }

    function runInitPickers() {
        if (!window.flatpickr) {
            return;
        }
        buildPickers();
    }

    function selectedType() {
        var checked = document.querySelector('.izin-type-radio:checked');
        return checked ? checked.value : 'cuti';
    }

    function isHalfDay(type) {
        return (cfg.halfDayTypes || []).indexOf(type) >= 0;
    }

    function isSingleDateType(type) {
        return isHalfDay(type) || type === cfg.masukLiburType || type === cfg.wfhType;
    }

    function syncSingleDateToEnd() {
        if (startHidden && endHidden && startHidden.value) {
            endHidden.value = startHidden.value;
        }
    }

    function updateDateFieldUi(type) {
        var single = isSingleDateType(type);
        var masukLibur = type === cfg.masukLiburType;
        var wfh = type === cfg.wfhType;

        if (dateFieldLabel) {
            if (masukLibur) {
                dateFieldLabel.textContent = 'Tanggal libur';
            } else if (wfh) {
                dateFieldLabel.textContent = 'Tanggal WFH';
            } else if (single) {
                dateFieldLabel.textContent = 'Tanggal';
            } else {
                dateFieldLabel.textContent = 'Rentang tanggal';
            }
        }

        if (dateRow) {
            dateRow.classList.toggle('is-single', single);
        }

        if (endCol) {
            endCol.style.display = single ? 'none' : '';
        }

        if (startEl) {
            if (masukLibur) {
                startEl.placeholder = 'Pilih tanggal libur';
                startEl.setAttribute('aria-label', 'Tanggal libur');
            } else if (wfh) {
                startEl.placeholder = 'Pilih tanggal WFH';
                startEl.setAttribute('aria-label', 'Tanggal WFH');
            } else if (single) {
                startEl.placeholder = 'Pilih tanggal';
                startEl.setAttribute('aria-label', 'Tanggal');
            } else {
                startEl.placeholder = 'Tanggal mulai';
                startEl.setAttribute('aria-label', 'Tanggal mulai');
            }
        }
    }

    function minDateForType(type) {
        var days = (cfg.minAdvance && cfg.minAdvance[type] != null) ? cfg.minAdvance[type] : 3;
        var d = new Date();
        d.setHours(0, 0, 0, 0);
        d.setDate(d.getDate() + days);
        return d;
    }

    function formatYmd(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    function parseYmd(str) {
        if (!str) return null;
        var p = str.split('-');
        if (p.length !== 3) return null;
        return new Date(Number(p[0]), Number(p[1]) - 1, Number(p[2]));
    }

    function formatDisplay(d) {
        try {
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        } catch (e) {
            return formatYmd(d);
        }
    }

    function destroyPickers() {
        if (fpStart) { fpStart.destroy(); fpStart = null; }
        if (fpEnd) { fpEnd.destroy(); fpEnd = null; }
        setPickerOpen(false);
    }

    function onDayCreate(_, __, ___, dayElem) {
        var dateStr = formatYmd(dayElem.dateObj);
        if (offDayMap[dateStr]) {
            dayElem.classList.add('day-libur');
            dayElem.title = offDayMap[dateStr];
        }
    }

    function syncHidden(fp, hidden) {
        if (!fp || !hidden) return;
        var sel = fp.selectedDates;
        hidden.value = sel.length ? formatYmd(sel[0]) : '';
    }

    function renderLiburList() {
        if (!liburList) return;
        liburList.innerHTML = '';
        var upcoming = (cfg.offDays || []).filter(function (row) {
            var d = parseYmd(row.date);
            return d && d >= minDateForType(cfg.masukLiburType);
        }).slice(0, 12);
        if (upcoming.length === 0) {
            var li = document.createElement('li');
            li.textContent = 'Belum ada jadwal libur tercatat untuk Anda. Hubungi Head HR jika seharusnya ada.';
            liburList.appendChild(li);
            return;
        }
        upcoming.forEach(function (row) {
            var li = document.createElement('li');
            var d = parseYmd(row.date);
            li.textContent = (d ? formatDisplay(d) : row.date) + ' — ' + (row.label || 'Libur');
            liburList.appendChild(li);
        });
    }

    function updateHints(type) {
        if (liburPanel) liburPanel.hidden = type !== cfg.masukLiburType;
        if (wfhPanel) wfhPanel.hidden = type !== cfg.wfhType;
        if (type === cfg.masukLiburType) {
            renderLiburList();
        }
    }

    function enabledOffDates(type) {
        var minD = minDateForType(type);
        return offDates.filter(function (d) {
            var parsed = parseYmd(d);
            return parsed && parsed >= minD;
        });
    }

    function buildPickers() {
        if (!startEl || !window.flatpickr) return;
        destroyPickers();

        var type = selectedType();
        updateHints(type);
        updateDateFieldUi(type);
        var minD = minDateForType(type);
        var single = isSingleDateType(type);
        var masukLibur = type === cfg.masukLiburType;
        var wfh = type === cfg.wfhType;
        var blockOffDays = wfh || type === 'sakit' || type === 'cuti' || isHalfDay(type);
        var selectableOff = enabledOffDates(cfg.masukLiburType);

        var common = Object.assign({}, flatpickrBaseOptions(), {
            minDate: minD,
        });

        if (masukLibur && selectableOff.length) {
            common.enable = selectableOff;
        }

        if (blockOffDays && offDates.length) {
            common.disable = offDates.slice();
            if (startHidden.value && offDayMap[startHidden.value]) {
                startHidden.value = '';
            }
            if (endHidden.value && offDayMap[endHidden.value]) {
                endHidden.value = '';
            }
        }

        fpStart = flatpickr(startEl, Object.assign({}, common, {
            defaultDate: startHidden.value || null,
            onChange: function (sel) {
                syncHidden(fpStart, startHidden);
                if (single) {
                    syncSingleDateToEnd();
                } else if (fpEnd && sel.length && fpEnd.selectedDates.length && fpEnd.selectedDates[0] < sel[0]) {
                    fpEnd.clear();
                    endHidden.value = '';
                }
                syncSuratDokterField();
            },
        }));

        if (single) {
            syncSingleDateToEnd();
            return;
        }

        var endCommon = Object.assign({}, common);

        fpEnd = flatpickr(endEl, Object.assign({}, endCommon, {
            defaultDate: endHidden.value || null,
            onChange: function (sel) {
                syncHidden(fpEnd, endHidden);
                syncSuratDokterField();
            },
        }));

        syncHidden(fpStart, startHidden);
        syncHidden(fpEnd, endHidden);
        syncSuratDokterField();
    }

    var suratField = document.getElementById('izinSuratDokterField');
    var suratInput = document.getElementById('suratDokter');
    var suratName = document.getElementById('suratDokterName');
    var suratDrop = suratField ? suratField.querySelector('.izin-upload-drop') : null;

    function syncSuratDokterField() {
        var isSakit = selectedType() === 'sakit';
        var required = isSakit && startHidden.value && endHidden.value && endHidden.value > startHidden.value;
        var mark = document.getElementById('suratDokterRequiredMark');
        var meta = document.getElementById('suratDokterMeta');
        if (suratField) suratField.hidden = !isSakit;
        if (mark) mark.hidden = !required;
        if (meta) {
            meta.textContent = required
                ? 'Wajib untuk sakit lebih dari 1 hari · JPG, PNG, WEBP, PDF · max 5MB'
                : 'Opsional jika 1 hari · wajib jika lebih dari 1 hari · JPG, PNG, WEBP, PDF · max 5MB';
        }
        if (suratInput) {
            suratInput.required = required;
            if (!isSakit) {
                suratInput.value = '';
                if (suratName) {
                    suratName.hidden = true;
                    suratName.textContent = '';
                }
                if (suratDrop) suratDrop.classList.remove('is-filled');
            }
        }
    }

    if (suratInput) {
        suratInput.addEventListener('change', function () {
            var file = suratInput.files && suratInput.files[0];
            if (!file) {
                if (suratName) {
                    suratName.hidden = true;
                    suratName.textContent = '';
                }
                if (suratDrop) suratDrop.classList.remove('is-filled');
                return;
            }
            if (suratName) {
                suratName.hidden = false;
                suratName.textContent = file.name;
            }
            if (suratDrop) suratDrop.classList.add('is-filled');
        });
    }

    var form = document.getElementById('izinCreateForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            var type = selectedType();
            if (fpStart) syncHidden(fpStart, startHidden);
            if (fpEnd) syncHidden(fpEnd, endHidden);
            if (isSingleDateType(type)) {
                syncSingleDateToEnd();
            }
            if (!startHidden.value || !endHidden.value) {
                e.preventDefault();
                window.alert('Lengkapi tanggal pengajuan terlebih dahulu.');
                return;
            }
            if (type === 'sakit' && startHidden.value && endHidden.value && endHidden.value > startHidden.value
                && (!suratInput || !suratInput.files || !suratInput.files.length)) {
                e.preventDefault();
                window.alert('Untuk izin sakit lebih dari 1 hari, unggah surat keterangan dokter terlebih dahulu.');
            }
        });
    }

    typeRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            syncSuratDokterField();
            if (dlg && dlg.open) {
                buildPickers();
            }
        });
    });

    function openCreateDialog() {
        if (!dlg) {
            return;
        }
        syncSuratDokterField();
        dlg.showModal();
        window.requestAnimationFrame(function () {
            runInitPickers();
        });
    }

    syncSuratDokterField();

    window.izinOpenCreateDialog = openCreateDialog;

    var openBtn = document.getElementById('izinOpenCreateBtn');
    if (openBtn) {
        openBtn.addEventListener('click', openCreateDialog);
    }

    if (dlg) {
        dlg.addEventListener('close', destroyPickers);
        dlg.addEventListener('toggle', function () {
            if (dlg.open) {
                window.requestAnimationFrame(runInitPickers);
            } else {
                destroyPickers();
            }
        });
        dlg.addEventListener('click', function (event) {
            var rect = dlg.getBoundingClientRect();
            var inDialog = rect.top <= event.clientY && event.clientY <= rect.top + rect.height
                && rect.left <= event.clientX && event.clientX <= rect.left + rect.width;
            if (!inDialog) {
                dlg.close();
            }
        });
    }

    if (dlg && dlg.open) {
        runInitPickers();
    }
})();
</script>
@endpush
@if($errors->any() || old('reason') || old('start_date'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var dlg = document.getElementById('izinCreateDialog');
        if (typeof window.izinOpenCreateDialog === 'function') {
            window.izinOpenCreateDialog();
        } else if (dlg) {
            dlg.showModal();
        }
    });
</script>
@endif
@endif
@endsection

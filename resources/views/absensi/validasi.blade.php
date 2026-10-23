@extends($layout)

@section('title', 'Validasi Absensi')

@section('content')
@include('partials.izin.portal-styles')
@include('partials.izin.data-table-styles')

@php
    $tz = config('app.timezone');
    $fmtTime = fn ($dt) => $dt ? $dt->timezone($tz)->format('H:i') : '—';
    $pendingCount = $pendingCount ?? 0;
    $groups = $groups ?? collect();
    $reviewer = auth()->user();
    $queueSub = ($isOwner ?? false)
        ? 'Semua antrian pending (Owner)'
        : 'Bawahan sesuai Ringkasan Penugasan (admin)';
    if ($pendingCount > 0) {
        $queueSub .= ' · '.$pendingCount.' menunggu';
    } else {
        $queueSub .= ' · tidak ada yang menunggu';
    }

    $mapRecord = function ($record) use ($tz, $fmtTime) {
        if (! $record) {
            return null;
        }

        return [
            'id' => $record->id,
            'type' => $record->type,
            'type_label' => $record->typeLabel(),
            'status' => $record->status,
            'status_label' => $record->statusLabel(),
            'is_pending' => $record->status === \App\Models\AttendanceRecord::STATUS_PENDING,
            'time_label' => $fmtTime($record->checked_at),
            'location' => $record->location?->displayName() ?? '—',
            'is_late' => (bool) $record->is_late,
            'late_reason' => $record->late_reason,
            'selfie_url' => $record->selfie_path ? $record->selfieUrl() : null,
        ];
    };

    $groupPayload = collect($groups->items())->map(function (array $group) use ($tz, $mapRecord) {
        $employee = $group['user'] ?? null;
        $date = \Carbon\Carbon::parse($group['date'], $tz);

        return [
            'key' => $group['key'],
            'employee_name' => $employee?->name ?? '—',
            'employee_meta' => trim(($employee?->section ?? '').' · '.($employee?->role ?? ''), ' ·'),
            'profile_url' => $employee?->fotoProfilUrl(),
            'date_label' => $date->translatedFormat('l, d F Y'),
            'date_short' => $date->translatedFormat('d M Y'),
            'pending_count' => (int) ($group['pending_count'] ?? 0),
            'check_in' => $mapRecord($group['check_in'] ?? null),
            'check_out' => $mapRecord($group['check_out'] ?? null),
        ];
    })->values();
@endphp

@push('styles')
<style>
.absensi-validasi-dialog {
    border: none;
    padding: 0;
    margin: auto;
    max-width: min(720px, calc(100vw - 28px));
    width: 100%;
    background: transparent;
    overflow: visible;
}
.absensi-validasi-dialog::backdrop {
    background: rgba(15, 23, 42, 0.52);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
.absensi-validasi-dialog .modal-shell {
    border-radius: 22px;
    border: 1px solid rgba(255, 255, 255, 0.65);
    background: #fff;
    box-shadow:
        0 0 0 1px rgba(15, 23, 42, 0.04),
        0 24px 48px rgba(15, 23, 42, 0.14);
    overflow: hidden;
    max-height: min(90vh, 900px);
    display: flex;
    flex-direction: column;
}
.absensi-validasi-dialog .modal-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 20px 22px 16px;
    border-bottom: 1px solid #f1efea;
    background: linear-gradient(180deg, #fafafa 0%, #fff 100%);
    flex-shrink: 0;
}
.absensi-validasi-dialog .modal-eyebrow {
    margin: 0;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: #d5241c;
}
.absensi-validasi-dialog .modal-head h3 {
    margin: 4px 0 0;
    font-size: 20px;
    font-weight: 700;
    color: #181819;
    line-height: 1.2;
}
.absensi-validasi-dialog .modal-sub {
    margin: 6px 0 0;
    font-size: 13px;
    color: #6e7078;
}
.absensi-validasi-dialog .modal-close {
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    border: 1px solid #e9e7e2;
    border-radius: 12px;
    background: #fff;
    color: #64748b;
    cursor: pointer;
    flex-shrink: 0;
}
.absensi-validasi-dialog .modal-close:hover {
    background: #f8fafc;
    color: #0f172a;
}
.absensi-validasi-dialog .modal-body {
    padding: 18px 22px 22px;
    overflow-y: auto;
}
.absensi-validasi-dialog .profile-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
}
.absensi-validasi-dialog .profile-avatar {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    object-fit: cover;
    border: 1px solid #e9e7e2;
    background: #f3f4f6;
}
.absensi-validasi-dialog .profile-avatar.is-empty {
    display: grid;
    place-items: center;
    color: #a6a8ae;
    font-size: 11px;
    font-weight: 700;
}
.absensi-validasi-dialog .slot {
    border: 1px solid #e9e7e2;
    border-radius: 16px;
    padding: 14px;
    background: #fafafa;
}
.absensi-validasi-dialog .slot + .slot {
    margin-top: 12px;
}
.absensi-validasi-dialog .slot-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 12px;
}
.absensi-validasi-dialog .slot-title {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    color: #181819;
}
.absensi-validasi-dialog .slot-meta {
    margin: 2px 0 0;
    font-size: 12px;
    color: #6e7078;
}
.absensi-validasi-dialog .photo-compare {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
@media (max-width: 560px) {
    .absensi-validasi-dialog .photo-compare { grid-template-columns: 1fr; }
}
.absensi-validasi-dialog .photo-box {
    border: 1px solid #e9e7e2;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
}
.absensi-validasi-dialog .photo-box .cap {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #a6a8ae;
    padding: 8px 10px 6px;
}
.absensi-validasi-dialog .photo-box img {
    width: 100%;
    aspect-ratio: 3 / 4;
    object-fit: cover;
    display: block;
    background: #f3f4f6;
}
.absensi-validasi-dialog .photo-empty {
    aspect-ratio: 3 / 4;
    display: grid;
    place-items: center;
    padding: 16px;
    text-align: center;
    color: #a6a8ae;
    font-size: 12px;
    background: #fff;
}
.absensi-validasi-dialog .late-note {
    margin-top: 10px;
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px dashed #fcd34d;
    background: #fffbeb;
    font-size: 12px;
    color: #92400e;
    line-height: 1.45;
}
.absensi-validasi-dialog .slot-empty {
    padding: 18px 12px;
    text-align: center;
    font-size: 13px;
    color: #6e7078;
    background: #fff;
    border-radius: 12px;
    border: 1px dashed #e9e7e2;
}
.absensi-validasi-dialog .day-actions {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #f1efea;
}
.absensi-validasi-dialog .day-actions-btns {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.absensi-validasi-dialog .day-actions-btns form {
    flex: 1;
    display: flex;
    min-width: 140px;
}
.absensi-validasi-dialog .day-actions-btns .btn-approve,
.absensi-validasi-dialog .day-actions-btns .btn-reject {
    flex: 1;
    min-width: 140px;
}
.absensi-validasi-dialog .day-actions-btns .btn-approve {
    width: 100%;
}
.absensi-validasi-dialog .day-reject-reason {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}
.absensi-validasi-dialog .reject-input {
    flex: 1;
    min-width: 160px;
    height: 42px;
    border: 1px solid #e9e7e2;
    border-radius: 12px;
    padding: 0 14px;
    font-size: 13px;
    font-family: inherit;
    background: #fff;
}
/* Dialog di luar .izin-page — style tombol harus di-scope di sini */
.absensi-validasi-dialog .btn-approve {
    height: 42px;
    padding: 0 18px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #1D8A5C, #157A4F);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    box-shadow: 0 6px 18px rgba(29, 138, 92, 0.25);
}
.absensi-validasi-dialog .btn-approve:hover {
    filter: brightness(1.05);
}
.absensi-validasi-dialog .btn-reject {
    height: 42px;
    padding: 0 18px;
    border-radius: 12px;
    border: 1px solid #FECACA;
    background: #FEF2F2;
    color: #991B1B;
    font-size: 13px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
}
.absensi-validasi-dialog .btn-reject:hover {
    background: #FEE2E2;
}
.absensi-validasi .jenis-stack {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
}
</style>
@endpush

<div class="izin-page absensi-validasi">
    <div class="hero-card">
        <div class="eyebrow">HR · Attendance</div>
        <h1 class="title">Validasi Absensi</h1>
        <p class="sub">Hanya absensi berstatus <strong>Pending (P)</strong> yang masuk antrian. Status <strong>H/T</strong> di rekap berarti sudah divalidasi. Klik <strong>Detail</strong> untuk setujui atau tolak.</p>
    </div>

    @if(session('success'))
    <div class="flash success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="flash error">{{ session('error') }}</div>
    @endif

    <section class="panel">
        <div class="panel-head">
            <div class="panel-head-text">
                <h2>Antrian Verifikasi Selfie</h2>
                <p>{{ $queueSub }}</p>
            </div>
        </div>

        @if($groups->isEmpty())
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <p>Tidak ada absensi yang menunggu validasi.</p>
        </div>
        @else
        <div class="validasi-table-wrap">
            <table class="validasi-table">
                <thead>
                    <tr>
                        <th>Karyawan</th>
                        <th>Tanggal</th>
                        <th>Absensi</th>
                        <th>Menunggu</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groups as $group)
                    @php
                        $employee = $group['user'] ?? null;
                        $checkIn = $group['check_in'] ?? null;
                        $checkOut = $group['check_out'] ?? null;
                        $date = \Carbon\Carbon::parse($group['date'], $tz);
                    @endphp
                    <tr>
                        <td>
                            <div class="cell-name">{{ $employee?->name }}</div>
                            <div class="cell-meta">{{ $employee?->section }} · {{ $employee?->role }}</div>
                        </td>
                        <td style="white-space:nowrap">{{ $date->translatedFormat('d M Y') }}</td>
                        <td>
                            <div class="jenis-stack">
                                <span>Masuk: {{ $checkIn ? $fmtTime($checkIn->checked_at) : '—' }}</span>
                                <span>Pulang: {{ $checkOut ? $fmtTime($checkOut->checked_at) : '—' }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="status-pill pending" style="display:inline-flex">
                                <span class="dot"></span>{{ (int) ($group['pending_count'] ?? 0) }} foto
                            </span>
                        </td>
                        <td style="text-align:right">
                            <button
                                type="button"
                                class="btn-detail"
                                data-absensi-group="{{ $group['key'] }}"
                            >Detail</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if($groups->hasPages())
        <div style="padding: 16px 22px; border-top: 1px solid var(--line-soft)">{{ $groups->links() }}</div>
        @endif
    </section>
</div>

<dialog id="absensiValidasiDialog" class="absensi-validasi-dialog">
    <div class="modal-shell">
        <div class="modal-head">
            <div>
                <p class="modal-eyebrow">Validasi foto</p>
                <h3 id="avModalTitle">Detail Absensi</h3>
                <p class="modal-sub" id="avModalSub"></p>
            </div>
            <button type="button" class="modal-close" id="avModalClose" aria-label="Tutup">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        <div class="modal-body">
            <div class="profile-row">
                <img class="profile-avatar" id="avProfileImg" src="" alt="Foto profil" hidden>
                <div class="profile-avatar is-empty" id="avProfileEmpty">N/A</div>
                <div>
                    <div class="cell-name" id="avEmployeeName" style="font-size:15px;font-weight:700">—</div>
                    <div class="cell-meta" id="avEmployeeMeta">—</div>
                </div>
            </div>

            <div class="slot" data-slot="check_in">
                <div class="slot-head">
                    <div>
                        <p class="slot-title">Absen Masuk</p>
                        <p class="slot-meta" data-slot-meta>—</p>
                    </div>
                    <span class="status-pill pending" data-slot-status style="display:inline-flex">
                        <span class="dot"></span><span data-slot-status-text>Menunggu</span>
                    </span>
                </div>
                <div data-slot-content></div>
            </div>

            <div class="slot" data-slot="check_out">
                <div class="slot-head">
                    <div>
                        <p class="slot-title">Absen Pulang</p>
                        <p class="slot-meta" data-slot-meta>—</p>
                    </div>
                    <span class="status-pill pending" data-slot-status style="display:inline-flex">
                        <span class="dot"></span><span data-slot-status-text>Menunggu</span>
                    </span>
                </div>
                <div data-slot-content></div>
            </div>

            <div id="avDayActions" class="day-actions" hidden>
                <div class="day-actions-btns">
                    <form id="avApproveDayForm" method="POST" action="{{ route('absensi.validasi.approve-hari') }}">
                        @csrf
                        <div id="avApproveDayIds"></div>
                        <button type="submit" class="btn-approve" id="avApproveDayBtn">Setujui</button>
                    </form>
                    <button type="button" class="btn-reject" id="avRejectToggle">Tolak</button>
                </div>
                <form id="avRejectDayForm" class="day-reject-reason" method="POST" action="{{ route('absensi.validasi.reject-hari') }}" hidden>
                    @csrf
                    <div id="avRejectDayIds"></div>
                    <input type="text" name="rejection_reason" id="avRejectReason" required maxlength="500" placeholder="Alasan penolakan…" class="reject-input">
                    <button type="submit" class="btn-reject" id="avRejectDayBtn">Kirim penolakan</button>
                </form>
            </div>
        </div>
    </div>
</dialog>

<template id="avSlotDoneTpl">
    <div class="photo-compare">
        <div class="photo-box">
            <div class="cap">Foto profil</div>
            <img data-profile alt="Foto profil">
        </div>
        <div class="photo-box">
            <div class="cap">Selfie absensi</div>
            <a data-selfie-link href="#" target="_blank" rel="noopener">
                <img data-selfie alt="Selfie absensi">
            </a>
            <div class="photo-empty" data-selfie-empty hidden>Tidak ada foto</div>
        </div>
    </div>
    <div class="late-note" data-late hidden></div>
</template>

<template id="avSlotEmptyTpl">
    <div class="slot-empty">Belum ada absensi untuk slot ini.</div>
</template>
@endsection

@push('scripts')
<script>
(function () {
    var groups = @json($groupPayload);
    var byKey = {};
    groups.forEach(function (row) { byKey[String(row.key)] = row; });

    var dlg = document.getElementById('absensiValidasiDialog');
    if (!dlg) return;

    var doneTpl = document.getElementById('avSlotDoneTpl');
    var emptyTpl = document.getElementById('avSlotEmptyTpl');

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function fillPhotos(root, profileUrl, selfieUrl) {
        var profileImg = root.querySelector('[data-profile]');
        if (profileImg) {
            if (profileUrl) {
                profileImg.src = profileUrl;
                profileImg.hidden = false;
            } else {
                profileImg.hidden = true;
                profileImg.removeAttribute('src');
            }
        }

        var selfieLink = root.querySelector('[data-selfie-link]');
        var selfieImg = root.querySelector('[data-selfie]');
        var selfieEmpty = root.querySelector('[data-selfie-empty]');
        if (selfieUrl) {
            if (selfieLink) {
                selfieLink.href = selfieUrl;
                selfieLink.hidden = false;
            }
            if (selfieImg) {
                selfieImg.src = selfieUrl;
                selfieImg.hidden = false;
            }
            if (selfieEmpty) selfieEmpty.hidden = true;
        } else {
            if (selfieLink) selfieLink.hidden = true;
            if (selfieImg) {
                selfieImg.hidden = true;
                selfieImg.removeAttribute('src');
            }
            if (selfieEmpty) selfieEmpty.hidden = false;
        }
    }

    function renderSlot(slotName, record, profileUrl) {
        var slotEl = dlg.querySelector('[data-slot="' + slotName + '"]');
        if (!slotEl) return;

        var metaEl = slotEl.querySelector('[data-slot-meta]');
        var statusEl = slotEl.querySelector('[data-slot-status]');
        var statusText = slotEl.querySelector('[data-slot-status-text]');
        var contentEl = slotEl.querySelector('[data-slot-content]');
        contentEl.innerHTML = '';

        if (!record) {
            metaEl.textContent = 'Belum absen';
            statusEl.className = 'status-pill';
            statusEl.style.display = 'inline-flex';
            statusEl.style.background = '#f3f4f6';
            statusEl.style.color = '#6b7280';
            statusText.textContent = 'Kosong';
            contentEl.appendChild(emptyTpl.content.cloneNode(true));
            return;
        }

        metaEl.textContent = record.time_label + ' · ' + record.location;

        if (record.is_pending) {
            statusEl.className = 'status-pill pending';
            statusEl.style.display = 'inline-flex';
            statusEl.style.background = '';
            statusEl.style.color = '';
            statusText.textContent = 'Menunggu validasi';
            var node = doneTpl.content.cloneNode(true);
            fillPhotos(node, profileUrl, record.selfie_url);
            var late = node.querySelector('[data-late]');
            if (record.is_late && record.late_reason) {
                late.hidden = false;
                late.innerHTML = '<strong>Alasan telat:</strong> ' + escapeHtml(record.late_reason);
            }
            contentEl.appendChild(node);
            return;
        }

        statusEl.className = 'status-pill';
        statusEl.style.display = 'inline-flex';
        if (record.status === 'valid') {
            statusEl.style.background = '#ecfdf5';
            statusEl.style.color = '#047857';
        } else {
            statusEl.style.background = '#fef2f2';
            statusEl.style.color = '#b91c1c';
        }
        statusText.textContent = record.status_label;

        var done = doneTpl.content.cloneNode(true);
        fillPhotos(done, profileUrl, record.selfie_url);
        var lateDone = done.querySelector('[data-late]');
        if (record.is_late && record.late_reason) {
            lateDone.hidden = false;
            lateDone.innerHTML = '<strong>Alasan telat:</strong> ' + escapeHtml(record.late_reason);
        }
        contentEl.appendChild(done);
    }

    function fillRecordIds(container, pending) {
        if (!container) return;
        container.innerHTML = '';
        pending.forEach(function (record) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'record_ids[]';
            input.value = String(record.id);
            container.appendChild(input);
        });
    }

    function syncDayActions(row) {
        var wrap = document.getElementById('avDayActions');
        var approveBtn = document.getElementById('avApproveDayBtn');
        var rejectForm = document.getElementById('avRejectDayForm');
        var reason = document.getElementById('avRejectReason');
        if (!wrap) return;

        var pending = [row.check_in, row.check_out].filter(function (record) {
            return record && record.is_pending;
        });

        if (rejectForm) rejectForm.hidden = true;
        if (reason) reason.value = '';

        if (!pending.length) {
            wrap.hidden = true;
            return;
        }

        fillRecordIds(document.getElementById('avApproveDayIds'), pending);
        fillRecordIds(document.getElementById('avRejectDayIds'), pending);

        var both = pending.length === 2;
        var oneLabel = pending[0].type === 'check_in' ? 'absen masuk' : 'absen pulang';
        if (approveBtn) approveBtn.textContent = both ? 'Setujui masuk & pulang' : 'Setujui ' + oneLabel;
        wrap.hidden = false;
    }

    function openGroup(key) {
        var row = byKey[String(key)];
        if (!row) return;

        document.getElementById('avModalTitle').textContent = row.date_label;
        document.getElementById('avModalSub').textContent = 'Validasi absen masuk & pulang';
        document.getElementById('avEmployeeName').textContent = row.employee_name;
        document.getElementById('avEmployeeMeta').textContent = row.employee_meta || '—';

        var profileImg = document.getElementById('avProfileImg');
        var profileEmpty = document.getElementById('avProfileEmpty');
        if (row.profile_url) {
            profileImg.src = row.profile_url;
            profileImg.hidden = false;
            profileEmpty.hidden = true;
        } else {
            profileImg.hidden = true;
            profileImg.removeAttribute('src');
            profileEmpty.hidden = false;
        }

        renderSlot('check_in', row.check_in, row.profile_url);
        renderSlot('check_out', row.check_out, row.profile_url);
        syncDayActions(row);

        if (typeof dlg.showModal === 'function') {
            dlg.showModal();
        }
    }

    document.querySelectorAll('[data-absensi-group]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openGroup(btn.getAttribute('data-absensi-group'));
        });
    });

    document.getElementById('avRejectToggle')?.addEventListener('click', function () {
        var rejectForm = document.getElementById('avRejectDayForm');
        var reason = document.getElementById('avRejectReason');
        if (!rejectForm) return;
        rejectForm.hidden = !rejectForm.hidden;
        if (!rejectForm.hidden && reason) reason.focus();
        if (rejectForm.hidden && reason) reason.value = '';
    });

    document.getElementById('avModalClose')?.addEventListener('click', function () {
        dlg.close();
    });

    dlg.addEventListener('click', function (event) {
        var rect = dlg.getBoundingClientRect();
        var inDialog = rect.top <= event.clientY && event.clientY <= rect.top + rect.height
            && rect.left <= event.clientX && event.clientX <= rect.left + rect.width;
        if (!inDialog) {
            dlg.close();
        }
    });
})();
</script>
@endpush

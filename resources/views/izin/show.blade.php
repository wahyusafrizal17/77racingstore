@extends($layout)

@section('title', 'Surat Pengajuan Izin')

@section('content')
@include('partials.izin.portal-styles')

@php
    $fromValidasi = request()->boolean('from_validasi')
        || (auth()->user()?->isAttendanceValidator() && (
            ($canReview ?? false) || ($canSignHead ?? false) || ($canSignManager ?? false) || ($canSignOwner ?? false)
        ));
    $backUrl = $fromValidasi ? route('izin.validasi.index') : route('izin.index');
    $backLabel = $fromValidasi ? 'Kembali ke Validasi Izin' : 'Kembali ke Pengajuan Izin';
    $employee = $leave->user;
    $tz = config('app.timezone');
    $docNomor = 'IZIN-'.str_pad((string) $leave->id, 5, '0', STR_PAD_LEFT).'/'.$leave->created_at->timezone($tz)->format('Y');
    $statusPillText = $leave->isPending() ? $leave->pendingStageLabel() : $leave->statusLabel();
@endphp

@push('styles')
@include('partials.shared.surat-document-styles')
@endpush

<div class="izin-page izin-surat-page">
    <div class="izin-surat-toolbar no-print">
        <a href="{{ $backUrl }}" class="izin-surat-back">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            {{ $backLabel }}
        </a>
        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px">
            <span class="status-pill {{ $leave->statusCssClass() }}" style="display:inline-flex">
                <span class="dot"></span>{{ $statusPillText }}
            </span>
            <button type="button" class="izin-surat-back" onclick="window.print()" style="cursor:pointer;border:none;font-family:inherit">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak / PDF
            </button>
        </div>
    </div>

    @if(session('success') || session('error'))
    <div class="izin-surat-flash no-print">
        @if(session('success'))
        <div class="flash success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="flash error">{{ session('error') }}</div>
        @endif
    </div>
    @endif

    <article class="izin-surat-paper" id="izin-surat-document">
        <header class="izin-surat-letterhead">
            <div class="izin-surat-brand-row">
                <div class="izin-surat-brand">
                    <img src="{{ asset('images/logo.png') }}" alt="77racing" onerror="this.hidden=true; this.nextElementSibling.hidden=false;">
                    <span class="izin-surat-brand-fallback" hidden>MO</span>
                    <div class="izin-surat-brand-text">
                        <h2>77racing</h2>
                        <p>{{ $employee?->section ?: 'Internal' }} · Pengajuan cuti &amp; izin</p>
                    </div>
                </div>
                <div class="izin-surat-doc-meta">
                    <p class="doc-type">Formulir resmi</p>
                    <h1 class="doc-title">Surat Pengajuan Izin</h1>
                    <p class="doc-nomor">Nomor: {{ $docNomor }}</p>
                    <p class="no-print" style="margin-top:8px;font-size:11px;color:#94a3b8">
                        {{ $leave->typeIcon() }} {{ $leave->typeLabel() }}
                    </p>
                </div>
            </div>
        </header>

        <div class="izin-surat-body">
            <div class="izin-surat-prose">
                <p class="surat-judul">Surat Permohonan Izin</p>
                <p>Yang bertanda tangan di bawah ini:</p>
                <table class="izin-surat-identitas" role="presentation">
                    <tr>
                        <td>Nama</td>
                        <td>:</td>
                        <td><strong>{{ $employee?->name ?? '—' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Jabatan</td>
                        <td>:</td>
                        <td>{{ $employee?->role ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td>Divisi</td>
                        <td>:</td>
                        <td>{{ $employee?->section ?? '—' }}</td>
                    </tr>
                </table>
                <p>
                    Dengan ini mengajukan permohonan <strong>{{ $leave->typeLabel() }}</strong>
                    pada tanggal <strong>{{ $leave->dateRangeLabel() }}</strong>.
                </p>
                <p>Alasan permohonan:</p>
                <div class="izin-surat-alasan">{{ $leave->reason }}</div>
                @if($leave->type === \App\Models\LeaveRequest::TYPE_SAKIT)
                <p class="no-print" style="margin-top:14px">
                    Surat keterangan dokter:
                    @if($leave->hasSuratDokter())
                    <a href="{{ $leave->suratDokterUrl() }}" target="_blank" rel="noopener" class="izin-surat-link">Lihat lampiran</a>
                    @else
                    <span style="color:#94a3b8">Tidak tersedia</span>
                    @endif
                </p>
                @endif
                <p>
                    Demikian permohonan ini kami sampaikan. Atas perhatian dan persetujuannya kami ucapkan terima kasih.
                </p>
            </div>

            <dl class="izin-surat-kv-grid no-print">
                <div class="izin-surat-kv">
                    <dt>Diajukan</dt>
                    <dd>{{ $leave->created_at->timezone($tz)->translatedFormat('d M Y · H:i') }}</dd>
                </div>
                <div class="izin-surat-kv">
                    <dt>Status</dt>
                    <dd>{{ $statusPillText }}</dd>
                </div>
                @if($leave->type === \App\Models\LeaveRequest::TYPE_SAKIT)
                <div class="izin-surat-kv">
                    <dt>Surat dokter</dt>
                    <dd>
                        @if($leave->hasSuratDokter())
                        <a href="{{ $leave->suratDokterUrl() }}" target="_blank" rel="noopener" class="izin-surat-link">Lihat file</a>
                        @else
                        —
                        @endif
                    </dd>
                </div>
                @endif
                @if($leave->reviewer)
                <div class="izin-surat-kv">
                    <dt>Disetujui oleh</dt>
                    <dd>{{ $leave->reviewer->name }}</dd>
                </div>
                <div class="izin-surat-kv">
                    <dt>Waktu disetujui</dt>
                    <dd>{{ $leave->reviewed_at?->timezone($tz)->translatedFormat('d M Y · H:i') ?? '—' }}</dd>
                </div>
                @endif
            </dl>

            @if($leave->rejection_reason)
            <div class="izin-surat-reject-box">
                <strong>Alasan penolakan:</strong><br>
                {{ $leave->rejection_reason }}
            </div>
            @endif

            <div class="izin-surat-pemohon">
                <div class="izin-surat-pemohon-inner {{ $leave->pemohonSigned() ? 'has-qr' : '' }}">
                    <p class="izin-surat-pemohon-label">Pemohon,</p>
                    @if($leave->pemohonSigned())
                        <div class="izin-surat-pemohon-qr" id="qr-izin-pemohon-{{ $leave->id }}"></div>
                        <p class="izin-surat-pemohon-name">{{ $employee?->name ?? '—' }}</p>
                        <p class="izin-surat-pemohon-meta">
                            {{ $leave->pemohon_signed_at->timezone($tz)->translatedFormat('d F Y') }}
                        </p>
                        <a href="{{ url('/izin/verify/'.$leave->pemohon_signature_token) }}" target="_blank" class="no-print izin-surat-pemohon-verify">Verifikasi QR</a>
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                if (typeof window.renderDinasQr === 'function') {
                                    window.renderDinasQr('qr-izin-pemohon-{{ $leave->id }}', @json(url('/izin/verify/'.$leave->pemohon_signature_token)));
                                }
                            });
                        </script>
                    @else
                        <div class="izin-surat-pemohon-line" aria-hidden="true"></div>
                        <p class="izin-surat-pemohon-name">{{ $employee?->name ?? '—' }}</p>
                        <p class="izin-surat-pemohon-meta">
                            {{ $leave->created_at->timezone($tz)->translatedFormat('d F Y') }}
                        </p>
                    @endif
                </div>
            </div>

            <footer class="izin-surat-signatures">
                <p class="izin-surat-signatures-title">Persetujuan · Tanda Tangan Digital (QR)</p>
                @if($leave->isPending())
                <p class="no-print izin-surat-sign-meta" style="text-align:center;margin:-8px 0 16px;line-height:1.55;max-width:560px;margin-left:auto;margin-right:auto">
                    @if($leave->manager_approver_id === null && ! $leave->requiresOwnerAcknowledgment())
                        Staf Marketing: cukup Verifikasi Head Marketing.
                    @else
                        77racing &amp; Speedshop (staf): Verifikasi 1 Head → Verifikasi 2 Manager. Diketahui Owner tanpa TTD.
                        Head: cukup Manager. Staf Marketing: cukup Head Marketing.
                    @endif
                </p>
                @endif
                @include('partials.izin.signature-slots', [
                    'leave' => $leave,
                    'signRoute' => route('izin.sign', $leave),
                    'canSignHead' => $canSignHead ?? false,
                    'canSignManager' => $canSignManager ?? false,
                    'canSignOwner' => $canSignOwner ?? false,
                    'letterLayout' => true,
                ])
            </footer>

            <div class="izin-surat-actions no-print">
                @if(!empty($canReview) && $leave->isPending())
                <p class="izin-show-section-title" style="color: var(--red-deep); margin-bottom: 10px">Tolak pengajuan</p>
                <form method="POST" action="{{ route('izin.validasi.reject', $leave) }}" class="izin-show-reject-form" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
                    @csrf
                    <input type="text" name="rejection_reason" required maxlength="500" placeholder="Alasan penolakan…" class="reject-input" style="flex:1; min-width:min(100%, 280px)">
                    <button type="submit" class="btn-reject">Tolak</button>
                </form>
                @endif

                @if($leave->isPending() && (int) auth()->id() === (int) $leave->user_id)
                <div style="margin-top: 20px">
                    <form method="POST" action="{{ route('izin.cancel', $leave) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-ghost" onclick="return confirm('Batalkan pengajuan izin ini?')">
                            Batalkan pengajuan
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>
    </article>
</div>

@include('partials.shared.surat-qr-scripts')
@endsection

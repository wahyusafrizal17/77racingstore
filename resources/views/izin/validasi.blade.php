@extends($layout)

@section('title', 'Validasi Izin')

@section('content')
@include('partials.izin.portal-styles')
@include('partials.izin.data-table-styles')

<div class="izin-page">
    <div class="hero-card">
        <div class="eyebrow">HR · Approval</div>
        <h1 class="title">Validasi Izin</h1>
        <p class="sub">Daftar pengajuan menunggu persetujuan. Klik <strong>Detail &amp; TTD</strong>.</p>
    </div>

    @if(session('success'))
    <div class="flash success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="flash error">{{ session('error') }}</div>
    @endif

    <section class="panel">
        <div class="panel-head">
            <h2>Antrian Persetujuan</h2>
            <p>Pengajuan dalam alur Anda — tetap tampil setelah Anda TTD sampai selesai disetujui</p>
        </div>

        @if($requests->isEmpty())
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <p>Tidak ada pengajuan izin yang menunggu validasi.</p>
        </div>
        @else
        <div class="validasi-table-wrap">
            <table class="validasi-table">
                <thead>
                    <tr>
                        <th>Karyawan</th>
                        <th>Jenis</th>
                        <th>Periode</th>
                        <th>Alasan</th>
                        <th>Langkah</th>
                        <th>Diajukan</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php $leaveService = app(\App\Services\LeaveRequestService::class); @endphp
                    @foreach($requests as $leave)
                    @php
                        $leave->loadMissing(['user']);
                        $needsAction = $leaveService->needsReviewerAction($leave, auth()->user());
                    @endphp
                    <tr>
                        <td>
                            <div class="cell-name">{{ $leave->user?->name }}</div>
                            <div class="cell-meta">{{ $leave->user?->section }} · {{ $leave->user?->role }}</div>
                        </td>
                        <td>
                            <span style="margin-right:4px">{{ $leave->typeIcon() }}</span>
                            {{ $leave->typeLabel() }}
                        </td>
                        <td>{{ $leave->dateRangeLabel() }}</td>
                        <td class="reason-cell" title="{{ $leave->reason }}">{{ $leave->reason }}</td>
                        <td>
                            <span class="status-pill {{ $leave->statusCssClass() }}" style="display:inline-flex">
                                <span class="dot"></span>{{ $leave->pendingStageLabel() }}
                            </span>
                            @if($leave->isPending() && ! $needsAction)
                            <div class="cell-meta" style="margin-top:4px">Anda sudah TTD · menunggu penyetuju lain</div>
                            @endif
                        </td>
                        <td style="white-space:nowrap">{{ $leave->created_at->translatedFormat('d M Y') }}</td>
                        <td style="text-align:right">
                            <a href="{{ route('izin.show', ['leaveRequest' => $leave, 'from_validasi' => 1]) }}" class="btn-detail">{{ $needsAction ? 'Detail & TTD' : 'Detail surat' }}</a>
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
@endsection

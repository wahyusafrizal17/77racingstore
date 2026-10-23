@extends('layouts.speedshop')

@section('title', 'Detail Setoran')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
@endpush

@php
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Support\Facades\Storage;
    $tz = config('app.timezone');
    $hasKkKeKbCol = Schema::hasColumn('speedshop_finance_setorans', 'setor_kk_ke_kb_actual');
    $setorKkKeKb = $hasKkKeKbCol ? (int) ($setoran->setor_kk_ke_kb_actual ?? 0) : 0;
    $totalSetor = (int) $setoran->setor_tunai_actual + (int) $setoran->setor_digital_actual + $setorKkKeKb;
    $itemsEnabled = Schema::hasTable('speedshop_finance_setoran_items');
    $items = $itemsEnabled ? ($setoran->items ?? collect()) : collect();
    $rincianBaris = $items->isNotEmpty() ? $items->count() : (2 + ($setorKkKeKb > 0 ? 1 : 0));
    $isRejected = $setoran->isRejected();
    $isPending = $setoran->isPending();
    $isCompleted = $setoran->isCompleted();
    $isSelesai = $isCompleted || $isRejected;
    $isVerifikasiPage = request()->routeIs('speedshop.verifikasi.setoran*');
    $backUrl = route($isVerifikasiPage ? 'speedshop.verifikasi.setoran.index' : 'speedshop.keuangan.setoran.index');

    $woPanelClass = 'panel-card panel-card-flush overflow-visible ss-dash-enter-child';

    $currentStep = match (true) {
        $isPending => 1,
        $isSelesai => 2,
        default => 0,
    };
    $steps = [
        ['label' => 'Diajukan'],
        ['label' => 'Menunggu Verifikasi'],
        ['label' => $isRejected ? 'Ditolak' : 'Disetujui'],
    ];

    $statusBadgeClass = match (true) {
        $isPending => 'border-amber-200 bg-amber-50 text-amber-700',
        $isRejected => 'border-red-200 bg-red-50 text-red-700',
        default => 'border-emerald-200 bg-emerald-50 text-emerald-700',
    };
    $statusBadgeLabel = match (true) {
        $isPending => 'Menunggu',
        $isRejected => 'Ditolak',
        default => 'Selesai',
    };

    $accountingUser = auth()->user()->isAccounting();
    $canApproveReject = $isPending && $accountingUser;
@endphp

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
>
<div class="ss-dash-content space-y-5">
    <div class="flex flex-col gap-3 ss-dash-enter sm:flex-row sm:items-start sm:justify-between" style="--ss-delay: 0ms">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Setoran {{ $setoran->tanggal_setor->format('d M Y') }}</h1>
                <span class="inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusBadgeClass }}">{{ $statusBadgeLabel }}</span>
            </div>
            <p class="mt-1 text-[13px] text-gray-500">
                @if($isVerifikasiPage)
                    Verifikasi setoran kas · diajukan {{ $setoran->created_at->timezone($tz)->format('d M Y H:i') }}
                @else
                    Detail setoran kas · diajukan {{ $setoran->created_at->timezone($tz)->format('d M Y H:i') }}
                @endif
            </p>
        </div>
        <a href="{{ $backUrl }}" class="btn-secondary shrink-0">← Kembali</a>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12 ss-dash-enter" style="--ss-delay: 80ms">
        <div class="space-y-5 lg:col-span-8">
            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 0">
                <div class="flex items-start justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-[20px] font-semibold text-gray-900">Setoran Kas</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Informasi pengajuan setoran dan rekening terkait</p>
                    </div>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                </div>

                <div class="space-y-4 px-5 py-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Tanggal Setor</label>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-semibold text-gray-900">{{ $setoran->tanggal_setor->format('d M Y') }}</div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Diajukan Oleh</label>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $setoran->user?->name ?? '—' }}</div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Setoran Oleh</label>
                            <div class="flex min-h-[42px] items-center rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5">
                                @if($setoran->isSubmittedByAccounting())
                                <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-800">Accounting</span>
                                @else
                                <span class="inline-flex items-center rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">Speedshop</span>
                                @endif
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Waktu Pengajuan</label>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $setoran->created_at->timezone($tz)->format('d M Y H:i') }}</div>
                        </div>
                    </div>

                    @if($setoran->keuanganRekening)
                    <div>
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Rekening Utama (Tujuan)</label>
                        <div class="rounded-xl border border-gray-200 bg-[#fcfafb]">
                            <div class="flex items-center gap-3 border-b border-gray-100 px-4 py-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand text-brand-ink">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                </div>
                                <div>
                                    <div class="text-base font-semibold text-gray-900">{{ $setoran->keuanganRekening->nama }}</div>
                                    <div class="text-[11px] uppercase tracking-wide text-gray-500">Rekening Utama</div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-2">
                                <div>
                                    <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Bank</div>
                                    <div class="mt-0.5 text-sm font-semibold text-gray-900">{{ $setoran->keuanganRekening->nama_bank ?: '—' }}</div>
                                </div>
                                <div>
                                    <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">No. Rekening</div>
                                    <div class="mt-0.5 font-mono text-sm font-semibold tabular-nums text-gray-900">{{ $setoran->keuanganRekening->nomor_rekening }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($setoran->keuanganKasBesar)
                    <div>
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Kas Besar @if($setorKkKeKb > 0)<span class="font-normal normal-case tracking-normal text-gray-600">(sumber digital &amp; tujuan pindah dari Kas Kecil)</span>@else<span class="font-normal normal-case tracking-normal text-gray-600">(sumber digital)</span>@endif</label>
                        <div class="rounded-xl border border-violet-200/80 bg-violet-50/40">
                            <div class="flex items-center gap-3 border-b border-violet-200/60 px-4 py-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-600 text-white">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                </div>
                                <div>
                                    <div class="text-base font-semibold text-gray-900">{{ $setoran->keuanganKasBesar->nama }}</div>
                                    <div class="text-[11px] uppercase tracking-wide text-violet-800/90">Rekening Kas Besar</div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-1 px-4 py-3 sm:grid-cols-2">
                                <div>
                                    <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">Bank</div>
                                    <div class="mt-0.5 text-sm font-semibold text-gray-900">{{ $setoran->keuanganKasBesar->nama_bank ?: '—' }}</div>
                                </div>
                                <div>
                                    <div class="text-[11px] font-medium uppercase tracking-wide text-gray-500">No. Rekening</div>
                                    <div class="mt-0.5 font-mono text-sm font-semibold tabular-nums text-gray-900">{{ $setoran->keuanganKasBesar->nomor_rekening }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($setoran->catatan)
                    <div>
                        <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Catatan</label>
                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 whitespace-pre-line">{{ $setoran->catatan }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 1">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-[20px] font-semibold text-gray-900">Transaksi Setoran</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Daftar transaksi sesuai format input setoran</p>
                    </div>
                    <span class="wo-badge">{{ $rincianBaris }} baris</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-[#f7f3f4]">
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Sumber</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tujuan</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500 text-right">Nominal</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Keterangan</th>
                                <th class="min-w-[120px] px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Bukti</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @if($itemsEnabled && $items->isNotEmpty())
                                @foreach($items as $i => $it)
                                    @php
                                        $srcJenis = '—';
                                        $srcNama = '—';
                                        $srcNo = null;
                                        $srcSaldo = null;
                                        if ($it->sumber === 'kas_kecil') {
                                            $srcJenis = 'Kas Kecil';
                                            $srcNama = 'Tunai';
                                        } elseif ($it->sumber === 'kas_besar') {
                                            $srcJenis = 'Kas Besar';
                                            $r = $it->sumberKasBesar;
                                            if ($r) {
                                                $srcNama = trim(($r->nama_bank ?: '').' — '.($r->nama ?: ''));
                                                $srcNo = $r->nomor_rekening;
                                                $srcSaldo = $saldoByRekId[(int) $r->id] ?? null;
                                            }
                                        } elseif ($it->sumber === 'rekening_utama') {
                                            $srcJenis = 'Rekening Utama';
                                            $r = $it->sumberRekeningUtama;
                                            if ($r) {
                                                $srcNama = trim(($r->nama_bank ?: '').' — '.($r->nama ?: ''));
                                                $srcNo = $r->nomor_rekening;
                                                $srcSaldo = $saldoByRekId[(int) $r->id] ?? null;
                                            }
                                        }

                                        $dstJenis = '—';
                                        $dstNama = '—';
                                        $dstNo = null;
                                        $dstSaldo = null;
                                        if ($it->tujuan_type === 'kas_kecil') {
                                            $dstJenis = 'Kas Kecil';
                                            $dstNama = 'Tunai';
                                        } elseif (in_array($it->tujuan_type, ['kas_besar', 'rekening_utama'], true)) {
                                            $r = $it->tujuanRekening;
                                            $dstJenis = $it->tujuan_type === 'kas_besar' ? 'Kas Besar' : 'Rekening Utama';
                                            if ($r) {
                                                $dstNama = trim(($r->nama_bank ?: '').' — '.($r->nama ?: ''));
                                                $dstNo = $r->nomor_rekening;
                                                $dstSaldo = $saldoByRekId[(int) $r->id] ?? null;
                                            }
                                        }

                                        $buktiUrl = $it->bukti_path ? Storage::disk((string) config('filesystems.upload_disk', 'public'))->url($it->bukti_path) : null;
                                    @endphp
                                    <tr class="transition hover:bg-[#fcfafb]">
                                        <td class="px-4 py-3 text-gray-500">{{ $i + 1 }}</td>
                                        <td class="px-4 py-3 text-gray-800">
                                            <div class="font-semibold text-gray-900">{{ $srcJenis }}</div>
                                            <div class="mt-0.5 text-xs text-gray-600">{{ $srcNama }}</div>
                                            @if($srcNo)
                                                <div class="mt-0.5 text-xs text-gray-500"><span class="font-mono tabular-nums">{{ $srcNo }}</span> · Saldo: <span class="font-semibold">Rp {{ number_format((int) $srcSaldo, 0, ',', '.') }}</span></div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-800">
                                            <div class="font-semibold text-gray-900">{{ $dstJenis }}</div>
                                            <div class="mt-0.5 text-xs text-gray-600">{{ $dstNama }}</div>
                                            @if($dstNo)
                                                <div class="mt-0.5 text-xs text-gray-500"><span class="font-mono tabular-nums">{{ $dstNo }}</span> · Saldo: <span class="font-semibold">Rp {{ number_format((int) $dstSaldo, 0, ',', '.') }}</span></div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold tabular-nums text-gray-900">Rp {{ number_format((int) $it->nominal, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $it->keterangan ?: '—' }}</td>
                                        <td class="px-4 py-3">
                                            @if($buktiUrl)
                                                <a href="{{ $buktiUrl }}" target="_blank" rel="noopener" class="text-sm font-medium text-red-600 hover:text-red-700">Lihat bukti</a>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr class="transition hover:bg-[#fcfafb]">
                                    <td class="px-4 py-3 text-gray-500">1</td>
                                    <td class="px-4 py-3">Kas Besar</td>
                                    <td class="px-4 py-3">Rekening Utama</td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums text-gray-900">Rp {{ number_format($setoran->setor_digital_actual, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-gray-700">—</td>
                                    <td class="px-4 py-3">
                                        @if(Schema::hasColumn('speedshop_finance_setorans', 'bukti_kas_besar_path') && $setoran->bukti_kas_besar_path)
                                            <a href="{{ $setoran->buktiKasBesarPublicUrl() }}" target="_blank" rel="noopener" class="text-sm font-medium text-violet-700 hover:text-violet-800">Lihat bukti</a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr class="transition hover:bg-[#fcfafb]">
                                    <td class="px-4 py-3 text-gray-500">2</td>
                                    <td class="px-4 py-3">Kas Kecil</td>
                                    <td class="px-4 py-3">Rekening Utama</td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums text-gray-900">Rp {{ number_format($setoran->setor_tunai_actual, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-gray-700">—</td>
                                    <td class="px-4 py-3">
                                        @if(Schema::hasColumn('speedshop_finance_setorans', 'bukti_kas_kecil_path') && $setoran->bukti_kas_kecil_path)
                                            <a href="{{ $setoran->buktiKasKecilPublicUrl() }}" target="_blank" rel="noopener" class="text-sm font-medium text-amber-700 hover:text-amber-800">Lihat bukti</a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($setorKkKeKb > 0)
                                <tr class="transition hover:bg-[#fcfafb]">
                                    <td class="px-4 py-3 text-gray-500">3</td>
                                    <td class="px-4 py-3">Kas Kecil</td>
                                    <td class="px-4 py-3">Kas Besar</td>
                                    <td class="px-4 py-3 text-right font-semibold tabular-nums text-gray-900">Rp {{ number_format($setorKkKeKb, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-gray-700">—</td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        @if(Schema::hasColumn('speedshop_finance_setorans', 'bukti_kas_kecil_path') && $setoran->bukti_kas_kecil_path)
                                            <span class="text-gray-600">Satu bukti dengan Kas Kecil</span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                            @endif
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-5 py-4 text-xs text-gray-500">
                    Pendapatan tunai hari: <span class="font-medium text-gray-800">Rp {{ number_format($setoran->pendapatan_tunai_hari, 0, ',', '.') }}</span>
                    · Pendapatan digital: <span class="font-medium text-gray-800">Rp {{ number_format($setoran->pendapatan_digital_hari, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="space-y-4 lg:col-span-4">
            <div class="panel-card panel-card-flush summary-card ss-dash-enter-child" style="--ss-delay: 120ms; --ss-stagger: 2">
                <div class="section-hd">
                    <h2>Ringkasan</h2>
                    <p>Total perpindahan kas dan status verifikasi</p>
                </div>

                <div class="mt-2 mb-2">
                    <div class="summary-row">
                        <span>Kas Besar → RU</span>
                        <span class="tabular-nums">Rp {{ number_format((int) $setoran->setor_digital_actual, 0, ',', '.') }}</span>
                    </div>
                    <div class="summary-row">
                        <span>KK → Rekening Utama</span>
                        <span class="tabular-nums">Rp {{ number_format((int) $setoran->setor_tunai_actual, 0, ',', '.') }}</span>
                    </div>
                    @if($setorKkKeKb > 0)
                    <div class="summary-row">
                        <span>KK → Kas Besar</span>
                        <span class="tabular-nums">Rp {{ number_format($setorKkKeKb, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="summary-total">
                        <span>Total Tercatat</span>
                        <span>Rp {{ number_format($totalSetor, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="border-t border-gray-100 px-5 py-4">
                    <label class="mb-3 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Alur Verifikasi</label>
                    <div class="flex items-start justify-between gap-1">
                        @foreach($steps as $idx => $step)
                        @php
                            $isStepDone = $idx < $currentStep || ($isSelesai && $idx === count($steps) - 1);
                            $isStepCurrent = $idx === $currentStep && ! $isSelesai;
                            $isLastRejected = $isRejected && $idx === count($steps) - 1 && $isStepDone;
                        @endphp
                        <div class="flex flex-col items-center" style="width: {{ 100 / count($steps) }}%">
                            <div class="relative flex w-full items-center justify-center">
                                @if($idx > 0)
                                <div class="absolute right-1/2 h-0.5 w-full {{ $isStepDone ? ($isLastRejected ? 'bg-red-400' : 'bg-green-400') : 'bg-gray-200' }}"></div>
                                @endif
                                @if($idx < count($steps) - 1)
                                @php $nextDone = ($idx + 1) < $currentStep || ($isSelesai && ($idx + 1) === count($steps) - 1); @endphp
                                <div class="absolute left-1/2 h-0.5 w-full {{ $nextDone ? ($isRejected && ($idx + 1) === count($steps) - 1 ? 'bg-red-400' : 'bg-green-400') : 'bg-gray-200' }}"></div>
                                @endif
                                @if($isStepDone)
                                    @if($isLastRejected)
                                        <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-brand shadow-sm shadow-brand/30">
                                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </div>
                                    @else
                                        <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-green-500 shadow-sm shadow-green-200">
                                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                    @endif
                                @elseif($isStepCurrent)
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full border-2 border-amber-400 bg-amber-50">
                                        <div class="h-2.5 w-2.5 rounded-full bg-yellow-400 animate-pulse"></div>
                                    </div>
                                @else
                                    <div class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-gray-200">
                                        <span class="text-xs text-gray-400">{{ $idx + 1 }}</span>
                                    </div>
                                @endif
                            </div>
                            <p class="mt-2 text-center text-[10px] font-medium leading-tight sm:text-xs {{ $isStepDone ? ($isLastRejected ? 'text-red-600' : 'text-green-600') : ($isStepCurrent ? 'text-amber-600' : 'text-gray-400') }}">{{ $step['label'] }}</p>
                        </div>
                        @endforeach
                    </div>
                    @if($isCompleted)
                    <p class="mt-3 text-center text-xs text-gray-500">
                        @if($setoran->approver)
                            Disetujui oleh {{ $setoran->approver->name }} · {{ $setoran->approved_at?->timezone($tz)->format('d M Y H:i') }}
                        @else
                            Selesai · {{ $setoran->completed_at?->timezone($tz)->format('d M Y H:i') }}
                        @endif
                    </p>
                    @elseif($isPending)
                    <p class="mt-3 text-center text-xs text-gray-500">Menunggu tindakan Accounting</p>
                    @endif
                </div>

                @if($isRejected && $setoran->rejected_reason)
                <div class="border-t border-gray-100 px-5 py-4">
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                        <div class="text-[11px] font-semibold uppercase tracking-wider text-red-600">Alasan Ditolak</div>
                        <div class="mt-1 whitespace-pre-line text-sm font-medium text-red-700">{{ $setoran->rejected_reason }}</div>
                    </div>
                </div>
                @endif

                @if($canApproveReject)
                <div class="space-y-3 border-t border-gray-100 px-5 py-4">
                    <form method="POST" action="{{ route('speedshop.keuangan.setoran.approve', $setoran) }}" data-swal-confirm="approve">
                        @csrf
                        <button type="submit" class="flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-emerald-300 bg-emerald-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Setujui Setoran
                        </button>
                    </form>
                    <div x-data="{ open: false }" class="space-y-2">
                        <button type="button" @click="open = !open" class="btn-secondary flex h-10 w-full text-red-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Tolak Pengajuan
                        </button>
                        <form x-show="open" x-cloak method="POST" action="{{ route('speedshop.keuangan.setoran.reject', $setoran) }}" data-swal-confirm="reject" class="rounded-xl border border-red-200 bg-white p-4">
                            @csrf
                            <label class="mb-2 block text-xs font-semibold text-gray-700">Alasan penolakan <span class="text-red-600">*</span></label>
                            <textarea name="rejected_reason" rows="3" required minlength="3" maxlength="2000" class="wo-textarea" placeholder="Jelaskan alasan penolakan…"></textarea>
                            <div class="mt-3 flex items-center justify-end gap-2">
                                <button type="button" @click="open = false" class="btn-secondary h-9 px-4">Batal</button>
                                <button type="submit" class="btn-primary h-9 px-4">Tolak & Kirim</button>
                            </div>
                        </form>
                    </div>
                </div>
                @elseif($isPending)
                <div class="border-t border-gray-100 px-5 py-4">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
                        Pengajuan ini menunggu verifikasi Accounting. Anda akan mendapat notifikasi setelah diproses.
                    </div>
                </div>
                @endif
            </div>

            @if($canApproveReject)
            <div class="panel-card panel-card-flush ss-dash-enter-child rounded-xl border-amber-200/80 bg-amber-50/80 p-4" style="--ss-delay: 120ms; --ss-stagger: 3">
                <div class="flex gap-3">
                    <svg class="h-5 w-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <p class="text-sm font-semibold text-amber-800">Menunggu Verifikasi</p>
                        <p class="mt-1 text-xs leading-relaxed text-amber-700">Periksa transaksi dan bukti transfer, lalu setujui atau tolak pengajuan setoran ini.</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const forms = document.querySelectorAll('form[data-swal-confirm]');
  forms.forEach((form) => {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const kind = form.getAttribute('data-swal-confirm');
      if (!kind) return form.submit();

      if (kind === 'reject') {
        const textarea = form.querySelector('textarea[name="rejected_reason"]');
        const reason = (textarea?.value || '').trim();
        if (reason.length < 3) {
          if (textarea) textarea.focus();
          return Swal.fire('Perhatian', 'Alasan penolakan wajib diisi (min. 3 karakter).', 'warning');
        }
      }

      const cfg = kind === 'approve'
        ? {
            title: 'Setujui setoran?',
            text: 'Setoran akan diposting ke jurnal kas.',
            icon: 'question',
            confirmButtonText: 'Ya, setujui',
            confirmButtonColor: '#16a34a',
          }
        : {
            title: 'Tolak pengajuan setoran?',
            text: 'Pengajuan akan ditandai ditolak.',
            icon: 'warning',
            confirmButtonText: 'Ya, tolak',
            confirmButtonColor: '#dc2626',
          };

      const result = await Swal.fire({
        ...cfg,
        showCancelButton: true,
        cancelButtonText: 'Batal',
        cancelButtonColor: '#9ca3af',
      });

      if (result.isConfirmed) {
        form.submit();
      }
    });
  });
});
</script>
@endpush

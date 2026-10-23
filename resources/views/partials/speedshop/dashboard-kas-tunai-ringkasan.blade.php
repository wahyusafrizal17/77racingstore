@php
    $kas = $kasTunaiRingkasan ?? null;
    $fmtKas = fn ($v) => number_format((float) $v, 0, ',', '.');
@endphp

@if(! empty($kas))
<div class="section-hd ss-dash-enter" style="--ss-delay: 200ms">
    <h2>Kas Tunai Gabungan</h2>
    <p>
        Transaksi tunai + permintaan kas − BOP tunai · {{ $kas['period_label'] ?? '' }}
        · saran setor sambil simpan kas untuk besok
    </p>
</div>

<div class="chart-card ss-dash-enter" style="--ss-delay: 220ms; margin-bottom: 20px;">
    <div class="payment-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr)); margin-bottom: 16px;">
        <div class="pay-card" style="box-shadow: none;">
            <div class="pay-card-top">
                <span class="pay-card-label">Transaksi Tunai</span>
                <div class="pay-icon teal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                </div>
            </div>
            <div class="pay-amount"><span class="curr">Rp</span>{{ $fmtKas($kas['pendapatan_tunai'] ?? 0) }}</div>
            <div class="pay-period">Dari invoice tunai</div>
        </div>
        <div class="pay-card" style="box-shadow: none;">
            <div class="pay-card-top">
                <span class="pay-card-label">+ Permintaan Kas</span>
                <div class="pay-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                </div>
            </div>
            <div class="pay-amount" style="color:var(--ss-blue)"><span class="curr">Rp</span>{{ $fmtKas($kas['permintaan_kas'] ?? 0) }}</div>
            <div class="pay-period">Kas kecil disetujui</div>
        </div>
        <div class="pay-card" style="box-shadow: none;">
            <div class="pay-card-top">
                <span class="pay-card-label">− BOP Tunai</span>
                <div class="pay-icon amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"/></svg>
                </div>
            </div>
            <div class="pay-amount" style="color:var(--ss-amber)"><span class="curr">Rp</span>{{ $fmtKas($kas['bop_kas_tunai'] ?? 0) }}</div>
            <div class="pay-period">Biaya operasional kas</div>
        </div>
    </div>

    <div class="branch-total-row" style="margin-top: 0; margin-bottom: 12px;">
        <div>
            <div class="branch-total-lbl">Total kas tunai</div>
            <div style="font-size: 11px; color: var(--ss-muted); margin-top: 2px;">Transaksi + permintaan</div>
        </div>
        <div class="branch-total-val">Rp {{ $fmtKas($kas['total_kas_tunai'] ?? 0) }}</div>
    </div>

    <div class="branch-total-row" style="margin-top: 0; margin-bottom: 12px; background: rgba(18,183,106,0.06);">
        <div>
            <div class="branch-total-lbl" style="color:#047857;">Sisa setelah BOP</div>
            <div style="font-size: 11px; color: var(--ss-muted); margin-top: 2px;">Siap dialokasikan (setor / simpan)</div>
        </div>
        <div class="branch-total-val" style="color:#047857;">Rp {{ $fmtKas($kas['sisa_setelah_bop'] ?? 0) }}</div>
    </div>

    <div class="payment-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); margin-bottom: 0;">
        <div class="pay-card" style="box-shadow: none; border-color: rgba(140,250,1,0.2); background: rgba(140,250,1,0.04);">
            <div class="pay-card-top">
                <span class="pay-card-label">Saran setor tunai</span>
                <div class="pay-icon red">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
            </div>
            <div class="pay-amount" style="color:var(--ss-red-ink)"><span class="curr">Rp</span>{{ $fmtKas($kas['wajib_setor_tunai'] ?? 0) }}</div>
            <div class="pay-period">
                @if(! empty($kas['is_single_day']))
                    Setorkan ke rekening · sisanya tetap di laci
                @else
                    Total saran setor periode ini
                @endif
            </div>
        </div>
        <div class="pay-card" style="box-shadow: none; border-color: rgba(46,107,230,0.2); background: rgba(46,107,230,0.04);">
            <div class="pay-card-top">
                <span class="pay-card-label">Kas disimpan (besok)</span>
                <div class="pay-icon blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
            </div>
            <div class="pay-amount" style="color:var(--ss-blue)"><span class="curr">Rp</span>{{ $fmtKas($kas['kas_disimpan_besok'] ?? 0) }}</div>
            <div class="pay-period">
                @if(($kas['permintaan_kas'] ?? 0) > 0)
                    Dari permintaan kas · tidak perlu minta lagi besok
                @else
                    Modal laci default · Rp {{ $fmtKas($kas['modal_laci'] ?? 0) }}/hari
                @endif
            </div>
        </div>
    </div>
</div>
@endif

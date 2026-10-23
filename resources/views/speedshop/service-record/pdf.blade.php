@extends('layouts.pdf-modifikasi')

@section('pdf_title', 'Service Record - '.($pelanggan->nama ?? 'Pelanggan'))

@section('content')
<div class="doc-header">
    <div class="doc-brand">
        <div class="doc-brand-logo">
            <div class="logo-mark">@include('layouts.partials.pdf-logo-img')</div>
            <span class="logo-name">77racing</span>
        </div>
        <div class="doc-sub">Service record pelanggan</div>
        <div class="doc-title-block">
            <div class="doc-title" style="font-size: 22px;">Service Record</div>
        </div>
    </div>
    <div class="doc-meta">
        <div class="doc-inv-no">{{ $pelanggan->nama ?: '-' }}</div>
        <div class="doc-meta-row">Dicetak: <span>{{ ($printedAt ?? now())->format('d M Y H:i') }}</span></div>
    </div>
</div>

<div class="summary-inline" style="background: #FAFAF9; border-color: #E7E5E4;">
    <table class="header-table" style="width:100%;">
        <tr>
            <td style="width:50%; vertical-align:top;">
                <div class="label">Pelanggan</div>
                <div class="value">{{ $pelanggan->nama ?: '-' }}</div>
            </td>
            <td style="width:50%; vertical-align:top; text-align:right;">
                <div class="label">No HP</div>
                <div class="value">{{ $pelanggan->no_hp ?: '-' }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding-top:12px; vertical-align:top;">
                <div class="label">Total kunjungan</div>
                <div class="value">{{ number_format($totalKunjungan ?? 0) }}×</div>
            </td>
            <td style="padding-top:12px; vertical-align:top; text-align:right;">
                <div class="label">Total pengeluaran</div>
                <div class="value">Rp {{ number_format($totalPengeluaran ?? 0, 0, ',', '.') }}</div>
            </td>
        </tr>
    </table>
</div>

<p class="doc-lead" style="margin-top: 18px; margin-bottom: 8px; font-size: 11px; color: #78716C;">
    Rincian per kunjungan — jasa servis, sparepart, kendaraan &amp; ringkasan biaya.
</p>

@forelse($orders as $order)
    @php
        $tanggal = $order->tanggal_selesai ?? $order->tanggal_masuk;
        $inv = $order->invoice;
        $k = $order->kendaraan;
        $polisi = $k?->nomor_polisi;
        $merkTipe = trim(implode(' ', array_filter([(string) ($k?->merk ?? ''), (string) ($k?->tipe ?? '')])));
        $keluhan = trim((string) ($order->keluhan ?? ''));
        $km = ($order->kilometer !== null && $order->kilometer !== '')
            ? number_format((int) $order->kilometer, 0, ',', '.').' km'
            : '—';
        $sumber = $order->sumber_kedatangan
            ? (\App\Models\ServiceOrder::SUMBER_KEDATANGAN[$order->sumber_kedatangan] ?? $order->sumber_kedatangan)
            : '—';
        $kategori = $order->kategoriServiceDisplay();
        $jenisBayarRaw = strtolower((string) ($order->jenis_pembayaran ?? ''));
        $jenisBayar = match ($jenisBayarRaw) {
            'cash', 'tunai' => 'Tunai',
            'transfer' => 'Transfer',
            'qris' => 'QRIS',
            'edc' => 'EDC',
            default => $jenisBayarRaw !== '' ? ucfirst($jenisBayarRaw) : '—',
        };
    @endphp
    <div class="product-block" style="margin-bottom: 14px; border-color: #E7E5E4;">
        <div class="product-header" style="background: #FAFAF9;">
            <div>
                <div class="product-title">{{ $order->kode_servis }}</div>
                <div class="product-sub">
                    {{ $tanggal?->format('d M Y') }}
                    @if(!empty($serviceRecordAllSpeedshops) && $order->speedshop?->nama)
                        · {{ $order->speedshop->nama }}
                    @endif
                    @if($inv?->kode_invoice)
                        · Invoice {{ $inv->kode_invoice }}
                    @endif
                    · {{ $order->jenisOrderDisplay() }}
                    · {{ $order->kategoriServiceDisplay() }}
                </div>
            </div>
        </div>
        <div style="padding: 12px 18px 14px;">
            <table class="header-table" style="margin-bottom: 10px;">
                <tr>
                    <td style="font-size: 10px; color: #57534E; padding-bottom: 4px;">
                        <strong style="color: #44403C;">Customer:</strong> {{ $order->pelanggan->nama ?? '-' }}
                    </td>
                    <td style="font-size: 10px; color: #57534E; padding-bottom: 4px; text-align:right;">
                        <strong style="color: #44403C;">No HP:</strong> {{ $order->pelanggan->no_hp ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <td style="font-size: 10px; color: #57534E; padding-bottom: 4px;">
                        <strong style="color: #44403C;">Kendaraan:</strong>
                        @if($polisi)<span style="font-weight: 700;">{{ $polisi }}</span>@endif
                        @if($merkTipe)<span> — {{ $merkTipe }}</span>@endif
                    </td>
                    <td style="font-size: 10px; color: #57534E; padding-bottom: 4px; text-align:right;">
                        <strong style="color: #44403C;">KM:</strong> {{ $km }}
                    </td>
                </tr>
                <tr>
                    <td style="font-size: 10px; color: #57534E; padding-bottom: 4px;">
                        <strong style="color: #44403C;">Sumber:</strong> {{ $sumber }}
                    </td>
                    <td style="font-size: 10px; color: #57534E; padding-bottom: 4px; text-align:right;">
                        <strong style="color: #44403C;">Kategori:</strong> {{ $kategori }}
                    </td>
                </tr>
                <tr>
                    <td style="font-size: 10px; color: #57534E; padding-bottom: 4px;">
                        <strong style="color: #44403C;">Jenis Pembayaran:</strong> {{ $jenisBayar }}
                    </td>
                    <td style="font-size: 10px; color: #57534E; padding-bottom: 8px; text-align:right;">
                        <strong style="color: #44403C;">Mekanik:</strong> {{ $order->mekanik->nama ?? '—' }}
                    </td>
                </tr>
            </table>
            @if($keluhan !== '')
            <div style="font-size: 10px; color: #57534E; background: #FAFAF9; border: 1px solid #F5F5F4; border-radius: 8px; padding: 8px 10px; margin-bottom: 12px; line-height: 1.45;">
                <strong style="color: #78716C;">Keluhan:</strong> {{ $keluhan }}
            </div>
            @endif

            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="border:none; width:50%; padding-right:10px; vertical-align:top;">
                        <p class="section-title" style="margin-top:0;">Jasa servis</p>
                        @if($order->jasaServis->isNotEmpty())
                            @foreach($order->jasaServis as $j)
                                @php
                                    $jq = (int) ($j->pivot->qty ?? 1);
                                    $jb = (int) ($j->pivot->biaya ?? 0);
                                    $jd = (int) ($j->pivot->diskon ?? 0);
                                    $jSub = $jq * $jb - $jd;
                                    $jLabel = $j->nama.($jq > 1 ? ' ×'.$jq : '');
                                @endphp
                                <div class="line">
                                    <span>{{ $jLabel }}</span>
                                    <span>Rp {{ number_format($jSub, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        @else
                            <div class="muted" style="font-size: 10px;">—</div>
                        @endif
                    </td>
                    <td style="border:none; width:50%; padding-left:10px; vertical-align:top;">
                        <p class="section-title" style="margin-top:0;">Sparepart</p>
                        @if($order->products->isNotEmpty())
                            @foreach($order->products as $p)
                                @php $sub = (int) $p->pivot->qty * (int) $p->pivot->harga - (int) ($p->pivot->diskon ?? 0); @endphp
                                <div class="line">
                                    <span>{{ $p->nama_produk }} ({{ $p->pivot->qty }}×)</span>
                                    <span>Rp {{ number_format($sub, 0, ',', '.') }}</span>
                                </div>
                            @endforeach
                        @else
                            <div class="muted" style="font-size: 10px;">—</div>
                        @endif
                    </td>
                </tr>
            </table>

            <div class="total" style="border-top-color: #E7E5E4;">
                Total: Rp {{ number_format($order->finalGrandTotal(), 0, ',', '.') }}
            </div>
        </div>
    </div>
@empty
    <div class="card" style="border-color: #E7E5E4;">
        <div class="muted" style="padding: 8px 0;">Belum ada riwayat transaksi untuk pelanggan ini.</div>
    </div>
@endforelse

<div class="doc-footer">
    <p>77racing Speedshop — {{ ($printedAt ?? now())->year }}</p>
</div>
@endsection

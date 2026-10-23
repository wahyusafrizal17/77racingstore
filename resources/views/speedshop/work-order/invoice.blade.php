<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $service_order->kode_servis }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            font-size: 14px;
            color: #1f2937;
            line-height: 1.5;
            max-width: 720px;
            margin: 0 auto;
            padding: 32px 24px;
            background: #f9fafb;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .invoice-paper { box-shadow: none; padding: 0; }
        }

        .no-print {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-print {
            padding: 10px 20px;
            background: #dc2626;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-print:hover { background: #b91c1c; }
        .link-close { color: #6b7280; text-decoration: none; font-size: 14px; }
        .link-close:hover { text-decoration: underline; }

        .invoice-paper {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,.08);
            padding: 40px;
            overflow: hidden;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 24px;
            padding-bottom: 24px;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 28px;
        }
        .invoice-brand {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .invoice-brand img {
            max-height: 52px;
            width: auto;
            object-fit: contain;
        }
        .logo {
            font-size: 15px;
            font-weight: 600;
            color: #6b7280;
            letter-spacing: -0.02em;
        }
        .invoice-meta {
            text-align: right;
        }
        .invoice-meta .title {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 4px 0;
            letter-spacing: -0.02em;
        }
        .invoice-meta .no { font-size: 15px; color: #4b5563; font-weight: 600; margin: 0; }
        .invoice-meta .date { font-size: 13px; color: #6b7280; margin: 2px 0 0 0; }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 32px;
            margin-bottom: 28px;
        }
        .info-block {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px 16px;
        }
        .info-block .label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #6b7280;
            margin-bottom: 6px;
        }
        .info-block .value { color: #1f2937; font-size: 14px; }
        .info-block .value + .value { margin-top: 2px; }

        .section-title {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            margin-bottom: 12px;
        }

        table.invoice-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        table.invoice-table th {
            text-align: left;
            padding: 12px 14px;
            background: #f3f4f6;
            font-weight: 600;
            color: #374151;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border: 1px solid #e5e7eb;
        }
        table.invoice-table th.num,
        table.invoice-table td.num { text-align: right; }
        table.invoice-table td {
            padding: 12px 14px;
            border: 1px solid #e5e7eb;
            color: #374151;
        }
        table.invoice-table tbody tr:nth-child(even) { background: #fafafa; }
        table.invoice-table tbody tr:hover { background: #f9fafb; }
        @media print {
            table.invoice-table tbody tr:nth-child(even) { background: #f9fafb; }
        }

        .totals {
            margin-top: 24px;
            margin-left: auto;
            width: 280px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }
        .totals-row.discount { color: #b45309; }
        .totals-row.grand {
            margin-top: 8px;
            padding-top: 14px;
            border-top: 2px solid #1f2937;
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }

        .footer-note {
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" class="btn-print" onclick="window.print()">Cetak Invoice</button>
        <a href="javascript:window.close()" class="link-close">Tutup</a>
    </div>

    <div class="invoice-paper">
        <header class="invoice-header">
            <div class="invoice-brand">
                <img src="{{ asset('images/logo.png') }}" alt="77racing" width="200" height="60" loading="eager" decoding="async">
                <div class="logo">Speedshop</div>
            </div>
            <div class="invoice-meta">
                <h1 class="title">INVOICE</h1>
                <p class="no">{{ $service_order->kode_servis }}</p>
                <p class="date">Tanggal: {{ $service_order->tanggal_selesai?->format('d/m/Y') ?? $service_order->tanggal_masuk->format('d/m/Y') }}</p>
            </div>
        </header>

        <div class="info-grid">
            <div class="info-block">
                <div class="label">Pelanggan</div>
                <div class="value">{{ $service_order->pelanggan->nama ?? '-' }}</div>
                @if($service_order->pelanggan->no_hp ?? null)
                <div class="value">{{ $service_order->pelanggan->no_hp }}</div>
                @endif
                @if($service_order->pelanggan->alamat ?? null)
                <div class="value">{{ $service_order->pelanggan->alamat }}</div>
                @endif
            </div>
            <div class="info-block">
                <div class="label">Kendaraan</div>
                <div class="value">
                    @if($service_order->kendaraan)
                        {{ $service_order->kendaraan->nomor_polisi ?? '-' }} — {{ trim(($service_order->kendaraan->merk ?? '').' '.($service_order->kendaraan->tipe ?? '')) ?: '-' }}
                    @else
                        —
                    @endif
                </div>
            </div>
            <div class="info-block">
                <div class="label">Mekanik</div>
                <div class="value">{{ $service_order->mekanik?->nama ?? '-' }}</div>
            </div>
        </div>

        <div class="section-title">Detail Jasa Servis</div>
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 44px;">No.</th>
                    <th>Deskripsi</th>
                    <th class="num" style="width: 110px;">Biaya</th>
                    <th class="num" style="width: 100px;">Diskon</th>
                    <th class="num" style="width: 120px;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp
                @foreach($service_order->jasaServis as $j)
                @php
                    $qty = (int) ($j->pivot->qty ?? 1);
                    $biayaSatuan = (int) $j->pivot->biaya;
                    $biaya = $qty * $biayaSatuan;
                    $diskon = (int) ($j->pivot->diskon ?? 0);
                @endphp
                <tr>
                    <td>{{ $no++ }}</td>
                    <td>{{ $j->nama }} @if($qty > 1)<span style="color: #9ca3af;">({{ $qty }} × Rp {{ number_format($biayaSatuan, 0, ',', '.') }})</span>@endif</td>
                    <td class="num">Rp {{ number_format($biaya, 0, ',', '.') }}</td>
                    <td class="num">Rp {{ number_format($diskon, 0, ',', '.') }}</td>
                    <td class="num"><strong>Rp {{ number_format($biaya - $diskon, 0, ',', '.') }}</strong></td>
                </tr>
                @endforeach
                @if($service_order->jasaServis->isEmpty())
                <tr>
                    <td colspan="5" style="text-align:center; color:#9ca3af;">Tidak ada jasa servis.</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="section-title" style="margin-top: 18px;">Detail Sparepart</div>
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 44px;">No.</th>
                    <th>Deskripsi</th>
                    <th class="num" style="width: 110px;">Biaya</th>
                    <th class="num" style="width: 100px;">Diskon</th>
                    <th class="num" style="width: 120px;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp
                @foreach($service_order->products as $p)
                @php $subtotal = $p->pivot->qty * $p->pivot->harga; $diskon = (int) ($p->pivot->diskon ?? 0); @endphp
                <tr>
                    <td>{{ $no++ }}</td>
                    <td>{{ $p->nama_produk }} <span style="color: #9ca3af;">({{ $p->pivot->qty }} × Rp {{ number_format($p->pivot->harga, 0, ',', '.') }})</span></td>
                    <td class="num">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                    <td class="num">Rp {{ number_format($diskon, 0, ',', '.') }}</td>
                    <td class="num"><strong>Rp {{ number_format($subtotal - $diskon, 0, ',', '.') }}</strong></td>
                </tr>
                @endforeach
                @if($service_order->products->isEmpty())
                <tr>
                    <td colspan="5" style="text-align:center; color:#9ca3af;">Tidak ada sparepart.</td>
                </tr>
                @endif
            </tbody>
        </table>

        <div class="totals">
            @if($service_order->total_diskon > 0)
            <div class="totals-row">
                <span>Subtotal</span>
                <span>Rp {{ number_format($service_order->subtotal_sebelum_diskon, 0, ',', '.') }}</span>
            </div>
            <div class="totals-row discount">
                <span>Total Diskon</span>
                <span>− Rp {{ number_format($service_order->total_diskon, 0, ',', '.') }}</span>
            </div>
            @endif
            <div class="totals-row grand">
                <span>TOTAL BAYAR</span>
                <span>Rp {{ number_format($service_order->finalGrandTotal(), 0, ',', '.') }}</span>
            </div>
        </div>

        <p class="footer-note">Terima kasih telah mempercayakan kendaraan Anda kepada kami.</p>
    </div>
</body>
</html>

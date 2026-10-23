<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WO {{ $service_order->kode_servis }} - Sheet A4</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji";
            font-size: 12pt;
            color: #111827;
            margin: 0;
            background: #f3f4f6;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 12mm auto;
            background: #fff;
            padding: 14mm 16mm;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        @media print {
            body { background: #fff; }
            .page { margin: 0; box-shadow: none; width: auto; min-height: auto; }
            @page { size: A4 portrait; margin: 10mm; }
        }
        .header-shop { text-align: center; margin-bottom: 4px; }
        .brand { font-size: 19pt; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 4px 0; }
        .contact { font-size: 11pt; color: #374151; margin: 0; line-height: 1.45; }
        .dotline { border: none; border-top: 1px dotted #9ca3af; margin: 12px 0; }

        .watermark {
            position: absolute;
            left: 50%;
            top: 52%;
            transform: translate(-50%, -50%);
            opacity: 0.06;
            z-index: 0;
            pointer-events: none;
        }
        .watermark img {
            width: 180mm;
            max-width: 180mm;
            height: auto;
            transform: rotate(90deg);
            transform-origin: center;
        }

        .content { position: relative; z-index: 1; }
        table.meta3 { width: 100%; border-collapse: collapse; margin: 0; }
        table.meta3 td { vertical-align: top; padding: 0 8px 12px 0; font-size: 11pt; }
        table.meta3 tr:last-child td { padding-bottom: 0; }
        .meta3 .cell { width: 33.333%; }
        .meta3 .lbl { color: #4b5563; font-size: 9.5pt; text-transform: uppercase; letter-spacing: 0.04em; }
        .meta3 .val { font-weight: 800; color: #111827; margin-top: 2px; font-size: 11.5pt; }
        .meta-wrap { border: 1px dashed #111827; border-radius: 3mm; padding: 12px; }

        .section-title { margin: 10px 0 6px 0; font-size: 11pt; font-weight: 800; color: #111827; text-transform: uppercase; letter-spacing: 0.04em; }
        table.simple { width: 100%; border-collapse: collapse; font-size: 11pt; }
        table.simple th { text-align: left; border-bottom: 1px solid #111827; padding: 7px 5px; font-size: 9.5pt; text-transform: uppercase; letter-spacing: 0.04em; }
        table.simple th.qty { text-align: center; width: 48px; }
        table.simple th.price { text-align: right; width: 96px; }
        table.simple th.total { text-align: right; width: 102px; }
        table.simple td { padding: 6px 5px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        table.simple td.qty { text-align: center; }
        table.simple td.price, table.simple td.total { text-align: right; white-space: nowrap; }

        .box-note, .box-analysis { border: 1px dashed #111827; border-radius: 3mm; padding: 12px; font-size: 11pt; min-height: 18mm; line-height: 1.45; }
        .ttd { margin-top: 16px; display: table; width: 100%; table-layout: fixed; }
        .ttd .col { display: table-cell; width: 50%; vertical-align: top; text-align: center; padding: 0 6mm; }
        .ttd .label { font-size: 9.5pt; font-weight: 700; color: #111827; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 6px; text-align: center; }
        .ttd .box { width: 78mm; max-width: 100%; height: 18mm; border: 1px dashed #111827; border-radius: 4mm; margin: 0 auto; }
    </style>
</head>
<body>
<script>
(() => {
    // Auto-open print dialog (mirip struk thermal), tapi tunggu layout + gambar watermark siap.
    // Hindari loop print kalau user refresh/back-forward cache.
    const KEY = 'wo_sheet_a4_printed:' + location.pathname;
    const already = sessionStorage.getItem(KEY) === '1';
    if (already) return;

    const trigger = () => {
        sessionStorage.setItem(KEY, '1');
        setTimeout(() => window.print(), 250);
    };

    if (document.readyState === 'complete') {
        trigger();
        return;
    }

    window.addEventListener('load', () => {
        // Jika gambar watermark gagal load pun tetap bisa print.
        const img = document.querySelector('.watermark img');
        if (!img) return trigger();
        if (img.complete) return trigger();
        img.addEventListener('load', trigger, { once: true });
        img.addEventListener('error', trigger, { once: true });
    }, { once: true });
})();
</script>
@php
    $namaOutlet = $outlet?->nama ?: '77racing';
    $hpOutlet = trim((string) ($outlet?->no_hp ?? ''));
    $alamatOutlet = trim((string) ($outlet?->alamat ?? ''));
    $woNo = (string) ($service_order->kode_servis ?? '-');
    $woTgl = $service_order->tanggal_masuk ? \Illuminate\Support\Carbon::parse($service_order->tanggal_masuk)->format('d/m/Y') : '-';
    $alamatCustomer = trim((string) ($service_order->pelanggan?->alamat ?? ''));
    $noHpCustomer = (string) ($service_order->pelanggan?->no_hp ?? '-');
    $noPol = (string) ($service_order->kendaraan?->nomor_polisi ?? '-');
    $tipeMotor = trim(implode(' ', array_filter([
        (string) ($service_order->kendaraan?->merk ?? ''),
        (string) ($service_order->kendaraan?->tipe ?? ''),
    ])));
    $tipeMotor = $tipeMotor !== '' ? $tipeMotor : '-';
    $tahunMotor = (string) ($service_order->kendaraan?->tahun ?? '-');
    $sumberKey = (string) ($service_order->sumber_kedatangan ?? '');
    $sumberLabel = \App\Models\ServiceOrder::SUMBER_KEDATANGAN[$sumberKey] ?? ($sumberKey !== '' ? $sumberKey : '-');
    $kategoriLabel = method_exists($service_order, 'kategoriServiceDisplay') ? $service_order->kategoriServiceDisplay() : ((string) ($service_order->kategori_service ?? '-'));
    $keluhan = trim((string) ($service_order->keluhan ?? ''));
    $logoUrl = asset('images/logo.png');
@endphp

<div class="page">
    <div class="watermark" aria-hidden="true">
        <img src="{{ $logoUrl }}" alt="">
    </div>

    <div class="content">
        <div class="header-shop">
            <p class="brand">{{ strtoupper($namaOutlet) }}</p>
            @if($hpOutlet !== '')
                <p class="contact">No. HP/Telp.: {{ $hpOutlet }}</p>
            @endif
            @if($alamatOutlet !== '')
                <p class="contact">Alamat: {{ $alamatOutlet }}</p>
            @endif
        </div>

        <hr class="dotline">

        <div class="meta-wrap">
            <table class="meta3" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="cell">
                        <div class="lbl">Nomor WO</div>
                        <div class="val">{{ $woNo }}</div>
                    </td>
                    <td class="cell">
                        <div class="lbl">Tanggal</div>
                        <div class="val">{{ $woTgl }}</div>
                    </td>
                    <td class="cell">
                        <div class="lbl">Nama Customer</div>
                        <div class="val">{{ $service_order->pelanggan?->nama ?? '-' }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="cell">
                        <div class="lbl">Alamat</div>
                        <div class="val">{{ $alamatCustomer !== '' ? $alamatCustomer : '-' }}</div>
                    </td>
                    <td class="cell">
                        <div class="lbl">No HP</div>
                        <div class="val">{{ $noHpCustomer }}</div>
                    </td>
                    <td class="cell">
                        <div class="lbl">No Polisi</div>
                        <div class="val">{{ $noPol }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="cell">
                        <div class="lbl">Merk / Tipe</div>
                        <div class="val">{{ $tipeMotor }}</div>
                    </td>
                    <td class="cell">
                        <div class="lbl">Tahun Motor</div>
                        <div class="val">{{ $tahunMotor }}</div>
                    </td>
                    <td class="cell">
                        <div class="lbl">Sumber Kedatangan</div>
                        <div class="val">{{ $sumberLabel }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="cell">
                        <div class="lbl">Kategori Service</div>
                        <div class="val">{{ $kategoriLabel }}</div>
                    </td>
                    <td class="cell">
                        <div class="lbl">Mekanik</div>
                        <div class="val">{{ $service_order->mekanik?->nama ?? '-' }}</div>
                    </td>
                    <td class="cell">
                        <div class="lbl">Kilometer</div>
                        <div class="val">{{ $service_order->kilometer !== null ? number_format((int) $service_order->kilometer, 0, ',', '.') : '-' }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section-title">Pekerjaan</div>
        <table class="simple" cellspacing="0">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">No</th>
                    <th>Jasa</th>
                    <th class="qty">Qty</th>
                    <th class="price">Harga</th>
                    <th class="total">Total</th>
                </tr>
            </thead>
            <tbody>
                @php $totalPekerjaan = 0; @endphp
                @forelse($service_order->jasaServis as $i => $j)
                    @php
                        $q = (int) ($j->pivot->qty ?? 1);
                        $b = (int) ($j->pivot->biaya ?? 0);
                        $line = $q * $b;
                        $totalPekerjaan += $line;
                    @endphp
                    <tr>
                        <td style="text-align:center;">{{ $i + 1 }}</td>
                        <td>{{ $j->nama }}</td>
                        <td class="qty">{{ $q }}</td>
                        <td class="price">{{ number_format($b, 0, ',', '.') }}</td>
                        <td class="total">{{ number_format($line, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 10px 4px; color:#6b7280;">Tidak ada pekerjaan.</td>
                    </tr>
                @endforelse
                <tr>
                    <td colspan="4" style="border-bottom: 0; padding-top: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color:#111827; text-align:right;">
                        Total Pekerjaan
                    </td>
                    <td class="total" style="border-bottom: 0; padding-top: 8px; font-weight: 800;">
                        {{ number_format($totalPekerjaan, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="section-title" style="margin-top: 12px;">Sparepart</div>
        <table class="simple" cellspacing="0">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">No</th>
                    <th>Nama</th>
                    <th class="qty">Qty</th>
                    <th class="price">Harga</th>
                    <th class="total">Total</th>
                </tr>
            </thead>
            <tbody>
                @php $totalSparepart = 0; @endphp
                @forelse($service_order->products as $i => $p)
                    @php
                        $q = (int) ($p->pivot->qty ?? 0);
                        $h = (int) ($p->pivot->harga ?? 0);
                        $line = $q * $h;
                        $totalSparepart += $line;
                    @endphp
                    <tr>
                        <td style="text-align:center;">{{ $i + 1 }}</td>
                        <td>{{ $p->nama_produk }}</td>
                        <td class="qty">{{ $q }}</td>
                        <td class="price">{{ number_format($h, 0, ',', '.') }}</td>
                        <td class="total">{{ number_format($line, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="padding: 10px 4px; color:#6b7280;">Tidak ada sparepart.</td>
                    </tr>
                @endforelse
                <tr>
                    <td colspan="4" style="border-bottom: 0; padding-top: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color:#111827; text-align:right;">
                        Total Sparepart
                    </td>
                    <td class="total" style="border-bottom: 0; padding-top: 8px; font-weight: 800;">
                        {{ number_format($totalSparepart, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="section-title" style="margin-top: 12px;">Keluhan Konsumen</div>
        <div class="box-note">{{ $keluhan !== '' ? $keluhan : '' }}</div>

        <div class="section-title" style="margin-top: 12px;">Analisis Mekanik</div>
        <div class="box-analysis"></div>

        <div class="ttd">
            <div class="col">
                <div class="label">Nama Mekanik</div>
                <div class="box"></div>
            </div>
            <div class="col">
                <div class="label">Konsumen</div>
                <div class="box"></div>
            </div>
        </div>
    </div>
</div>
</body>
</html>


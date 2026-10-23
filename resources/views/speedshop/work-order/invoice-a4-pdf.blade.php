<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #111827; margin: 0; padding: 14mm 16mm; }
        .logo-wrap { text-align: center; margin: 0 0 10px 0; }
        .logo-wrap img { max-width: 42mm; width: 100%; height: auto; display: inline-block; vertical-align: middle; }
        .brand { font-size: 16pt; font-weight: bold; letter-spacing: -0.02em; margin: 0 0 4px 0; }
        .header-shop { text-align: center; margin-bottom: 4px; }
        .contact { font-size: 9pt; color: #374151; margin: 0; line-height: 1.45; }
        .dotline { border: none; border-top: 1px dotted #9ca3af; margin: 12px 0; }
        .watermark { position: fixed; left: 50%; top: 52%; transform: translate(-50%, -50%); opacity: 0.06; z-index: 0; }
        /* Rotasi watermark supaya orientasi atas-bawah (portrait) */
        .watermark img { width: 180mm; max-width: 180mm; height: auto; transform: rotate(90deg); transform-origin: center; }
        .page { position: relative; z-index: 1; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.meta td { vertical-align: top; padding: 2px 8px 2px 0; font-size: 9pt; }
        table.meta td.lbl { width: 38%; color: #4b5563; white-space: nowrap; }
        table.meta td.val { font-weight: 600; color: #111827; }
        table.meta2 { width: 100%; border-collapse: collapse; margin: 0 0 10px 0; }
        table.meta2 td { vertical-align: top; padding: 3px 8px 3px 0; font-size: 9pt; }
        table.meta2 td.lbl { width: 18%; color: #4b5563; white-space: nowrap; }
        table.meta2 td.val { width: 32%; font-weight: 600; color: #111827; }
        table.meta3 { width: 100%; border-collapse: collapse; margin: 0 0 10px 0; }
        table.meta3 td { vertical-align: top; padding: 0 8px 8px 0; font-size: 9pt; }
        .meta3 .cell { width: 33.333%; }
        .meta3 .lbl { color: #4b5563; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.04em; }
        .meta3 .val { font-weight: 800; color: #111827; margin-top: 2px; }
        .meta-wrap { border: 1px dashed #111827; border-radius: 3mm; padding: 12px; }
        .meta3 td { padding: 0 8px 12px 0; }
        .meta3 tr:last-child td { padding-bottom: 0; }
        .section-title { margin: 10px 0 6px 0; font-size: 9pt; font-weight: 800; color: #111827; text-transform: uppercase; letter-spacing: 0.04em; }
        table.simple { width: 100%; border-collapse: collapse; font-size: 9pt; }
        table.simple th { text-align: left; border-bottom: 1px solid #111827; padding: 6px 4px; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.04em; }
        table.simple th.qty { text-align: center; width: 44px; }
        table.simple th.price { text-align: right; width: 86px; }
        table.simple th.total { text-align: right; width: 92px; }
        table.simple td { padding: 5px 4px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        table.simple td.qty { text-align: center; }
        table.simple td.price, table.simple td.total { text-align: right; white-space: nowrap; }
        .panel { padding: 0; margin: 6px 0 10px 0; }
        .panel .section-title { margin-top: 0; }
        .box-note { border: 1px dashed #111827; border-radius: 3mm; padding: 12px; font-size: 9pt; min-height: 16mm; }
        .box-analysis { border: 1px dashed #111827; border-radius: 3mm; padding: 12px; min-height: 16mm; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 9pt; }
        table.items th { text-align: left; border-bottom: 1px solid #111827; padding: 6px 4px; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.04em; }
        table.items th.qty { text-align: center; width: 44px; }
        table.items th.price { text-align: right; width: 82px; }
        table.items th.disc { text-align: right; width: 82px; }
        table.items th.total { text-align: right; width: 86px; }
        table.items td { padding: 5px 4px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        table.items td.qty { text-align: center; }
        table.items td.price { text-align: right; white-space: nowrap; }
        table.items td.disc { text-align: right; white-space: nowrap; }
        table.items td.total { text-align: right; white-space: nowrap; }
        .summary { width: 240px; margin-left: auto; margin-top: 12px; font-size: 9pt; }
        .summary table { width: 100%; border-collapse: collapse; }
        .summary td { padding: 4px 0; }
        .summary td:first-child { color: #4b5563; }
        .summary td:last-child { text-align: right; font-weight: 600; }
        .summary .grand td { font-size: 11pt; font-weight: bold; padding-top: 8px; border-top: 2px solid #111827; }
        .footer { margin-top: 18px; text-align: left; font-size: 7.5pt; color: #4b5563; line-height: 1.4; text-transform: uppercase; letter-spacing: 0.02em; }
        .printed { font-size: 8pt; color: #6b7280; margin-top: 10px; }
        .ttd { margin-top: 16px; display: table; width: 100%; table-layout: fixed; }
        /* Beri jarak antar kolom supaya box tidak "nyatu" */
        .ttd .col { display: table-cell; width: 50%; vertical-align: top; text-align: center; padding: 0 6mm; }
        .ttd .label { font-size: 8pt; font-weight: 700; color: #111827; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 6px; text-align: center; }
        /* Kurangi lebar agar dua box tidak "nempel" di tengah */
        .ttd .box { width: 78mm; max-width: 100%; height: 18mm; border: 1px dashed #111827; border-radius: 4mm; margin: 0 auto; }
        .ttd .spacer { height: 6mm; }
    </style>
</head>
<body>
@php
    $pdfMode = (string) ($pdfMode ?? '');
    $isSheet = $pdfMode === 'sheet';
    $isWaInvoice = $pdfMode === 'wa_invoice';
    $metodeLabel = $service_order->pembayaranDisplayLabel();
    $metodeRaw = strtolower((string) ($service_order->invoice?->metode_pembayaran ?? ''));
    $splitPayRows = $service_order->invoice?->pembayaran_split;
    if (is_string($splitPayRows)) {
        $splitPayRows = json_decode($splitPayRows, true);
    }
    $isSplitInvoice = ($metodeRaw === 'split') && is_array($splitPayRows) && $splitPayRows !== [];
    $noInvoice = $service_order->invoice?->kode_invoice ?? ('WO-'.$service_order->kode_servis);
    $tgl = $service_order->tanggal_selesai ?? $service_order->invoice?->tanggal ?? $service_order->tanggal_masuk;
    $tglStr = $tgl ? \Illuminate\Support\Carbon::parse($tgl)->format('d/m/Y') : '-';
    $namaOutlet = $outlet?->nama ?: '77racing';
    $hpOutlet = trim((string) ($outlet?->no_hp ?? ''));
    $alamatOutlet = trim((string) ($outlet?->alamat ?? ''));
    $diskonInv = (int) ($service_order->invoice?->diskon ?? $service_order->total_diskon);
    $grand = $service_order->finalGrandTotal();
    $subtotalBefore = (int) ($service_order->subtotal_sebelum_diskon ?? ($grand + $diskonInv));
    $globalInvoice = app(\App\Services\InvoiceSettingsService::class)->get();
    $chargeMode = (string) ($service_order->invoice?->charge_mode ?? ($globalInvoice['invoice_charge_mode'] ?? 'percent'));
    $chargePct = (float) ($service_order->invoice?->charge_percent ?? ($globalInvoice['invoice_charge_percent'] ?? 0));
    $chargeRp = (int) ($service_order->invoice?->charge_rupiah ?? ($globalInvoice['invoice_charge_rupiah'] ?? 0));
    $ppnMode = (string) ($service_order->invoice?->ppn_mode ?? ($globalInvoice['invoice_ppn_mode'] ?? 'percent'));
    $ppnPct = (float) ($service_order->invoice?->ppn_percent ?? ($globalInvoice['invoice_ppn_percent'] ?? 0));
    $ppnRp = (int) ($service_order->invoice?->ppn_rupiah ?? ($globalInvoice['invoice_ppn_rupiah'] ?? 0));
    $chargeAmt = (int) ($service_order->invoice?->charge_amount ?? 0);
    $ppnAmt = (int) ($service_order->invoice?->ppn_amount ?? 0);
    $catatan = trim((string) ($service_order->invoice?->catatan ?? ''));
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
    $__logoFile = public_path('images/logo.png');
    // Catatan performa: Dompdf cenderung lambat saat decode base64 + transform gambar besar (watermark).
    // Untuk watermark di WO sheet / WA invoice, pakai file lokal langsung agar lebih ringan.
    $__logoDataUri = is_file($__logoFile)
        ? 'data:image/webp;base64,'.base64_encode((string) file_get_contents($__logoFile))
        : '';
    $__logoFileUri = is_file($__logoFile) ? ('file://'.$__logoFile) : '';
@endphp

    @if(($isSheet || $isWaInvoice) && $__logoFileUri !== '')
        <div class="watermark" aria-hidden="true">
            <img src="{{ $__logoFileUri }}" alt="">
        </div>
    @endif

    <div class="page">
        <div class="header-shop">
            @if((! $isSheet && ! $isWaInvoice) && $__logoDataUri !== '')
            <div class="logo-wrap">
                <img src="{{ $__logoDataUri }}" alt="">
            </div>
            @endif
            <p class="brand">{{ strtoupper($namaOutlet) }}</p>
            @if($hpOutlet !== '')
                <p class="contact">No. HP/Telp.: {{ $hpOutlet }}</p>
            @endif
            @if($alamatOutlet !== '')
                <p class="contact">Alamat: {{ $alamatOutlet }}</p>
            @endif
        </div>

    <hr class="dotline">

    @if($isSheet || $isWaInvoice)
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
                <th style="width:34px; text-align:center;">No</th>
                <th>Jasa</th>
                <th class="qty">Qty</th>
                <th class="price">Harga</th>
                @if($isWaInvoice)
                    <th class="price">Diskon</th>
                @endif
                <th class="total">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $totalPekerjaan = 0; @endphp
            @forelse($service_order->jasaServis as $i => $j)
                @php
                    $q = (int) ($j->pivot->qty ?? 1);
                    $b = (int) ($j->pivot->biaya ?? 0);
                    $sub = ($q * $b);
                    $d = (int) ($j->pivot->diskon ?? 0);
                    $d = max(0, min($d, $sub));
                    $line = $sub - ($isWaInvoice ? $d : 0);
                    $totalPekerjaan += (int) $line;
                @endphp
                <tr>
                    <td class="qty">{{ $i + 1 }}</td>
                    <td>{{ $j->nama }}</td>
                    <td class="qty">{{ $q }}</td>
                    <td class="price">{{ number_format($b, 0, ',', '.') }}</td>
                    @if($isWaInvoice)
                        <td class="price">{{ $d > 0 ? number_format($d, 0, ',', '.') : '-' }}</td>
                    @endif
                    <td class="total">{{ number_format($line, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $isWaInvoice ? 6 : 5 }}" style="color:#6b7280;">—</td></tr>
            @endforelse
            <tr>
                <td colspan="{{ $isWaInvoice ? 5 : 4 }}" style="border-bottom:0; padding-top:8px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; text-align:right;">
                    Total Pekerjaan
                </td>
                <td class="total" style="border-bottom:0; padding-top:8px; font-weight:800;">
                    {{ number_format($totalPekerjaan, 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">Sparepart</div>
    <table class="simple" cellspacing="0">
        <thead>
            <tr>
                <th style="width:34px; text-align:center;">No</th>
                <th>Nama</th>
                <th class="qty">Qty</th>
                <th class="price">Harga</th>
                @if($isWaInvoice)
                    <th class="price">Diskon</th>
                @endif
                <th class="total">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $totalSparepart = 0; @endphp
            @forelse($service_order->products as $i => $p)
                @php
                    $q = (int) ($p->pivot->qty ?? 0);
                    $h = (int) ($p->pivot->harga ?? 0);
                    $sub = ($q * $h);
                    $d = (int) ($p->pivot->diskon ?? 0);
                    $d = max(0, min($d, $sub));
                    $line = $sub - ($isWaInvoice ? $d : 0);
                    $totalSparepart += (int) $line;
                @endphp
                <tr>
                    <td class="qty">{{ $i + 1 }}</td>
                    <td>{{ $p->nama_produk }}</td>
                    <td class="qty">{{ $q }}</td>
                    <td class="price">{{ number_format($h, 0, ',', '.') }}</td>
                    @if($isWaInvoice)
                        <td class="price">{{ $d > 0 ? number_format($d, 0, ',', '.') : '-' }}</td>
                    @endif
                    <td class="total">{{ number_format($line, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $isWaInvoice ? 6 : 5 }}" style="color:#6b7280;">—</td></tr>
            @endforelse
            <tr>
                <td colspan="{{ $isWaInvoice ? 5 : 4 }}" style="border-bottom:0; padding-top:8px; font-weight:800; text-transform:uppercase; letter-spacing:0.04em; text-align:right;">
                    Total Sparepart
                </td>
                <td class="total" style="border-bottom:0; padding-top:8px; font-weight:800;">
                    {{ number_format($totalSparepart, 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    @if($isWaInvoice && ! $isSheet)
        @php
            // Pastikan subtotal sesuai jumlah baris yang tercetak.
            $subtotalBeforeCalc = (int) $totalPekerjaan + (int) $totalSparepart;
        @endphp
        <div class="section-title">Total &amp; Pembayaran</div>
        <table class="simple" cellspacing="0">
            <tbody>
                @if($diskonInv > 0)
                    <tr>
                        <td style="padding-top:8px;">Subtotal</td>
                        <td class="total" style="padding-top:8px;">{{ number_format($subtotalBeforeCalc, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>Diskon</td>
                        <td class="total">{{ number_format($diskonInv, 0, ',', '.') }}</td>
                    </tr>
                @endif
                <tr>
                    <td>Charge{{ $chargeMode === 'percent' ? ' ('.rtrim(rtrim(number_format($chargePct, 2, '.', ''), '0'), '.').'%)' : '' }}</td>
                    <td class="total">{{ number_format($chargeAmt, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>PPN{{ $ppnMode === 'percent' ? ' ('.rtrim(rtrim(number_format($ppnPct, 2, '.', ''), '0'), '.').'%)' : '' }}</td>
                    <td class="total">{{ number_format($ppnAmt, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td style="padding-top:8px; font-weight:800;">Total</td>
                    <td class="total" style="padding-top:8px; font-weight:800;">{{ number_format($grand, 0, ',', '.') }}</td>
                </tr>
                @if($isSplitInvoice)
                    @foreach($splitPayRows as $row)
                        @php
                            $m = strtolower(trim((string) ($row['metode'] ?? '')));
                            $lineAmt = (int) ($row['jumlah'] ?? 0);
                            $lineLabel = match ($m) {
                                'cash', 'tunai' => 'Tunai',
                                'transfer' => 'Transfer',
                                'qris' => 'QRIS',
                                'edc' => 'EDC',
                                default => $m !== '' ? ucfirst($m) : '-',
                            };
                        @endphp
                        @continue($lineAmt <= 0)
                        <tr>
                            <td style="padding-top:4px;">{{ $lineLabel }}</td>
                            <td class="total" style="padding-top:4px;">{{ number_format($lineAmt, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="2" style="padding-top:8px; font-weight:600;">Metode pembayaran: {{ $metodeLabel }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif

    <div class="section-title">Keluhan Konsumen</div>
    <div class="box-note">{{ $keluhan !== '' ? $keluhan : '—' }}</div>

    @if($isSheet)
        <div class="section-title">Analisis Mekanik</div>
        <div class="box-analysis"></div>

        <div class="ttd">
            <div class="col">
                <div class="label">NAMA MEKANIK</div>
                <div class="box"></div>
            </div>
            <div class="col">
                <div class="label">KONSUMEN</div>
                <div class="box"></div>
            </div>
        </div>
    @endif

    @else
    <table class="meta" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width:50%; padding-right:12px; vertical-align:top;">
                <div style="margin-bottom:4px;"><span style="color:#4b5563;">Customer :</span> <strong>{{ $service_order->pelanggan?->nama ?? '-' }}</strong></div>
                <div style="margin-bottom:4px;"><span style="color:#4b5563;">No. Invoice :</span> <strong>{{ $noInvoice }}</strong></div>
                <div><span style="color:#4b5563;">Nama Mekanik :</span> <strong>{{ $service_order->mekanik?->nama ?? '-' }}</strong></div>
            </td>
            <td style="width:50%; vertical-align:top;">
                <div style="margin-bottom:4px;"><span style="color:#4b5563;">No. HP :</span> <strong>{{ $service_order->pelanggan?->no_hp ?? '-' }}</strong></div>
                <div style="margin-bottom:4px;"><span style="color:#4b5563;">Tgl. :</span> <strong>{{ $tglStr }}</strong></div>
                <div><span style="color:#4b5563;">Catatan :</span> <strong>{{ $catatan !== '' ? $catatan : '—' }}</strong></div>
            </td>
        </tr>
    </table>

    <hr class="dotline">

    <table class="items" cellspacing="0">
        <thead>
            <tr>
                <th>Item</th>
                <th class="qty">Qty</th>
                <th class="price">Harga</th>
                <th class="disc">Diskon</th>
                <th class="total">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $subtotalBeforeCalc = 0; @endphp
            @foreach($service_order->jasaServis as $j)
                @php
                    $q = (int) ($j->pivot->qty ?? 1);
                    $b = (int) ($j->pivot->biaya ?? 0);
                    $d = (int) ($j->pivot->diskon ?? 0);
                    $line = ($q * $b) - $d;
                    $subtotalBeforeCalc += (int) $line;
                @endphp
                <tr>
                    <td>{{ $j->nama }}</td>
                    <td class="qty">{{ $q }}</td>
                    <td class="price">{{ number_format($q * $b, 0, ',', '.') }}</td>
                    <td class="disc">{{ $d > 0 ? number_format($d, 0, ',', '.') : '-' }}</td>
                    <td class="total">{{ number_format($line, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            @foreach($service_order->products as $p)
                @php
                    $q = (int) ($p->pivot->qty ?? 0);
                    $h = (int) ($p->pivot->harga ?? 0);
                    $d = (int) ($p->pivot->diskon ?? 0);
                    $line = ($q * $h) - $d;
                    $subtotalBeforeCalc += (int) $line;
                @endphp
                <tr>
                    <td>{{ $p->nama_produk }}</td>
                    <td class="qty">{{ $q }}</td>
                    <td class="price">{{ number_format($q * $h, 0, ',', '.') }}</td>
                    <td class="disc">{{ $d > 0 ? number_format($d, 0, ',', '.') : '-' }}</td>
                    <td class="total">{{ number_format($line, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <table cellspacing="0">
            @if($diskonInv > 0)
            <tr>
                <td>Subtotal</td>
                <td>{{ number_format($subtotalBeforeCalc, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Diskon</td>
                <td>{{ number_format($diskonInv, 0, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="grand">
                <td>Total</td>
                <td>{{ number_format($grand, 0, ',', '.') }}</td>
            </tr>
            @if($isSplitInvoice)
                <tr>
                    <td colspan="2" style="padding-top:8px; font-weight:700;">Pembayaran (split)</td>
                </tr>
                @foreach($splitPayRows as $row)
                    @php
                        $m = strtolower(trim((string) ($row['metode'] ?? '')));
                        $lineAmt = (int) ($row['jumlah'] ?? 0);
                        $lineLabel = match ($m) {
                            'cash', 'tunai' => 'Tunai',
                            'transfer' => 'Transfer',
                            'qris' => 'QRIS',
                            'edc' => 'EDC',
                            default => $m !== '' ? ucfirst($m) : '-',
                        };
                    @endphp
                    @continue($lineAmt <= 0)
                    <tr>
                        <td style="padding-top:4px;">{{ $lineLabel }}</td>
                        <td style="padding-top:4px; text-align:right; font-weight:600;">{{ number_format($lineAmt, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @else
            <tr>
                <td colspan="2" style="padding-top:8px; font-weight:600;">Metode pembayaran: {{ $metodeLabel }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="ttd">
        <div class="col">
            <div class="label">NAMA MEKANIK</div>
            <div class="box"></div>
        </div>
        <div class="col">
            <div class="label">KONSUMEN</div>
            <div class="box"></div>
        </div>
    </div>

    <p class="footer" style="text-align:left;">
        <strong>GARANSI PEKERJAAN :</strong><br>
        - SERVICE LENGKAP/TURN UP = 500KM/1MINGGU<br>
        - HEAVY REPAIR/TURUN MESIN = 1000KM/1 BULAN
    </p>
    @endif
</div>
</body>
</html>

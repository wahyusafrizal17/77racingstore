<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk {{ $service_order->invoice?->kode_invoice ?? $service_order->kode_servis }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #111;
            margin: 0;
            padding: 0;
            line-height: 1.35;
        }
        .paper {
            /* Beberapa printer thermal punya margin kiri "non-printable". */
            width: 72mm;
            margin: 0 auto;
            padding: 2mm 2.5mm 4mm 4mm; /* extra left padding */
        }
        @media print {
            @page { size: 80mm auto; margin: 0; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .paper { width: 72mm; padding: 2mm 2.5mm 4mm 4mm; margin: 0; }
        }
        .muted { color: #444; font-size: 10px; }
        .center { text-align: center; }
        .invoice-logo {
            display: block;
            max-width: 52mm;
            width: 100%;
            height: auto;
            margin: 0 auto 8px;
            object-fit: contain;
        }
        /* Supaya lebih cocok untuk printer thermal (hitam-putih & kontras) */
        .invoice-logo.thermal {
            filter: grayscale(100%) contrast(140%);
        }
        .dash {
            border: none;
            border-top: 1px dashed #333;
            margin: 8px 0;
        }
        .title-shop { font-weight: 700; font-size: 13px; margin: 0 0 4px 0; }
        .meta-row { display: flex; justify-content: space-between; gap: 8px; margin: 2px 0; font-size: 10px; }
        .meta-row span:first-child { flex-shrink: 0; }
        .meta-row span:last-child { text-align: right; word-break: break-word; }
        .sec-title { text-align: center; font-weight: 700; letter-spacing: 0.06em; margin: 10px 0 6px; font-size: 11px; }
        .item-name { font-weight: 600; margin-top: 6px; word-break: break-word; }
        .item-line { display: flex; justify-content: space-between; font-size: 10px; margin-top: 2px; }
        .tot-line { display: flex; justify-content: space-between; margin-top: 6px; font-weight: 700; }
        .num { text-align: right; white-space: nowrap; }
    </style>
</head>
<body>
@php
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
    $alamatCustomer = trim((string) ($service_order->pelanggan?->alamat ?? ''));
    $noHpCustomer = trim((string) ($service_order->pelanggan?->no_hp ?? ''));
    $k = $service_order->kendaraan;
    $tipeMotor = $k ? trim(implode(' ', array_filter([(string) ($k->merk ?? ''), (string) ($k->tipe ?? '')]))) : '';
    $tipeMotor = $tipeMotor !== '' ? $tipeMotor : '-';
    $noPolisi = $k?->nomor_polisi ?? '-';
    $tahunMotor = (string) ($k?->tahun ?? '-');
    $kmStr = ($service_order->kilometer !== null && $service_order->kilometer !== '')
        ? number_format((int) $service_order->kilometer, 0, ',', '.')
        : '-';
    $kategoriService = method_exists($service_order, 'kategoriServiceDisplay')
        ? $service_order->kategoriServiceDisplay()
        : ((string) ($service_order->kategori_service ?? '-'));
    $totalJasa = 0;
    $diskonJasa = 0;
    foreach ($service_order->jasaServis as $j) {
        $q = (int) ($j->pivot->qty ?? 1);
        $b = (int) ($j->pivot->biaya ?? 0);
        $d = (int) ($j->pivot->diskon ?? 0);
        $totalJasa += ($q * $b) - $d;
        $diskonJasa += $d;
    }
    $totalPart = 0;
    $diskonPart = 0;
    foreach ($service_order->products as $p) {
        $q = (int) ($p->pivot->qty ?? 0);
        $h = (int) ($p->pivot->harga ?? 0);
        $d = (int) ($p->pivot->diskon ?? 0);
        $totalPart += ($q * $h) - $d;
        $diskonPart += $d;
    }
    $grand = $service_order->finalGrandTotal();
    $diskonTotal = (int) ($service_order->invoice?->diskon ?? ($diskonJasa + $diskonPart));
    $subtotalBefore = (int) (($totalJasa + $totalPart) + $diskonTotal);
    $baseTotal = (int) ($totalJasa + $totalPart);
    $globalInvoice = app(\App\Services\InvoiceSettingsService::class)->get();
    $chargeMode = (string) ($service_order->invoice?->charge_mode ?? ($globalInvoice['invoice_charge_mode'] ?? 'percent'));
    $chargePct = (float) ($service_order->invoice?->charge_percent ?? ($globalInvoice['invoice_charge_percent'] ?? 0));
    $chargeRp = (int) ($service_order->invoice?->charge_rupiah ?? ($globalInvoice['invoice_charge_rupiah'] ?? 0));
    $ppnMode = (string) ($service_order->invoice?->ppn_mode ?? ($globalInvoice['invoice_ppn_mode'] ?? 'percent'));
    $ppnPct = (float) ($service_order->invoice?->ppn_percent ?? ($globalInvoice['invoice_ppn_percent'] ?? 0));
    $ppnRp = (int) ($service_order->invoice?->ppn_rupiah ?? ($globalInvoice['invoice_ppn_rupiah'] ?? 0));
    $chargeAmt = (int) ($service_order->invoice?->charge_amount ?? 0);
    $ppnAmt = (int) ($service_order->invoice?->ppn_amount ?? 0);
    // Fallback invoice lama: pakai nilai tersimpan / turunan grand_total, bukan aturan minimal baru.
    if ($chargeAmt <= 0 && $service_order->invoice) {
        $chargeAmt = \App\Services\InvoiceSettingsService::resolveChargeAmountForInvoice(
            $service_order->invoice,
            $baseTotal,
        );
    } elseif ($chargeAmt <= 0) {
        $chargeAmt = \App\Services\InvoiceSettingsService::calculateChargeAmount(
            $baseTotal,
            $chargeMode,
            $chargePct,
            $chargeRp,
        );
    }
    if ($ppnAmt <= 0) {
        $ppnAmt = $ppnMode === 'rupiah'
            ? (int) $ppnRp
            : (int) round(($baseTotal * $ppnPct) / 100);
    }
    $__logoFile = public_path('images/logo.png');
    $__logoDataUri = is_file($__logoFile)
        ? 'data:image/webp;base64,'.base64_encode((string) file_get_contents($__logoFile))
        : '';
@endphp

    <div class="paper">
    <div class="center">
        @if($__logoDataUri !== '')
            <img src="{{ $__logoDataUri }}" alt="" class="invoice-logo thermal" width="200" height="60" loading="eager" decoding="async">
        @endif
        <p class="title-shop">{{ $namaOutlet }}</p>
        @if($alamatOutlet !== '')
            <p class="muted" style="margin:0;">{{ $alamatOutlet }}</p>
        @endif
        @if($hpOutlet !== '')
            <p class="muted" style="margin:2px 0 0;">Telp/WA: {{ $hpOutlet }}</p>
        @endif
    </div>
    <hr class="dash">

    <div class="meta-row"><span>No Invoice</span><span>{{ $noInvoice }}</span></div>
    <div class="meta-row"><span>Tanggal</span><span>{{ $tglStr }}</span></div>
    <div class="meta-row"><span>Nama Customer</span><span>{{ $service_order->pelanggan?->nama ?? '-' }}</span></div>
    <div class="meta-row"><span>Alamat</span><span>{{ $alamatCustomer !== '' ? $alamatCustomer : '-' }}</span></div>
    <div class="meta-row"><span>No Hp</span><span>{{ $noHpCustomer !== '' ? $noHpCustomer : '-' }}</span></div>
    <div class="meta-row"><span>No Polisi</span><span>{{ $noPolisi }}</span></div>
    <div class="meta-row"><span>Merk / Tipe</span><span>{{ $tipeMotor }}</span></div>
    <div class="meta-row"><span>Tahun Motor</span><span>{{ $tahunMotor }}</span></div>
    <div class="meta-row"><span>Kategori Service</span><span>{{ $kategoriService }}</span></div>
    <div class="meta-row"><span>Mekanik</span><span>{{ $service_order->mekanik?->nama ?? '-' }}</span></div>
    <div class="meta-row"><span>Kilometer</span><span>{{ $kmStr }}</span></div>
    <hr class="dash">

    @if($service_order->jasaServis->isNotEmpty())
    <div class="sec-title">JASA SERVICE</div>
    @foreach($service_order->jasaServis as $j)
        @php
            $q = (int) ($j->pivot->qty ?? 1);
            $b = (int) ($j->pivot->biaya ?? 0);
            $d = (int) ($j->pivot->diskon ?? 0);
            $line = ($q * $b) - $d;
        @endphp
        <div class="item-name">{{ $j->nama }}</div>
        <div class="item-line">
            <span>{{ $q }} x {{ number_format($b, 0, ',', '.') }}</span>
            <span class="num">{{ number_format($line, 0, ',', '.') }}</span>
        </div>
        @if($d > 0)
        <div class="item-line muted">
            <span>Diskon</span>
            <span class="num">-{{ number_format($d, 0, ',', '.') }}</span>
        </div>
        @endif
    @endforeach
    <div class="tot-line">
        <span>Total layanan</span>
        <span class="num">{{ number_format($totalJasa, 0, ',', '.') }}</span>
    </div>
    <hr class="dash">
    @endif

    @if($service_order->products->isNotEmpty())
    <div class="sec-title">SPARE PART</div>
    @foreach($service_order->products as $p)
        @php
            $q = (int) ($p->pivot->qty ?? 0);
            $h = (int) ($p->pivot->harga ?? 0);
            $d = (int) ($p->pivot->diskon ?? 0);
            $line = ($q * $h) - $d;
        @endphp
        <div class="item-name">{{ $p->nama_produk }}</div>
        <div class="item-line">
            <span>{{ $q }} pcs x {{ number_format($h, 0, ',', '.') }}</span>
            <span class="num">{{ number_format($line, 0, ',', '.') }}</span>
        </div>
        @if($d > 0)
        <div class="item-line muted">
            <span>Diskon</span>
            <span class="num">-{{ number_format($d, 0, ',', '.') }}</span>
        </div>
        @endif
    @endforeach
    <div class="tot-line">
        <span>Total spare part</span>
        <span class="num">{{ number_format($totalPart, 0, ',', '.') }}</span>
    </div>
    <hr class="dash">
    @endif

    <div class="meta-row"><span>Mekanik</span><span>{{ $service_order->mekanik?->nama ?? '-' }}</span></div>
    @if($isSplitInvoice)
        <div class="sec-title" style="margin-top:6px;">PEMBAYARAN</div>
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
            <div class="meta-row"><span>{{ $lineLabel }}</span><span class="num">{{ number_format($lineAmt, 0, ',', '.') }}</span></div>
        @endforeach
    @else
        <div class="meta-row"><span>Metode bayar</span><span>{{ $metodeLabel }}</span></div>
    @endif
    @if($diskonTotal > 0)
        <div class="meta-row"><span>Subtotal</span><span class="num">{{ number_format($subtotalBefore, 0, ',', '.') }}</span></div>
        <div class="meta-row"><span>Diskon</span><span class="num">-{{ number_format($diskonTotal, 0, ',', '.') }}</span></div>
    @endif
    <hr class="dash">
    <div class="meta-row">
        <span>
            Charge{{ $chargeMode === 'percent' ? ' ('.rtrim(rtrim(number_format($chargePct, 2, '.', ''), '0'), '.').'%)' : '' }}
        </span>
        <span class="num">{{ number_format($chargeAmt, 0, ',', '.') }}</span>
    </div>
    <div class="meta-row">
        <span>
            PPN{{ $ppnMode === 'percent' ? ' ('.rtrim(rtrim(number_format($ppnPct, 2, '.', ''), '0'), '.').'%)' : '' }}
        </span>
        <span class="num">{{ number_format($ppnAmt, 0, ',', '.') }}</span>
    </div>
    <div class="tot-line" style="font-size:12px; margin-top:8px;">
        <span>TOTAL</span>
        <span class="num">{{ number_format($grand, 0, ',', '.') }}</span>
    </div>
    <hr class="dash">

    @php $cat = trim((string) ($service_order->invoice?->catatan ?? '')); @endphp
    @if($cat !== '')
    <p class="muted" style="margin:8px 0 0;">*{{ $cat }}</p>
    @endif
    <p class="center muted" style="margin:8px 0 0; font-size:9px; letter-spacing:0.02em;">
        GARANSI SERVICE 1 MINGGU, TUNJUKKAN INVOICE INI UNTUK MENDAPATKAN DISKON SERVICE SELANJUTNYA RP.12.500
    </p>
    <hr class="dash">
    <p class="center muted" style="margin:8px 0 0; font-size:9px; letter-spacing:0.02em;">
        Terima kasih
    </p>
    <p class="center muted" style="margin:4px 0 0; font-size:9px; letter-spacing:0.02em;">
        — 77racing Speedshop —
    </p>
    </div>
    <script>
    (function () {
        function triggerPrint() {
            window.print();
        }
        if (document.readyState === 'complete') {
            setTimeout(triggerPrint, 100);
        } else {
            window.addEventListener('load', function () {
                setTimeout(triggerPrint, 150);
            });
        }
        window.addEventListener('afterprint', function () {
            window.close();
        });
    })();
    </script>
</body>
</html>

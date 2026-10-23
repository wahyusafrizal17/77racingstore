{{-- Gaya dokumen cetak/PDF 77racing (referensi: cetak-pdf). Font DejaVu untuk DomPDF. --}}
<style>
  *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

  :root {
    --red:      #8CFA01;
    --red-ink:  #3d6b00;
    --red-soft: #f3fde0;
    --gray-50:  #FAFAFA;
    --gray-100: #F5F5F5;
    --gray-200: #EEEEEE;
    --gray-300: #E0E0E0;
    --gray-400: #BDBDBD;
    --gray-500: #9E9E9E;
    --gray-600: #757575;
    --gray-700: #616161;
    --gray-800: #424242;
    --gray-900: #212121;
    --orange-bg: #FFF3E0;
    --orange:    #E65100;
  }

  body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 12px;
    color: var(--gray-900);
    background: #fff;
    margin: 0;
    padding: 0;
  }

  body.pdf-screen {
    background: #E8E8E8;
    padding: 20px 16px 40px;
    min-height: 100vh;
  }

  body.pdf-screen .doc {
    max-width: 780px;
    margin: 0 auto;
    border-radius: 12px;
    box-shadow: 0 4px 24px rgba(0,0,0,0.10);
    overflow: hidden;
  }

  .print-bar {
    width: 100%;
    max-width: 780px;
    margin: 0 auto 16px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
  }

  .btn-print {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--red);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 10px 20px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    letter-spacing: 0.2px;
  }

  .btn-print svg { width: 16px; height: 16px; }

  .doc {
    width: 100%;
    background: white;
  }

  .doc-accent {
    height: 5px;
    background: var(--red);
  }

  .doc-body {
    padding: 36px 40px 40px;
  }

  .doc-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 28px;
    padding-bottom: 24px;
    border-bottom: 1.5px solid var(--gray-200);
  }

  .doc-brand { display: flex; flex-direction: column; gap: 4px; }

  .doc-brand-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 2px;
  }

  /* Logo: public/images/logo.png (DomPDF: public_path) */
  .logo-mark {
    width: 40px;
    height: 40px;
    background: #fff;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
  }

  .logo-mark img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
    padding: 3px;
  }

  .logo-name {
    font-size: 16px;
    font-weight: 800;
    color: var(--gray-900);
    letter-spacing: -0.2px;
  }

  .doc-sub {
    font-size: 11px;
    color: var(--gray-500);
    letter-spacing: 0.3px;
  }

  .doc-title-block { margin-top: 12px; }

  .doc-title {
    font-size: 26px;
    font-weight: 800;
    color: var(--red-ink);
    letter-spacing: -0.5px;
    line-height: 1;
  }

  .doc-meta {
    text-align: right;
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding-top: 4px;
  }

  .doc-inv-no {
    font-size: 15px;
    font-weight: 700;
    color: var(--gray-900);
    letter-spacing: 0.3px;
  }

  .doc-meta-row {
    font-size: 11px;
    color: var(--gray-600);
  }

  .doc-meta-row span {
    font-weight: 600;
    color: var(--gray-800);
  }

  .status-chip {
    display: inline-block;
    align-self: flex-end;
    background: #E8F5E9;
    color: #2E7D32;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    padding: 4px 10px;
    border-radius: 20px;
    border: 1px solid #C8E6C9;
    margin-top: 4px;
  }

  .status-chip.warn {
    background: #FFF8E1;
    color: #F57F17;
    border-color: #FFE082;
  }

  .status-chip.danger {
    background: #FFEBEE;
    color: #C62828;
    border-color: #FFCDD2;
  }

  /* Kartu info grid (Stock IN, dll.) */
  .pdf-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 12px;
  }

  .info-card {
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: 10px;
    padding: 14px 18px;
  }

  .info-card.full { grid-column: 1 / -1; }

  .info-label {
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--gray-500);
    margin-bottom: 6px;
  }

  .info-name {
    font-size: 13px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 2px;
  }

  .info-detail {
    font-size: 11px;
    color: var(--gray-600);
    line-height: 1.6;
  }

  /* Blok info lama (border satu, kolom table) — transaksi warehouse, dll. */
  .info-section {
    border: 1px solid var(--gray-200);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 12px;
  }

  .info-section .info-row { display: table; width: 100%; }
  .info-section .info-col {
    display: table-cell;
    padding: 14px 18px;
    width: 50%;
    vertical-align: top;
    background: var(--gray-50);
  }
  .info-section .info-col + .info-col { border-left: 1px solid var(--gray-200); }

  .info-col-label {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--gray-500);
    margin-bottom: 6px;
  }

  .info-col-value {
    font-size: 13px;
    font-weight: 700;
    color: var(--gray-900);
  }

  .info-col-sub {
    font-size: 11px;
    color: var(--gray-600);
    margin-top: 2px;
    line-height: 1.5;
  }

  .reject-section {
    border: 1px solid #FFCDD2;
    border-radius: 10px;
    background: #FFF0F0;
    padding: 14px 18px;
    margin-bottom: 14px;
  }

  .reject-label {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--red-ink);
    margin-bottom: 4px;
  }

  .reject-value { font-size: 12px; font-weight: 700; color: #B71C1C; }
  .reject-note { font-size: 11px; color: #991B1B; margin-top: 4px; }

  .table-section { margin: 22px 0 0; }

  .table-title {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--gray-500);
    margin-bottom: 10px;
  }

  .doc-body table {
    width: 100%;
    border-collapse: collapse;
  }

  /* Hex literal: Dompdf tidak selalu menerapkan var() ke background thead → teks putih bisa hilang di atas putih */
  .doc-body table thead tr {
    background: #212121;
    background-color: #212121;
  }

  .doc-body table thead th {
    font-size: 9.5px;
    font-weight: 700;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    color: #ffffff;
    background: #212121;
    background-color: #212121;
    padding: 10px 12px;
    text-align: left;
    border: none;
    border-bottom: 1px solid #424242;
  }

  .doc-body table thead th.right { text-align: right; }
  .doc-body table thead th.center { text-align: center; }

  .doc-body table tbody td {
    font-size: 11.5px;
    color: var(--gray-800);
    padding: 11px 12px;
    vertical-align: middle;
    border-bottom: 1px solid var(--gray-200);
  }

  .doc-body table tbody tr:nth-child(even) td {
    background: var(--gray-50);
  }

  .text-right, td.right, th.right { text-align: right; }
  .text-center, td.center, th.center { text-align: center; }
  .fw-600 { font-weight: 600; }
  .fw-700 { font-weight: 700; }
  .muted { color: var(--gray-500); }

  .type-badge {
    display: inline-block;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.2px;
    padding: 3px 8px;
    border-radius: 5px;
    white-space: nowrap;
    background: var(--orange-bg);
    color: var(--orange);
    border: 1px solid rgba(230,81,0,0.15);
  }

  .badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 5px;
    font-size: 9px;
    font-weight: 700;
  }

  .badge-blue { background: #E3F2FD; color: #1565C0; border: 1px solid #BBDEFB; }
  .badge-purple { background: #F3E5F5; color: #6A1B9A; border: 1px solid #E1BEE7; }
  .badge-amber { background: var(--orange-bg); color: var(--orange); border: 1px solid rgba(230,81,0,0.15); }
  .badge-green { background: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9; }

  .item-name { font-weight: 600; color: var(--gray-900); }
  .item-code { font-size: 10px; color: var(--gray-500); margin-top: 1px; }

  .status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    margin-top: 4px;
  }

  .status-badge.status-pending,
  .status-pending { background: #FFF8E1; color: #F57F17; border: 1px solid #FFE082; }

  .status-badge.status-approved,
  .status-approved { background: #E8F5E9; color: #2E7D32; border: 1px solid #C8E6C9; }

  .status-badge.status-rejected,
  .status-rejected { background: #FFEBEE; color: #C62828; border: 1px solid #FFCDD2; }

  .totals-section {
    margin-top: 0;
    display: flex;
    justify-content: flex-end;
  }

  .totals-box {
    width: 300px;
    max-width: 100%;
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-top: none;
    border-radius: 0 0 10px 10px;
    overflow: hidden;
  }

  .totals-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 16px;
    border-bottom: 1px solid var(--gray-200);
    font-size: 11.5px;
    color: var(--gray-600);
  }

  .totals-row .val {
    font-weight: 600;
    color: var(--gray-800);
  }

  .totals-row.grand {
    background: var(--red);
    padding: 12px 16px;
    border-bottom: none;
  }

  .totals-row.grand .lbl,
  .totals-row.grand .val {
    font-size: 13px;
    font-weight: 800;
    color: #fff;
  }

  /* Ringkasan float kanan (format lama summary-table) */
  .summary { margin-top: 12px; overflow: hidden; }
  .summary-table { float: right; width: 300px; max-width: 100%; border-collapse: collapse; }
  .summary-table tr td { padding: 7px 0; font-size: 11.5px; border: none; }
  .summary-table .label { color: var(--gray-600); }
  .summary-table .value { text-align: right; font-weight: 600; color: var(--gray-900); }
  .summary-table tr.grand td,
  .summary-table .total {
    font-size: 13px;
    font-weight: 800;
    color: var(--red-ink);
    padding-top: 10px;
    border-top: 1.5px solid var(--gray-200);
  }

  .sig-section {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0;
    margin-top: 36px;
    padding-top: 22px;
    border-top: 1.5px solid var(--gray-200);
  }

  .sig-col {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0 12px;
  }

  .sig-col:not(:last-child) {
    border-right: 1px solid var(--gray-200);
  }

  .sig-label {
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--gray-500);
    margin-bottom: 44px;
  }

  .sig-line {
    width: 100%;
    border-top: 1.5px solid var(--gray-700);
    margin-bottom: 6px;
  }

  .sig-name {
    font-size: 12px;
    font-weight: 700;
    color: var(--gray-900);
    text-align: center;
  }

  .sig-date {
    font-size: 10.5px;
    color: var(--gray-500);
    margin-top: 2px;
  }

  .signatures { margin-top: 36px; }
  .signatures table { width: 100%; border-collapse: collapse; }
  .signatures td { width: 33.33%; text-align: center; vertical-align: top; padding: 0 10px; border: none; }

  .doc-footer, .footer {
    margin-top: 28px;
    padding-top: 18px;
    border-top: 1px solid var(--gray-200);
    text-align: center;
  }

  .doc-footer p, .footer p {
    font-size: 10.5px;
    color: var(--gray-500);
    line-height: 1.7;
  }

  /* Packing & dokumen khusus */
  .product-block {
    border: 1px solid var(--gray-200);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 16px;
  }

  .product-header {
    background: var(--gray-50);
    padding: 12px 18px;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .product-title { font-size: 13px; font-weight: 700; color: var(--gray-900); }
  .product-sub { font-size: 10px; color: var(--gray-600); margin-top: 2px; }
  .product-badge { font-size: 10px; font-weight: 600; color: var(--gray-600); background: var(--gray-200); padding: 3px 8px; border-radius: 5px; }

  .summary-section {
    border: 1px solid var(--gray-200);
    border-radius: 10px;
    padding: 16px 18px;
    margin-bottom: 14px;
    background: var(--gray-50);
  }

  .summary-title {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--gray-500);
    margin-bottom: 10px;
  }

  .summary-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 12px; }
  .summary-row .label { color: var(--gray-600); }
  .summary-row .value { font-weight: 600; color: var(--gray-900); }

  /* Service record / kartu */
  .card {
    margin-top: 12px;
    border: 1px solid var(--gray-200);
    border-radius: 10px;
    padding: 10px 14px;
    background: #fff;
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--gray-200);
  }

  .card-title { font-size: 12px; font-weight: 800; color: var(--gray-900); }

  .section-title {
    font-size: 11px;
    font-weight: 800;
    margin: 14px 0 8px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--gray-500);
    border-bottom: 1px solid var(--gray-200);
    padding-bottom: 4px;
  }

  .line {
    display: table;
    width: 100%;
    margin: 3px 0;
    font-size: 11px;
    color: var(--gray-800);
  }

  .line span:first-child { display: table-cell; padding-right: 8px; }
  .line span:last-child { display: table-cell; text-align: right; font-weight: 600; white-space: nowrap; }

  .total {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid var(--gray-200);
    text-align: right;
    font-weight: 800;
    font-size: 12px;
    color: var(--gray-900);
  }

  .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  .header-table td { border: 0; padding: 0; vertical-align: top; }

  .brand { display: flex; align-items: center; gap: 10px; }
  .logo-img { width: 180px; max-height: 56px; object-fit: contain; }

  .meta-right { text-align: right; font-size: 10px; color: var(--gray-600); }
  .divider { height: 1px; background: var(--gray-200); margin: 10px 0 14px; }

  .summary-inline {
    margin-top: 10px;
    padding: 10px 14px;
    border: 1px solid var(--gray-200);
    border-radius: 10px;
    background: var(--gray-50);
  }

  .summary-inline .label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.8px; color: var(--gray-500); font-weight: 700; }
  .summary-inline .value { font-size: 12px; font-weight: 700; color: var(--gray-900); margin-top: 2px; }

  h1.doc-h1 {
    font-size: 15px;
    font-weight: 800;
    color: var(--gray-900);
    margin: 0 0 4px 0;
  }

  p.doc-lead {
    font-size: 10px;
    color: var(--gray-600);
    margin-bottom: 14px;
    line-height: 1.5;
  }

  .mono { font-family: DejaVu Sans Mono, monospace; font-size: 10.5px; }

  @media print {
    body { background: #fff !important; padding: 0 !important; }
    body.pdf-screen .doc { box-shadow: none; border-radius: 0; max-width: 100%; }
    .print-bar { display: none !important; }
    .doc-body { padding: 28px 32px; }
    @page { margin: 10mm; size: A4; }
  }
</style>

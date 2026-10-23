<style>
main:has(.izin-surat-page) {
  padding: 0 !important;
  max-width: none !important;
}
.izin-surat-page {
  --izin-ink: #0f172a;
  --izin-muted: #64748b;
  --izin-line: #e2e8f0;
  --izin-accent: #d5241c;
  --izin-paper: #ffffff;
  width: 100%;
  max-width: none;
  min-height: calc(100vh - 4rem);
  margin: 0;
  padding: 12px 16px 32px;
  box-sizing: border-box;
  background: linear-gradient(180deg, #ebe8e3 0%, #f6f5f2 42%, #fafaf9 100%);
}
@media (min-width: 1024px) {
  .izin-surat-page { padding: 16px 24px 40px; }
}
.izin-surat-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 18px;
}
.izin-surat-back {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 40px;
  padding: 0 16px;
  border-radius: 12px;
  border: 1px solid rgba(15, 23, 42, .08);
  background: #fff;
  color: #334155;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
}
.izin-surat-back:hover { background: #f8fafc; color: #0f172a; }
.izin-surat-back svg { width: 16px; height: 16px; flex-shrink: 0; }
.izin-surat-paper {
  overflow: hidden;
  border-radius: 18px;
  border: 1px solid rgba(15, 23, 42, .07);
  background: var(--izin-paper);
  box-shadow:
    0 1px 2px rgba(15, 23, 42, .04),
    0 16px 48px rgba(15, 23, 42, .07);
}
@media (min-width: 1024px) {
  .izin-surat-paper { border-radius: 22px; }
}
.izin-surat-letterhead {
  position: relative;
  padding: 28px 32px 24px;
  background:
    linear-gradient(135deg, rgba(213, 36, 28, .08) 0%, transparent 45%),
    linear-gradient(180deg, #fafafa 0%, #fff 100%);
  border-bottom: 1px solid var(--izin-line);
}
.izin-surat-letterhead::after {
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 3px;
  background: linear-gradient(90deg, var(--izin-accent) 0%, #f97316 50%, transparent 100%);
}
.izin-surat-brand-row {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
}
.izin-surat-brand {
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 0;
}
.izin-surat-brand img {
  height: 48px;
  width: auto;
  object-fit: contain;
}
.izin-surat-brand-fallback {
  display: flex;
  height: 48px;
  width: 48px;
  align-items: center;
  justify-content: center;
  border-radius: 14px;
  background: linear-gradient(145deg, #1e293b, #0f172a);
  font-size: 11px;
  font-weight: 800;
  color: #fff;
}
.izin-surat-brand-text h2 {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  letter-spacing: -.02em;
  color: var(--izin-ink);
}
.izin-surat-brand-text p {
  margin: 2px 0 0;
  font-size: 12px;
  color: var(--izin-muted);
}
.izin-surat-doc-meta {
  text-align: right;
  min-width: 200px;
}
.izin-surat-doc-meta .doc-type {
  margin: 0;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: var(--izin-accent);
}
.izin-surat-doc-meta .doc-title {
  margin: 6px 0 0;
  font-family: 'Barlow Condensed', sans-serif;
  font-size: clamp(22px, 3.5vw, 28px);
  font-weight: 700;
  letter-spacing: .02em;
  line-height: 1.15;
  color: var(--izin-ink);
}
.izin-surat-doc-meta .doc-nomor {
  margin: 4px 0 0;
  font-size: 13px;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
  color: #475569;
}
.izin-surat-body {
  padding: 28px 32px 36px;
}
@media (max-width: 640px) {
  .izin-surat-letterhead,
  .izin-surat-body { padding-left: 20px; padding-right: 20px; }
  .izin-surat-doc-meta { text-align: left; width: 100%; }
}
.izin-surat-section-title {
  margin: 0 0 14px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: #94a3b8;
}
.izin-surat-prose {
  font-size: 14px;
  line-height: 1.75;
  color: #334155;
}
.izin-surat-prose p { margin: 0 0 14px; }
.izin-surat-prose .surat-judul {
  text-align: center;
  font-weight: 700;
  font-size: 15px;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: var(--izin-ink);
  margin-bottom: 20px;
}
.izin-surat-identitas {
  margin: 16px 0 20px;
  border-collapse: collapse;
  width: 100%;
  max-width: 520px;
}
.izin-surat-identitas td {
  padding: 3px 0;
  vertical-align: top;
}
.izin-surat-identitas td:first-child {
  width: 88px;
  color: var(--izin-muted);
  padding-right: 8px;
}
.izin-surat-identitas td:nth-child(2) {
  width: 12px;
  color: var(--izin-muted);
}
.izin-surat-alasan,
.izin-surat-note {
  margin: 16px 0;
  padding: 14px 16px;
  border-radius: 12px;
  border: 1px dashed #cbd5e1;
  background: #f8fafc;
  white-space: pre-line;
  line-height: 1.6;
  font-size: 13px;
  color: #475569;
}
.izin-surat-kv-grid {
  display: grid;
  gap: 10px 20px;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}
@media (min-width: 768px) {
  .izin-surat-kv-grid.cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (min-width: 1280px) {
  .izin-surat-kv-grid.cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
  .izin-surat-kv-grid { grid-template-columns: 1fr; }
}
.izin-surat-kv {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 12px;
  background: #f8fafc;
  border: 1px solid #f1f5f9;
  font-size: 13px;
}
.izin-surat-kv-wide {
  grid-column: 1 / -1;
  flex-direction: column;
  align-items: stretch;
  gap: 6px;
}
.izin-surat-kv-wide dd { text-align: left; white-space: pre-line; line-height: 1.55; }
.izin-surat-kv dt { color: var(--izin-muted); font-weight: 500; }
.izin-surat-kv dd { margin: 0; text-align: right; font-weight: 600; color: var(--izin-ink); }
.izin-surat-pemohon {
  margin-top: 36px;
  display: flex;
  justify-content: flex-end;
}
.izin-surat-pemohon-inner {
  text-align: center;
  min-width: 240px;
}
.izin-surat-pemohon-inner.has-qr .izin-surat-pemohon-line { display: none; }
.izin-surat-pemohon-qr {
  margin: 12px auto 6px;
  display: flex;
  justify-content: center;
}
.izin-surat-pemohon-qr img,
.izin-surat-pemohon-qr canvas,
.izin-surat-pemohon-qr table {
  width: 108px !important;
  height: 108px !important;
}
.izin-surat-pemohon-verify {
  display: inline-block;
  margin-top: 6px;
  font-size: 11px;
  font-weight: 600;
  color: #dc2626;
  text-decoration: none;
}
.izin-surat-pemohon-verify:hover { text-decoration: underline; }
.izin-surat-pemohon-label {
  font-size: 13px;
  font-weight: 600;
  color: var(--izin-ink);
}
.izin-surat-pemohon-line {
  margin: 56px auto 8px;
  max-width: 220px;
  border-bottom: 1px solid #334155;
}
.izin-surat-pemohon-name {
  font-size: 14px;
  font-weight: 700;
  color: var(--izin-ink);
}
.izin-surat-pemohon-meta {
  margin-top: 4px;
  font-size: 11px;
  color: var(--izin-muted);
}
.izin-surat-signatures {
  margin-top: 40px;
  padding-top: 28px;
  border-top: 1px solid var(--izin-line);
  break-inside: avoid-page;
}
.izin-surat-signatures-title {
  margin: 0 0 20px;
  text-align: center;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: #94a3b8;
}
.izin-surat-sign-grid {
  display: grid;
  gap: 28px 32px;
  grid-template-columns: 1fr;
}
@media (min-width: 640px) {
  .izin-surat-sign-grid {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  }
}
.izin-surat-sign-slot {
  text-align: center;
  padding: 4px 8px 12px;
  min-height: 160px;
}
.izin-surat-sign-role {
  margin: 0;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: #64748b;
}
.izin-surat-sign-name {
  margin: 10px 0 0;
  font-size: 14px;
  font-weight: 700;
  color: var(--izin-ink);
}
.izin-surat-sign-meta {
  margin: 4px 0 0;
  font-size: 11px;
  color: #94a3b8;
}
.izin-surat-sign-line {
  margin: 40px auto 0;
  max-width: 200px;
  border-bottom: 1px dashed #cbd5e1;
}
.izin-surat-table-wrap {
  overflow: hidden;
  border-radius: 14px;
  border: 1px solid var(--izin-line);
}
.izin-surat-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.izin-surat-table thead { background: #f8fafc; }
.izin-surat-table th {
  padding: 10px 14px;
  text-align: left;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: #64748b;
  border-bottom: 1px solid var(--izin-line);
}
.izin-surat-table td {
  padding: 12px 14px;
  vertical-align: top;
  color: #334155;
  border-bottom: 1px solid #f1f5f9;
}
.izin-surat-table tbody tr:last-child td { border-bottom: none; }
.izin-surat-table .col-kode {
  width: 48px;
  font-weight: 800;
  color: var(--izin-accent);
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}
.izin-surat-team {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 4px;
}
.izin-surat-team-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border-radius: 999px;
  border: 1px solid var(--izin-line);
  background: #fff;
  padding: 6px 12px 6px 6px;
  font-size: 12px;
  font-weight: 600;
  color: #334155;
}
.izin-surat-team-chip .av {
  display: flex;
  height: 28px;
  width: 28px;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: #0f172a;
  font-size: 10px;
  font-weight: 800;
  color: #fff;
}
.izin-surat-team-chip.is-leader {
  border-color: #fcd34d;
  background: linear-gradient(180deg, #fffbeb, #fff);
}
.izin-surat-team-chip .tag-leader {
  font-size: 9px;
  font-weight: 800;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: #b45309;
}

/* Pembagian tugas per karyawan — card list (bukan tabel lebar) */
.dinas-task-board {
  display: grid;
  gap: 12px;
  margin-top: 4px;
}
.dinas-task-person {
  border: 1px solid var(--izin-line);
  border-radius: 14px;
  background: #fff;
  overflow: hidden;
}
.dinas-task-person-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 12px 14px;
  background: #fafafa;
  border-bottom: 1px solid #f1f5f9;
}
.dinas-task-person-head .who {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}
.dinas-task-person-head .av {
  flex-shrink: 0;
  display: grid;
  place-items: center;
  width: 34px;
  height: 34px;
  border-radius: 999px;
  background: #181819;
  color: #fff;
  font-size: 11px;
  font-weight: 800;
}
.dinas-task-person-head .name {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  color: #181819;
  line-height: 1.25;
}
.dinas-task-person-head .meta {
  margin: 2px 0 0;
  font-size: 11px;
  color: #94a3b8;
  font-weight: 600;
}
.dinas-task-person-head .badge-leader {
  display: inline-flex;
  align-items: center;
  margin-left: 6px;
  padding: 1px 7px;
  border-radius: 999px;
  border: 1px solid #fde68a;
  background: #fffbeb;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: .04em;
  text-transform: uppercase;
  color: #b45309;
  vertical-align: middle;
}
.dinas-task-person-head .count {
  flex-shrink: 0;
  font-size: 11px;
  font-weight: 700;
  color: #64748b;
  background: #f1f5f9;
  border-radius: 999px;
  padding: 4px 9px;
}
.dinas-task-list {
  margin: 0;
  padding: 0;
  list-style: none;
}
.dinas-task-row {
  display: grid;
  grid-template-columns: 34px minmax(0, 1fr);
  gap: 10px;
  padding: 12px 14px;
  border-top: 1px solid #f8fafc;
}
.dinas-task-row:first-child { border-top: none; }
.dinas-task-kode {
  display: grid;
  place-items: center;
  width: 28px;
  height: 28px;
  margin-top: 1px;
  border-radius: 8px;
  background: linear-gradient(145deg, #8CFA01, #6ed000);
  color: #142000;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: .02em;
}
.dinas-task-body .title {
  margin: 0;
  font-size: 13px;
  font-weight: 650;
  color: #1e293b;
  line-height: 1.35;
}
.dinas-task-body .tags {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-left: 6px;
  vertical-align: middle;
}
.dinas-task-tag {
  display: inline-flex;
  align-items: center;
  padding: 1px 6px;
  border-radius: 6px;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: .03em;
  text-transform: uppercase;
}
.dinas-task-tag.is-shared {
  background: #f1f5f9;
  color: #64748b;
}
.dinas-task-tag.is-field {
  background: #fffbeb;
  border: 1px solid #fde68a;
  color: #b45309;
}
.dinas-task-body .facts {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 12px;
  margin-top: 6px;
}
.dinas-task-fact {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
}
.dinas-task-fact svg {
  width: 12px;
  height: 12px;
  flex-shrink: 0;
  opacity: .7;
}
.dinas-task-empty {
  padding: 16px 14px;
  text-align: center;
  font-size: 12px;
  color: #94a3b8;
}
.dinas-task-shared-label {
  margin: 0 0 8px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: #b45309;
}
.dinas-task-status {
  display: inline-flex;
  align-items: center;
  height: 22px;
  padding: 0 8px;
  border-radius: 999px;
  font-size: 10px;
  font-weight: 700;
  white-space: nowrap;
}
.dinas-task-status.is-ok {
  background: #ecfdf5;
  color: #166534;
  border: 1px solid #bbf7d0;
}
.dinas-task-status.is-pending {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
}
.dinas-task-head-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px 12px;
}
.dinas-task-head-row .title { flex: 1 1 auto; min-width: 0; }
.dinas-task-reports {
  margin-top: 10px;
  display: grid;
  gap: 8px;
}
.dinas-task-report {
  border: 1px solid #bbf7d0;
  border-radius: 10px;
  background: #f0fdf4;
  padding: 10px 12px;
}
.dinas-task-report .who {
  margin: 0;
  font-size: 11px;
  font-weight: 700;
  color: #065f46;
}
.dinas-task-report .text {
  margin: 5px 0 0;
  font-size: 12px;
  color: #14532d;
  white-space: pre-line;
  line-height: 1.45;
}
.dinas-task-report-evs {
  margin-top: 7px;
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}
.dinas-task-report-ev {
  display: inline-flex;
  align-items: center;
  padding: 4px 8px;
  border-radius: 7px;
  background: #fff;
  border: 1px solid #e2e8f0;
  font-size: 10px;
  font-weight: 600;
  color: #334155;
  text-decoration: none;
}
.dinas-task-report-ev:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}
.dinas-task-no-report {
  margin-top: 8px;
  font-size: 11px;
  font-weight: 600;
  color: #94a3b8;
}

@media (max-width: 640px) {
  .dinas-task-person-head { padding: 11px 12px; }
  .dinas-task-row { padding: 11px 12px; gap: 8px; }
  .dinas-task-body .facts { gap: 4px 10px; }
}
.izin-surat-link {
  font-weight: 600;
  color: var(--izin-accent);
}
.izin-surat-link:hover { text-decoration: underline; }
.izin-surat-reject-box {
  margin-top: 24px;
  padding: 14px 16px;
  border-radius: 12px;
  border: 1px solid #fecaca;
  background: #fef2f2;
  font-size: 13px;
  color: #b91c1c;
  line-height: 1.55;
}
.izin-surat-actions {
  margin-top: 28px;
  padding-top: 24px;
  border-top: 1px dashed var(--izin-line);
}
.izin-surat-flash { margin-bottom: 16px; }
.izin-surat-page .status-pill.dinas-diajukan { background: rgba(245, 158, 11, 0.12); color: #92400e; }
.izin-surat-page .status-pill.dinas-diajukan .dot { background: #f59e0b; }
.izin-surat-page .status-pill.dinas-diketahui { background: rgba(46, 107, 230, 0.12); color: #1d4ed8; }
.izin-surat-page .status-pill.dinas-diketahui .dot { background: #2e6be6; }
.izin-surat-page .status-pill.dinas-disetujui { background: rgba(124, 58, 237, 0.12); color: #6d28d9; }
.izin-surat-page .status-pill.dinas-disetujui .dot { background: #7c3aed; }
.izin-surat-page .status-pill.dinas-divalidasi { background: var(--green-tint, #f0fdf4); color: #166534; }
.izin-surat-page .status-pill.dinas-divalidasi .dot { background: #22c55e; }
.izin-surat-page .status-pill.dinas-ditolak { background: var(--red-tint, #fef2f2); color: #b91c1c; }
.izin-surat-page .status-pill.dinas-ditolak .dot { background: #dc2626; }
.izin-surat-page .status-pill.dinas-draft { background: #f3f4f6; color: #6b7280; }
.izin-surat-page .status-pill.dinas-draft .dot { background: #9ca3af; }
@media print {
  @page { size: A4; margin: 12mm 14mm; }
  .no-print { display: none !important; }
  .izin-surat-page { background: #fff; padding: 0; }
  .izin-surat-paper {
    border: none;
    border-radius: 0;
    box-shadow: none;
  }
  .izin-surat-letterhead { background: #fff !important; padding-top: 0; }
  .izin-surat-signatures { break-inside: avoid-page; }
  .izin-surat-alasan,
  .izin-surat-note { background: #fff; }
  .izin-surat-kv { break-inside: avoid; }
  .izin-surat-table-wrap { break-inside: avoid; }
}
</style>

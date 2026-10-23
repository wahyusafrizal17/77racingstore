@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.izin-page {
  --bg: #F6F5F2;
  --surface: #FFFFFF;
  --ink: #181819;
  --ink-soft: #6E7078;
  --ink-faint: #A6A8AE;
  --line: #E9E7E2;
  --line-soft: #F1EFEA;
  --red: #8CFA01;
  --red-ink: #3d6b00;
  --red-deep: #6ed000;
  --red-tint: #f3fde0;
  --green: #1D8A5C;
  --green-tint: #E8F5EF;
  --amber: #B9791A;
  --amber-tint: #FBF1E2;
  --violet-tint: #F3E8FF;
  --sidebar: #121214;
  --shadow: 0 1px 2px rgba(20,20,20,.04), 0 8px 24px rgba(20,20,20,.05);
  --shadow-lg: 0 4px 10px rgba(20,20,20,.05), 0 20px 48px rgba(20,20,20,.08);
  color: var(--ink);
  font-family: 'Inter', sans-serif;
  -webkit-font-smoothing: antialiased;
  width: 100%;
  max-width: none;
  margin: 0;
  box-sizing: border-box;
}
.izin-page *, .izin-page *::before, .izin-page *::after { box-sizing: border-box; }

.izin-page .hero-card {
  background: linear-gradient(155deg, var(--sidebar), #1D1D20);
  border-radius: 18px;
  padding: 22px 24px;
  color: #fff;
  box-shadow: var(--shadow-lg);
  position: relative;
  overflow: hidden;
  margin-bottom: 20px;
}
.izin-page .hero-card::before {
  content: '';
  position: absolute;
  top: -70px; right: -50px;
  width: 200px; height: 200px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(140,250,1,.32), transparent 70%);
}
.izin-page .hero-card .eyebrow {
  font-size: 11px;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: #9A9BA2;
  position: relative;
  z-index: 1;
}
.izin-page .hero-card .title {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 28px;
  font-weight: 700;
  margin: 6px 0 0;
  letter-spacing: .01em;
  position: relative;
  z-index: 1;
}
.izin-page .hero-card .sub {
  font-size: 13px;
  color: #B4B5BC;
  margin-top: 6px;
  max-width: 520px;
  line-height: 1.55;
  position: relative;
  z-index: 1;
}
.izin-page .hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 16px;
  position: relative;
  z-index: 1;
}
.izin-page .hero-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 38px;
  padding: 0 14px;
  border-radius: 11px;
  font-size: 12.5px;
  font-weight: 700;
  text-decoration: none;
  transition: .15s ease;
  border: 1px solid rgba(255,255,255,.14);
  background: rgba(255,255,255,.08);
  color: #fff;
}
.izin-page .hero-btn:hover { background: rgba(255,255,255,.14); }
.izin-page .hero-btn.primary {
  background: var(--red);
  border-color: var(--red-ink);
}
.izin-page .hero-btn.primary:hover { background: var(--red-deep); }

.izin-page .stat-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 20px;
}
.izin-page .stat-card {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: 16px;
  padding: 16px 18px;
  box-shadow: var(--shadow);
}
.izin-page .stat-card .k {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: var(--ink-faint);
}
.izin-page .stat-card .v {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 32px;
  font-weight: 700;
  margin-top: 4px;
  line-height: 1;
}
.izin-page .stat-card.pending .v { color: var(--amber); }
.izin-page .stat-card.approved .v { color: var(--green); }
.izin-page .stat-card.rejected .v { color: var(--red-ink); }

.izin-page .panel {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: 22px;
  box-shadow: var(--shadow-lg);
  overflow: hidden;
}
.izin-page .panel-head {
  padding: 22px 24px;
  border-bottom: 1px solid var(--line-soft);
}
.izin-page .panel-head h2 {
  font-size: 18px;
  font-weight: 700;
  margin: 0 0 4px;
}
.izin-page .panel-head p {
  font-size: 13px;
  color: var(--ink-soft);
  margin: 0;
}
.izin-page .panel-body { padding: 22px 24px; }

.izin-page .field { margin-bottom: 18px; }
.izin-page .field-label {
  display: block;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: var(--ink-faint);
  margin-bottom: 8px;
}
.izin-page .field-input,
.izin-page .field-select,
.izin-page .field-textarea {
  width: 100%;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--surface);
  font-size: 14px;
  font-weight: 500;
  color: var(--ink);
  outline: none;
  transition: border-color .15s, box-shadow .15s;
}
.izin-page .field-input,
.izin-page .field-select { height: 44px; padding: 0 14px; }
.izin-page .field-textarea { padding: 12px 14px; min-height: 110px; resize: vertical; }
.izin-page .field-input:focus,
.izin-page .field-select:focus,
.izin-page .field-textarea:focus {
  border-color: rgba(140,250,1,.45);
  box-shadow: 0 0 0 3px rgba(140,250,1,.1);
}

.izin-page .type-pills {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}
.izin-page .type-pill {
  position: relative;
  cursor: pointer;
}
.izin-page .type-pill input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}
.izin-page .type-pill span {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  padding: 12px 8px;
  border-radius: 12px;
  border: 1.5px solid var(--line);
  background: #FAFAF9;
  font-size: 12px;
  font-weight: 700;
  color: var(--ink-soft);
  transition: .15s ease;
  text-align: center;
}
.izin-page .type-pill input:checked + span {
  border-color: var(--red-ink);
  background: var(--red-tint);
  color: var(--red-deep);
  box-shadow: 0 0 0 1px rgba(140,250,1,.08);
}
.izin-page .type-pill em {
  font-style: normal;
  font-size: 18px;
  line-height: 1;
}

.izin-page .date-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.izin-page .btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  height: 48px;
  border: none;
  border-radius: 14px;
  background: linear-gradient(135deg, var(--red), var(--red-deep));
  color: #142000;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 8px 24px rgba(140,250,1,.28);
  transition: transform .15s, box-shadow .15s;
}
.izin-page .btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 30px rgba(140,250,1,.34);
}

.izin-page .flash {
  border-radius: 14px;
  padding: 14px 16px;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 16px;
}
.izin-page .flash.success { background: var(--green-tint); color: #166534; border: 1px solid #BBF7D0; }
.izin-page .flash.error { background: var(--red-tint); color: var(--red-deep); border: 1px solid #FECACA; }

.izin-page .layout-grid {
  display: grid;
  grid-template-columns: minmax(300px, 380px) 1fr;
  gap: 18px;
  align-items: start;
}

.izin-page .history-list { display: flex; flex-direction: column; }
.izin-page .history-item {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 14px;
  align-items: center;
  padding: 18px 22px;
  border-bottom: 1px solid var(--line-soft);
  text-decoration: none;
  color: inherit;
  transition: background .12s ease;
}
.izin-page .history-item:last-child { border-bottom: none; }
.izin-page .history-item:hover { background: #FAFAF9; }

.izin-page .type-icon {
  width: 44px;
  height: 44px;
  border-radius: 13px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}
.izin-page .type-icon.izin { background: #EEF3FF; }
.izin-page .type-icon.sakit { background: var(--red-tint); }
.izin-page .type-icon.cuti { background: var(--violet-tint); }

.izin-page .history-title {
  font-size: 14.5px;
  font-weight: 700;
  color: var(--ink);
  margin: 0 0 3px;
}
.izin-page .history-meta {
  font-size: 12.5px;
  color: var(--ink-faint);
  margin: 0;
}
.izin-page .history-reason {
  font-size: 12.5px;
  color: var(--ink-soft);
  margin: 6px 0 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.izin-page .status-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 11px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: .02em;
  white-space: nowrap;
}
.izin-page .status-pill .dot { width: 6px; height: 6px; border-radius: 50%; }
.izin-page .status-pill.pending { background: var(--amber-tint); color: #92400E; }
.izin-page .status-pill.pending .dot { background: var(--amber); }
.izin-page .status-pill.approved { background: var(--green-tint); color: #166534; }
.izin-page .status-pill.approved .dot { background: var(--green); }
.izin-page .status-pill.rejected { background: var(--red-tint); color: var(--red-deep); }
.izin-page .status-pill.rejected .dot { background: var(--red); }
.izin-page .status-pill.cancelled { background: #F3F4F6; color: #6B7280; }
.izin-page .status-pill.cancelled .dot { background: #9CA3AF; }

.izin-page .empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 24px;
  text-align: center;
  color: var(--ink-faint);
}
.izin-page .empty-state svg {
  display: block;
  width: 40px;
  height: 40px;
  opacity: .35;
  margin: 0 auto 10px;
  flex-shrink: 0;
}
.izin-page .empty-state p { font-size: 13.5px; margin: 0; max-width: 360px; }

.izin-page .detail-card { max-width: 720px; margin: 0 auto; }
.izin-page .detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}
.izin-page .detail-box {
  background: #FAFAF9;
  border: 1px solid var(--line-soft);
  border-radius: 14px;
  padding: 14px 16px;
}
.izin-page .detail-box .k {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--ink-faint);
}
.izin-page .detail-box .v {
  font-size: 14px;
  font-weight: 600;
  color: var(--ink);
  margin-top: 6px;
}
.izin-page .detail-reason {
  background: #FAFAF9;
  border: 1px solid var(--line-soft);
  border-radius: 14px;
  padding: 16px 18px;
  font-size: 14px;
  line-height: 1.65;
  color: var(--ink-soft);
  white-space: pre-wrap;
}
.izin-page .reject-box {
  background: var(--red-tint);
  border: 1px solid #FECACA;
  border-radius: 14px;
  padding: 16px 18px;
  color: var(--red-deep);
  font-size: 14px;
  line-height: 1.55;
}
.izin-page .btn-ghost {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 40px;
  padding: 0 16px;
  border-radius: 12px;
  border: 1px solid var(--line);
  background: var(--surface);
  font-size: 13px;
  font-weight: 700;
  color: var(--ink-soft);
  cursor: pointer;
  text-decoration: none;
  transition: .12s ease;
}
.izin-page .btn-ghost:hover { background: #FAFAF9; color: var(--ink); }

.izin-page .validasi-card {
  border-bottom: 1px solid var(--line-soft);
  padding: 22px 24px;
}
.izin-page .validasi-card:last-child { border-bottom: none; }
.izin-page .validasi-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 16px;
  align-items: center;
}
.izin-page .btn-approve {
  height: 42px;
  padding: 0 18px;
  border: none;
  border-radius: 12px;
  background: linear-gradient(135deg, #1D8A5C, #157A4F);
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 6px 18px rgba(29,138,92,.25);
}
.izin-page .btn-reject {
  height: 42px;
  padding: 0 18px;
  border-radius: 12px;
  border: 1px solid #FECACA;
  background: var(--red-tint);
  color: var(--red-deep);
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}
.izin-page .reject-input {
  flex: 1;
  min-width: 200px;
  height: 42px;
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 0 14px;
  font-size: 13px;
}

@media (max-width: 960px) {
  .izin-page .layout-grid { grid-template-columns: 1fr; }
  .izin-page .stat-grid { grid-template-columns: 1fr; }
  .izin-page .date-row { grid-template-columns: 1fr; }
  .izin-page .detail-grid { grid-template-columns: 1fr; }
  .izin-page .history-item { grid-template-columns: auto 1fr; }
  .izin-page .history-item .status-pill { grid-column: 2; justify-self: start; margin-top: 4px; }
}
</style>
@endpush


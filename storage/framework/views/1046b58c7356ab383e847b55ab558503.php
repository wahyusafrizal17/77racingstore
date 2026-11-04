<style>
.ss-dashboard {
  --ss-red: #8CFA01;
  --ss-red-ink: #3d6b00;
  --ss-red-dim: rgba(140,250,1,0.16);
  --ss-black: #0D0D0D;
  --ss-bg: #F4F5F7;
  --ss-card: #FFFFFF;
  --ss-border: rgba(0,0,0,0.07);
  --ss-text: #0D0D0D;
  --ss-muted: #8A8F98;
  --ss-green: #12B76A;
  --ss-blue: #2E6BE6;
  --ss-amber: #F59E0B;
  --ss-purple: #7C3AED;
  --ss-shadow-sm: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
  --ss-shadow-md: 0 4px 16px rgba(0,0,0,0.08);
  --ss-r: 12px;
  color: var(--ss-text);
}

.ss-dashboard .filter-strip {
  display: flex; align-items: center; gap: 10px; margin-bottom: 24px; flex-wrap: wrap;
}
.ss-dashboard .filter-label { font-size: 12px; font-weight: 500; color: var(--ss-muted); }
.ss-dashboard .filter-select,
.ss-dashboard .filter-date {
  appearance: none; background: var(--ss-card); border: 1px solid var(--ss-border);
  border-radius: 8px; padding: 7px 12px; font-size: 12.5px; font-weight: 500;
  color: var(--ss-text); font-family: inherit; cursor: pointer;
}
.ss-dashboard .filter-select {
  padding-right: 30px;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238A8F98' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 10px center;
}
.ss-dashboard .filter-compare {
  display: inline-flex; align-items: center; gap: 8px;
  font-size: 12px; font-weight: 500; color: var(--ss-muted);
  cursor: pointer; user-select: none;
}
.ss-dashboard .filter-compare input {
  width: 15px; height: 15px; accent-color: var(--ss-red-ink); cursor: pointer;
}
.ss-dashboard .filter-range-label,
.ss-dashboard .filter-range-vs {
  font-size: 12px; font-weight: 600; color: var(--ss-muted);
}
.ss-dashboard .filter-range-vs { text-transform: uppercase; letter-spacing: 0.04em; }

.ss-dashboard .dash-compare-banner {
  margin: -8px 0 20px;
}
.ss-dashboard .dash-compare-banner-inner {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 16px; border-radius: 10px;
  background: linear-gradient(135deg, rgba(18, 183, 106, 0.08) 0%, rgba(46, 107, 230, 0.06) 100%);
  border: 1px solid rgba(18, 183, 106, 0.18);
}
.ss-dashboard .dash-compare-banner-icon {
  width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  background: rgba(18, 183, 106, 0.12); color: #059669;
}
.ss-dashboard .dash-compare-banner-icon svg { width: 18px; height: 18px; }
.ss-dashboard .dash-compare-banner-title {
  font-size: 12px; font-weight: 700; color: var(--ss-text); line-height: 1.2;
}
.ss-dashboard .dash-compare-banner-sub {
  display: flex; align-items: center; flex-wrap: wrap; gap: 6px;
  margin-top: 4px; font-size: 11px; color: var(--ss-muted);
}
.ss-dashboard .dash-compare-pill {
  display: inline-flex; align-items: center;
  border-radius: 999px; padding: 2px 10px; font-size: 10.5px; font-weight: 600;
}
.ss-dashboard .dash-compare-pill.current {
  background: rgba(13, 13, 13, 0.06); color: var(--ss-text);
}
.ss-dashboard .dash-compare-pill.previous {
  background: rgba(18, 183, 106, 0.12); color: #047857;
}
.ss-dashboard .dash-compare-vs {
  font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: var(--ss-muted);
}

.ss-dashboard .dash-cmp-line {
  margin-top: 6px; padding-top: 8px; border-top: 1px dashed rgba(15, 23, 42, 0.08);
  display: flex; flex-direction: row; align-items: center; justify-content: space-between;
  gap: 8px; width: 100%;
}
.ss-dashboard .dash-cmp-prev {
  font-size: 10px; color: var(--ss-muted); line-height: 1.35;
  flex: 1; min-width: 0;
}
.ss-dashboard .dash-cmp-badge {
  display: inline-flex; align-items: center; flex-shrink: 0;
  border-radius: 999px; padding: 2px 8px; font-size: 10px; font-weight: 700;
}
.ss-dashboard .dash-cmp-badge.sm { padding: 1px 6px; font-size: 9px; }
.ss-dashboard .dash-cmp-badge.up { background: rgba(16, 185, 129, 0.12); color: #059669; }
.ss-dashboard .dash-cmp-badge.down { background: rgba(239, 68, 68, 0.12); color: #dc2626; }
.ss-dashboard .btn-apply {
  background: var(--ss-red); color: #142000; border: none; border-radius: 8px;
  padding: 7px 18px; font-size: 12.5px; font-weight: 600; cursor: pointer;
  transition: opacity 0.15s;
}
.ss-dashboard .btn-apply:hover { opacity: 0.88; }
.ss-dashboard .btn-export {
  background: #ecfdf5; color: #047857; border: 1px solid #86efac;
  border-radius: 9px; padding: 8px 16px; min-height: 40px; font-size: 13px; font-weight: 500;
  cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
  transition: background 0.15s, border-color 0.15s; text-decoration: none; flex-shrink: 0;
}
.ss-dashboard .btn-export:hover { border-color: var(--ss-green); background: #d1fae5; }
.ss-dashboard .btn-export svg { width: 16px; height: 16px; color: var(--ss-green); flex-shrink: 0; }
.ss-dashboard .btn-export-image {
  background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;
  border-radius: 9px; padding: 8px 16px; min-height: 40px; font-size: 13px; font-weight: 600;
  cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
  transition: background 0.15s, border-color 0.15s, opacity 0.15s; flex-shrink: 0;
}
.ss-dashboard .btn-export-image:hover:not(:disabled) { background: #dbeafe; border-color: #93c5fd; }
.ss-dashboard .btn-export-image:disabled { opacity: 0.6; cursor: not-allowed; }
.ss-dashboard .btn-export-image svg { width: 16px; height: 16px; flex-shrink: 0; }

.ss-dashboard .ss-dashboard-capture {
  display: flex; flex-direction: column; gap: 0;
}
.ss-dashboard .ss-dashboard-capture.ss-dashboard-capture-exporting,
.ss-dashboard .ss-dashboard-capture.ss-dashboard-capture-exporting * {
  font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
}
.ss-dash-export-overlay {
  position: fixed; inset: 0; z-index: 45;
  display: flex; align-items: center; justify-content: center;
  background: rgba(244, 245, 247, 0.78); backdrop-filter: blur(4px);
}
.ss-dash-export-panel {
  display: flex; flex-direction: column; align-items: center; gap: 14px;
  padding: 22px 28px; border-radius: 14px; background: #fff;
  border: 1px solid rgba(0,0,0,0.06); box-shadow: 0 8px 32px rgba(0,0,0,0.08);
}
.ss-dashboard .btn-export-pdf {
  background: #fef2f2;
  color: #b91c1c;
  border: 1px solid #fecaca;
  border-radius: 9px;
  padding: 8px 16px;
  min-height: 40px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  box-shadow: 0 1px 2px rgba(239, 68, 68, 0.08);
  transition: background 0.15s, border-color 0.15s, color 0.15s, box-shadow 0.15s;
  text-decoration: none;
  flex-shrink: 0;
}
.ss-dashboard .btn-export-pdf:hover {
  opacity: 1;
  background: #fee2e2;
  border-color: #fca5a5;
  color: #991b1b;
  box-shadow: 0 2px 6px rgba(239, 68, 68, 0.12);
}
.ss-dashboard .btn-export-pdf svg { width: 16px; height: 16px; color: #dc2626; flex-shrink: 0; }

.ss-dashboard .hero-strip {
  background: var(--ss-black); border-radius: var(--ss-r);
  padding: 22px 28px; margin-bottom: 20px;
  display: flex; align-items: center; gap: 0;
  position: relative; overflow: hidden;
}
.ss-dashboard .hero-strip::before {
  content: ''; position: absolute; top: -40px; right: -40px;
  width: 200px; height: 200px; border-radius: 50%;
  background: radial-gradient(circle, rgba(140,250,1,0.18) 0%, transparent 70%);
  pointer-events: none;
}
.ss-dashboard .hero-main { flex: 1; min-width: 0; position: relative; z-index: 1; }
.ss-dashboard .hero-eyebrow {
  font-size: 10.5px; font-weight: 600; letter-spacing: 0.1em;
  text-transform: uppercase; color: rgba(255,255,255,0.4); margin-bottom: 6px;
  overflow-wrap: anywhere;
}
.ss-dashboard .hero-number {
  font-size: 38px; font-weight: 800; color: #fff;
  letter-spacing: -0.02em; line-height: 1;
}
.ss-dashboard .hero-number span {
  font-size: 18px; font-weight: 500; color: rgba(255,255,255,0.5); margin-right: 2px;
}
.ss-dashboard .hero-sub { font-size: 12px; color: rgba(255,255,255,0.4); margin-top: 6px; overflow-wrap: anywhere; }
.ss-dashboard .hero-compare {
  display: flex; align-items: center; justify-content: space-between;
  gap: 8px; margin-top: 8px; width: 100%; max-width: 420px;
}
.ss-dashboard .hero-compare-prev {
  font-size: 11px; color: rgba(255,255,255,0.45);
  flex: 1; min-width: 0;
}
.ss-dashboard .hero-compare .dash-cmp-badge {
  flex-shrink: 0;
  background: rgba(255,255,255,0.1); color: #fff;
}
.ss-dashboard .hero-compare .dash-cmp-badge.up { background: rgba(18, 183, 106, 0.25); color: #6ee7b7; }
.ss-dashboard .hero-compare .dash-cmp-badge.down { background: rgba(239, 68, 68, 0.25); color: #fca5a5; }
.ss-dashboard .hero-stat-cmp { margin-top: 4px; margin-bottom: 2px; }
.ss-dashboard .hero-stat-cmp .dash-cmp-badge {
  background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.85);
}
.ss-dashboard .hero-stat-cmp .dash-cmp-badge.up { background: rgba(18, 183, 106, 0.22); color: #6ee7b7; }
.ss-dashboard .hero-stat-cmp .dash-cmp-badge.down { background: rgba(239, 68, 68, 0.22); color: #fca5a5; }
.ss-dashboard .hero-divider {
  width: 1px; height: 52px; background: rgba(255,255,255,0.08);
  margin: 0 28px; position: relative; z-index: 1;
}
.ss-dashboard .hero-stat { text-align: center; position: relative; z-index: 1; }
.ss-dashboard .hero-stat-val { font-size: 18px; font-weight: 700; color: #fff; }
.ss-dashboard .hero-stat-val.green { color: var(--ss-green); }
.ss-dashboard .hero-stat-val.amber { color: var(--ss-amber); }
.ss-dashboard .hero-stat-val.purple { color: #a78bfa; }
.ss-dashboard .hero-stat-lbl {
  font-size: 10.5px; color: rgba(255,255,255,0.35); margin-top: 3px; font-weight: 500;
}

.ss-dashboard .section-hd { margin-bottom: 14px; }
.ss-dashboard .section-hd h2 { font-size: 13.5px; font-weight: 700; color: var(--ss-text); }
.ss-dashboard .section-hd p { font-size: 11.5px; color: var(--ss-muted); margin-top: 2px; }

.ss-dashboard .payment-grid {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px;
}
.ss-dashboard .pay-card {
  background: var(--ss-card); border-radius: var(--ss-r); padding: 16px 18px;
  border: 1px solid var(--ss-border); box-shadow: var(--ss-shadow-sm);
  display: flex; flex-direction: column; gap: 10px; transition: box-shadow 0.2s;
}
.ss-dashboard .pay-card:hover { box-shadow: var(--ss-shadow-md); }
.ss-dashboard .pay-card-top { display: flex; align-items: center; justify-content: space-between; }
.ss-dashboard .pay-card-label {
  font-size: 10.5px; font-weight: 600; letter-spacing: 0.06em;
  text-transform: uppercase; color: var(--ss-muted);
}
.ss-dashboard .pay-icon {
  width: 30px; height: 30px; border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
}
.ss-dashboard .pay-icon.red { background: rgba(140,250,1,0.10); color: #3d6b00; }
.ss-dashboard .pay-icon.teal { background: rgba(20,184,166,0.10); color: #14B8A6; }
.ss-dashboard .pay-icon.blue { background: rgba(46,107,230,0.10); color: var(--ss-blue); }
.ss-dashboard .pay-icon.purple { background: rgba(124,58,237,0.10); color: var(--ss-purple); }
.ss-dashboard .pay-icon.amber { background: rgba(245,158,11,0.10); color: var(--ss-amber); }
.ss-dashboard .pay-icon svg { width: 15px; height: 15px; flex-shrink: 0; }
.ss-dashboard .pay-amount {
  font-size: 22px; font-weight: 800; color: var(--ss-text);
  letter-spacing: -0.02em; line-height: 1;
}
.ss-dashboard .pay-amount .curr { font-size: 12px; font-weight: 600; color: var(--ss-muted); margin-right: 2px; }
.ss-dashboard .pay-period { font-size: 10.5px; color: var(--ss-muted); }

.ss-dashboard .dual-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;
}
.ss-dashboard .dual-grid.single { grid-template-columns: 1fr; }

.ss-dashboard .branch-card,
.ss-dashboard .omset-card,
.ss-dashboard .chart-card {
  background: var(--ss-card); border-radius: var(--ss-r); padding: 20px 22px;
  border: 1px solid var(--ss-border); box-shadow: var(--ss-shadow-sm);
}
.ss-dashboard .branch-list { display: flex; flex-direction: column; gap: 0; }
.ss-dashboard .branch-bar-legend {
  display: flex; align-items: center; justify-content: flex-end; gap: 14px;
  margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dashed var(--ss-border);
}
.ss-dashboard .branch-bar-legend-item {
  display: flex; align-items: center; gap: 5px;
  font-size: 10px; font-weight: 500; color: var(--ss-muted);
}
.ss-dashboard .branch-bar-legend-dot {
  width: 14px; height: 5px; border-radius: 10px; flex-shrink: 0;
}
.ss-dashboard .branch-bar-legend-dot--current { background: var(--ss-blue); }
.ss-dashboard .branch-bar-legend-dot--prev {
  background: rgba(138, 143, 152, 0.35);
  border: 1px dashed rgba(138, 143, 152, 0.65);
  height: 4px;
}
.ss-dashboard .branch-row {
  display: flex; align-items: center; gap: 12px;
  padding: 11px 0; border-bottom: 1px solid var(--ss-border);
}
.ss-dashboard .branch-row.has-compare { align-items: flex-start; padding: 12px 0; }
.ss-dashboard .branch-row.has-compare .branch-dot { margin-top: 6px; }
.ss-dashboard .branch-row.has-compare .branch-name { margin-top: 4px; }
.ss-dashboard .branch-row.has-compare .branch-amount-col { margin-top: 2px; }
.ss-dashboard .branch-row:last-child { border-bottom: none; padding-bottom: 0; }
.ss-dashboard .branch-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.ss-dashboard .branch-name { font-size: 12.5px; font-weight: 600; flex: 1; min-width: 0; max-width: 88px; }
.ss-dashboard .branch-bars {
  flex: 2; display: flex; flex-direction: column; gap: 6px; min-width: 60px;
}
.ss-dashboard .branch-bar-row {
  display: flex; align-items: center; gap: 7px;
}
.ss-dashboard .branch-bar-tag {
  font-size: 9px; font-weight: 600; color: var(--ss-text);
  min-width: 54px; max-width: 82px; flex-shrink: 0;
  line-height: 1.25; text-align: right;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ss-dashboard .branch-bar-wrap {
  flex: 1; height: 6px; background: var(--ss-bg); border-radius: 10px; overflow: hidden; min-width: 48px;
}
.ss-dashboard .branch-row > .branch-bar-wrap {
  flex: 2; height: 5px; min-width: 60px;
}
.ss-dashboard .branch-bar {
  width: var(--bar-width, 0%);
  height: 100%;
  border-radius: 10px;
  transition: width 0.4s ease;
}
.ss-dashboard .branch-bar--prev {
  box-sizing: border-box;
}
.ss-dashboard .branch-amount-col {
  text-align: right; min-width: 96px; flex-shrink: 0;
}
.ss-dashboard .branch-amount {
  font-size: 13px; font-weight: 700; white-space: nowrap;
}
.ss-dashboard .branch-compare-line,
.ss-dashboard .branch-total-compare {
  display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 4px;
  margin-top: 3px;
}
.ss-dashboard .branch-compare-prev {
  font-size: 9.5px; color: var(--ss-muted); white-space: nowrap;
}
.ss-dashboard .branch-total-right {
  display: flex; flex-direction: column; align-items: flex-end; gap: 2px;
}
.ss-dashboard .branch-total-row {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  padding: 12px 14px; background: var(--ss-bg); border-radius: 8px; margin-top: 12px;
}
.ss-dashboard .branch-total-lbl { font-size: 11.5px; font-weight: 600; color: var(--ss-muted); }
.ss-dashboard .branch-total-val { font-size: 15px; font-weight: 800; color: var(--ss-red-ink); white-space: nowrap; }

.ss-dashboard .speedshop-summary-card .top-products-block {
  margin-top: 0; padding-top: 0; border-top: none;
}

.ss-dashboard .top-products-block {
  margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--ss-border);
}
.ss-dashboard .top-products-hd h3 {
  font-size: 12px; font-weight: 700; color: var(--ss-text);
}
.ss-dashboard .top-products-hd p {
  font-size: 11px; color: var(--ss-muted); margin-top: 2px;
}
.ss-dashboard .top-products-list { display: flex; flex-direction: column; gap: 8px; margin-top: 12px; }
.ss-dashboard .top-product-row {
  display: flex; align-items: center; gap: 10px;
  padding: 10px 12px; background: var(--ss-bg); border-radius: 9px;
}
.ss-dashboard .top-product-rank {
  width: 24px; height: 24px; border-radius: 7px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 800; color: #142000; background: var(--ss-red);
}
.ss-dashboard .top-product-row:nth-child(2) .top-product-rank { background: #374151; }
.ss-dashboard .top-product-row:nth-child(3) .top-product-rank { background: #9CA3AF; }
.ss-dashboard .top-product-info { flex: 1; min-width: 0; }
.ss-dashboard .top-product-name {
  font-size: 12.5px; font-weight: 600; color: var(--ss-text);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ss-dashboard .top-product-code { font-size: 10.5px; color: var(--ss-muted); margin-top: 2px; }
.ss-dashboard .top-product-meta { text-align: right; flex-shrink: 0; }
.ss-dashboard .top-product-qty { font-size: 12px; font-weight: 700; color: var(--ss-text); }
.ss-dashboard .top-product-value { font-size: 10.5px; color: var(--ss-muted); margin-top: 2px; }
.ss-dashboard .top-products-empty-text {
  margin-top: 12px; font-size: 12px; color: var(--ss-muted); text-align: center; padding: 12px 0;
}

.ss-dashboard .top-lists-section { margin-top: 4px; margin-bottom: 20px; }
.ss-dashboard .top-lists-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}
.ss-dashboard .top-lists-grid--trio {
  margin-top: 14px;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}
.ss-dashboard .top-list-card {
  background: var(--ss-card); border-radius: var(--ss-r); padding: 16px 18px;
  border: 1px solid var(--ss-border); box-shadow: var(--ss-shadow-sm);
  display: flex; flex-direction: column; min-height: 100%;
}
.ss-dashboard .top-list-card-hd h3 {
  font-size: 13px; font-weight: 700; color: var(--ss-text); line-height: 1.25;
}
.ss-dashboard .top-list-card-hd p {
  font-size: 11px; color: var(--ss-muted); margin-top: 3px;
}
.ss-dashboard .top-list-card-body {
  display: flex; flex-direction: column; gap: 7px; margin-top: 12px;
}
.ss-dashboard .top-list-row {
  display: flex; align-items: flex-start; gap: 10px;
  padding: 9px 10px; background: var(--ss-bg); border-radius: 8px;
}
.ss-dashboard .top-list-main {
  flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 6px;
}
.ss-dashboard .top-list-row-top {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;
}
.ss-dashboard .top-list-compare {
  display: flex; align-items: center; justify-content: space-between; gap: 8px;
  padding-top: 6px; border-top: 1px dashed rgba(15, 23, 42, 0.08);
}
.ss-dashboard .top-list-compare-prev {
  font-size: 9.5px; color: var(--ss-muted); line-height: 1.35;
  flex: 1; min-width: 0;
}
.ss-dashboard .top-list-card.has-compare .top-list-row { padding: 10px 10px 9px; }
.ss-dashboard .top-list-rank {
  width: 22px; height: 22px; border-radius: 6px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 10px; font-weight: 800; color: #fff; background: #374151;
}
.ss-dashboard .top-list-card--blue .top-list-row:first-child .top-list-rank { background: var(--ss-blue); }
.ss-dashboard .top-list-card--amber .top-list-row:first-child .top-list-rank { background: var(--ss-amber); }
.ss-dashboard .top-list-card--purple .top-list-row:first-child .top-list-rank { background: var(--ss-purple); }
.ss-dashboard .top-list-card--red .top-list-row:first-child .top-list-rank { background: var(--ss-red); }
.ss-dashboard .top-list-card--green .top-list-row:first-child .top-list-rank { background: var(--ss-green); }
.ss-dashboard .top-list-info { flex: 1; min-width: 0; }
.ss-dashboard .top-list-name {
  font-size: 12px; font-weight: 600; color: var(--ss-text);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ss-dashboard .top-list-code {
  font-size: 10px; color: var(--ss-muted); margin-top: 2px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ss-dashboard .top-list-meta { text-align: right; flex-shrink: 0; }
.ss-dashboard .top-list-qty { font-size: 11.5px; font-weight: 700; color: var(--ss-text); white-space: nowrap; }
.ss-dashboard .top-list-value { font-size: 10px; color: var(--ss-muted); margin-top: 2px; white-space: nowrap; }
.ss-dashboard .top-list-empty {
  margin-top: 12px; font-size: 12px; color: var(--ss-muted); text-align: center; padding: 16px 8px;
}

.ss-dashboard .omset-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.ss-dashboard .omset-cell {
  background: var(--ss-bg); border-radius: 9px; padding: 14px 16px; border-left: 3px solid transparent;
  position: relative;
}
.ss-dashboard .omset-cell.red { border-left-color: var(--ss-red-ink); }
.ss-dashboard .omset-cell.green { border-left-color: var(--ss-green); }
.ss-dashboard .omset-cell.blue { border-left-color: var(--ss-blue); }
.ss-dashboard .omset-cell.amber { border-left-color: var(--ss-amber); }
.ss-dashboard .omset-cell.purple { border-left-color: var(--ss-purple); }
.ss-dashboard .omset-cell-top {
  display: flex; align-items: flex-start; justify-content: space-between; gap: 8px;
  margin-bottom: 6px;
}
.ss-dashboard .omset-cell-top .omset-lbl { margin-bottom: 0; }
.ss-dashboard .omset-view-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 28px; height: 28px; flex-shrink: 0; margin-top: -2px;
  border-radius: 8px; border: 1px solid rgba(15, 23, 42, 0.08);
  background: #fff; color: #64748b; cursor: pointer;
  transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.ss-dashboard .omset-view-btn:hover {
  background: #fff1f2; color: var(--ss-red-ink); border-color: rgba(140, 250, 1, 0.25);
}
.ss-dashboard .omset-view-btn svg { width: 15px; height: 15px; }

.omset-detail-native-table { border-collapse: collapse; }
.omset-detail-native-table th,
.omset-detail-native-table td { vertical-align: middle; }

.ss-dashboard .omset-lbl {
  font-size: 10px; font-weight: 600; text-transform: uppercase;
  letter-spacing: 0.08em; color: var(--ss-muted); margin-bottom: 6px;
}
.ss-dashboard .omset-val { font-size: 17px; font-weight: 800; color: var(--ss-text); letter-spacing: -0.01em; }
.ss-dashboard .omset-val .curr { font-size: 10.5px; font-weight: 600; color: var(--ss-muted); }
.ss-dashboard .omset-sub { font-size: 10.5px; color: var(--ss-muted); margin-top: 3px; }
.ss-dashboard .omset-cell .dash-cmp-line { margin-top: 8px; }

.ss-dashboard .total-tagihan-cell {
  grid-column: 1 / -1; background: var(--ss-black); border-radius: 9px; padding: 14px 16px;
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  border-left: 3px solid var(--ss-red);
}
.ss-dashboard .total-tagihan-cell .omset-lbl { color: rgba(255,255,255,0.4); }
.ss-dashboard .total-tagihan-cell .omset-val { color: #fff; }
.ss-dashboard .total-tagihan-cell .omset-sub { color: rgba(255,255,255,0.35); }

.ss-dashboard .charts-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.ss-dashboard .chart-hd {
  display: flex; align-items: flex-start; justify-content: space-between;
  gap: 12px; margin-bottom: 4px; flex-wrap: wrap;
}
.ss-dashboard .chart-title { font-size: 13px; font-weight: 700; }
.ss-dashboard .chart-sub { font-size: 11px; color: var(--ss-muted); margin-top: 2px; }
.ss-dashboard .chart-legend { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.ss-dashboard .legend-item {
  display: flex; align-items: center; gap: 5px; font-size: 11px; color: var(--ss-muted);
}
.ss-dashboard .legend-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.ss-dashboard .legend-dot--ghost {
  border: 1.5px solid; border-radius: 3px; width: 10px; height: 10px;
}
.ss-dashboard .legend-dot--line {
  border-radius: 2px; width: 14px; height: 3px;
}
.ss-dashboard .legend-tag {
  font-size: 9px; font-weight: 600; text-transform: uppercase;
  letter-spacing: 0.04em; color: #94a3b8; margin-left: 2px;
}
.ss-dashboard .chart-area { margin-top: 16px; position: relative; }
.ss-dashboard .chart-canvas { height: 300px; }
.ss-dashboard .chart-canvas--split { height: 128px; }

.ss-dashboard .chart-card--penjualan .chart-hd,
.ss-dashboard .chart-card--pendapatan .chart-hd { margin-bottom: 12px; }
.ss-dashboard .chart-legend--compact { gap: 10px; }
.ss-dashboard .legend-dot--solid { width: 10px; height: 10px; border-radius: 3px; }
.ss-dashboard .legend-dot--dash {
  width: 16px; height: 0; border-top: 2px dashed rgba(138, 143, 152, 0.75);
  border-radius: 0; background: transparent !important;
}

.ss-dashboard .chart-penjualan-kpi {
  display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 14px;
}
.ss-dashboard .chart-kpi-cell {
  padding: 12px 14px; border-radius: 9px; background: var(--ss-bg);
  border: 1px solid var(--ss-border);
}
.ss-dashboard .chart-kpi-cell--part { border-left: 3px solid var(--ss-blue); }
.ss-dashboard .chart-kpi-cell--jasa { border-left: 3px solid var(--ss-red); }
.ss-dashboard .chart-kpi-cell--omset { border-left: 3px solid var(--ss-green); }
.ss-dashboard .chart-kpi-cell--tagihan { border-left: 3px solid var(--ss-purple); }
.ss-dashboard .chart-kpi-label {
  font-size: 10px; font-weight: 600; text-transform: uppercase;
  letter-spacing: 0.06em; color: var(--ss-muted);
}
.ss-dashboard .chart-kpi-value {
  margin-top: 4px; font-size: 16px; font-weight: 800; color: var(--ss-text);
  letter-spacing: -0.01em; line-height: 1.2;
}
.ss-dashboard .chart-kpi-compare {
  display: flex; align-items: center; justify-content: space-between; gap: 8px;
  margin-top: 8px; padding-top: 8px; border-top: 1px dashed rgba(15, 23, 42, 0.08);
}
.ss-dashboard .chart-kpi-prev { font-size: 10px; color: var(--ss-muted); flex: 1; min-width: 0; }

.ss-dashboard .chart-split {
  display: flex; flex-direction: column; gap: 14px; margin-top: 4px;
}
.ss-dashboard .chart-split-item {
  padding: 12px 12px 8px; border-radius: 9px; background: rgba(244, 245, 247, 0.55);
  border: 1px solid var(--ss-border);
}
.ss-dashboard .chart-split-hd {
  display: flex; align-items: center; gap: 7px; margin-bottom: 8px;
}
.ss-dashboard .chart-split-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.ss-dashboard .chart-split-title { font-size: 11.5px; font-weight: 600; color: var(--ss-text); }

.ss-dashboard .filter-date-year { width: 90px; }
.ss-dashboard .filter-export-group {
  margin-left: auto; display: flex; gap: 8px; flex-wrap: wrap;
}

.ss-dashboard .panel-card {
  background: var(--ss-card); border-radius: var(--ss-r); padding: 20px 22px;
  border: 1px solid var(--ss-border); box-shadow: var(--ss-shadow-sm);
  margin-bottom: 20px;
}
.ss-dashboard .panel-card-hd {
  display: flex; flex-direction: column; gap: 12px;
  margin-bottom: 16px;
}
@media (min-width: 640px) {
  .ss-dashboard .panel-card-hd {
    flex-direction: row; align-items: flex-start; justify-content: space-between;
  }
}
.ss-dashboard .panel-card-title { font-size: 15px; font-weight: 700; color: var(--ss-text); }
.ss-dashboard .panel-card-sub { font-size: 12px; color: var(--ss-muted); margin-top: 4px; max-width: 36rem; }
.ss-dashboard .table-wrap {
  overflow-x: auto; border-radius: 9px; border: 1px solid var(--ss-border);
}
.ss-dashboard .data-table {
  width: 100%; min-width: 1960px; border-collapse: collapse; text-align: left; font-size: 13px;
}
.ss-dashboard .data-table thead tr { background: var(--ss-bg); }
.ss-dashboard .data-table th {
  padding: 11px 12px; font-size: 10.5px; font-weight: 600; letter-spacing: 0.04em;
  text-transform: uppercase; color: var(--ss-muted); white-space: nowrap;
}
.ss-dashboard .data-table th.text-right,
.ss-dashboard .data-table td.text-right { text-align: right; }
.ss-dashboard .data-table th.text-center,
.ss-dashboard .data-table td.text-center { text-align: center; }
.ss-dashboard .data-table tbody tr { border-top: 1px solid var(--ss-border); transition: background 0.12s; }
.ss-dashboard .data-table tbody tr:hover { background: rgba(244, 245, 247, 0.85); }
.ss-dashboard .data-table td { padding: 11px 12px; color: #374151; vertical-align: middle; }
.ss-dashboard .data-table td strong { color: var(--ss-text); font-weight: 600; }
.ss-dashboard .data-table .amount-charge { color: #7C3AED; }
.ss-dashboard .data-table .amount-ppn { color: #9333EA; }
.ss-dashboard .data-table .amount-total { color: #059669; font-weight: 700; }
.ss-dashboard .btn-detail {
  display: inline-flex; align-items: center; justify-content: center; gap: 6px;
  border: 1px solid #fecaca; background: #fef2f2; color: #dc2626;
  border-radius: 8px; padding: 8px 12px; min-height: 36px;
  font-size: 12px; font-weight: 600; text-decoration: none; cursor: pointer;
  transition: background 0.15s, color 0.15s, border-color 0.15s; flex-shrink: 0;
}
.ss-dashboard .btn-detail:hover { background: var(--ss-red); color: #142000; border-color: var(--ss-red-ink); }
.ss-dashboard .btn-detail svg { width: 16px; height: 16px; flex-shrink: 0; }
.ss-dashboard .btn-detail.btn-detail-icon { width: 32px; height: 32px; min-height: 32px; padding: 0; }
.ss-dashboard .table-empty { padding: 40px 16px; text-align: center; color: var(--ss-muted); }
.ss-dashboard .mk-perf-section { margin-top: 8px; }
.ss-dashboard .mk-perf-card { margin-bottom: 14px; }
.ss-dashboard .mk-perf-table {
  width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;
}
.ss-dashboard .mk-perf-table thead tr { background: var(--ss-bg); }
.ss-dashboard .mk-perf-table th {
  padding: 11px 12px; font-size: 10.5px; font-weight: 600; letter-spacing: 0.04em;
  text-transform: uppercase; color: var(--ss-muted); white-space: nowrap;
}
.ss-dashboard .mk-perf-table th.text-right,
.ss-dashboard .mk-perf-table td.text-right { text-align: right; }
.ss-dashboard .mk-perf-table th.text-center,
.ss-dashboard .mk-perf-table td.text-center { text-align: center; }
.ss-dashboard .mk-perf-table tbody tr { border-top: 1px solid var(--ss-border); }
.ss-dashboard .mk-perf-table tbody tr:hover { background: rgba(244, 245, 247, 0.85); }
.ss-dashboard .mk-perf-table td { padding: 11px 12px; color: #374151; vertical-align: middle; }
.ss-dashboard .ss-target-table { font-size: 12px; }
.ss-dashboard .ss-target-table th,
.ss-dashboard .ss-target-table td { padding: 8px 8px; white-space: nowrap; }
.ss-dashboard .mk-perf-rank {
  display: inline-flex; align-items: center; justify-content: center;
  min-width: 22px; height: 22px; padding: 0 6px; border-radius: 6px;
  background: #f3f4f6; font-size: 11px; font-weight: 700; color: #111827;
}
.ss-dashboard .pagination-wrap { margin-top: 16px; }

@media (max-width: 1100px) {
  .ss-dashboard .payment-grid { grid-template-columns: repeat(2, 1fr); }
  .ss-dashboard .dual-grid,
  .ss-dashboard .charts-row { grid-template-columns: 1fr; }
  .ss-dashboard .top-lists-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .ss-dashboard .top-lists-grid--trio { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  /* Keep Gross / Nett / Biaya visible — previously display:none made accounting dash look empty */
  .ss-dashboard .hero-strip {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px 20px;
    align-items: start;
  }
  .ss-dashboard .hero-main {
    grid-column: 1 / -1;
    min-width: 0;
  }
  .ss-dashboard .hero-divider { display: none; }
  .ss-dashboard .hero-stat {
    display: block;
    min-width: 0;
    text-align: left;
    padding: 0;
  }
  .ss-dashboard .hero-stat-val {
    font-size: 16px;
    overflow-wrap: anywhere;
  }
}
@media (max-width: 640px) {
  .ss-dashboard .top-lists-grid,
  .ss-dashboard .top-lists-grid--trio { grid-template-columns: 1fr; }
}
</style>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/speedshop/dashboard-manager-template-styles.blade.php ENDPATH**/ ?>
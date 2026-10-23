<style>
.ss-wo-create {
  --ss-red: #8CFA01;
  --ss-red-ink: #3d6b00;
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

.ss-wo-create .wo-breadcrumb {
  display: flex; flex-wrap: wrap; align-items: center; gap: 8px;
  font-size: 12.5px; color: var(--ss-muted); margin-bottom: 4px;
}
.ss-wo-create .wo-breadcrumb a { color: var(--ss-muted); text-decoration: none; transition: color 0.15s; }
.ss-wo-create .wo-breadcrumb a:hover { color: var(--ss-text); }
.ss-wo-create .wo-breadcrumb .current { color: var(--ss-text); font-weight: 600; }

.ss-wo-create .wo-page-hd { margin-bottom: 4px; }
.ss-wo-create .wo-page-hd h1 { font-size: 20px; font-weight: 800; letter-spacing: -0.02em; }
.ss-wo-create .wo-page-hd p { font-size: 12.5px; color: var(--ss-muted); margin-top: 4px; }

.ss-wo-create .wo-alert {
  border-radius: 10px; border: 1px solid #fecaca; background: #fef2f2;
  padding: 14px 16px; font-size: 13px; color: #b91c1c;
}
.ss-wo-create .wo-alert ul { list-style: disc; padding-left: 18px; }

.ss-wo-create .panel-card {
  background: var(--ss-card); border-radius: var(--ss-r); padding: 20px 22px;
  border: 1px solid var(--ss-border); box-shadow: var(--ss-shadow-sm);
  position: relative; z-index: 1; overflow: visible;
}
.ss-wo-create .panel-card:has(.ts-wrapper.focus),
.ss-wo-create .panel-card:has(.ts-wrapper.dropdown-active),
.ss-wo-create .panel-card:has(.select2-container--open) {
  z-index: 40;
}
.ss-wo-create .panel-card-flush { padding: 0; overflow: visible; }
.ss-wo-create .panel-card-head {
  border-bottom: 1px solid var(--ss-border);
  background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);
  padding: 16px 22px;
}
.ss-wo-create .panel-card-body { padding: 20px 22px; }

.ss-wo-create .section-hd { margin-bottom: 16px; }
.ss-wo-create .section-hd h2,
.ss-wo-create .panel-card-title { font-size: 15px; font-weight: 700; color: var(--ss-text); }
.ss-wo-create .section-hd p,
.ss-wo-create .panel-card-sub { font-size: 12px; color: var(--ss-muted); margin-top: 4px; max-width: 40rem; }

.ss-wo-create .wo-badge {
  display: inline-flex; align-items: center; border-radius: 8px;
  background: var(--ss-bg); padding: 4px 10px; font-size: 11px; font-weight: 600; color: var(--ss-muted);
}

.ss-wo-create .type-toggle {
  display: inline-flex; flex-wrap: wrap; gap: 4px; border-radius: 10px;
  background: var(--ss-bg); padding: 4px; max-width: 100%;
}
.ss-wo-create .type-toggle button {
  border: none; border-radius: 8px; padding: 9px 14px; font-size: 12.5px; font-weight: 600;
  cursor: pointer; transition: all 0.15s; white-space: nowrap; background: transparent; color: #4b5563;
}
.ss-wo-create .type-toggle button.is-active {
  background: #fff; color: var(--ss-red-ink); box-shadow: var(--ss-shadow-sm);
}

.ss-wo-create .wo-label {
  display: block; margin-bottom: 6px; font-size: 10px; font-weight: 600;
  letter-spacing: 0.08em; text-transform: uppercase; color: var(--ss-muted);
}
.ss-wo-create .wo-label .req { color: var(--ss-red-ink); }

.ss-wo-create .wo-input,
.ss-wo-create .wo-textarea,
.ss-wo-create select.wo-native {
  width: 100%; border: 1px solid var(--ss-border); border-radius: 9px;
  background: #fff; padding: 9px 12px; font-size: 13px; color: var(--ss-text);
  font-family: inherit; transition: border-color 0.15s, box-shadow 0.15s;
}
.ss-wo-create .wo-input:focus,
.ss-wo-create .wo-textarea:focus,
.ss-wo-create select.wo-native:focus {
  outline: none; border-color: rgba(140,250,1,0.45); box-shadow: 0 0 0 3px rgba(140,250,1,0.08);
}
.ss-wo-create .wo-input:disabled,
.ss-wo-create select.wo-native:disabled { background: var(--ss-bg); color: #6b7280; }

.ss-wo-create .wo-icon-btn {
  display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;
  width: 38px; height: 38px; border-radius: 9px; border: 1px solid rgba(140,250,1,0.25);
  background: #fff; color: var(--ss-red-ink); cursor: pointer; transition: background 0.15s;
}
.ss-wo-create .wo-icon-btn:hover { background: rgba(140,250,1,0.06); }
.ss-wo-create .wo-icon-btn:disabled { opacity: 0.45; cursor: not-allowed; }

.ss-wo-create .btn-primary {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  border: none; border-radius: 9px; background: var(--ss-red); color: #142000;
  padding: 10px 18px; font-size: 13px; font-weight: 600; cursor: pointer; transition: opacity 0.15s;
}
.ss-wo-create .btn-primary:hover { opacity: 0.9; }
.ss-wo-create .btn-primary:disabled { opacity: 0.55; cursor: not-allowed; }

.ss-wo-create .btn-secondary {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  border: 1px solid var(--ss-border); border-radius: 9px; background: #fff; color: #374151;
  padding: 10px 18px; font-size: 13px; font-weight: 500; text-decoration: none; transition: border-color 0.15s;
}
.ss-wo-create .btn-secondary:hover { border-color: #bbb; }

.ss-wo-create .table-wrap {
  overflow-x: auto; border-radius: 9px; border: 1px solid var(--ss-border); margin-top: 14px;
}
.ss-wo-create .data-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
.ss-wo-create .data-table thead tr { background: var(--ss-bg); }
.ss-wo-create .data-table th {
  padding: 11px 14px; font-size: 10.5px; font-weight: 600; letter-spacing: 0.04em;
  text-transform: uppercase; color: var(--ss-muted); white-space: nowrap;
}
.ss-wo-create .data-table th.text-right, .ss-wo-create .data-table td.text-right { text-align: right; }
.ss-wo-create .data-table th.text-center, .ss-wo-create .data-table td.text-center { text-align: center; }
.ss-wo-create .data-table tbody tr { border-top: 1px solid var(--ss-border); transition: background 0.12s; }
.ss-wo-create .data-table tbody tr:hover { background: rgba(244,245,247,0.85); }
.ss-wo-create .data-table td { padding: 11px 14px; color: #374151; vertical-align: middle; }
.ss-wo-create .data-table .empty-cell { padding: 32px 14px; text-align: center; color: var(--ss-muted); }

.ss-wo-create .wo-notice {
  margin-top: 14px; border-radius: 9px; border: 1px solid #fde68a;
  background: #fffbeb; padding: 12px 14px; font-size: 12.5px; color: #92400e;
}

.ss-wo-create .summary-card {
  position: sticky; top: 88px;
}
.ss-wo-create .summary-row {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  border-radius: 9px; background: var(--ss-bg); padding: 12px 14px; margin-bottom: 8px;
}
.ss-wo-create .summary-row span:first-child { color: #4b5563; font-size: 12.5px; font-weight: 500; }
.ss-wo-create .summary-row span:last-child { color: var(--ss-text); font-size: 13px; font-weight: 700; }
.ss-wo-create .summary-total {
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
  border-radius: 10px; background: var(--ss-black); padding: 14px 16px; margin-top: 4px;
}
.ss-wo-create .summary-total span:first-child { color: rgba(255,255,255,0.55); font-size: 12px; font-weight: 600; }
.ss-wo-create .summary-total span:last-child { color: #fff; font-size: 15px; font-weight: 800; }

.ss-wo-create .pay-toggle {
  display: flex; border-radius: 9px; border: 1px solid var(--ss-border); background: var(--ss-bg); padding: 3px;
}
.ss-wo-create .pay-toggle button {
  flex: 1; border: none; border-radius: 7px; padding: 8px 10px; font-size: 11.5px; font-weight: 600;
  cursor: pointer; background: transparent; color: var(--ss-muted); transition: all 0.15s;
}
.ss-wo-create .pay-toggle button.is-active {
  background: #fff; color: var(--ss-text); box-shadow: var(--ss-shadow-sm);
}

.ss-wo-create .wo-modal-panel {
  background: #fff; border-radius: 14px; border: 1px solid var(--ss-border);
  box-shadow: var(--ss-shadow-md);
}
.ss-wo-create .wo-modal-panel h3 { font-size: 18px; font-weight: 700; }
.ss-wo-create .wo-modal-panel > p { font-size: 12.5px; color: var(--ss-muted); margin-top: 4px; }

/* Teleported modals (outside .ss-wo-create) */
.wo-modal-root {
  position: fixed;
  inset: 0;
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
}
.wo-modal-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(13, 13, 13, 0.48);
  backdrop-filter: blur(2px);
  -webkit-backdrop-filter: blur(2px);
}
.wo-modal-center {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 28rem;
  padding: 1rem;
  pointer-events: none;
}
.wo-modal-panel {
  pointer-events: auto;
  display: flex;
  flex-direction: column;
  width: 100%;
  max-height: min(88vh, 640px);
  overflow: hidden;
  background: #fff;
  border-radius: 16px;
  border: 1px solid rgba(0,0,0,0.08);
  box-shadow: 0 24px 64px rgba(0,0,0,0.22);
}
.wo-modal-hd {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 18px 20px 14px;
  border-bottom: 1px solid rgba(0,0,0,0.06);
}
.wo-modal-hd h3 {
  margin: 0;
  font-size: 17px;
  font-weight: 700;
  color: #0D0D0D;
  letter-spacing: -0.01em;
}
.wo-modal-hd p {
  margin: 4px 0 0;
  font-size: 12.5px;
  color: #8A8F98;
  line-height: 1.4;
}
.wo-modal-close {
  display: inline-flex;
  height: 32px;
  width: 32px;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  border: 1px solid rgba(0,0,0,0.08);
  background: #fff;
  color: #6B7280;
  transition: background .12s ease, color .12s ease;
}
.wo-modal-close:hover { background: #F3F4F6; color: #111827; }
.wo-modal-close svg { width: 16px; height: 16px; }
.wo-modal-bd {
  flex: 1;
  overflow-y: auto;
  padding: 16px 20px;
}
.wo-modal-section {
  border-radius: 12px;
  border: 1px solid rgba(0,0,0,0.06);
  background: #F8F9FB;
  padding: 14px;
}
.wo-modal-section-label {
  margin: 0 0 12px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: #8A8F98;
}
.wo-modal-label {
  display: block;
  margin-bottom: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #4B5563;
}
.wo-modal-input {
  width: 100%;
  height: 40px;
  border-radius: 10px;
  border: 1px solid rgba(0,0,0,0.1);
  background: #fff;
  padding: 0 12px;
  font-size: 13px;
  color: #111827;
  outline: none;
  transition: border-color .12s ease, box-shadow .12s ease;
}
.wo-modal-input:focus {
  border-color: rgba(140,250,1,0.45);
  box-shadow: 0 0 0 3px rgba(140,250,1,0.08);
}
.wo-modal-textarea {
  height: auto;
  min-height: 72px;
  padding: 10px 12px;
  resize: vertical;
}
.wo-modal-ft {
  display: flex;
  gap: 10px;
  padding: 14px 20px 18px;
  border-top: 1px solid rgba(0,0,0,0.06);
  background: #FAFAFB;
}
.wo-modal-btn-ghost,
.wo-modal-btn-primary {
  display: inline-flex;
  height: 40px;
  flex: 1;
  align-items: center;
  justify-content: center;
  border-radius: 11px;
  font-size: 13px;
  font-weight: 600;
  transition: background .12s ease, transform .1s ease, opacity .12s ease;
}
.wo-modal-btn-ghost {
  border: 1px solid rgba(0,0,0,0.1);
  background: #fff;
  color: #374151;
}
.wo-modal-btn-ghost:hover { background: #F3F4F6; }
.wo-modal-btn-primary {
  border: none;
  background: #8CFA01;
  color: #142000;
  box-shadow: 0 8px 18px rgba(140, 250, 1, 0.28);
}
.wo-modal-btn-primary:hover { background: #6ed000; }
.wo-modal-btn-primary:active,
.wo-modal-btn-ghost:active { transform: scale(0.98); }
.wo-modal-btn-primary:disabled { opacity: 0.55; cursor: not-allowed; }

.ss-wo-create .ts-wrapper.single .ts-control {
  border: 1px solid var(--ss-border) !important; border-radius: 9px !important;
  min-height: 40px !important; box-shadow: none !important;
}
.ss-wo-create .ts-wrapper.single.focus .ts-control {
  border-color: rgba(140,250,1,0.45) !important;
  box-shadow: 0 0 0 3px rgba(140,250,1,0.08) !important;
}

body > .ts-dropdown {
  z-index: 70;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
body > .ts-dropdown .ts-dropdown-content {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  overscroll-behavior: contain;
  -webkit-overflow-scrolling: touch;
}

@media (max-width: 1024px) {
  .ss-wo-create .summary-card { position: static; }
}

/* Selaraskan komponen Tailwind yang sudah ada */
.ss-wo-create input.rounded-xl:not([type="checkbox"]):not([type="radio"]),
.ss-wo-create select.rounded-xl,
.ss-wo-create textarea.rounded-xl {
  border-color: var(--ss-border);
  font-size: 13px;
}
.ss-wo-create input.rounded-xl:focus,
.ss-wo-create select.rounded-xl:focus,
.ss-wo-create textarea.rounded-xl:focus {
  border-color: rgba(140,250,1,0.45);
  box-shadow: 0 0 0 3px rgba(140,250,1,0.08);
}
.ss-wo-create label[class*="uppercase"] {
  font-size: 10px !important; font-weight: 600 !important;
  letter-spacing: 0.08em !important; color: var(--ss-muted) !important;
}
.ss-wo-create .panel-card .rounded-2xl.border,
.ss-wo-create .rounded-2xl.border.border-gray-200.bg-white.p-5 {
  border-color: var(--ss-border) !important;
  box-shadow: var(--ss-shadow-sm) !important;
  border-radius: var(--ss-r) !important;
}
.ss-wo-create button.rounded-xl.bg-brand,
.ss-wo-create button.flex.h-11.w-full.bg-brand {
  background: var(--ss-red) !important;
  box-shadow: none !important;
  border-radius: 9px !important;
}

/* Detail WIP / selesaikan — panel cards */
.ss-wo-create .panel-card.panel-card-flush > .flex.border-b.border-gray-100,
.ss-wo-create .panel-card.panel-card-flush > .flex.flex-col.border-b.border-gray-100 {
  border-color: var(--ss-border) !important;
  background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);
}
.ss-wo-create .panel-card.summary-card.panel-card-flush .section-hd {
  padding: 20px 22px 0;
  margin-bottom: 0;
}
.ss-wo-create .panel-card.summary-card.panel-card-flush .section-hd + .mt-2 {
  padding: 12px 22px 0;
}
.ss-wo-create .panel-card.summary-card.panel-card-flush > .border-t.border-gray-100,
.ss-wo-create .panel-card.summary-card.panel-card-flush > .space-y-3.border-t.border-gray-100 {
  padding-left: 22px;
  padding-right: 22px;
}
.ss-wo-create .panel-card .border-b.border-gray-100,
.ss-wo-create .panel-card .border-t.border-gray-100 {
  border-color: var(--ss-border) !important;
}
.ss-wo-create .panel-card h2.text-\[20px\] {
  font-size: 15px !important;
  font-weight: 700 !important;
  letter-spacing: normal !important;
  color: var(--ss-text) !important;
}
.ss-wo-create .panel-card table thead tr {
  background: var(--ss-bg) !important;
}
.ss-wo-create .panel-card table th {
  font-size: 10.5px !important;
  letter-spacing: 0.04em !important;
  color: var(--ss-muted) !important;
  text-transform: uppercase;
}
.ss-wo-create .panel-card table tbody tr:hover {
  background: rgba(244, 245, 247, 0.85);
}
.ss-wo-create .panel-card .inline-flex.items-center.gap-2.rounded-lg.border.border-red-200 {
  border-color: rgba(140, 250, 1, 0.22) !important;
  background: rgba(140, 250, 1, 0.06) !important;
  color: var(--ss-red-ink) !important;
  border-radius: 8px !important;
}
.ss-wo-create .rounded-2xl.border.border-amber-200,
.ss-wo-create .rounded-2xl.border.border-rose-200 {
  border-radius: 10px !important;
}
</style>

<style>
.izin-page .validasi-table-wrap { overflow-x: auto; padding: 0 22px 22px; }
.izin-page .validasi-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.izin-page .validasi-table th {
    text-align: left;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--ink-faint);
    padding: 12px 10px;
    border-bottom: 1px solid var(--line-soft);
    white-space: nowrap;
}
.izin-page .validasi-table td {
    padding: 14px 10px;
    border-bottom: 1px solid var(--line-soft);
    vertical-align: middle;
    color: var(--ink-soft);
}
.izin-page .validasi-table tbody tr:hover { background: #fafafa; }
.izin-page .validasi-table .cell-name {
    font-weight: 700;
    color: var(--ink);
}
.izin-page .validasi-table .cell-meta {
    font-size: 11.5px;
    color: var(--ink-faint);
    margin-top: 2px;
}
.izin-page .validasi-table .reason-cell {
    max-width: 280px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.izin-page a.btn-detail,
.izin-page button.btn-detail {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 36px;
    padding: 0 14px;
    border: none;
    border-radius: 10px;
    background: #dc2626;
    color: #fff !important;
    font-size: 12px;
    font-weight: 700;
    text-decoration: none !important;
    cursor: pointer;
    white-space: nowrap;
    box-shadow: 0 4px 14px rgba(220, 38, 38, 0.28);
    font-family: inherit;
}
.izin-page a.btn-detail:hover,
.izin-page button.btn-detail:hover {
    background: #b91c1c;
    color: #fff !important;
}
.izin-page .btn-detail-ghost {
    background: #fff;
    color: var(--ink) !important;
    border: 1px solid var(--line);
    box-shadow: none;
}
.izin-page .btn-detail-ghost:hover {
    background: #fafafa;
    color: var(--ink) !important;
}
.izin-page .panel-head-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px 16px;
}
.izin-page .panel-head-row .panel-head-text { min-width: 0; flex: 1 1 auto; }
.izin-page .panel-head-row .btn-panel-action {
    flex-shrink: 0;
    gap: 6px;
    margin-left: auto;
}

/* —— Modal pengajuan (premium) —— */
.izin-create-dialog {
    border: none;
    padding: 0;
    margin: auto;
    max-width: min(560px, calc(100vw - 28px));
    width: 100%;
    background: transparent;
    overflow: visible;
}
.izin-create-dialog::backdrop {
    background: rgba(15, 23, 42, 0.52);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
.izin-create-dialog[open] {
    animation: izinModalFadeIn 0.28s ease-out;
}
.izin-create-dialog[open]::backdrop {
    animation: izinBackdropFadeIn 0.28s ease-out;
}
@keyframes izinModalFadeIn {
    from { opacity: 0; transform: translateY(12px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes izinBackdropFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.izin-create-dialog .izin-modal-shell {
    overflow: visible;
    border-radius: 22px;
    border: 1px solid rgba(255, 255, 255, 0.65);
    background: #fff;
    box-shadow:
        0 0 0 1px rgba(15, 23, 42, 0.04),
        0 24px 48px rgba(15, 23, 42, 0.14),
        0 48px 96px rgba(15, 23, 42, 0.08);
}
.izin-create-dialog .izin-modal-sheet-handle {
    display: none;
}
.izin-create-dialog .izin-modal-form {
    display: block;
}
.izin-create-dialog .izin-modal-hero {
    position: relative;
    border-radius: 22px 22px 0 0;
    overflow: hidden;
    padding: 24px 24px 20px;
    background:
        linear-gradient(135deg, rgba(213, 36, 28, 0.09) 0%, transparent 50%),
        linear-gradient(180deg, #fafafa 0%, #fff 100%);
    border-bottom: 1px solid #eceae4;
}
.izin-create-dialog .izin-modal-hero::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 2px;
    background: linear-gradient(90deg, #d5241c 0%, #f97316 45%, transparent 100%);
}
.izin-create-dialog .izin-modal-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}
.izin-create-dialog .izin-modal-hero-main {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    min-width: 0;
}
.izin-create-dialog .izin-modal-icon {
    width: 48px;
    height: 48px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: #fff;
    border: 1px solid #eceae4;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
    font-size: 22px;
    line-height: 1;
}
.izin-create-dialog .izin-modal-eyebrow {
    margin: 0;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: #d5241c;
}
.izin-create-dialog .izin-modal-hero h3 {
    margin: 4px 0 0;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 26px;
    font-weight: 700;
    letter-spacing: 0.02em;
    line-height: 1.1;
    color: #181819;
}
.izin-create-dialog .izin-modal-sub {
    margin: 8px 0 0;
    font-size: 13px;
    line-height: 1.5;
    color: #6e7078;
    max-width: 340px;
}
.izin-create-dialog .izin-modal-close {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    border: 1px solid #e9e7e2;
    border-radius: 12px;
    background: #fff;
    color: #64748b;
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
}
.izin-create-dialog .izin-modal-close:hover {
    background: #f8fafc;
    color: #0f172a;
    border-color: #cbd5e1;
}
.izin-create-dialog .izin-modal-close svg { width: 18px; height: 18px; }
.izin-create-dialog .izin-modal-body {
    position: relative;
    padding: 22px 24px 8px;
    max-height: min(62vh, 520px);
    overflow-y: auto;
}
.izin-create-dialog .izin-modal-body .field { margin-bottom: 20px; }
.izin-create-dialog .izin-modal-body .field-label {
    font-size: 10px;
    letter-spacing: 0.08em;
    margin-bottom: 10px;
}
.izin-create-dialog .type-pills {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}
/* 6 opsi: baris 2 · 2 · 2 */
.izin-create-dialog .type-pills.is-rows-2-2-2 {
    grid-template-columns: repeat(2, 1fr);
}
/* 7 opsi: baris 3 · 2 · 2 */
.izin-create-dialog .type-pills.is-rows-3-2-2 {
    grid-template-columns: repeat(6, 1fr);
}
.izin-create-dialog .type-pills.is-rows-3-2-2 .type-pill:nth-child(-n+3) {
    grid-column: span 2;
}
.izin-create-dialog .type-pills.is-rows-3-2-2 .type-pill:nth-child(n+4) {
    grid-column: span 3;
}
.izin-create-dialog .type-pill span {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 14px 10px;
    border-radius: 14px;
    border: 1.5px solid #e9e7e2;
    background: #fff;
    font-size: 12px;
    font-weight: 700;
    color: #6e7078;
    transition: 0.15s ease;
    text-align: center;
}
.izin-create-dialog .type-pill input { position: absolute; opacity: 0; pointer-events: none; }
.izin-create-dialog .type-pill { position: relative; cursor: pointer; }
.izin-create-dialog .type-pill input:checked + span {
    border-color: #d5241c;
    background: rgba(213, 36, 28, 0.06);
    color: #b91c1c;
    box-shadow: 0 0 0 1px rgba(213, 36, 28, 0.08);
}
.izin-create-dialog .type-pill em { font-style: normal; font-size: 18px; }
.izin-create-dialog .date-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.izin-create-dialog .date-row.is-single {
    grid-template-columns: 1fr;
}
.izin-create-dialog .field-label {
    display: block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #a6a8ae;
    margin-bottom: 10px;
}
.izin-create-dialog .field-input,
.izin-create-dialog .field-textarea {
    width: 100%;
    border: 1px solid #e9e7e2;
    border-radius: 12px;
    background: #fafaf9;
    font-size: 14px;
    font-weight: 500;
    color: #181819;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
}
.izin-create-dialog .field-input { height: 44px; padding: 0 14px; }
.izin-create-dialog .field-textarea { padding: 12px 14px; min-height: 110px; resize: vertical; }
.izin-create-dialog .field-input:focus,
.izin-create-dialog .field-textarea:focus {
    background: #fff;
    border-color: rgba(213, 36, 28, 0.45);
    box-shadow: 0 0 0 3px rgba(213, 36, 28, 0.1);
}
.izin-create-dialog .izin-modal-alert {
    margin-bottom: 18px;
    padding: 12px 14px;
    border-radius: 12px;
    border: 1px solid #fecaca;
    background: #fef2f2;
    font-size: 12px;
    font-weight: 600;
    color: #b91c1c;
    line-height: 1.45;
}
.izin-create-dialog .izin-modal-alert ul {
    margin: 6px 0 0;
    padding-left: 18px;
}
.izin-create-dialog .izin-modal-footer {
    position: relative;
    z-index: 1;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 24px 22px;
    border-top: 1px solid #f1efea;
    border-radius: 0 0 22px 22px;
    background: linear-gradient(180deg, #fafaf9 0%, #fff 100%);
}
.izin-create-dialog .btn-modal-cancel {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 44px;
    padding: 0 18px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #475569;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    transition: background 0.15s ease;
}
.izin-create-dialog .btn-modal-cancel:hover { background: #f8fafc; }
.izin-create-dialog .btn-modal-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 44px;
    padding: 0 22px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(145deg, #8CFA01, #6ed000);
    color: #142000;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    font-family: inherit;
    box-shadow: 0 8px 22px rgba(140, 250, 1, 0.28);
    transition: filter 0.15s ease, transform 0.12s ease;
}
.izin-create-dialog .btn-modal-submit:hover { filter: brightness(0.96); }
.izin-create-dialog .btn-modal-submit:active { transform: scale(0.98); }

@media (max-width: 640px) {
    .izin-create-dialog {
        inset: auto 0 0 0;
        top: auto;
        max-width: 100%;
        width: 100%;
        height: min(94dvh, 100%);
        max-height: 94dvh;
        margin: 0;
        padding: 0;
        border-radius: 0;
        overflow: hidden;
        background: transparent;
    }
    .izin-create-dialog[open] {
        animation: izinSheetUp 0.32s cubic-bezier(0.22, 1, 0.36, 1);
    }
    @keyframes izinSheetUp {
        from { transform: translateY(18px); opacity: 0.6; }
        to { transform: translateY(0); opacity: 1; }
    }
    .izin-create-dialog .izin-modal-shell {
        display: flex;
        flex-direction: column;
        height: 100%;
        max-height: 94dvh;
        border-radius: 22px 22px 0 0;
        border: none;
        box-shadow: 0 -8px 40px rgba(15, 23, 42, 0.18);
        overflow: hidden;
        background: #fff;
    }
    .izin-create-dialog .izin-modal-sheet-handle {
        display: block;
        flex-shrink: 0;
        width: 40px;
        height: 4px;
        margin: 10px auto 0;
        border-radius: 999px;
        background: #e2e0db;
    }
    .izin-create-dialog .izin-modal-hero {
        border-radius: 0;
        padding: 10px 18px 14px;
        flex-shrink: 0;
        background: #fff;
        border-bottom: 1px solid #f1efea;
    }
    .izin-create-dialog .izin-modal-hero::after {
        height: 1.5px;
        background: linear-gradient(90deg, #d5241c 0%, rgba(213, 36, 28, 0.15) 55%, transparent 100%);
    }
    .izin-create-dialog .izin-modal-icon {
        display: none;
    }
    .izin-create-dialog .izin-modal-eyebrow {
        font-size: 9px;
        letter-spacing: 0.16em;
    }
    .izin-create-dialog .izin-modal-hero h3 {
        margin-top: 2px;
        font-size: 22px;
        letter-spacing: 0.01em;
    }
    .izin-create-dialog .izin-modal-sub {
        display: none;
    }
    .izin-create-dialog .izin-modal-close {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        border-color: #eceae4;
        background: #fafaf9;
    }
    /* Form = kolom flex agar body bisa scroll, footer tetap di bawah */
    .izin-create-dialog .izin-modal-form {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
    }
    .izin-create-dialog .izin-modal-body {
        flex: 1 1 auto;
        min-height: 0;
        max-height: none;
        overflow-x: hidden;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        padding: 16px 18px 20px;
        touch-action: pan-y;
    }
    .izin-create-dialog .izin-modal-body .field {
        margin-bottom: 18px;
    }
    .izin-create-dialog .izin-modal-body .field:last-child {
        margin-bottom: 4px;
    }
    .izin-create-dialog .field-label {
        margin-bottom: 8px;
        color: #8b8d94;
    }
    /* 7 opsi: 3 · 2 · 2 | 6 opsi: 2 · 2 · 2 */
    .izin-create-dialog .type-pills.is-rows-3-2-2 {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 8px;
    }
    .izin-create-dialog .type-pills.is-rows-3-2-2 .type-pill:nth-child(-n+3) {
        grid-column: span 2;
    }
    .izin-create-dialog .type-pills.is-rows-3-2-2 .type-pill:nth-child(n+4) {
        grid-column: span 3;
    }
    .izin-create-dialog .type-pills.is-rows-2-2-2 {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }
    .izin-create-dialog .type-pills.is-rows-2-2-2 .type-pill:nth-child(-n+3),
    .izin-create-dialog .type-pills.is-rows-2-2-2 .type-pill:nth-child(n+4) {
        grid-column: auto;
    }
    .izin-create-dialog .type-pill > span {
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        gap: 5px;
        padding: 10px 6px;
        min-height: 64px;
        border-radius: 14px;
        border: 1px solid #ebe9e4;
        background: #fafaf9;
        font-size: 11px;
        font-weight: 650;
        line-height: 1.25;
        box-shadow: none;
    }
    .izin-create-dialog .type-pill input:checked + span {
        border-color: #d5241c;
        background: #fff5f4;
        color: #b91c1c;
        box-shadow: inset 0 0 0 1px rgba(213, 36, 28, 0.12);
    }
    .izin-create-dialog .type-pill em {
        font-size: 16px;
        width: auto;
        text-align: center;
        flex-shrink: 0;
        line-height: 1;
    }
    .izin-create-dialog .date-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    .izin-create-dialog .field-input,
    .izin-create-dialog .field-textarea {
        border-radius: 14px;
        border-color: #ebe9e4;
        background: #fafaf9;
    }
    .izin-create-dialog .field-textarea {
        min-height: 96px;
    }
    /* 16px: cegah auto-zoom iOS saat fokus input */
    .izin-create-dialog .field-input,
    .izin-create-dialog .field-textarea,
    .izin-create-dialog .izin-date-wrap input,
    .izin-create-dialog .flatpickr-input,
    .izin-create-dialog .flatpickr-mobile {
        font-size: 16px !important;
    }
    .izin-create-dialog .izin-modal-footer {
        flex-shrink: 0;
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
        padding: 12px 18px calc(14px + env(safe-area-inset-bottom, 0px));
        border-radius: 0;
        border-top: 1px solid #f1efea;
        background: #fff;
        position: relative;
        z-index: 2;
        box-shadow: 0 -10px 24px rgba(15, 23, 42, 0.04);
    }
    .izin-create-dialog .btn-modal-cancel,
    .izin-create-dialog .btn-modal-submit {
        width: 100%;
        height: 48px;
        border-radius: 14px;
        font-size: 14px;
    }
    .izin-create-dialog .btn-modal-submit {
        order: -1;
        box-shadow: 0 10px 24px rgba(140, 250, 1, 0.26);
    }
    .izin-create-dialog .btn-modal-cancel {
        background: #fafaf9;
        border-color: #ebe9e4;
        color: #64748b;
    }
    /* Kalender flatpickr fallback */
    .izin-create-dialog .izin-date-wrap .flatpickr-calendar {
        width: min(100%, 320px) !important;
        max-width: calc(100vw - 36px) !important;
        left: 0 !important;
        right: 0 !important;
        font-size: 13px;
    }
    .izin-create-dialog .flatpickr-days,
    .izin-create-dialog .dayContainer {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
    }
    .izin-create-dialog .flatpickr-day {
        max-width: none;
        height: 36px;
        line-height: 36px;
    }
}

@media (max-width: 640px) {
    .izin-page .hero-card {
        padding: 16px;
        border-radius: 14px;
        margin-bottom: 14px;
    }
    .izin-page .hero-card .title { font-size: 22px; }
    .izin-page .stat-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        margin-bottom: 14px;
    }
    .izin-page .stat-card {
        padding: 12px 10px;
        border-radius: 12px;
    }
    .izin-page .stat-card .v { font-size: 24px; }
    .izin-page .stat-card .k { font-size: 10px; letter-spacing: .04em; }
    .izin-page .panel { border-radius: 16px; }
    .izin-page .panel-head { padding: 14px 16px; }
    .izin-page .panel-head-row {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }
    .izin-page .btn-panel-action {
        width: 100%;
        justify-content: center;
    }
    .izin-page .validasi-table-wrap {
        padding: 0 12px 16px;
        -webkit-overflow-scrolling: touch;
    }
    .izin-page .validasi-table th,
    .izin-page .validasi-table td {
        padding: 10px 8px;
        font-size: 12px;
    }
}
</style>

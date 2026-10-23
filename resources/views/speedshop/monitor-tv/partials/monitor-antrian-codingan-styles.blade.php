<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    color: #fff;
    font-family: 'Inter', sans-serif;
    height: 100vh;
    height: 100dvh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background-color: #0a0a0a;
    background-image: url("{{ asset('images/monitor-antrian-content-bg.png') }}");
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
  }

  /* ===== HEADER ===== */
  .header {
    flex-shrink: 0;
    background-color: #0d0d0d;
    background-image:
      linear-gradient(135deg, rgba(13, 13, 13, 0.5) 0%, rgba(13, 13, 13, 0.25) 42%, rgba(13, 13, 13, 0.45) 100%),
      url("{{ asset('images/monitor-antrian-header-bg.png') }}");
    background-size: cover, cover;
    background-position: center, center;
    background-repeat: no-repeat, no-repeat;
    border-bottom: 1px solid #333;
    padding: 0 28px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
  }

  .header-left,
  .header-center,
  .header-right {
    position: relative;
    z-index: 2;
  }

  .header::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, #c0392b 20%, #c0392b 80%, transparent);
    z-index: 3;
  }

  /* Red diagonal accent top-right */
  .header-red-accent {
    position: absolute;
    top: 0; right: 0;
    width: 180px;
    height: 80px;
    background: linear-gradient(135deg, transparent 40%, rgba(192,57,43,0.5) 100%);
    pointer-events: none;
    z-index: 1;
  }

  .header-left {
    display: flex;
    align-items: center;
    gap: 14px;
  }

  .logo-box {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .logo-box img {
    width: 44px;
    height: 44px;
    display: block;
    object-fit: contain;
  }

  .brand-name {
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 18px;
    letter-spacing: 0.06em;
    color: #fff;
    line-height: 1;
  }

  .brand-sub {
    font-size: 11px;
    color: #888;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-top: 3px;
  }

  .header-center {
    text-align: center;
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
  }

  .monitor-label {
    font-size: 11px;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    color: #c0392b;
    font-weight: 600;
    margin-bottom: 5px;
  }

  .monitor-title {
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 26px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #fff;
  }

  .header-right {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .clock-time {
    font-family: 'Orbitron', sans-serif;
    font-weight: 700;
    font-size: 36px;
    letter-spacing: 0.04em;
    color: #fff;
    line-height: 1;
  }

  .live-row {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 4px;
    justify-content: flex-end;
  }

  .live-dot {
    width: 8px; height: 8px;
    background: #22c55e;
    border-radius: 50%;
    animation: blink 1.5s infinite;
  }

  @keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
  }

  .live-text {
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.1em;
    color: #22c55e;
  }

  .clock-date {
    font-size: 12px;
    color: #888;
    text-align: right;
    margin-top: 2px;
  }

  /* ===== STATS ROW ===== */
  .stats-row {
    flex-shrink: 0;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    padding: 14px 20px;
    background: transparent;
  }

  .stat-card {
    background: #161616;
    border: 1px solid #2a2a2a;
    border-radius: 12px;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
  }

  .stat-icon {
    width: 46px; height: 46px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 20px;
  }

  .stat-icon.green  { border: 2px solid #22c55e; color: #22c55e; background: rgba(34,197,94,0.08); }
  .stat-icon.orange { border: 2px solid #f59e0b; color: #f59e0b; background: rgba(245,158,11,0.08); }
  .stat-icon.blue   { border: 2px solid #3b82f6; color: #3b82f6; background: rgba(59,130,246,0.08); }
  .stat-icon.purple { border: 2px solid #a855f7; color: #a855f7; background: rgba(168,85,247,0.08); }

  .stat-num {
    font-family: 'Orbitron', 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 30px;
    line-height: 1;
    min-width: 1.5ch;
    flex-shrink: 0;
  }

  .stat-num.green  { color: #22c55e; }
  .stat-num.orange { color: #f59e0b; }
  .stat-num.blue   { color: #3b82f6; }
  .stat-num.purple { color: #a855f7; }

  .stat-info { }
  .stat-title {
    font-weight: 700;
    font-size: 13px;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #fff;
    margin-top: 2px;
  }
  .stat-sub {
    font-size: 11px;
    color: #666;
    margin-top: 2px;
  }

  /* ===== MAIN PANELS ===== */
  .panels {
    flex: 1;
    min-height: 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    padding: 0 20px 10px;
    background: transparent;
  }

  .panel {
    border-radius: 14px;
    border: 1px solid;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-height: 0;
  }

  .panel.left  { border-color: #22c55e; background: transparent; }
  .panel.right { border-color: #f59e0b; background: transparent; }
  .panel.blue  { border-color: #3b82f6; background: transparent; }

  .panels.panels--1 {
    grid-template-columns: 1fr;
  }

  .panels.panels--3 {
    grid-template-columns: repeat(3, 1fr);
  }

  .panel-header {
    padding: 12px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
  }

  .panel.left  .panel-header { background: transparent; }
  .panel.right .panel-header { background: transparent; }

  .panel-title-row {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .panel-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    animation: blink 1.5s infinite;
  }
  .panel-dot.green  { background: #22c55e; }
  .panel-dot.orange { background: #f59e0b; }

  .panel-title {
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 16px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
  }
  .panel.left  .panel-title { color: #22c55e; }
  .panel.right .panel-title { color: #f59e0b; }
  .panel.blue  .panel-title { color: #60a5fa; }

  .panel-dot.blue { background: #3b82f6; }

  .panel-count {
    font-family: 'Rajdhani', sans-serif;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.08em;
    padding: 4px 14px;
    border-radius: 20px;
    border: 1px solid;
  }
  .panel.left  .panel-count { color: #22c55e; border-color: #22c55e; background: transparent; }
  .panel.right .panel-count { color: #f59e0b; border-color: #f59e0b; background: transparent; }
  .panel.blue  .panel-count { color: #60a5fa; border-color: #3b82f6; background: transparent; }

  .panel-body {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    scrollbar-width: thin;
    scrollbar-color: #333 transparent;
    min-height: 0;
  }

  /* Menunggu: dua kartu terpisah (Express / Reguler) */
  .panel-body.panel-body--wait-split {
    overflow: hidden;
    gap: 12px;
  }

  .wait-queue-bucket {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: rgba(10, 10, 10, 0.45);
    overflow: hidden;
  }

  .wait-queue-bucket--express {
    border-color: rgba(147, 51, 234, 0.55);
  }

  .wait-queue-bucket--reguler {
    border-color: rgba(71, 85, 105, 0.7);
  }

  .wait-queue-bucket-head {
    flex-shrink: 0;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 12px;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    padding: 8px 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    color: #94a3b8;
  }

  .wait-queue-bucket--express .wait-queue-bucket-head {
    color: #c4b5fd;
    border-bottom-color: rgba(147, 51, 234, 0.28);
  }

  .wait-queue-bucket-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
  }

  .wait-queue-bucket-dot.express {
    background: #a855f7;
    box-shadow: 0 0 10px rgba(168, 85, 247, 0.45);
  }

  .wait-queue-bucket-dot.reguler {
    background: #64748b;
  }

  .wait-queue-bucket-scroll {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    scrollbar-width: thin;
    scrollbar-color: #333 transparent;
  }

  /* Jalur scroll kolom monitor (WebView TV / browser lawas): scroll + touch, hindari bleed scroll */
  #list-proses,
  #list-menunggu-express,
  #list-menunggu-reguler {
    overflow-y: scroll;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
  }

  /* Kolom proses: ruang bawah agar kartu terakhir tidak mepet border panel */
  #list-proses.panel-body {
    padding-bottom: 22px;
  }

  /* ===== WORKING CARD (LEFT) ===== */
  .work-card {
    flex-shrink: 0;
    background: #1a1a1a;
    border: 1px solid #2a2a2a;
    border-radius: 12px;
    padding: 16px;
    position: relative;
    overflow: hidden;
  }

  .work-card-top {
    display: flex;
    gap: 16px;
    align-items: flex-start;
  }

  .queue-big {
    font-family: 'Orbitron', sans-serif;
    font-weight: 800;
    font-size: 52px;
    color: #22c55e;
    line-height: 1;
    min-width: 70px;
    text-align: center;
  }

  .plate-box {
    background: #111;
    border: 1px solid #444;
    border-radius: 6px;
    padding: 5px 10px;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 14px;
    letter-spacing: 0.12em;
    color: #fff;
    text-align: center;
    margin-top: 6px;
    min-width: 70px;
  }

  .work-queue-block-row {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
  }

  .work-queue-num-plate {
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .motor-brand-logo {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .motor-brand-logo img {
    display: block;
    object-fit: contain;
  }

  .motor-brand-logo--work img {
    height: 44px;
    width: auto;
    max-width: 80px;
  }

  .motor-brand-logo--wait img {
    height: 34px;
    width: auto;
    max-width: 70px;
  }

  .work-card-mid {
    flex: 1;
  }

  .motor-merk-label {
    font-family: 'Rajdhani', sans-serif;
    font-weight: 800;
    color: #c0392b;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    line-height: 1.1;
  }

  .work-card-mid .motor-merk-label {
    font-size: 14px;
    margin-bottom: 4px;
  }

  .wait-mid .motor-merk-label {
    font-size: 13px;
    margin-bottom: 3px;
  }

  .work-vehicle {
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 24px;
    color: #fff;
    line-height: 1.1;
    margin-bottom: 6px;
  }

  .work-meta {
    font-size: 12px;
    color: #888;
    display: flex;
    flex-direction: column;
    gap: 3px;
  }

  .work-meta span { display: flex; align-items: center; gap: 5px; }

  .work-card-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
    min-width: 110px;
  }

  .type-badge {
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 12px;
    letter-spacing: 0.1em;
    padding: 5px 14px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 5px;
    text-transform: uppercase;
  }

  .type-badge.express {
    background: #7c3aed;
    color: #fff;
    border: 1px solid #9333ea;
  }

  .type-badge.reguler {
    background: transparent;
    color: #94a3b8;
    border: 1px solid #475569;
  }

  .wo-num {
    font-size: 11px;
    color: #666;
    letter-spacing: 0.06em;
    text-align: right;
  }

  .status-btn {
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 13px;
    letter-spacing: 0.1em;
    padding: 6px 18px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
    text-transform: uppercase;
  }

  .status-btn.proses {
    background: rgba(34,197,94,0.15);
    color: #22c55e;
    border: 1px solid #22c55e;
  }

  .status-btn.menunggu {
    background: rgba(245,158,11,0.1);
    color: #f59e0b;
    border: 1px solid rgba(245,158,11,0.4);
  }

  /* ===== WAIT CARD (RIGHT) ===== */
  .wait-card {
    flex-shrink: 0;
    background: #1a1a1a;
    border: 1px solid #2a2a2a;
    border-radius: 10px;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .wait-num {
    font-family: 'Orbitron', sans-serif;
    font-weight: 800;
    font-size: 32px;
    color: #f59e0b;
    line-height: 1;
    min-width: 44px;
    text-align: center;
  }

  .wait-num.regular { color: #888; }

  .wait-plate {
    background: #111;
    border: 1px solid #444;
    border-radius: 5px;
    padding: 3px 8px;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 11px;
    letter-spacing: 0.1em;
    color: #aaa;
    text-align: center;
    margin-top: 4px;
  }

  .wait-queue-block-row {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
  }

  .wait-queue-num-plate {
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .wait-mid { flex: 1; }

  .wait-vehicle {
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 17px;
    color: #fff;
    line-height: 1.2;
  }

  .wait-customer {
    font-size: 11px;
    color: #666;
    margin-top: 3px;
    display: flex;
    align-items: center;
    gap: 4px;
  }

  .wait-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 5px;
    min-width: 110px;
  }

  .wait-wo {
    font-size: 10px;
    color: #555;
    letter-spacing: 0.05em;
    text-align: right;
  }

  /* ===== FOOTER ===== */
  .footer {
    flex-shrink: 0;
    height: 44px;
    background: #111;
    border-top: 1px solid #222;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 32px;
    font-size: 12px;
    color: #555;
    padding: 0 24px;
  }

  .footer-sep { color: #333; }
</style>

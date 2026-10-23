<style>
  :root {
    --lime: #8cfa01;
    --ink: #142000;
    --bg: #050605;
    --gold: #e4d3a8;
    --silver: #e7e8e3;
    --mute: #8e9488;
    --line: rgba(255, 255, 255, 0.08);
    --card: rgba(12, 14, 11, 0.72);
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    color: var(--silver);
    font-family: 'Inter', sans-serif;
    height: 100vh;
    height: 100dvh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background-color: var(--bg);
    background-image:
      radial-gradient(980px 420px at 0% -8%, rgba(140, 250, 1, 0.11), transparent 58%),
      radial-gradient(720px 460px at 100% 112%, rgba(140, 250, 1, 0.05), transparent 52%);
    background-size: cover;
    background-position: center;
  }

  body > * { position: relative; z-index: 1; }

  body::after {
    content: '';
    position: fixed;
    inset: 0;
    background: url("<?php echo e(asset('images/logo.png')); ?>") center 56% / 86vw auto no-repeat;
    mix-blend-mode: screen;
    opacity: 0.3;
    pointer-events: none;
    z-index: 4;
  }

  .header {
    flex-shrink: 0;
    background:
      linear-gradient(90deg, rgba(140, 250, 1, 0.07), transparent 32%),
      #000;
    border-bottom: 1px solid var(--line);
    padding: 0 32px;
    height: 92px;
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
    background: linear-gradient(90deg, transparent, var(--lime) 16%, var(--lime) 84%, transparent);
    z-index: 3;
  }

  .header-red-accent {
    position: absolute;
    top: 0; right: 0;
    width: 240px;
    height: 92px;
    background: linear-gradient(125deg, transparent 46%, rgba(140, 250, 1, 0.14) 100%);
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
    height: 58px;
    width: auto;
    display: block;
    object-fit: contain;
  }

  .brand-name {
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 18px;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: #fff;
    line-height: 1;
  }

  .brand-sub {
    font-size: 11px;
    color: var(--mute);
    letter-spacing: 0.18em;
    text-transform: uppercase;
    margin-top: 4px;
  }

  .header-center {
    text-align: center;
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
  }

  .monitor-label {
    font-size: 11px;
    letter-spacing: 0.32em;
    text-transform: uppercase;
    color: var(--lime);
    font-weight: 600;
    margin-bottom: 4px;
  }

  .monitor-title {
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 26px;
    letter-spacing: 0.12em;
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
    font-weight: 600;
    font-size: 32px;
    letter-spacing: 0.08em;
    color: #fff;
    line-height: 1;
  }

  .live-row {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 5px;
    justify-content: flex-end;
  }

  .live-dot {
    width: 7px; height: 7px;
    background: var(--lime);
    border-radius: 50%;
    box-shadow: 0 0 10px rgba(140, 250, 1, 0.7);
    animation: blink 1.6s infinite;
  }

  @keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.35; }
  }

  .live-text {
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.22em;
    color: var(--lime);
  }

  .clock-date {
    font-size: 12px;
    color: var(--mute);
    letter-spacing: 0.04em;
    text-align: right;
    margin-top: 3px;
  }

  .stats-row {
    flex-shrink: 0;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    padding: 16px 24px 14px;
    background: transparent;
  }

  .stat-card {
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.045), rgba(255, 255, 255, 0.015));
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06);
  }

  .stat-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 18px;
  }

  .stat-icon.green  { border: 1px solid rgba(140, 250, 1, 0.55); color: var(--lime); background: rgba(140, 250, 1, 0.08); }
  .stat-icon.orange { border: 1px solid rgba(228, 211, 168, 0.5); color: var(--gold); background: rgba(228, 211, 168, 0.08); }
  .stat-icon.blue   { border: 1px solid rgba(255, 255, 255, 0.22); color: var(--silver); background: rgba(255, 255, 255, 0.04); }
  .stat-icon.purple { border: 1px solid rgba(140, 250, 1, 0.28); color: #d7f7a4; background: rgba(140, 250, 1, 0.05); }

  .stat-num {
    font-family: 'Orbitron', 'Rajdhani', sans-serif;
    font-weight: 600;
    font-size: 28px;
    line-height: 1;
    letter-spacing: 0.04em;
    min-width: 1.5ch;
    flex-shrink: 0;
  }

  .stat-num.green  { color: var(--lime); }
  .stat-num.orange { color: var(--gold); }
  .stat-num.blue   { color: #fff; }
  .stat-num.purple { color: #d7f7a4; }

  .stat-title {
    font-weight: 600;
    font-size: 12px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #fff;
    margin-top: 2px;
  }
  .stat-sub {
    font-size: 11px;
    color: var(--mute);
    margin-top: 3px;
    letter-spacing: 0.02em;
  }

  .panels {
    flex: 1;
    min-height: 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    padding: 0 24px 20px;
    background: transparent;
  }

  .panel {
    border-radius: 18px;
    border: 1px solid var(--line);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-height: 0;
    background: var(--card);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
  }

  .panel.left  { border-color: rgba(140, 250, 1, 0.38); }
  .panel.right { border-color: rgba(228, 211, 168, 0.34); }
  .panel.blue  { border-color: rgba(255, 255, 255, 0.16); }

  .panels.panels--1 { grid-template-columns: 1fr; }
  .panels.panels--3 { grid-template-columns: repeat(3, 1fr); }

  .panel-header {
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--line);
    background: rgba(0, 0, 0, 0.28);
  }

  .panel-title-row {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .panel-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    animation: blink 1.6s infinite;
  }
  .panel-dot.green  { background: var(--lime); box-shadow: 0 0 10px rgba(140, 250, 1, 0.65); }
  .panel-dot.orange { background: var(--gold); }
  .panel-dot.blue   { background: var(--silver); }

  .panel-title {
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 16px;
    letter-spacing: 0.16em;
    text-transform: uppercase;
  }
  .panel.left  .panel-title { color: var(--lime); }
  .panel.right .panel-title { color: var(--gold); }
  .panel.blue  .panel-title { color: var(--silver); }

  .panel-count {
    font-family: 'Rajdhani', sans-serif;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.08em;
    padding: 4px 14px;
    border-radius: 999px;
    border: 1px solid;
    background: transparent;
  }
  .panel.left  .panel-count { color: var(--lime); border-color: rgba(140, 250, 1, 0.45); }
  .panel.right .panel-count { color: var(--gold); border-color: rgba(228, 211, 168, 0.45); }
  .panel.blue  .panel-count { color: var(--silver); border-color: rgba(255, 255, 255, 0.22); }

  .panel-body {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    scrollbar-width: thin;
    scrollbar-color: rgba(140, 250, 1, 0.35) transparent;
    min-height: 0;
  }

  .panel-body.panel-body--wait-split {
    overflow: hidden;
    gap: 12px;
  }

  .wait-queue-bucket {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    border-radius: 14px;
    border: 1px solid var(--line);
    background: rgba(0, 0, 0, 0.28);
    overflow: hidden;
  }

  .wait-queue-bucket--express { border-color: rgba(176, 154, 214, 0.4); }
  .wait-queue-bucket--reguler { border-color: rgba(255, 255, 255, 0.1); }

  .wait-queue-bucket-head {
    flex-shrink: 0;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 12px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    padding: 9px 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 1px solid var(--line);
    color: var(--mute);
  }

  .wait-queue-bucket--express .wait-queue-bucket-head {
    color: #ddd4ee;
    border-bottom-color: rgba(176, 154, 214, 0.28);
  }

  .wait-queue-bucket-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
  }

  .wait-queue-bucket-dot.express { background: #c4b5e0; }
  .wait-queue-bucket-dot.reguler { background: #8d9388; }

  .wait-queue-bucket-scroll {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    scrollbar-width: thin;
    scrollbar-color: rgba(140, 250, 1, 0.3) transparent;
  }

  #list-proses,
  #list-menunggu-express,
  #list-menunggu-reguler {
    overflow-y: scroll;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
  }

  #list-proses:empty::after,
  #list-menunggu-express:empty::after,
  #list-menunggu-reguler:empty::after {
    content: 'Belum ada antrian';
    margin: auto;
    font-family: 'Rajdhani', sans-serif;
    font-size: 15px;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: rgba(255, 255, 255, 0.38);
  }

  #list-proses.panel-body { padding-bottom: 22px; }

  .work-card,
  .wait-card {
    flex-shrink: 0;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.035), rgba(0, 0, 0, 0.2));
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
  }

  .work-card {
    border-radius: 14px;
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
    font-weight: 700;
    font-size: 48px;
    color: var(--lime);
    line-height: 1;
    min-width: 70px;
    text-align: center;
    letter-spacing: 0.02em;
  }

  .plate-box,
  .wait-plate {
    background: #0a0a0a;
    border: 1px solid rgba(255, 255, 255, 0.28);
    color: #fff;
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-align: center;
  }

  .plate-box {
    border-radius: 4px;
    padding: 4px 10px;
    font-size: 13px;
    margin-top: 8px;
    min-width: 70px;
  }

  .work-queue-block-row,
  .wait-queue-block-row {
    display: flex;
    flex-direction: row;
    align-items: center;
    flex-shrink: 0;
  }

  .work-queue-block-row { gap: 10px; }
  .wait-queue-block-row { gap: 8px; }

  .work-queue-num-plate,
  .wait-queue-num-plate {
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
    filter: grayscale(0.15) contrast(1.05);
  }

  .motor-brand-logo--work img { height: 44px; width: auto; max-width: 80px; }
  .motor-brand-logo--wait img { height: 34px; width: auto; max-width: 70px; }

  .work-card-mid { flex: 1; }

  .motor-merk-label {
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    color: #c8c9c4;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    line-height: 1.1;
  }

  .work-card-mid .motor-merk-label { font-size: 13px; margin-bottom: 4px; }
  .wait-mid .motor-merk-label { font-size: 12px; margin-bottom: 3px; }

  .work-vehicle {
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 24px;
    color: #fff;
    line-height: 1.1;
    margin-bottom: 6px;
    letter-spacing: 0.02em;
  }

  .work-meta {
    font-size: 12px;
    color: var(--mute);
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
    letter-spacing: 0.12em;
    padding: 5px 12px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    gap: 5px;
    text-transform: uppercase;
  }

  .type-badge.express {
    background: rgba(176, 154, 214, 0.16);
    color: #efe8f8;
    border: 1px solid rgba(196, 181, 224, 0.55);
  }

  .type-badge.reguler {
    background: transparent;
    color: #b7bdb4;
    border: 1px solid rgba(255, 255, 255, 0.16);
  }

  .wo-num {
    font-size: 11px;
    color: var(--mute);
    letter-spacing: 0.08em;
    text-align: right;
  }

  .status-btn {
    font-family: 'Rajdhani', sans-serif;
    font-weight: 700;
    font-size: 12px;
    letter-spacing: 0.12em;
    padding: 5px 14px;
    border-radius: 999px;
    display: flex;
    align-items: center;
    gap: 6px;
    text-transform: uppercase;
  }

  .status-pip {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: currentColor;
    flex-shrink: 0;
  }

  .status-btn.proses {
    background: rgba(140, 250, 1, 0.1);
    color: var(--lime);
    border: 1px solid rgba(140, 250, 1, 0.45);
  }

  .status-btn.menunggu {
    background: rgba(228, 211, 168, 0.08);
    color: var(--gold);
    border: 1px solid rgba(228, 211, 168, 0.4);
    font-size: 11px;
    padding: 4px 12px;
  }

  .wait-card {
    border-radius: 12px;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .wait-num {
    font-family: 'Orbitron', sans-serif;
    font-weight: 700;
    font-size: 30px;
    color: var(--gold);
    line-height: 1;
    min-width: 44px;
    text-align: center;
  }

  .wait-num.regular { color: #9aa095; }

  .wait-plate {
    border-radius: 4px;
    padding: 3px 8px;
    font-size: 11px;
    color: #ddd;
    margin-top: 5px;
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
    color: var(--mute);
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
    color: #6f756c;
    letter-spacing: 0.06em;
    text-align: right;
  }

  #antrian-audio-hint {
    cursor: pointer;
    position: fixed;
    bottom: 16px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 9999;
    max-width: min(720px, 92vw);
    padding: 10px 18px;
    border-radius: 14px;
    background: rgba(8, 10, 7, 0.94);
    color: #f3f5f0;
    font-size: 13px;
    text-align: center;
    font-family: 'Inter', sans-serif;
    line-height: 1.45;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.45);
    border: 1px solid rgba(140, 250, 1, 0.4);
  }

  #antrian-audio-hint strong {
    display: block;
    margin-bottom: 4px;
    color: var(--lime);
    letter-spacing: 0.12em;
    text-transform: uppercase;
    font-family: 'Rajdhani', sans-serif;
    font-size: 15px;
  }
</style>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/monitor-tv/partials/monitor-antrian-codingan-styles.blade.php ENDPATH**/ ?>
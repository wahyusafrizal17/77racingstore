<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>77racing – {{ $monitorAntrianTitle ?? 'Monitoring Antrian' }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Rajdhani:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  @include('speedshop.monitor-tv.partials.monitor-antrian-codingan-styles')
  <style>
    .work-card, .wait-card {
      animation: antrianCardIn 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
      transition: border-color 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
    }
    .work-card.is-quiet, .wait-card.is-quiet {
      animation: none !important;
    }
    @keyframes antrianCardIn {
      from { opacity: 0; transform: translateY(12px) scale(0.985); }
      to { opacity: 1; transform: none; }
    }
    #list-proses,
    #list-menunggu-express,
    #list-menunggu-reguler {
      scroll-behavior: auto;
    }
    .antrian-scroll-fade {
      position: relative;
    }
    .antrian-scroll-fade::before,
    .antrian-scroll-fade::after {
      content: '';
      position: absolute;
      left: 0;
      right: 0;
      height: 26px;
      pointer-events: none;
      z-index: 2;
      opacity: 0;
      transition: opacity 0.35s ease;
    }
    .antrian-scroll-fade::before {
      top: 0;
      background: linear-gradient(to bottom, rgba(5,6,5,0.92), transparent);
    }
    .antrian-scroll-fade::after {
      bottom: 0;
      background: linear-gradient(to top, rgba(5,6,5,0.92), transparent);
    }
    .antrian-scroll-fade.is-scrollable::before,
    .antrian-scroll-fade.is-scrollable::after {
      opacity: 1;
    }
  </style>
</head>
<body>

<header class="header">
  <div class="header-red-accent"></div>

  <div class="header-left">
    <div class="logo-box">
      <img src="{{ asset('images/logo.png') }}" alt="77 Racing" decoding="async" />
    </div>
  </div>

  <div class="header-center">
    <div class="monitor-label">Monitoring Antrian</div>
    <div class="monitor-title">{{ $monitorAntrianTitle ?? 'ANTRIAN SPEEDSHOP' }}</div>
        </div>

  <div class="header-right">
    <div>
      <div class="clock-time" id="clock">--:--:--</div>
      <div class="live-row">
        <div class="live-dot"></div>
        <div class="live-text">LIVE</div>
      </div>
      <div class="clock-date" id="date-display">-</div>
    </div>
  </div>
</header>

<div class="stats-row">
  <div class="stat-card">
    <div class="stat-icon orange">🕐</div>
    <div style="display:flex;align-items:center;gap:12px;">
      <div class="stat-num orange" id="stat-menunggu">{{ (int) (count($queueExpressData ?? []) + count($queueStandardData ?? [])) }}</div>
      <div class="stat-info">
        <div class="stat-title">Menunggu Dipanggil</div>
        <div class="stat-sub">Dalam antrian</div>
      </div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green">🔧</div>
    <div style="display:flex;align-items:center;gap:12px;">
      <div class="stat-num green" id="stat-proses">{{ (int) (count($inProcessExpressData ?? []) + count($inProcessStandardData ?? [])) }}</div>
      <div class="stat-info">
        <div class="stat-title">Sedang Dikerjakan</div>
        <div class="stat-sub">Aktif dikerjakan</div>
      </div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue">📋</div>
    <div style="display:flex;align-items:center;gap:12px;">
      <div class="stat-num blue" id="stat-total">{{ (int) ($statTotalHariIni ?? 0) }}</div>
      <div class="stat-info">
        <div class="stat-title">Total Unit Hari Ini</div>
        <div class="stat-sub">Semua jenis layanan</div>
      </div>
          </div>
        </div>
  <div class="stat-card">
    <div class="stat-icon purple">✅</div>
    <div style="display:flex;align-items:center;gap:12px;">
      <div class="stat-num purple" id="stat-selesai">{{ (int) ($statSelesaiHariIni ?? 0) }}</div>
      <div class="stat-info">
        <div class="stat-title">Selesai Hari Ini</div>
        <div class="stat-sub">Terlayani</div>
      </div>
    </div>
  </div>
</div>

<div class="panels">

  <div class="panel right">
    <div class="panel-header">
      <div class="panel-title-row">
        <div class="panel-dot orange"></div>
        <div class="panel-title">Menunggu Dipanggil</div>
      </div>
      <div class="panel-count" id="badge-menunggu">0 Kendaraan</div>
    </div>
    <div class="panel-body panel-body--wait-split">
      <div class="wait-queue-bucket wait-queue-bucket--express">
        <div class="wait-queue-bucket-head">
          <span class="wait-queue-bucket-dot express" aria-hidden="true"></span>
          Antrian Express
        </div>
        <div class="wait-queue-bucket-scroll antrian-scroll-fade" id="list-menunggu-express"></div>
          </div>
      <div class="wait-queue-bucket wait-queue-bucket--reguler">
        <div class="wait-queue-bucket-head">
          <span class="wait-queue-bucket-dot reguler" aria-hidden="true"></span>
          Antrian Reguler
        </div>
        <div class="wait-queue-bucket-scroll antrian-scroll-fade" id="list-menunggu-reguler"></div>
      </div>
    </div>
  </div>

  <div class="panel left">
    <div class="panel-header">
      <div class="panel-title-row">
        <div class="panel-dot green"></div>
        <div class="panel-title">Sedang Dikerjakan</div>
      </div>
      <div class="panel-count" id="badge-proses">0 Kendaraan</div>
    </div>
    <div class="panel-body antrian-scroll-fade" id="list-proses"></div>
  </div>

</div>

<audio id="antrian-voice-audio" preload="auto" playsinline webkit-playsinline style="position:absolute;width:0;height:0;opacity:0;pointer-events:none;overflow:hidden" aria-hidden="true"></audio>

<div id="antrian-audio-hint" role="button" tabindex="0" aria-live="polite" aria-label="Sentuh untuk mengaktifkan suara monitor antrian" style="display:none">
  <strong>Suara panggilan</strong>
  Browser/TV memblokir audio otomatis sampai ada interaksi. Sentuh layar atau tekan OK pada kotak ini <strong>sekali</strong> untuk mengaktifkan chime dan pengumuman suara (MP3 dari server).
  </div>

<script src="{{ asset('js/monitor-tv-antrian-audio.js') }}?v=18"></script>
  <script>
    let rawInProcessExpress = @json($inProcessExpressData ?? []);
    let rawInProcessStandard = @json($inProcessStandardData ?? []);
    let rawQueueExpress = @json($queueExpressData ?? []);
    let rawQueueStandard = @json($queueStandardData ?? []);
    let rawInProcess = @json($inProcessData ?? []);
    let rawQueue = @json($queueData ?? []);
    let statTotalHariIni = @json((int) ($statTotalHariIni ?? 0));
    let statSelesaiHariIni = @json((int) ($statSelesaiHariIni ?? 0));
    const monitorWarehouseId = @json($warehouseId ?? 0);
    const monitorSpeedshopId = @json($monitorSpeedshopId ?? null);
    const ANTRIAN_POLL_URL = @json(route('speedshop.monitor-tv.antrian.data', array_filter(['speedshop_id' => $monitorSpeedshopId ?? null])));
    const ANTRIAN_TTS_URL = @json(route('speedshop.monitor-tv.antrian.tts'));
    const antrianElevenlabsConfigured = @json($antrianElevenlabsConfigured ?? false);
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? (csrfMeta.getAttribute('content') || '') : '';
    const ANTRIAN_POLL_MS = 800;
    const ANTRIAN_SNAP_KEY = `speedshop_monitor_antrian_v1_ss_${monitorSpeedshopId || ('w_' + monitorWarehouseId)}`;
    const ANTRIAN_CHIME_URL = @json(asset('ting_ting_ting_ting.mp3'));
    const ANTRIAN_CHIME_END_URL = @json(asset('ting_ting_ting_ting_akhir.mp3'));
    /** Volume chime ting-ting (0–1). Suara panggilan TTS tetap 1. */
    const ANTRIAN_CHIME_VOLUME = 1;
    const ANTRIAN_SPEECH_VOLUME = 1;
    /** 1 = tanpa Web Audio boost (lebih aman di TV; &gt;1 kadang bikin suara mati total). */
    const ANTRIAN_SPEECH_GAIN = 1;
    const ANTRIAN_CLIP_BASE = @json(rtrim(asset('audio-antrian'), '/'));
    /** 1.0 = tempo asli file MP3. Kecepatan bicara TTS diatur di server (ElevenLabs voice_settings.speed). */
    const ANTRIAN_VOICE_PLAYBACK_RATE = 1.0;
    const voiceEl = document.getElementById('antrian-voice-audio');
    try {
      if (voiceEl) {
        voiceEl.volume = 1;
        voiceEl.muted = false;
        voiceEl.playbackRate = ANTRIAN_VOICE_PLAYBACK_RATE;
      }
    } catch (e) { /* ignore */ }
    const antrianVoice = typeof window.createAntrianVoicePlayer === 'function'
      ? window.createAntrianVoicePlayer({
          audioEl: voiceEl,
          clipBaseUrl: ANTRIAN_CLIP_BASE,
          chimeUrl: ANTRIAN_CHIME_URL,
          chimeEndUrl: ANTRIAN_CHIME_END_URL,
          gapMs: 48,
          chimeToSpeechGapMs: 0,
          chimeVolume: ANTRIAN_CHIME_VOLUME,
          speechVolume: ANTRIAN_SPEECH_VOLUME,
          speechGain: ANTRIAN_SPEECH_GAIN,
          playbackRate: ANTRIAN_VOICE_PLAYBACK_RATE,
          stallSkipMs: 16000,
          canplayTimeoutMs: 12000,
        })
      : null;
    let antrianMediaUnlocked = false;
    const ANTRIAN_MEDIA_UNLOCK_KEY = `speedshop_monitor_antrian_audio_unlock_v1_ss_${monitorSpeedshopId || ('w_' + monitorWarehouseId)}`;
    const PANGGILAN_PLAYED_STORAGE_KEY = monitorSpeedshopId
      ? `speedshop_monitor_panggilan_played_v1_ss_${monitorSpeedshopId}`
      : `speedshop_monitor_panggilan_played_v1_w_${monitorWarehouseId}`;
    let lastPanggilanPlayedKeyMem = '';
    let chimePreloadAudio = null;

    function preloadChimeAudio() {
      [ANTRIAN_CHIME_URL, ANTRIAN_CHIME_END_URL].forEach((url) => {
        if (!url) return;
        try {
          const a = new Audio(url);
          a.preload = 'auto';
          a.volume = ANTRIAN_CHIME_VOLUME;
          a.load();
        } catch (e) { /* ignore */ }
      });
      if (!ANTRIAN_CHIME_URL || chimePreloadAudio) return;
      try {
        chimePreloadAudio = new Audio(ANTRIAN_CHIME_URL);
        chimePreloadAudio.preload = 'auto';
        chimePreloadAudio.volume = ANTRIAN_CHIME_VOLUME;
        chimePreloadAudio.load();
      } catch (e) { /* ignore */ }
    }

    const MOTOR_BRAND_LOGO = {
      honda: @json(asset('images/motor-brands/honda.png')),
      yamaha: @json(asset('images/motor-brands/yamaha.png')),
    };

    function escapeHtml(s) {
      return String(s ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function normalizeMerkLabel(text) {
      return String(text || '')
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/\p{M}/gu, '');
    }

    function resolveMotorBrandKey(merk) {
      const m = normalizeMerkLabel(merk);
      if (!m) return null;
      if (m === 'honda' || m.startsWith('honda ')) return 'honda';
      if (m === 'yamaha' || m.startsWith('yamaha ')) return 'yamaha';
      return null;
    }

    /** @param {'work'|'wait'} variant */
    function motorBrandLogoHtml(merk, variant) {
      const key = resolveMotorBrandKey(merk);
      const url = key ? MOTOR_BRAND_LOGO[key] : '';
      if (!url) return '';
      const alt = key === 'honda' ? 'Honda' : 'Yamaha';
      return `<div class="motor-brand-logo motor-brand-logo--${variant}"><img src="${escapeHtml(url)}" alt="${alt}" decoding="async" /></div>`;
    }

    function motorMerkLabelHtml(merk) {
      const raw = String(merk ?? '').trim();
      if (!raw) return '';
      const label = escapeHtml(raw.toUpperCase());
      return `<div class="motor-merk-label">${label}</div>`;
    }

    function normalizeRows(rows, status, expressLane = null) {
      return (rows || []).map((row, idx) => {
        const ks = String(row.kategori_service || '').toLowerCase();
        const express = expressLane === true || expressLane === false
          ? expressLane === true
          : ks === 'express';
        const id = row.id != null ? String(row.id) : (`tmp-${status}-${idx}`);
        return {
          id,
          key: `${status}:${id}`,
          num: idx + 1,
          srv: row.kode_servis || `SO-${row.id ?? idx + 1}`,
          customer: row.pelanggan || '-',
          mechanic: row.mekanik || '-',
          plate: row.kendaraan || '-',
          tipe: row.tipe_motor || '-',
          merk: row.merk || '',
          status,
          express,
        };
      });
    }

    function itemSignature(item) {
      return [
        String(item.key ?? ''),
        String(item.num ?? ''),
        String(item.srv ?? ''),
        String(item.customer ?? ''),
        String(item.mechanic ?? ''),
        String(item.plate ?? ''),
        String(item.tipe ?? ''),
        String(item.merk ?? ''),
        item.express ? '1' : '0',
        String(item.status ?? ''),
      ].join('\u007c');
    }

    function listItemsSignature(items) {
      return (items || []).map(itemSignature).join('\u001f');
    }

    function isExpressOrderRow(r) {
      return String(r?.kategori_service ?? '').toLowerCase() === 'express';
    }

    function splitRowsByExpress(rows) {
      const express = [];
      const standard = [];
      for (const r of rows || []) {
        (isExpressOrderRow(r) ? express : standard).push(r);
      }
      return { express, standard };
    }

    function mergeExpressFirst(express, standard) {
      return [...(express || []), ...(standard || [])];
    }

    function listFingerprint(ipEx, ipStd, qEx, qStd) {
      const rowSig = (r) => [
        String(r.id ?? ''),
        String(r.kategori_service ?? ''),
        String(r.kode_servis ?? ''),
        String(r.pelanggan ?? ''),
        String(r.merk ?? ''),
        String(r.tipe_motor ?? ''),
        String(r.kendaraan ?? ''),
        String(r.mekanik ?? ''),
      ].join('\u007c');
      const snap = (rows) => (rows || []).map(rowSig).join('\u001f');
      return `${snap(ipEx)}\u001d${snap(ipStd)}\u001e${snap(qEx)}\u001d${snap(qStd)}`;
    }

    function renderWorkCard(item, opts) {
      opts = opts || {};
      const animate = opts.animate !== false;
      const num = String(item.num).padStart(2, '0');
      const cust = escapeHtml(item.customer);
      const mech = escapeHtml((item.mechanic || '').trim() || '-');
      const typeBadge = item.express
        ? '<div class="type-badge express">⚡ Express</div>'
        : '<div class="type-badge reguler">≡ Reguler</div>';
      const div = document.createElement('div');
      div.className = 'work-card' + (animate ? '' : ' is-quiet');
      div.setAttribute('data-key', String(item.key || ''));
      div.setAttribute('data-sig', itemSignature(item));
      if (!animate) div.style.animation = 'none';
      div.innerHTML = `
    <div class="work-card-top">
      <div class="work-queue-block-row">
        <div class="work-queue-num-plate">
          <div class="queue-big">${num}</div>
          <div class="plate-box">${escapeHtml(item.plate)}</div>
        </div>
        ${motorBrandLogoHtml(item.merk, 'work')}
      </div>
      <div class="work-card-mid">
        ${motorMerkLabelHtml(item.merk)}
        <div class="work-vehicle">${escapeHtml(item.tipe)}</div>
        <div class="work-meta">
          <span>👤 Pelanggan: ${cust}</span>
          <span>🔧 Mekanik: ${mech}</span>
        </div>
      </div>
      <div class="work-card-right">
        ${typeBadge}
        <div class="wo-num">${escapeHtml(item.srv)}</div>
        <div class="status-btn proses">
          <div class="status-pip"></div>
          Proses
        </div>
      </div>
    </div>`;
      return div;
    }

    function renderWaitCard(item, opts) {
      opts = opts || {};
      const animate = opts.animate !== false;
      const num = String(item.num).padStart(2, '0');
      const numCls = item.express ? 'wait-num' : 'wait-num regular';
      const typeBadge = item.express
        ? '<div class="type-badge express">⚡ Express</div>'
        : '<div class="type-badge reguler">≡ Reguler</div>';
      const div = document.createElement('div');
      div.className = 'wait-card' + (animate ? '' : ' is-quiet');
      div.setAttribute('data-key', String(item.key || ''));
      div.setAttribute('data-sig', itemSignature(item));
      if (!animate) div.style.animation = 'none';
      div.innerHTML = `
    <div class="wait-queue-block-row">
      <div class="wait-queue-num-plate">
        <div class="${numCls}">${num}</div>
        <div class="wait-plate">${escapeHtml(item.plate)}</div>
      </div>
      ${motorBrandLogoHtml(item.merk, 'wait')}
    </div>
    <div class="wait-mid">
      ${motorMerkLabelHtml(item.merk)}
      <div class="wait-vehicle">${escapeHtml(item.tipe)}</div>
      <div class="wait-customer">👤 Pelanggan: ${escapeHtml(item.customer)}</div>
    </div>
    <div class="wait-right">
      ${typeBadge}
      <div class="wait-wo">${escapeHtml(item.srv)}</div>
      <div class="status-btn menunggu">
        <div class="status-pip"></div>
        Menunggu
      </div>
    </div>`;
      return div;
    }

    const antrianListDomSig = {
      'list-proses': '',
      'list-menunggu-express': '',
      'list-menunggu-reguler': '',
    };
    let antrianListsHydrated = false;

    function syncAntrianList(rootId, items, renderCard) {
      const root = document.getElementById(rootId);
      if (!root) return false;
      const nextItems = Array.isArray(items) ? items : [];
      const nextSig = listItemsSignature(nextItems);
      if (antrianListDomSig[rootId] === nextSig) return false;

      const prevTop = root.scrollTop || 0;

      const existing = Array.prototype.slice.call(root.querySelectorAll('[data-key]'));
      const byKey = new Map();
      existing.forEach(function (el) {
        byKey.set(String(el.getAttribute('data-key') || ''), el);
      });

      const frag = document.createDocumentFragment();
      let structuralChange = existing.length !== nextItems.length;
      let firstNewEl = null;

      nextItems.forEach(function (item, idx) {
        const key = String(item.key || '');
        const sig = itemSignature(item);
        let el = byKey.get(key);
        if (el) {
          byKey.delete(key);
          if (el.getAttribute('data-sig') !== sig) {
            el = renderCard(item, { animate: false });
            el.classList.add('is-quiet');
            el.style.animation = 'none';
          } else {
            el.classList.add('is-quiet');
          }
          if (existing[idx] && existing[idx].getAttribute('data-key') !== key) {
            structuralChange = true;
          }
        } else {
          structuralChange = true;
          el = renderCard(item, { animate: antrianListsHydrated });
          if (!antrianListsHydrated) {
            el.classList.add('is-quiet');
            el.style.animation = 'none';
          }
          if (!firstNewEl) firstNewEl = el;
        }
        if (el) frag.appendChild(el);
      });

      if (byKey.size > 0) structuralChange = true;

      root.innerHTML = '';
      root.appendChild(frag);

      const mx = Math.max(0, root.scrollHeight - root.clientHeight);
      if (antrianListsHydrated && firstNewEl && prevTop < 24) {
        root.scrollTop = 0;
      } else {
        // Pertahankan posisi absolut (bukan ratio) agar auto-scroll tidak loncat saat poll.
        root.scrollTop = Math.min(prevTop, mx);
      }

      antrianListDomSig[rootId] = nextSig;
      // Selalu true jika signature berubah — refresh auto-scroll supaya pos tidak loncat.
      return true;
    }

    function renderProsesList() {
      const exp = normalizeRows(rawInProcessExpress || [], 'active', true);
      const std = normalizeRows(rawInProcessStandard || [], 'active', false);
      return syncAntrianList('list-proses', [...exp, ...std], renderWorkCard);
    }

    function renderMenungguList() {
      const exp = normalizeRows(rawQueueExpress || [], 'waiting', true);
      const std = normalizeRows(rawQueueStandard || [], 'waiting', false);
      const a = syncAntrianList('list-menunggu-express', exp, renderWaitCard);
      const b = syncAntrianList('list-menunggu-reguler', std, renderWaitCard);
      return a || b;
    }

    /**
     * Auto-scroll halus naik-turun per kolom (pause di ujung).
     * Update poll tidak mereset scroll — hanya sync DOM yang berubah.
     */
    var MonitorTvAutoScroll = (function () {
      var ANCHOR_IDS = ['list-proses', 'list-menunggu-express', 'list-menunggu-reguler'];
      var PX_PER_SEC = 28;
      var PAUSE_MS = 1600;
      var rafId = null;
      var lastTs = 0;
      var lanes = [];

      function prefersReducedMotion() {
        try {
          return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        } catch (e) {
          return false;
        }
      }

      function byId(id) {
        return document.getElementById ? document.getElementById(id) : null;
      }

      function maxScroll(el) {
        return Math.max(0, (el.scrollHeight || 0) - (el.clientHeight || 0));
      }

      function markFade(el, mx) {
        if (!el) return;
        if (mx > 8) el.classList.add('is-scrollable');
        else el.classList.remove('is-scrollable');
      }

      function rebuildRows() {
        var next = [];
        var i, id, el, prev, mx;
        for (i = 0; i < ANCHOR_IDS.length; i++) {
          id = ANCHOR_IDS[i];
          el = byId(id);
          if (!el) continue;
          prev = null;
          for (var j = 0; j < lanes.length; j++) {
            if (lanes[j].id === id) { prev = lanes[j]; break; }
          }
          mx = maxScroll(el);
          markFade(el, mx);
          next.push({
            id: id,
            el: el,
            dir: prev && prev.dir ? prev.dir : 1,
            pauseUntil: 0,
            pos: Math.min(prev && typeof prev.pos === 'number' ? prev.pos : (el.scrollTop || 0), mx),
          });
        }
        lanes = next;
      }

      function onTick(ts) {
        rafId = window.requestAnimationFrame(onTick);
        if (document.visibilityState && document.visibilityState !== 'visible') {
          lastTs = ts;
          return;
        }
        if (!lastTs) lastTs = ts;
        var dt = Math.min(48, ts - lastTs);
        lastTs = ts;
        if (prefersReducedMotion()) return;

        var speed = PX_PER_SEC * (dt / 1000);
        var i, lane, el, mx;
        for (i = 0; i < lanes.length; i++) {
          lane = lanes[i];
          el = lane.el;
          if (!el) continue;
          mx = maxScroll(el);
          markFade(el, mx);
          if (mx <= 8) {
            lane.pos = 0;
            el.scrollTop = 0;
            continue;
          }
          if (ts < lane.pauseUntil) continue;

          lane.pos += speed * lane.dir;
          if (lane.pos >= mx) {
            lane.pos = mx;
            el.scrollTop = mx;
            lane.dir = -1;
            lane.pauseUntil = ts + PAUSE_MS;
            continue;
          }
          if (lane.pos <= 0) {
            lane.pos = 0;
            el.scrollTop = 0;
            lane.dir = 1;
            lane.pauseUntil = ts + PAUSE_MS;
            continue;
          }
          el.scrollTop = lane.pos;
        }
      }

      function stop() {
        if (rafId !== null) {
          window.cancelAnimationFrame(rafId);
          rafId = null;
        }
        lastTs = 0;
      }

      function start() {
        stop();
        rebuildRows();
        if (!lanes.length) return;
        rafId = window.requestAnimationFrame(onTick);
      }

      function restartSoon() {
        rebuildRows();
        if (rafId === null) start();
      }

      return { start: start, stop: stop, restartSoon: restartSoon };
    })();

    function cancelAllColumnAutoscroll() {
      MonitorTvAutoScroll.stop();
    }

    function refreshColumnAutoscroll() {
      if (document.visibilityState && document.visibilityState !== 'visible') return;
      MonitorTvAutoScroll.restartSoon();
    }

    function updateBadges(proses, menunggu) {
      const a = document.getElementById('badge-proses');
      const b = document.getElementById('badge-menunggu');
      if (a) a.textContent = `${proses} Kendaraan`;
      if (b) b.textContent = `${menunggu} Kendaraan`;
    }

    function updateStatsRow(proses, menunggu) {
      const set = (id, v) => {
        const el = document.getElementById(id);
        if (el) el.textContent = String(v);
      };
      set('stat-proses', proses);
      set('stat-menunggu', menunggu);
      set('stat-total', statTotalHariIni);
      set('stat-selesai', statSelesaiHariIni);
    }

    let antrianPollTimer = null;

    function startAntrianPollTimer() {
      if (antrianPollTimer) {
        window.clearInterval(antrianPollTimer);
        antrianPollTimer = null;
      }
      antrianPollTimer = window.setInterval(pollMonitorAntrian, ANTRIAN_POLL_MS);
      void pollMonitorAntrian();
    }

    function panggilanPlayKey(panggilan) {
      if (!panggilan || typeof panggilan !== 'object') return '';
      const id = panggilan.id != null ? String(panggilan.id) : '';
      const at = panggilan.at != null ? String(panggilan.at) : '';
      if (id !== '' && at !== '') return `${id}:${at}`;
      const texts = Array.isArray(panggilan.texts) ? panggilan.texts.join('|') : '';
      return texts;
    }

    async function fetchAntrianTtsUrls(texts) {
      const res = await fetch(ANTRIAN_TTS_URL, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ texts }),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok || !Array.isArray(data.clips)) {
        const msg = data && data.message ? String(data.message) : `HTTP ${res.status}`;
        try {
          console.warn('[Monitor TTS]', msg);
        } catch (e2) { /* ignore */ }
        return [];
      }
      return data.clips.map((c) => c && c.url).filter(Boolean);
    }

    /**
     * Cadangan bila ElevenLabs gagal (key invalid / kredit / jaringan).
     * Dipakai agar panggilan tetap terbaca di browser/TV yang mendukung speechSynthesis.
     * @param {string[]} texts
     * @param {() => void} [onComplete]
     */
    function speakAntrianFallback(texts, onComplete) {
      const list = Array.isArray(texts) ? texts.map((t) => String(t || '').trim()).filter(Boolean) : [];
      const done = () => {
        if (typeof onComplete === 'function') {
          try { onComplete(); } catch (e) { /* ignore */ }
        }
      };
      if (!list.length) {
        done();
        return false;
      }
      if (!('speechSynthesis' in window) || typeof window.SpeechSynthesisUtterance !== 'function') {
        try { console.warn('[Monitor TTS] speechSynthesis tidak tersedia di perangkat ini'); } catch (e) { /* ignore */ }
        done();
        return false;
      }
      try {
        window.speechSynthesis.cancel();
      } catch (e) { /* ignore */ }

      const speakNext = (idx) => {
        if (idx >= list.length) {
          done();
          return;
        }
        const u = new SpeechSynthesisUtterance(list[idx]);
        u.lang = 'id-ID';
        u.rate = 0.92;
        u.pitch = 1;
        u.volume = 1;
        u.onend = () => speakNext(idx + 1);
        u.onerror = () => speakNext(idx + 1);
        try {
          window.speechSynthesis.speak(u);
        } catch (e2) {
          speakNext(idx + 1);
        }
      };

      speakNext(0);
      return true;
    }

    /**
     * Chime + suara panggilan dalam satu antrean (tanpa jeda panjang).
     * URL TTS idealnya sudah disiapkan server saat tombol Panggil diklik (clip_urls).
     */
    async function playPanggilanMonitorAudio(panggilan) {
      if (!antrianVoice) return;
      if (!antrianMediaUnlocked) {
        showAntrianAudioHintIfLocked();
        return;
      }
      const texts = Array.isArray(panggilan?.texts) ? panggilan.texts : [];
      let speechUrls = Array.isArray(panggilan?.clip_urls) ? panggilan.clip_urls.filter(Boolean) : [];

      if (!speechUrls.length && texts.length && antrianElevenlabsConfigured) {
        speechUrls = await fetchAntrianTtsUrls(texts);
      }

      if (!speechUrls.length && !ANTRIAN_CHIME_URL && !texts.length) {
        return;
      }

      // Ada MP3 ElevenLabs: chime → narasi → penutup (semua dalam antrean audio).
      if (speechUrls.length) {
        if (typeof antrianVoice.preloadUrls === 'function') {
          antrianVoice.preloadUrls(speechUrls);
        }
        if (typeof antrianVoice.enqueueChimeThenClips === 'function') {
          antrianVoice.enqueueChimeThenClips(speechUrls, { chimeGapMs: 0 });
        } else if (ANTRIAN_CHIME_URL) {
          antrianVoice.enqueueUrls([ANTRIAN_CHIME_URL, ...speechUrls, ANTRIAN_CHIME_END_URL].filter(Boolean));
        }
        return;
      }

      // Fallback speechSynthesis: chime awal → narasi selesai → baru penutup.
      // Jangan antrekan penutup bareng chime (nanti bunyi sebelum narasi selesai).
      if (typeof antrianVoice.enqueueChimeThenClips === 'function') {
        antrianVoice.enqueueChimeThenClips([], { chimeGapMs: 0, skipEndChime: true });
      } else if (ANTRIAN_CHIME_URL) {
        antrianVoice.enqueueUrls([ANTRIAN_CHIME_URL]);
      }

      const playEndChime = () => {
        if (!ANTRIAN_CHIME_END_URL) return;
        antrianVoice.enqueueUrls([ANTRIAN_CHIME_END_URL]);
      };

      // Tunggu chime awal (~1s) baru bicara; penutup setelah narasi selesai.
      window.setTimeout(() => {
        if (texts.length) {
          speakAntrianFallback(texts, playEndChime);
        } else {
          playEndChime();
        }
      }, 1100);
    }

    function handleMonitorPanggilan(panggilan) {
      if (!panggilan || !Array.isArray(panggilan.texts) || !panggilan.texts.length) return;

      if (monitorSpeedshopId != null && panggilan.speedshop_id != null
          && Number(panggilan.speedshop_id) !== Number(monitorSpeedshopId)) {
        return;
      }

      const key = panggilanPlayKey(panggilan);
      if (!key) return;
      if (key === lastPanggilanPlayedKeyMem) return;

      try {
        if (sessionStorage.getItem(PANGGILAN_PLAYED_STORAGE_KEY) === key) {
          lastPanggilanPlayedKeyMem = key;
          return;
        }
        sessionStorage.setItem(PANGGILAN_PLAYED_STORAGE_KEY, key);
      } catch (e) {
        /* private mode TV */
      }

      lastPanggilanPlayedKeyMem = key;
      void playPanggilanMonitorAudio(panggilan);
    }

    /**
     * Panggilan contoh: "Nomor antrian A 12 silahkan menuju loket 3" dari potongan MP3.
     * Memerlukan antrian media sudah di-unlock (satu kali sentuh layar).
     */
    function panggilAntrian(params) {
      if (!antrianVoice) return;
      if (!antrianMediaUnlocked) {
        showAntrianAudioHintIfLocked();
        return;
      }
      antrianVoice.panggilAntrian(params || {});
    }

    function slimOrderRow(row) {
      return {
        id: row.id,
        kode_servis: row.kode_servis ?? '',
        pelanggan: row.pelanggan ?? '',
        tipe_motor: row.tipe_motor ?? '',
        kendaraan: row.kendaraan ?? '',
        mekanik: row.mekanik ?? '',
        tanggal_masuk_at: row.tanggal_masuk_at ?? '',
      };
    }

    function buildSnapshot() {
      const ip = mergeExpressFirst(rawInProcessExpress, rawInProcessStandard);
      const q = mergeExpressFirst(rawQueueExpress, rawQueueStandard);
      return {
        savedAt: Date.now(),
        inProcess: ip.map(slimOrderRow),
        queueIds: q.map((r) => Number(r.id)),
      };
    }

    function hideAntrianAudioHint() {
      const h = document.getElementById('antrian-audio-hint');
      if (h) h.style.display = 'none';
    }

    function showAntrianAudioHintIfLocked() {
      if (antrianMediaUnlocked) return;
      const h = document.getElementById('antrian-audio-hint');
      if (!h) return;
      h.style.display = 'block';
      try {
        h.focus({ preventScroll: true });
      } catch (e) {
        try {
          h.focus();
        } catch (e2) { /* ignore */ }
      }
    }

    /**
     * Satu kali interaksi: main chime lewat pemutar MP3 (membuka autoplay untuk klip berikutnya).
     */
    function unlockAntrianMediaOnUserGestureOnce() {
      const cap = { capture: true, passive: true };
      const hintKeyCap = { capture: true, passive: false };
      const tryUnlock = () => {
        if (antrianMediaUnlocked) return;
        try {
          if (voiceEl) {
            voiceEl.muted = false;
            voiceEl.volume = 1;
          }
        } catch (e) { /* ignore */ }
        const detach = () => {
          document.removeEventListener('pointerdown', tryUnlock, cap);
          document.removeEventListener('touchstart', tryUnlock, cap);
          document.removeEventListener('mousedown', tryUnlock, cap);
          document.removeEventListener('keydown', tryUnlock, cap);
          window.removeEventListener('click', tryUnlock, cap);
          const h = document.getElementById('antrian-audio-hint');
          if (h) {
            h.removeEventListener('pointerdown', tryUnlock, cap);
            h.removeEventListener('click', tryUnlock, cap);
            h.removeEventListener('keydown', onHintKey, hintKeyCap);
          }
        };
        const finishOk = () => {
          antrianMediaUnlocked = true;
          preloadChimeAudio();
          try {
            sessionStorage.setItem(ANTRIAN_MEDIA_UNLOCK_KEY, '1');
          } catch (e) { /* ignore */ }
          detach();
          hideAntrianAudioHint();
        };
        const finishFail = () => {
          showAntrianAudioHintIfLocked();
        };
        const run = antrianVoice
          ? antrianVoice.tryUnlockWithChime()
          : (() => {
              try {
                if (!voiceEl) return Promise.reject(new Error('no audio'));
                voiceEl.playbackRate = ANTRIAN_VOICE_PLAYBACK_RATE;
                voiceEl.volume = ANTRIAN_CHIME_VOLUME;
                voiceEl.src = ANTRIAN_CHIME_URL;
                voiceEl.load();
                const p = voiceEl.play();
                if (p !== undefined && typeof p.then === 'function') return p;
                return Promise.resolve();
              } catch (e) {
                return Promise.reject(e);
              }
            })();
        if (run !== undefined && typeof run.then === 'function') {
          run.then(() => finishOk()).catch(() => finishFail());
        } else {
          finishOk();
        }
      };

      function onHintKey(ev) {
        if (ev.key === 'Enter' || ev.key === ' ' || ev.key === 'Spacebar') {
          ev.preventDefault();
          tryUnlock();
        }
      }

      document.addEventListener('pointerdown', tryUnlock, cap);
      document.addEventListener('touchstart', tryUnlock, cap);
      document.addEventListener('mousedown', tryUnlock, cap);
      document.addEventListener('keydown', tryUnlock, cap);
      window.addEventListener('click', tryUnlock, cap);
      const hint = document.getElementById('antrian-audio-hint');
      if (hint) {
        hint.addEventListener('pointerdown', tryUnlock, cap);
        hint.addEventListener('click', tryUnlock, cap);
        hint.addEventListener('keydown', onHintKey, hintKeyCap);
      }
    }

    function persistSnapshotAndMaybeAnnounce() {
      try {
        sessionStorage.setItem(ANTRIAN_SNAP_KEY, JSON.stringify(buildSnapshot()));
      } catch (e) {
        /* private mode / quota */
      }
    }

    const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

    function updateClock() {
      const now = new Date();
      const h = String(now.getHours()).padStart(2, '0');
      const m = String(now.getMinutes()).padStart(2, '0');
      const s = String(now.getSeconds()).padStart(2, '0');
      const elClock = document.getElementById('clock');
      const elDate = document.getElementById('date-display');
      if (elClock) elClock.textContent = `${h}:${m}:${s}`;
      if (elDate) elDate.textContent = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
    }

    function renderBoardFromState() {
      const nProses = (rawInProcessExpress || []).length + (rawInProcessStandard || []).length;
      const nMenunggu = (rawQueueExpress || []).length + (rawQueueStandard || []).length;

      const fp = listFingerprint(rawInProcessExpress, rawInProcessStandard, rawQueueExpress, rawQueueStandard);
      if (window.__antrianListFp === fp) {
        // List sama — hanya refresh angka (total/selesai) tanpa sentuh DOM kartu.
        updateBadges(nProses, nMenunggu);
        updateStatsRow(nProses, nMenunggu);
        return;
      }
      window.__antrianListFp = fp;

      const changedProses = renderProsesList();
      const changedMenunggu = renderMenungguList();
      antrianListsHydrated = true;
      updateBadges(nProses, nMenunggu);
      updateStatsRow(nProses, nMenunggu);

      // Jangan reset scrollTop ke 0 — biarkan auto-scroll lanjut tanpa terlihat “reload”.
      if (changedProses || changedMenunggu) {
        refreshColumnAutoscroll();
      }
    }

    function applyMonitorPayload(payload) {
      if (!payload || typeof payload !== 'object') return;

      const hasSplitIp = Array.isArray(payload.in_process_express) && Array.isArray(payload.in_process_standard);
      const hasSplitQ = Array.isArray(payload.queue_express) && Array.isArray(payload.queue_standard);

      if (hasSplitIp) {
        rawInProcessExpress = payload.in_process_express;
        rawInProcessStandard = payload.in_process_standard;
        rawInProcess = mergeExpressFirst(rawInProcessExpress, rawInProcessStandard);
      } else if (Array.isArray(payload.in_process)) {
        rawInProcess = payload.in_process;
        const s = splitRowsByExpress(payload.in_process);
        rawInProcessExpress = s.express;
        rawInProcessStandard = s.standard;
      }

      if (hasSplitQ) {
        rawQueueExpress = payload.queue_express;
        rawQueueStandard = payload.queue_standard;
        rawQueue = mergeExpressFirst(rawQueueExpress, rawQueueStandard);
      } else if (Array.isArray(payload.queue)) {
        rawQueue = payload.queue;
        const s = splitRowsByExpress(payload.queue);
        rawQueueExpress = s.express;
        rawQueueStandard = s.standard;
      }

      if (typeof payload.stat_total_hari_ini === 'number') {
        statTotalHariIni = payload.stat_total_hari_ini;
      }
      if (typeof payload.stat_selesai_hari_ini === 'number') {
        statSelesaiHariIni = payload.stat_selesai_hari_ini;
      }

      const titleEl = document.querySelector('.monitor-title');
      if (titleEl && typeof payload.monitor_antrian_title === 'string' && payload.monitor_antrian_title !== '') {
        titleEl.textContent = payload.monitor_antrian_title;
      }
      if (payload.panggilan && Array.isArray(payload.panggilan.texts) && payload.panggilan.texts.length) {
        handleMonitorPanggilan(payload.panggilan);
      }
      renderBoardFromState();
      persistSnapshotAndMaybeAnnounce();
    }

    let antrianPollInFlight = false;
    async function pollMonitorAntrian() {
      if (antrianPollInFlight) return;
      antrianPollInFlight = true;
      const controller = new AbortController();
      const t = window.setTimeout(() => controller.abort(), 15000);
      try {
        const sep = ANTRIAN_POLL_URL.includes('?') ? '&' : '?';
        const res = await fetch(`${ANTRIAN_POLL_URL}${sep}_=${Date.now()}`, {
          credentials: 'same-origin',
          cache: 'no-store',
          signal: controller.signal,
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });
        if (!res.ok) return;
        const ct = (res.headers.get('content-type') || '').toLowerCase();
        if (!ct.includes('application/json')) {
          const u = String(res.url || '');
          if (u.includes('/login')) window.location.assign(res.url);
          return;
        }
        const data = await res.json();
        applyMonitorPayload(data);
      } catch (e) {
        /* jaringan / timeout / parse — biarkan tampilan terakhir */
      } finally {
        window.clearTimeout(t);
        antrianPollInFlight = false;
      }
    }

    renderBoardFromState();
    MonitorTvAutoScroll.start();

    unlockAntrianMediaOnUserGestureOnce();
    try {
      if (sessionStorage.getItem(ANTRIAN_MEDIA_UNLOCK_KEY) === '1') {
        antrianMediaUnlocked = true;
        preloadChimeAudio();
        hideAntrianAudioHint();
      }
    } catch (e) { /* ignore */ }
    showAntrianAudioHintIfLocked();
    persistSnapshotAndMaybeAnnounce();

    updateClock();
    setInterval(updateClock, 1000);
    startAntrianPollTimer();
    let autoscrollResizeTimer = null;
    window.addEventListener('resize', () => {
      window.clearTimeout(autoscrollResizeTimer);
      autoscrollResizeTimer = window.setTimeout(refreshColumnAutoscroll, 200);
    });
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible') {
        pollMonitorAntrian();
        MonitorTvAutoScroll.start();
      } else {
        cancelAllColumnAutoscroll();
      }
    });
    window.addEventListener('pageshow', function (ev) {
      if (ev.persisted) {
        MonitorTvAutoScroll.start();
      }
    });
    window.MonitorTvAutoScroll = MonitorTvAutoScroll;
    window.antrianVoice = antrianVoice;
    window.panggilAntrian = panggilAntrian;
  </script>

</body>
</html>

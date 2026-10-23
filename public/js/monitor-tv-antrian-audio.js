/**
 * Pemutar antrian audio monitor — SATU elemen <audio>, antrian URL MP3.
 * Tanpa speechSynthesis. Target: Android TV, Xiaomi TV Browser, WebView, smart TV lama.
 *
 * @param {object} options
 * @param {HTMLAudioElement} options.audioEl
 * @param {string} options.clipBaseUrl — tanpa trailing slash
 * @param {string} options.chimeUrl — URL penuh chime awal (opsional)
 * @param {string} options.chimeEndUrl — URL chime penutup setelah panggilan selesai (opsional)
 * @param {number} [options.gapMs=72] — jeda default antar-klip (bisa di-override per item lewat gapAfter)
 * @param {number} [options.chimeToSpeechGapMs=0] — jeda setelah chime sebelum klip berikutnya (enqueueChimeThenClips)
 * @param {number} [options.chimeVolume=1] — volume chime ting-ting (0–1)
 * @param {number} [options.speechVolume=1] — volume elemen audio untuk TTS (0–1)
 * @param {number} [options.speechGain=1.35] — boost TTS lewat Web Audio (1 = normal, &gt;1 lebih keras jika didukung)
 * @param {number} [options.playbackRate=1] — kecepatan pemutaran HTMLAudio (0.5–1.0 = lebih pelan, untuk TV)
 * @param {number} [options.stallSkipMs=14000] — lewati klip jika hang
 * @param {number} [options.canplayTimeoutMs=4500] — lewati jika buffer tidak siap
 */
(function (global) {
  'use strict';

  function trimSlash(u) {
    return String(u || '').replace(/\/+$/, '');
  }

  function joinClip(base, relPathNoExt) {
    var b = trimSlash(base);
    var r = String(relPathNoExt || '')
      .replace(/^\/+/, '')
      .replace(/\.mp3$/i, '');
    if (!b || !r) return '';
    return b + '/' + r + '.mp3';
  }

  function digitsToClipTokens(n) {
    var s = String(Math.abs(Math.floor(Number(n))));
    if (!s || s === 'NaN') return ['angka/0'];
    return s.split('').map(function (d) {
      return 'angka/' + d;
    });
  }

  function kodeServisToClipTokens(code) {
    var s = String(code || '')
      .toUpperCase()
      .replace(/[^A-Z0-9]/g, '');
    var out = [];
    for (var i = 0; i < s.length; i += 1) {
      var ch = s.charAt(i);
      if (ch >= '0' && ch <= '9') out.push('angka/' + ch);
      else if (ch >= 'A' && ch <= 'Z') out.push('huruf/' + ch.toLowerCase());
    }
    return out;
  }

  function createAntrianVoicePlayer(options) {
    var audioEl = options && options.audioEl;
    var clipBaseUrl = trimSlash(options && options.clipBaseUrl);
    var chimeUrl = String((options && options.chimeUrl) || '');
    var chimeEndUrl = String((options && options.chimeEndUrl) || '');
    var gapMs = typeof options.gapMs === 'number' ? options.gapMs : 72;
    var chimeToSpeechGapMs = typeof options.chimeToSpeechGapMs === 'number' ? options.chimeToSpeechGapMs : 0;
    var chimeVolume = typeof options.chimeVolume === 'number' ? options.chimeVolume : 1;
    var speechVolume = typeof options.speechVolume === 'number' ? options.speechVolume : 1;
    var speechGain = typeof options.speechGain === 'number' ? options.speechGain : 1.35;
    var stallSkipMs = typeof options.stallSkipMs === 'number' ? options.stallSkipMs : 14000;
    var canplayTimeoutMs = typeof options.canplayTimeoutMs === 'number' ? options.canplayTimeoutMs : 4500;
    var playbackRateRaw = typeof options.playbackRate === 'number' ? options.playbackRate : 1;
    var playbackRate = playbackRateRaw;
    if (!isFinite(playbackRate) || playbackRate <= 0) {
      playbackRate = 1;
    } else if (playbackRate < 0.25) {
      playbackRate = 0.25;
    } else if (playbackRate > 4) {
      playbackRate = 4;
    }

    function clampVolume(v, fallback) {
      if (!isFinite(v)) return fallback;
      if (v < 0) return 0;
      if (v > 1) return 1;
      return v;
    }

    chimeVolume = clampVolume(chimeVolume, 1);
    speechVolume = clampVolume(speechVolume, 1);
    if (!isFinite(speechGain) || speechGain < 1) {
      speechGain = 1;
    } else if (speechGain > 2) {
      speechGain = 2;
    }

    var webAudioCtx = null;
    var webAudioGain = null;
    var webAudioHooked = false;

    function setupWebAudioBoost() {
      if (webAudioHooked || speechGain <= 1) return;
      try {
        var AC = global.AudioContext || global.webkitAudioContext;
        if (!AC) return;
        webAudioCtx = new AC();
        var src = webAudioCtx.createMediaElementSource(audioEl);
        webAudioGain = webAudioCtx.createGain();
        src.connect(webAudioGain);
        webAudioGain.connect(webAudioCtx.destination);
        webAudioHooked = true;
      } catch (e) {
        webAudioCtx = null;
        webAudioGain = null;
        webAudioHooked = false;
      }
    }

    function resumeWebAudioCtx() {
      if (webAudioCtx && webAudioCtx.state === 'suspended' && typeof webAudioCtx.resume === 'function') {
        try {
          var r = webAudioCtx.resume();
          if (r && typeof r.catch === 'function') r.catch(function () {});
        } catch (e) {
          /* ignore */
        }
      }
    }

    function applyOutputLevels(isChime) {
      try {
        if (isChime) {
          if (webAudioGain) webAudioGain.gain.value = 1;
          audioEl.volume = chimeVolume;
          return;
        }
        if (webAudioGain && speechGain > 1) {
          resumeWebAudioCtx();
          webAudioGain.gain.value = speechGain;
          audioEl.volume = 1;
        } else {
          if (webAudioGain) webAudioGain.gain.value = 1;
          audioEl.volume = speechVolume;
        }
      } catch (e) {
        /* ignore */
      }
    }

    function isChimeClipUrl(url) {
      if (!chimeUrl || !url) return false;
      return String(url).split('?')[0] === String(chimeUrl).split('?')[0];
    }

    function isChimeEndClipUrl(url) {
      if (!chimeEndUrl || !url) return false;
      return String(url).split('?')[0] === String(chimeEndUrl).split('?')[0];
    }

    function isChimeFamilyUrl(url) {
      return isChimeClipUrl(url) || isChimeEndClipUrl(url);
    }

    function appendEndChimeItems(items) {
      if (!chimeEndUrl || !Array.isArray(items)) return items;
      var alreadyQueued = items.some(function (it) {
        var u = typeof it === 'string' ? it : it && it.url;
        return isChimeEndClipUrl(u);
      });
      if (alreadyQueued) return items;
      items.push({ url: chimeEndUrl, gapAfter: gapMs, volume: chimeVolume });
      return items;
    }

    var noop = function () {};

    if (!audioEl || typeof audioEl.play !== 'function') {
      return {
        enqueueUrls: noop,
        enqueueTokens: noop,
        panggilAntrian: noop,
        enqueueSelesaiServis: noop,
        enqueueSelesaiServisBatch: noop,
        enqueueChimeThenClips: noop,
        preloadUrls: noop,
        clearQueue: noop,
        getQueueLength: function () {
          return 0;
        },
        tryUnlockWithChime: function () {
          return Promise.resolve(false);
        },
      };
    }

    try {
      audioEl.setAttribute('playsinline', '');
      audioEl.setAttribute('webkit-playsinline', '');
      audioEl.preload = 'auto';
    } catch (e) {
      /* ignore */
    }

    setupWebAudioBoost();

    var queue = [];
    var playing = false;
    var stallTimer = null;
    var canplayTimer = null;

    function clearTimers() {
      if (stallTimer) {
        global.clearTimeout(stallTimer);
        stallTimer = null;
      }
      if (canplayTimer) {
        global.clearTimeout(canplayTimer);
        canplayTimer = null;
      }
    }

    function resolveTokensToUrls(tokens) {
      var urls = [];
      for (var i = 0; i < tokens.length; i += 1) {
        var t = tokens[i];
        if (!t) continue;
        if (/^https?:\/\//i.test(t) || t.indexOf('/') === 0) {
          urls.push(t);
          continue;
        }
        var u = joinClip(clipBaseUrl, t);
        if (u) urls.push(u);
      }
      return urls;
    }

    function playUrlDirect(url, playOpts) {
      playOpts = playOpts || {};
      var vol =
        typeof playOpts.volume === 'number'
          ? clampVolume(playOpts.volume, speechVolume)
          : isChimeFamilyUrl(url)
            ? chimeVolume
            : speechVolume;

      return new Promise(function (resolve) {
        var settled = false;
        function fin() {
          if (settled) return;
          settled = true;
          clearTimers();
          try {
            audioEl.removeEventListener('ended', onEnded);
            audioEl.removeEventListener('error', onErr);
            audioEl.removeEventListener('stalled', onStalled);
            audioEl.removeEventListener('canplaythrough', onReady);
            audioEl.removeEventListener('canplay', onReady);
            audioEl.removeEventListener('playing', onReady);
          } catch (e) {
            /* ignore */
          }
          applyOutputLevels(false);
          resolve();
        }
        function armStall() {
          if (stallTimer) global.clearTimeout(stallTimer);
          stallTimer = global.setTimeout(function () {
            try {
              audioEl.pause();
            } catch (e) {
              /* ignore */
            }
            fin();
          }, stallSkipMs);
        }
        if (!url) {
          fin();
          return;
        }
        function clearCanplayTimer() {
          if (canplayTimer) {
            global.clearTimeout(canplayTimer);
            canplayTimer = null;
          }
        }
        function onEnded() {
          fin();
        }
        function onErr() {
          fin();
        }
        function onStalled() {
          armStall();
        }
        var playAttempts = 0;
        var playFailed = false;
        function startPlay() {
          if (settled) return;
          playAttempts += 1;
          var attempt = playAttempts;
          var p;
          try {
            p = audioEl.play();
          } catch (ePlay) {
            playFailed = attempt < 2;
            if (!playFailed) fin();
            return;
          }
          if (p !== undefined && typeof p.then === 'function') {
            p.then(function () {
              playFailed = false;
              clearCanplayTimer();
              armStall();
            }).catch(function () {
              if (settled || attempt >= 2) {
                if (!settled) fin();
                return;
              }
              var ready = 0;
              try {
                ready = audioEl.readyState;
              } catch (eReady) {
                /* ignore */
              }
              if (ready >= 2) {
                startPlay();
                return;
              }
              playFailed = true;
            });
          } else {
            clearCanplayTimer();
            armStall();
          }
        }
        /** TCL/Android TV sering menolak play() sebelum buffer MP3 siap. */
        function onReady() {
          clearCanplayTimer();
          if (!playFailed || settled) return;
          playFailed = false;
          startPlay();
        }
        try {
          audioEl.addEventListener('ended', onEnded, { once: true });
          audioEl.addEventListener('error', onErr, { once: true });
          audioEl.addEventListener('stalled', onStalled, { once: true });
          audioEl.addEventListener('canplaythrough', onReady, { once: true });
          audioEl.addEventListener('canplay', onReady, { once: true });
          audioEl.addEventListener('playing', onReady, { once: true });
        } catch (e) {
          /* ignore */
        }
        // Jangan potong klip yang sudah berbunyi — timeout hanya untuk buffer yang macet.
        canplayTimer = global.setTimeout(function () {
          canplayTimer = null;
          var started = false;
          try {
            started = !audioEl.paused && audioEl.currentTime > 0;
          } catch (eChk) {
            /* ignore */
          }
          if (started) {
            armStall();
            return;
          }
          try {
            audioEl.pause();
          } catch (e2) {
            /* ignore */
          }
          fin();
        }, canplayTimeoutMs);

        try {
          audioEl.pause();
          audioEl.src = url;
          audioEl.load();
        } catch (e3) {
          fin();
          return;
        }

        var isChime = isChimeFamilyUrl(url);
        try {
          audioEl.playbackRate = playbackRate;
          applyOutputLevels(isChime);
          if (!isChime && typeof playOpts.volume === 'number') {
            audioEl.volume = clampVolume(playOpts.volume, speechVolume);
          }
        } catch (eRate) {
          /* TV lama: abaikan */
        }

        startPlay();
      });
    }

    function playNext() {
      if (!queue.length) {
        playing = false;
        clearTimers();
        return;
      }
      playing = true;
      var next = queue.shift();
      var url = next && next.url;
      if (!url) {
        global.setTimeout(playNext, 0);
        return;
      }
      var vol =
        next && typeof next.volume === 'number'
          ? clampVolume(next.volume, speechVolume)
          : isChimeFamilyUrl(url)
            ? chimeVolume
            : speechVolume;
      var afterGap = next && typeof next.gapAfter === 'number' ? next.gapAfter : gapMs;
      playUrlDirect(url, { volume: vol }).then(function () {
        if (afterGap > 0) {
          global.setTimeout(playNext, afterGap);
        } else {
          playNext();
        }
      });
    }

    function normalizeQueueItem(item) {
      if (!item) return null;
      if (typeof item === 'string') {
        var u = String(item || '').trim();
        if (!u) return null;
        var row = { url: u };
        if (isChimeFamilyUrl(u)) row.volume = chimeVolume;
        return row;
      }
      if (typeof item === 'object' && item.url) {
        var url = String(item.url || '').trim();
        if (!url) return null;
        var out = { url: url };
        if (typeof item.gapAfter === 'number' && isFinite(item.gapAfter)) {
          out.gapAfter = item.gapAfter;
        }
        if (typeof item.volume === 'number' && isFinite(item.volume)) {
          out.volume = clampVolume(item.volume, speechVolume);
        } else if (isChimeFamilyUrl(url)) {
          out.volume = chimeVolume;
        }
        return out;
      }
      return null;
    }

    function enqueueUrls(urlList, atFront) {
      var arr = Array.isArray(urlList) ? urlList.slice() : [];
      var wrapped = arr
        .map(normalizeQueueItem)
        .filter(function (x) {
          return !!x;
        });
      if (!wrapped.length) return;
      if (atFront) {
        queue = wrapped.concat(queue);
      } else {
        queue = queue.concat(wrapped);
      }
      if (!playing) playNext();
    }

    function enqueueTokens(tokens, atFront) {
      enqueueUrls(resolveTokensToUrls(tokens), atFront);
    }

    /**
     * "Nomor antrian A 12 silahkan menuju loket 3" → potongan MP3 per kata/huruf/angka.
     * @param {object} params
     * @param {string} [params.huruf]
     * @param {number|string} params.nomor
     * @param {number|string} params.loket
     * @param {boolean} [params.denganChime]
     */
    function panggilAntrian(params) {
      params = params || {};
      var urls = [];
      if (params.denganChime && chimeUrl) urls.push(chimeUrl);
      var huruf = String(params.huruf || '')
        .trim()
        .charAt(0)
        .toLowerCase();
      var tokens = ['kata/nomor', 'kata/antrian'];
      if (huruf >= 'a' && huruf <= 'z') tokens.push('huruf/' + huruf);
      tokens = tokens.concat(digitsToClipTokens(params.nomor));
      tokens.push('kata/silahkan', 'kata/menuju', 'kata/loket');
      tokens = tokens.concat(digitsToClipTokens(params.loket));
      urls = urls.concat(resolveTokensToUrls(tokens));
      appendEndChimeItems(urls);
      enqueueUrls(urls, false);
    }

    function tokensForSelesaiPayload(payload) {
      payload = payload || {};
      var tokens = ['kata/nomor', 'kata/antrian'];
      var kode = payload.kode_servis != null ? String(payload.kode_servis) : '';
      var kt = kodeServisToClipTokens(kode);
      if (kt.length) {
        tokens = tokens.concat(kt);
      } else {
        tokens = tokens.concat(digitsToClipTokens(payload.orderId));
      }
      tokens.push('kata/silahkan_ke_kasir');
      return tokens;
    }

    /** Satu kali chime + satu rangkaian selesai servis. */
    function enqueueSelesaiServis(payload) {
      var urls = [];
      if (chimeUrl) urls.push(chimeUrl);
      urls = urls.concat(resolveTokensToUrls(tokensForSelesaiPayload(payload)));
      appendEndChimeItems(urls);
      enqueueUrls(urls, false);
    }

    /** Satu chime di awal, lalu beberapa WO tanpa chime berulang. */
    function enqueueSelesaiServisBatch(list) {
      if (!Array.isArray(list) || !list.length) return;
      var urls = [];
      if (chimeUrl) urls.push(chimeUrl);
      for (var i = 0; i < list.length; i += 1) {
        urls = urls.concat(resolveTokensToUrls(tokensForSelesaiPayload(list[i])));
      }
      appendEndChimeItems(urls);
      enqueueUrls(urls, false);
    }

    function enqueueChimeThenClips(urlList, opts) {
      opts = opts || {};
      var afterChime = typeof opts.chimeGapMs === 'number' ? opts.chimeGapMs : chimeToSpeechGapMs;
      var skipEndChime = !!opts.skipEndChime;
      var items = [];
      if (chimeUrl) {
        items.push({ url: chimeUrl, gapAfter: afterChime, volume: chimeVolume });
      }
      if (Array.isArray(urlList)) {
        for (var i = 0; i < urlList.length; i += 1) {
          var it = normalizeQueueItem(urlList[i]);
          if (it) items.push(it);
        }
      }
      if (!skipEndChime) {
        appendEndChimeItems(items);
      }
      enqueueUrls(items, false);
    }

    function preloadUrls(urlList) {
      if (!Array.isArray(urlList)) return;
      for (var i = 0; i < urlList.length; i += 1) {
        var u = String(urlList[i] || '').trim();
        if (!u) continue;
        try {
          var a = new Audio();
          a.preload = 'auto';
          a.src = u;
          a.load();
        } catch (e) {
          /* ignore */
        }
      }
    }

    function clearQueue() {
      queue = [];
      clearTimers();
      try {
        audioEl.pause();
        audioEl.removeAttribute('src');
        audioEl.load();
      } catch (e) {
        /* ignore */
      }
      playing = false;
    }

    function tryUnlockWithChime() {
      if (!chimeUrl) return Promise.resolve(false);
      return playUrlDirect(chimeUrl, { volume: chimeVolume }).then(function () {
        return true;
      });
    }

    return {
      enqueueUrls: enqueueUrls,
      enqueueTokens: enqueueTokens,
      panggilAntrian: panggilAntrian,
      enqueueSelesaiServis: enqueueSelesaiServis,
      enqueueSelesaiServisBatch: enqueueSelesaiServisBatch,
      enqueueChimeThenClips: enqueueChimeThenClips,
      preloadUrls: preloadUrls,
      clearQueue: clearQueue,
      getQueueLength: function () {
        return queue.length;
      },
      tryUnlockWithChime: tryUnlockWithChime,
      _joinClip: joinClip,
      _digitsToClipTokens: digitsToClipTokens,
      _kodeServisToClipTokens: kodeServisToClipTokens,
    };
  }

  global.createAntrianVoicePlayer = createAntrianVoicePlayer;
})(typeof window !== 'undefined' ? window : this);

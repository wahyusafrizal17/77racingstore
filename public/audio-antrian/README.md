# Audio antrian monitor (MP3 lokal — opsional)

Pengumuman **WO selesai** pada halaman monitor TV kini memakai **ElevenLabs** lewat Laravel (lihat `App\Services\ElevenLabsTtsService` dan route `speedshop.monitor-tv.antrian.tts`). File di folder ini hanya dipakai jika Anda memanggil `window.panggilAntrian(...)` (potongan kata/huruf/angka lokal).

## Struktur folder (opsional, untuk `panggilAntrian` saja)

Letakkan file di `public/audio-antrian/` agar URL mengikuti `asset('audio-antrian/...')`:

```
public/audio-antrian/
  README.md
  kata/
    nomor.mp3
    antrian.mp3
    silahkan.mp3
    menuju.mp3
    loket.mp3
    silahkan_ke_kasir.mp3
  huruf/
    a.mp3 … z.mp3
  angka/
    0.mp3 … 9.mp3
```

- **Kata**: rekaman frasa pendek (satu kata per file).
- **Huruf / angka**: satu file per karakter; nomor antrian dibaca **per digit** (mis. `12` → `1.mp3` lalu `2.mp3`).
- **Chime awal** layar monitor: `public/ting_ting_ting_ting.mp3`
- **Chime akhir** (setelah pengumuman panggilan selesai): `public/ting_ting_ting_ting_akhir.mp3`

Contoh urutan untuk *"Nomor antrian A 12 silahkan menuju loket 3"*:

`kata/nomor` → `kata/antrian` → `huruf/a` → `angka/1` → `angka/2` → `kata/silahkan` → `kata/menuju` → `kata/loket` → `angka/3`

Contoh untuk **WO selesai** (tanpa nama pelanggan): chime → `kata/nomor` → `kata/antrian` → potongan dari **kode servis** (huruf/angka) → `kata/silahkan_ke_kasir`. Jika kode kosong, sistem membacakan **ID order** per digit.

## Integrasi Laravel

1. Salin file MP3 ke folder di atas (nama file **persis** huruf kecil untuk `huruf/` dan `angka/`).
2. Halaman `resources/views/speedshop/monitor-tv/antrian.blade.php` sudah memuat:
   - `<audio id="antrian-voice-audio" preload="auto" playsinline webkit-playsinline>`
   - `{{ asset('js/monitor-tv-antrian-audio.js') }}`
   - Base klip: `rtrim(asset('audio-antrian'), '/')`
3. Di konsol / skrip lain pada halaman yang sama:
   - `window.panggilAntrian({ huruf: 'A', nomor: 12, loket: 3, denganChime: true })`
   - `window.antrianVoice` — instance pemutar (enqueue manual, dll.).

## Perilaku antrian (playNext)

Implementasi di `public/js/monitor-tv-antrian-audio.js`:

- **Satu** `HTMLAudioElement`; antrian berisi objek `{ url }`.
- **`playNext`**: mengambil URL berikutnya, mengatur `src`, `load()`, `play()`, menunggu event **`ended`** atau **`error`** (klip hilang / gagal → **dilompati**, lanjut ke berikutnya).
- **Jeda** `gapMs` (default 80 ms) antar klip untuk mengurangi “putus” di WebView TV.
- **Timeout** `canplayTimeoutMs` dan **stall** `stallSkipMs` agar antrian tidak macet jika satu file korup atau jaringan putus.

## Autoplay & kiosk / fullscreen

- Browser/TV tetap memerlukan **satu interaksi pengguna** (sentuh / OK) sebelum audio otomatis dari polling; teks hint di layar menjelaskan ini.
- Gunakan `playsinline` + `webkit-playsinline` (sudah di HTML) agar tidak memaksa fullscreen pemutaran di iOS/WebView.
- Mode kiosk: pastikan volume sistem TV tidak dibisukan; hindari mode hemat daya yang membunuh tab browser.

## Optimasi Xiaomi / Android TV

- Pakai **bitrate rendah** (mis. 64–96 kbps mono 22–44 kHz) untuk respons cepat.
- **Nama file pendek** tanpa spasi; hanya `a-z`, `0-9`, garis bawah.
- Setelah deploy, buka URL monitor sekali dan **sentuh layar** untuk unlock; uji dengan `panggilAntrian` dari konsol remote debugging.
- Jika klip sering ter-skip: cek **404** di Network tab; pastikan `public/audio-antrian` ter-deploy ke server yang sama dengan domain halaman (hindari mixed content HTTP/HTTPS).

## Contoh minimal HTML + inisialisasi

```html
<audio id="antrian-voice-audio" preload="auto" playsinline webkit-playsinline></audio>
<script src="/js/monitor-tv-antrian-audio.js"></script>
<script>
  const antrianVoice = createAntrianVoicePlayer({
    audioEl: document.getElementById('antrian-voice-audio'),
    clipBaseUrl: '/audio-antrian',
    chimeUrl: '/ting_ting_ting_ting.mp3',
    gapMs: 80,
  });
  function panggilAntrian(p) {
    antrianVoice.panggilAntrian(p);
  }
  panggilAntrian({ huruf: 'A', nomor: 12, loket: 3, denganChime: true });
</script>
```

`playNext` di dalam `monitor-tv-antrian-audio.js` dipanggil otomatis setelah setiap klip `ended` / `error` (plus jeda `gapMs`); tidak perlu dipanggil dari luar kecuali Anda mem-fork modul.

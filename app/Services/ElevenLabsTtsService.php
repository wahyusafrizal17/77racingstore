<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * TTS ElevenLabs → file MP3 di public/elevenlabs-tts/ (cache per isi teks + voice + model + speech_speed).
 * Langsung bisa diakses lewat URL tanpa php artisan storage:link.
 */
final class ElevenLabsTtsService
{
    private const MODEL_ID = 'eleven_multilingual_v2';

    private const OUTPUT_FORMAT = 'mp3_44100_64';

    private const MAX_TEXT_LEN = 500;

    private const PUBLIC_SUBDIR = 'elevenlabs-tts';

    public function isConfigured(?string $voiceId = null): bool
    {
        $key = (string) config('services.elevenlabs.api_key', '');
        $voice = $voiceId !== null && trim($voiceId) !== ''
            ? trim($voiceId)
            : (string) config('services.elevenlabs.voice_id', '');

        return $key !== '' && $voice !== '';
    }

    public function resellerVoiceId(): string
    {
        $reseller = trim((string) config('services.elevenlabs.voice_id_reseller', ''));
        if ($reseller !== '') {
            return $reseller;
        }

        return trim((string) config('services.elevenlabs.voice_id', ''));
    }

    public function isResellerConfigured(): bool
    {
        return $this->isConfigured($this->resellerVoiceId());
    }

    /**
     * @param  list<string>  $texts
     * @return list<string|null> URL ke /elevenlabs-tts/...mp3 (urutan sama dengan input)
     */
    public function resolveClipUrls(array $texts, ?string $voiceId = null): array
    {
        $resolvedVoiceId = trim((string) ($voiceId ?: config('services.elevenlabs.voice_id', '')));
        if (! $this->isConfigured($resolvedVoiceId)) {
            throw new \RuntimeException('ElevenLabs tidak dikonfigurasi.');
        }

        $this->ensureClipsDirectoryExists();

        $voiceId = $resolvedVoiceId;
        $apiKey = (string) config('services.elevenlabs.api_key');
        $timeout = (int) config('services.elevenlabs.timeout', 45);
        $speechSpeed = $this->resolvedSpeechSpeed();
        $speedTag = sprintf('%.4f', $speechSpeed);

        $n = count($texts);
        $out = array_fill(0, $n, null);
        $pending = [];

        for ($i = 0; $i < $n; $i += 1) {
            $norm = $this->normalizeText((string) ($texts[$i] ?? ''));
            if ($norm === '') {
                continue;
            }

            $hash = hash('sha256', $voiceId.'|'.self::MODEL_ID.'|'.$norm.'|s='.$speedTag);
            $filename = $hash.'.mp3';
            $fullPath = $this->clipFilesystemPath($filename);

            if (is_file($fullPath) && filesize($fullPath) > 0) {
                $out[$i] = $this->publicUrl($filename);
                continue;
            }

            $pending[] = [
                'i' => $i,
                'norm' => $norm,
                'filename' => $filename,
                'path' => $fullPath,
            ];
        }

        if ($pending === []) {
            return $out;
        }

        $endpoint = sprintf(
            'https://api.elevenlabs.io/v1/text-to-speech/%s',
            rawurlencode($voiceId)
        );

        foreach ($pending as $row) {
            try {
                $response = Http::timeout($timeout)
                    ->connectTimeout(10)
                    ->withHeaders([
                        'xi-api-key' => $apiKey,
                        'Accept' => 'audio/mpeg',
                        'Content-Type' => 'application/json',
                    ])
                    ->withQueryParameters([
                        'output_format' => self::OUTPUT_FORMAT,
                    ])
                    ->post($endpoint, [
                        'text' => $row['norm'],
                        'model_id' => self::MODEL_ID,
                        'voice_settings' => [
                            'speed' => $speechSpeed,
                        ],
                    ]);
            } catch (Throwable $e) {
                throw new \RuntimeException('Koneksi ElevenLabs gagal.', 0, $e);
            }

            if (! $response->successful()) {
                throw new \RuntimeException($this->describeHttpFailure($response));
            }

            $body = $response->body();
            if ($body === '' || strlen($body) < 32) {
                throw new \RuntimeException('Respons audio ElevenLabs tidak valid.');
            }

            $written = @file_put_contents($row['path'], $body, LOCK_EX);
            if ($written === false) {
                $last = error_get_last();
                $detail = is_array($last) && isset($last['message']) && is_string($last['message'])
                    ? $last['message']
                    : 'file_put_contents gagal.';
                throw new \RuntimeException($this->clipWriteFailureMessage($row['path'], $detail));
            }

            @chmod($row['path'], 0644);

            $out[$row['i']] = $this->publicUrl($row['filename']);
        }

        return $out;
    }

    /**
     * Kecepatan ucapan untuk API ElevenLabs (natural di bawah 1.0).
     */
    private function resolvedSpeechSpeed(): float
    {
        $v = (float) config('services.elevenlabs.speech_speed', 0.82);
        if (! is_finite($v)) {
            return 0.82;
        }
        if ($v < 0.65) {
            return 0.65;
        }
        if ($v > 1.05) {
            return 1.05;
        }

        return $v;
    }

    private function clipsDirectory(): string
    {
        return public_path(self::PUBLIC_SUBDIR);
    }

    private function ensureClipsDirectoryExists(): void
    {
        $dir = $this->clipsDirectory();
        if (! is_dir($dir)) {
            if (! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
                throw new \RuntimeException('Tidak dapat membuat direktori '.self::PUBLIC_SUBDIR.' di public.');
            }
        }
        if (! is_writable($dir)) {
            throw new \RuntimeException(
                'Folder cache TTS tidak dapat ditulis: '.$dir.'. '
                .'Berikan hak tulis ke user proses PHP-FPM (sering `www-data` atau `nginx`), contoh: '
                .'sudo chown -R www-data:www-data '.$dir.' && sudo chmod 775 '.$dir
            );
        }
    }

    private function clipWriteFailureMessage(string $path, string $phpDetail): string
    {
        $dir = dirname($path);
        $hint = 'sudo chown -R www-data:www-data '.$dir.' && sudo chmod 775 '.$dir;
        if (stripos($phpDetail, 'Permission denied') !== false) {
            return 'Tidak bisa menulis cache MP3 TTS (permission denied): '.$path.'. '
                .'Perbaiki izin folder `public/'.self::PUBLIC_SUBDIR.'` di server, contoh: '.$hint
                .' (sesuaikan user `www-data` dengan user PHP-FPM di server Anda).';
        }

        return 'Gagal menulis file MP3 ke public: '.$phpDetail;
    }

    private function clipFilesystemPath(string $filename): string
    {
        return $this->clipsDirectory().DIRECTORY_SEPARATOR.$filename;
    }

    private function normalizeText(string $text): string
    {
        $t = strip_tags($text);
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = preg_replace('/\s+/u', ' ', trim($t)) ?? '';

        if (mb_strlen($t) > self::MAX_TEXT_LEN) {
            $t = mb_substr($t, 0, self::MAX_TEXT_LEN);
        }

        return $t;
    }

    private function publicUrl(string $filename): string
    {
        return asset(self::PUBLIC_SUBDIR.'/'.ltrim($filename, '/'));
    }

    private function describeHttpFailure(Response $response): string
    {
        $status = $response->status();
        $data = $response->json();
        if (is_array($data)) {
            $detail = $data['detail'] ?? null;
            if (is_array($detail) && ! empty($detail['message']) && is_string($detail['message'])) {
                return (string) $detail['message'];
            }
            if (is_string($detail) && $detail !== '') {
                return $detail;
            }
            if (! empty($data['message']) && is_string($data['message'])) {
                return (string) $data['message'];
            }
        }

        return match ($status) {
            401 => 'Kunci API ElevenLabs tidak valid atau ditolak.',
            402 => 'ElevenLabs: paket berbayar / kredit diperlukan untuk TTS lewat API (HTTP 402).',
            403 => 'Akses ElevenLabs ditolak (HTTP 403).',
            404 => 'Voice ID tidak ditemukan di ElevenLabs (HTTP 404).',
            422 => 'Permintaan TTS ditolak ElevenLabs (validasi, HTTP 422).',
            429 => 'Batas permintaan ElevenLabs tercapai (HTTP 429). Coba lagi sebentar.',
            default => 'ElevenLabs mengembalikan HTTP '.$status.'.',
        };
    }
}

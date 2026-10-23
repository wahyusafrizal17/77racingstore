<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WacloudMessageService
{
    public function resolveDeviceId(): ?string
    {
        $id = trim((string) config('services.wacloud.device_id'));

        return $id !== '' ? $id : null;
    }

    /** Device ID untuk konteks non-speedshop. */
    public function resolveDeviceIdForGeneral(): ?string
    {
        return $this->resolveDeviceId();
    }

    public function isConfiguredForGeneral(): bool
    {
        $apiKey = trim((string) config('services.wacloud.api_key'));

        return $apiKey !== '' && $this->resolveDeviceIdForGeneral() !== null;
    }

    public function isConfigured(): bool
    {
        $apiKey = trim((string) config('services.wacloud.api_key'));
        $deviceId = trim((string) ($this->resolveDeviceId() ?? ''));

        return $apiKey !== '' && $deviceId !== '';
    }

    public function hasApiKey(): bool
    {
        return trim((string) config('services.wacloud.api_key')) !== '';
    }

    /**
     * Sisa quota WhatsApp dari WACLOUD (GET /account → data.quota).
     *
     * @return array{
     *     ok: bool,
     *     multimedia_quota: int,
     *     text_quota: int,
     *     total_text_quota: int,
     *     chat_quota: int,
     *     balance: float,
     *     subtitle: string,
     *     error?: string
     * }
     */
    public function accountQuota(): array
    {
        $empty = [
            'ok' => false,
            'multimedia_quota' => 0,
            'text_quota' => 0,
            'total_text_quota' => 0,
            'chat_quota' => 0,
            'balance' => 0.0,
            'subtitle' => '',
        ];

        if (! $this->hasApiKey()) {
            return array_merge($empty, ['error' => 'API key WACLOUD belum dikonfigurasi.']);
        }

        return Cache::remember('wacloud.account.quota', now()->addMinutes(15), function () use ($empty) {
            $base = rtrim((string) config('services.wacloud.base_url', 'https://app.wacloud.id/api/v1'), '/');
            $apiKey = config('services.wacloud.api_key');

            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'X-Api-Key' => $apiKey,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->get($base.'/account');
            } catch (\Throwable $e) {
                Log::warning('wacloud.account_quota_failed', ['exception' => $e->getMessage()]);

                return array_merge($empty, ['error' => 'Gagal menghubungi WACLOUD.']);
            }

            $json = $response->json();
            if (! $response->successful() || ! (bool) ($json['success'] ?? false)) {
                $msg = is_array($json)
                    ? ($json['message'] ?? $json['error'] ?? 'Respons tidak valid.')
                    : 'Respons tidak valid.';

                return array_merge($empty, ['error' => is_string($msg) ? $msg : 'Gagal memuat quota.']);
            }

            $quota = is_array($json['data']['quota'] ?? null) ? $json['data']['quota'] : [];
            $multimedia = (int) ($quota['multimedia_quota'] ?? 0);
            $text = (int) ($quota['text_quota'] ?? 0);
            $totalText = (int) ($quota['total_text_quota'] ?? ($text + (int) ($quota['free_text_quota'] ?? 0)));
            $balance = (float) ($quota['balance'] ?? 0);

            $subtitle = 'Multimedia: '.number_format($multimedia, 0, ',', '.').' pesan';
            if ($totalText > 0) {
                $subtitle .= ' · Teks: '.number_format($totalText, 0, ',', '.');
            }

            return [
                'ok' => true,
                'multimedia_quota' => $multimedia,
                'text_quota' => $text,
                'total_text_quota' => $totalText,
                'chat_quota' => $multimedia,
                'balance' => $balance,
                'subtitle' => $subtitle,
            ];
        });
    }

    /**
     * Statistik pemakaian API WACLOUD per periode (bukan sisa quota).
     *
     * @return array{
     *     ok: bool,
     *     message_count: int,
     *     successful_requests: int,
     *     total_requests: int,
     *     start_date: string,
     *     end_date: string,
     *     period_label: string,
     *     error?: string
     * }
     */
    public function accountUsage(?Carbon $start = null, ?Carbon $end = null): array
    {
        $start = ($start ?? now()->startOfMonth())->copy()->startOfDay();
        $end = ($end ?? now()->endOfMonth())->copy()->startOfDay();
        $startStr = $start->toDateString();
        $endStr = $end->toDateString();
        $periodLabel = $start->locale('id')->translatedFormat('d M Y').' – '.$end->locale('id')->translatedFormat('d M Y');

        $empty = [
            'ok' => false,
            'message_count' => 0,
            'successful_requests' => 0,
            'total_requests' => 0,
            'start_date' => $startStr,
            'end_date' => $endStr,
            'period_label' => $periodLabel,
        ];

        if (! $this->hasApiKey()) {
            return array_merge($empty, ['error' => 'API key WACLOUD belum dikonfigurasi.']);
        }

        $cacheKey = 'wacloud.account.usage.'.$startStr.'.'.$endStr;

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($startStr, $endStr, $periodLabel, $empty) {
            $base = rtrim((string) config('services.wacloud.base_url', 'https://app.wacloud.id/api/v1'), '/');
            $apiKey = config('services.wacloud.api_key');

            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'X-Api-Key' => $apiKey,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->get($base.'/account/usage', [
                        'start_date' => $startStr,
                        'end_date' => $endStr,
                    ]);
            } catch (\Throwable $e) {
                Log::warning('wacloud.account_usage_failed', ['exception' => $e->getMessage()]);

                return array_merge($empty, ['error' => 'Gagal menghubungi WACLOUD.']);
            }

            $json = $response->json();
            if (! $response->successful() || ! (bool) ($json['success'] ?? false)) {
                $msg = is_array($json)
                    ? ($json['message'] ?? $json['error'] ?? 'Respons tidak valid.')
                    : 'Respons tidak valid.';

                return array_merge($empty, ['error' => is_string($msg) ? $msg : 'Gagal memuat pemakaian.']);
            }

            $data = is_array($json['data'] ?? null) ? $json['data'] : [];

            return [
                'ok' => true,
                'message_count' => $this->extractMessageRequestCount($data),
                'successful_requests' => (int) ($data['successful_requests'] ?? 0),
                'total_requests' => (int) ($data['total_requests'] ?? 0),
                'start_date' => $startStr,
                'end_date' => $endStr,
                'period_label' => $periodLabel,
            ];
        });
    }

    /** @param  array<string, mixed>  $data */
    private function extractMessageRequestCount(array $data): int
    {
        $total = 0;
        foreach ($data['requests_by_endpoint'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }
            $endpoint = strtolower((string) ($row['endpoint'] ?? ''));
            if (str_contains($endpoint, 'message')) {
                $total += (int) ($row['count'] ?? 0);
            }
        }

        if ($total > 0) {
            return $total;
        }

        return (int) ($data['successful_requests'] ?? 0);
    }

    /**
     * Kirim dokumen (PDF) ke WhatsApp lewat WACLOUD (app.wacloud.id).
     *
     * @return array{ok: bool, message?: string, raw?: mixed}
     */
    public function sendDocument(string $toDigits, string $documentUrl, string $filename, string $caption): array
    {
        $apiKey = config('services.wacloud.api_key');
        $deviceId = $this->resolveDeviceId();
        $base = rtrim((string) config('services.wacloud.base_url', 'https://app.wacloud.id/api/v1'), '/');

        if ($apiKey === null || $apiKey === '' || $deviceId === null || $deviceId === '') {
            return ['ok' => false, 'message' => 'WACLOUD belum dikonfigurasi: isi WACLOUD_API_KEY dan device ID outlet (Bandung/Depok) di .env.'];
        }

        $url = $base.'/messages';
        $payload = [
            'device_id' => $deviceId,
            'to' => $toDigits,
            'message_type' => 'document',
            'document_url' => $documentUrl,
            'filename' => $filename,
            'caption' => $caption,
            'chat_type' => 'personal',
        ];

        try {
            $response = Http::timeout(90)
                ->withHeaders([
                    'X-Api-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($url, $payload);
        } catch (\Throwable $e) {
            Log::warning('wacloud.request_failed', ['exception' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'Gagal menghubungi WACLOUD: '.$e->getMessage()];
        }

        $json = $response->json();
        $ok = $response->successful() && (bool) ($json['success'] ?? false);

        if ($ok) {
            return ['ok' => true, 'raw' => $json];
        }

        $msg = is_array($json)
            ? ($json['message'] ?? $json['error'] ?? $response->body())
            : $response->body();

        if (is_array($json) && ! empty($json['errors']) && is_array($json['errors'])) {
            $details = collect($json['errors'])
                ->flatten()
                ->filter(fn ($v) => is_string($v) && trim($v) !== '')
                ->implode(' ');
            if ($details !== '') {
                $msg = $details;
            }
        }

        Log::warning('wacloud.send_document_failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return ['ok' => false, 'message' => is_string($msg) ? $msg : $response->body(), 'raw' => $json];
    }

    /**
     * Kirim pesan teks ke WhatsApp lewat WACLOUD.
     *
     * @return array{ok: bool, message?: string, raw?: mixed}
     */
    public function sendText(string $toDigits, string $message, ?string $deviceId = null): array
    {
        $apiKey = config('services.wacloud.api_key');
        $deviceId = $deviceId ?? $this->resolveDeviceIdForGeneral();
        $base = rtrim((string) config('services.wacloud.base_url', 'https://app.wacloud.id/api/v1'), '/');

        if ($apiKey === null || $apiKey === '' || $deviceId === null || $deviceId === '') {
            return ['ok' => false, 'message' => 'WACLOUD belum dikonfigurasi.'];
        }

        $message = trim($message);
        if ($message === '') {
            return ['ok' => false, 'message' => 'Isi pesan WhatsApp kosong.'];
        }

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'X-Api-Key' => $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($base.'/messages', [
                    'device_id' => $deviceId,
                    'to' => $toDigits,
                    'message_type' => 'text',
                    'text' => $message,
                    'message' => $message,
                    'chat_type' => 'personal',
                ]);
        } catch (\Throwable $e) {
            Log::warning('wacloud.send_text_failed', ['exception' => $e->getMessage()]);

            return ['ok' => false, 'message' => 'Gagal menghubungi WACLOUD: '.$e->getMessage()];
        }

        $json = $response->json();
        $ok = $response->successful() && (bool) ($json['success'] ?? false);

        if ($ok) {
            return ['ok' => true, 'raw' => $json];
        }

        $msg = is_array($json)
            ? ($json['message'] ?? $json['error'] ?? $response->body())
            : $response->body();

        if (is_array($json) && ! empty($json['errors']) && is_array($json['errors'])) {
            $details = collect($json['errors'])
                ->flatten()
                ->filter(fn ($v) => is_string($v) && trim($v) !== '')
                ->implode(' ');
            if ($details !== '') {
                $msg = $details;
            }
        }

        Log::warning('wacloud.send_text_failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return ['ok' => false, 'message' => is_string($msg) ? $msg : $response->body(), 'raw' => $json];
    }
}

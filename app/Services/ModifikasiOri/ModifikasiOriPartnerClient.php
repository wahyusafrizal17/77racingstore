<?php

namespace App\Services\ModifikasiOri;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Klien HTTP ke API partner Modifikasi Ori (routes/api.php → prefix /api/partner di projek modifikasiori).
 */
class ModifikasiOriPartnerClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.modifikasi_ori.base_url')) && filled(config('services.modifikasi_ori.token'));
    }

    /**
     * Katalog produk warehouse Modifikasi Ori (sku, nama, brand, kategori, harga, stok_warehouse).
     * Di-cache singkat agar halaman Buat PO tidak memanggil API berulang.
     *
     * @return list<array<string, mixed>>
     */
    public function catalog(bool $fresh = false): array
    {
        $key = 'mo_partner_catalog';
        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addMinutes(2), function () {
            return (array) ($this->send('get', '/catalog/products')['data'] ?? []);
        });
    }

    /**
     * @param  list<string>  $refs
     * @return list<array<string, mixed>>
     */
    public function purchaseOrders(array $refs): array
    {
        if ($refs === []) {
            return [];
        }

        return (array) ($this->send('get', '/purchase-orders', ['refs' => array_values($refs)])['data'] ?? []);
    }

    /**
     * @param  list<array{sku: string, qty: int}>  $items
     * @return array<string, mixed>
     */
    public function createPurchaseOrder(string $ref, ?string $catatan, array $items): array
    {
        return (array) $this->send('post', '/purchase-orders', [
            'external_ref' => $ref,
            'catatan' => $catatan,
            'items' => $items,
        ])['data'];
    }

    /** @return array<string, mixed> */
    public function cancel(string $ref, string $reason): array
    {
        return (array) $this->send('post', '/purchase-orders/cancel', ['ref' => $ref, 'reason' => $reason])['data'];
    }

    /** @return array<string, mixed> */
    public function receive(string $ref): array
    {
        return (array) $this->send('post', '/purchase-orders/receive', ['ref' => $ref])['data'];
    }

    /** @return array<string, mixed> */
    public function reject(string $ref, ?string $reason): array
    {
        return (array) $this->send('post', '/purchase-orders/reject', ['ref' => $ref, 'reason' => $reason])['data'];
    }

    /** @return array<string, mixed> */
    private function send(string $method, string $path, array $data = []): array
    {
        if (! $this->isConfigured()) {
            throw new ModifikasiOriApiException('Integrasi Modifikasi Ori belum dikonfigurasi (MO_PARTNER_API_URL / MO_PARTNER_API_TOKEN).');
        }

        try {
            /** @var Response $response */
            $response = $this->http()->{$method}($path, $data);
        } catch (ConnectionException $e) {
            throw new ModifikasiOriApiException('Tidak dapat terhubung ke server Modifikasi Ori. Coba lagi beberapa saat.', 0, $e);
        }

        if ($response->successful()) {
            return (array) $response->json();
        }

        $message = (string) ($response->json('message') ?: 'Server Modifikasi Ori mengembalikan error (HTTP '.$response->status().').');
        if ($response->status() === 401) {
            $message = 'Token API Modifikasi Ori tidak valid atau sudah diganti. Perbarui MO_PARTNER_API_TOKEN.';
        }
        $errors = (array) ($response->json('errors') ?? []);
        if ($errors !== []) {
            $first = collect($errors)->flatten()->first();
            if (is_string($first) && $first !== '') {
                $message = $first;
            }
        }

        throw new ModifikasiOriApiException($message, $response->status());
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.modifikasi_ori.base_url'), '/'))
            ->withToken((string) config('services.modifikasi_ori.token'))
            ->acceptJson()
            ->asJson()
            ->timeout(max(5, (int) config('services.modifikasi_ori.timeout', 20)))
            ->withUserAgent('77RacingStore-PartnerPO/1.0');
    }
}

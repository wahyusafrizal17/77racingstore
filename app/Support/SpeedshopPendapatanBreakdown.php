<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class SpeedshopPendapatanBreakdown
{
    /**
     * @param  array<int, int>  $serviceOrderIds
     * @return array{tunai: float, qris: float, transfer: float, edc: float}
     */
    public static function byMetodeFromServiceOrderIds(array $serviceOrderIds): array
    {
        $out = [
            'tunai' => 0.0,
            'qris' => 0.0,
            'transfer' => 0.0,
            'edc' => 0.0,
        ];

        if ($serviceOrderIds === []) {
            return $out;
        }

        $rows = DB::table('invoices')
            ->whereIn('service_order_id', $serviceOrderIds)
            ->get(['metode_pembayaran', 'grand_total', 'pembayaran_split']);

        foreach ($rows as $inv) {
            $metode = strtolower(trim((string) ($inv->metode_pembayaran ?? '')));
            $grandTotal = (float) ($inv->grand_total ?? 0);

            if ($metode === 'split') {
                $split = $inv->pembayaran_split;
                if (is_string($split) && $split !== '') {
                    $split = json_decode($split, true);
                }
                if (is_array($split)) {
                    foreach ($split as $row) {
                        if (! is_array($row)) {
                            continue;
                        }
                        $rowMetode = $row['metode'] ?? $row['jenis'] ?? $row['method'] ?? $row['jenis_pembayaran'] ?? $row['pembayaran'] ?? null;
                        $rowJumlah = $row['jumlah'] ?? $row['nominal'] ?? $row['amount'] ?? null;

                        $bucket = self::normalizeMetodeBucket($rowMetode);
                        if ($bucket === null) {
                            continue;
                        }
                        $out[$bucket] += (float) ($rowJumlah ?? 0);
                    }

                    continue;
                }
            }

            $bucket = self::normalizeMetodeBucket($metode);
            if ($bucket !== null) {
                $out[$bucket] += $grandTotal;
            }
        }

        return $out;
    }

    public static function normalizeMetodeBucket(?string $metode): ?string
    {
        $m = strtolower(trim((string) ($metode ?? '')));
        if ($m === '') {
            return null;
        }

        if (in_array($m, ['tunai', 'cash'], true)) {
            return 'tunai';
        }
        if (in_array($m, ['qris', 'qr', 'qriss'], true)) {
            return 'qris';
        }
        if (in_array($m, ['transfer', 'tf', 'bank'], true)) {
            return 'transfer';
        }
        if (in_array($m, ['edc', 'debit', 'kartu', 'card'], true)) {
            return 'edc';
        }

        return null;
    }

    /**
     * @param  array{tunai: float, qris: float, transfer: float, edc: float}  $buckets
     * @return array{cash: float, qris: float, transfer: float, edc: float}
     */
    public static function toReportPaymentKeys(array $buckets): array
    {
        return [
            'cash' => (float) ($buckets['tunai'] ?? 0),
            'qris' => (float) ($buckets['qris'] ?? 0),
            'transfer' => (float) ($buckets['transfer'] ?? 0),
            'edc' => (float) ($buckets['edc'] ?? 0),
        ];
    }
}

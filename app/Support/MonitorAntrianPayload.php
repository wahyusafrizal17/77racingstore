<?php

namespace App\Support;

use App\Models\ServiceOrder;
use App\Models\Speedshop;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class MonitorAntrianPayload
{
    private static function isExpress(?string $kategoriService): bool
    {
        return strtolower(trim((string) $kategoriService)) === 'express';
    }

    /**
     * @return array<string, mixed>
     */
    private static function mapInProcessRow(ServiceOrder $o): array
    {
        $k = $o->kategori_service !== null && $o->kategori_service !== ''
            ? strtolower(trim((string) $o->kategori_service))
            : null;

        return [
            'id' => $o->id,
            'kode_servis' => $o->kode_servis,
            'pelanggan' => $o->pelanggan?->nama,
            'no_hp' => $o->pelanggan?->no_hp,
            'kendaraan' => $o->kendaraan?->nomor_polisi,
            'tipe_motor' => trim(implode(' ', array_filter([
                $o->kendaraan?->merk,
                $o->kendaraan?->tipe,
            ], fn ($v) => (string) $v !== ''))),
            'merk' => $o->kendaraan?->merk !== null && $o->kendaraan?->merk !== ''
                ? trim((string) $o->kendaraan->merk)
                : null,
            'mekanik' => $o->mekanik?->nama,
            'kategori_service' => $k,
            'kategori_service_label' => $o->kategoriServiceDisplay(),
            'tanggal_masuk' => $o->tanggal_masuk?->format('Y-m-d'),
            'tanggal_masuk_at' => $o->tanggal_masuk?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function mapQueueRow(ServiceOrder $o): array
    {
        $k = $o->kategori_service !== null && $o->kategori_service !== ''
            ? strtolower(trim((string) $o->kategori_service))
            : null;

        return [
            'id' => $o->id,
            'kode_servis' => $o->kode_servis,
            'pelanggan' => $o->pelanggan?->nama,
            'no_hp' => $o->pelanggan?->no_hp,
            'kendaraan' => $o->kendaraan?->nomor_polisi,
            'tipe_motor' => trim(implode(' ', array_filter([
                $o->kendaraan?->merk,
                $o->kendaraan?->tipe,
            ], fn ($v) => (string) $v !== ''))),
            'merk' => $o->kendaraan?->merk !== null && $o->kendaraan?->merk !== ''
                ? trim((string) $o->kendaraan->merk)
                : null,
            'kategori_service' => $k,
            'kategori_service_label' => $o->kategoriServiceDisplay(),
            'tanggal_masuk' => $o->tanggal_masuk?->format('Y-m-d'),
            'tanggal_masuk_at' => $o->tanggal_masuk?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function mapCollection(Collection $c, callable $mapper): array
    {
        return $c->map($mapper)->values()->all();
    }

    /**
     * @param  null|array<int, int>  $speedshopScopeIds  null jika kolom speedshop_id belum ada (DB lama). Array ID speedshop yang boleh tampil di monitor (satu outlet atau gabungan outlet sesuai scope pengguna).
     */
    private static function baseServiceOrderQuery(int $warehouseId, ?array $speedshopScopeIds): Builder
    {
        $q = ServiceOrder::with(['pelanggan', 'kendaraan', 'mekanik']);
        if ($warehouseId > 0) {
            $q->where('warehouse_id', $warehouseId);
        }

        if (! Schema::hasColumn('service_orders', 'speedshop_id')) {
            return $warehouseId > 0 ? $q : $q->whereRaw('1 = 0');
        }

        if ($speedshopScopeIds === null) {
            return $warehouseId > 0 ? $q : $q->whereRaw('1 = 0');
        }

        $ids = array_values(array_unique(array_map(static fn ($id) => (int) $id, $speedshopScopeIds)));

        if ($ids === []) {
            return $q->whereRaw('1 = 0');
        }

        return $q->whereIn('speedshop_id', $ids);
    }

    /**
     * @param  null|array<int, int>  $speedshopScopeIds
     * @return array{
     *     warehouseId: int,
     *     inProcessExpressData: array<int, array<string, mixed>>,
     *     inProcessStandardData: array<int, array<string, mixed>>,
     *     queueExpressData: array<int, array<string, mixed>>,
     *     queueStandardData: array<int, array<string, mixed>>,
     *     inProcessData: array<int, array<string, mixed>>,
     *     queueData: array<int, array<string, mixed>>,
     *     monitorAntrianTitle: string,
     *     statTotalHariIni: int,
     *     statSelesaiHariIni: int
     * }
     */
    public static function build(int $warehouseId, ?array $speedshopScopeIds): array
    {
        $base = self::baseServiceOrderQuery($warehouseId, $speedshopScopeIds);

        $today = now()->toDateString();
        $baseToday = (clone $base)->whereDate('tanggal_masuk', $today);

        $inProcess = (clone $baseToday)->where('status', 'proses')->orderByDesc('tanggal_masuk')->get();
        $queue = (clone $baseToday)->where('status', 'antri')->orderBy('tanggal_masuk')->get();

        $inExpress = $inProcess->filter(fn (ServiceOrder $o) => self::isExpress($o->kategori_service))->values();
        $inStandard = $inProcess->filter(fn (ServiceOrder $o) => ! self::isExpress($o->kategori_service))->values();

        $qExpress = $queue->filter(fn (ServiceOrder $o) => self::isExpress($o->kategori_service))->values();
        $qStandard = $queue->filter(fn (ServiceOrder $o) => ! self::isExpress($o->kategori_service))->values();

        $inProcessExpressData = self::mapCollection($inExpress, fn (ServiceOrder $o) => self::mapInProcessRow($o));
        $inProcessStandardData = self::mapCollection($inStandard, fn (ServiceOrder $o) => self::mapInProcessRow($o));
        $queueExpressData = self::mapCollection($qExpress, fn (ServiceOrder $o) => self::mapQueueRow($o));
        $queueStandardData = self::mapCollection($qStandard, fn (ServiceOrder $o) => self::mapQueueRow($o));

        /** Gabungan prioritas Express dulu (kompatibilitas / ringkas). */
        $inProcessData = array_merge($inProcessExpressData, $inProcessStandardData);
        $queueData = array_merge($queueExpressData, $queueStandardData);

        $warehouseModel = null;
        $scopeIds = $speedshopScopeIds === null
            ? null
            : array_values(array_unique(array_map(static fn ($id) => (int) $id, $speedshopScopeIds)));

        if ($scopeIds !== null && count($scopeIds) === 1) {
            $speedshop = Speedshop::query()->find($scopeIds[0]);
            $cabangNama = $speedshop?->nama ?? $warehouseModel?->nama ?? 'Speedshop';
        } elseif ($scopeIds !== null && count($scopeIds) > 1) {
            $cabangNama = $warehouseModel?->nama ?? 'Speedshop';
        } else {
            $speedshop = Speedshop::query()
                ->where(function ($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId)
                        ->orWhere('mutasi_warehouse_id', $warehouseId);
                })
                ->orderBy('id')
                ->first();
            $cabangNama = $speedshop?->nama ?? $warehouseModel?->nama ?? 'Speedshop';
        }

        $monitorAntrianTitle = 'ANTRIAN '.mb_strtoupper($cabangNama, 'UTF-8');

        $totalHariIni = (clone $baseToday)->count();
        $selesaiHariIni = (clone $baseToday)->where('status', 'selesai')->count();

        return [
            'warehouseId' => $warehouseId,
            'inProcessExpressData' => $inProcessExpressData,
            'inProcessStandardData' => $inProcessStandardData,
            'queueExpressData' => $queueExpressData,
            'queueStandardData' => $queueStandardData,
            'inProcessData' => $inProcessData,
            'queueData' => $queueData,
            'monitorAntrianTitle' => $monitorAntrianTitle,
            'statTotalHariIni' => $totalHariIni,
            'statSelesaiHariIni' => $selesaiHariIni,
        ];
    }
}

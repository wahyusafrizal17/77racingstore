<?php

namespace App\Support;

use App\Models\AkunOperasional;
use App\Models\Product;
use App\Models\SpeedshopRincianBiayaEntry;
use App\Models\TransaksiOffline;
use App\Models\TransaksiOnline;
use App\Models\WarehouseRekapPenjualanMonthlyAdjustment;
use App\Models\WarehouseReseller;
use App\Services\SpeedshopPoTagihanService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class WarehouseLabaRugiFinancials
{
    /**
     * @return array{
     *     total_pendapatan: float,
     *     total_hpp: float,
     *     total_hpp_channel: float,
     *     total_hpp_returns: float,
     *     total_biaya_marketplace: float,
     *     total_biaya_iklan: float,
     *     gross_profit: float,
     *     total_biaya_operasional: float,
     *     total_biaya_non_operasional: float,
     *     total_biaya_operasional_section: float,
     *     total_biaya: float,
     *     net_profit_before_zakat: float,
     *     total_modal: float,
     *     zakat: float,
     *     net_profit: float,
     *     unit_entry: int,
     *     pct_hpp: float,
     *     pct_gross: float,
     *     pct_biaya: float,
     *     pct_net_before: float,
     *     pct_net: float,
     * }
     */
    public static function computeMetrics(Carbon $dari, Carbon $sampai, ?int $warehouseId): array
    {
        $channelKeys = array_column(WarehouseLabaRugiChannels::SALES, 'key');
        $pendapatan = array_fill_keys($channelKeys, 0.0);
        $hpp = array_fill_keys($channelKeys, 0.0);

        if ($warehouseId) {
            self::accumulateOnlineTotals($warehouseId, $dari, $sampai, $pendapatan, $hpp);
            self::accumulateOfflineTotals($warehouseId, $dari, $sampai, $pendapatan, $hpp);
            self::accumulateWarehouseToSpeedshopTotals($warehouseId, $dari, $sampai, $pendapatan, $hpp);
        }

        $totalPendapatan = array_sum($pendapatan);

        $biayaMarketplace = self::rincianBiayaTotalsByPrefix($warehouseId, $dari, $sampai, '5.2.');
        $biayaIklan = self::rincianBiayaTotalsByPrefix($warehouseId, $dari, $sampai, '5.3.');
        $hppReturns = self::rincianBiayaTotalsForAccounts(
            $warehouseId,
            $dari,
            $sampai,
            array_column(WarehouseLabaRugiChannels::HPP_RETURNS, 'no'),
        );
        $biayaOpByAkun = self::rincianBiayaTotalsByPrefix($warehouseId, $dari, $sampai, '5.4.');
        $biayaNonOpByAkun = self::rincianBiayaTotalsByPrefix($warehouseId, $dari, $sampai, '5.5.');

        $totalHppChannel = array_sum($hpp);
        $totalHppReturns = array_sum($hppReturns);
        $totalHpp = $totalHppChannel + $totalHppReturns;

        $totalBiayaMarketplace = array_sum($biayaMarketplace);
        $totalBiayaIklan = array_sum($biayaIklan);

        $grossProfit = $totalPendapatan - $totalHpp - $totalBiayaMarketplace - $totalBiayaIklan;

        $totalBiayaOperasional = array_sum($biayaOpByAkun);
        $totalBiayaNonOperasional = array_sum($biayaNonOpByAkun);
        $totalBiayaOperasionalSection = $totalBiayaOperasional + $totalBiayaNonOperasional;

        $totalBiayaAll = $totalHpp + $totalBiayaMarketplace + $totalBiayaIklan + $totalBiayaOperasionalSection;

        $netProfitBeforeZakat = $totalPendapatan - $totalBiayaAll;
        $totalModal = WarehouseNeracaFinancials::stokModalValue($warehouseId);
        $zakatPct = 2.5;
        $zakat = max(0, $totalModal) * ($zakatPct / 100) / 12;
        $netProfit = $netProfitBeforeZakat - $zakat;

        $pctHpp = $totalPendapatan > 0 ? ($totalHpp / $totalPendapatan) * 100 : 0;
        $pctGross = $totalPendapatan > 0 ? ($grossProfit / $totalPendapatan) * 100 : 0;
        $pctBiaya = $totalPendapatan > 0 ? ($totalBiayaAll / $totalPendapatan) * 100 : 0;
        $pctNetBefore = $totalPendapatan > 0 ? ($netProfitBeforeZakat / $totalPendapatan) * 100 : 0;
        $pctNet = $totalPendapatan > 0 ? ($netProfit / $totalPendapatan) * 100 : 0;

        return [
            'total_pendapatan' => $totalPendapatan,
            'total_hpp' => $totalHpp,
            'total_hpp_channel' => $totalHppChannel,
            'total_hpp_returns' => $totalHppReturns,
            'total_biaya_marketplace' => $totalBiayaMarketplace,
            'total_biaya_iklan' => $totalBiayaIklan,
            'gross_profit' => $grossProfit,
            'total_biaya_operasional' => $totalBiayaOperasional,
            'total_biaya_non_operasional' => $totalBiayaNonOperasional,
            'total_biaya_operasional_section' => $totalBiayaOperasionalSection,
            'total_biaya' => $totalBiayaAll,
            'net_profit_before_zakat' => $netProfitBeforeZakat,
            'total_modal' => $totalModal,
            'zakat' => $zakat,
            'net_profit' => $netProfit,
            'unit_entry' => self::unitEntryCount($warehouseId, $dari, $sampai),
            'pct_hpp' => $pctHpp,
            'pct_gross' => $pctGross,
            'pct_biaya' => $pctBiaya,
            'pct_net_before' => $pctNetBefore,
            'pct_net' => $pctNet,
        ];
    }

    /**
     * Baris metrik laporan Pendapatan & Biaya warehouse — selaras laba rugi.
     *
     * @return array<string, float|int>
     */
    public static function pendapatanBiayaRow(Carbon $dari, Carbon $sampai, ?int $warehouseId): array
    {
        $m = self::computeMetrics($dari, $sampai, $warehouseId);

        return [
            'omzet_kotor' => (float) $m['total_pendapatan'],
            'unit_entry' => (int) $m['unit_entry'],
            'hpp_modal' => (float) $m['total_hpp_channel'],
            'hpp_return' => (float) $m['total_hpp_returns'],
            'biaya_marketplace' => (float) $m['total_biaya_marketplace'],
            'biaya_iklan' => (float) $m['total_biaya_iklan'],
            'gross_profit' => (float) $m['gross_profit'],
            'bop_operasional' => (float) $m['total_biaya_operasional'],
            'bop_non_operasional' => (float) $m['total_biaya_non_operasional'],
            'profit_bersih' => (float) $m['net_profit_before_zakat'],
            'nett_profit' => (float) $m['net_profit'],
        ];
    }

    public static function unitEntryCount(?int $warehouseId, Carbon $dari, Carbon $sampai): int
    {
        if (! $warehouseId) {
            return 0;
        }

        $online = TransaksiOnline::query()
            ->selesai()
            ->where('warehouse_id', $warehouseId)
            ->whereBetween('created_at', [$dari, $sampai])
            ->count();

        $offline = self::offlineCountableQuery($warehouseId, $dari, $sampai)->count();
        $speedshop = self::warehouseToSpeedshopCountableQuery($warehouseId, $dari, $sampai)->count();

        return $online + $offline + $speedshop;
    }

    /**
     * @return array{pendapatan: array<string, float>, hpp: array<string, float>}
     */
    public static function channelTotalsForPeriod(
        ?int $warehouseId,
        Carbon $dari,
        Carbon $sampai,
    ): array {
        $channelKeys = array_column(WarehouseLabaRugiChannels::SALES, 'key');
        $pendapatan = array_fill_keys($channelKeys, 0.0);
        $hpp = array_fill_keys($channelKeys, 0.0);

        if ($warehouseId) {
            self::accumulateOnlineTotals($warehouseId, $dari, $sampai, $pendapatan, $hpp);
            self::accumulateOfflineTotals($warehouseId, $dari, $sampai, $pendapatan, $hpp);
            self::accumulateWarehouseToSpeedshopTotals($warehouseId, $dari, $sampai, $pendapatan, $hpp);
        }

        return ['pendapatan' => $pendapatan, 'hpp' => $hpp];
    }

    /**
     * Rekap harian per saluran penjualan — untuk laporan Summary warehouse.
     *
     * @return \Illuminate\Support\Collection<int, object{
     *     tanggal: string,
     *     rows: \Illuminate\Support\Collection,
     *     pendapatan_kotor: float,
     *     hpp_part: float,
     *     biaya_marketplace: float,
     *     biaya_iklan: float,
     *     biaya_operasional: float,
     *     biaya_non_operasional: float,
     *     total_biaya: float,
     *     gross_profit: float,
     *     nett_profit: float,
     *     total_gross_profit_before_hpp: float
     * }>
     */
    public static function dailySummaryReports(
        Carbon $dari,
        Carbon $sampai,
        ?int $warehouseId,
    ): \Illuminate\Support\Collection {
        $reports = collect();
        $cursor = $dari->copy()->startOfDay();
        $end = $sampai->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $dayStart = $cursor->copy()->startOfDay();
            $dayEnd = $cursor->copy()->endOfDay();
            $channels = self::channelTotalsForPeriod($warehouseId, $dayStart, $dayEnd);
            $m = self::computeMetrics($dayStart, $dayEnd, $warehouseId);

            $rows = collect(WarehouseLabaRugiChannels::SALES)
                ->map(function (array $channel) use ($channels) {
                    $key = $channel['key'];
                    $pendapatan = (float) ($channels['pendapatan'][$key] ?? 0);

                    return (object) [
                        'key' => $key,
                        'label' => $channel['label'],
                        'pendapatan' => $pendapatan,
                        'hpp' => (float) ($channels['hpp'][$key] ?? 0),
                        'gross_profit' => $pendapatan,
                    ];
                })
                ->filter(fn ($row) => (float) $row->pendapatan > 0)
                ->values();

            if ($rows->isEmpty() && ((float) $m['total_pendapatan'] > 0 || (float) $m['total_biaya'] > 0)) {
                $rows = collect([(object) [
                    'key' => null,
                    'label' => 'Tidak ada penjualan tercatat',
                    'pendapatan' => 0.0,
                    'hpp' => 0.0,
                    'gross_profit' => 0.0,
                ]]);
            }

            if ($rows->isNotEmpty() || (float) $m['total_pendapatan'] > 0 || (float) $m['total_biaya'] > 0) {
                $reports->push((object) [
                    'tanggal' => $cursor->toDateString(),
                    'rows' => $rows,
                    'unit_entry' => (int) $m['unit_entry'],
                    'pendapatan_kotor' => (float) $m['total_pendapatan'],
                    'hpp_part' => (float) $m['total_hpp'],
                    'biaya_marketplace' => (float) $m['total_biaya_marketplace'],
                    'biaya_iklan' => (float) $m['total_biaya_iklan'],
                    'biaya_operasional' => (float) $m['total_biaya_operasional'],
                    'biaya_non_operasional' => (float) $m['total_biaya_non_operasional'],
                    'total_biaya' => (float) $m['total_biaya'],
                    'gross_profit' => (float) $m['gross_profit'],
                    'nett_profit' => (float) $m['net_profit_before_zakat'],
                    'total_gross_profit_before_hpp' => (float) $m['net_profit_before_zakat'],
                ]);
            }

            $cursor->addDay();
        }

        return $reports;
    }

    /**
     * Rekap penjualan per reseller master (ONLINE, CANVASSING, OFFLINE).
     *
     * @return array{
     *     rows: list<array{
     *         no: int,
     *         kategori: string,
     *         keterangan: string,
     *         total_order: int,
     *         pendapatan_kotor: float,
     *         return_amount: float,
     *         pendapatan_bersih: float,
     *         diskon: float,
     *         ongkir: float,
     *         hpp: float,
     *         iklan_bop: float,
     *         profit_before_tax: float
     *     }>,
     *     totals: array{
     *         total_order: int,
     *         pendapatan_kotor: float,
     *         return_amount: float,
     *         pendapatan_bersih: float,
     *         diskon: float,
     *         ongkir: float,
     *         hpp: float,
     *         iklan_bop: float,
     *         profit_before_tax: float
     *     },
     *     kategori_options: list<string>
     * }
     */
    public static function rekapPenjualanReport(
        Carbon $dari,
        Carbon $sampai,
        ?int $warehouseId,
    ): array {
        $resellerAgg = [];
        if ($warehouseId) {
            $resellers = WarehouseReseller::query()
                ->forWarehouse($warehouseId)
                ->orderBy('kategori')
                ->orderBy('nama')
                ->get(['id', 'nama', 'kategori']);

            foreach ($resellers as $reseller) {
                $resellerAgg[(int) $reseller->id] = [
                    'kategori' => mb_strtoupper(trim((string) $reseller->kategori)),
                    'keterangan' => mb_strtoupper(trim((string) $reseller->nama)),
                    'total_order' => 0,
                    'pendapatan_kotor' => 0.0,
                    'hpp' => 0.0,
                ];
            }

            $offlineTransaksis = self::offlineCountableQuery($warehouseId, $dari, $sampai)
                ->where('tujuan', 'reseller')
                ->whereNotNull('warehouse_reseller_id')
                ->with(['items.product', 'warehouseReseller'])
                ->get();

            foreach ($offlineTransaksis as $transaksi) {
                $resellerId = (int) ($transaksi->warehouse_reseller_id ?? 0);
                if ($resellerId < 1) {
                    continue;
                }

                if (! array_key_exists($resellerId, $resellerAgg)) {
                    $nama = mb_strtoupper(trim((string) ($transaksi->warehouseReseller?->nama ?? 'RESELLER')));
                    $kategori = mb_strtoupper(trim((string) ($transaksi->warehouseReseller?->kategori ?? WarehouseReseller::KATEGORI_OFFLINE)));
                    $resellerAgg[$resellerId] = [
                        'kategori' => $kategori !== '' ? $kategori : WarehouseReseller::KATEGORI_OFFLINE,
                        'keterangan' => $nama !== '' ? $nama : 'RESELLER',
                        'total_order' => 0,
                        'pendapatan_kotor' => 0.0,
                        'hpp' => 0.0,
                    ];
                }

                if (($resellerAgg[$resellerId]['kategori'] ?? '') === WarehouseReseller::KATEGORI_ONLINE) {
                    continue;
                }

                $resellerAgg[$resellerId]['total_order']++;

                foreach ($transaksi->items as $item) {
                    $qty = (int) ($item->qty ?? 0);
                    if ($qty < 1) {
                        continue;
                    }
                    $product = $item->product;
                    $unit = self::offlineLineUnitPrice($transaksi, $item);
                    $hppUnit = (float) ($product?->hpp ?? $product?->harga_pembelian ?? 0);

                    $resellerAgg[$resellerId]['pendapatan_kotor'] += ($unit * $qty);
                    $resellerAgg[$resellerId]['hpp'] += ($hppUnit * $qty);
                }
            }

            $onlineResellerTransaksis = TransaksiOnline::query()
                ->selesai()
                ->where('warehouse_id', $warehouseId)
                ->whereBetween('created_at', [$dari, $sampai])
                ->whereNotNull('warehouse_reseller_id')
                ->with(['items.product', 'warehouseReseller'])
                ->get();

            foreach ($onlineResellerTransaksis as $transaksi) {
                $resellerId = (int) ($transaksi->warehouse_reseller_id ?? 0);
                if ($resellerId < 1 || ! array_key_exists($resellerId, $resellerAgg)) {
                    continue;
                }

                if (($resellerAgg[$resellerId]['kategori'] ?? '') !== WarehouseReseller::KATEGORI_ONLINE) {
                    continue;
                }

                $resellerAgg[$resellerId]['total_order']++;

                foreach ($transaksi->items as $item) {
                    $qty = (int) ($item->qty ?? 0);
                    if ($qty < 1) {
                        continue;
                    }
                    $harga = (float) ($item->harga ?? 0);
                    $product = $item->product;
                    $hppUnit = (float) ($product?->hpp ?? $product?->harga_pembelian ?? 0);

                    $resellerAgg[$resellerId]['pendapatan_kotor'] += ($harga * $qty);
                    $resellerAgg[$resellerId]['hpp'] += ($hppUnit * $qty);
                }
            }
        }

        uasort($resellerAgg, fn (array $a, array $b) => strcmp($a['keterangan'], $b['keterangan']));

        $monthsInPeriod = self::monthsInPeriod($dari, $sampai);
        $adjustmentsByReseller = self::sumMonthlyAdjustmentsByReseller($warehouseId, $monthsInPeriod);

        $resellerRows = [];
        foreach ($resellerAgg as $resellerId => $agg) {
            $returnAmount = 0.0;
            $adj = $adjustmentsByReseller[(int) $resellerId] ?? [
                'diskon' => 0.0,
                'ongkir' => 0.0,
                'iklan_bop' => 0.0,
            ];
            $diskon = (float) $adj['diskon'];
            $ongkir = (float) $adj['ongkir'];
            $iklanBop = (float) $adj['iklan_bop'];
            $pendapatanKotor = (float) $agg['pendapatan_kotor'];
            $hppAmount = (float) $agg['hpp'];
            $pendapatanBersih = $pendapatanKotor - $returnAmount;
            $profitBeforeTax = $pendapatanBersih - $diskon - $ongkir - $hppAmount - $iklanBop;

            $resellerRows[] = [
                'warehouse_reseller_id' => (int) $resellerId,
                'kategori' => $agg['kategori'],
                'keterangan' => $agg['keterangan'],
                'total_order' => (int) $agg['total_order'],
                'pendapatan_kotor' => $pendapatanKotor,
                'return_amount' => $returnAmount,
                'pendapatan_bersih' => $pendapatanBersih,
                'diskon' => $diskon,
                'ongkir' => $ongkir,
                'hpp' => $hppAmount,
                'iklan_bop' => $iklanBop,
                'profit_before_tax' => $profitBeforeTax,
            ];
        }

        $rows = [];
        $totals = [
            'total_order' => 0,
            'pendapatan_kotor' => 0.0,
            'return_amount' => 0.0,
            'pendapatan_bersih' => 0.0,
            'diskon' => 0.0,
            'ongkir' => 0.0,
            'hpp' => 0.0,
            'iklan_bop' => 0.0,
            'profit_before_tax' => 0.0,
        ];
        $kategoriOptions = [];

        foreach ($resellerRows as $index => $row) {
            $row['no'] = $index + 1;
            $rows[] = $row;

            $kategori = (string) ($row['kategori'] ?? '');
            if ($kategori !== '' && ! in_array($kategori, $kategoriOptions, true)) {
                $kategoriOptions[] = $kategori;
            }

            $totals['total_order'] += (int) $row['total_order'];
            $totals['pendapatan_kotor'] += (float) $row['pendapatan_kotor'];
            $totals['return_amount'] += (float) $row['return_amount'];
            $totals['pendapatan_bersih'] += (float) $row['pendapatan_bersih'];
            $totals['diskon'] += (float) $row['diskon'];
            $totals['ongkir'] += (float) $row['ongkir'];
            $totals['hpp'] += (float) $row['hpp'];
            $totals['iklan_bop'] += (float) $row['iklan_bop'];
            $totals['profit_before_tax'] += (float) $row['profit_before_tax'];
        }

        sort($kategoriOptions);

        return [
            'rows' => $rows,
            'totals' => $totals,
            'kategori_options' => $kategoriOptions,
        ];
    }

    /**
     * @return array{
     *     rows: list<array{no: string, label: string, kind: string, amount: ?float, is_percent: bool}>,
     *     summary: array<string, float>
     * }
     */
    public static function labaRugiPresentation(
        Carbon $dari,
        Carbon $sampai,
        ?int $warehouseId,
        array $sortBySection = [],
    ): array {
        $channels = self::channelTotalsForPeriod($warehouseId, $dari, $sampai);
        $pendapatan = $channels['pendapatan'];
        $hpp = $channels['hpp'];

        $biayaMarketplace = self::rincianBiayaTotalsByPrefix($warehouseId, $dari, $sampai, '5.2.');
        $biayaIklan = self::rincianBiayaTotalsByPrefix($warehouseId, $dari, $sampai, '5.3.');
        $hppReturns = self::rincianBiayaTotalsForAccounts(
            $warehouseId,
            $dari,
            $sampai,
            array_column(WarehouseLabaRugiChannels::HPP_RETURNS, 'no'),
        );
        $biayaOpByAkun = self::rincianBiayaTotalsByPrefix($warehouseId, $dari, $sampai, '5.4.');
        $biayaNonOpByAkun = self::rincianBiayaTotalsByPrefix($warehouseId, $dari, $sampai, '5.5.');

        $m = self::computeMetrics($dari, $sampai, $warehouseId);
        $totalPendapatan = $m['total_pendapatan'];
        $totalHpp = $m['total_hpp'];
        $totalBiayaMarketplace = $m['total_biaya_marketplace'];
        $totalBiayaIklan = $m['total_biaya_iklan'];
        $grossProfit = $m['gross_profit'];
        $totalBiayaOperasional = $m['total_biaya_operasional'];
        $totalBiayaNonOperasional = $m['total_biaya_non_operasional'];
        $totalBiayaOperasionalSection = $m['total_biaya_operasional_section'];
        $totalBiayaAll = $m['total_biaya'];
        $netProfitBeforeZakat = $m['net_profit_before_zakat'];
        $totalModal = $m['total_modal'];
        $zakat = $m['zakat'];
        $netProfit = $m['net_profit'];
        $pctHpp = $m['pct_hpp'];
        $pctGross = $m['pct_gross'];
        $pctBiaya = $m['pct_biaya'];
        $pctNetBefore = $m['pct_net_before'];
        $pctNet = $m['pct_net'];

        $akunOperasional = $warehouseId
            ? AkunOperasional::queryForScope(null, $warehouseId)
                ->where('no_akun', 'like', '5.4.%')
                ->get(['no_akun', 'nama_akun'])
                ->sort(fn (AkunOperasional $a, AkunOperasional $b) => SpeedshopLabaRugiFinancials::compareAccountNumbers(
                    (string) $a->no_akun,
                    (string) $b->no_akun,
                ))
                ->values()
            : collect();

        $akunNonOperasional = $warehouseId
            ? AkunOperasional::queryForScope(null, $warehouseId)
                ->where('no_akun', 'like', '5.5.%')
                ->get(['no_akun', 'nama_akun'])
                ->sort(fn (AkunOperasional $a, AkunOperasional $b) => SpeedshopLabaRugiFinancials::compareAccountNumbers(
                    (string) $a->no_akun,
                    (string) $b->no_akun,
                ))
                ->values()
            : collect();

        $rows = [];
        $fmtLabel = fn (string $label): string => self::formatLabaRugiLabel($label);
        $push = function (
            string $no,
            string $label,
            string $kind,
            ?float $amount = null,
            bool $isPercent = false,
        ) use (&$rows, $fmtLabel) {
            $rows[] = [
                'no' => $no,
                'label' => $fmtLabel($label),
                'kind' => $kind,
                'amount' => $amount,
                'is_percent' => $isPercent,
            ];
        };

        $push('4.', 'Pendapatan (Omzet)', 'section');
        $push('4.1', 'Pendapatan Operasional', 'sub');
        foreach (WarehouseLabaRugiChannels::SALES as $channel) {
            $no = WarehouseLabaRugiChannels::accountNoForSalesSuffix($channel['suffix'], 'pendapatan');
            $push($no, $channel['label'], 'line', (float) ($pendapatan[$channel['key']] ?? 0));
        }
        $push('', 'Total Pendapatan', 'grand', $totalPendapatan);

        $push('5.', 'Biaya', 'section');
        $push('5.1', 'Biaya HPP Operasional (Modal)', 'sub');
        foreach (WarehouseLabaRugiChannels::SALES as $channel) {
            $no = WarehouseLabaRugiChannels::accountNoForSalesSuffix($channel['suffix'], 'hpp');
            $push($no, $channel['label'], 'line', (float) ($hpp[$channel['key']] ?? 0));
        }
        foreach (WarehouseLabaRugiChannels::HPP_RETURNS as $returnRow) {
            $push($returnRow['no'], $returnRow['label'], 'line', (float) ($hppReturns[$returnRow['no']] ?? 0));
        }
        $push('', 'Total Biaya HPP Operasional (Modal)', 'subtotal', $totalHpp);
        $push('', 'Persentase HPP Operasional dari Pendapatan', 'pct', $pctHpp, true);

        $push('5.2', 'Biaya Marketplace', 'sub');
        foreach (WarehouseLabaRugiChannels::SALES as $channel) {
            $no = WarehouseLabaRugiChannels::accountNoForSalesSuffix($channel['suffix'], 'marketplace');
            $push($no, $channel['label'], 'line', (float) ($biayaMarketplace[$no] ?? 0));
        }
        $push('', 'Total Biaya Marketplace', 'subtotal', $totalBiayaMarketplace);

        $push('5.3', 'Biaya Iklan', 'sub');
        foreach (WarehouseLabaRugiChannels::SALES as $channel) {
            $no = WarehouseLabaRugiChannels::accountNoForSalesSuffix($channel['suffix'], 'iklan');
            $push($no, $channel['label'], 'line', (float) ($biayaIklan[$no] ?? 0));
        }
        $push('', 'Total Biaya Iklan', 'subtotal', $totalBiayaIklan);

        $push('', 'Gross Profit', 'grand', $grossProfit);
        $push('', 'Persentase Gross Profit dari Pendapatan', 'pct', $pctGross, true);

        $push('5.4', 'Biaya Operasional', 'sub');
        self::appendBiayaOperasionalRows($push, $akunOperasional, $biayaOpByAkun);

        $push('5.5', 'Biaya Non Operasional', 'sub');
        foreach ($akunNonOperasional as $akun) {
            $no = (string) $akun->no_akun;
            $push($no, (string) $akun->nama_akun, 'line', (float) ($biayaNonOpByAkun[$no] ?? 0));
        }

        $push('', 'Total Biaya Operasional', 'subtotal', $totalBiayaOperasionalSection);
        $push('', 'Total Biaya', 'grand', $totalBiayaAll);
        $push('', 'Persentase Biaya dari Pendapatan', 'pct', $pctBiaya, true);

        $push('', 'Total Nett Profit Sebelum Zakat', 'grand', $netProfitBeforeZakat);
        $push('', 'Persentase Nett Profit dari Pendapatan', 'pct', $pctNetBefore, true);

        $push('', 'Keterangan', 'ket');
        $push('', 'Total Modal', 'line', $totalModal);
        $push('', 'Zakat 2,5%', 'line', $zakat);
        $push('', 'Nett Profit', 'footer', $netProfit);
        $push('', 'Persentase Nett Profit dari Pendapatan', 'pct', $pctNet, true);

        return [
            'rows' => self::sortLabaRugiRows($rows, $sortBySection),
            'summary' => [
                'total_pendapatan' => $totalPendapatan,
                'total_biaya' => $totalBiayaAll,
                'gross_profit' => $grossProfit,
                'nett_profit' => $netProfit,
                'pct_gross' => $pctGross,
            ],
        ];
    }

    public static function formatLabaRugiLabel(string $label): string
    {
        $label = trim($label);
        if ($label === '') {
            return '';
        }

        return mb_convert_case(mb_strtolower($label), MB_CASE_TITLE, 'UTF-8');
    }

    /** Sub-bagian laba rugi yang baris rinciannya bisa diurutkan berdasarkan nilai. */
    private const LABARUGI_SORTABLE_SUB_SECTIONS = [
        '4.1' => true,
        '5.1' => true,
        '5.2' => true,
        '5.3' => true,
        '5.4' => true,
    ];

    /** @return list<string> */
    public static function labaRugiSortableSubSections(): array
    {
        return array_keys(self::LABARUGI_SORTABLE_SUB_SECTIONS);
    }

    public static function sortQueryParamForSection(string $sectionNo): string
    {
        return 'sort_'.str_replace('.', '_', $sectionNo);
    }

    /**
     * @return array<string, 'asc'|'desc'>
     */
    public static function parseSectionSortFromRequest(?\Illuminate\Http\Request $request = null): array
    {
        $request = $request ?? request();
        $sortBySection = [];

        foreach (self::labaRugiSortableSubSections() as $sectionNo) {
            $value = $request->query(self::sortQueryParamForSection($sectionNo));
            if ($value === 'asc' || $value === 'desc') {
                $sortBySection[$sectionNo] = $value;
            }
        }

        return $sortBySection;
    }

    /**
     * @param  array<string, mixed>  $baseQuery
     * @param  array<string, 'asc'|'desc'>  $currentSort
     * @return array<string, mixed>
     */
    public static function buildSectionSortQuery(
        array $baseQuery,
        string $targetSection,
        string $direction,
        array $currentSort,
    ): array {
        $query = $baseQuery;

        foreach (self::labaRugiSortableSubSections() as $sectionNo) {
            $param = self::sortQueryParamForSection($sectionNo);
            unset($query[$param]);

            if ($sectionNo === $targetSection) {
                if ($direction === 'asc' || $direction === 'desc') {
                    $query[$param] = $direction;
                }

                continue;
            }

            $existing = $currentSort[$sectionNo] ?? null;
            if ($existing === 'asc' || $existing === 'desc') {
                $query[$param] = $existing;
            }
        }

        return $query;
    }

    /**
     * Urutkan baris rincian (kind=line) di dalam sub 4.1, 5.1, 5.2, 5.3, dan 5.4.
     *
     * @param  list<array{no: string, label: string, kind: string, amount: ?float, is_percent: bool}>  $rows
     * @param  array<string, 'asc'|'desc'>  $sortBySection
     * @return list<array{no: string, label: string, kind: string, amount: ?float, is_percent: bool}>
     */
    public static function sortLabaRugiRows(array $rows, array $sortBySection = []): array
    {
        if ($sortBySection === []) {
            return $rows;
        }

        $result = [];
        $count = count($rows);
        $index = 0;

        while ($index < $count) {
            $row = $rows[$index];
            $subNo = (string) ($row['no'] ?? '');

            if (($row['kind'] ?? '') === 'sub' && isset(self::LABARUGI_SORTABLE_SUB_SECTIONS[$subNo])) {
                $result[] = $row;
                $index++;

                $lines = [];
                while ($index < $count && ($rows[$index]['kind'] ?? '') === 'line') {
                    $lines[] = $rows[$index];
                    $index++;
                }

                $direction = $sortBySection[$subNo] ?? null;
                if ($direction === 'asc' || $direction === 'desc') {
                    $lines = $subNo === '5.4'
                        ? self::sortOperasionalLineBlocks($lines, $direction)
                        : self::sortLineRowsByAmount($lines, $direction);
                }

                foreach ($lines as $lineRow) {
                    $result[] = $lineRow;
                }

                continue;
            }

            $result[] = $row;
            $index++;
        }

        return $result;
    }

    /**
     * @param  list<array{no: string, label: string, kind: string, amount: ?float, is_percent: bool}>  $lines
     * @return list<array{no: string, label: string, kind: string, amount: ?float, is_percent: bool}>
     */
    private static function sortLineRowsByAmount(array $lines, string $urutNilai): array
    {
        usort($lines, function (array $a, array $b) use ($urutNilai): int {
            $cmp = self::compareLabaRugiAmount(
                (float) ($a['amount'] ?? 0),
                (float) ($b['amount'] ?? 0),
                $urutNilai,
            );
            if ($cmp !== 0) {
                return $cmp;
            }

            return SpeedshopLabaRugiFinancials::compareAccountNumbers(
                (string) ($a['no'] ?? ''),
                (string) ($b['no'] ?? ''),
            );
        });

        return $lines;
    }

    /**
     * Kelompokkan parent + sub akun 5.4.x, urutkan blok berdasarkan nilai parent.
     *
     * @param  list<array{no: string, label: string, kind: string, amount: ?float, is_percent: bool}>  $lines
     * @return list<array{no: string, label: string, kind: string, amount: ?float, is_percent: bool}>
     */
    private static function sortOperasionalLineBlocks(array $lines, string $urutNilai): array
    {
        $blocks = [];
        $current = null;

        foreach ($lines as $row) {
            $no = (string) ($row['no'] ?? '');
            if ($no !== '' && WarehouseLabaRugiChannels::isOperasionalSubAccount($no)) {
                if ($current !== null) {
                    $current['rows'][] = $row;
                }

                continue;
            }

            if ($current !== null) {
                $blocks[] = $current;
            }

            $current = [
                'sort_amount' => (float) ($row['amount'] ?? 0),
                'rows' => [$row],
            ];
        }

        if ($current !== null) {
            $blocks[] = $current;
        }

        usort($blocks, function (array $a, array $b) use ($urutNilai): int {
            $cmp = self::compareLabaRugiAmount(
                (float) ($a['sort_amount'] ?? 0),
                (float) ($b['sort_amount'] ?? 0),
                $urutNilai,
            );
            if ($cmp !== 0) {
                return $cmp;
            }

            $aNo = (string) ($a['rows'][0]['no'] ?? '');
            $bNo = (string) ($b['rows'][0]['no'] ?? '');

            return SpeedshopLabaRugiFinancials::compareAccountNumbers($aNo, $bNo);
        });

        $sorted = [];
        foreach ($blocks as $block) {
            foreach ($block['rows'] as $row) {
                $sorted[] = $row;
            }
        }

        return $sorted;
    }

    private static function compareLabaRugiAmount(float $a, float $b, string $urutNilai): int
    {
        if ($a === $b) {
            return 0;
        }

        return $urutNilai === 'asc' ? ($a <=> $b) : ($b <=> $a);
    }

    /**
     * @param  callable(string, string, string, ?float, bool): void  $push
     * @param  \Illuminate\Support\Collection<int, AkunOperasional>  $akunOperasional
     * @param  array<string, float>  $biayaOpByAkun
     */
    private static function appendBiayaOperasionalRows(
        callable $push,
        \Illuminate\Support\Collection $akunOperasional,
        array $biayaOpByAkun,
    ): void {
        $parentsWithSubs = WarehouseLabaRugiChannels::operasionalParentNosWithSubs();

        foreach ($akunOperasional as $akun) {
            $no = (string) $akun->no_akun;
            if (WarehouseLabaRugiChannels::isOperasionalSubAccount($no)) {
                continue;
            }

            if (in_array($no, $parentsWithSubs, true)) {
                $subs = $akunOperasional->filter(
                    fn (AkunOperasional $row) => str_starts_with((string) $row->no_akun, $no.'.')
                );

                $parentTotal = (float) ($biayaOpByAkun[$no] ?? 0);
                foreach ($subs as $sub) {
                    $parentTotal += (float) ($biayaOpByAkun[(string) $sub->no_akun] ?? 0);
                }

                $push($no, (string) $akun->nama_akun, 'line', $parentTotal);

                foreach ($subs as $sub) {
                    $subNo = (string) $sub->no_akun;
                    $push($subNo, (string) $sub->nama_akun, 'line', (float) ($biayaOpByAkun[$subNo] ?? 0));
                }

                continue;
            }

            $push($no, (string) $akun->nama_akun, 'line', (float) ($biayaOpByAkun[$no] ?? 0));
        }
    }

    /**
     * @param  array<string, float>  $pendapatan
     * @param  array<string, float>  $hpp
     */
    private static function accumulateOnlineTotals(
        int $warehouseId,
        Carbon $dari,
        Carbon $sampai,
        array &$pendapatan,
        array &$hpp,
    ): void {
        $channelExpr = WarehouseLabaRugiChannels::sqlOnlineChannelKeyExpr('warehouse_resellers.nama');
        $hppExpr = 'COALESCE(products.hpp, products.harga_pembelian, 0)';

        $rows = DB::table('transaksi_online_items')
            ->join('transaksi_online', 'transaksi_online.id', '=', 'transaksi_online_items.transaksi_online_id')
            ->leftJoin('warehouse_resellers', 'warehouse_resellers.id', '=', 'transaksi_online.warehouse_reseller_id')
            ->leftJoin('products', 'products.id', '=', 'transaksi_online_items.product_id')
            ->where('transaksi_online.warehouse_id', $warehouseId)
            ->where('transaksi_online.status', TransaksiOnline::STATUS_SELESAI)
            ->whereBetween('transaksi_online.created_at', [$dari->copy()->startOfDay(), $sampai->copy()->endOfDay()])
            ->where('transaksi_online_items.qty', '>', 0)
            ->selectRaw("{$channelExpr} as channel_key")
            ->selectRaw('COALESCE(SUM(transaksi_online_items.qty * transaksi_online_items.harga), 0) as pendapatan')
            ->selectRaw("COALESCE(SUM(transaksi_online_items.qty * ({$hppExpr})), 0) as hpp")
            ->groupByRaw($channelExpr)
            ->get();

        foreach ($rows as $row) {
            $key = (string) ($row->channel_key ?? 'reseller_online');
            if (! array_key_exists($key, $pendapatan)) {
                $key = 'reseller_online';
            }
            $pendapatan[$key] = ($pendapatan[$key] ?? 0) + (float) ($row->pendapatan ?? 0);
            $hpp[$key] = ($hpp[$key] ?? 0) + (float) ($row->hpp ?? 0);
        }
    }

    /**
     * @param  array<string, float>  $pendapatan
     * @param  array<string, float>  $hpp
     */
    private static function accumulateOfflineTotals(
        int $warehouseId,
        Carbon $dari,
        Carbon $sampai,
        array &$pendapatan,
        array &$hpp,
    ): void {
        $priceExpr = self::sqlOfflineResellerLinePriceExpr();
        $hppExpr = 'COALESCE(products.hpp, products.harga_pembelian, 0)';
        $channelExpr = "CASE WHEN UPPER(TRIM(COALESCE(warehouse_resellers.kategori, ''))) = '".WarehouseReseller::KATEGORI_CANVASSING."' THEN 'reseller_canvassing' ELSE 'reseller_offline' END";

        $rows = DB::table('transaksi_offline_items')
            ->join('transaksi_offline', 'transaksi_offline.id', '=', 'transaksi_offline_items.transaksi_offline_id')
            ->leftJoin('warehouse_resellers', 'warehouse_resellers.id', '=', 'transaksi_offline.warehouse_reseller_id')
            ->leftJoin('products', 'products.id', '=', 'transaksi_offline_items.product_id')
            ->where('transaksi_offline.warehouse_id', $warehouseId)
            ->whereBetween('transaksi_offline.created_at', [$dari->copy()->startOfDay(), $sampai->copy()->endOfDay()])
            ->whereNull('transaksi_offline.manager_rejection_at')
            ->whereNull('transaksi_offline.warehouse_po_review_rejected_at')
            ->whereIn('transaksi_offline.tujuan', ['reseller', 'umum'])
            ->whereNotNull('transaksi_offline.petugas_id')
            ->where(function ($q) {
                $q->whereNull('transaksi_offline.warehouse_reseller_id')
                    ->orWhere('warehouse_resellers.kategori', '!=', WarehouseReseller::KATEGORI_ONLINE);
            })
            ->where('transaksi_offline_items.qty', '>', 0)
            ->selectRaw("{$channelExpr} as channel_key")
            ->selectRaw("COALESCE(SUM(transaksi_offline_items.qty * ({$priceExpr})), 0) as pendapatan")
            ->selectRaw("COALESCE(SUM(transaksi_offline_items.qty * ({$hppExpr})), 0) as hpp")
            ->groupByRaw($channelExpr)
            ->get();

        foreach ($rows as $row) {
            $key = (string) ($row->channel_key ?? 'reseller_offline');
            if (! array_key_exists($key, $pendapatan)) {
                $key = 'reseller_offline';
            }
            $pendapatan[$key] = ($pendapatan[$key] ?? 0) + (float) ($row->pendapatan ?? 0);
            $hpp[$key] = ($hpp[$key] ?? 0) + (float) ($row->hpp ?? 0);
        }
    }

    /**
     * Pendapatan & HPP penjualan part gudang → speedshop via PO (menu PO Speedshop).
     * Bukan omset bengkel speedshop (WO) — itu laporan speedshop sendiri.
     *
     * @param  array<string, float>  $pendapatan
     * @param  array<string, float>  $hpp
     */
    private static function accumulateWarehouseToSpeedshopTotals(
        int $warehouseId,
        Carbon $dari,
        Carbon $sampai,
        array &$pendapatan,
        array &$hpp,
    ): void {
        $priceExpr = 'COALESCE(NULLIF(transaksi_offline_items.harga, 0), CASE'
            .' WHEN COALESCE(products.harga_jual_speedshop, 0) > 0 THEN products.harga_jual_speedshop'
            .' WHEN COALESCE(products.harga_jual, 0) > 0 THEN products.harga_jual'
            .' ELSE COALESCE(products.hpp, products.harga_pembelian, 0) END)';
        $hppExpr = 'COALESCE(products.hpp, products.harga_pembelian, 0)';

        $row = DB::table('transaksi_offline_items')
            ->join('transaksi_offline', 'transaksi_offline.id', '=', 'transaksi_offline_items.transaksi_offline_id')
            ->leftJoin('products', 'products.id', '=', 'transaksi_offline_items.product_id')
            ->where('transaksi_offline.warehouse_id', $warehouseId)
            ->where('transaksi_offline.tujuan', 'speedshop')
            ->whereNotNull('transaksi_offline.speedshop_purchase_order_id')
            ->whereBetween('transaksi_offline.created_at', [$dari->copy()->startOfDay(), $sampai->copy()->endOfDay()])
            ->whereNull('transaksi_offline.manager_rejection_at')
            ->whereNull('transaksi_offline.warehouse_po_review_rejected_at')
            ->whereNotNull('transaksi_offline.petugas_id')
            ->where('transaksi_offline_items.qty', '>', 0)
            ->selectRaw("COALESCE(SUM(transaksi_offline_items.qty * ({$priceExpr})), 0) as pendapatan")
            ->selectRaw("COALESCE(SUM(transaksi_offline_items.qty * ({$hppExpr})), 0) as hpp")
            ->first();

        if ($row === null) {
            return;
        }

        $pendapatan['speedshop'] = ($pendapatan['speedshop'] ?? 0) + (float) ($row->pendapatan ?? 0);
        $hpp['speedshop'] = ($hpp['speedshop'] ?? 0) + (float) ($row->hpp ?? 0);
    }

    private static function sqlOfflineResellerLinePriceExpr(): string
    {
        return 'COALESCE(NULLIF(transaksi_offline_items.harga, 0), CASE'
            ." WHEN UPPER(TRIM(COALESCE(warehouse_resellers.kategori, ''))) = '".WarehouseReseller::KATEGORI_CANVASSING."'"
            .' THEN COALESCE(products.harga_jual_reseler, 0)'
            .' WHEN COALESCE(products.harga_jual, 0) > 0 THEN products.harga_jual'
            .' ELSE COALESCE(products.harga_eceran_terendah, 0) END)';
    }

    private static function resolveOfflineChannelKey(TransaksiOffline $transaksi): string
    {
        if ($transaksi->tujuan === 'speedshop') {
            return 'speedshop';
        }

        $kategori = mb_strtoupper(trim((string) ($transaksi->warehouseReseller?->kategori ?? '')));

        return match ($kategori) {
            WarehouseReseller::KATEGORI_CANVASSING => 'reseller_canvassing',
            WarehouseReseller::KATEGORI_OFFLINE => 'reseller_offline',
            default => 'reseller_offline',
        };
    }

    private static function offlineUnitPrice(?object $product): float
    {
        if ($product === null) {
            return 0.0;
        }

        $jual = (int) ($product->harga_jual ?? 0);

        return $jual > 0 ? (float) $jual : (float) max(0, (int) ($product->harga_eceran_terendah ?? 0));
    }

    private static function offlineLineUnitPrice(TransaksiOffline $transaksi, object $item): float
    {
        $stored = (float) ($item->harga ?? 0);
        if ($stored > 0) {
            return $stored;
        }

        $product = $item->product ?? null;
        if (! $product instanceof Product) {
            return 0.0;
        }

        if ($transaksi->tujuan === 'speedshop' || $transaksi->speedshop_purchase_order_id !== null) {
            return (float) SpeedshopPoTagihanService::productHargaPoRp($product);
        }

        if ($transaksi->tujuan === 'reseller' && $transaksi->isCanvassingResellerTransaction()) {
            return (float) max(0, (int) ($product->harga_jual_reseler ?? 0));
        }

        return self::offlineUnitPrice($product);
    }

    /**
     * @return Builder<TransaksiOffline>
     */
    private static function warehouseToSpeedshopCountableQuery(int $warehouseId, Carbon $dari, Carbon $sampai): Builder
    {
        return TransaksiOffline::query()
            ->where('warehouse_id', $warehouseId)
            ->where('tujuan', 'speedshop')
            ->whereNotNull('speedshop_purchase_order_id')
            ->whereBetween('created_at', [$dari, $sampai])
            ->whereNull('manager_rejection_at')
            ->whereNull('warehouse_po_review_rejected_at')
            ->whereNotNull('petugas_id');
    }

    /**
     * @return Builder<TransaksiOffline>
     */
    private static function offlineCountableQuery(int $warehouseId, Carbon $dari, Carbon $sampai): Builder
    {
        return TransaksiOffline::query()
            ->where('warehouse_id', $warehouseId)
            ->whereBetween('created_at', [$dari, $sampai])
            ->whereNull('manager_rejection_at')
            ->whereNull('warehouse_po_review_rejected_at')
            ->whereIn('tujuan', ['reseller', 'umum'])
            ->whereNotNull('petugas_id')
            ->where(function (Builder $q) {
                $q->whereNull('warehouse_reseller_id')
                    ->orWhereHas('warehouseReseller', function (Builder $resellerQuery) {
                        $resellerQuery->where('kategori', '!=', WarehouseReseller::KATEGORI_ONLINE);
                    });
            });
    }

    /**
     * @param  list<string>  $noAkunList
     * @return array<string, float>
     */
    public static function rincianBiayaTotalsForAccounts(
        ?int $warehouseId,
        Carbon $dari,
        Carbon $sampai,
        array $noAkunList,
    ): array {
        if ($noAkunList === []) {
            return [];
        }

        $query = self::rincianBiayaApprovedQuery($warehouseId, $dari, $sampai);
        if ($query === null) {
            return [];
        }

        return $query
            ->whereIn('no_akun', $noAkunList)
            ->selectRaw('no_akun, SUM(nominal) as total')
            ->groupBy('no_akun')
            ->pluck('total', 'no_akun')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * @return array<string, float>
     */
    public static function rincianBiayaTotalsByPrefix(
        ?int $warehouseId,
        Carbon $dari,
        Carbon $sampai,
        string $noAkunPrefix,
    ): array {
        $query = self::rincianBiayaApprovedQuery($warehouseId, $dari, $sampai);
        if ($query === null) {
            return [];
        }

        return $query
            ->where('no_akun', 'like', $noAkunPrefix.'%')
            ->selectRaw('no_akun, SUM(nominal) as total')
            ->groupBy('no_akun')
            ->pluck('total', 'no_akun')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<SpeedshopRincianBiayaEntry>|null
     */
    private static function rincianBiayaApprovedQuery(
        ?int $warehouseId,
        Carbon $dari,
        Carbon $sampai,
    ) {
        if (! $warehouseId || ! Schema::hasTable('speedshop_rincian_biaya_entries')) {
            return null;
        }

        return SpeedshopRincianBiayaEntry::query()
            ->where('warehouse_id', $warehouseId)
            ->whereNull('speedshop_id')
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->where(function ($q) {
                $q->whereNull('approval_status')
                    ->orWhere('approval_status', SpeedshopRincianBiayaEntry::STATUS_APPROVED);
            });
    }

    /**
     * @param  array<string, float>  $pendapatan
     * @return array{marketplace: float, reseller: float, speedshop: float}
     */
    public static function aggregateSalesGroups(array $pendapatan): array
    {
        $marketplace = 0.0;
        $reseller = 0.0;
        $speedshop = 0.0;

        foreach (WarehouseLabaRugiChannels::SALES as $channel) {
            $key = $channel['key'];
            $v = (float) ($pendapatan[$key] ?? 0);
            if (in_array($key, WarehouseLabaRugiChannels::MARKETPLACE_KEYS, true)) {
                $marketplace += $v;
            } elseif ($key === 'speedshop') {
                $speedshop += $v;
            } else {
                $reseller += $v;
            }
        }

        return [
            'marketplace' => $marketplace,
            'reseller' => $reseller,
            'speedshop' => $speedshop,
        ];
    }

    /**
     * @return array{
     *   chartLabels: list<string>,
     *   chartMarketplace: list<float>,
     *   chartReseller: list<float>,
     *   chartSpeedshop: list<float>,
     *   chartPendapatanKotor: list<float>,
     *   chartTotalBiaya: list<float>,
     *   chartGrossProfit: list<float>
     * }
     */
    public static function accountingDashboardChartSeries(
        Carbon $dari,
        Carbon $sampai,
        ?int $warehouseId,
        string $mode,
        string $selectedDate,
        string $selectedMonth,
        int $selectedYear,
    ): array {
        $dailyMaps = self::accountingDashboardDailyMaps($warehouseId, $dari, $sampai);

        $rollup = function (Carbon $start, Carbon $end) use ($dailyMaps): array {
            $channelKeys = array_column(WarehouseLabaRugiChannels::SALES, 'key');
            $pendapatan = array_fill_keys($channelKeys, 0.0);
            $biayaMarketplace = 0.0;
            $biayaIklan = 0.0;
            $biayaOp = 0.0;
            $biayaNonOp = 0.0;
            $hppReturns = 0.0;
            $hppByChannel = array_fill_keys($channelKeys, 0.0);

            $cursor = $start->copy()->startOfDay();
            $endDay = $end->copy()->startOfDay();
            while ($cursor->lte($endDay)) {
                $day = $cursor->toDateString();
                $entry = $dailyMaps[$day] ?? null;
                if ($entry !== null) {
                    foreach ($channelKeys as $key) {
                        $pendapatan[$key] += (float) ($entry['pendapatan'][$key] ?? 0);
                        $hppByChannel[$key] += (float) ($entry['hpp'][$key] ?? 0);
                    }
                    $biayaMarketplace += (float) $entry['biaya_marketplace'];
                    $biayaIklan += (float) $entry['biaya_iklan'];
                    $biayaOp += (float) $entry['biaya_op'];
                    $biayaNonOp += (float) $entry['biaya_non_op'];
                    $hppReturns += (float) $entry['hpp_returns'];
                }
                $cursor->addDay();
            }

            $groups = self::aggregateSalesGroups($pendapatan);
            $totalPendapatan = array_sum($pendapatan);
            $totalHpp = array_sum($hppByChannel) + $hppReturns;
            $grossProfit = $totalPendapatan - $totalHpp - $biayaMarketplace - $biayaIklan;
            $totalBiaya = $totalHpp + $biayaMarketplace + $biayaIklan + $biayaOp + $biayaNonOp;

            return [
                'marketplace' => $groups['marketplace'],
                'reseller' => $groups['reseller'],
                'speedshop' => $groups['speedshop'],
                'pendapatan' => $totalPendapatan,
                'total_biaya' => $totalBiaya,
                'gross_profit' => $grossProfit,
                'hpp' => $totalHpp,
                'biaya_marketplace' => $biayaMarketplace,
                'biaya_iklan' => $biayaIklan,
                'biaya_operasional' => $biayaOp,
                'biaya_non_operasional' => $biayaNonOp,
            ];
        };

        if ($mode === 'tahunan') {
            $chartLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            $chartMarketplace = [];
            $chartReseller = [];
            $chartSpeedshop = [];
            $chartPendapatanKotor = [];
            $chartTotalBiaya = [];
            $chartGrossProfit = [];
            $chartHpp = [];
            $chartBiayaMarketplace = [];
            $chartBiayaIklan = [];
            $chartBiayaOperasional = [];
            $chartBiayaNonOperasional = [];

            for ($month = 1; $month <= 12; $month++) {
                $start = Carbon::create($selectedYear, $month, 1)->startOfMonth();
                $end = $start->copy()->endOfMonth();
                $b = $rollup($start, $end);
                $chartMarketplace[] = $b['marketplace'];
                $chartReseller[] = $b['reseller'];
                $chartSpeedshop[] = $b['speedshop'];
                $chartPendapatanKotor[] = $b['pendapatan'];
                $chartTotalBiaya[] = $b['total_biaya'];
                $chartGrossProfit[] = $b['gross_profit'];
                $chartHpp[] = $b['hpp'];
                $chartBiayaMarketplace[] = $b['biaya_marketplace'];
                $chartBiayaIklan[] = $b['biaya_iklan'];
                $chartBiayaOperasional[] = $b['biaya_operasional'];
                $chartBiayaNonOperasional[] = $b['biaya_non_operasional'];
            }

            return compact(
                'chartLabels',
                'chartMarketplace',
                'chartReseller',
                'chartSpeedshop',
                'chartPendapatanKotor',
                'chartTotalBiaya',
                'chartGrossProfit',
                'chartHpp',
                'chartBiayaMarketplace',
                'chartBiayaIklan',
                'chartBiayaOperasional',
                'chartBiayaNonOperasional',
            );
        }

        if ($mode === 'bulanan' || $mode === 'range') {
            $chartLabels = [];
            $chartMarketplace = [];
            $chartReseller = [];
            $chartSpeedshop = [];
            $chartPendapatanKotor = [];
            $chartTotalBiaya = [];
            $chartGrossProfit = [];
            $chartHpp = [];
            $chartBiayaMarketplace = [];
            $chartBiayaIklan = [];
            $chartBiayaOperasional = [];
            $chartBiayaNonOperasional = [];

            $cursor = $dari->copy()->startOfDay();
            $end = $sampai->copy()->startOfDay();
            while ($cursor->lte($end)) {
                $b = $rollup($cursor->copy()->startOfDay(), $cursor->copy()->endOfDay());
                $chartLabels[] = $cursor->format('d/m');
                $chartMarketplace[] = $b['marketplace'];
                $chartReseller[] = $b['reseller'];
                $chartSpeedshop[] = $b['speedshop'];
                $chartPendapatanKotor[] = $b['pendapatan'];
                $chartTotalBiaya[] = $b['total_biaya'];
                $chartGrossProfit[] = $b['gross_profit'];
                $chartHpp[] = $b['hpp'];
                $chartBiayaMarketplace[] = $b['biaya_marketplace'];
                $chartBiayaIklan[] = $b['biaya_iklan'];
                $chartBiayaOperasional[] = $b['biaya_operasional'];
                $chartBiayaNonOperasional[] = $b['biaya_non_operasional'];
                $cursor->addDay();
            }

            return compact(
                'chartLabels',
                'chartMarketplace',
                'chartReseller',
                'chartSpeedshop',
                'chartPendapatanKotor',
                'chartTotalBiaya',
                'chartGrossProfit',
                'chartHpp',
                'chartBiayaMarketplace',
                'chartBiayaIklan',
                'chartBiayaOperasional',
                'chartBiayaNonOperasional',
            );
        }

        try {
            $day = Carbon::parse($selectedDate);
        } catch (\Throwable $e) {
            $day = now();
        }
        $b = $rollup($day->copy()->startOfDay(), $day->copy()->endOfDay());

        return [
            'chartLabels' => [$day->format('d/m')],
            'chartMarketplace' => [$b['marketplace']],
            'chartReseller' => [$b['reseller']],
            'chartSpeedshop' => [$b['speedshop']],
            'chartPendapatanKotor' => [$b['pendapatan']],
            'chartTotalBiaya' => [$b['total_biaya']],
            'chartGrossProfit' => [$b['gross_profit']],
            'chartHpp' => [$b['hpp']],
            'chartBiayaMarketplace' => [$b['biaya_marketplace']],
            'chartBiayaIklan' => [$b['biaya_iklan']],
            'chartBiayaOperasional' => [$b['biaya_operasional']],
            'chartBiayaNonOperasional' => [$b['biaya_non_operasional']],
        ];
    }

    /**
     * @return array<string, array{
     *     pendapatan: array<string, float>,
     *     hpp: array<string, float>,
     *     biaya_marketplace: float,
     *     biaya_iklan: float,
     *     biaya_op: float,
     *     biaya_non_op: float,
     *     hpp_returns: float
     * }>
     */
    private static function accountingDashboardDailyMaps(?int $warehouseId, Carbon $dari, Carbon $sampai): array
    {
        $channelKeys = array_column(WarehouseLabaRugiChannels::SALES, 'key');
        $emptyChannels = array_fill_keys($channelKeys, 0.0);
        $maps = [];

        $cursor = $dari->copy()->startOfDay();
        $end = $sampai->copy()->startOfDay();
        while ($cursor->lte($end)) {
            $maps[$cursor->toDateString()] = [
                'pendapatan' => $emptyChannels,
                'hpp' => $emptyChannels,
                'biaya_marketplace' => 0.0,
                'biaya_iklan' => 0.0,
                'biaya_op' => 0.0,
                'biaya_non_op' => 0.0,
                'hpp_returns' => 0.0,
            ];
            $cursor->addDay();
        }

        if (! $warehouseId || $maps === []) {
            return $maps;
        }

        $rangeStart = $dari->copy()->startOfDay();
        $rangeEnd = $sampai->copy()->endOfDay();
        $driver = DB::connection()->getDriverName();
        $dayExprOnline = $driver === 'sqlite'
            ? 'date(transaksi_online.created_at)'
            : 'DATE(transaksi_online.created_at)';
        $dayExprOffline = $driver === 'sqlite'
            ? 'date(transaksi_offline.created_at)'
            : 'DATE(transaksi_offline.created_at)';

        $channelExpr = WarehouseLabaRugiChannels::sqlOnlineChannelKeyExpr('warehouse_resellers.nama');
        $hppExpr = 'COALESCE(products.hpp, products.harga_pembelian, 0)';

        $onlineRows = DB::table('transaksi_online_items')
            ->join('transaksi_online', 'transaksi_online.id', '=', 'transaksi_online_items.transaksi_online_id')
            ->leftJoin('warehouse_resellers', 'warehouse_resellers.id', '=', 'transaksi_online.warehouse_reseller_id')
            ->leftJoin('products', 'products.id', '=', 'transaksi_online_items.product_id')
            ->where('transaksi_online.warehouse_id', $warehouseId)
            ->where('transaksi_online.status', TransaksiOnline::STATUS_SELESAI)
            ->whereBetween('transaksi_online.created_at', [$rangeStart, $rangeEnd])
            ->where('transaksi_online_items.qty', '>', 0)
            ->selectRaw("{$dayExprOnline} as day_key")
            ->selectRaw("{$channelExpr} as channel_key")
            ->selectRaw('COALESCE(SUM(transaksi_online_items.qty * transaksi_online_items.harga), 0) as pendapatan')
            ->selectRaw("COALESCE(SUM(transaksi_online_items.qty * ({$hppExpr})), 0) as hpp")
            ->groupByRaw("{$dayExprOnline}, {$channelExpr}")
            ->get();

        foreach ($onlineRows as $row) {
            $day = (string) $row->day_key;
            if (! isset($maps[$day])) {
                continue;
            }
            $key = (string) ($row->channel_key ?? 'reseller_online');
            if (! array_key_exists($key, $maps[$day]['pendapatan'])) {
                $key = 'reseller_online';
            }
            $maps[$day]['pendapatan'][$key] = ($maps[$day]['pendapatan'][$key] ?? 0) + (float) ($row->pendapatan ?? 0);
            $maps[$day]['hpp'][$key] = ($maps[$day]['hpp'][$key] ?? 0) + (float) ($row->hpp ?? 0);
        }

        $offlinePriceExpr = self::sqlOfflineResellerLinePriceExpr();
        $offlineChannelExpr = "CASE WHEN UPPER(TRIM(COALESCE(warehouse_resellers.kategori, ''))) = '".WarehouseReseller::KATEGORI_CANVASSING."' THEN 'reseller_canvassing' ELSE 'reseller_offline' END";

        $offlineRows = DB::table('transaksi_offline_items')
            ->join('transaksi_offline', 'transaksi_offline.id', '=', 'transaksi_offline_items.transaksi_offline_id')
            ->leftJoin('warehouse_resellers', 'warehouse_resellers.id', '=', 'transaksi_offline.warehouse_reseller_id')
            ->leftJoin('products', 'products.id', '=', 'transaksi_offline_items.product_id')
            ->where('transaksi_offline.warehouse_id', $warehouseId)
            ->whereBetween('transaksi_offline.created_at', [$rangeStart, $rangeEnd])
            ->whereNull('transaksi_offline.manager_rejection_at')
            ->whereNull('transaksi_offline.warehouse_po_review_rejected_at')
            ->whereIn('transaksi_offline.tujuan', ['reseller', 'umum'])
            ->whereNotNull('transaksi_offline.petugas_id')
            ->where(function ($q) {
                $q->whereNull('transaksi_offline.warehouse_reseller_id')
                    ->orWhere('warehouse_resellers.kategori', '!=', WarehouseReseller::KATEGORI_ONLINE);
            })
            ->where('transaksi_offline_items.qty', '>', 0)
            ->selectRaw("{$dayExprOffline} as day_key")
            ->selectRaw("{$offlineChannelExpr} as channel_key")
            ->selectRaw("COALESCE(SUM(transaksi_offline_items.qty * ({$offlinePriceExpr})), 0) as pendapatan")
            ->selectRaw("COALESCE(SUM(transaksi_offline_items.qty * ({$hppExpr})), 0) as hpp")
            ->groupByRaw("{$dayExprOffline}, {$offlineChannelExpr}")
            ->get();

        foreach ($offlineRows as $row) {
            $day = (string) $row->day_key;
            if (! isset($maps[$day])) {
                continue;
            }
            $key = (string) ($row->channel_key ?? 'reseller_offline');
            if (! array_key_exists($key, $maps[$day]['pendapatan'])) {
                $key = 'reseller_offline';
            }
            $maps[$day]['pendapatan'][$key] = ($maps[$day]['pendapatan'][$key] ?? 0) + (float) ($row->pendapatan ?? 0);
            $maps[$day]['hpp'][$key] = ($maps[$day]['hpp'][$key] ?? 0) + (float) ($row->hpp ?? 0);
        }

        self::mergeAccountingWarehouseToSpeedshopDailyMaps($warehouseId, $dari, $sampai, $maps);
        self::mergeAccountingRincianBiayaDailyMaps($warehouseId, $dari, $sampai, $maps);

        return $maps;
    }

    /**
     * @param  array<string, array{
     *     pendapatan: array<string, float>,
     *     hpp: array<string, float>,
     *     biaya_marketplace: float,
     *     biaya_iklan: float,
     *     biaya_op: float,
     *     biaya_non_op: float,
     *     hpp_returns: float
     * }>  $maps
     */
    private static function mergeAccountingWarehouseToSpeedshopDailyMaps(
        int $warehouseId,
        Carbon $dari,
        Carbon $sampai,
        array &$maps,
    ): void {
        $driver = DB::connection()->getDriverName();
        $dayExpr = $driver === 'sqlite'
            ? 'date(transaksi_offline.created_at)'
            : 'DATE(transaksi_offline.created_at)';
        $priceExpr = 'COALESCE(NULLIF(transaksi_offline_items.harga, 0), CASE'
            .' WHEN COALESCE(products.harga_jual_speedshop, 0) > 0 THEN products.harga_jual_speedshop'
            .' WHEN COALESCE(products.harga_jual, 0) > 0 THEN products.harga_jual'
            .' ELSE COALESCE(products.hpp, products.harga_pembelian, 0) END)';
        $hppExpr = 'COALESCE(products.hpp, products.harga_pembelian, 0)';

        $rows = DB::table('transaksi_offline_items')
            ->join('transaksi_offline', 'transaksi_offline.id', '=', 'transaksi_offline_items.transaksi_offline_id')
            ->join('products', 'products.id', '=', 'transaksi_offline_items.product_id')
            ->where('transaksi_offline.warehouse_id', $warehouseId)
            ->where('transaksi_offline.tujuan', 'speedshop')
            ->whereNotNull('transaksi_offline.speedshop_purchase_order_id')
            ->whereBetween('transaksi_offline.created_at', [$dari->copy()->startOfDay(), $sampai->copy()->endOfDay()])
            ->whereNull('transaksi_offline.manager_rejection_at')
            ->whereNull('transaksi_offline.warehouse_po_review_rejected_at')
            ->whereNotNull('transaksi_offline.petugas_id')
            ->selectRaw("
                {$dayExpr} as day_key,
                SUM(transaksi_offline_items.qty * ({$priceExpr})) as pendapatan,
                SUM(transaksi_offline_items.qty * ({$hppExpr})) as hpp
            ")
            ->groupByRaw($dayExpr)
            ->get();

        foreach ($rows as $row) {
            $day = (string) $row->day_key;
            if (! isset($maps[$day])) {
                continue;
            }
            $maps[$day]['pendapatan']['speedshop'] = ($maps[$day]['pendapatan']['speedshop'] ?? 0) + (float) ($row->pendapatan ?? 0);
            $maps[$day]['hpp']['speedshop'] = ($maps[$day]['hpp']['speedshop'] ?? 0) + (float) ($row->hpp ?? 0);
        }
    }

    /**
     * @param  array<string, array{
     *     pendapatan: array<string, float>,
     *     hpp: array<string, float>,
     *     biaya_marketplace: float,
     *     biaya_iklan: float,
     *     biaya_op: float,
     *     biaya_non_op: float,
     *     hpp_returns: float
     * }>  $maps
     */
    private static function mergeAccountingRincianBiayaDailyMaps(
        int $warehouseId,
        Carbon $dari,
        Carbon $sampai,
        array &$maps,
    ): void {
        $query = self::rincianBiayaApprovedQuery($warehouseId, $dari, $sampai);
        if ($query === null) {
            return;
        }

        $hppReturnSql = implode(',', array_map(
            fn (string $no): string => "'".str_replace("'", "''", $no)."'",
            array_column(WarehouseLabaRugiChannels::HPP_RETURNS, 'no'),
        ));

        $rows = $query
            ->selectRaw("
                tanggal,
                COALESCE(SUM(CASE WHEN no_akun LIKE '5.2.%' THEN nominal ELSE 0 END), 0) as biaya_marketplace,
                COALESCE(SUM(CASE WHEN no_akun LIKE '5.3.%' THEN nominal ELSE 0 END), 0) as biaya_iklan,
                COALESCE(SUM(CASE WHEN no_akun LIKE '5.4.%' THEN nominal ELSE 0 END), 0) as biaya_op,
                COALESCE(SUM(CASE WHEN no_akun LIKE '5.5.%' THEN nominal ELSE 0 END), 0) as biaya_non_op,
                COALESCE(SUM(CASE WHEN no_akun IN ({$hppReturnSql}) THEN nominal ELSE 0 END), 0) as hpp_returns
            ")
            ->groupBy('tanggal')
            ->get();

        foreach ($rows as $row) {
            try {
                $day = Carbon::parse($row->tanggal)->toDateString();
            } catch (\Throwable $e) {
                continue;
            }
            if (! isset($maps[$day])) {
                continue;
            }
            $maps[$day]['biaya_marketplace'] += (float) ($row->biaya_marketplace ?? 0);
            $maps[$day]['biaya_iklan'] += (float) ($row->biaya_iklan ?? 0);
            $maps[$day]['biaya_op'] += (float) ($row->biaya_op ?? 0);
            $maps[$day]['biaya_non_op'] += (float) ($row->biaya_non_op ?? 0);
            $maps[$day]['hpp_returns'] += (float) ($row->hpp_returns ?? 0);
        }
    }

    /** @return list<string> */
    public static function monthsInPeriod(Carbon $dari, Carbon $sampai): array
    {
        $months = [];
        $cursor = $dari->copy()->startOfMonth();
        $end = $sampai->copy()->startOfMonth();

        while ($cursor <= $end) {
            $months[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        return $months;
    }

    public static function singleMonthKeyForPeriod(Carbon $dari, Carbon $sampai): ?string
    {
        if ($dari->format('Y-m') !== $sampai->format('Y-m')) {
            return null;
        }

        return $dari->format('Y-m');
    }

    /**
     * @param  list<string>  $months
     * @return array<int, array{diskon: float, ongkir: float, iklan_bop: float}>
     */
    public static function sumMonthlyAdjustmentsByReseller(?int $warehouseId, array $months): array
    {
        if (! $warehouseId || $months === [] || ! Schema::hasTable('warehouse_rekap_penjualan_monthly_adjustments')) {
            return [];
        }

        $out = [];
        $rows = WarehouseRekapPenjualanMonthlyAdjustment::query()
            ->where('warehouse_id', $warehouseId)
            ->whereIn('periode_bulan', $months)
            ->get(['warehouse_reseller_id', 'diskon', 'ongkir', 'iklan_bop']);

        foreach ($rows as $row) {
            $rid = (int) $row->warehouse_reseller_id;
            if (! isset($out[$rid])) {
                $out[$rid] = ['diskon' => 0.0, 'ongkir' => 0.0, 'iklan_bop' => 0.0];
            }
            $out[$rid]['diskon'] += (float) ($row->diskon ?? 0);
            $out[$rid]['ongkir'] += (float) ($row->ongkir ?? 0);
            $out[$rid]['iklan_bop'] += (float) ($row->iklan_bop ?? 0);
        }

        return $out;
    }

    /**
     * @return array{diskon: int, ongkir: int, iklan_bop: int}
     */
    public static function monthlyAdjustmentForReseller(
        ?int $warehouseId,
        int $resellerId,
        string $periodeBulan,
    ): array {
        $empty = ['diskon' => 0, 'ongkir' => 0, 'iklan_bop' => 0];
        if (! $warehouseId || ! Schema::hasTable('warehouse_rekap_penjualan_monthly_adjustments')) {
            return $empty;
        }

        $row = WarehouseRekapPenjualanMonthlyAdjustment::query()
            ->where('warehouse_id', $warehouseId)
            ->where('warehouse_reseller_id', $resellerId)
            ->where('periode_bulan', $periodeBulan)
            ->first();

        if (! $row) {
            return $empty;
        }

        return [
            'diskon' => (int) ($row->diskon ?? 0),
            'ongkir' => (int) ($row->ongkir ?? 0),
            'iklan_bop' => (int) ($row->iklan_bop ?? 0),
        ];
    }

    /**
     * @return list<array{kode: string, nama: string, qty: int, nilai: float}>
     */
    public static function topProductsForPeriod(
        Carbon $dari,
        Carbon $sampai,
        ?int $warehouseId,
        int $limit = 3,
    ): array {
        if ($warehouseId === null) {
            return [];
        }

        /** @var array<int, array{kode: string, nama: string, qty: int, nilai: float}> $aggregates */
        $aggregates = [];

        $rangeStart = $dari->copy()->startOfDay();
        $rangeEnd = $sampai->copy()->endOfDay();

        $onlineRows = \Illuminate\Support\Facades\DB::table('transaksi_online_items')
            ->join('transaksi_online', 'transaksi_online.id', '=', 'transaksi_online_items.transaksi_online_id')
            ->join('products', 'products.id', '=', 'transaksi_online_items.product_id')
            ->where('transaksi_online.warehouse_id', $warehouseId)
            ->where('transaksi_online.status', TransaksiOnline::STATUS_SELESAI)
            ->whereBetween('transaksi_online.created_at', [$rangeStart, $rangeEnd])
            ->groupBy('products.id', 'products.kode_produk', 'products.nama_produk')
            ->selectRaw('
                products.id,
                products.kode_produk,
                products.nama_produk,
                COALESCE(SUM(transaksi_online_items.qty), 0) as qty,
                COALESCE(SUM(transaksi_online_items.qty * transaksi_online_items.harga), 0) as nilai
            ')
            ->get();

        foreach ($onlineRows as $row) {
            $qty = (int) ($row->qty ?? 0);
            if ($qty < 1) {
                continue;
            }
            self::accumulateTopProductFromRow($aggregates, $row, $qty, (float) ($row->nilai ?? 0));
        }

        $offlinePriceExpr = 'CASE WHEN COALESCE(products.harga_jual, 0) > 0 THEN products.harga_jual ELSE COALESCE(products.harga_eceran_terendah, 0) END';

        $offlineRows = \Illuminate\Support\Facades\DB::table('transaksi_offline_items')
            ->join('transaksi_offline', 'transaksi_offline.id', '=', 'transaksi_offline_items.transaksi_offline_id')
            ->join('products', 'products.id', '=', 'transaksi_offline_items.product_id')
            ->where('transaksi_offline.warehouse_id', $warehouseId)
            ->whereBetween('transaksi_offline.created_at', [$rangeStart, $rangeEnd])
            ->whereNull('transaksi_offline.manager_rejection_at')
            ->whereNull('transaksi_offline.warehouse_po_review_rejected_at')
            ->whereIn('transaksi_offline.tujuan', ['reseller', 'umum'])
            ->whereNotNull('transaksi_offline.petugas_id')
            ->where(function ($q) {
                $q->whereNull('transaksi_offline.warehouse_reseller_id')
                    ->orWhereExists(function ($resellerQuery) {
                        $resellerQuery->selectRaw('1')
                            ->from('warehouse_resellers')
                            ->whereColumn('warehouse_resellers.id', 'transaksi_offline.warehouse_reseller_id')
                            ->where('warehouse_resellers.kategori', '!=', WarehouseReseller::KATEGORI_ONLINE);
                    });
            })
            ->groupBy('products.id', 'products.kode_produk', 'products.nama_produk')
            ->selectRaw("
                products.id,
                products.kode_produk,
                products.nama_produk,
                COALESCE(SUM(transaksi_offline_items.qty), 0) as qty,
                COALESCE(SUM(transaksi_offline_items.qty * ({$offlinePriceExpr})), 0) as nilai
            ")
            ->get();

        foreach ($offlineRows as $row) {
            $qty = (int) ($row->qty ?? 0);
            if ($qty < 1) {
                continue;
            }
            self::accumulateTopProductFromRow($aggregates, $row, $qty, (float) ($row->nilai ?? 0));
        }

        $speedshopPriceExpr = 'COALESCE(NULLIF(transaksi_offline_items.harga, 0), CASE'
            .' WHEN COALESCE(products.harga_jual_speedshop, 0) > 0 THEN products.harga_jual_speedshop'
            .' WHEN COALESCE(products.harga_jual, 0) > 0 THEN products.harga_jual'
            .' ELSE COALESCE(products.hpp, products.harga_pembelian, 0) END)';

        $speedshopRows = \Illuminate\Support\Facades\DB::table('transaksi_offline_items')
            ->join('transaksi_offline', 'transaksi_offline.id', '=', 'transaksi_offline_items.transaksi_offline_id')
            ->join('products', 'products.id', '=', 'transaksi_offline_items.product_id')
            ->where('transaksi_offline.warehouse_id', $warehouseId)
            ->where('transaksi_offline.tujuan', 'speedshop')
            ->whereNotNull('transaksi_offline.speedshop_purchase_order_id')
            ->whereBetween('transaksi_offline.created_at', [$rangeStart, $rangeEnd])
            ->whereNull('transaksi_offline.manager_rejection_at')
            ->whereNull('transaksi_offline.warehouse_po_review_rejected_at')
            ->whereNotNull('transaksi_offline.petugas_id')
            ->groupBy('products.id', 'products.kode_produk', 'products.nama_produk')
            ->selectRaw("
                products.id,
                products.kode_produk,
                products.nama_produk,
                COALESCE(SUM(transaksi_offline_items.qty), 0) as qty,
                COALESCE(SUM(transaksi_offline_items.qty * ({$speedshopPriceExpr})), 0) as nilai
            ")
            ->get();

        foreach ($speedshopRows as $row) {
            $qty = (int) ($row->qty ?? 0);
            if ($qty < 1) {
                continue;
            }
            self::accumulateTopProductFromRow($aggregates, $row, $qty, (float) ($row->nilai ?? 0));
        }

        return collect($aggregates)
            ->sort(function (array $a, array $b): int {
                if ($a['qty'] !== $b['qty']) {
                    return $b['qty'] <=> $a['qty'];
                }

                return $b['nilai'] <=> $a['nilai'];
            })
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{kode: string, nama: string, qty: int, nilai: float}>  $aggregates
     */
    private static function accumulateTopProductFromRow(array &$aggregates, object $row, int $qty, float $nilai): void
    {
        $id = (int) $row->id;
        if (! isset($aggregates[$id])) {
            $kode = (string) ($row->kode_produk ?? '');
            $aggregates[$id] = [
                'kode' => $kode !== '' ? $kode : '-',
                'nama' => (string) ($row->nama_produk ?? '-'),
                'qty' => 0,
                'nilai' => 0.0,
            ];
        }

        $aggregates[$id]['qty'] += $qty;
        $aggregates[$id]['nilai'] += $nilai;
    }

    /**
     * @param  array<int, array{kode: string, nama: string, qty: int, nilai: float}>  $aggregates
     */
    private static function accumulateTopProduct(array &$aggregates, object $product, int $qty, float $nilai): void
    {
        $id = (int) $product->id;
        if (! isset($aggregates[$id])) {
            $kode = (string) ($product->kode_produk ?? '');
            $aggregates[$id] = [
                'kode' => $kode !== '' ? $kode : '-',
                'nama' => (string) ($product->nama_produk ?? '-'),
                'qty' => 0,
                'nilai' => 0.0,
            ];
        }

        $aggregates[$id]['qty'] += $qty;
        $aggregates[$id]['nilai'] += $nilai;
    }
}

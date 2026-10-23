<?php

namespace App\Support;

use App\Models\SpeedshopKeuanganRekening;
use App\Models\SpeedshopRincianBiayaEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Rekap pemisahan dana speedshop per minggu dalam satu bulan.
 */
final class SpeedshopPemisahanDanaReport
{
    /**
     * Mapping kategori laporan → no akun (master Keuangan → No Akun).
     *
     * @var array<string, string>
     */
    private const NO_AKUN_NAMA = [
        '5.2.14' => 'Sewa Bangunan',
        '5.2.1' => 'Gaji Karyawan',
        '5.2.4' => 'Gaji Management',
        '5.2.2' => 'THR Karyawan',
        '5.2.5' => 'THR Management',
        '5.2.3' => 'Bonus Karyawan',
        '5.2.6' => 'Bonus Management',
    ];

    /**
     * Pembagian KARYAWAN / MANAGEMENT per kategori (satu no akun per baris).
     *
     * @var array<string, array<string, string>>
     */
    private const PEMBAGIAN_AKUN_BY_CATEGORY = [
        'gaji' => [
            'KARYAWAN' => '5.2.1',
            'MANAGEMENT' => '5.2.4',
        ],
        'thr' => [
            'KARYAWAN' => '5.2.2',
            'MANAGEMENT' => '5.2.5',
        ],
        'bonus' => [
            'KARYAWAN' => '5.2.3',
            'MANAGEMENT' => '5.2.6',
        ],
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public static function categoryDefinitions(): array
    {
        return [
            ['key' => 'omset', 'no' => 1, 'label' => 'OMSET', 'type' => 'omset', 'color' => 'row-red'],
            ['key' => 'hpp', 'no' => 2, 'label' => 'HPP', 'type' => 'hpp', 'color' => 'row-red'],
            [
                'key' => 'sewa',
                'no' => 3,
                'label' => 'SEWA GEDUNG',
                'type' => 'bop',
                'akuns' => ['5.2.14'], // Sewa Bangunan
                'color' => 'row-blue',
                'sub_mode' => 'keterangan',
            ],
            [
                'key' => 'depresiasi',
                'no' => 4,
                'label' => 'DEPRESIASI',
                'type' => 'bop',
                'akuns' => ['5.2.12'],
                'color' => 'row-green',
            ],
            [
                'key' => 'gaji',
                'no' => 5,
                'label' => 'GAJI',
                'type' => 'bop',
                'akuns' => ['5.2.1', '5.2.4'], // Gaji Karyawan, Gaji Management
                'color' => 'row-yellow',
                'sub_mode' => 'akun',
            ],
            [
                'key' => 'thr',
                'no' => 6,
                'label' => 'THR',
                'type' => 'bop',
                'akuns' => ['5.2.2', '5.2.5'], // THR Karyawan, THR Management
                'color' => 'row-orange',
                'sub_mode' => 'akun',
            ],
            [
                'key' => 'bonus',
                'no' => 7,
                'label' => 'BONUS',
                'type' => 'bop',
                'akuns' => ['5.2.3', '5.2.6'], // Bonus Karyawan, Bonus Management
                'color' => 'row-gray',
                'sub_mode' => 'akun',
            ],
            [
                'key' => 'reward',
                'no' => 8,
                'label' => 'REWARD KARYAWAN',
                'type' => 'bop',
                'akuns' => ['5.2.8'],
                'color' => 'row-teal',
            ],
            ['key' => 'sisa_profit', 'no' => 9, 'label' => 'SISA PROFIT', 'type' => 'sisa_profit', 'color' => 'row-purple'],
        ];
    }

    /**
     * @param  list<int>  $speedshopIds
     * @return array<string, mixed>
     */
    public static function build(
        Carbon $dari,
        Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        array $speedshopIds = [],
    ): array {
        $monthStart = $dari->copy()->startOfMonth();
        $monthEnd = $sampai->copy()->endOfMonth();
        if ($dari->gt($monthStart) || $sampai->lt($monthEnd)) {
            $monthStart = $dari->copy()->startOfDay();
            $monthEnd = $sampai->copy()->endOfDay();
        }

        $weekColumns = self::weekColumnsForMonth($monthStart, $monthEnd);
        $bopEntries = self::loadBopEntries($monthStart, $monthEnd, $speedshopId, $warehouseId, $speedshopIds);

        $rows = [];
        $bopWeekTotals = self::emptyWeekCells($weekColumns);

        foreach (self::categoryDefinitions() as $def) {
            $row = self::buildRow(
                $def,
                $weekColumns,
                $monthStart,
                $monthEnd,
                $speedshopId,
                $warehouseId,
                $speedshopIds,
                $bopEntries,
                $bopWeekTotals,
            );
            $rows[] = $row;
        }

        return [
            'weekColumns' => $weekColumns,
            'rows' => $rows,
            'monthLabel' => $monthStart->translatedFormat('F Y'),
            'periodDari' => $monthStart,
            'periodSampai' => $monthEnd,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function weekColumnsForMonth(Carbon $monthStart, Carbon $monthEnd): array
    {
        $lastDay = (int) $monthEnd->day;
        $monthName = $monthStart->translatedFormat('F');
        $year = (int) $monthStart->year;

        $defs = [
            ['key' => 'minggu_1', 'num' => 1, 'from' => 1, 'to' => 7],
            ['key' => 'minggu_2', 'num' => 2, 'from' => 8, 'to' => 14],
            ['key' => 'minggu_3', 'num' => 3, 'from' => 15, 'to' => 21],
            ['key' => 'minggu_4', 'num' => 4, 'from' => 22, 'to' => 28],
            ['key' => 'tambahan', 'num' => null, 'from' => 29, 'to' => $lastDay],
        ];

        $columns = [];
        foreach ($defs as $def) {
            if ($def['from'] > $lastDay) {
                continue;
            }
            $to = min($def['to'], $lastDay);
            $start = $monthStart->copy()->day($def['from'])->startOfDay();
            $end = $monthStart->copy()->day($to)->endOfDay();

            $columns[] = [
                'key' => $def['key'],
                'num' => $def['num'],
                'from_day' => $def['from'],
                'to_day' => $to,
                'dari' => $start,
                'sampai' => $end,
                'title' => $def['num'] !== null ? 'MINGGU KE-'.$def['num'] : 'TAMBAHAN',
                'range_label' => sprintf(
                    'Tanggal %d–%d %s %d',
                    $def['from'],
                    $to,
                    $monthName,
                    $year,
                ),
            ];
        }

        return $columns;
    }

    /**
     * @param  list<int>  $speedshopIds
     * @return Collection<int, SpeedshopRincianBiayaEntry>
     */
    private static function loadBopEntries(
        Carbon $dari,
        Carbon $sampai,
        ?int $speedshopId,
        ?int $warehouseId,
        array $speedshopIds,
    ): Collection {
        if (! Schema::hasTable('speedshop_rincian_biaya_entries')) {
            return collect();
        }

        $allAkuns = collect(self::categoryDefinitions())
            ->where('type', 'bop')
            ->flatMap(fn (array $d) => $d['akuns'] ?? [])
            ->unique()
            ->values()
            ->all();

        if ($allAkuns === []) {
            return collect();
        }

        $ids = $speedshopIds !== []
            ? array_values(array_unique(array_map('intval', $speedshopIds)))
            : ($speedshopId ? [(int) $speedshopId] : []);

        if ($ids === [] && $warehouseId) {
            $ids = \App\Models\Speedshop::query()
                ->where(function ($q) use ($warehouseId) {
                    $q->where('mutasi_warehouse_id', $warehouseId)
                        ->orWhere('warehouse_id', $warehouseId);
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        if ($ids === []) {
            return collect();
        }

        $withTujuan = Schema::hasColumn('speedshop_rincian_biaya_entries', 'speedshop_keuangan_rekening_tujuan_id')
            ? ['keuanganRekeningTujuan']
            : [];

        return SpeedshopRincianBiayaEntry::query()
            ->with($withTujuan)
            ->when(
                count($ids) === 1,
                fn ($q) => $q->where('speedshop_id', $ids[0]),
                fn ($q) => $q->whereIn('speedshop_id', $ids),
            )
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->whereIn('no_akun', $allAkuns)
            ->where(function ($q) {
                $q->whereNull('approval_status')
                    ->orWhere('approval_status', SpeedshopRincianBiayaEntry::STATUS_APPROVED);
            })
            ->where(function ($q) {
                $q->where('jenis_operasional', SpeedshopRincianBiayaEntry::JENIS_BIAYA_OPERASIONAL)
                    ->orWhereNull('jenis_operasional');
            })
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<array<string, mixed>>  $weekColumns
     * @param  array<string, mixed>  $def
     * @param  Collection<int, SpeedshopRincianBiayaEntry>  $allBopEntries
     * @param  array<string, array{nominal: int, tanggal: string}>  $bopWeekTotals
     * @return array<string, mixed>
     */
    private static function buildRow(
        array $def,
        array $weekColumns,
        Carbon $monthStart,
        Carbon $monthEnd,
        ?int $speedshopId,
        ?int $warehouseId,
        array $speedshopIds,
        Collection $allBopEntries,
        array &$bopWeekTotals,
    ): array {
        $cells = self::emptyWeekCells($weekColumns);
        $subRows = [];
        $rekeningSuffix = '';

        $type = (string) ($def['type'] ?? 'bop');

        if ($type === 'omset') {
            foreach ($weekColumns as $col) {
                $row = SpeedshopLabaRugiFinancials::pendapatanBiayaRow(
                    $col['dari'],
                    $col['sampai'],
                    $speedshopId,
                    $warehouseId,
                    $speedshopIds,
                );
                $cells[$col['key']] = self::formatCell((int) round((float) ($row['omzet_kotor'] ?? 0)), []);
            }
        } elseif ($type === 'hpp') {
            foreach ($weekColumns as $col) {
                $row = SpeedshopLabaRugiFinancials::pendapatanBiayaRow(
                    $col['dari'],
                    $col['sampai'],
                    $speedshopId,
                    $warehouseId,
                    $speedshopIds,
                );
                $cells[$col['key']] = self::formatCell((int) round((float) ($row['hpp_part'] ?? 0)), []);
            }
        } elseif ($type === 'sisa_profit') {
            foreach ($weekColumns as $col) {
                $row = SpeedshopLabaRugiFinancials::pendapatanBiayaRow(
                    $col['dari'],
                    $col['sampai'],
                    $speedshopId,
                    $warehouseId,
                    $speedshopIds,
                );
                $cells[$col['key']] = self::formatCell((int) round((float) ($row['profit_bersih'] ?? 0)), []);
            }
            $rekeningSuffix = self::resolveProfitRekeningSuffix($speedshopId, $speedshopIds);
        } else {
            $akuns = $def['akuns'] ?? [];
            $entries = $allBopEntries->filter(
                fn (SpeedshopRincianBiayaEntry $e) => in_array(self::normalizeNoAkun($e->no_akun), $akuns, true),
            );

            $cells = self::aggregateEntriesToWeekCells($entries, $weekColumns);
            $rekeningSuffix = self::resolveRekeningSuffixFromEntries($entries);

            foreach ($weekColumns as $col) {
                $key = $col['key'];
                $bopWeekTotals[$key]['nominal'] += $cells[$key]['nominal'];
            }

            if (($def['sub_mode'] ?? null) === 'akun') {
                $subRows = self::buildAkunSubRows($entries, $weekColumns, (string) ($def['key'] ?? ''));
            } elseif (($def['sub_mode'] ?? null) === 'keterangan') {
                $subRows = self::buildKeteranganSubRows($entries, $weekColumns);
            }
        }

        $cells['total'] = self::sumCells($cells, $weekColumns);

        foreach ($subRows as &$sub) {
            $sub['cells']['total'] = self::sumCells($sub['cells'], $weekColumns);
        }
        unset($sub);

        return [
            'key' => $def['key'],
            'no' => $def['no'],
            'label' => $def['label'],
            'rekening_suffix' => $rekeningSuffix,
            'color' => $def['color'] ?? '',
            'cells' => $cells,
            'sub_rows' => $subRows,
            'is_parent' => $subRows !== [],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $weekColumns
     * @return list<array<string, mixed>>
     */
    private static function buildAkunSubRows(Collection $entries, array $weekColumns, string $categoryKey): array
    {
        $pembagianMap = self::PEMBAGIAN_AKUN_BY_CATEGORY[$categoryKey] ?? [];
        if (count($pembagianMap) <= 1) {
            return [];
        }

        $subs = [];
        foreach ($pembagianMap as $label => $noAkun) {
            $subset = $entries->filter(
                fn (SpeedshopRincianBiayaEntry $e) => self::normalizeNoAkun($e->no_akun) === $noAkun,
            );
            $subs[] = [
                'label' => $label,
                'no_akun' => $noAkun,
                'nama_akun' => self::NO_AKUN_NAMA[$noAkun] ?? $noAkun,
                'cells' => self::aggregateEntriesToWeekCells($subset, $weekColumns),
            ];
        }

        return $subs;
    }

    private static function normalizeNoAkun(mixed $noAkun): string
    {
        return trim((string) $noAkun);
    }

    /**
     * @param  Collection<int, SpeedshopRincianBiayaEntry>  $entries
     * @param  list<array<string, mixed>>  $weekColumns
     * @return list<array<string, mixed>>
     */
    private static function buildKeteranganSubRows(Collection $entries, array $weekColumns): array
    {
        $groups = $entries->groupBy(function (SpeedshopRincianBiayaEntry $e) {
            $ket = trim((string) ($e->keterangan ?? ''));

            return $ket !== '' ? mb_strtoupper($ket) : 'LAINNYA';
        });

        $subs = [];
        foreach ($groups as $label => $subset) {
            $subs[] = [
                'label' => (string) $label,
                'cells' => self::aggregateEntriesToWeekCells($subset, $weekColumns),
            ];
        }

        return $subs;
    }

    /**
     * @param  Collection<int, SpeedshopRincianBiayaEntry>  $entries
     * @param  list<array<string, mixed>>  $weekColumns
     * @return array<string, array{nominal: int, nominal_fmt: string, tanggal: string, is_empty: bool}>
     */
    private static function aggregateEntriesToWeekCells(Collection $entries, array $weekColumns): array
    {
        $cells = self::emptyWeekCells($weekColumns);
        $datesByWeek = [];
        foreach ($weekColumns as $col) {
            $datesByWeek[$col['key']] = [];
        }

        foreach ($entries as $entry) {
            $day = $entry->tanggal instanceof Carbon
                ? (int) $entry->tanggal->day
                : (int) Carbon::parse($entry->tanggal)->day;
            $weekKey = self::weekKeyForDay($day);
            if (! isset($cells[$weekKey])) {
                continue;
            }
            $cells[$weekKey]['nominal'] += $entry->nominalEfektif();
            $tanggal = $entry->tanggal instanceof Carbon
                ? $entry->tanggal
                : Carbon::parse($entry->tanggal);
            $datesByWeek[$weekKey][] = $tanggal;
        }

        foreach ($weekColumns as $col) {
            $key = $col['key'];
            $cells[$key] = self::formatCell($cells[$key]['nominal'], $datesByWeek[$key] ?? []);
        }

        return $cells;
    }

    /**
     * @param  list<Carbon>  $dates
     * @return array{nominal: int, nominal_fmt: string, tanggal: string, is_empty: bool}
     */
    private static function formatCell(int $nominal, array $dates): array
    {
        $uniqueDates = collect($dates)
            ->map(fn (Carbon $d) => $d->copy()->startOfDay())
            ->unique(fn (Carbon $d) => $d->toDateString())
            ->sortBy(fn (Carbon $d) => $d->timestamp)
            ->values();

        $tanggalLabel = $uniqueDates->isEmpty()
            ? '—'
            : $uniqueDates->map(fn (Carbon $d) => $d->translatedFormat('d M Y'))->implode(', ');

        return [
            'nominal' => $nominal,
            'nominal_fmt' => $nominal > 0 ? 'Rp '.number_format($nominal, 0, ',', '.') : '—',
            'tanggal' => $tanggalLabel,
            'is_empty' => $nominal <= 0,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $weekColumns
     * @return array<string, array{nominal: int, nominal_fmt: string, tanggal: string, is_empty: bool}>
     */
    private static function emptyWeekCells(array $weekColumns): array
    {
        $cells = [];
        foreach ($weekColumns as $col) {
            $cells[$col['key']] = self::formatCell(0, []);
        }

        return $cells;
    }

    /**
     * @param  array<string, array{nominal: int}>  $cells
     * @param  list<array<string, mixed>>  $weekColumns
     * @return array{nominal: int, nominal_fmt: string, tanggal: string, is_empty: bool}
     */
    private static function sumCells(array $cells, array $weekColumns): array
    {
        $total = 0;
        foreach ($weekColumns as $col) {
            $total += (int) ($cells[$col['key']]['nominal'] ?? 0);
        }

        return self::formatCell($total, []);
    }

    private static function weekKeyForDay(int $day): string
    {
        if ($day <= 7) {
            return 'minggu_1';
        }
        if ($day <= 14) {
            return 'minggu_2';
        }
        if ($day <= 21) {
            return 'minggu_3';
        }
        if ($day <= 28) {
            return 'minggu_4';
        }

        return 'tambahan';
    }

    /**
     * @param  Collection<int, SpeedshopRincianBiayaEntry>  $entries
     */
    private static function resolveRekeningSuffixFromEntries(Collection $entries): string
    {
        if ($entries->isEmpty() || ! Schema::hasColumn('speedshop_rincian_biaya_entries', 'speedshop_keuangan_rekening_tujuan_id')) {
            return '';
        }

        $rekId = $entries
            ->filter(fn (SpeedshopRincianBiayaEntry $e) => (int) ($e->speedshop_keuangan_rekening_tujuan_id ?? 0) > 0)
            ->groupBy(fn (SpeedshopRincianBiayaEntry $e) => (int) $e->speedshop_keuangan_rekening_tujuan_id)
            ->sortByDesc(fn (Collection $g) => $g->count())
            ->keys()
            ->first();

        if (! $rekId) {
            return '';
        }

        $rek = $entries->first(fn (SpeedshopRincianBiayaEntry $e) => (int) ($e->speedshop_keuangan_rekening_tujuan_id ?? 0) === (int) $rekId)
            ?->keuanganRekeningTujuan;

        return self::formatRekeningSuffix($rek);
    }

    /**
     * @param  list<int>  $speedshopIds
     */
    private static function resolveProfitRekeningSuffix(?int $speedshopId, array $speedshopIds): string
    {
        $ids = $speedshopIds !== [] ? $speedshopIds : ($speedshopId ? [(int) $speedshopId] : []);
        if ($ids === []) {
            return '';
        }

        $rek = SpeedshopKeuanganRekening::query()
            ->whereNull('warehouse_id')
            ->when(count($ids) === 1, fn ($q) => $q->where('speedshop_id', $ids[0]), fn ($q) => $q->whereIn('speedshop_id', $ids))
            ->where(function ($q) {
                $q->whereRaw('UPPER(nama) LIKE ?', ['%SISA%PROFIT%'])
                    ->orWhereRaw('UPPER(nama) LIKE ?', ['%PROFIT%']);
            })
            ->orderBy('id')
            ->first();

        return self::formatRekeningSuffix($rek);
    }

    public static function formatRekeningSuffix(?SpeedshopKeuanganRekening $rek): string
    {
        if (! $rek) {
            return '';
        }

        $no = preg_replace('/\D+/', '', (string) ($rek->nomor_rekening ?? '')) ?: trim((string) ($rek->nomor_rekening ?? ''));
        $bank = self::shortBankCode((string) ($rek->nama_bank ?? ''));

        if ($no === '' && $bank === '') {
            return '';
        }

        if ($no !== '' && $bank !== '') {
            return '('.$no.'-'.$bank.')';
        }

        return '('.($no !== '' ? $no : $bank).')';
    }

    private static function shortBankCode(string $bankName): string
    {
        $bank = mb_strtoupper(trim($bankName));
        if ($bank === '') {
            return '';
        }

        $known = ['DBS', 'BRI', 'BCA', 'BNI', 'MANDIRI', 'SAQU', 'CIMB', 'PERMATA', 'BTN', 'BSI'];
        foreach ($known as $code) {
            if (str_contains($bank, $code)) {
                return $code;
            }
        }

        $parts = preg_split('/\s+/', $bank) ?: [];

        return $parts[0] ?? $bank;
    }
}

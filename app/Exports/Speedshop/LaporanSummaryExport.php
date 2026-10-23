<?php

namespace App\Exports\Speedshop;

use App\Exports\Concerns\WithExcelHeaderStyle;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;

class LaporanSummaryExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings
{
    use WithExcelHeaderStyle;

    public function __construct(protected array $ctx = []) {}

    public function array(): array
    {
        /** @var Collection<int, object> $dailyReports */
        $dailyReports = collect($this->ctx['dailyReports'] ?? []);
        $rows = [];
        $dayIdx = 0;

        foreach ($dailyReports as $day) {
            $dayIdx++;
            $dateLabel = Carbon::parse($day->tanggal)->locale('id')->translatedFormat('d F Y');

            foreach ($day->rows as $rowIdx => $row) {
                $biayaTotal = (float) ($row->biaya_total ?? 0);
                $rows[] = [
                    $rowIdx === 0 ? $dayIdx : '',
                    $rowIdx === 0 ? $dateLabel : '',
                    $row->label,
                    $rowIdx === 0 ? 'Rp' : '',
                    $rowIdx === 0 ? (float) $day->pendapatan_kotor : '',
                    $biayaTotal > 0 ? 'Rp' : '',
                    $biayaTotal > 0 ? $biayaTotal : '',
                    'Rp',
                    (float) $row->gross_profit,
                    $rowIdx === 0 ? 'Rp' : '',
                    $rowIdx === 0 ? (float) $day->total_gross_profit_before_hpp : '',
                ];
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'No',
            'Tanggal',
            'Keterangan',
            'PK Rp',
            'Pendapatan kotor',
            'BO Rp',
            'Biaya operasional',
            'GP Rp',
            'Gross profit',
            'Total GP Rp',
            'Total gross profit before HPP',
        ];
    }
}

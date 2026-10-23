<?php

namespace App\Exports\Speedshop;

use App\Support\SpeedshopLabaRugiFinancials;
use App\Support\WarehouseLabaRugiFinancials;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LaporanLabaRugiExport implements WithEvents
{
    private const FILL_LIGHT = 'FFD9E1F2';

    private const FILL_TOTAL = 'FFB4C6E7';

    private const FILL_RED_BAR = 'FFE31837';

    public function __construct(
        protected array $filters = []
    ) {}

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->renderSheet($event->sheet->getDelegate());
            },
        ];
    }

    protected function renderSheet(Worksheet $sheet): void
    {
        $dari = ! empty($this->filters['dari']) ? Carbon::parse($this->filters['dari'])->startOfDay() : now()->startOfMonth();
        $sampai = ! empty($this->filters['sampai']) ? Carbon::parse($this->filters['sampai'])->endOfDay() : now()->endOfMonth();

        if (! empty($this->filters['warehouse_laba_rugi'])) {
            $warehouseId = isset($this->filters['warehouse_id'])
                ? (int) $this->filters['warehouse_id']
                : null;
            $presentation = WarehouseLabaRugiFinancials::labaRugiPresentation(
                $dari,
                $sampai,
                $warehouseId,
                is_array($this->filters['sort_by_section'] ?? null) ? $this->filters['sort_by_section'] : [],
            );
        } else {
            $scope = SpeedshopLabaRugiFinancials::resolveScopeIds();
            $presentation = SpeedshopLabaRugiFinancials::labaRugiPresentation(
                $dari,
                $sampai,
                $scope['speedshop_id'],
                $scope['warehouse_id'],
                $scope['speedshop_ids'] ?? [],
            );
        }

        $period = $this->periodBarLabels($dari, $sampai, $this->filters['mode'] ?? null);

        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(48);
        $sheet->getColumnDimension('C')->setWidth(6);
        $sheet->getColumnDimension('D')->setWidth(28);

        $sheet->getRowDimension(1)->setRowHeight(52);
        $sheet->getRowDimension(2)->setRowHeight(26);

        $logoPath = public_path('images/logo.png');
        if (is_file($logoPath)) {
            $drawing = new Drawing;
            $drawing->setName('Logo');
            $drawing->setDescription('Logo');
            $drawing->setPath($logoPath);
            $drawing->setHeight(44);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(4);
            $drawing->setOffsetY(4);
            $drawing->setWorksheet($sheet);
        }

        $sheet->mergeCells('B1:D1');
        $sheet->setCellValue('B1', 'LAPORAN LABA RUGI');
        $sheet->getStyle('B1:D1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF000000']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->mergeCells('A2:B2');
        $sheet->setCellValue('A2', $period[0]);
        $sheet->setCellValue('D2', $period[1]);
        $sheet->getStyle('A2:D2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::FILL_RED_BAR],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => $this->thinOutlineBorders(),
        ]);
        $sheet->getStyle('A2:B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $startRow = 4;
        $r = $startRow;

        foreach ($presentation['rows'] as $row) {
            $kind = $row['kind'] ?? 'line';
            $showRp = in_array($kind, ['line', 'subtotal', 'grand', 'footer'], true) && ! ($row['is_percent'] ?? false);
            $amount = $row['amount'] ?? null;

            $sheet->setCellValue('A'.$r, $row['no'] ?? '');
            $sheet->setCellValue('B'.$r, $row['label'] ?? '');
            $sheet->setCellValue('C'.$r, $showRp ? 'Rp' : '');
            if ($amount !== null) {
                $sheet->setCellValue(
                    'D'.$r,
                    ($row['is_percent'] ?? false)
                        ? number_format((float) $amount, 2, ',', '.').'%'
                        : $this->fmtMoney((float) $amount)
                );
            }

            $style = $this->styleForRowType($kind);
            $sheet->getStyle('A'.$r.':D'.$r)->applyFromArray(array_merge($style, [
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF9CA3AF'],
                    ],
                ],
            ]));

            $indent = SpeedshopLabaRugiFinancials::labaRugiRowDepth($row);
            $sheet->getStyle('A'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('B'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('C'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            if ($indent > 0) {
                $sheet->getStyle('A'.$r)->getAlignment()->setIndent($indent);
                $sheet->getStyle('B'.$r)->getAlignment()->setIndent($indent);
            }

            $r++;
        }

        $sheet->freezePane('A'.$startRow);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function periodBarLabels(Carbon $dari, Carbon $sampai, ?string $mode): array
    {
        $mode = $mode ?? '';

        if ($mode === 'range' || ($dari->toDateString() !== $sampai->toDateString() && $mode !== 'bulanan' && $mode !== 'tahunan')) {
            return [
                'Periode',
                mb_strtoupper(
                    $dari->locale('id')->translatedFormat('d M Y').' – '.$sampai->locale('id')->translatedFormat('d M Y'),
                    'UTF-8'
                ),
            ];
        }

        if ($mode === 'harian' && $dari->isSameDay($sampai)) {
            return [
                'Tanggal',
                mb_strtoupper($dari->locale('id')->translatedFormat('d F Y'), 'UTF-8'),
            ];
        }

        if ($mode === 'tahunan' && $dari->isStartOfYear() && $sampai->isEndOfYear() && $dari->year === $sampai->year) {
            return ['Tahun', (string) $dari->year];
        }

        if ($dari->isStartOfMonth() && $sampai->isEndOfMonth() && $dari->month === $sampai->month && $dari->year === $sampai->year) {
            return [
                'Bulan',
                mb_strtoupper($dari->locale('id')->translatedFormat('F'), 'UTF-8'),
            ];
        }

        return [
            'Periode',
            mb_strtoupper(
                $dari->locale('id')->translatedFormat('d M Y').' – '.$sampai->locale('id')->translatedFormat('d M Y'),
                'UTF-8'
            ),
        ];
    }

    private function fmtMoney(float $n): string
    {
        return number_format($n, 0, ',', '.');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function styleForRowType(string $t): array
    {
        $light = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::FILL_LIGHT],
            ],
            'font' => ['bold' => false, 'size' => 10, 'color' => ['argb' => 'FF111827']],
        ];

        $dark = [
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::FILL_TOTAL],
            ],
            'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF111827']],
        ];

        return match ($t) {
            'section', 'sub' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => self::FILL_LIGHT],
                ],
                'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF000000']],
            ],
            'line' => $light,
            'pct' => array_merge($light, ['font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF111827']]]),
            'subtotal' => $dark,
            'grand', 'footer', 'ket' => $dark,
            default => $light,
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function thinOutlineBorders(): array
    {
        return [
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF7F1D1D'],
                ],
            ],
        ];
    }
}

<?php

namespace App\Exports;

use App\Services\AttendanceScheduleService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AbsensiRecapExport implements FromArray, WithEvents, WithTitle
{
    /**
     * @param  Collection<int, array{label: string, employees: Collection}>  $displayGroups
     * @param  list<array{day: int, key: string, dow: string, iso: int, is_sunday: bool}>  $monthDays
     * @param  array<int, array<string, array{code: string, class: string, title: string}>>  $cells
     * @param  array{present: int, late: int, absent: int, off_days: int, pending: int}  $summary
     */
    public function __construct(
        private readonly Carbon $calendarMonth,
        private readonly Collection $displayGroups,
        private readonly array $monthDays,
        private readonly array $cells,
        private readonly array $summary,
        private readonly string $scopeLabel = 'Rekap Absensi',
    ) {}

    public function title(): string
    {
        return 'Rekap '.$this->calendarMonth->format('Y-m');
    }

    public function array(): array
    {
        $dayHeaders = array_map(fn (array $d) => (string) $d['day'], $this->monthDays);
        $dowHeaders = array_map(fn (array $d) => (string) $d['dow'], $this->monthDays);

        $rows = [
            ['Rekap Absensi · '.$this->calendarMonth->translatedFormat('F Y')],
            [$this->scopeLabel],
            [
                'Ringkasan: Hadir '.$this->summary['present']
                .' · Telat '.$this->summary['late']
                .' · Alpha '.$this->summary['absent']
                .' · Pending '.$this->summary['pending']
                .' · Libur/Dinas '.$this->summary['off_days'],
            ],
            [],
            array_merge(['No', 'Nama Karyawan'], $dayHeaders, AttendanceScheduleService::RECAP_TOTAL_CODES),
            array_merge(['', ''], $dowHeaders, array_fill(0, count(AttendanceScheduleService::RECAP_TOTAL_CODES), '')),
        ];

        foreach ($this->displayGroups as $group) {
            $rows[] = array_merge(
                [strtoupper((string) $group['label'])],
                array_fill(0, 1 + count($this->monthDays) + count(AttendanceScheduleService::RECAP_TOTAL_CODES), ''),
            );

            $no = 1;
            foreach ($group['employees'] as $emp) {
                $uid = (int) $emp->id;
                $dayCodes = [];
                foreach ($this->monthDays as $d) {
                    $cell = $this->cells[$uid][$d['key']] ?? [];
                    $dayCodes[] = (string) ($cell['label'] ?? $cell['code'] ?? '');
                }
                $totals = AttendanceScheduleService::recapStatusTotals($this->cells[$uid] ?? []);
                $totalValues = array_map(
                    fn (string $code) => (string) ($totals[$code] ?? 0),
                    AttendanceScheduleService::RECAP_TOTAL_CODES,
                );
                $rows[] = array_merge([(string) $no, (string) $emp->name], $dayCodes, $totalValues);
                $no++;
            }
        }

        if ($this->displayGroups->isEmpty()) {
            $rows[] = ['Belum ada data karyawan pada filter ini.'];
        }

        $rows[] = [];
        $rows[] = ['Legenda: H=Hadir · T=Telat · A=Tidak hadir · P=Pending · WFH · IZIN · SAKIT · CUTI · LIBUR · DINAS · Format hadir: H 08:05-17:12'];

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = (int) $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();
                $dayCount = count($this->monthDays);
                $lastColIndex = 2 + $dayCount;

                $sheet->mergeCells('A1:'.$highestColumn.'1');
                $sheet->mergeCells('A2:'.$highestColumn.'2');
                $sheet->mergeCells('A3:'.$highestColumn.'3');

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF111827']],
                ]);
                $sheet->getStyle('A2:A3')->applyFromArray([
                    'font' => ['size' => 10, 'color' => ['argb' => 'FF6B7280']],
                ]);

                $headerRow = 5;
                $dowRow = 6;
                $sheet->getStyle('A'.$headerRow.':'.$highestColumn.$dowRow)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 9],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF4472C4'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                for ($row = 7; $row <= $highestRow; $row++) {
                    $first = trim((string) $sheet->getCell('A'.$row)->getValue());
                    $second = trim((string) $sheet->getCell('B'.$row)->getValue());

                    if ($first !== '' && $second === '' && ! is_numeric($first)) {
                        $sheet->mergeCells('A'.$row.':'.$highestColumn.$row);
                        $sheet->getStyle('A'.$row)->applyFromArray([
                            'font' => ['bold' => true, 'size' => 9],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => 'FFBFBFBF'],
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                            ],
                        ]);

                        continue;
                    }

                    if (! is_numeric($first)) {
                        continue;
                    }

                    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('B'.$row)->getFont()->setBold(true);

                    for ($col = 3; $col <= $lastColIndex; $col++) {
                        $coord = $this->columnLetter($col).$row;
                        $value = strtoupper(trim((string) $sheet->getCell($coord)->getValue()));
                        $code = $this->statusCodeFromLabel($value);
                        $fill = $this->fillForCode($code);
                        if ($fill === null) {
                            continue;
                        }
                        $sheet->getStyle($coord)->applyFromArray([
                            'font' => ['bold' => true, 'size' => 7, 'color' => ['argb' => $fill['font']]],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['argb' => $fill['bg']],
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                                'vertical' => Alignment::VERTICAL_CENTER,
                                'wrapText' => true,
                            ],
                        ]);
                    }
                }

                $sheet->getStyle('A5:'.$highestColumn.$highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FFD1D5DB'],
                        ],
                    ],
                ]);

                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(28);
                for ($col = 3; $col <= $lastColIndex; $col++) {
                    $sheet->getColumnDimension($this->columnLetter($col))->setWidth(9);
                }
                $sheet->getDefaultRowDimension()->setRowHeight(18);
                for ($row = 7; $row <= $highestRow; $row++) {
                    if (is_numeric(trim((string) $sheet->getCell('A'.$row)->getValue()))) {
                        $sheet->getRowDimension($row)->setRowHeight(28);
                    }
                }

                // Tandai kolom Minggu di header.
                foreach ($this->monthDays as $i => $day) {
                    if (! ($day['is_sunday'] ?? false)) {
                        continue;
                    }
                    $col = $this->columnLetter(3 + $i);
                    $sheet->getStyle($col.$headerRow.':'.$col.$dowRow)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFFF0000'],
                        ],
                        'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    ]);
                }
            },
        ];
    }

    private function statusCodeFromLabel(string $value): string
    {
        if ($value === '') {
            return '';
        }
        foreach (['LIBUR', 'DINAS', 'IZIN', 'SAKIT', 'CUTI', 'WFH'] as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return $prefix;
            }
        }

        $first = strtok($value, " \t\n\r");

        return is_string($first) ? $first : $value;
    }

    /** @return array{bg: string, font: string}|null */
    private function fillForCode(string $code): ?array
    {
        return match ($code) {
            'H' => ['bg' => 'FF86EFAC', 'font' => 'FF14532D'],
            'T' => ['bg' => 'FFFCD34D', 'font' => 'FF78350F'],
            'A' => ['bg' => 'FFFCA5A5', 'font' => 'FF7F1D1D'],
            'P' => ['bg' => 'FFFDBA74', 'font' => 'FF7C2D12'],
            'WFH' => ['bg' => 'FFDDD6FE', 'font' => 'FF5B21B6'],
            'IZIN' => ['bg' => 'FFC7D2FE', 'font' => 'FF312E81'],
            'SAKIT' => ['bg' => 'FFFBCFE8', 'font' => 'FF9D174D'],
            'CUTI' => ['bg' => 'FFA5F3FC', 'font' => 'FF155E75'],
            'LIBUR' => ['bg' => 'FFFFC000', 'font' => 'FF111827'],
            'DINAS' => ['bg' => 'FF7DD3FC', 'font' => 'FF0C4A6E'],
            default => null,
        };
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod).$letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter;
    }
}

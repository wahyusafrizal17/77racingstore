<?php

namespace App\Exports\Speedshop;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JurnalDigitalExport implements WithEvents
{
    private const HEADER_ROW = 6;

    private const DATA_START_ROW = 7;

    private const HEADER_FILL = '991B1B';

    private const LABEL_GRAY = 'D9D9D9';

    private const ZEBRA_FILL = 'F2F2F2';

    private const SALDO_HIGHLIGHT = 'FEE2E2';

    public function __construct(
        protected Collection $rows,
        protected string $titleLine1,
        protected string $titleLine2,
        protected string $ringkasanTanggal,
        protected float $pemasukan,
        protected float $pengeluaran,
        protected float $sisaSaldo,
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
        $sheet->getColumnDimension('A')->setWidth(7);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(26);
        $sheet->getColumnDimension('D')->setWidth(42);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(20);

        $sheet->getRowDimension(1)->setRowHeight(50);
        $sheet->getRowDimension(2)->setRowHeight(22);
        $sheet->getRowDimension(3)->setRowHeight(20);
        $sheet->getRowDimension(4)->setRowHeight(20);

        $logoPath = public_path('images/logo.png');
        if (is_string($logoPath) && $logoPath !== '' && is_file($logoPath)) {
            $drawing = new Drawing;
            $drawing->setName('Logo');
            $drawing->setDescription('Logo');
            $drawing->setPath($logoPath);
            $drawing->setHeight(48);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(6);
            $drawing->setOffsetY(4);
            $drawing->setWorksheet($sheet);
        }

        $sheet->mergeCells('B1:E1');
        $sheet->setCellValue('B1', $this->titleLine1);
        $sheet->getStyle('B1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->mergeCells('B2:E2');
        $sheet->setCellValue('B2', $this->titleLine2);
        $sheet->getStyle('B2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $summaryRows = [
            ['TANGGAL', $this->ringkasanTanggal],
            ['PEMASUKAN', $this->rp($this->pemasukan)],
            ['PENGELUARAN', $this->rpPengeluaran($this->pengeluaran)],
            ['SISA SALDO DIGITAL', $this->rp($this->sisaSaldo)],
        ];
        $sr = 1;
        foreach ($summaryRows as $pair) {
            $sheet->setCellValue('F'.$sr, $pair[0]);
            $sheet->setCellValue('G'.$sr, $pair[1]);
            $sheet->getStyle('F'.$sr)->applyFromArray(array_merge([
                'font' => ['bold' => true, 'size' => 10],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => self::LABEL_GRAY],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ], $this->allBordersArray()));
            $sheet->getStyle('G'.$sr)->applyFromArray(array_merge([
                'font' => ['bold' => true, 'size' => 10],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => $sr === 4 ? self::SALDO_HIGHLIGHT : 'FFFFFFFF'],
                ],
                'alignment' => [
                    'horizontal' => $sr === 1 ? Alignment::HORIZONTAL_LEFT : Alignment::HORIZONTAL_RIGHT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ], $this->allBordersArray()));
            $sr++;
        }
        $sheet->getStyle('F1:G4')->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        $h = self::HEADER_ROW;
        $headers = ['NO', 'TANGGAL', 'AKUN', 'KETERANGAN', 'KREDIT', 'DEBET', 'TOTAL (RP.)'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        foreach ($cols as $i => $col) {
            $sheet->setCellValue($col.$h, $headers[$i] ?? '');
        }
        $sheet->getStyle('A'.$h.':G'.$h)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::HEADER_FILL],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => $this->allBordersArray(),
        ]);
        $sheet->getStyle('D'.$h)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A'.$h)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B'.$h)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $r = self::DATA_START_ROW;
        $idx = 0;
        foreach ($this->rows as $row) {
            $zebra = ($idx % 2) === 1;
            $tanggalStr = Carbon::parse($row->tanggal)->locale('id')->translatedFormat('d F Y');
            $kredit = (float) ($row->kredit ?? 0);
            $debet = (float) ($row->debet ?? 0);
            $saldo = (float) ($row->saldo ?? 0);

            $sheet->setCellValue('A'.$r, (int) ($row->no ?? $idx + 1));
            $sheet->setCellValue('B'.$r, $tanggalStr);
            $sheet->setCellValue('C'.$r, (string) ($row->akun ?? ''));
            $sheet->setCellValue('D'.$r, (string) ($row->keterangan ?? ''));
            $sheet->setCellValue('E'.$r, $kredit > 0 ? $this->rp($kredit) : '');
            $sheet->setCellValue('F'.$r, $debet > 0 ? $this->rp($debet) : '');
            $sheet->setCellValue('G'.$r, $this->rp($saldo));

            $rowFill = [];
            if ($zebra) {
                $rowFill = [
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => self::ZEBRA_FILL],
                    ],
                ];
            }

            $sheet->getStyle('A'.$r.':G'.$r)->applyFromArray(array_merge([
                'font' => ['size' => 10],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_TOP,
                    'wrapText' => true,
                ],
                'borders' => $this->allBordersArray(),
            ], $rowFill));

            $sheet->getStyle('A'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('C'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('D'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('E'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('F'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('G'.$r)->applyFromArray([
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ]);

            $r++;
            $idx++;
        }

        if ($this->rows->isEmpty()) {
            $sheet->mergeCells('A'.$r.':G'.$r);
            $sheet->setCellValue('A'.$r, 'Belum ada data jurnal kas digital pada periode ini.');
            $sheet->getStyle('A'.$r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A'.$r.':G'.$r)->applyFromArray([
                'font' => ['size' => 10, 'italic' => true],
                'borders' => $this->allBordersArray(),
            ]);
        }

        $sheet->freezePane('A'.self::DATA_START_ROW);
    }

    private function rp(float $n): string
    {
        return 'Rp '.number_format($n, 0, ',', '.');
    }

    private function rpPengeluaran(float $n): string
    {
        if ($n <= 0) {
            return 'Rp -';
        }

        return $this->rp($n);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function allBordersArray(): array
    {
        return [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ];
    }
}

<?php

namespace App\Exports\Speedshop;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class LaporanRekapPemisahanDanaExport implements FromArray, ShouldAutoSize, WithEvents
{
    public function __construct(protected array $ctx = []) {}

    public function array(): array
    {
        /** @var list<array<string, mixed>> $weekColumns */
        $weekColumns = $this->ctx['weekColumns'] ?? [];
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->ctx['rows'] ?? [];
        $out = [];

        $header1 = ['No', 'Kategori', 'Pembagian'];
        foreach ($weekColumns as $col) {
            $header1[] = ($col['title'] ?? '').' ('.($col['range_label'] ?? '').')';
            $header1[] = '';
        }
        $header1[] = 'TOTAL';
        $header1[] = '';
        $out[] = $header1;

        $header2 = ['', '', ''];
        foreach ($weekColumns as $col) {
            $header2[] = 'Nominal';
            $header2[] = 'Tanggal Pembayaran';
        }
        $header2[] = 'Nominal';
        $header2[] = 'Tanggal Pembayaran';
        $out[] = $header2;

        foreach ($rows as $row) {
            $label = (string) ($row['label'] ?? '');
            if (! empty($row['rekening_suffix'])) {
                $label .= ' '.(string) $row['rekening_suffix'];
            }

            $subRows = $row['sub_rows'] ?? [];
            if ($subRows !== []) {
                foreach ($subRows as $i => $sub) {
                    $out[] = $this->mapDataRow(
                        $i === 0 ? ($row['no'] ?? '') : '',
                        $i === 0 ? $label : '',
                        (string) ($sub['label'] ?? ''),
                        $sub['cells'] ?? [],
                        $weekColumns,
                    );
                }

                continue;
            }

            $out[] = $this->mapDataRow(
                $row['no'] ?? '',
                $label,
                '',
                $row['cells'] ?? [],
                $weekColumns,
            );
        }

        return $out;
    }

    /**
     * @param  list<array<string, mixed>>  $weekColumns
     * @param  array<string, mixed>  $cells
     * @return list<mixed>
     */
    private function mapDataRow(mixed $no, string $label, string $pembagian, array $cells, array $weekColumns): array
    {
        $line = [$no, $label, $pembagian !== '' ? $pembagian : '—'];
        foreach ($weekColumns as $col) {
            $cell = $cells[$col['key']] ?? [];
            $nominal = (int) ($cell['nominal'] ?? 0);
            $line[] = $nominal > 0 ? $nominal : '';
            $line[] = ($cell['tanggal'] ?? '—') !== '—' ? (string) ($cell['tanggal'] ?? '') : '';
        }
        $total = $cells['total'] ?? [];
        $totalNominal = (int) ($total['nominal'] ?? 0);
        $line[] = $totalNominal > 0 ? $totalNominal : '';
        $line[] = ($total['tanggal'] ?? '—') !== '—' ? (string) ($total['tanggal'] ?? '') : '';

        return $line;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $sheet->getHighestColumn();

                foreach (['1', '2'] as $row) {
                    $sheet->getStyle('A'.$row.':'.$highestColumn.$row)->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['argb' => 'FFFFFF'],
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'EF4444'],
                        ],
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                    ]);
                }

                foreach (range('A', $highestColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}

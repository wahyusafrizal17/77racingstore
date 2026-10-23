<?php

namespace App\Exports\Speedshop;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Models\SpeedshopPoTagihan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TagihanPoSpeedshopExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings
{
    use WithExcelHeaderStyle;

    /** @param Collection<int, SpeedshopPoTagihan> $rows */
    public function __construct(
        protected Collection $rows,
        protected string $periodeLabel,
        protected array $stats = [],
        protected string $cabangLabel = 'Speedshop',
    ) {}

    public function array(): array
    {
        $rows = [
            ['Tagihan PO '.$this->cabangLabel, $this->periodeLabel],
            [
                'Total: '.number_format((int) ($this->stats['total'] ?? 0), 0, ',', '.'),
                'Belum lunas: '.number_format((int) ($this->stats['belum_lunas'] ?? 0), 0, ',', '.'),
                'Menunggu konfirmasi: '.number_format((int) ($this->stats['menunggu_konfirmasi'] ?? 0), 0, ',', '.'),
                'Lunas: '.number_format((int) ($this->stats['lunas'] ?? 0), 0, ',', '.'),
            ],
            [''],
        ];

        $totalNominal = 0;
        $no = 1;
        $tz = config('app.timezone');

        foreach ($this->rows as $row) {
            $totalNominal += (int) $row->total_nominal;
            $rows[] = [
                $no++,
                (string) $row->kode,
                (string) ($row->purchaseOrder?->kode ?? '—'),
                (string) ($row->warehouse?->nama ?? '—'),
                $row->tanggal_tagihan?->timezone($tz)->translatedFormat('j M Y') ?? '—',
                (int) $row->total_nominal,
                SpeedshopPoTagihan::statusLabel((string) $row->status),
            ];
        }

        $rows[] = ['', '', '', '', 'TOTAL', $totalNominal, ''];

        return $rows;
    }

    public function headings(): array
    {
        return [
            'No.',
            'No. Tagihan',
            'PO',
            'Warehouse',
            'Tanggal',
            'Total (Rp)',
            'Status',
        ];
    }
}

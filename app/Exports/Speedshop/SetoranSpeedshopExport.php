<?php

namespace App\Exports\Speedshop;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Models\SpeedshopFinanceSetoran;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SetoranSpeedshopExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings
{
    use WithExcelHeaderStyle;

    /** @param Collection<int, SpeedshopFinanceSetoran> $rows */
    public function __construct(
        protected Collection $rows,
        protected string $periodeLabel,
        protected array $stats = [],
        protected string $cabangLabel = 'Speedshop',
    ) {}

    public function array(): array
    {
        $rows = [
            ['Data Setoran '.$this->cabangLabel, $this->periodeLabel],
            [
                'Total: '.number_format((int) ($this->stats['total'] ?? 0), 0, ',', '.'),
                'Menunggu: '.number_format((int) ($this->stats['pending'] ?? 0), 0, ',', '.'),
                'Selesai: '.number_format((int) ($this->stats['completed'] ?? 0), 0, ',', '.'),
                'Ditolak: '.number_format((int) ($this->stats['rejected'] ?? 0), 0, ',', '.'),
            ],
            [''],
        ];

        $totalNominal = 0;
        $no = 1;
        $tz = config('app.timezone');

        foreach ($this->rows as $row) {
            $totalNominal += $row->totalNominalSetor();
            $tgl = $row->tanggal_setor?->timezone($tz);
            $dicatat = $row->isPending() || $row->isRejected()
                ? $row->created_at?->timezone($tz)
                : ($row->completed_at?->timezone($tz) ?? $row->updated_at?->timezone($tz));

            $rows[] = [
                $no++,
                $tgl?->translatedFormat('j M Y') ?? '—',
                $this->statusLabel($row),
                (string) ($row->user?->name ?? '—'),
                $dicatat?->translatedFormat('j M Y, H:i') ?? '—',
                $row->totalNominalSetor(),
            ];
        }

        $rows[] = ['', '', '', '', 'TOTAL', $totalNominal];

        return $rows;
    }

    public function headings(): array
    {
        return [
            'No.',
            'Tanggal setor',
            'Status',
            'Diajukan oleh',
            'Dicatat',
            'Nominal (Rp)',
        ];
    }

    private function statusLabel(SpeedshopFinanceSetoran $row): string
    {
        if ($row->isPending()) {
            return 'Menunggu';
        }
        if ($row->isRejected()) {
            return 'Ditolak';
        }

        return 'Selesai';
    }
}

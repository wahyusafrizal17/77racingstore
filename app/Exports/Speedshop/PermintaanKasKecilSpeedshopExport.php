<?php

namespace App\Exports\Speedshop;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Models\SpeedshopFinancePermintaanKasKecil;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PermintaanKasKecilSpeedshopExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings
{
    use WithExcelHeaderStyle;

    /** @param Collection<int, SpeedshopFinancePermintaanKasKecil> $rows */
    public function __construct(
        protected Collection $rows,
        protected string $periodeLabel,
        protected array $stats = [],
        protected bool $hasCatatanCol = false,
        protected string $cabangLabel = 'Speedshop',
    ) {}

    public function array(): array
    {
        $rows = [
            ['Permintaan Kas Kecil '.$this->cabangLabel, $this->periodeLabel],
            [
                'Total: '.number_format((int) ($this->stats['total'] ?? 0), 0, ',', '.'),
                'Menunggu: '.number_format((int) ($this->stats['pending'] ?? 0), 0, ',', '.'),
                'Disetujui: '.number_format((int) ($this->stats['completed'] ?? 0), 0, ',', '.'),
                'Ditolak: '.number_format((int) ($this->stats['rejected'] ?? 0), 0, ',', '.'),
            ],
            [''],
        ];

        $totalNominal = 0;
        $no = 1;
        $tz = config('app.timezone');

        foreach ($this->rows as $row) {
            $totalNominal += (int) $row->nominal;
            $line = [
                $no++,
                $row->tanggal?->timezone($tz)->translatedFormat('j M Y') ?? '—',
                $this->statusLabel((string) $row->status),
                (int) $row->nominal,
                $row->created_at?->timezone($tz)->translatedFormat('j M Y, H:i') ?? '—',
                (string) ($row->requester?->name ?? '—'),
            ];

            if ($this->hasCatatanCol) {
                $line[] = filled($row->catatan) ? (string) $row->catatan : '—';
            }

            $line[] = $this->verifikasiLabel($row);

            $rows[] = $line;
        }

        $totalRow = ['', '', '', $totalNominal, 'TOTAL', ''];
        if ($this->hasCatatanCol) {
            $totalRow[] = '';
        }
        $totalRow[] = '';
        $rows[] = $totalRow;

        return $rows;
    }

    public function headings(): array
    {
        $headings = [
            'No.',
            'Tanggal jurnal',
            'Status',
            'Nominal (Rp)',
            'Diajukan',
            'Pemohon',
        ];

        if ($this->hasCatatanCol) {
            $headings[] = 'Alasan / catatan';
        }

        $headings[] = 'Verifikasi';

        return $headings;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            SpeedshopFinancePermintaanKasKecil::STATUS_COMPLETED => 'Disetujui',
            SpeedshopFinancePermintaanKasKecil::STATUS_REJECTED => 'Ditolak',
            default => 'Menunggu',
        };
    }

    private function verifikasiLabel(SpeedshopFinancePermintaanKasKecil $row): string
    {
        $tz = config('app.timezone');

        if ($row->status === SpeedshopFinancePermintaanKasKecil::STATUS_COMPLETED) {
            return ($row->approver?->name ?? 'Accounting').' · '.($row->approved_at?->timezone($tz)->translatedFormat('j M Y, H:i') ?? '—');
        }

        if ($row->status === SpeedshopFinancePermintaanKasKecil::STATUS_REJECTED) {
            $label = ($row->approver?->name ?? 'Accounting').' · '.($row->rejected_at?->timezone($tz)->translatedFormat('j M Y, H:i') ?? '—');
            if (filled($row->rejected_reason)) {
                $label .= ' — '.$row->rejected_reason;
            }

            return $label;
        }

        return '—';
    }
}

<?php

namespace App\Exports\Speedshop;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Models\SpeedshopRincianBiayaAdjustment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BopPengembalianSpeedshopExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings
{
    use WithExcelHeaderStyle;

    /** @param Collection<int, SpeedshopRincianBiayaAdjustment> $rows */
    public function __construct(
        protected Collection $rows,
        protected string $periodeLabel,
        protected array $stats = [],
        protected string $cabangLabel = 'Speedshop',
    ) {}

    public function array(): array
    {
        $rows = [
            ['Pengembalian Dana BOP '.$this->cabangLabel, $this->periodeLabel],
            [
                'Penyesuaian: '.number_format((int) ($this->stats['count'] ?? 0), 0, ',', '.'),
                'Total pengurangan: Rp '.number_format((int) ($this->stats['total_pengurangan'] ?? 0), 0, ',', '.'),
            ],
            [''],
        ];

        $totalPengurangan = 0;
        $totalAdmin = 0;
        $no = 1;
        $tz = config('app.timezone');

        foreach ($this->rows as $adj) {
            $entry = $adj->rincianBiayaEntry;
            $totalPengurangan += (int) $adj->nominal_pengurangan;
            $totalAdmin += (int) $adj->biaya_admin;

            $rows[] = [
                $no++,
                $adj->tanggal_pengembalian?->timezone($tz)->translatedFormat('j M Y') ?? '—',
                $entry?->tanggal?->timezone($tz)->translatedFormat('j M Y') ?? '—',
                (string) ($entry?->no_akun ?? '—'),
                (string) ($entry?->nama_akun ?? '—'),
                (string) ($entry?->keterangan ?? '—'),
                (int) $adj->nominal_pengurangan,
                (int) $adj->biaya_admin,
                $entry ? (int) $entry->nominalEfektif() : 0,
                filled($adj->catatan) ? (string) $adj->catatan : '—',
                (string) ($adj->createdByUser?->name ?? '—'),
            ];
        }

        $rows[] = ['', '', '', '', '', 'TOTAL', $totalPengurangan, $totalAdmin, '', '', ''];

        return $rows;
    }

    public function headings(): array
    {
        return [
            'No.',
            'Tgl refund',
            'Tgl BOP',
            'No. Akun',
            'Nama Akun',
            'Keterangan BOP',
            'Pengurangan (Rp)',
            'Admin (Rp)',
            'BOP efektif (Rp)',
            'Catatan',
            'Oleh',
        ];
    }
}

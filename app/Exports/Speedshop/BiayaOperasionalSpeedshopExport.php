<?php

namespace App\Exports\Speedshop;

use App\Exports\Concerns\WithExcelHeaderStyle;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BiayaOperasionalSpeedshopExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings
{
    use WithExcelHeaderStyle;

    /** @param Collection<int, object> $rows */
    public function __construct(
        protected Collection $rows,
        protected string $periodeLabel,
        protected array $stats = [],
        protected string $cabangLabel = 'Speedshop',
    ) {}

    public function array(): array
    {
        $rows = [
            ['Biaya Operasional '.$this->cabangLabel, $this->periodeLabel],
            [
                'Menunggu verifikasi: '.number_format((int) ($this->stats['pending'] ?? 0), 0, ',', '.'),
                'Tercatat / disetujui: '.number_format((int) ($this->stats['disetujui'] ?? 0), 0, ',', '.'),
                'Total biaya: Rp '.number_format((int) ($this->stats['total_nominal'] ?? 0), 0, ',', '.'),
            ],
            [''],
        ];

        $totalTunai = 0;
        $totalTransfer = 0;
        $totalBiaya = 0;
        $no = 1;

        foreach ($this->rows as $row) {
            $tunai = (int) ($row->tunai ?? 0);
            $transfer = (int) ($row->transfer ?? 0);
            $total = (int) ($row->total ?? ($tunai + $transfer));
            if ($row->counts_toward_total ?? false) {
                $totalTunai += $tunai;
                $totalTransfer += $transfer;
                $totalBiaya += $total;
            }

            $rows[] = [
                $no++,
                (string) ($row->tanggal_label ?? '—'),
                (string) ($row->jenis_label ?? '—'),
                (string) ($row->no_akun ?? '—'),
                (string) ($row->nama_akun ?? '—'),
                (string) ($row->keterangan ?? '—'),
                (string) ($row->pic ?? '—'),
                (string) ($row->pengaju ?? '—'),
                (string) ($row->status_label ?? '—'),
                $tunai > 0 ? $tunai : '',
                $transfer > 0 ? $transfer : '',
                filled($row->rekening_label ?? null) ? (string) $row->rekening_label : '',
                $total > 0 ? $total : '',
            ];
        }

        $rows[] = ['', '', '', '', '', '', '', '', 'TOTAL', $totalTunai, $totalTransfer, '', $totalBiaya];

        return $rows;
    }

    public function headings(): array
    {
        return [
            'No.',
            'Tanggal',
            'Jenis',
            'No. Akun',
            'Nama Akun',
            'Keterangan',
            'Pemohon',
            'Pengaju',
            'Status',
            'Tunai (Rp)',
            'Transfer (Rp)',
            'Rekening',
            'Total (Rp)',
        ];
    }
}

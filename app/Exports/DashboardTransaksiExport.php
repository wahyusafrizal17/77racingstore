<?php

namespace App\Exports;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Models\ServiceOrder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class DashboardTransaksiExport implements FromArray, WithHeadings, WithEvents, ShouldAutoSize, WithColumnFormatting
{
    use WithExcelHeaderStyle;

    public function __construct(private array $rows)
    {
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->applyHeaderStyle($event);
                $this->appendPeriodTotalsRow($event);
            },
        ];
    }

    protected function appendPeriodTotalsRow(AfterSheet $event): void
    {
        if ($this->rows === []) {
            return;
        }

        $sheet = $event->sheet->getDelegate();
        $lastDataRow = count($this->rows) + 1;
        $totalsRow = $lastDataRow + 2;

        $sheet->setCellValue('N'.$totalsRow, 'TOTAL PERIODE');
        foreach (['P', 'Q', 'R', 'S', 'T', 'U'] as $col) {
            $sheet->setCellValue($col.$totalsRow, "=SUM({$col}2:{$col}{$lastDataRow})");
        }

        $sheet->getStyle('N'.$totalsRow.':U'.$totalsRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'ECFDF5'],
            ],
        ]);
    }

    public function array(): array
    {
        return $this->rows;
    }

    /**
     * Format Rupiah untuk kolom nominal (nilai tetap numerik agar bisa di-SUM di Excel).
     *
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        $rupiah = '"Rp" #,##0_-';

        return [
            'P' => $rupiah,
            'Q' => $rupiah,
            'R' => $rupiah,
            'S' => $rupiah,
            'T' => $rupiah,
            'U' => $rupiah,
        ];
    }

    public function headings(): array
    {
        return [
            'No.',
            'Order ID',
            'No. Invoice',
            'Tgl. Invoice',
            'Jenis Order',
            'Kategori Service',
            'Sumber Kedatangan',
            'Nama Customer',
            'Nama mekanik',
            'No. HP',
            'No. Polisi',
            'Jenis Kendaraan',
            'KM Kendaraan',
            'Tahun Kendaraan',
            'Metode Pembayaran',
            'Pendapatan Omset',
            'Charge',
            'PPN',
            'Total Tagihan',
            'Total HPP',
            'Total Keuntungan',
        ];
    }

    /**
     * @return array<int, int|string|float> (21 kolom)
     */
    public static function rowFromOrder(ServiceOrder $so, int $no): array
    {
        $jenisOrder = $so->jenisOrderDisplay();
        $kategoriService = $so->kategoriServiceDisplay();

        $sk = $so->sumber_kedatangan;
        $sumberLabel = ($sk !== null && $sk !== '' && isset(ServiceOrder::SUMBER_KEDATANGAN[$sk]))
            ? ServiceOrder::SUMBER_KEDATANGAN[$sk]
            : '-';

        $metodeLabel = $so->pembayaranDisplayLabel();

        $k = $so->kendaraan;
        $jenisKendaraan = $k ? trim(implode(' ', array_filter([(string) ($k->merk ?? ''), (string) ($k->tipe ?? '')]))) : '';
        $jenisKendaraan = $jenisKendaraan !== '' ? $jenisKendaraan : '-';

        $tglInvoice = $so->tanggal_selesai ?? $so->invoice?->tanggal ?? $so->tanggal_masuk;
        $tglStr = $tglInvoice ? $tglInvoice->format('d/m/Y') : '-';

        $noInvoice = $so->invoice?->kode_invoice ?? ('WO-'.$so->kode_servis);

        $totalHpp = (int) round($so->products->sum(function ($p) {
            $qty = (int) ($p->pivot->qty ?? 0);
            $unit = (float) ($p->hpp ?? $p->harga_pembelian ?? 0);

            return $qty * $unit;
        }));

        $omset = $so->invoiceOmsetBreakdown();
        $totalKeuntungan = $omset['pendapatan_omset'] - $totalHpp;

        // Angka bulat tanpa separator ribuan — format "2.500" / "2,500" di Excel sering salah dibaca sebagai desimal.
        $kmValue = ($so->kilometer !== null && $so->kilometer !== '')
            ? (int) round((float) $so->kilometer)
            : '-';

        $tahun = $so->kendaraan?->tahun;
        $tahunStr = $tahun !== null && $tahun !== '' ? (string) $tahun : '-';

        return [
            $no,
            $so->id,
            $noInvoice,
            $tglStr,
            $jenisOrder,
            $kategoriService,
            $sumberLabel,
            $so->pelanggan?->nama ?? '-',
            $so->mekanik?->nama ?? '-',
            $so->pelanggan?->no_hp ?? '-',
            $so->kendaraan?->nomor_polisi ?? '-',
            $jenisKendaraan,
            $kmValue,
            $tahunStr,
            $metodeLabel,
            $omset['pendapatan_omset'],
            $omset['charge'],
            $omset['ppn'],
            $omset['total_tagihan'],
            $totalHpp,
            $totalKeuntungan,
        ];
    }
}

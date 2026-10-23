<?php

namespace App\Exports;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Models\ServiceOrder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DashboardTransaksiDetailExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings
{
    use WithExcelHeaderStyle;

    public function __construct(private array $rows) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'No Invoice',
            'No',
            'Kode Produk',
            'Nama Produk',
            'Harga Satuan',
            'Jumlah',
            'Diskon',
            'Subtotal',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        $rupiah = '"Rp" #,##0_-';

        return [
            'E' => $rupiah,
            'G' => $rupiah,
            'H' => $rupiah,
        ];
    }

    /**
     * @param  iterable<ServiceOrder>  $orders
     * @return array<int, array<int, int|string>>
     */
    public static function rowsFromOrders(iterable $orders): array
    {
        $rows = [];

        foreach ($orders as $so) {
            $noInvoice = $so->invoice?->kode_invoice ?? ('WO-'.$so->kode_servis);
            $lines = self::lineItemsForOrder($so);
            $no = 0;

            if ($lines === []) {
                $no++;
                $rows[] = [
                    $noInvoice,
                    $no,
                    '-',
                    '-',
                    0,
                    0,
                    0,
                    0,
                ];
                self::appendInvoiceSummaryRows($rows, $noInvoice, $so);

                continue;
            }

            foreach ($lines as $line) {
                $no++;
                $harga = (int) $line['harga'];
                $jumlah = (int) $line['jumlah'];
                $diskon = (int) ($line['diskon'] ?? 0);
                $bruto = $harga * $jumlah;
                $subtotal = max(0, $bruto - $diskon);
                $rows[] = [
                    $noInvoice,
                    $no,
                    $line['kode'],
                    $line['nama'],
                    $harga,
                    $jumlah,
                    $diskon,
                    $subtotal,
                ];
            }

            self::appendInvoiceSummaryRows($rows, $noInvoice, $so);
        }

        return $rows;
    }

    /**
     * @param  list<array<int, int|string>>  $rows
     */
    private static function appendInvoiceSummaryRows(array &$rows, string $noInvoice, ServiceOrder $so): void
    {
        $omset = $so->invoiceOmsetBreakdown();

        $rows[] = [$noInvoice, '', '', 'Charge', '', '', '', $omset['charge']];
        $rows[] = [$noInvoice, '', '', 'PPN', '', '', '', $omset['ppn']];
    }

    /**
     * @return list<array{kode: string, nama: string, jumlah: int, harga: int, diskon: int}>
     */
    private static function lineItemsForOrder(ServiceOrder $so): array
    {
        $lines = [];

        foreach ($so->jasaServis as $j) {
            $q = (int) ($j->pivot->qty ?? 1);
            $b = (int) ($j->pivot->biaya ?? 0);
            $d = (int) ($j->pivot->diskon ?? 0);
            $lines[] = [
                'kode' => '-',
                'nama' => (string) $j->nama,
                'jumlah' => $q,
                'harga' => $b,
                'diskon' => $d,
            ];
        }

        foreach ($so->products as $p) {
            $q = (int) ($p->pivot->qty ?? 0);
            $h = (int) ($p->pivot->harga ?? 0);
            $d = (int) ($p->pivot->diskon ?? 0);
            $lines[] = [
                'kode' => ($p->kode_produk !== null && $p->kode_produk !== '') ? (string) $p->kode_produk : '-',
                'nama' => (string) $p->nama_produk,
                'jumlah' => $q,
                'harga' => $h,
                'diskon' => $d,
            ];
        }

        return $lines;
    }
}

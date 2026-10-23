<?php

namespace App\Exports\Speedshop;

use App\Exports\Concerns\WithExcelHeaderStyle;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LaporanBiayaExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    use WithExcelHeaderStyle;

    public function __construct(
        protected array $ctx = []
    ) {}

    public function collection()
    {
        $dari = $this->ctx['dari'] ?? now()->startOfMonth();
        $sampai = $this->ctx['sampai'] ?? now()->endOfMonth();
        $realtimeDate = $this->ctx['realtimeDate'] ?? $sampai->copy()->startOfDay();
        $periode = $this->ctx['periode'] ?? [];
        $realtime = $this->ctx['realtime'] ?? $periode;

        if (! $dari instanceof Carbon) {
            $dari = Carbon::parse($dari)->startOfDay();
        }
        if (! $sampai instanceof Carbon) {
            $sampai = Carbon::parse($sampai)->endOfDay();
        }
        if (! $realtimeDate instanceof Carbon) {
            $realtimeDate = Carbon::parse($realtimeDate)->startOfDay();
        }

        return collect([
            $this->mapExportRow('Real time', $realtimeDate->format('d/m/y'), $realtime),
            $this->mapExportRow(
                'Bulan berjalan (s/d)',
                $dari->format('d M Y').' – '.$sampai->format('d M Y'),
                $periode,
            ),
        ]);
    }

    public function headings(): array
    {
        return [
            'Jenis data',
            'Tanggal / periode',
            'Total omset kotor',
            'Unit entry',
            'Pendapatan jasa',
            'Part',
            'HPP part',
            'Profit part',
            'Diskon',
            'Profit',
            'BOP Operasional',
            'BOP Non Operasional',
            'Gross profit',
        ];
    }

    public function map($row): array
    {
        return [
            $row->jenis,
            $row->tanggal,
            $row->omzet_kotor,
            $row->unit_entry,
            $row->pendapatan_jasa,
            $row->pendapatan_part,
            $row->hpp_part,
            $row->profit_part,
            $row->diskon,
            $row->profit,
            $row->bop_operasional,
            $row->bop_non_operasional,
            $row->profit_bersih,
        ];
    }

    /**
     * @param  array<string, float|int>  $row
     */
    private function mapExportRow(string $jenis, string $tanggal, array $row): object
    {
        return (object) [
            'jenis' => $jenis,
            'tanggal' => $tanggal,
            'omzet_kotor' => $row['omzet_kotor'] ?? 0,
            'unit_entry' => $row['unit_entry'] ?? 0,
            'pendapatan_jasa' => $row['jasa'] ?? 0,
            'pendapatan_part' => $row['part'] ?? 0,
            'hpp_part' => $row['hpp_part'] ?? 0,
            'profit_part' => $row['profit_part'] ?? 0,
            'diskon' => $row['diskon'] ?? 0,
            'profit' => $row['profit'] ?? 0,
            'bop_operasional' => $row['bop_operasional'] ?? 0,
            'bop_non_operasional' => $row['bop_non_operasional'] ?? 0,
            'profit_bersih' => $row['profit_bersih'] ?? 0,
        ];
    }
}

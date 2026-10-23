<?php

namespace App\Exports\Speedshop;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Models\ServiceOrder;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LaporanMekanikPerformanceExport implements FromCollection, WithHeadings, WithMapping, WithEvents, ShouldAutoSize
{
    use WithExcelHeaderStyle;
    public function __construct(
        protected array $filters = []
    ) {}

    public function collection()
    {
        $warehouseId = auth()->user()->activeWarehouseId();
        $dari = !empty($this->filters['dari']) ? Carbon::parse($this->filters['dari'])->startOfDay() : now()->startOfMonth();
        $sampai = !empty($this->filters['sampai']) ? Carbon::parse($this->filters['sampai'])->endOfDay() : now()->endOfDay();

        return ServiceOrder::with(['mekanik', 'jasaServis', 'products'])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->whereBetween('tanggal_masuk', [$dari, $sampai])
            ->whereIn('status', ['proses', 'selesai'])
            ->whereNotNull('mekanik_id')
            ->get()
            ->groupBy('mekanik_id')
            ->map(function ($orders, $mekanikId) {
                $mekanik = $orders->first()->mekanik;
                $totalJasa = 0;
                $totalSparepart = 0;
                foreach ($orders as $so) {
                    $totalJasa += $so->jasaServis->sum('pivot.biaya');
                    $totalSparepart += $so->products->sum(fn ($p) => $p->pivot->qty * $p->pivot->harga);
                }
                return (object) [
                    'mekanik_nama' => $mekanik?->nama ?? '-',
                    'total_order' => $orders->count(),
                    'total_jasa' => $totalJasa,
                    'total_sparepart' => $totalSparepart,
                    'total' => $totalJasa + $totalSparepart,
                ];
            })
            ->sortByDesc('total_order')
            ->values();
    }

    public function headings(): array
    {
        return ['Mekanik', 'Total Order', 'Total Jasa (Rp)', 'Total Sparepart (Rp)', 'Total (Rp)'];
    }

    public function map($row): array
    {
        return [
            $row->mekanik_nama,
            $row->total_order,
            number_format($row->total_jasa, 0, ',', '.'),
            number_format($row->total_sparepart, 0, ',', '.'),
            number_format($row->total, 0, ',', '.'),
        ];
    }
}

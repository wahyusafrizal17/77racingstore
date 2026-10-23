<?php

namespace App\Events;

use App\Models\ServiceOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkOrderQueueUpdated implements ShouldBroadcast, ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $warehouseId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('speedshop.queue.'.$this->warehouseId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'WorkOrderQueueUpdated';
    }

    public function broadcastWith(): array
    {
        $base = ServiceOrder::with(['pelanggan', 'kendaraan', 'mekanik'])
            ->where(function ($q) {
                $q->where('warehouse_id', $this->warehouseId)->orWhereNull('warehouse_id');
            });

        $inProcess = (clone $base)
            ->where('status', 'proses')
            ->orderByDesc('tanggal_masuk')
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'kode_servis' => $o->kode_servis,
                'pelanggan' => $o->pelanggan?->nama,
                'no_hp' => $o->pelanggan?->no_hp,
                'kendaraan' => $o->kendaraan?->nomor_polisi,
                'mekanik' => $o->mekanik?->nama,
                'tanggal_masuk' => $o->tanggal_masuk?->format('Y-m-d'),
            ])
            ->values()
            ->all();

        $queue = (clone $base)
            ->where('status', 'antri')
            ->orderBy('tanggal_masuk')
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'kode_servis' => $o->kode_servis,
                'pelanggan' => $o->pelanggan?->nama,
                'no_hp' => $o->pelanggan?->no_hp,
                'kendaraan' => $o->kendaraan?->nomor_polisi,
                'tanggal_masuk' => $o->tanggal_masuk?->format('Y-m-d'),
            ])
            ->values()
            ->all();

        return [
            'warehouse_id' => $this->warehouseId,
            'in_process' => $inProcess,
            'queue' => $queue,
            'generated_at' => now()->toISOString(),
        ];
    }
}

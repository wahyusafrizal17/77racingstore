<?php

namespace App\Services;

use App\Models\Speedshop;
use App\Models\SpeedshopRincianBiayaAdjustment;
use App\Models\SpeedshopRincianBiayaEntry;
use App\Support\KeuanganScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SpeedshopRincianBiayaAdjustmentService
{
    public function apply(
        Request $request,
        SpeedshopRincianBiayaEntry $entry,
        KeuanganScope $scope,
        SpeedshopFinanceService $finance,
        array $validated,
    ): SpeedshopRincianBiayaAdjustment {
        if (! Schema::hasTable('speedshop_rincian_biaya_adjustments')) {
            throw new \RuntimeException('Migrasi pengembalian dana BOP belum dijalankan.');
        }

        if ((int) ($entry->warehouse_id ?? 0) > 0 || ! $entry->isApprovedForReporting()) {
            throw new \InvalidArgumentException('Hanya BOP speedshop yang sudah tercatat yang dapat disesuaikan.');
        }

        if ((int) ($entry->finance_ledger_id ?? 0) <= 0) {
            throw new \InvalidArgumentException('BOP belum memiliki jurnal keuangan — penyesuaian tidak dapat diproses.');
        }

        if (! $scope->ownsRincianBiaya($entry)) {
            throw new \InvalidArgumentException('Entri biaya tidak termasuk cabang aktif.');
        }

        $pengurangan = (int) $validated['nominal_pengurangan'];
        $biayaAdmin = (int) ($validated['biaya_admin'] ?? 0);
        $totalKurang = $pengurangan + $biayaAdmin;

        if ($pengurangan < 1) {
            throw new \InvalidArgumentException('Nominal pengurangan harus lebih dari Rp 0.');
        }

        if ($biayaAdmin < 0) {
            throw new \InvalidArgumentException('Biaya admin tidak valid.');
        }

        if ($totalKurang > $entry->sisaDapatDikurangi()) {
            throw new \InvalidArgumentException(
                'Total penyesuaian melebihi sisa BOP (Rp '.number_format($entry->sisaDapatDikurangi(), 0, ',', '.').').'
            );
        }

        $tanggalPengembalian = (string) $validated['tanggal_pengembalian'];
        $buktiPath = $this->storeBuktiOrNull($request, (int) $entry->speedshop_id);
        $userId = (int) auth()->id();

        return DB::transaction(function () use (
            $entry,
            $scope,
            $finance,
            $validated,
            $pengurangan,
            $biayaAdmin,
            $tanggalPengembalian,
            $buktiPath,
            $userId,
        ) {
            $locked = SpeedshopRincianBiayaEntry::query()
                ->whereKey($entry->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($pengurangan + $biayaAdmin > $locked->sisaDapatDikurangi()) {
                throw new \InvalidArgumentException('Total penyesuaian melebihi sisa BOP.');
            }

            $adjustment = SpeedshopRincianBiayaAdjustment::query()->create([
                'speedshop_id' => (int) $locked->speedshop_id,
                'rincian_biaya_entry_id' => (int) $locked->id,
                'tanggal_pengembalian' => $tanggalPengembalian,
                'nominal_pengurangan' => $pengurangan,
                'biaya_admin' => $biayaAdmin,
                'catatan' => isset($validated['catatan']) ? trim((string) $validated['catatan']) : null,
                'bukti_path' => $buktiPath,
                'created_by' => $userId,
            ]);

            $ledger = $finance->recordBopPengembalianMasukForScope(
                $scope,
                $locked,
                $adjustment,
                $tanggalPengembalian,
                $pengurangan,
                $userId,
            );

            $adjustment->update(['finance_ledger_id' => (int) $ledger->id]);

            $locked = $locked->fresh(['adjustments']);
            app(SpeedshopJurnalAutomationService::class)->syncRincianBiayaPengeluaranJurnal($locked);

            return $adjustment->fresh(['rincianBiayaEntry', 'createdByUser']);
        });
    }

    private function storeBuktiOrNull(Request $request, int $speedshopId): ?string
    {
        if (! $request->hasFile('bukti')) {
            return null;
        }

        $file = $request->file('bukti');
        if (! $file || ! $file->isValid()) {
            throw new \InvalidArgumentException('File bukti tidak valid atau gagal diunggah.');
        }

        $sp = Speedshop::query()->find($speedshopId);
        $outletCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $sp?->invoiceOutletCode() ?? '') ?: 'UNK');
        $prefix = 'speedshop/'.$outletCode.'-'.$speedshopId.'/bop-pengembalian';
        $disk = (string) config('filesystems.upload_disk', 'public');
        $stored = $file->store($prefix, $disk);

        if (! is_string($stored) || $stored === '') {
            throw new \InvalidArgumentException('Gagal menyimpan file bukti.');
        }

        return $stored;
    }
}

<?php

use App\Models\AppNotification;
use App\Models\Speedshop;
use App\Models\TransaksiOffline;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Isi speedshop_id untuk transaksi lama (nama seed "Speed Shop …" tidak sama persis dengan master "Speedshop …").
     * Buat notifikasi sekali untuk transaksi yang masih menunggu verifikasi (belum ada notifikasi dengan link sama).
     */
    public function up(): void
    {
        foreach (TransaksiOffline::where('tujuan', 'speedshop')->whereNull('speedshop_id')->cursor() as $t) {
            $id = Speedshop::findIdByNamaTokoForOffline($t->nama_toko);
            if ($id) {
                $t->forceFill(['speedshop_id' => $id])->save();
            }
        }

        foreach (TransaksiOffline::with('warehouse')
            ->where('tujuan', 'speedshop')
            ->whereNull('verified_at')
            ->whereNotNull('speedshop_id')
            ->cursor() as $t) {
            $link = route('speedshop.stock-in.transaksi-offline.show', $t);
            if (AppNotification::where('link', $link)->exists()) {
                continue;
            }
            $warehouseName = $t->warehouse?->nama ?? 'Warehouse';
            foreach (User::where('speedshop_id', $t->speedshop_id)->get() as $user) {
                AppNotification::create([
                    'user_id' => $user->id,
                    'type' => 'transaksi_offline_speedshop',
                    'title' => 'Transaksi Offline dari Warehouse Menunggu Verifikasi',
                    'message' => "{$warehouseName} mengirim transaksi {$t->no_transaksi} ke Speedshop Anda. Verifikasi agar stok masuk ke stok produk Speedshop.",
                    'data' => ['transaksi_offline_id' => $t->id],
                    'link' => $link,
                ]);
            }
        }
    }
};

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Speedshop extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'no_hp',
        'alamat',
        'kota_id',
        'warehouse_id',
        'mutasi_warehouse_id',
        'invoice_charge_percent',
        'invoice_charge_mode',
        'invoice_charge_rupiah',
        'invoice_ppn_percent',
        'invoice_ppn_mode',
        'invoice_ppn_rupiah',
        'stok_audit_percent',
    ];

    protected function casts(): array
    {
        return [
            'invoice_charge_percent' => 'decimal:2',
            'invoice_charge_rupiah' => 'decimal:0',
            'invoice_ppn_percent' => 'decimal:2',
            'invoice_ppn_rupiah' => 'decimal:0',
            'stok_audit_percent' => 'decimal:2',
        ];
    }

    public function kota()
    {
        return $this->belongsTo(Kota::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** Warehouse yang dipakai sebagai tujuan mutasi (biasanya warehouse dengan nama sama) */
    public function mutasiWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'mutasi_warehouse_id');
    }

    /**
     * Sufiks cabang untuk nomor invoice speedshop: Bandung KTP, Bogor CLK, Depok CSK, Bekasi BKS.
     */
    public function invoiceOutletCode(): string
    {
        $nama = mb_strtolower((string) ($this->nama ?? ''));
        if (str_contains($nama, 'bogor')) {
            return 'CLK';
        }
        if (str_contains($nama, 'depok')) {
            return 'CSK';
        }
        if (str_contains($nama, 'bandung')) {
            return 'KTP';
        }
        if (str_contains($nama, 'bekasi')) {
            return 'BKS';
        }

        $this->loadMissing('kota');
        $kota = mb_strtolower((string) ($this->kota?->nama ?? ''));
        if (str_contains($kota, 'bogor')) {
            return 'CLK';
        }
        if (str_contains($kota, 'depok')) {
            return 'CSK';
        }
        if (str_contains($kota, 'bandung')) {
            return 'KTP';
        }
        if (str_contains($kota, 'bekasi')) {
            return 'BKS';
        }

        return 'UNK';
    }

    /**
     * Sufiks cabang (KTP/CLK/CSK/UNK) dari pelanggan (speedshop terikat) atau speedshop aktif user.
     */
    public static function resolveOutletSuffixFromPelangganId(?int $pelangganId): string
    {
        if ($pelangganId) {
            $pelanggan = Pelanggan::query()->with('speedshop.kota')->find($pelangganId);
            if ($pelanggan?->speedshop) {
                return $pelanggan->speedshop->invoiceOutletCode();
            }
        }

        $uid = auth()->id();
        if ($uid) {
            $user = auth()->user();
            $ssid = $user?->activeSpeedshopId();
            if ($ssid) {
                $sp = static::query()->find($ssid);
                if ($sp) {
                    return $sp->invoiceOutletCode();
                }
            }
        }

        return 'UNK';
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Cabang pertama (id terkecil) untuk gudang — dipakai konsisten untuk FK kategori produk
     * bila satu gudang dipakai beberapa outlet.
     */
    public static function primaryIdForWarehouse(?int $warehouseId): ?int
    {
        if (! $warehouseId) {
            return null;
        }

        $ids = static::resolveCanonicalOutletIds(
            static::query()
                ->where('warehouse_id', $warehouseId)
                ->orderBy('id')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all(),
        );

        return $ids[0] ?? null;
    }

    /**
     * Satu outlet per kode cabang (KTP/CSK/CLK/BKS) — hindari duplikat legacy vs seeder.
     *
     * @param  list<int>  $candidateIds
     * @return list<int>
     */
    public static function resolveCanonicalOutletIds(array $candidateIds): array
    {
        $candidateIds = array_values(array_unique(array_map('intval', $candidateIds)));
        if ($candidateIds === []) {
            return [];
        }

        $outlets = static::query()
            ->with('kota')
            ->whereIn('id', $candidateIds)
            ->orderBy('id')
            ->get();

        if ($outlets->count() <= 1) {
            return $outlets->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $stockCounts = SpeedshopStock::query()
            ->whereIn('speedshop_id', $candidateIds)
            ->selectRaw('speedshop_id, count(*) as aggregate')
            ->groupBy('speedshop_id')
            ->pluck('aggregate', 'speedshop_id');

        $userCounts = User::query()
            ->whereIn('speedshop_id', $candidateIds)
            ->whereNotNull('speedshop_id')
            ->selectRaw('speedshop_id, count(*) as aggregate')
            ->groupBy('speedshop_id')
            ->pluck('aggregate', 'speedshop_id');

        /** @var array<string, list<self>> $groups */
        $groups = [];
        foreach ($outlets as $outlet) {
            $code = $outlet->invoiceOutletCode();
            $groups[$code][] = $outlet;
        }

        $selected = [];
        foreach ($groups as $members) {
            usort($members, function (self $a, self $b) use ($stockCounts, $userCounts): int {
                $scoreA = (int) ($stockCounts[$a->id] ?? 0) + (int) ($userCounts[$a->id] ?? 0);
                $scoreB = (int) ($stockCounts[$b->id] ?? 0) + (int) ($userCounts[$b->id] ?? 0);
                if ($scoreB !== $scoreA) {
                    return $scoreB <=> $scoreA;
                }

                return $a->id <=> $b->id;
            });
            $selected[] = (int) $members[0]->id;
        }

        sort($selected);

        return $selected;
    }

    /**
     * Cocokkan nama toko transaksi offline ke master speedshop (mis. "Speed Shop Bandung" → baris master dengan nama resmi cabang).
     */
    public static function findIdByNamaTokoForOffline(?string $nama): ?int
    {
        if (! is_string($nama) || trim($nama) === '') {
            return null;
        }

        $trimmed = trim($nama);

        $id = static::whereRaw('LOWER(TRIM(nama)) = LOWER(?)', [$trimmed])->value('id');
        if ($id) {
            return (int) $id;
        }

        $needle = preg_replace('/\s+/u', '', mb_strtolower($trimmed));
        foreach (static::query()->cursor() as $sp) {
            if (preg_replace('/\s+/u', '', mb_strtolower($sp->nama)) === $needle) {
                return (int) $sp->id;
            }
        }

        return null;
    }
}

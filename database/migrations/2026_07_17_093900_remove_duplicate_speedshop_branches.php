<?php

use App\Models\Speedshop;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hapus cabang speedshop duplikat dari seeder (nama kode *-01/*-02)
 * jika sudah ada cabang operasional sejenis yang dipakai transaksi/user.
 */
return new class extends Migration
{
    /** @var list<array{official: string, keywords: list<string>}> */
    private array $groups = [
        [
            'official' => 'Speedshop Bandung-KTP-01',
            'keywords' => ['bandung'],
        ],
        [
            'official' => 'Speedshop Depok-CSK-02',
            'keywords' => ['depok'],
        ],
        [
            'official' => 'Speedshop Bogor-CLK-03',
            'keywords' => ['bogor'],
        ],
        [
            'official' => 'Speedshop Bekasi-JAS-04',
            'keywords' => ['bekasi'],
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('speedshops')) {
            return;
        }

        foreach ($this->groups as $group) {
            $all = Speedshop::query()
                ->where(function ($q) use ($group) {
                    $q->where('nama', $group['official']);
                    foreach ($group['keywords'] as $keyword) {
                        $q->orWhereRaw('LOWER(nama) LIKE ?', ['%'.$keyword.'%']);
                    }
                })
                ->orderBy('id')
                ->get();

            if ($all->count() < 2) {
                continue;
            }

            $scored = $all->map(function (Speedshop $s) {
                return [
                    'model' => $s,
                    'score' => $this->usageScore((int) $s->id),
                ];
            })->sortByDesc('score')->values();

            $keeper = $scored->first();
            if (! $keeper || $keeper['score'] === 0) {
                // Semua kosong: pertahankan id terkecil.
                $keeper = [
                    'model' => $all->sortBy('id')->first(),
                    'score' => 0,
                ];
            }

            $keeperId = (int) $keeper['model']->id;

            foreach ($all as $candidate) {
                if ((int) $candidate->id === $keeperId) {
                    continue;
                }

                // Jangan hapus cabang yang masih dipakai signifikan.
                if ($this->usageScore((int) $candidate->id) > 0) {
                    continue;
                }

                $this->deleteUnusedSpeedshop((int) $candidate->id);
            }
        }
    }

    public function down(): void
    {
        // Data duplikat tidak dikembalikan.
    }

    private function usageScore(int $speedshopId): int
    {
        $score = 0;

        foreach ([
            'users' => 'speedshop_id',
            'mekaniks' => 'speedshop_id',
            'service_orders' => 'speedshop_id',
        ] as $table => $column) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }
            $score += (int) DB::table($table)->where($column, $speedshopId)->count();
        }

        if (Schema::hasTable('speedshop_stock') && Schema::hasColumn('speedshop_stock', 'speedshop_id')) {
            $qtyCol = Schema::hasColumn('speedshop_stock', 'jumlah') ? 'jumlah' : null;
            if ($qtyCol) {
                $score += (int) DB::table('speedshop_stock')
                    ->where('speedshop_id', $speedshopId)
                    ->where($qtyCol, '>', 0)
                    ->count();
            }
        }

        return $score;
    }

    private function deleteUnusedSpeedshop(int $speedshopId): void
    {
        if (Schema::hasTable('speedshop_stock') && Schema::hasColumn('speedshop_stock', 'speedshop_id')) {
            DB::table('speedshop_stock')->where('speedshop_id', $speedshopId)->delete();
        }

        // Hapus referensi kosong lain yang aman.
        foreach ([
            'attendance_locations' => 'speedshop_id',
        ] as $table => $column) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                DB::table($table)->where($column, $speedshopId)->delete();
            }
        }

        Speedshop::query()->whereKey($speedshopId)->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jasa servis menjadi 1 master global untuk semua cabang speedshop.
     * Duplikat per cabang digabung berdasarkan nama (case-insensitive).
     */
    public function up(): void
    {
        if (! Schema::hasTable('jasa_servis')) {
            return;
        }

        if (Schema::hasColumn('jasa_servis', 'speedshop_id')) {
            $this->mergeDuplicateJasaByNama();
        }

        if (Schema::hasColumn('jasa_servis', 'speedshop_id')) {
            Schema::table('jasa_servis', function (Blueprint $table) {
                $table->dropConstrainedForeignId('speedshop_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('jasa_servis')) {
            return;
        }

        if (! Schema::hasColumn('jasa_servis', 'speedshop_id')) {
            Schema::table('jasa_servis', function (Blueprint $table) {
                $table->foreignId('speedshop_id')->nullable()->after('id')->constrained('speedshops')->nullOnDelete();
                $table->index(['speedshop_id', 'nama']);
            });
        }
    }

    private function mergeDuplicateJasaByNama(): void
    {
        $rows = DB::table('jasa_servis')
            ->select(['id', 'nama', 'biaya'])
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $groups = [];
        foreach ($rows as $row) {
            $key = mb_strtolower(trim((string) $row->nama));
            if ($key === '') {
                $key = '__empty_'.$row->id;
            }
            $groups[$key][] = $row;
        }

        $hasPivot = Schema::hasTable('service_order_jasa');

        foreach ($groups as $group) {
            if (count($group) < 2) {
                continue;
            }

            $ids = array_map(fn ($r) => (int) $r->id, $group);
            $usage = [];
            if ($hasPivot) {
                $usage = DB::table('service_order_jasa')
                    ->select('jasa_servis_id', DB::raw('count(*) as total'))
                    ->whereIn('jasa_servis_id', $ids)
                    ->groupBy('jasa_servis_id')
                    ->pluck('total', 'jasa_servis_id')
                    ->map(fn ($v) => (int) $v)
                    ->all();
            }

            usort($group, function ($a, $b) use ($usage) {
                $ua = $usage[(int) $a->id] ?? 0;
                $ub = $usage[(int) $b->id] ?? 0;
                if ($ua !== $ub) {
                    return $ub <=> $ua;
                }

                return (int) $a->id <=> (int) $b->id;
            });

            $canonicalId = (int) $group[0]->id;
            $duplicateIds = array_values(array_filter(
                $ids,
                fn (int $id) => $id !== $canonicalId
            ));

            if ($duplicateIds === []) {
                continue;
            }

            if ($hasPivot) {
                foreach ($duplicateIds as $dupId) {
                    $pivotRows = DB::table('service_order_jasa')
                        ->where('jasa_servis_id', $dupId)
                        ->get();

                    foreach ($pivotRows as $pivot) {
                        $exists = DB::table('service_order_jasa')
                            ->where('service_order_id', $pivot->service_order_id)
                            ->where('jasa_servis_id', $canonicalId)
                            ->exists();

                        if ($exists) {
                            DB::table('service_order_jasa')->where('id', $pivot->id)->delete();
                        } else {
                            DB::table('service_order_jasa')
                                ->where('id', $pivot->id)
                                ->update(['jasa_servis_id' => $canonicalId]);
                        }
                    }
                }
            }

            DB::table('jasa_servis')->whereIn('id', $duplicateIds)->delete();
        }
    }
};

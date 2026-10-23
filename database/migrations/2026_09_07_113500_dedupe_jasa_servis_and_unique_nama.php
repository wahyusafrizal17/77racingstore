<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gabungkan jasa servis duplikat (nama sama, case-insensitive)
     * lalu kunci unique pada kolom nama.
     */
    public function up(): void
    {
        if (! Schema::hasTable('jasa_servis')) {
            return;
        }

        $this->mergeDuplicateJasaByNama();

        if (! $this->hasUniqueNamaIndex()) {
            Schema::table('jasa_servis', function (Blueprint $table) {
                $table->unique('nama');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('jasa_servis')) {
            return;
        }

        if ($this->hasUniqueNamaIndex()) {
            Schema::table('jasa_servis', function (Blueprint $table) {
                $table->dropUnique(['nama']);
            });
        }
    }

    private function hasUniqueNamaIndex(): bool
    {
        $sm = Schema::getConnection()->getSchemaBuilder();
        $indexes = $sm->getIndexes('jasa_servis');

        foreach ($indexes as $index) {
            if (! empty($index['unique']) && ($index['columns'] ?? []) === ['nama']) {
                return true;
            }
        }

        return false;
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

            $canonical = $group[0];
            $canonicalId = (int) $canonical->id;
            $canonicalNama = trim((string) $canonical->nama);
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

            // Normalisasi nama tersimpan (trim) pada baris yang dipertahankan.
            if ($canonicalNama !== (string) $canonical->nama) {
                DB::table('jasa_servis')->where('id', $canonicalId)->update([
                    'nama' => $canonicalNama,
                    'updated_at' => now(),
                ]);
            }

            DB::table('jasa_servis')->whereIn('id', $duplicateIds)->delete();
        }
    }
};

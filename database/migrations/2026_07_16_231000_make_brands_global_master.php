<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Brand menjadi 1 master global (speedshop + warehouse + produksi).
     * Duplikat digabung berdasarkan nama (case-insensitive).
     */
    public function up(): void
    {
        if (! Schema::hasTable('brands')) {
            return;
        }

        if (Schema::hasColumn('brands', 'section')) {
            $this->mergeDuplicateBrandsByNama();
            $this->dropSectionUniqueIfExists();

            Schema::table('brands', function (Blueprint $table) {
                $table->dropColumn('section');
            });
        }

        if (! $this->hasNamaUnique()) {
            Schema::table('brands', function (Blueprint $table) {
                $table->unique('nama');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('brands')) {
            return;
        }

        if ($this->hasNamaUnique()) {
            Schema::table('brands', function (Blueprint $table) {
                $table->dropUnique(['nama']);
            });
        }

        if (! Schema::hasColumn('brands', 'section')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->string('section', 20)->default('speedshop')->after('nama');
            });
        }

        if (! $this->hasSectionNamaUnique()) {
            Schema::table('brands', function (Blueprint $table) {
                $table->unique(['section', 'nama']);
            });
        }
    }

    private function mergeDuplicateBrandsByNama(): void
    {
        $rows = DB::table('brands')
            ->select(['id', 'nama'])
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

        $hasProducts = Schema::hasTable('products') && Schema::hasColumn('products', 'brand_id');

        foreach ($groups as $group) {
            if (count($group) < 2) {
                continue;
            }

            $ids = array_map(fn ($r) => (int) $r->id, $group);
            $usage = [];
            if ($hasProducts) {
                $usage = DB::table('products')
                    ->select('brand_id', DB::raw('count(*) as total'))
                    ->whereIn('brand_id', $ids)
                    ->groupBy('brand_id')
                    ->pluck('total', 'brand_id')
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

            if ($hasProducts) {
                DB::table('products')
                    ->whereIn('brand_id', $duplicateIds)
                    ->update(['brand_id' => $canonicalId]);
            }

            DB::table('brands')->whereIn('id', $duplicateIds)->delete();
        }
    }

    private function dropSectionUniqueIfExists(): void
    {
        if (! $this->hasSectionNamaUnique()) {
            return;
        }

        Schema::table('brands', function (Blueprint $table) {
            $table->dropUnique(['section', 'nama']);
        });
    }

    private function hasSectionNamaUnique(): bool
    {
        return collect(Schema::getIndexes('brands'))
            ->pluck('name')
            ->contains('brands_section_nama_unique');
    }

    private function hasNamaUnique(): bool
    {
        return collect(Schema::getIndexes('brands'))
            ->pluck('name')
            ->contains('brands_nama_unique');
    }
};

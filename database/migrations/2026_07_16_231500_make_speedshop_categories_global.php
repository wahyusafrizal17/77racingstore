<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kategori speedshop menjadi 1 master untuk semua cabang (tetap terpisah dari warehouse/produksi).
     */
    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        if (Schema::hasColumn('categories', 'speedshop_id')) {
            $this->mergeDuplicateSpeedshopCategories();
            $this->dropSpeedshopUniqueIfExists();
            $this->dropSpeedshopForeignIfExists();

            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('speedshop_id');
            });
        }

        if (! $this->hasSectionNamaUnique()) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unique(['section', 'nama'], 'categories_section_nama_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        if ($this->hasSectionNamaUnique()) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropUnique('categories_section_nama_unique');
            });
        }

        if (! Schema::hasColumn('categories', 'speedshop_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unsignedBigInteger('speedshop_id')->nullable()->after('section');
            });

            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->foreign('speedshop_id', 'ss_cat_spdshop_fk')->references('id')->on('speedshops')->nullOnDelete();
                });
            } catch (\Throwable) {
                //
            }
        }

        if (! $this->hasCatSecSpidNamaUnique()) {
            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->unique(['section', 'speedshop_id', 'nama'], 'cat_sec_spid_nama_uq');
                });
            } catch (\Throwable) {
                //
            }
        }
    }

    private function mergeDuplicateSpeedshopCategories(): void
    {
        $rows = DB::table('categories')
            ->where('section', 'speedshop')
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

        $hasProducts = Schema::hasTable('products') && Schema::hasColumn('products', 'category_id');

        foreach ($groups as $group) {
            if (count($group) < 2) {
                continue;
            }

            $ids = array_map(fn ($r) => (int) $r->id, $group);
            $usage = [];
            if ($hasProducts) {
                $usage = DB::table('products')
                    ->select('category_id', DB::raw('count(*) as total'))
                    ->whereIn('category_id', $ids)
                    ->groupBy('category_id')
                    ->pluck('total', 'category_id')
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
                    ->whereIn('category_id', $duplicateIds)
                    ->update(['category_id' => $canonicalId]);
            }

            DB::table('categories')->whereIn('id', $duplicateIds)->delete();
        }
    }

    private function dropSpeedshopUniqueIfExists(): void
    {
        if (! $this->hasCatSecSpidNamaUnique()) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('cat_sec_spid_nama_uq');
        });
    }

    private function dropSpeedshopForeignIfExists(): void
    {
        try {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropForeign('ss_cat_spdshop_fk');
            });
        } catch (\Throwable) {
            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->dropForeign(['speedshop_id']);
                });
            } catch (\Throwable) {
                //
            }
        }
    }

    private function indexNames(): array
    {
        return collect(Schema::getIndexes('categories'))->pluck('name')->all();
    }

    private function hasCatSecSpidNamaUnique(): bool
    {
        return in_array('cat_sec_spid_nama_uq', $this->indexNames(), true);
    }

    private function hasSectionNamaUnique(): bool
    {
        return in_array('categories_section_nama_unique', $this->indexNames(), true);
    }
};

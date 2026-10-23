<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexNames(): array
    {
        if (! Schema::hasTable('categories')) {
            return [];
        }

        return collect(Schema::getIndexes('categories'))->pluck('name')->all();
    }

    private function hasNewUnique(): bool
    {
        return in_array('cat_sec_spid_nama_uq', $this->indexNames(), true);
    }

    public function up(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        if ($this->hasNewUnique()) {
            return;
        }

        if (! Schema::hasColumn('categories', 'speedshop_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unsignedBigInteger('speedshop_id')->nullable()->after('section');
            });
        }

        if (Schema::hasTable('speedshops')) {
            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->foreign('speedshop_id', 'ss_cat_spdshop_fk')->references('id')->on('speedshops')->nullOnDelete();
                });
            } catch (\Throwable) {
                //
            }
        }

        $names = $this->indexNames();
        if (in_array('categories_section_nama_unique', $names, true)) {
            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->dropUnique('categories_section_nama_unique');
                });
            } catch (\Throwable) {
                //
            }
        }

        $speedshopIds = Schema::hasTable('speedshops')
            ? DB::table('speedshops')->orderBy('id')->pluck('id')->all()
            : [];

        $oldIds = DB::table('categories')
            ->where('section', 'speedshop')
            ->whereNull('speedshop_id')
            ->pluck('id')
            ->all();

        if ($speedshopIds !== [] && $oldIds !== []) {
            $map = [];
            foreach ($oldIds as $oldId) {
                $row = DB::table('categories')->where('id', $oldId)->first();
                if (! $row) {
                    continue;
                }
                foreach ($speedshopIds as $spId) {
                    $newId = DB::table('categories')->insertGetId([
                        'nama' => $row->nama,
                        'section' => 'speedshop',
                        'speedshop_id' => (int) $spId,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]);
                    $map[(int) $oldId][(int) $spId] = (int) $newId;
                }
            }

            $products = DB::table('products')->whereIn('category_id', array_map('intval', $oldIds))->get(['id', 'category_id', 'warehouse_id']);
            foreach ($products as $p) {
                $wh = $p->warehouse_id ?? null;
                if (! $wh) {
                    continue;
                }
                $primary = DB::table('speedshops')->where('warehouse_id', $wh)->orderBy('id')->value('id');
                if (! $primary) {
                    continue;
                }
                $primary = (int) $primary;
                $oldCat = (int) $p->category_id;
                if (! isset($map[$oldCat][$primary])) {
                    continue;
                }
                DB::table('products')->where('id', $p->id)->update(['category_id' => $map[$oldCat][$primary]]);
            }

            DB::table('categories')->whereIn('id', array_map('intval', $oldIds))->delete();
        }

        if (! $this->hasNewUnique()) {
            try {
                Schema::table('categories', function (Blueprint $table) {
                    $table->unique(['section', 'speedshop_id', 'nama'], 'cat_sec_spid_nama_uq');
                });
            } catch (\Throwable) {
                //
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('categories')) {
            return;
        }

        if ($this->hasNewUnique()) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropUnique('cat_sec_spid_nama_uq');
            });
        }

        if (Schema::hasColumn('categories', 'speedshop_id')) {
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
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('speedshop_id');
            });
        }

        $names = $this->indexNames();
        if (! in_array('categories_section_nama_unique', $names, true)) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unique(['section', 'nama'], 'categories_section_nama_unique');
            });
        }
    }
};

<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('categories', 'section')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('section', 20)->default(Category::SECTION_SPEEDSHOP)->after('nama');
            });
        }

        // Kategori yang dipakai produksi DAN gudang/outlet sekaligus: duplikat baris produksi, pindahkan FK produk produksi.
        $overlapIds = Product::query()
            ->whereNotNull('category_id')
            ->select('category_id')
            ->groupBy('category_id')
            ->havingRaw('SUM(CASE WHEN warehouse_id IS NULL THEN 1 ELSE 0 END) > 0')
            ->havingRaw('SUM(CASE WHEN warehouse_id IS NOT NULL THEN 1 ELSE 0 END) > 0')
            ->pluck('category_id');

        foreach ($overlapIds as $oldId) {
            $old = Category::query()->find((int) $oldId);
            if (! $old) {
                continue;
            }
            $dup = Category::query()->create([
                'nama' => $old->nama,
                'section' => Category::SECTION_PRODUKSI,
            ]);
            Product::query()
                ->where('category_id', $old->id)
                ->whereNull('warehouse_id')
                ->update(['category_id' => $dup->id]);
        }

        foreach (Category::query()->cursor() as $cat) {
            $base = Product::query()->where('category_id', $cat->id);
            $hasProduksi = (clone $base)->whereNull('warehouse_id')->exists();
            $hasWarehouse = (clone $base)->whereNotNull('warehouse_id')->exists();

            if ($hasProduksi && ! $hasWarehouse) {
                $cat->update(['section' => Category::SECTION_PRODUKSI]);
            } elseif ($hasWarehouse && ! $hasProduksi) {
                $cat->update(['section' => Category::SECTION_WAREHOUSE]);
            } else {
                $cat->update(['section' => Category::SECTION_SPEEDSHOP]);
            }
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->unique(['section', 'nama']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('categories', 'section')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['section', 'nama']);
            $table->dropColumn('section');
        });
    }
};

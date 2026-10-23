<?php

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasCompositeUnique(): bool
    {
        if (! Schema::hasTable('brands')) {
            return false;
        }

        return collect(Schema::getIndexes('brands'))
            ->pluck('name')
            ->contains('brands_section_nama_unique');
    }

    public function up(): void
    {
        if (! Schema::hasColumn('brands', 'section')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->string('section', 20)->default(Brand::SECTION_SPEEDSHOP)->after('nama');
            });
        }

        $overlapIds = Product::query()
            ->whereNotNull('brand_id')
            ->select('brand_id')
            ->groupBy('brand_id')
            ->havingRaw('SUM(CASE WHEN warehouse_id IS NULL THEN 1 ELSE 0 END) > 0')
            ->havingRaw('SUM(CASE WHEN warehouse_id IS NOT NULL THEN 1 ELSE 0 END) > 0')
            ->pluck('brand_id');

        foreach ($overlapIds as $oldId) {
            $old = Brand::query()->find((int) $oldId);
            if (! $old) {
                continue;
            }
            $dup = Brand::query()->create([
                'nama' => $old->nama,
                'section' => Brand::SECTION_PRODUKSI,
            ]);
            Product::query()
                ->where('brand_id', $old->id)
                ->whereNull('warehouse_id')
                ->update(['brand_id' => $dup->id]);
        }

        foreach (Brand::query()->cursor() as $brand) {
            $base = Product::query()->where('brand_id', $brand->id);
            $hasProduksi = (clone $base)->whereNull('warehouse_id')->exists();
            $hasWarehouse = (clone $base)->whereNotNull('warehouse_id')->exists();

            if ($hasProduksi && ! $hasWarehouse) {
                $brand->update(['section' => Brand::SECTION_PRODUKSI]);
            } elseif ($hasWarehouse && ! $hasProduksi) {
                $brand->update(['section' => Brand::SECTION_WAREHOUSE]);
            } else {
                $brand->update(['section' => Brand::SECTION_SPEEDSHOP]);
            }
        }

        if (! $this->hasCompositeUnique()) {
            Schema::table('brands', function (Blueprint $table) {
                $table->unique(['section', 'nama']);
            });
        }
    }

    public function down(): void
    {
        if ($this->hasCompositeUnique()) {
            Schema::table('brands', function (Blueprint $table) {
                $table->dropUnique(['section', 'nama']);
            });
        }

        if (Schema::hasColumn('brands', 'section')) {
            Schema::table('brands', function (Blueprint $table) {
                $table->dropColumn('section');
            });
        }
    }
};

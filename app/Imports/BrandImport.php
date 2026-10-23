<?php

namespace App\Imports;

use App\Models\Brand;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BrandImport implements ToModel, WithHeadingRow
{
    public function model(array $row): ?Brand
    {
        $nama = trim($row['nama_brand'] ?? $row['nama'] ?? '');

        if ($nama === '') {
            return null;
        }

        return Brand::firstOrCreate(['nama' => $nama]);
    }
}

<?php

namespace App\Imports;

use App\Models\Category;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class CategoryImport implements ToModel, WithHeadingRow
{
    public function __construct(
        private readonly string $section = Category::SECTION_SPEEDSHOP,
    ) {}

    public function model(array $row): ?Category
    {
        $nama = trim($row['nama_kategori'] ?? $row['nama'] ?? '');

        if ($nama === '') {
            return null;
        }

        return Category::firstOrCreate(
            ['nama' => $nama, 'section' => $this->section],
            ['section' => $this->section],
        );
    }
}

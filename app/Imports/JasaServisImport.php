<?php

namespace App\Imports;

use App\Models\JasaServis;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class JasaServisImport implements ToModel, WithHeadingRow
{
    public function model(array $row): ?JasaServis
    {
        $nama = trim((string) ($row['nama_jasa_servis'] ?? $row['nama'] ?? ''));
        $biaya = (int) round((float) ($row['biaya'] ?? $row['biaya_rp'] ?? 0));

        if ($nama === '') {
            return null;
        }

        $existing = JasaServis::query()
            ->whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower($nama)])
            ->first();

        if ($existing) {
            $existing->update([
                'nama' => $nama,
                'biaya' => $biaya,
            ]);

            return null;
        }

        return new JasaServis([
            'nama' => $nama,
            'biaya' => $biaya,
        ]);
    }
}

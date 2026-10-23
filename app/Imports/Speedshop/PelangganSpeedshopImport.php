<?php

namespace App\Imports\Speedshop;

use App\Models\Pelanggan;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PelangganSpeedshopImport implements ToModel, WithHeadingRow
{
    public function model(array $row): ?Pelanggan
    {
        $nama = trim((string) ($row['nama'] ?? ''));
        $noHp = trim((string) ($row['no_hp'] ?? ''));
        $alamat = trim((string) ($row['alamat'] ?? ''));

        if ($nama === '') {
            return null;
        }

        $payload = [
            'section' => Pelanggan::SECTION_SPEEDSHOP,
            'speedshop_id' => null,
            'nama' => $nama,
            'no_hp' => $noHp !== '' ? $noHp : null,
            'alamat' => $alamat !== '' ? $alamat : null,
        ];

        if ($noHp !== '') {
            Pelanggan::updateOrCreate(
                ['section' => Pelanggan::SECTION_SPEEDSHOP, 'no_hp' => $noHp],
                $payload
            );
        } else {
            Pelanggan::create($payload);
        }

        return null;
    }
}

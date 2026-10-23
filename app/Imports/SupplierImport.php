<?php

namespace App\Imports;

use App\Models\Kota;
use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SupplierImport implements ToModel, WithHeadingRow
{
    public function __construct(
        private readonly string $section,
        private readonly ?int $speedshopId = null
    ) {}

    public function model(array $row): ?Supplier
    {
        $row = $this->normalizeHeadingRow($row);

        $kodeSupplier = mb_strtoupper(trim((string) ($row['kode_supplier'] ?? '')));
        if ($this->isEmptyCell($kodeSupplier)) {
            return null;
        }

        $nama = trim((string) ($row['nama'] ?? ''));
        if ($this->isEmptyCell($nama)) {
            return null;
        }

        $len = mb_strlen($kodeSupplier);
        if ($len < 2 || $len > 3) {
            return null;
        }

        $scope = $this->section === Supplier::SECTION_SPEEDSHOP
            ? ('speedshop:'.(int) ($this->speedshopId ?? 0))
            : $this->section;

        $kotaNama = $this->cleanCell($row['kota'] ?? null) ?? '';
        $kotaId = null;
        if ($kotaNama !== '') {
            $kota = Kota::query()
                ->whereRaw('LOWER(nama) = ?', [mb_strtolower($kotaNama)])
                ->first();
            $kotaId = $kota?->id;
        }

        $jenisRekening = $this->nullableString($row['jenis_rekening'] ?? null);
        $namaRekening = $this->nullableString($row['nama_rekening'] ?? null);
        $nomorRekening = $this->nullableString($row['nomor_rekening'] ?? null);

        if ($jenisRekening === null && $namaRekening === null && $nomorRekening === null) {
            $parsed = $this->parseRekeningGabungan($row['rekening'] ?? null);
            $jenisRekening = $parsed['jenis_rekening'];
            $namaRekening = $parsed['nama_rekening'];
            $nomorRekening = $parsed['nomor_rekening'];
        }

        $payload = [
            'kode_supplier' => $kodeSupplier,
            'nama' => $nama,
            'no_hp' => $this->nullableString($row['no_hp'] ?? null),
            'email' => $this->nullableString($row['email'] ?? null),
            'alamat' => $this->nullableString($row['alamat'] ?? null),
            'kota_id' => $kotaId,
            'jenis_rekening' => $jenisRekening,
            'nama_rekening' => $namaRekening,
            'nomor_rekening' => $nomorRekening,
            'section' => $this->section,
            'speedshop_id' => $this->section === Supplier::SECTION_SPEEDSHOP ? ($this->speedshopId ? (int) $this->speedshopId : null) : null,
            'supplier_scope' => $scope,
        ];

        Supplier::updateOrCreate(
            ['supplier_scope' => $scope, 'kode_supplier' => $kodeSupplier],
            $payload
        );

        return null;
    }

    /**
     * Normalisasi header lama / alias agar tetap bisa di-import.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeHeadingRow(array $row): array
    {
        if (! array_key_exists('nama', $row) || $row['nama'] === null || trim((string) $row['nama']) === '') {
            if (isset($row['nama_supplier'])) {
                $row['nama'] = $row['nama_supplier'];
            }
        }
        if (! array_key_exists('kode_supplier', $row) || $row['kode_supplier'] === null || trim((string) $row['kode_supplier']) === '') {
            if (isset($row['kode'])) {
                $row['kode_supplier'] = $row['kode'];
            }
        }

        return $row;
    }

    /**
     * @return array{jenis_rekening: ?string, nama_rekening: ?string, nomor_rekening: ?string}
     */
    private function parseRekeningGabungan(mixed $value): array
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '' || $raw === '-' || $raw === '—') {
            return ['jenis_rekening' => null, 'nama_rekening' => null, 'nomor_rekening' => null];
        }

        $parts = array_map('trim', explode(' - ', $raw, 3));
        if (count($parts) === 3) {
            return [
                'jenis_rekening' => $parts[0] !== '' ? $parts[0] : null,
                'nama_rekening' => $parts[1] !== '' ? $parts[1] : null,
                'nomor_rekening' => $parts[2] !== '' ? $parts[2] : null,
            ];
        }
        if (count($parts) === 2) {
            return [
                'jenis_rekening' => $parts[0] !== '' ? $parts[0] : null,
                'nama_rekening' => $parts[1] !== '' ? $parts[1] : null,
                'nomor_rekening' => null,
            ];
        }

        return [
            'jenis_rekening' => $parts[0] !== '' ? $parts[0] : null,
            'nama_rekening' => null,
            'nomor_rekening' => null,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        if ($text === '' || $text === '-' || $text === '—') {
            return null;
        }

        return $text;
    }

    private function isEmptyCell(string $value): bool
    {
        $v = trim($value);

        return $v === '' || $v === '-' || $v === '—';
    }

    private function cleanCell(mixed $value): ?string
    {
        return $this->nullableString($value);
    }
}

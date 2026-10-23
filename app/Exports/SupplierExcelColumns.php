<?php

namespace App\Exports;

use App\Models\Supplier;

/**
 * Kolom Excel supplier — dipakai template, export, dan import (WithHeadingRow → slug).
 */
class SupplierExcelColumns
{
    /** @return list<string> */
    public static function headings(): array
    {
        return [
            'Kode Supplier',
            'Nama',
            'No. HP',
            'Email',
            'Kota',
            'Alamat',
            'Jenis Rekening',
            'Nama Rekening',
            'Nomor Rekening',
        ];
    }

    /** @return list<string|int|null> */
    public static function mapRow(Supplier $row): array
    {
        return [
            self::cell($row->kode_supplier),
            self::cell($row->nama),
            self::cell($row->no_hp),
            self::cell($row->email),
            self::cell($row->kota?->nama),
            self::cell($row->alamat),
            self::cell($row->jenis_rekening),
            self::cell($row->nama_rekening),
            self::cell($row->nomor_rekening),
        ];
    }

    private static function cell(mixed $value): string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? '' : $text;
    }
}

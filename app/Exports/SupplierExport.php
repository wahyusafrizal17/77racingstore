<?php

namespace App\Exports;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SupplierExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    use WithExcelHeaderStyle;

    public function __construct(
        protected ?string $search = null,
        protected ?string $section = null,
        protected array $filters = []
    ) {}

    public function query()
    {
        $query = Supplier::with('kota')->orderBy('nama');

        if ($this->section !== null && $this->section !== '') {
            $query->where('section', $this->section);
        }

        if (($this->section ?? null) === Supplier::SECTION_SPEEDSHOP) {
            $scopes = $this->filters['_supplier_scopes'] ?? null;
            if (is_array($scopes) && $scopes !== []) {
                $query->whereIn('supplier_scope', $scopes);
            }
        }

        $restrictIds = $this->filters['_restrict_ids'] ?? null;
        if (is_array($restrictIds) && $restrictIds !== []) {
            $query->whereIn('id', $restrictIds);
        } elseif (is_array($restrictIds) && $restrictIds === []) {
            $query->whereRaw('0 = 1');
        }

        $kodeSupplier = $this->filters['kode_supplier'] ?? null;
        if (filled($kodeSupplier)) {
            $query->where('kode_supplier', 'like', '%'.$kodeSupplier.'%');
        }

        $nama = $this->filters['nama'] ?? null;
        if (filled($nama)) {
            $query->where('nama', 'like', '%'.$nama.'%');
        }

        $noHp = $this->filters['no_hp'] ?? null;
        if (filled($noHp)) {
            $query->where('no_hp', 'like', '%'.$noHp.'%');
        }

        $email = $this->filters['email'] ?? null;
        if (filled($email)) {
            $query->where('email', 'like', '%'.$email.'%');
        }

        $kota = $this->filters['kota'] ?? null;
        if (filled($kota)) {
            $query->whereHas('kota', fn ($q) => $q->where('nama', 'like', '%'.$kota.'%'));
        }

        $alamat = $this->filters['alamat'] ?? null;
        if (filled($alamat)) {
            $query->where('alamat', 'like', '%'.$alamat.'%');
        }

        $rekening = $this->filters['rekening'] ?? null;
        if (filled($rekening)) {
            $query->where(function ($q) use ($rekening) {
                $q->where('jenis_rekening', 'like', '%'.$rekening.'%')
                    ->orWhere('nama_rekening', 'like', '%'.$rekening.'%')
                    ->orWhere('nomor_rekening', 'like', '%'.$rekening.'%');
            });
        }

        $search = $this->filters['search'] ?? $this->search;
        if (
            filled($search)
            && ! filled($kodeSupplier)
            && ! filled($nama)
            && ! filled($noHp)
            && ! filled($email)
            && ! filled($kota)
            && ! filled($alamat)
            && ! filled($rekening)
        ) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('no_hp', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return SupplierExcelColumns::headings();
    }

    public function map($row): array
    {
        return SupplierExcelColumns::mapRow($row);
    }
}

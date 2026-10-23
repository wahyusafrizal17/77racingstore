<?php

namespace App\Exports\Admin;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromQuery, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    use WithExcelHeaderStyle;

    public function __construct(private array $filters = [])
    {
    }

    public function query()
    {
        $q = User::query()->adminKaryawan()->with(['warehouse', 'speedshop']);

        if (! empty($this->filters['name'])) {
            $term = '%'.addcslashes((string) $this->filters['name'], '%_\\').'%';
            $q->where('name', 'like', $term);
        }
        if (! empty($this->filters['email'])) {
            $term = '%'.addcslashes((string) $this->filters['email'], '%_\\').'%';
            $q->where('email', 'like', $term);
        }
        if (! empty($this->filters['section'])) {
            $q->where('section', $this->filters['section']);
        }
        if (! empty($this->filters['role'])) {
            $q->where('role', $this->filters['role']);
        }

        return $q->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'Nama',
            'Username',
            'Email',
            'Divisi',
            'Role',
            'Gudang',
            'Cabang',
            'Password',
        ];
    }

    public function map($user): array
    {
        /** @var User $user */
        $gudang = '';
        $cabang = '';

        if ($user->relationLoaded('assignedSpeedshops') && $user->assignedSpeedshops->isNotEmpty()) {
            $cabang = $user->assignedSpeedshops->pluck('nama')->filter()->join(' · ');
        } elseif ($user->assignedSpeedshopIds() !== []) {
            $cabang = $user->assignedSpeedshops()->pluck('speedshops.nama')->filter()->join(' · ');
        } elseif ($user->isSpeedshopRegionalManager()) {
            $cabang = 'Semua Cabang';
        } elseif (($user->section ?? '') === 'warehouse') {
            $gudang = (string) ($user->warehouse?->nama ?? '');
        } elseif ($user->speedshop_id) {
            $cabang = (string) ($user->speedshop?->nama ?? '');
        } elseif ($user->warehouse_id) {
            $gudang = (string) ($user->warehouse?->nama ?? '');
        }

        return [
            $user->name,
            $user->username ?? '',
            $user->email,
            $user->section ?? '',
            $user->role ?? '',
            $gudang,
            $cabang,
            '',
        ];
    }
}

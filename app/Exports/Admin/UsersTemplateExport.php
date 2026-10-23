<?php

namespace App\Exports\Admin;

use App\Exports\Concerns\WithExcelHeaderStyle;
use App\Models\MekanikLevel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class UsersTemplateExport implements FromArray, ShouldAutoSize, WithEvents, WithHeadings, WithTitle
{
    use WithExcelHeaderStyle;

    /** @var list<string> */
    private array $mekanikLevels;

    public function __construct()
    {
        $levels = MekanikLevel::query()
            ->orderBy('nama')
            ->pluck('nama')
            ->map(fn ($nama) => trim((string) $nama))
            ->filter()
            ->values()
            ->all();

        $this->mekanikLevels = $levels !== []
            ? $levels
            : ['KEPALA MEKANIK', 'MEKANIK LEVEL 1'];
    }

    public function title(): string
    {
        return 'Template';
    }

    public function array(): array
    {
        $sampleLevel = $this->mekanikLevels[0] ?? 'KEPALA MEKANIK';

        return [
            [
                'Budi Outlet',
                'budi.outlet',
                'budi.outlet@example.com',
                'speedshop',
                'Staf',
                '',
                'Speedshop Bandung - KTP',
                '',
                '',
                'password123',
            ],
            [
                'Agus Mekanik',
                'agus.mekanik',
                'agus.mekanik@example.com',
                'speedshop',
                'Mekanik',
                '',
                'Speedshop Bandung - KTP',
                $sampleLevel,
                '081234567890',
                'password123',
            ],
            [
                'Siti Manager',
                'siti.manager',
                'siti.manager@example.com',
                'speedshop',
                'Manager',
                '',
                'Semua Cabang',
                '',
                '',
                'password123',
            ],
            [
                'Andi HR',
                'andi.hr',
                'andi.hr@example.com',
                'modifikasi_ori',
                'Head HR',
                '',
                '',
                '',
                '',
                'password123',
            ],
            [
                'Rina Owner',
                'rina.owner',
                'rina.owner@example.com',
                'owner',
                'Owner',
                '',
                '',
                '',
                '',
                'password123',
            ],
        ];
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
            'Level Mekanik',
            'No HP',
            'Password',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->applyHeaderStyle($event);
                $this->applyMekanikLevelDropdown($event);
            },
        ];
    }

    private function applyMekanikLevelDropdown(AfterSheet $event): void
    {
        $sheet = $event->sheet->getDelegate();
        $list = '"'.implode(',', array_map(
            fn (string $level) => str_replace('"', "''", $level),
            $this->mekanikLevels,
        )).'"';

        $validation = $sheet->getCell('H2')->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Level Mekanik tidak valid');
        $validation->setError('Pilih Level Mekanik dari daftar: '.implode(', ', $this->mekanikLevels));
        $validation->setPromptTitle('Level Mekanik');
        $validation->setPrompt('Wajib diisi jika Role = Mekanik');
        $validation->setFormula1($list);
        $validation->setSqref('H2:H2000');
    }
}

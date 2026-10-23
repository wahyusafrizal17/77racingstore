<?php

namespace App\Exports\Admin;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class UsersTemplateWorkbookExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new UsersTemplateExport,
            new UsersTemplatePetunjukSheet,
        ];
    }
}

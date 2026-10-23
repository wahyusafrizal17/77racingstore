<?php

namespace App\Http\Controllers\Admin;

use App\Exports\Admin\RolesExport;
use App\Http\Controllers\Controller;
use App\Support\AdminRoleCatalog;
use App\Support\AdminUserPayload;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class RoleCatalogController extends Controller
{
    /** @return array<string, string> */
    private function filtersFromRequest(Request $request): array
    {
        return $request->only(['section', 'role']);
    }

    public function index(Request $request): View
    {
        $filters = $this->filtersFromRequest($request);
        $rows = AdminRoleCatalog::filtered($filters);

        $sectionFilter = $request->filled('section') ? $request->string('section')->toString() : null;
        $roleOptions = AdminRoleCatalog::filterRoleOptions($sectionFilter);
        $selectedRole = $request->string('role')->toString();
        if ($selectedRole !== '' && ! in_array($selectedRole, $roleOptions, true)) {
            $roleOptions[] = $selectedRole;
            sort($roleOptions);
        }

        return view('admin.roles.index', [
            'rows' => $rows,
            'statTotalRoles' => $rows->count(),
            'statTotalKaryawan' => $rows->sum('karyawan_count'),
            'adminDivisiOptions' => AdminUserPayload::divisiOptions(),
            'filterRoleOptions' => $roleOptions,
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->filtersFromRequest($request);
        $rows = AdminRoleCatalog::filtered($filters);

        return Excel::download(
            new RolesExport($rows),
            'role-'.now()->format('Y-m-d-His').'.xlsx',
        );
    }

    public function exportPdf(Request $request)
    {
        $filters = $this->filtersFromRequest($request);
        $rows = AdminRoleCatalog::filtered($filters);
        $filename = 'role-'.now()->format('Y-m-d-His').'.pdf';

        return Pdf::loadView('admin.roles.pdf', [
            'rows' => $rows,
            'filters' => $filters,
            'printedAt' => now(),
        ])->setPaper('a4', 'portrait')->stream($filename);
    }
}

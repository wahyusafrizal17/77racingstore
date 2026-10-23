<?php

namespace App\Http\Controllers;

use App\Exports\SupplierExport;
use App\Exports\SupplierTemplateExport;
use App\Imports\SupplierImport;
use App\Models\Kota;
use App\Models\Supplier;
use App\Models\WarehouseTransaksiItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class SupplierController extends Controller
{
    private function speedshopSupplierScopes(): array
    {
        $ssid = auth()->user()?->activeSpeedshopId();
        if (! $ssid) {
            return ['speedshop:0'];
        }

        return ['speedshop:'.(int) $ssid, 'speedshop:0'];
    }

    private function denySpeedshopStafSupplierMaster(): void
    {
        $routeName = request()->route()->getName() ?? '';
        if (! str_starts_with($routeName, 'speedshop.')) {
            return;
        }
        // Master supplier Speedshop boleh diakses oleh user Speedshop (termasuk staf) dan Accounting.
    }

    public function index(Request $request)
    {
        $sectionFilter = $request->get('section');
        if ($sectionFilter !== null && $sectionFilter !== '' && ! in_array($sectionFilter, Supplier::sections(), true)) {
            $sectionFilter = null;
        }
        if ($sectionFilter === '') {
            $sectionFilter = null;
        }

        $suppliers = $this->suppliersQuery($request, $sectionFilter);
        $kotas = Kota::orderBy('nama')->get();

        return view('admin.suppliers.index', compact('suppliers', 'kotas'));
    }

    /**
     * Halaman supplier untuk section Produksi (Inventori).
     */
    public function indexForProduksi(Request $request)
    {
        $suppliers = $this->suppliersQuery($request, Supplier::SECTION_PRODUKSI);
        $kotas = Kota::orderBy('nama')->get();

        return view('produksi.master-data.suppliers', compact('suppliers', 'kotas'));
    }

    /**
     * Halaman supplier untuk section Warehouse (Inventori).
     */
    public function indexForWarehouse(Request $request)
    {
        $builder = $this->suppliersFilteredBuilder($request, Supplier::SECTION_WAREHOUSE);
        $agg = (clone $builder)->selectRaw("count(*) as total, sum(case when no_hp is not null and no_hp != '' then 1 else 0 end) as punya_no_hp, sum(case when nomor_rekening is not null and nomor_rekening != '' then 1 else 0 end) as punya_rekening")->first();
        $supplierStats = [
            'total' => (int) ($agg->total ?? 0),
            'punya_no_hp' => (int) ($agg->punya_no_hp ?? 0),
            'punya_rekening' => (int) ($agg->punya_rekening ?? 0),
        ];
        $suppliers = $builder->paginate(20)->withQueryString();
        $kotas = Kota::orderBy('nama')->get();

        return view('warehouse.suppliers.index', compact('suppliers', 'kotas', 'supplierStats'));
    }

    /**
     * Master supplier Speedshop.
     */
    public function indexForSpeedshop(Request $request)
    {
        $builder = $this->suppliersFilteredBuilder($request, Supplier::SECTION_SPEEDSHOP);
        $agg = (clone $builder)->selectRaw("count(*) as total, sum(case when no_hp is not null and no_hp != '' then 1 else 0 end) as punya_no_hp, sum(case when nomor_rekening is not null and nomor_rekening != '' then 1 else 0 end) as punya_rekening")->first();
        $supplierStats = [
            'total' => (int) ($agg->total ?? 0),
            'punya_no_hp' => (int) ($agg->punya_no_hp ?? 0),
            'punya_rekening' => (int) ($agg->punya_rekening ?? 0),
        ];
        $suppliers = $builder->paginate(20)->withQueryString();
        $kotas = Kota::orderBy('nama')->get();

        return view('speedshop.suppliers.index', compact('suppliers', 'kotas', 'supplierStats'));
    }

    /** @return list<int> */
    private function speedshopSupplierIdsFromPembelianLuar(?int $warehouseId, ?int $speedshopId): array
    {
        if (! $warehouseId || ! $speedshopId) {
            return [];
        }

        return WarehouseTransaksiItem::query()
            ->join('warehouse_transaksis', 'warehouse_transaksis.id', '=', 'warehouse_transaksi_items.warehouse_transaksi_id')
            ->join('users', 'users.id', '=', 'warehouse_transaksis.user_id')
            ->where('warehouse_transaksis.warehouse_id', $warehouseId)
            ->where('users.section', 'speedshop')
            ->where('users.speedshop_id', (int) $speedshopId)
            ->whereNotNull('warehouse_transaksi_items.supplier_id')
            ->distinct()
            ->pluck('warehouse_transaksi_items.supplier_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function suppliersFilteredBuilder(Request $request, ?string $sectionOnly, ?array $onlyIds = null)
    {
        $query = Supplier::with('kota');

        if ($sectionOnly !== null) {
            $query->where('section', $sectionOnly);
        }

        // Speedshop supplier master harus scope per outlet aktif (tetap tampilkan legacy shared: speedshop:0).
        $routeName = $request->route()?->getName() ?? '';
        if (
            $sectionOnly === Supplier::SECTION_SPEEDSHOP
            && str_starts_with($routeName, 'speedshop.')
            && \Illuminate\Support\Facades\Schema::hasColumn('suppliers', 'supplier_scope')
        ) {
            $query->whereIn('supplier_scope', $this->speedshopSupplierScopes());
        }

        if ($onlyIds !== null) {
            if ($onlyIds === []) {
                $query->whereRaw('0 = 1');
            } else {
                $query->whereIn('id', $onlyIds);
            }
        }

        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%'.$request->nama.'%');
        }

        if ($request->filled('kode_supplier')) {
            $query->where('kode_supplier', 'like', '%'.$request->kode_supplier.'%');
        }

        if ($request->filled('no_hp')) {
            $query->where('no_hp', 'like', '%'.$request->no_hp.'%');
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%'.$request->email.'%');
        }

        if ($request->filled('kota')) {
            $term = $request->kota;
            $query->whereHas('kota', fn ($q) => $q->where('nama', 'like', '%'.$term.'%'));
        }

        if ($request->filled('alamat')) {
            $query->where('alamat', 'like', '%'.$request->alamat.'%');
        }

        if ($request->filled('rekening')) {
            $term = $request->rekening;
            $query->where(function ($q) use ($term) {
                $q->where('jenis_rekening', 'like', '%'.$term.'%')
                    ->orWhere('nama_rekening', 'like', '%'.$term.'%')
                    ->orWhere('nomor_rekening', 'like', '%'.$term.'%');
            });
        }

        if (
            $request->filled('search')
            && ! $request->filled('nama')
            && ! $request->filled('no_hp')
            && ! $request->filled('email')
            && ! $request->filled('kota')
            && ! $request->filled('alamat')
            && ! $request->filled('rekening')
        ) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('no_hp', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('nama');
    }

    private function suppliersQuery(Request $request, ?string $sectionOnly, ?array $onlyIds = null)
    {
        return $this->suppliersFilteredBuilder($request, $sectionOnly, $onlyIds)->paginate(20)->withQueryString();
    }

    public function store(Request $request)
    {
        $this->denySpeedshopStafSupplierMaster();

        $routeName = $request->route()->getName() ?? '';
        $isSpeedshopRoute = str_starts_with($routeName, 'speedshop.');
        $speedshopScope = $isSpeedshopRoute ? ('speedshop:'.(int) (auth()->user()?->activeSpeedshopId() ?? 0)) : null;

        $rules = [
            'kode_supplier' => [
                'required',
                'string',
                'min:2',
                'max:3',
                Rule::unique('suppliers', 'kode_supplier')
                    ->when($isSpeedshopRoute && $speedshopScope, fn ($r) => $r->where('supplier_scope', $speedshopScope)),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'kota_id' => ['nullable', 'exists:kotas,id'],
            'jenis_rekening' => ['nullable', 'string', 'max:50'],
            'nama_rekening' => ['nullable', 'string', 'max:255'],
            'nomor_rekening' => ['nullable', 'string', 'max:50'],
        ];

        if (str_starts_with($routeName, 'admin.')) {
            $rules['section'] = ['required', Rule::in(Supplier::sections())];
        }

        $validated = $request->validate($rules);

        if (! str_starts_with($routeName, 'admin.')) {
            $validated['section'] = $this->sectionFromRouteName($routeName);
        }

        // Speedshop master supplier: set per outlet
        if ($validated['section'] === Supplier::SECTION_SPEEDSHOP && $isSpeedshopRoute) {
            $ssid = auth()->user()?->activeSpeedshopId();
            $validated['speedshop_id'] = $ssid ? (int) $ssid : null;
            $validated['supplier_scope'] = 'speedshop:'.(int) ($ssid ?? 0);
        }

        $validated['kode_supplier'] = mb_strtoupper(trim((string) ($validated['kode_supplier'] ?? '')));
        Supplier::create($validated);
        session()->flash('success', 'Supplier berhasil ditambahkan.');

        return response()->json(['success' => true]);
    }

    public function show(Request $request, Supplier $supplier)
    {
        $this->denySpeedshopStafSupplierMaster();

        $this->authorizeSupplierSection($request, $supplier);

        return response()->json($supplier);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->denySpeedshopStafSupplierMaster();

        $this->authorizeSupplierSection($request, $supplier);

        $routeName = $request->route()->getName() ?? '';
        $isSpeedshopRoute = str_starts_with($routeName, 'speedshop.');
        $speedshopScope = $isSpeedshopRoute ? ('speedshop:'.(int) (auth()->user()?->activeSpeedshopId() ?? 0)) : null;

        $rules = [
            'kode_supplier' => [
                'required',
                'string',
                'min:2',
                'max:3',
                Rule::unique('suppliers', 'kode_supplier')
                    ->ignore($supplier->id)
                    ->when($isSpeedshopRoute && $speedshopScope, fn ($r) => $r->where('supplier_scope', $speedshopScope)),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'kota_id' => ['nullable', 'exists:kotas,id'],
            'jenis_rekening' => ['nullable', 'string', 'max:50'],
            'nama_rekening' => ['nullable', 'string', 'max:255'],
            'nomor_rekening' => ['nullable', 'string', 'max:50'],
        ];

        if (str_starts_with($routeName, 'admin.')) {
            $rules['section'] = ['required', Rule::in(Supplier::sections())];
        }

        $validated = $request->validate($rules);

        if (! str_starts_with($routeName, 'admin.')) {
            unset($validated['section']);
        }

        if ($isSpeedshopRoute) {
            // Pastikan supplier tetap terikat ke outlet aktif jika edit dari speedshop route.
            $ssid = auth()->user()?->activeSpeedshopId();
            $validated['speedshop_id'] = $ssid ? (int) $ssid : null;
            $validated['supplier_scope'] = 'speedshop:'.(int) ($ssid ?? 0);
        }

        $validated['kode_supplier'] = mb_strtoupper(trim((string) ($validated['kode_supplier'] ?? '')));
        $supplier->update($validated);
        session()->flash('success', 'Supplier berhasil diperbarui.');

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, Supplier $supplier)
    {
        $this->denySpeedshopStafSupplierMaster();

        $this->authorizeSupplierSection($request, $supplier);

        $supplier->delete();
        session()->flash('success', 'Supplier berhasil dihapus.');

        return response()->json(['success' => true]);
    }

    public function export(Request $request)
    {
        $routeName = $request->route()->getName() ?? '';
        $section = $this->exportSection($request, $routeName);
        $filters = $request->only(['kode_supplier', 'nama', 'no_hp', 'email', 'kota', 'alamat', 'rekening', 'search']);
        if (str_starts_with($routeName, 'speedshop.') && $section === Supplier::SECTION_SPEEDSHOP) {
            $filters['_supplier_scopes'] = $this->speedshopSupplierScopes();
        }
        $export = new SupplierExport($request->get('search'), $section, $filters);

        return Excel::download($export, 'suppliers-'.now()->format('Y-m-d-His').'.xlsx');
    }

    public function import(Request $request)
    {
        $this->denySpeedshopStafSupplierMaster();

        $routeName = $request->route()->getName() ?? '';
        $section = $this->sectionFromRouteName($routeName);
        $speedshopId = str_starts_with($routeName, 'speedshop.') ? auth()->user()?->activeSpeedshopId() : null;

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        try {
            Excel::import(new SupplierImport($section, $speedshopId ? (int) $speedshopId : null), $request->file('file'));
            session()->flash('success', 'Import supplier berhasil.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $messages = [];
            foreach ($failures as $failure) {
                $messages[] = 'Baris '.$failure->row().': '.implode(', ', $failure->errors());
            }

            return back()->with('error', implode(' ', $messages));
        } catch (\Throwable $e) {
            return back()->with('error', 'Import gagal: '.$e->getMessage());
        }

        $indexRoute = match (true) {
            str_starts_with($routeName, 'produksi.') => 'produksi.master.suppliers',
            str_starts_with($routeName, 'warehouse.') => 'warehouse.suppliers.index',
            str_starts_with($routeName, 'speedshop.') => 'speedshop.suppliers.index',
            default => 'produksi.master.suppliers',
        };

        return redirect()->route($indexRoute);
    }

    public function template(Request $request)
    {
        $this->denySpeedshopStafSupplierMaster();

        return Excel::download(new SupplierTemplateExport, 'template-supplier.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $routeName = $request->route()->getName() ?? '';
        $section = $this->exportSection($request, $routeName);
        $items = $this->suppliersFilteredBuilder($request, $section)->get();
        $filename = 'suppliers-'.now()->format('Y-m-d-His').'.pdf';
        $pdfView = match (true) {
            str_starts_with($routeName, 'warehouse.') => 'warehouse.suppliers.pdf',
            str_starts_with($routeName, 'speedshop.') => 'speedshop.suppliers.pdf',
            default => 'produksi.master-data.suppliers-pdf',
        };

        return Pdf::loadView($pdfView, [
            'items' => $items,
            'search' => $request->get('search'),
            'printedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream($filename);
    }

    private function exportSection(Request $request, string $routeName): ?string
    {
        if (str_starts_with($routeName, 'produksi.')) {
            return Supplier::SECTION_PRODUKSI;
        }
        if (str_starts_with($routeName, 'warehouse.')) {
            return Supplier::SECTION_WAREHOUSE;
        }
        if (str_starts_with($routeName, 'speedshop.')) {
            return Supplier::SECTION_SPEEDSHOP;
        }
        if (str_starts_with($routeName, 'admin.')) {
            $section = $request->get('section');
            if ($section !== null && $section !== '' && in_array($section, Supplier::sections(), true)) {
                return $section;
            }

            return null;
        }

        return null;
    }

    private function sectionFromRouteName(string $routeName): string
    {
        return match (true) {
            str_starts_with($routeName, 'produksi.') => Supplier::SECTION_PRODUKSI,
            str_starts_with($routeName, 'warehouse.') => Supplier::SECTION_WAREHOUSE,
            str_starts_with($routeName, 'speedshop.') => Supplier::SECTION_SPEEDSHOP,
            default => Supplier::SECTION_PRODUKSI,
        };
    }

    private function authorizeSupplierSection(Request $request, Supplier $supplier): void
    {
        $routeName = $request->route()->getName() ?? '';
        if (str_starts_with($routeName, 'admin.')) {
            return;
        }

        $expected = match (true) {
            str_starts_with($routeName, 'produksi.') => Supplier::SECTION_PRODUKSI,
            str_starts_with($routeName, 'warehouse.') => Supplier::SECTION_WAREHOUSE,
            str_starts_with($routeName, 'speedshop.') => Supplier::SECTION_SPEEDSHOP,
            default => null,
        };

        if ($expected !== null && $supplier->section !== $expected) {
            abort(403);
        }

        // Speedshop route: batasi ke supplier outlet aktif (dan legacy shared: speedshop:0).
        if ($expected === Supplier::SECTION_SPEEDSHOP && str_starts_with($routeName, 'speedshop.')) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('suppliers', 'supplier_scope')) {
                $allowed = $this->speedshopSupplierScopes();
                if (! in_array((string) ($supplier->supplier_scope ?? ''), $allowed, true)) {
                    abort(403);
                }
            }
        }
    }
}

<?php

namespace App\Http\Controllers\Speedshop;

use App\Exports\BrandExport;
use App\Exports\BrandTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\BrandImport;
use App\Models\Brand;
use App\Support\SpeedshopSimpleMasterPageConfig;
use App\Support\UploadStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class MasterBrandController extends Controller
{
    use SpeedshopSimpleMasterPageConfig;

    protected function masterIndexRouteName(): string
    {
        return 'speedshop.brands';
    }

    protected function masterBasePath(): string
    {
        return 'speedshop/brands';
    }

    private function filteredQuery(Request $request)
    {
        $query = Brand::query();

        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%'.$request->string('nama')->toString().'%');
        }

        return $query;
    }

    public function index(Request $request)
    {
        $items = $this->filteredQuery($request)->orderBy('nama')->paginate(20)->withQueryString();
        $mp = $this->masterPageConfig();

        return view('speedshop.brands.index', compact('items', 'mp'));
    }

    public function export(Request $request)
    {
        return Excel::download(
            new BrandExport($request->only(['nama'])),
            'brands-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $items = $this->filteredQuery($request)->orderBy('nama')->get();

        return Pdf::loadView('speedshop.brands.pdf', [
            'items' => $items,
            'filters' => $request->only(['nama']),
            'printedAt' => now(),
        ])->setPaper('a4', 'portrait')->stream('brands-'.now()->format('Y-m-d-His').'.pdf');
    }

    public function template()
    {
        return Excel::download(new BrandTemplateExport, 'template-brands.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        try {
            Excel::import(new BrandImport, $request->file('file'));
            session()->flash('success', 'Import brand berhasil.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $messages = [];
            foreach ($e->failures() as $failure) {
                $messages[] = 'Baris '.$failure->row().': '.implode(', ', $failure->errors());
            }

            return back()->with('error', implode(' ', $messages));
        } catch (\Throwable $e) {
            return back()->with('error', 'Import gagal: '.$e->getMessage());
        }

        return redirect()->route($this->masterIndexRouteName().'.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'nama'),
            ],
        ]);

        Brand::create($validated);
        session()->flash('success', 'Brand berhasil ditambahkan.');

        return response()->json(['success' => true]);
    }

    public function show(Brand $brand)
    {
        return response()->json($brand);
    }

    public function update(Request $request, Brand $brand)
    {
        $validated = $request->validate([
            'nama' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brands', 'nama')->ignore($brand->id),
            ],
        ]);

        $brand->update($validated);
        session()->flash('success', 'Brand berhasil diperbarui.');

        return response()->json(['success' => true]);
    }

    public function destroy(Brand $brand)
    {
        if ($brand->products()->exists()) {
            return response()->json([
                'message' => 'Brand tidak dapat dihapus karena masih dipakai produk.',
            ], 422);
        }

        UploadStorage::delete($brand->logo_path);
        $brand->delete();
        session()->flash('success', 'Brand berhasil dihapus.');

        return response()->json(['success' => true]);
    }
}

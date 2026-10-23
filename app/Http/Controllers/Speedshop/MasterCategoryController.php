<?php

namespace App\Http\Controllers\Speedshop;

use App\Exports\CategoryExport;
use App\Exports\CategoryTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\CategoryImport;
use App\Models\Category;
use App\Support\SpeedshopSimpleMasterPageConfig;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class MasterCategoryController extends Controller
{
    use SpeedshopSimpleMasterPageConfig;

    protected function masterIndexRouteName(): string
    {
        return 'speedshop.categories';
    }

    protected function masterBasePath(): string
    {
        return 'speedshop/categories';
    }

    private function filteredQuery(Request $request)
    {
        $query = Category::query()->forSpeedshop();

        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%'.$request->string('nama')->toString().'%');
        }

        return $query;
    }

    public function index(Request $request)
    {
        $items = $this->filteredQuery($request)->orderBy('nama')->paginate(20)->withQueryString();
        $mp = $this->masterPageConfig();

        return view('speedshop.categories.index', compact('items', 'mp'));
    }

    public function export(Request $request)
    {
        return Excel::download(
            new CategoryExport(array_merge($request->only(['nama']), [
                'section' => Category::SECTION_SPEEDSHOP,
            ])),
            'categories-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $items = $this->filteredQuery($request)->orderBy('nama')->get();

        return Pdf::loadView('speedshop.categories.pdf', [
            'items' => $items,
            'filters' => $request->only(['nama']),
            'printedAt' => now(),
        ])->setPaper('a4', 'portrait')->stream('categories-'.now()->format('Y-m-d-His').'.pdf');
    }

    public function template()
    {
        return Excel::download(new CategoryTemplateExport, 'template-categories.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        try {
            Excel::import(new CategoryImport(Category::SECTION_SPEEDSHOP), $request->file('file'));
            session()->flash('success', 'Import kategori berhasil.');
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
                Rule::unique('categories', 'nama')->where(fn ($q) => $q
                    ->where('section', Category::SECTION_SPEEDSHOP)),
            ],
        ]);

        Category::create(array_merge($validated, [
            'section' => Category::SECTION_SPEEDSHOP,
        ]));
        session()->flash('success', 'Kategori berhasil ditambahkan.');

        return response()->json(['success' => true]);
    }

    public function show(Category $category)
    {
        abort_unless($category->section === Category::SECTION_SPEEDSHOP, 404);

        return response()->json($category);
    }

    public function update(Request $request, Category $category)
    {
        abort_unless($category->section === Category::SECTION_SPEEDSHOP, 404);

        $validated = $request->validate([
            'nama' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'nama')->where(fn ($q) => $q
                    ->where('section', Category::SECTION_SPEEDSHOP))->ignore($category->id),
            ],
        ]);

        $category->update($validated);
        session()->flash('success', 'Kategori berhasil diperbarui.');

        return response()->json(['success' => true]);
    }

    public function destroy(Category $category)
    {
        abort_unless($category->section === Category::SECTION_SPEEDSHOP, 404);

        if ($category->products()->exists()) {
            $count = $category->products()->count();
            $sample = $category->products()->orderBy('kode_produk')->limit(12)->pluck('kode_produk')->implode(', ');

            return response()->json([
                'message' => 'Kategori tidak dapat dihapus karena masih dipakai produk.',
                'detail' => 'Ada '.$count.' produk dengan kategori ini. Contoh kode: '.$sample.($count > 12 ? ' …' : '').' Ubah kategori produk tersebut di menu Database Produk, lalu hapus lagi kategori ini.',
            ], 422);
        }

        $category->delete();
        session()->flash('success', 'Kategori berhasil dihapus.');

        return response()->json(['success' => true]);
    }
}

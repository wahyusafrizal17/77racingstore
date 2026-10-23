<?php

namespace App\Http\Controllers\Speedshop;

use App\Exports\JasaServisExport;
use App\Exports\JasaServisTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\JasaServisImport;
use App\Models\JasaServis;
use App\Support\SpeedshopSimpleMasterPageConfig;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class JasaServisController extends Controller
{
    use SpeedshopSimpleMasterPageConfig;

    protected function masterIndexRouteName(): string
    {
        return 'speedshop.jasa-servis';
    }

    protected function masterBasePath(): string
    {
        return 'speedshop/jasa-servis';
    }

    private function filteredQuery(Request $request)
    {
        $query = JasaServis::query();

        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%'.$request->string('nama')->toString().'%');
        }

        if ($request->filled('biaya')) {
            $query->where('biaya', $request->integer('biaya'));
        }

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);
        $agg = (clone $query)->selectRaw('count(*) as total, coalesce(avg(biaya), 0) as avg_biaya, coalesce(max(biaya), 0) as max_biaya')->first();
        $jasaStats = [
            'total' => (int) ($agg->total ?? 0),
            'avg_biaya' => (int) round((float) ($agg->avg_biaya ?? 0)),
            'max_biaya' => (int) ($agg->max_biaya ?? 0),
        ];
        $items = $query->orderBy('nama')->paginate(20)->withQueryString();
        $mp = $this->masterPageConfig();

        return view('speedshop.jasa-servis.index', compact('items', 'jasaStats', 'mp'));
    }

    public function export(Request $request)
    {
        return Excel::download(
            new JasaServisExport($request->only(['nama', 'biaya'])),
            'jasa-servis-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $items = $this->filteredQuery($request)->orderBy('nama')->get();
        $filename = 'jasa-servis-'.now()->format('Y-m-d-His').'.pdf';

        return Pdf::loadView('speedshop.jasa-servis.pdf', [
            'items' => $items,
            'filters' => $request->only(['nama', 'biaya']),
            'printedAt' => now(),
        ])->setPaper('a4', 'portrait')->stream($filename);
    }

    public function template()
    {
        return Excel::download(new JasaServisTemplateExport, 'template-jasa-servis.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        try {
            Excel::import(new JasaServisImport, $request->file('file'));
            session()->flash('success', 'Import jasa servis berhasil.');
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
        $request->merge([
            'nama' => trim((string) $request->input('nama')),
        ]);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('jasa_servis', 'nama')],
            'biaya' => ['required', 'numeric', 'min:0'],
        ], [
            'nama.unique' => 'Nama jasa servis sudah ada.',
        ]);

        $validated['biaya'] = (int) $validated['biaya'];

        JasaServis::create($validated);
        session()->flash('success', 'Jasa servis berhasil ditambahkan.');

        return response()->json(['success' => true]);
    }

    public function show(JasaServis $jasaServi)
    {
        return response()->json($jasaServi);
    }

    public function update(Request $request, JasaServis $jasaServi)
    {
        $request->merge([
            'nama' => trim((string) $request->input('nama')),
        ]);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('jasa_servis', 'nama')->ignore($jasaServi->id)],
            'biaya' => ['required', 'numeric', 'min:0'],
        ], [
            'nama.unique' => 'Nama jasa servis sudah ada.',
        ]);

        $validated['biaya'] = (int) $validated['biaya'];

        $jasaServi->update($validated);
        session()->flash('success', 'Jasa servis berhasil diperbarui.');

        return response()->json(['success' => true]);
    }

    public function destroy(JasaServis $jasaServi)
    {
        $jasaServi->delete();
        session()->flash('success', 'Jasa servis berhasil dihapus.');

        return response()->json(['success' => true]);
    }
}

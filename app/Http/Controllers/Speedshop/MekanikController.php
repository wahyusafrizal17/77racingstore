<?php

namespace App\Http\Controllers\Speedshop;

use App\Exports\MekanikExport;
use App\Exports\MekanikTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Mekanik;
use App\Models\MekanikLevel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MekanikController extends Controller
{
    /** @return list<string> */
    private function levelOptions(): array
    {
        return MekanikLevel::query()
            ->orderBy('nama')
            ->pluck('nama')
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();
    }

    private function filteredQuery(Request $request)
    {
        $query = Mekanik::query()
            ->with(['mekanikLevel', 'speedshop', 'user'])
            ->whereNotNull('user_id');

        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%'.$request->string('nama')->toString().'%');
        }

        if ($request->filled('no_hp')) {
            $query->where('no_hp', 'like', '%'.$request->string('no_hp')->toString().'%');
        }

        if ($request->filled('level')) {
            $level = $request->string('level')->toString();
            $query->where(function ($q) use ($level) {
                $q->where('spesialisasi', $level)
                    ->orWhereHas('mekanikLevel', fn ($inner) => $inner->where('nama', $level));
            });
        }

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->filteredQuery($request);
        $agg = (clone $query)->selectRaw('count(*) as total, sum(case when status = ? then 1 else 0 end) as aktif, count(distinct coalesce(mekanik_level_id, spesialisasi)) as level_count', ['aktif'])->first();
        $mekanikStats = [
            'total' => (int) ($agg->total ?? 0),
            'aktif' => (int) ($agg->aktif ?? 0),
            'level_count' => (int) ($agg->level_count ?? 0),
        ];
        $items = $query->orderBy('nama')->paginate(20)->withQueryString();
        $levels = $this->levelOptions();

        return view('speedshop.mekaniks.index', compact('items', 'levels', 'mekanikStats'));
    }

    public function export(Request $request)
    {
        return Excel::download(
            new MekanikExport($request->only(['nama', 'no_hp', 'level'])),
            'mekanik-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $items = $this->filteredQuery($request)->orderBy('nama')->get();
        $filename = 'mekanik-'.now()->format('Y-m-d-His').'.pdf';

        return Pdf::loadView('speedshop.mekaniks.pdf', [
            'items' => $items,
            'filters' => $request->only(['nama', 'no_hp', 'level']),
            'printedAt' => now(),
        ])->setPaper('a4', 'portrait')->stream($filename);
    }

    public function template()
    {
        return Excel::download(new MekanikTemplateExport, 'template-mekanik.xlsx');
    }

    public function import(Request $request)
    {
        return response()->json([
            'message' => 'Import mekanik dinonaktifkan. Kelola mekanik melalui Admin → Karyawan (role Mekanik).',
        ], 422);
    }

    public function store(Request $request)
    {
        return response()->json([
            'message' => 'Tambah mekanik melalui Admin → Karyawan dengan role Mekanik.',
        ], 422);
    }

    public function show(Mekanik $mekanik)
    {
        $mekanik->load(['mekanikLevel', 'user']);

        return response()->json([
            'id' => $mekanik->id,
            'nama' => $mekanik->nama,
            'no_hp' => $mekanik->no_hp,
            'level' => $mekanik->levelName(),
            'email' => $mekanik->user?->email,
        ]);
    }

    public function update(Request $request, Mekanik $mekanik)
    {
        return response()->json([
            'message' => 'Edit mekanik melalui Admin → Karyawan dengan role Mekanik.',
        ], 422);
    }

    public function destroy(Mekanik $mekanik)
    {
        return response()->json([
            'message' => 'Hapus mekanik melalui Admin → Karyawan dengan role Mekanik.',
        ], 422);
    }
}

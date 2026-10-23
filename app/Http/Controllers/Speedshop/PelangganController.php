<?php

namespace App\Http\Controllers\Speedshop;

use App\Exports\Speedshop\PelangganSpeedshopExport;
use App\Exports\Speedshop\PelangganSpeedshopTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\Speedshop\PelangganSpeedshopImport;
use App\Models\Kendaraan;
use App\Models\Pelanggan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class PelangganController extends Controller
{
    private function resolveOrCreateSpeedshopPelanggan(array $validated): Pelanggan
    {
        $validated = [
            'nama' => $validated['nama'],
            'no_hp' => $validated['no_hp'] ?? null,
            'alamat' => $validated['alamat'] ?? null,
        ];

        $query = Pelanggan::query()->forSpeedshop();

        if (filled($validated['no_hp'])) {
            $existing = (clone $query)->where('no_hp', $validated['no_hp'])->first();
            if ($existing) {
                if (blank($existing->alamat) && filled($validated['alamat'])) {
                    $existing->update(['alamat' => $validated['alamat']]);
                }

                return $existing;
            }
        } elseif (filled($validated['alamat'])) {
            $existing = (clone $query)
                ->where('nama', $validated['nama'])
                ->where('alamat', $validated['alamat'])
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        return Pelanggan::create([
            'section' => Pelanggan::SECTION_SPEEDSHOP,
            'speedshop_id' => null,
            'nama' => $validated['nama'],
            'no_hp' => $validated['no_hp'],
            'alamat' => $validated['alamat'],
        ]);
    }

    public function index(Request $request)
    {
        $query = Pelanggan::query()->forSpeedshop();

        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%'.$request->nama.'%');
        }

        if ($request->filled('no_hp')) {
            $query->where('no_hp', 'like', '%'.$request->no_hp.'%');
        }

        if ($request->filled('alamat')) {
            $query->where('alamat', 'like', '%'.$request->alamat.'%');
        }

        if (
            $request->filled('search')
            && ! $request->filled('nama')
            && ! $request->filled('no_hp')
            && ! $request->filled('alamat')
        ) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nama', 'like', "%{$s}%")
                    ->orWhere('no_hp', 'like', "%{$s}%")
                    ->orWhere('alamat', 'like', "%{$s}%");
            });
        }

        $agg = (clone $query)->selectRaw("count(*) as total, sum(case when no_hp is not null and no_hp != '' then 1 else 0 end) as punya_no_hp")->first();
        $pelangganStats = [
            'total' => (int) ($agg->total ?? 0),
            'punya_no_hp' => (int) ($agg->punya_no_hp ?? 0),
            'punya_kendaraan' => (clone $query)->whereHas('kendaraans')->count(),
        ];

        $pelanggans = $query->orderBy('nama')->paginate(20)->withQueryString();

        return view('speedshop.pelanggan.index', compact('pelanggans', 'pelangganStats'));
    }

    public function export(Request $request)
    {
        abort_unless(str_starts_with($request->route()->getName() ?? '', 'speedshop.'), 403);

        $filters = $request->only(['nama', 'no_hp', 'alamat', 'search']);

        return Excel::download(
            new PelangganSpeedshopExport($filters),
            'pelanggan-speedshop-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        abort_unless(str_starts_with($request->route()->getName() ?? '', 'speedshop.'), 403);

        $query = Pelanggan::query()->forSpeedshop();

        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%'.$request->nama.'%');
        }

        if ($request->filled('no_hp')) {
            $query->where('no_hp', 'like', '%'.$request->no_hp.'%');
        }

        if ($request->filled('alamat')) {
            $query->where('alamat', 'like', '%'.$request->alamat.'%');
        }

        if (
            $request->filled('search')
            && ! $request->filled('nama')
            && ! $request->filled('no_hp')
            && ! $request->filled('alamat')
        ) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nama', 'like', "%{$s}%")
                    ->orWhere('no_hp', 'like', "%{$s}%")
                    ->orWhere('alamat', 'like', "%{$s}%");
            });
        }

        $items = $query->orderBy('nama')->get();
        $filename = 'pelanggan-speedshop-'.now()->format('Y-m-d-His').'.pdf';

        return Pdf::loadView('speedshop.pelanggan.pdf', [
            'items' => $items,
            'search' => $request->get('search'),
            'filters' => $request->only(['nama', 'no_hp', 'alamat']),
            'printedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream($filename);
    }

    public function template()
    {
        return Excel::download(new PelangganSpeedshopTemplateExport, 'template-pelanggan-speedshop.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ]);

        try {
            Excel::import(new PelangganSpeedshopImport, $request->file('file'));
            session()->flash('success', 'Import pelanggan speedshop berhasil.');
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

        return redirect()->route('speedshop.pelanggans.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
        ]);

        $pelanggan = $this->resolveOrCreateSpeedshopPelanggan($validated);
        session()->flash('success', 'Pelanggan berhasil disimpan.');

        return response()->json(['success' => true, 'pelanggan' => $pelanggan->load('kendaraans')]);
    }

    public function show(Pelanggan $pelanggan)
    {
        return response()->json($pelanggan);
    }

    public function update(Request $request, Pelanggan $pelanggan)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
        ]);

        $pelanggan->update($validated);
        session()->flash('success', 'Pelanggan berhasil diperbarui.');

        return response()->json(['success' => true]);
    }

    public function quickAdd(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
            'nomor_polisi' => ['required', 'string', 'max:20'],
            'merk' => ['required', 'string', 'max:100'],
            'tipe' => ['nullable', 'string', 'max:100'],
            'tahun' => ['nullable', 'integer', 'min:1900', 'max:2100'],
        ]);

        $pelanggan = $this->resolveOrCreateSpeedshopPelanggan($validated);

        $kendaraan = Kendaraan::create([
            'pelanggan_id' => $pelanggan->id,
            'nomor_polisi' => $validated['nomor_polisi'],
            'merk' => $validated['merk'],
            'tipe' => $validated['tipe'] ?? null,
            'tahun' => $validated['tahun'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'pelanggan' => $pelanggan->load('kendaraans'),
        ]);
    }

    public function quickAddKendaraan(Request $request)
    {
        $validated = $request->validate([
            'pelanggan_id' => ['required', 'exists:pelanggan,id'],
            'nomor_polisi' => ['required', 'string', 'max:20'],
            'merk' => ['required', 'string', 'max:100'],
            'tipe' => ['nullable', 'string', 'max:100'],
            'tahun' => ['nullable', 'integer', 'min:1900', 'max:2100'],
        ]);

        $kendaraan = Kendaraan::create([
            'pelanggan_id' => $validated['pelanggan_id'],
            'nomor_polisi' => $validated['nomor_polisi'],
            'merk' => $validated['merk'],
            'tipe' => $validated['tipe'] ?? null,
            'tahun' => $validated['tahun'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'kendaraan' => $kendaraan,
        ]);
    }

    public function destroy(Pelanggan $pelanggan)
    {
        $pelanggan->delete();
        session()->flash('success', 'Pelanggan berhasil dihapus.');

        return response()->json(['success' => true]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Speedshop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(Request $request): View
    {
        $q = Speedshop::query();

        if ($request->filled('nama')) {
            $term = '%'.addcslashes($request->string('nama')->toString(), '%_\\').'%';
            $q->where('nama', 'like', $term);
        }

        $statTotal = (clone $q)->count();
        $statUsersLinked = User::query()->whereNotNull('speedshop_id')->count();

        $branches = (clone $q)->orderBy('nama')->paginate(20)->withQueryString();

        return view('admin.branches.index', [
            'branches' => $branches,
            'statTotal' => $statTotal,
            'statUsersLinked' => $statUsersLinked,
        ]);
    }

    public function show(Speedshop $speedshop): JsonResponse
    {
        return response()->json([
            'id' => $speedshop->id,
            'nama' => $speedshop->nama,
            'no_hp' => $speedshop->no_hp,
            'alamat' => $speedshop->alamat,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ]);

        Speedshop::create([
            'nama' => $validated['nama'],
            'no_hp' => $validated['no_hp'] ?? null,
            'alamat' => $validated['alamat'] ?? null,
            'kota_id' => null,
            'warehouse_id' => null,
            'mutasi_warehouse_id' => null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.branches.index')->with('success', 'Cabang speedshop berhasil ditambahkan.');
    }

    public function update(Request $request, Speedshop $speedshop): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ]);

        $speedshop->update([
            'nama' => $validated['nama'],
            'no_hp' => $validated['no_hp'] ?? null,
            'alamat' => $validated['alamat'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.branches.index')->with('success', 'Cabang berhasil diperbarui.');
    }

    public function destroy(Request $request, Speedshop $speedshop): JsonResponse|RedirectResponse
    {
        if (User::where('speedshop_id', $speedshop->id)->exists()) {
            $msg = 'Tidak dapat menghapus cabang: masih ada pengguna yang terhubung ke cabang ini.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $speedshop->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.branches.index')->with('success', 'Cabang berhasil dihapus.');
    }
}

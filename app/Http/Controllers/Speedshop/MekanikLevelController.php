<?php

namespace App\Http\Controllers\Speedshop;

use App\Http\Controllers\Controller;
use App\Models\Mekanik;
use App\Models\MekanikLevel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MekanikLevelController extends Controller
{
    private function ensureManager(): void
    {
        if (! auth()->user()?->isManager()) {
            abort(403);
        }
    }

    public function index()
    {
        $this->ensureManager();

        $items = MekanikLevel::query()
            ->orderBy('nama')
            ->get();

        return view('speedshop.mekanik-levels.index', compact('items'));
    }

    public function store(Request $request)
    {
        $this->ensureManager();

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:60', Rule::unique('mekanik_levels', 'nama')],
        ]);

        MekanikLevel::create([
            'nama' => trim($validated['nama']),
        ]);

        session()->flash('success', 'Level mekanik berhasil ditambahkan.');

        return response()->json(['success' => true]);
    }

    public function show(MekanikLevel $mekanik_level)
    {
        $this->ensureManager();

        return response()->json($mekanik_level);
    }

    public function update(Request $request, MekanikLevel $mekanik_level)
    {
        $this->ensureManager();

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:60', Rule::unique('mekanik_levels', 'nama')->ignore($mekanik_level->id)],
        ]);

        $mekanik_level->update([
            'nama' => trim($validated['nama']),
        ]);

        session()->flash('success', 'Level mekanik berhasil diperbarui.');

        return response()->json(['success' => true]);
    }

    public function destroy(MekanikLevel $mekanik_level)
    {
        $this->ensureManager();

        $inUse = Mekanik::query()
            ->where('mekanik_level_id', $mekanik_level->id)
            ->orWhere('spesialisasi', $mekanik_level->nama)
            ->exists();
        if ($inUse) {
            return response()->json([
                'success' => false,
                'message' => 'Level sedang dipakai pada data mekanik, tidak bisa dihapus.',
            ], 422);
        }

        $mekanik_level->delete();
        session()->flash('success', 'Level mekanik berhasil dihapus.');

        return response()->json(['success' => true]);
    }
}


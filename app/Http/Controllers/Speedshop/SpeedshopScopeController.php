<?php

namespace App\Http\Controllers\Speedshop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SpeedshopScopeController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $scope = (string) $request->input('scope', '');

        $allowedIds = $user->activeSpeedshopIds();
        if (count($allowedIds) <= 1) {
            return back();
        }

        if ($scope === 'all') {
            $request->session()->forget('speedshop_scope_id');

            return back()->with('success', 'Menampilkan gabungan semua speedshop.');
        }

        $speedshopId = (int) $scope;
        if (! in_array($speedshopId, $allowedIds, true)) {
            return back()->with('error', 'Pilihan speedshop tidak valid.');
        }

        $request->session()->put('speedshop_scope_id', $speedshopId);

        return back()->with('success', 'Filter speedshop berhasil diperbarui.');
    }
}

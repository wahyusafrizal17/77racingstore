<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Speedshop;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'userCount' => User::query()->adminKaryawan()->count(),
            'branchCount' => Speedshop::count(),
            'warehouseCount' => 0,
        ]);
    }
}

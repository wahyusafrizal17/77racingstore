<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLocation;
use App\Models\AttendanceRecord;
use Database\Seeders\AttendanceLocationSeeder;
use Illuminate\Http\Request;

class AttendanceLocationController extends Controller
{
    public function index(Request $request)
    {
        if (AttendanceLocation::query()->count() === 0) {
            (new AttendanceLocationSeeder)->run();
        }

        $locations = AttendanceLocation::query()->orderBy('id')->get();

        $todayCounts = AttendanceRecord::query()
            ->where('status', AttendanceRecord::STATUS_VALID)
            ->whereDate('checked_at', now()->toDateString())
            ->selectRaw('attendance_location_id, type, COUNT(*) as total')
            ->groupBy('attendance_location_id', 'type')
            ->get()
            ->groupBy('attendance_location_id');

        return view('admin.attendance-locations.index', [
            'locations' => $locations,
            'todayCounts' => $todayCounts,
            'canEdit' => auth()->user()?->canManageHrAdminSettings() ?? false,
        ]);
    }

    public function sync(Request $request)
    {
        abort_unless(auth()->user()?->canManageHrAdminSettings(), 403);

        (new AttendanceLocationSeeder)->run();

        return redirect()
            ->route('admin.attendance-locations.index')
            ->with('success', 'Lokasi absensi berhasil disinkronkan dari Warehouse & cabang Speedshop.');
    }

    public function update(Request $request, AttendanceLocation $attendanceLocation)
    {
        abort_unless(auth()->user()?->canManageHrAdminSettings(), 403, 'Hanya pengelola panel HR yang dapat mengatur koordinat lokasi.');

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['nullable', 'integer', 'min:10', 'max:500'],
            'name' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $attendanceLocation->update([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'radius_meters' => $validated['radius_meters'] ?? 50,
            'name' => $validated['name'] ?? $attendanceLocation->name,
            'is_active' => array_key_exists('is_active', $validated)
                ? (bool) $validated['is_active']
                : $attendanceLocation->is_active,
        ]);

        return back()->with('success', 'Koordinat «'.$attendanceLocation->displayName().'» berhasil disimpan.');
    }
}

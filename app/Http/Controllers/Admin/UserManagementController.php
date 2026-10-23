<?php

namespace App\Http\Controllers\Admin;

use App\Exports\Admin\UsersExport;
use App\Exports\Admin\UsersTemplateWorkbookExport;
use App\Http\Controllers\Controller;
use App\Imports\Admin\UsersImport;
use App\Models\AttendanceLocation;
use App\Models\MekanikLevel;
use App\Models\Speedshop;
use App\Models\User;
use App\Support\AdminUserPayload;
use App\Services\MekanikUserSyncService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class UserManagementController extends Controller
{
    private function rolesForSection(string $section): array
    {
        return AdminUserPayload::rolesForSection($section);
    }

    private function filteredQuery(Request $request)
    {
        $q = User::query()->adminKaryawan()->with(['warehouse', 'speedshop', 'assignedSpeedshops', 'mekanik.mekanikLevel', 'attendanceLocation']);

        if ($request->filled('name')) {
            $term = '%'.addcslashes($request->string('name')->toString(), '%_\\').'%';
            $q->where('name', 'like', $term);
        }
        if ($request->filled('email')) {
            $term = '%'.addcslashes($request->string('email')->toString(), '%_\\').'%';
            $q->where('email', 'like', $term);
        }
        if ($request->filled('section')) {
            $section = $request->string('section')->toString();
            if ($section === User::SECTION_MODIFIKASI_ORI) {
                $q->whereIn('section', [User::SECTION_MODIFIKASI_ORI, 'produksi', 'warehouse']);
            } else {
                $q->where('section', $section);
            }
        }
        if ($request->filled('role')) {
            $role = $request->string('role')->toString();
            $section = $request->filled('section') ? $request->string('section')->toString() : '';

            if ($section === User::SECTION_MODIFIKASI_ORI) {
                AdminUserPayload::applyModifikasiOriRoleFilter($q, $role);
            } elseif ($section === 'speedshop') {
                AdminUserPayload::applySpeedshopRoleFilter($q, $role);
            } else {
                $q->where('role', $role);
            }
        }

        return $q;
    }

    public function index(Request $request): View
    {
        $q = $this->filteredQuery($request);

        $statTotal = (clone $q)->count();
        $statHeadHrAdmins = User::adminPanelUserQuery()->count();

        $users = (clone $q)->orderBy('name')->paginate(25)->withQueryString();

        $sectionFilter = $request->filled('section') ? $request->string('section')->toString() : null;
        $adminFilterRoleOptions = AdminUserPayload::filterRoleOptions($sectionFilter);
        $selectedRole = $request->string('role')->toString();
        if ($selectedRole !== '' && ! in_array($selectedRole, $adminFilterRoleOptions, true)) {
            $adminFilterRoleOptions[] = $selectedRole;
        }

        return view('admin.users.index', [
            'users' => $users,
            'statTotal' => $statTotal,
            'statHeadHrAdmins' => $statHeadHrAdmins,
            'warehouses' => collect(),
            'speedshops' => Speedshop::orderBy('nama')->get(),
            'adminDivisiOptions' => AdminUserPayload::divisiOptions(),
            'adminRolesBySection' => AdminUserPayload::rolesBySectionForAdmin(),
            'adminFilterRoleOptions' => $adminFilterRoleOptions,
            'mekanikLevels' => MekanikLevel::query()->orderBy('nama')->get(['id', 'nama']),
            'attendanceLocations' => AttendanceLocation::query()->orderBy('name')->get(['id', 'name', 'is_active']),
        ]);
    }

    public function export(Request $request)
    {
        return Excel::download(
            new UsersExport($request->only(['name', 'email', 'section', 'role'])),
            'pengguna-'.now()->format('Y-m-d-His').'.xlsx',
        );
    }

    public function exportPdf(Request $request)
    {
        $users = $this->filteredQuery($request)->orderBy('name')->get();
        $filename = 'pengguna-'.now()->format('Y-m-d-His').'.pdf';

        return Pdf::loadView('admin.users.pdf', [
            'users' => $users,
            'filters' => $request->only(['name', 'email', 'section', 'role']),
            'printedAt' => now(),
        ])->setPaper('a4', 'landscape')->stream($filename);
    }

    public function template()
    {
        return Excel::download(new UsersTemplateWorkbookExport, 'template-pengguna.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
            'sync_replace' => ['nullable', 'boolean'],
        ]);

        $syncReplace = $request->boolean('sync_replace');

        $warehouseIdsByName = [];

        $speedshopIdsByName = Speedshop::query()
            ->get(['id', 'nama'])
            ->mapWithKeys(fn (Speedshop $s) => [mb_strtolower((string) $s->nama) => (int) $s->id])
            ->all();

        $mekanikLevelIdsByName = MekanikLevel::query()
            ->get(['id', 'nama'])
            ->mapWithKeys(fn (MekanikLevel $level) => [mb_strtolower((string) $level->nama) => (int) $level->id])
            ->all();

        try {
            $importer = new UsersImport(
                $warehouseIdsByName,
                $speedshopIdsByName,
                $mekanikLevelIdsByName,
                $syncReplace,
                auth()->id(),
            );
            Excel::import($importer, $request->file('file'));

            if ($syncReplace && $importer->skipped > 0 && $importer->created === 0 && $importer->updated === 0) {
                return redirect()
                    ->route('admin.users.index')
                    ->with('warning', implode(' ', $importer->errors));
            }

            $msg = 'Import selesai: '.$importer->created.' baru, '.$importer->updated.' diperbarui';
            if ($importer->skipped > 0) {
                $msg .= ', '.$importer->skipped.' dilewati';
            }
            if ($syncReplace) {
                $msg .= ', '.$importer->deleted.' dihapus (sinkron penuh)';
                if ($importer->deleteSkipped > 0) {
                    $msg .= ', '.$importer->deleteSkipped.' gagal dihapus';
                }
            }
            $msg .= '.';

            $detail = array_merge($importer->errors, $importer->deleteErrors);
            if ($detail !== []) {
                session()->flash('warning', $msg.' '.implode(' ', $detail));
            } else {
                session()->flash('success', $msg);
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Import gagal: '.$e->getMessage());
        }

        return redirect()->route('admin.users.index');
    }

    public function show(User $user): JsonResponse
    {
        if (! $user->isVisibleInAdminKaryawan()) {
            abort(404);
        }

        $user->load(['mekanik', 'assignedSpeedshops']);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'section' => AdminUserPayload::sectionForAdminForm($user->section),
            'role' => AdminUserPayload::roleForAdminForm($user->section, $user->role),
            'warehouse_id' => $user->warehouse_id,
            'speedshop_id' => $user->speedshop_id,
            'speedshop_ids' => $user->assignedSpeedshopIds() ?: ($user->speedshop_id ? [(int) $user->speedshop_id] : []),
            'mekanik_level_id' => $user->isSpeedshopMekanik() ? $user->mekanik?->mekanik_level_id : null,
            'no_hp' => $user->isSpeedshopMekanik() ? $user->mekanik?->no_hp : null,
            'attendance_location_id' => $user->attendance_location_id,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $section = $request->string('section')->toString();
        $roles = $this->rolesForSection($section);

        $validated = $this->validateKaryawan($request, $section, $roles);
        $validated = $this->normalizePayload($validated);

        if ($msg = $this->sectionAssignmentError($validated)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg, 'errors' => ['section' => [$msg]]], 422);
            }

            return back()->withInput()->withErrors(['section' => $msg]);
        }

        $mekanikMeta = $this->extractMekanikMeta($validated);
        $speedshopIds = $this->extractSpeedshopIds($validated);
        $payload = $validated;
        unset($payload['password_confirmation'], $payload['mekanik_level_id'], $payload['no_hp'], $payload['speedshop_ids']);

        $user = User::create($payload);
        MekanikUserSyncService::syncFromUser($user, $mekanikMeta['mekanik_level_id'], $mekanikMeta['no_hp']);
        $user->syncAssignedSpeedshops(
            ($payload['section'] ?? '') === 'speedshop' ? $speedshopIds : []
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.users.index')->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): JsonResponse|RedirectResponse
    {
        if (! $user->isVisibleInAdminKaryawan()) {
            abort(404);
        }

        $section = $request->string('section')->toString();
        $roles = $this->rolesForSection($section);

        $validated = $this->validateKaryawan($request, $section, $roles, $user);
        $validated = $this->normalizePayload($validated);

        if ($msg = $this->sectionAssignmentError($validated)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg, 'errors' => ['section' => [$msg]]], 422);
            }

            return back()->withInput()->withErrors(['section' => $msg]);
        }

        if ($user->id === auth()->id() && $user->canAccessAdminPanel()) {
            $stillAdminPanelUser = User::isModifikasiOriHrAdminRole(
                $validated['section'],
                $validated['role'],
            );
            if (! $stillAdminPanelUser) {
                $other = User::adminPanelUserQuery()
                    ->where('id', '!=', $user->id)
                    ->exists();
                if (! $other) {
                    $err = 'Buat atau aktifkan akun HR (77racing) lain sebelum mengubah divisi/role akun Anda.';
                    if ($request->wantsJson()) {
                        return response()->json(['message' => $err, 'errors' => ['role' => [$err]]], 422);
                    }

                    return back()->withInput()->withErrors(['role' => $err]);
                }
            }
        }

        $mekanikMeta = $this->extractMekanikMeta($validated);
        $speedshopIds = $this->extractSpeedshopIds($validated);
        $payload = $validated;
        unset($payload['password_confirmation'], $payload['mekanik_level_id'], $payload['no_hp'], $payload['speedshop_ids']);

        if (empty($payload['password'])) {
            unset($payload['password']);
        }

        $user->update($payload);
        MekanikUserSyncService::syncFromUser($user->fresh(), $mekanikMeta['mekanik_level_id'], $mekanikMeta['no_hp']);
        $user->fresh()->syncAssignedSpeedshops(
            ($payload['section'] ?? '') === 'speedshop' ? $speedshopIds : []
        );

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.users.index')->with('success', 'Karyawan berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): JsonResponse|RedirectResponse
    {
        if (! $user->isVisibleInAdminKaryawan()) {
            abort(404);
        }

        if ($user->id === auth()->id()) {
            $msg = 'Anda tidak dapat menghapus akun sendiri.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        if ($user->canAccessAdminPanel()) {
            $other = User::adminPanelUserQuery()
                ->where('id', '!=', $user->id)
                ->exists();
            if (! $other) {
                $msg = 'Harus ada minimal satu akun yang bisa membuka panel admin.';
                if ($request->wantsJson()) {
                    return response()->json(['message' => $msg], 422);
                }

                return back()->with('error', $msg);
            }
        }

        if ($msg = MekanikUserSyncService::deleteForUser($user)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $user->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.users.index')->with('success', 'Karyawan berhasil dihapus.');
    }

    private function sectionAssignmentError(array $v): ?string
    {
        return AdminUserPayload::sectionAssignmentError($v);
    }

    private function normalizePayload(array $v): array
    {
        return AdminUserPayload::normalize($v);
    }

    /** @param list<string> $roles */
    private function validateKaryawan(Request $request, string $section, array $roles, ?User $user = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'section' => ['required', Rule::in(AdminUserPayload::divisiValues())],
            'role' => ['required', 'string', Rule::in($roles)],
            'warehouse_id' => ['nullable', 'integer'],
            'speedshop_id' => ['nullable', 'exists:speedshops,id'],
            'speedshop_ids' => ['nullable', 'array'],
            'speedshop_ids.*' => ['integer', 'exists:speedshops,id'],
            'mekanik_level_id' => ['nullable', 'exists:mekanik_levels,id'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'attendance_location_id' => ['nullable', 'exists:attendance_locations,id'],
        ];

        if (! $user) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        } else {
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        }

        if ($section === 'speedshop' && $request->string('role')->toString() === User::ROLE_SPEEDSHOP_MEKANIK) {
            $rules['mekanik_level_id'] = ['required', 'exists:mekanik_levels,id'];
        }

        return $request->validate($rules);
    }

    /** @param array<string, mixed> $validated */
    private function extractMekanikMeta(array $validated): array
    {
        return [
            'mekanik_level_id' => isset($validated['mekanik_level_id']) ? (int) $validated['mekanik_level_id'] : null,
            'no_hp' => isset($validated['no_hp']) ? (string) $validated['no_hp'] : null,
        ];
    }

    /** @param array<string, mixed> $validated @return list<int> */
    private function extractSpeedshopIds(array $validated): array
    {
        $ids = collect($validated['speedshop_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === [] && ! empty($validated['speedshop_id'])) {
            $ids = [(int) $validated['speedshop_id']];
        }

        return $ids;
    }
}

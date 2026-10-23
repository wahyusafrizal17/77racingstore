<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceValidatorController extends Controller
{
    public function index(Request $request): View
    {
        $employees = User::query()
            ->adminKaryawan()
            ->with('attendanceValidator:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'section', 'role', 'attendance_validator_id']);

        $validators = $employees->values();

        $assignmentsByValidator = $employees
            ->filter(fn (User $employee) => $employee->attendance_validator_id)
            ->groupBy('attendance_validator_id')
            ->map(fn ($group) => $group->map(fn (User $employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
            ])->values()->all());

        $selectedValidatorId = $request->integer('validator_id') ?: null;
        if ($selectedValidatorId && ! $validators->contains('id', $selectedValidatorId)) {
            $selectedValidatorId = null;
        }

        return view('admin.attendance-validators.index', [
            'validators' => $validators,
            'employeesPayload' => $employees->map(fn (User $employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
                'section' => $employee->section,
                'role' => $employee->role,
                'validator_id' => $employee->attendance_validator_id,
                'validator_name' => $employee->attendanceValidator?->name,
            ])->values()->all(),
            'assignmentsPayload' => $assignmentsByValidator->mapWithKeys(
                fn ($group, $validatorId) => [(string) $validatorId => collect($group)->pluck('id')->all()]
            )->all(),
            'selectedValidatorId' => $selectedValidatorId,
            'canEdit' => auth()->user()?->canManageHrAdminSettings() ?? false,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageHrAdminSettings(), 403, 'Hanya pengelola panel HR yang dapat mengatur penanggung jawab validasi.');

        $validated = $request->validate([
            'validator_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', Rule::exists('users', 'id')],
        ]);

        $validatorId = (int) $validated['validator_id'];
        $employeeIds = User::query()
            ->adminKaryawan()
            ->pluck('id')
            ->flip();

        abort_unless(isset($employeeIds[$validatorId]), 404);

        $selectedIds = collect($validated['employee_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id !== $validatorId && isset($employeeIds[$id]))
            ->unique()
            ->values()
            ->all();

        User::query()
            ->where('attendance_validator_id', $validatorId)
            ->when($selectedIds !== [], fn ($query) => $query->whereNotIn('id', $selectedIds))
            ->update(['attendance_validator_id' => null]);

        if ($selectedIds !== []) {
            User::query()
                ->whereIn('id', $selectedIds)
                ->update(['attendance_validator_id' => $validatorId]);
        }

        $validatorName = User::query()->whereKey($validatorId)->value('name');

        return redirect()
            ->route('admin.attendance-validators.index', ['validator_id' => $validatorId])
            ->with('success', 'Bawahan validasi untuk '.$validatorName.' berhasil disimpan.');
    }
}

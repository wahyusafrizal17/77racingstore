@extends('layouts.admin')

@section('title', 'Penanggung Jawab Approval Izin')

@section('content')

@if(session('success'))
<div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
    {{ session('success') }}
</div>
@endif

<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Penanggung Jawab Validasi</h1>
    <p class="mt-1 text-sm text-gray-500">Pilih penanggung jawab, lalu tentukan karyawan yang pengajuan izinnya perlu disetujui olehnya.</p>
    @unless($canEdit)
    <p class="mt-3 inline-block rounded-xl bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800 ring-1 ring-amber-200">Hanya Head HR yang dapat mengubah penugasan.</p>
    @endunless
</div>

<div
    x-data="attendanceValidatorPicker({
        validators: @js($validators->map(fn ($v) => ['id' => $v->id, 'name' => $v->name, 'section' => $v->section, 'role' => $v->role])->values()->all()),
        employees: @js($employeesPayload),
        assignments: @js($assignmentsPayload),
        initialValidatorId: @js($selectedValidatorId),
        canEdit: @js($canEdit),
    })"
    class="grid gap-5 lg:grid-cols-[minmax(260px,320px)_1fr]"
>
    {{-- Langkah 1: Pilih penanggung jawab --}}
    <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm lg:sticky lg:top-6 lg:self-start">
        <div class="mb-3 flex items-center gap-2">
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand text-xs font-bold text-brand-ink">1</span>
            <h2 class="text-sm font-bold text-gray-900">Penanggung Jawab</h2>
        </div>

        <input
            type="search"
            x-model="validatorSearch"
            placeholder="Cari nama…"
            class="mb-3 h-10 w-full rounded-xl border border-gray-200 px-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40"
        >

        <div class="max-h-[420px] space-y-1 overflow-y-auto pr-1">
            <template x-for="validator in filteredValidators()" :key="validator.id">
                <button
                    type="button"
                    @click="selectValidator(validator.id)"
                    class="flex w-full items-start gap-3 rounded-xl border px-3 py-2.5 text-left transition"
                    :class="selectedValidatorId === validator.id
                        ? 'border-red-300 bg-red-50 ring-1 ring-red-200'
                        : 'border-transparent hover:border-gray-200 hover:bg-gray-50'"
                >
                    <span
                        class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold"
                        :class="selectedValidatorId === validator.id ? 'bg-brand text-brand-ink' : 'bg-gray-100 text-gray-600'"
                        x-text="validator.name.charAt(0).toUpperCase()"
                    ></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-gray-900" x-text="validator.name"></span>
                        <span class="block truncate text-xs text-gray-500" x-text="validator.section + ' · ' + validator.role"></span>
                        <span class="mt-1 inline-block text-[11px] font-medium text-gray-400" x-text="assigneeCount(validator.id) + ' bawahan'"></span>
                    </span>
                </button>
            </template>
            <p x-show="filteredValidators().length === 0" class="py-6 text-center text-sm text-gray-400">Tidak ditemukan.</p>
        </div>
    </section>

    {{-- Langkah 2: Pilih bawahan --}}
    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <template x-if="!selectedValidatorId">
            <div class="flex min-h-[320px] flex-col items-center justify-center px-6 py-12 text-center">
                <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <p class="text-sm font-semibold text-gray-700">Pilih penanggung jawab terlebih dahulu</p>
                <p class="mt-1 max-w-sm text-sm text-gray-500">Setelah itu Anda bisa memilih karyawan yang menjadi bawahan validasinya.</p>
            </div>
        </template>

        <template x-if="selectedValidatorId">
            <div>
                <div class="border-b border-gray-100 px-5 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="mb-1 flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand text-xs font-bold text-brand-ink">2</span>
                                <h2 class="text-sm font-bold text-gray-900">Bawahan Approval</h2>
                            </div>
                            <p class="text-sm text-gray-500">
                                Pengajuan izin karyawan terpilih akan disetujui oleh
                                <strong class="text-gray-800" x-text="selectedValidator()?.name"></strong>
                            </p>
                        </div>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600" x-text="selectedEmployeeIds.length + ' dipilih'"></span>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.attendance-validators.update') }}" class="p-5">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="validator_id" :value="selectedValidatorId">
                    <template x-for="id in selectedEmployeeIds" :key="'emp-' + id">
                        <input type="hidden" name="employee_ids[]" :value="id">
                    </template>

                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        <input
                            type="search"
                            x-model="employeeSearch"
                            placeholder="Cari bawahan…"
                            class="h-10 min-w-[200px] flex-1 rounded-xl border border-gray-200 px-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40"
                        >
                        <template x-if="canEdit">
                            <div class="flex gap-2">
                                <button type="button" @click="selectAllVisible()" class="h-10 rounded-xl border border-gray-200 px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50">Pilih semua</button>
                                <button type="button" @click="clearSelection()" class="h-10 rounded-xl border border-gray-200 px-3 text-xs font-semibold text-gray-700 hover:bg-gray-50">Kosongkan</button>
                            </div>
                        </template>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2">
                        <template x-for="employee in filteredEmployees()" :key="employee.id">
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-xl border px-3 py-3 transition"
                                :class="isSelected(employee.id)
                                    ? 'border-red-300 bg-red-50/60'
                                    : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                            >
                                <input
                                    type="checkbox"
                                    class="mt-1 h-4 w-4 rounded border-gray-300 accent-brand text-brand focus:ring-brand"
                                    :checked="isSelected(employee.id)"
                                    @change="toggleEmployee(employee.id)"
                                    :disabled="!canEdit"
                                >
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-gray-900" x-text="employee.name"></span>
                                    <span class="block text-xs text-gray-500" x-text="employee.section + ' · ' + employee.role"></span>
                                    <span
                                        x-show="employee.validator_id && employee.validator_id !== selectedValidatorId"
                                        class="mt-1 block text-[11px] font-medium text-amber-700"
                                        x-text="'Saat ini: ' + (employee.validator_name || '—')"
                                    ></span>
                                </span>
                            </label>
                        </template>
                    </div>

                    <p x-show="filteredEmployees().length === 0" class="py-8 text-center text-sm text-gray-400">Tidak ada karyawan ditemukan.</p>

                    <template x-if="canEdit">
                        <div class="mt-5 flex justify-end border-t border-gray-100 pt-4">
                            <button type="submit" class="inline-flex h-11 items-center rounded-xl bg-brand px-5 text-sm font-semibold text-brand-ink shadow-lg shadow-brand/35 hover:bg-brand-hover">
                                Simpan Bawahan
                            </button>
                        </div>
                    </template>
                </form>
            </div>
        </template>
    </section>
</div>

{{-- Ringkasan penugasan --}}
<section class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
    <h2 class="mb-3 text-sm font-bold text-gray-900">Ringkasan Penugasan</h2>
    <div class="space-y-2">
        @php
            $hasAssignment = collect($employeesPayload)->contains(fn ($e) => ! empty($e['validator_id']));
        @endphp
        @if($hasAssignment)
            @foreach($validators as $validator)
                @php
                    $assigneeNames = collect($employeesPayload)
                        ->where('validator_id', $validator->id)
                        ->pluck('name');
                @endphp
                @if($assigneeNames->isNotEmpty())
                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1 rounded-xl bg-gray-50 px-4 py-3 text-sm">
                    <span class="font-semibold text-gray-900">{{ $validator->name }}</span>
                    <span class="text-gray-400">→</span>
                    <span class="text-gray-600">{{ $assigneeNames->join(', ') }}</span>
                </div>
                @endif
            @endforeach
        @else
            <p class="text-sm text-gray-500">Belum ada penugasan. Pilih penanggung jawab di atas untuk mulai.</p>
        @endif
    </div>
</section>

@push('scripts')
<script>
function attendanceValidatorPicker(config) {
    return {
        validators: config.validators,
        employees: config.employees,
        assignments: config.assignments,
        canEdit: config.canEdit,
        selectedValidatorId: config.initialValidatorId || null,
        selectedEmployeeIds: [],
        validatorSearch: '',
        employeeSearch: '',

        init() {
            if (this.selectedValidatorId) {
                this.loadSelection(this.selectedValidatorId);
            }
        },

        selectValidator(id) {
            this.selectedValidatorId = id;
            this.employeeSearch = '';
            this.loadSelection(id);
            const url = new URL(window.location.href);
            url.searchParams.set('validator_id', id);
            window.history.replaceState({}, '', url);
        },

        loadSelection(validatorId) {
            const key = String(validatorId);
            this.selectedEmployeeIds = [...(this.assignments[key] || [])];
        },

        selectedValidator() {
            return this.validators.find(v => v.id === this.selectedValidatorId) || null;
        },

        assigneeCount(validatorId) {
            return (this.assignments[String(validatorId)] || []).length;
        },

        filteredValidators() {
            const q = this.validatorSearch.trim().toLowerCase();
            if (!q) return this.validators;
            return this.validators.filter(v =>
                v.name.toLowerCase().includes(q)
                || v.section.toLowerCase().includes(q)
                || v.role.toLowerCase().includes(q)
            );
        },

        filteredEmployees() {
            if (!this.selectedValidatorId) return [];
            const q = this.employeeSearch.trim().toLowerCase();
            return this.employees
                .filter(e => e.id !== this.selectedValidatorId)
                .filter(e => !q || e.name.toLowerCase().includes(q) || e.section.toLowerCase().includes(q));
        },

        isSelected(id) {
            return this.selectedEmployeeIds.includes(id);
        },

        toggleEmployee(id) {
            if (this.isSelected(id)) {
                this.selectedEmployeeIds = this.selectedEmployeeIds.filter(x => x !== id);
            } else {
                this.selectedEmployeeIds.push(id);
            }
        },

        selectAllVisible() {
            const ids = this.filteredEmployees().map(e => e.id);
            this.selectedEmployeeIds = [...new Set([...this.selectedEmployeeIds, ...ids])];
        },

        clearSelection() {
            this.selectedEmployeeIds = [];
        },
    };
}
</script>
@endpush
@endsection

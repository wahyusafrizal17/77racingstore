@extends($layout)

@section('title', 'Profil Saya')

@section('content')
@php
    $fotoUrl = $user->fotoProfilUrl();
    $hasCustomFoto = $user->hasCustomFotoProfil();
    $sectionLabel = match ($user->section) {
        'speedshop' => 'Speedshop',
        'warehouse' => 'Warehouse',
        'produksi' => 'Produksi',
        'owner' => 'Owner',
        'admin' => 'Admin',
        \App\Models\User::SECTION_MODIFIKASI_ORI => '77racing',
        default => ucfirst((string) $user->section),
    };
@endphp
<div class="w-full space-y-6">

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div class="grid w-full gap-6 lg:grid-cols-[minmax(280px,360px)_minmax(0,1fr)] lg:items-stretch">
        <div class="flex w-full flex-col rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-1 flex-col items-center justify-center text-center">
                <div class="relative">
                    <img
                        src="{{ $fotoUrl }}"
                        alt="{{ $user->name }}"
                        id="profileAvatarPreview"
                        class="h-28 w-28 rounded-2xl border border-gray-200 object-cover {{ $hasCustomFoto ? '' : 'bg-white p-3 object-contain' }}"
                    >
                    <form method="POST" action="{{ route('profil.foto') }}" enctype="multipart/form-data" id="profileFotoForm" class="absolute -bottom-2 -right-2">
                        @csrf
                        <label for="foto_profil" class="inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-700 shadow-sm transition hover:bg-gray-50" title="Ubah foto">
                            <input type="file" name="foto_profil" id="foto_profil" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp" class="sr-only">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </label>
                    </form>
                </div>
                <h2 class="mt-5 text-lg font-semibold text-gray-900">{{ $user->name }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $user->email }}</p>
                <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">{{ $sectionLabel }}</span>
                    <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-medium text-red-700">{{ $user->role }}</span>
                </div>
                <p class="mt-3 text-xs text-gray-400">JPG, PNG, atau WebP · maks. 2 MB</p>
                @error('foto_profil')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
                @if($hasCustomFoto)
                    <form method="POST" action="{{ route('profil.foto.hapus') }}" class="mt-3" onsubmit="return confirm('Hapus foto profil?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-700">Hapus foto</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="flex w-full flex-col rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-gray-900">Informasi Akun</h2>
            <p class="mt-1 text-sm text-gray-500">Perbarui data diri. Kosongkan password jika tidak ingin diganti.</p>

            <form method="POST" action="{{ route('profil.update') }}" class="mt-6 space-y-4">
                @csrf
                @method('PUT')

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700">Nama lengkap</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-gray-900 outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="username" class="mb-1.5 block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" id="username" name="username" value="{{ old('username', $user->username) }}"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-gray-900 outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                        @error('username')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="no_hp" class="mb-1.5 block text-sm font-medium text-gray-700">No. HP</label>
                        <input type="text" id="no_hp" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" placeholder="08xxxxxxxxxx"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-gray-900 outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                        @error('no_hp')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="tanggal_lahir" class="mb-1.5 block text-sm font-medium text-gray-700">Tanggal lahir</label>
                        <div class="relative">
                            <input type="text" id="tanggal_lahir" name="tanggal_lahir"
                                value="{{ old('tanggal_lahir', optional($user->tanggal_lahir)->format('Y-m-d')) }}"
                                placeholder="Pilih tanggal lahir"
                                autocomplete="off"
                                class="profil-datepicker w-full rounded-xl border border-gray-200 px-3 py-2.5 pr-10 text-sm text-gray-900 outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                            <span class="pointer-events-none absolute inset-y-0 right-3 inline-flex items-center text-gray-400" aria-hidden="true">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                        </div>
                        @error('tanggal_lahir')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-gray-900 outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">Password baru</label>
                        <input type="password" id="password" name="password" autocomplete="new-password"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-gray-900 outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                        @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700">Konfirmasi password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                            class="w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm text-gray-900 outline-none focus:border-brand focus:ring-2 focus:ring-brand/40">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="submit" class="inline-flex h-10 items-center justify-center rounded-xl bg-brand px-5 text-sm font-semibold text-brand-ink shadow-sm transition hover:bg-brand-hover">
                        Simpan Profil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
<style>
.flatpickr-calendar {
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 12px 30px rgba(15, 23, 42, .12);
  font-family: inherit;
}
.flatpickr-day.selected,
.flatpickr-day.startRange,
.flatpickr-day.endRange,
.flatpickr-day.selected:hover {
  background: #dc2626;
  border-color: #dc2626;
}
.profil-datepicker.flatpickr-input,
input.profil-datepicker {
  background-color: #fff;
  cursor: pointer;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/id.js"></script>
<script>
(function () {
    const input = document.getElementById('foto_profil');
    const form = document.getElementById('profileFotoForm');
    const preview = document.getElementById('profileAvatarPreview');
    if (input && form) {
        input.addEventListener('change', function () {
            if (!input.files || !input.files[0]) return;
            if (preview) {
                const url = URL.createObjectURL(input.files[0]);
                preview.src = url;
                preview.classList.remove('p-3', 'object-contain');
                preview.classList.add('object-cover');
            }
            form.submit();
        });
    }

    const tanggalLahir = document.getElementById('tanggal_lahir');
    if (tanggalLahir && window.flatpickr) {
        if (flatpickr.l10ns && flatpickr.l10ns.id) {
            flatpickr.localize(flatpickr.l10ns.id);
        }
        flatpickr(tanggalLahir, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'j F Y',
            allowInput: false,
            disableMobile: true,
            maxDate: new Date(new Date().setHours(0, 0, 0, 0) - 86400000),
            minDate: '1900-01-01',
            defaultDate: tanggalLahir.value || null,
        });
    }
})();
</script>
@endpush
@endsection

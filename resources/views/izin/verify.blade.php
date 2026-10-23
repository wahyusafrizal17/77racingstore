<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi TTD Izin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
@php
    $signer = $result['signer'];
    $employee = $result['employee'];
@endphp
<div class="mx-auto flex min-h-screen max-w-lg flex-col justify-center px-4 py-10">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm text-center">
        <img src="{{ asset('images/logo.png') }}" alt="77racing" class="mx-auto h-12 w-12 object-contain" onerror="this.style.display='none'">
        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Verifikasi Tanda Tangan Digital</p>
        <h1 class="mt-2 text-xl font-bold text-gray-900">{{ $result['label'] }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $result['doc_title'] ?? 'Pengajuan Izin' }}</p>

        <div class="mt-6 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-800">
            @if(($result['slot'] ?? '') === 'pemohon')
                Pengajuan ini telah ditandatangani pemohon secara digital.
            @else
                Persetujuan izin ini telah ditandatangani secara digital.
            @endif
        </div>

        <dl class="mt-6 space-y-3 text-left text-sm">
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                <dt class="text-gray-500">Referensi</dt>
                <dd class="font-semibold text-gray-900">{{ $result['nomor'] ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                <dt class="text-gray-500">Karyawan</dt>
                <dd class="font-semibold text-gray-900">{{ $employee?->name ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                <dt class="text-gray-500">Periode</dt>
                <dd class="font-semibold text-gray-900">{{ $result['date_range'] ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                <dt class="text-gray-500">Ditandatangani oleh</dt>
                <dd class="font-semibold text-gray-900">{{ $signer?->name ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 border-b border-gray-100 pb-2">
                <dt class="text-gray-500">Jabatan</dt>
                <dd class="font-semibold text-gray-900">{{ $signer?->role ?? '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-gray-500">Waktu</dt>
                <dd class="font-semibold text-gray-900">{{ $result['signed_at']?->timezone(config('app.timezone'))->format('d M Y H:i') }}</dd>
            </div>
        </dl>
    </div>
</div>
</body>
</html>

@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')

<div class="grid gap-5 sm:grid-cols-3">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Karyawan</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $userCount }}</p>
        <p class="mt-1 text-sm text-gray-500">Akun karyawan internal</p>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Cabang speedshop</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $branchCount }}</p>
        <p class="mt-1 text-sm text-gray-500">Outlet terdaftar</p>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Gudang</p>
        <p class="mt-2 text-3xl font-bold text-gray-900">{{ $warehouseCount }}</p>
        <p class="mt-1 text-sm text-gray-500">Referensi warehouse</p>
    </div>
</div>

@endsection

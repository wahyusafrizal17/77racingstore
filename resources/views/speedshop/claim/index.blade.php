@extends('layouts.speedshop')

@section('title', 'Claim Produk Cacat (Coming Soon)')

@section('content')
@php
    $filterBaseClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
@endphp
<div class="space-y-5">
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('speedshop.dashboard') }}" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">Claim Produk Cacat</span>
    </nav>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
        <div class="px-6 py-10 text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Claim Produk Cacat</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-gray-900">Coming Soon</h1>
            <p class="mt-3 text-sm text-gray-500 max-w-md mx-auto">
                Fitur claim produk cacat untuk penggantian dari warehouse sedang disiapkan. Sementara, proses klaim masih dilakukan manual di luar sistem.
            </p>
        </div>
    </div>
</div>
@endsection

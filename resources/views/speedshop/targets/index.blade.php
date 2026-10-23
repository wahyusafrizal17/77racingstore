@extends('layouts.speedshop')

@section('title', 'Target Speedshop')

@section('content')
@php
    $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $periode)->translatedFormat('F Y');
@endphp

@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@endpush

<div class="ss-dashboard ss-dash-page" x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })">
<div class="ss-dash-content space-y-5">
    <div class="flex flex-col gap-3 ss-dash-enter sm:flex-row sm:items-start sm:justify-between" style="--ss-delay: 0ms">
        <div>
            <h1 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Target Speedshop</h1>
            <p class="mt-1 text-[13px] text-gray-500">Satu angka total per bulan · sparepart 65% · jasa 35%</p>
        </div>
        <form method="GET" action="{{ route('speedshop.targets.index') }}" class="flex items-center gap-2">
            <input type="month" name="bulan" value="{{ $periode }}" onchange="this.form.submit()"
                class="h-10 rounded-xl border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
        </form>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('speedshop.targets.update') }}" class="space-y-5">
        @csrf
        @method('PUT')
        <input type="hidden" name="bulan" value="{{ $periode }}">

        <section class="panel-card ss-dash-enter" style="--ss-delay: 80ms">
            <div class="panel-card-hd">
                <div>
                    <div class="panel-card-title">Target cabang · {{ $monthLabel }}</div>
                    <p class="panel-card-sub">Isi total target bulanan. Sparepart dan jasa terhitung otomatis.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="mk-perf-table">
                    <thead>
                        <tr>
                            <th>Cabang</th>
                            <th class="text-right">Target total</th>
                            <th class="text-right">Sparepart 65%</th>
                            <th class="text-right">Jasa 35%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cabang as $row)
                        <tr x-data="{
                            total: {{ (int) $row['target_bulanan'] }},
                            parse(v) { return parseInt(String(v).replace(/\D/g, ''), 10) || 0 },
                            fmt(n) { return new Intl.NumberFormat('id-ID').format(n || 0) },
                        }">
                            <td><strong>{{ $row['nama'] }}</strong></td>
                            <td class="text-right">
                                <div class="relative inline-flex">
                                    <span class="pointer-events-none absolute inset-y-0 left-2.5 flex items-center text-xs font-medium text-gray-400">Rp</span>
                                    <input type="text" inputmode="numeric" :value="fmt(total)" @input="total = parse($event.target.value)"
                                        class="h-9 w-44 rounded-lg border border-gray-200 py-1 pl-8 pr-2 text-right text-sm tabular-nums focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                                    <input type="hidden" name="cabang[{{ $row['id'] }}]" :value="total">
                                </div>
                            </td>
                            <td class="text-right tabular-nums" x-text="'Rp ' + fmt(Math.round((total || 0) * 0.65))"></td>
                            <td class="text-right tabular-nums" x-text="'Rp ' + fmt((total || 0) - Math.round((total || 0) * 0.65))"></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="table-empty">Belum ada cabang.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel-card ss-dash-enter" style="--ss-delay: 140ms">
            <div class="panel-card-hd">
                <div>
                    <div class="panel-card-title">Target mekanik · {{ $monthLabel }}</div>
                    <p class="panel-card-sub">Dibanding pencapaian jasa + sparepart di dashboard, bukan jumlah WO.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="mk-perf-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Jabatan</th>
                            <th>Cabang</th>
                            <th class="text-right">Target pencapaian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mekaniks as $row)
                        <tr x-data="{
                            total: {{ (int) $row['target_bulanan'] }},
                            parse(v) { return parseInt(String(v).replace(/\D/g, ''), 10) || 0 },
                            fmt(n) { return new Intl.NumberFormat('id-ID').format(n || 0) },
                        }">
                            <td><strong>{{ $row['nama'] }}</strong></td>
                            <td>{{ $row['jabatan'] }}</td>
                            <td>{{ $row['cabang'] }}</td>
                            <td class="text-right">
                                <div class="relative inline-flex">
                                    <span class="pointer-events-none absolute inset-y-0 left-2.5 flex items-center text-xs font-medium text-gray-400">Rp</span>
                                    <input type="text" inputmode="numeric" :value="fmt(total)" @input="total = parse($event.target.value)"
                                        class="h-9 w-44 rounded-lg border border-gray-200 py-1 pl-8 pr-2 text-right text-sm tabular-nums focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40">
                                    <input type="hidden" name="mekanik[{{ $row['id'] }}]" :value="total">
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="table-empty">Belum ada mekanik aktif.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="inline-flex h-10 items-center rounded-xl bg-brand px-4 text-sm font-semibold text-brand-ink shadow-lg shadow-brand/35 hover:bg-brand-hover">
                Simpan target
            </button>
        </div>
    </form>
</div>
</div>
@endsection

@extends('layouts.admin')

@section('title', 'Lokasi Absensi')

@section('content')

<div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Lokasi Absensi</h1>
        <p class="mt-1 text-sm text-gray-500">Set titik koordinat untuk Warehouse & 4 cabang Speedshop. Radius default 50 meter.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        @if($canEdit)
        <form method="POST" action="{{ route('admin.attendance-locations.sync') }}">
            @csrf
            <button type="submit" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                Sinkronkan lokasi
            </button>
        </form>
        @endif
        @unless($canEdit)
        <p class="rounded-xl bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800 ring-1 ring-amber-200">Hanya Head HR yang dapat mengubah koordinat.</p>
        @endunless
    </div>
</div>

@if(session('success'))
<div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
@endif

@if($locations->isEmpty())
<div class="rounded-2xl border border-dashed border-gray-200 bg-white px-6 py-14 text-center">
    <p class="text-sm font-semibold text-gray-900">Belum ada lokasi absensi</p>
    <p class="mt-1 text-sm text-gray-500">Data lokasi belum di-seed di server ini. Sinkronkan dari Warehouse & cabang Speedshop.</p>
    @if($canEdit)
    <form method="POST" action="{{ route('admin.attendance-locations.sync') }}" class="mt-5">
        @csrf
        <button type="submit" class="rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-brand-ink hover:bg-brand-hover">
            Sinkronkan lokasi sekarang
        </button>
    </form>
    @endif
</div>
@endif

<div class="grid gap-4 lg:grid-cols-2">
    @foreach($locations as $loc)
    @php
        $counts = $todayCounts->get($loc->id) ?? collect();
        $in = (int) optional($counts->firstWhere('type', 'check_in'))->total;
        $out = (int) optional($counts->firstWhere('type', 'check_out'))->total;
    @endphp
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" x-data="{
        lat: @js($loc->latitude),
        lng: @js($loc->longitude),
        openMap() {
            if (this.lat == null || this.lng == null) return;
            window.open('https://www.google.com/maps?q=' + this.lat + ',' + this.lng, '_blank');
        },
        useMyGps() {
            if (!navigator.geolocation) return;
            navigator.geolocation.getCurrentPosition((pos) => {
                this.lat = pos.coords.latitude.toFixed(7);
                this.lng = pos.coords.longitude.toFixed(7);
            });
        }
    }">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">{{ str_replace('_', ' ', $loc->code) }}</p>
                <h2 class="text-lg font-bold text-gray-900">{{ $loc->displayName() }}</h2>
                <p class="mt-1 text-xs text-gray-500">
                    @if($loc->hasCoordinates())
                        <span class="font-medium text-emerald-600">Koordinat siap</span> · radius {{ $loc->radius_meters }} m
                    @else
                        <span class="font-medium text-amber-600">Belum diset</span>
                    @endif
                </p>
            </div>
            <div class="rounded-xl bg-gray-50 px-3 py-2 text-center">
                <p class="text-[10px] font-semibold uppercase text-gray-400">Hari ini</p>
                <p class="text-sm font-bold text-gray-800">{{ $in }} in · {{ $out }} out</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.attendance-locations.update', $loc) }}" class="mt-4 space-y-3">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-500">Latitude</label>
                    <input type="number" step="any" name="latitude" x-model="lat" value="{{ old('latitude', $loc->latitude) }}"
                           class="h-10 w-full rounded-xl border border-gray-200 px-3 text-sm tabular-nums outline-none focus:border-brand focus:ring-2 focus:ring-brand/40"
                           @disabled(! $canEdit) required>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-500">Longitude</label>
                    <input type="number" step="any" name="longitude" x-model="lng" value="{{ old('longitude', $loc->longitude) }}"
                           class="h-10 w-full rounded-xl border border-gray-200 px-3 text-sm tabular-nums outline-none focus:border-brand focus:ring-2 focus:ring-brand/40"
                           @disabled(! $canEdit) required>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-500">Radius (meter)</label>
                    <input type="number" name="radius_meters" min="10" max="500" value="{{ old('radius_meters', $loc->radius_meters) }}"
                           class="h-10 w-full rounded-xl border border-gray-200 px-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40"
                           @disabled(! $canEdit)>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-500">Nama tampilan</label>
                    <input type="text" name="name" value="{{ old('name', $loc->isHeadOffice() ? 'Head Office' : $loc->name) }}"
                           class="h-10 w-full rounded-xl border border-gray-200 px-3 text-sm outline-none focus:border-brand focus:ring-2 focus:ring-brand/40"
                           @disabled(! $canEdit)>
                </div>
            </div>

            @if($canEdit)
            <div class="flex flex-wrap gap-2 pt-1">
                <button type="button" @click="useMyGps()" class="rounded-lg bg-gray-900 px-3 py-2 text-xs font-semibold text-white hover:bg-black">Pakai GPS saya</button>
                <button type="button" @click="openMap()" class="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-gray-700 ring-1 ring-gray-200">Buka di Maps</button>
                <button type="submit" class="ml-auto rounded-lg bg-brand px-4 py-2 text-xs font-bold text-brand-ink shadow-sm shadow-brand/35 hover:bg-brand-hover">Simpan</button>
            </div>
            @else
            <button type="button" @click="openMap()" class="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-gray-700 ring-1 ring-gray-200">Buka di Maps</button>
            @endif
        </form>
    </div>
    @endforeach
</div>
@endsection

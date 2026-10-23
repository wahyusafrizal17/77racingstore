@php
    $filterBaseClass = $filterBaseClass ?? 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 placeholder-gray-400 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $hasActiveFilters = request()->filled('kode') || request()->filled('nama') || request()->filled('category_id') || request()->filled('brand_id');
    $mp = $mp ?? [];
@endphp

<div class="space-y-4">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
            <p class="text-xs font-medium text-gray-500">Total Arsip</p>
            <p class="mt-1 text-xl font-bold text-gray-900">{{ number_format($totalTrashedCount ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
            <p class="text-xs font-medium text-gray-500">Hasil Filter</p>
            <p class="mt-1 text-xl font-bold text-gray-900">{{ number_format($items->total(), 0, ',', '.') }}</p>
        </div>
        <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
            <p class="text-xs font-medium text-gray-500">Halaman</p>
            <p class="mt-1 text-xl font-bold text-gray-900">{{ $items->currentPage() }} / {{ max(1, $items->lastPage()) }}</p>
        </div>
    </div>

    <div class="panel-card panel-card-flush">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Daftar Arsip</h2>
                <p class="mt-1 text-xs text-gray-500">{{ $mp['trashed_subtitle'] ?? 'Produk yang diarsipkan dari master outlet' }}</p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                @if($canManageUnusedCleanup ?? false)
                <button type="button" data-arsip-unused
                        class="btn-secondary inline-flex h-9 items-center gap-2 border-rose-200 bg-rose-50 px-4 text-sm text-rose-800 hover:bg-rose-100"
                        title="Lihat produk arsip yang tidak dipakai di outlet ini dan boleh dibersihkan">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Produk tidak terpakai
                </button>
                @endif
                <button type="button" data-arsip-restore-all data-arsip-total="{{ (int) ($totalTrashedCount ?? 0) }}"
                        @if(($totalTrashedCount ?? 0) === 0) disabled @endif
                        class="btn-secondary inline-flex h-9 items-center gap-2 border-emerald-300 bg-emerald-600 px-4 text-sm text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                    Pulihkan semua ({{ number_format((int) ($totalTrashedCount ?? 0), 0, ',', '.') }})
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <form method="GET" action="{{ route($mp['route_trashed'] ?? 'speedshop.master.produk.trashed') }}" onsubmit="return false;">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-[#f7f3f4]">
                            <th class="w-16 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kode</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nama Produk</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Kategori</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Brand</th>
                            <th class="px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tanggal arsip</th>
                            <th class="w-36 px-5 py-3.5 text-[11px] font-semibold uppercase tracking-wider text-gray-500"></th>
                        </tr>
                        <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2">
                                <input name="kode" value="{{ request('kode') }}" placeholder="Cari kode..." data-arsip-filter class="{{ $filterBaseClass }}">
                            </th>
                            <th class="px-5 py-2">
                                <input name="nama" value="{{ request('nama') }}" placeholder="Cari nama..." data-arsip-filter class="{{ $filterBaseClass }}">
                            </th>
                            <th class="px-5 py-2">
                                <select name="category_id" data-arsip-filter class="{{ $filterBaseClass }}">
                                    <option value="">Semua</option>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}" @selected((string) request('category_id') === (string) $c->id)>{{ $c->nama }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-5 py-2">
                                <select name="brand_id" data-arsip-filter class="{{ $filterBaseClass }}">
                                    <option value="">Semua</option>
                                    @foreach($brands as $b)
                                        <option value="{{ $b->id }}" @selected((string) request('brand_id') === (string) $b->id)>{{ $b->nama }}</option>
                                    @endforeach
                                </select>
                            </th>
                            <th class="px-5 py-2"></th>
                            <th class="px-5 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    @forelse($items as $i => $p)
                    @php
                        $archivedAt = $p->speedshopMasterArchives->first()?->archived_at ?? $p->hidden_from_warehouse_master_at;
                    @endphp
                    <tr class="transition hover:bg-[#fcfafb]">
                        <td class="px-5 py-4 tabular-nums text-gray-500">{{ $items->firstItem() + $i }}</td>
                        <td class="px-5 py-4 font-mono text-sm font-semibold text-gray-700">{{ $p->kode_produk }}</td>
                        <td class="px-5 py-4 font-semibold text-gray-900">{{ $p->nama_produk }}</td>
                        <td class="px-5 py-4 text-gray-700">{{ $p->category?->nama ?? '—' }}</td>
                        <td class="px-5 py-4 text-gray-700">{{ $p->brand?->nama ?? '—' }}</td>
                        <td class="px-5 py-4 text-gray-600">
                            {{ $archivedAt ? $archivedAt->timezone(config('app.timezone'))->format('d/m/Y H:i') : '—' }}
                        </td>
                        <td class="px-5 py-4">
                            <button type="button" data-arsip-restore="{{ $p->id }}" data-arsip-label="{{ $p->kode_produk }} — {{ $p->nama_produk }}"
                                    class="btn-secondary inline-flex h-8 items-center gap-1.5 border-emerald-300 bg-emerald-600 px-3 text-xs text-white hover:bg-emerald-700">
                                Pulihkan
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                            {{ $hasActiveFilters ? 'Tidak ada arsip sesuai filter.' : 'Tidak ada entri arsip.' }}
                        </td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </form>
        </div>

        @if($items->hasPages())
        <div class="arsip-modal-pagination border-t border-gray-100 px-5 py-4">{{ $items->links() }}</div>
        @endif
    </div>
</div>

@extends('layouts.speedshop')

@section('title', 'Detail Hutang Supplier')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
@endpush

@php
    use App\Models\SpeedshopKeuanganRekening;
    use App\Models\WarehouseSupplierTagihan;
    use App\Models\WarehouseSupplierTagihanPembayaran;
    $tz = config('app.timezone');
    $uploadDisk = (string) config('filesystems.upload_disk', 'public');
    $indexRoute = 'speedshop.keuangan.hutang.index';
    $supplierRoute = $tagihan->supplier_id
        ? route('speedshop.keuangan.hutang.supplier', $tagihan->supplier_id)
        : route($indexRoute);
    $isBelumLunas = $tagihan->isBelumLunas();
    $isLunas = $tagihan->isLunas();
    $sisa = $tagihan->sisaHutang();
    $statusLabel = WarehouseSupplierTagihan::statusLabel($tagihan->status);
    $statusBadgeClass = match ($tagihan->status) {
        WarehouseSupplierTagihan::STATUS_LUNAS => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        default => 'border-red-200 bg-red-50 text-red-700',
    };
    $itemCount = $tagihan->items->count();
    $woPanelClass = 'panel-card panel-card-flush overflow-visible ss-dash-enter-child';
    $rekenings = $rekenings ?? collect();
    $saldoRekeningMap = $saldoRekeningMap ?? [];
    $canPay = $canPay ?? false;
    $jatuhTempoTampil = $tagihan->tanggal_jatuh_tempo ?? $tagihan->warehouseTransaksi?->tanggal_jatuh_tempo;
    $buktiUrl = $tagihan->bukti_bayar_path
        ? \Illuminate\Support\Facades\Storage::disk($uploadDisk)->url($tagihan->bukti_bayar_path)
        : null;
@endphp

<div
    class="ss-dashboard ss-dash-page ss-wo-create"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
>
<div class="ss-dash-content space-y-5">
    <div class="flex flex-col gap-3 ss-dash-enter sm:flex-row sm:items-start sm:justify-between" style="--ss-delay: 0ms">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">{{ $tagihan->kode }}</h1>
                <span class="inline-flex items-center rounded-lg border px-2.5 py-1 text-xs font-semibold uppercase tracking-wide {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
            </div>
            <p class="mt-1 text-[13px] text-gray-500">
                Hutang ke supplier · {{ $tagihan->supplier?->nama ?? '—' }} · {{ $tagihan->tanggal_tagihan?->translatedFormat('d M Y') ?? '—' }}
            </p>
        </div>
        <a href="{{ $supplierRoute }}" class="btn-secondary shrink-0">← Kembali</a>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12 ss-dash-enter" style="--ss-delay: 80ms">
        <div class="space-y-5 lg:col-span-8">
            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 0">
                <div class="flex items-start justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-[20px] font-semibold text-gray-900">Hutang Supplier</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Dari Stok In Supplier yang sudah disetujui accounting</p>
                    </div>
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l6-6m-5 5.5a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0zm10 0a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg>
                    </div>
                </div>
                <div class="space-y-4 px-5 py-5">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">No. hutang</label>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-semibold text-gray-900">{{ $tagihan->kode }}</div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">No. stok in</label>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-semibold text-gray-900">{{ $tagihan->warehouseTransaksi?->kode ?? '—' }}</div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Supplier</label>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $tagihan->supplier?->nama ?? '—' }}</div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Tanggal tagihan</label>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700">{{ $tagihan->tanggal_tagihan?->translatedFormat('d M Y') ?? '—' }}</div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Jatuh tempo</label>
                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-semibold text-gray-900">{{ $jatuhTempoTampil?->translatedFormat('d M Y') ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 1">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 class="text-[20px] font-semibold text-gray-900">Rincian barang</h2>
                        <p class="mt-1 text-[13px] text-gray-500">Harga mengikuti harga invoice Stok In Supplier</p>
                    </div>
                    <span class="wo-badge">{{ $itemCount }} item</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-[#f7f3f4]">
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">No.</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Produk</th>
                                <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Qty</th>
                                <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Harga invoice</th>
                                <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($tagihan->items as $i => $item)
                            <tr class="transition hover:bg-[#fcfafb]">
                                <td class="px-4 py-3 text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gray-900">{{ $item->nama_item ?: ($item->product?->nama_produk ?? '—') }}</div>
                                    <div class="mt-0.5 font-mono text-xs text-gray-500">{{ $item->product?->kode_produk ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-800">{{ number_format($item->qty, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-gray-700">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-gray-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-200 bg-[#fbf8f9]">
                                <td colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Total hutang</td>
                                <td class="px-4 py-3 text-right text-base font-bold tabular-nums text-gray-900">Rp {{ number_format($tagihan->total_nominal, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if($tagihan->pembayarans->isNotEmpty())
            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 2">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="text-[20px] font-semibold text-gray-900">Riwayat pembayaran</h2>
                    <p class="mt-1 text-[13px] text-gray-500">Cicilan dan pelunasan yang sudah dicatat</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="bg-[#f7f3f4]">
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Jenis</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Rekening</th>
                                <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-500">Nominal</th>
                                <th class="px-4 py-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Bukti</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($tagihan->pembayarans as $bayar)
                            @php
                                $payBukti = $bayar->bukti_bayar_path
                                    ? \Illuminate\Support\Facades\Storage::disk($uploadDisk)->url($bayar->bukti_bayar_path)
                                    : null;
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $bayar->dibayar_at?->timezone($tz)->format('d M Y H:i') ?? '—' }}
                                    <p class="text-xs text-gray-500">{{ $bayar->dibayarByUser?->name ?? '—' }}</p>
                                </td>
                                <td class="px-4 py-3 text-gray-800">{{ WarehouseSupplierTagihanPembayaran::jenisPembayaranLabel($bayar->jenis_pembayaran ?? null) }}</td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $bayar->sumberRekening ? SpeedshopKeuanganRekening::jenisLabel($bayar->sumberRekening->jenis).' — '.$bayar->sumberRekening->nama : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums text-gray-900">Rp {{ number_format($bayar->nominal, 0, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    @if($payBukti)
                                    <a href="{{ $payBukti }}" target="_blank" rel="noopener" class="text-xs font-semibold text-red-600 hover:text-red-700">Lihat</a>
                                    @else
                                    <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>

        <div class="space-y-4 lg:col-span-4">
            <div class="panel-card panel-card-flush summary-card ss-dash-enter-child" style="--ss-delay: 120ms; --ss-stagger: 3">
                <div class="section-hd">
                    <h2>Ringkasan</h2>
                    <p>Status dan sisa hutang</p>
                </div>
                <div class="mt-2 mb-2">
                    <div class="summary-row">
                        <span>Status</span>
                        <span class="inline-flex items-center rounded-lg border px-2 py-0.5 text-xs font-semibold uppercase tracking-wide {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                    </div>
                    <div class="summary-row">
                        <span>Total hutang</span>
                        <span class="tabular-nums">Rp {{ number_format($tagihan->total_nominal, 0, ',', '.') }}</span>
                    </div>
                    <div class="summary-row">
                        <span>Sudah dibayar</span>
                        <span class="tabular-nums">Rp {{ number_format($tagihan->totalDibayar(), 0, ',', '.') }}</span>
                    </div>
                    <div class="summary-row">
                        <span>Jatuh tempo</span>
                        <span>{{ $jatuhTempoTampil?->translatedFormat('d M Y') ?? '—' }}</span>
                    </div>
                    <div class="summary-total">
                        <span>Sisa hutang</span>
                        <span>Rp {{ number_format($sisa, 0, ',', '.') }}</span>
                    </div>
                </div>

                @if($canPay)
                <div class="space-y-2.5 border-t border-gray-100 px-5 pb-5 pt-4" x-data="{
                    rekId: @js((string) old('keuangan_rekening_id', $rekenings->first()?->id ?? '')),
                    jenisPembayaran: @js((string) old('jenis_pembayaran', '')),
                    saldoMap: @js($saldoRekeningMap),
                    nominal: @js((int) old('nominal', $sisa)),
                    sisa: @js((int) $sisa),
                    fmtRp(n) {
                        const x = Number(n);
                        if (!Number.isFinite(x)) return '—';
                        return 'Rp ' + x.toLocaleString('id-ID');
                    },
                    parseRupiahDigits(str) {
                        const d = String(str ?? '').replace(/\D/g, '');
                        if (d === '') return 0;
                        const n = parseInt(d, 10);
                        return Number.isNaN(n) ? 0 : n;
                    },
                    formatRupiahDigits(n) {
                        const v = Math.max(0, Math.floor(Number(n) || 0));
                        return v > 0 ? v.toLocaleString('id-ID') : '';
                    },
                    onNominalInput(e) {
                        let val = this.parseRupiahDigits(e.target.value);
                        if (this.sisa > 0 && val > this.sisa) val = this.sisa;
                        this.nominal = val;
                    },
                    saldoRek() {
                        const id = String(this.rekId ?? '').trim();
                        if (!id) return null;
                        let v = this.saldoMap[id];
                        if (v === undefined || v === null) v = this.saldoMap[parseInt(id, 10)];
                        return v === undefined || v === null ? null : Number(v);
                    },
                    saldoCukup() {
                        const s = this.saldoRek();
                        return s === null || s >= Number(this.nominal || 0);
                    },
                    canSubmitPay() {
                        return !!this.rekId && !!this.jenisPembayaran && this.saldoCukup() && Number(this.nominal || 0) >= 1;
                    }
                }">
                    <h3 class="text-sm font-semibold text-gray-900">Catat pembayaran</h3>
                    <p class="text-xs text-gray-500">Jenis pembayaran wajib diisi. Nominal bisa cicilan.</p>
                    <form method="POST" action="{{ route('speedshop.keuangan.hutang.bayar', $tagihan) }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                        @csrf
                        <div>
                            <label class="wo-label">Jenis pembayaran <span class="text-red-500">*</span></label>
                            <select name="jenis_pembayaran" x-model="jenisPembayaran" required class="wo-input">
                                <option value="">Pilih jenis</option>
                                @foreach(WarehouseSupplierTagihanPembayaran::jenisPembayaranOptions() as $value => $label)
                                <option value="{{ $value }}" @selected((string) old('jenis_pembayaran') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('jenis_pembayaran')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="wo-label">Rekening sumber <span class="text-red-500">*</span></label>
                            <select name="keuangan_rekening_id" x-model="rekId" required class="wo-input" @if($rekenings->isEmpty()) disabled @endif>
                                <option value="">Pilih rekening</option>
                                @foreach($rekenings as $rek)
                                <option value="{{ $rek->id }}" @selected((string) old('keuangan_rekening_id') === (string) $rek->id)>{{ SpeedshopKeuanganRekening::jenisLabel($rek->jenis) }} — {{ $rek->nama }}@if($rek->nama_bank) ({{ $rek->nama_bank }})@endif</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">
                                Saldo tersedia: <span class="font-semibold text-gray-800" x-text="saldoRek() === null ? '—' : fmtRp(saldoRek())"></span>
                            </p>
                            <p x-show="rekId && !saldoCukup()" x-cloak class="mt-1 text-xs text-red-600">Saldo rekening tidak mencukupi.</p>
                            @error('keuangan_rekening_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            @if($rekenings->isEmpty())
                            <p class="mt-1 text-xs text-amber-700">Belum ada Rekening Utama atau Kas Besar. Tambah rekening di menu Keuangan → Rekening.</p>
                            @endif
                        </div>
                        <div>
                            <label class="wo-label">Nominal <span class="text-red-500">*</span></label>
                            <input type="hidden" name="nominal" :value="nominal">
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm font-semibold text-gray-500">Rp</span>
                                <input type="text" inputmode="numeric" autocomplete="off" required class="wo-input pl-10 text-right tabular-nums"
                                       :value="formatRupiahDigits(nominal)"
                                       @input="onNominalInput($event)">
                            </div>
                            <p class="mt-1 text-[11px] text-gray-400">Maksimal sisa hutang Rp {{ number_format($sisa, 0, ',', '.') }}</p>
                            @error('nominal')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="wo-label">Bukti pembayaran <span class="text-red-500">*</span></label>
                            <input type="file" name="bukti_bayar" accept=".jpg,.jpeg,.png,.pdf" required class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-red-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-red-700">
                            @error('bukti_bayar')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="wo-label">Catatan</label>
                            <textarea name="catatan_pembayaran" rows="2" maxlength="1000" class="wo-input wo-native min-h-[4.5rem] resize-y py-2.5" placeholder="Opsional…">{{ old('catatan_pembayaran') }}</textarea>
                        </div>
                        <button type="submit" class="btn-primary flex h-11 w-full items-center justify-center gap-2" @if($rekenings->isEmpty()) disabled @endif x-bind:disabled="!canSubmitPay()">
                            Simpan pembayaran
                        </button>
                    </form>
                </div>
                @elseif($isLunas)
                <div class="border-t border-gray-100 px-5 py-4">
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs text-emerald-900">
                        Invoice ini sudah lunas.
                        @if($tagihan->dibayar_at)
                        <span class="block mt-1">{{ $tagihan->dibayar_at->timezone($tz)->format('d M Y H:i') }} · {{ $tagihan->dibayarByUser?->name ?? '—' }}</span>
                        @endif
                        @if($buktiUrl)
                        <a href="{{ $buktiUrl }}" target="_blank" rel="noopener" class="mt-2 inline-flex font-semibold underline">Lihat bukti terakhir</a>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@extends('layouts.speedshop')

@section('title', 'Keuangan — Pengambilan Dividen')

@section('content')
@php
    use App\Models\SpeedshopKeuanganRekening;
    use App\Models\SpeedshopOwnerPengambilanDividen;
    $inputClass = 'h-10 w-full rounded-xl border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $filterClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $filterFormId = 'owner-dividen-filters';
    $rekeningsUrlTemplate = route('speedshop.keuangan.pengambilan-dividen.rekenings', ['speedshop' => '__ID__']);
    $saldosUrl = route('speedshop.keuangan.pengambilan-dividen.saldos');
@endphp
<div
    class="space-y-5"
    x-data="ownerPengambilanDividen({
        rekeningsUrlTemplate: @js($rekeningsUrlTemplate),
        saldosUrl: @js($saldosUrl),
        openModal: @js((bool) ($openModal ?? false)),
        hasOwnerRekenings: @js($ownerRekenings->isNotEmpty()),
        oldSpeedshopId: @js(old('speedshop_id', '')),
        oldSpeedshopRekId: @js(old('speedshop_rekening_id', '')),
        oldOwnerRekId: @js(old('owner_rekening_id', '')),
        defaultTanggal: @js(old('tanggal', $asOfYmd)),
    })"
    x-init="init()"
>
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('speedshop.dashboard') }}" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">Keuangan / Pengambilan Dividen</span>
    </nav>


    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Pengambilan dividen dari speedshop</h2>
                <p class="mt-1 text-sm text-gray-500">Ajukan pengambilan dividen ke cabang speedshop.</p>
            </div>
            <button type="button" @click="openDividenModal()" class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand px-5 text-sm font-medium text-brand-ink shadow-lg shadow-brand/35 transition hover:bg-brand-hover">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Ajukan dividen
            </button>
        </div>

        @if($ownerRekenings->isEmpty())
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            Belum ada rekening owner. <a href="{{ route('speedshop.keuangan.rekening', ['buka_modal' => 'tambah']) }}" class="font-semibold text-red-600 underline">Tambah rekening</a> terlebih dahulu.
        </div>
        @endif
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <h3 class="text-lg font-semibold text-gray-900">Riwayat pengajuan</h3>
        <form id="{{ $filterFormId }}" method="GET" action="{{ route('speedshop.keuangan.pengambilan-dividen.index') }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Speedshop</label>
                <select name="speedshop_id" class="{{ $filterClass }}" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    @foreach($speedshops as $s)
                    <option value="{{ $s->id }}" @selected((string) request('speedshop_id') === (string) $s->id)>{{ $s->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Status</label>
                <select name="status" class="{{ $filterClass }}" onchange="this.form.submit()">
                    <option value="">Semua</option>
                    <option value="pending" @selected(request('status') === 'pending')>Menunggu</option>
                    <option value="completed" @selected(request('status') === 'completed')>Disetujui</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Dari tanggal</label>
                <input type="date" name="dari" value="{{ request('dari') }}" class="{{ $filterClass }}" onchange="this.form.submit()">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Sampai tanggal</label>
                <input type="date" name="sampai" value="{{ request('sampai') }}" class="{{ $filterClass }}" onchange="this.form.submit()">
            </div>
        </form>

        <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200">
            <table class="w-full min-w-[1000px] text-left text-sm">
                <thead>
                    <tr class="bg-[#f7f3f4]">
                        <th class="px-5 py-3 text-xs font-semibold uppercase text-gray-500">No.</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase text-gray-500">Tanggal</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase text-gray-500">Status</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase text-gray-500">Speedshop</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase text-gray-500">Rek. speedshop</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase text-gray-500">Rek. owner</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-gray-500">Nominal</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase text-gray-500">Bukti</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pengajuans as $i => $row)
                    @php
                        $statusClass = match ($row->status) {
                            SpeedshopOwnerPengambilanDividen::STATUS_COMPLETED => 'bg-emerald-100 text-emerald-900',
                            SpeedshopOwnerPengambilanDividen::STATUS_REJECTED => 'bg-red-100 text-red-800',
                            default => 'bg-amber-100 text-amber-900',
                        };
                        $statusLabel = match ($row->status) {
                            SpeedshopOwnerPengambilanDividen::STATUS_COMPLETED => 'Disetujui',
                            SpeedshopOwnerPengambilanDividen::STATUS_REJECTED => 'Ditolak',
                            default => 'Menunggu',
                        };
                    @endphp
                    <tr class="hover:bg-rose-50/40">
                        <td class="px-5 py-3 text-gray-500">{{ $pengajuans->firstItem() + $i }}</td>
                        <td class="px-5 py-3 text-gray-800">{{ $row->tanggal->format('d/m/Y') }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                            @if($row->status === SpeedshopOwnerPengambilanDividen::STATUS_REJECTED && $row->rejected_reason)
                            <p class="mt-1 max-w-xs text-xs text-red-700">{{ $row->rejected_reason }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $row->speedshop?->nama ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $row->speedshopRekening?->nama ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $row->ownerRekening?->nama ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-semibold tabular-nums text-gray-900">Rp {{ number_format($row->nominal, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-xs">
                            @if($row->buktiPengajuanUrl())
                            <a href="{{ $row->buktiPengajuanUrl() }}" target="_blank" rel="noopener" class="font-medium text-red-600 hover:underline">Pengajuan</a>
                            @endif
                            @if($row->buktiTransferUrl())
                            <span class="text-gray-400"> · </span>
                            <a href="{{ $row->buktiTransferUrl() }}" target="_blank" rel="noopener" class="font-medium text-emerald-700 hover:underline">Transfer</a>
                            @endif
                            @if(! $row->buktiPengajuanUrl() && ! $row->buktiTransferUrl())
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-10 text-center text-sm text-gray-500">Belum ada pengajuan dividen.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pengajuans->hasPages())
        <div class="mt-4">{{ $pengajuans->links() }}</div>
        @endif
    </div>

    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center" @keydown.escape.window="showModal = false">
        <div class="w-full max-w-4xl overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-2xl" style="max-height:min(94vh,860px)" @click.stop>
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-100 bg-white px-6 py-4">
                <h3 class="text-xl font-semibold text-gray-900">Pengajuan dividen baru</h3>
                <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" @click="showModal = false" aria-label="Tutup">&times;</button>
            </div>
            <form method="POST" action="{{ route('speedshop.keuangan.pengambilan-dividen.store') }}" enctype="multipart/form-data" class="space-y-5 p-6">
                @csrf
                <input type="hidden" name="open_modal" value="1">

                <div x-show="!hasOwnerRekenings" x-cloak class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                    Belum ada rekening owner. <a href="{{ route('speedshop.keuangan.rekening', ['buka_modal' => 'tambah']) }}" class="font-semibold text-red-600 underline">Tambah rekening</a> terlebih dahulu sebelum mengajukan dividen.
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2" :class="{ 'pointer-events-none opacity-50': !hasOwnerRekenings }">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Speedshop <span class="text-red-500">*</span></label>
                        <select name="speedshop_id" x-model="speedshopId" @change="onSpeedshopChange()" required class="{{ $inputClass }} @error('speedshop_id') border-red-400 @enderror">
                            <option value="">— Pilih speedshop —</option>
                            @foreach($speedshops as $s)
                            <option value="{{ $s->id }}">{{ $s->nama }}</option>
                            @endforeach
                        </select>
                        @error('speedshop_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Rekening speedshop (sumber) <span class="text-red-500">*</span></label>
                        <select name="speedshop_rekening_id" x-ref="speedshopRekSelect" x-model="speedshopRekeningId" @change="onSpeedshopRekChange()" required class="{{ $inputClass }} @error('speedshop_rekening_id') border-red-400 @enderror" :disabled="!speedshopId || loadingRekenings">
                            <option value="">— Pilih speedshop dulu —</option>
                        </select>
                        @error('speedshop_rekening_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        <div x-show="speedshopRekeningId" x-cloak class="mt-2 rounded-lg border border-violet-200 bg-violet-50/90 px-3 py-2">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-violet-800/80">Saldo rekening speedshop</p>
                            <p class="text-base font-semibold tabular-nums text-violet-950" x-show="!loadingSaldos" x-text="formatRp(previewSaldoSpeedshop)"></p>
                            <p class="text-sm text-violet-700" x-show="loadingSaldos">Memuat saldo…</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Rekening owner (tujuan) <span class="text-red-500">*</span></label>
                    <select name="owner_rekening_id" x-model="ownerRekeningId" @change="refreshSaldos()" required class="{{ $inputClass }} @error('owner_rekening_id') border-red-400 @enderror">
                        <option value="">— Pilih rekening owner —</option>
                        @foreach($ownerRekenings as $rek)
                        <option value="{{ $rek->id }}">{{ $rek->nama }} — {{ SpeedshopKeuanganRekening::jenisLabel($rek->jenis) }}</option>
                        @endforeach
                    </select>
                    @error('owner_rekening_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    <div x-show="ownerRekeningId" x-cloak class="mt-2 rounded-lg border border-sky-200 bg-sky-50/90 px-3 py-2">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-sky-800/80">Saldo rekening owner (setelah disetujui)</p>
                        <p class="text-base font-semibold tabular-nums text-sky-950" x-show="!loadingSaldos" x-text="formatRp(previewSaldoOwner)"></p>
                    </div>
                </div>

                <p x-show="speedshopRekeningId && saldoSpeedshop !== null && nominalAngka > saldoSpeedshop" x-cloak class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
                    Nominal melebihi saldo rekening speedshop saat ini (validasi akhir saat approval Accounting).
                </p>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Tanggal <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal" x-model="tanggal" @change="refreshSaldos()" max="{{ $asOfYmd }}" required class="{{ $inputClass }} @error('tanggal') border-red-400 @enderror">
                        @error('tanggal')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Nominal (Rp) <span class="text-red-500">*</span></label>
                        <input type="number" name="nominal" x-model="nominal" min="1" step="1" required class="{{ $inputClass }} @error('nominal') border-red-400 @enderror" placeholder="0">
                        @error('nominal')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Bukti pengajuan <span class="text-red-500">*</span></label>
                    <input type="file" name="bukti_pengajuan" accept=".jpg,.jpeg,.png,.pdf" required class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-red-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-red-700">
                    <p class="mt-1 text-xs text-gray-500">JPG, PNG, atau PDF. Maks. 5 MB.</p>
                    @error('bukti_pengajuan')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Catatan</label>
                    <textarea name="catatan" rows="3" class="{{ $inputClass }} min-h-[5rem]" placeholder="Opsional">{{ old('catatan') }}</textarea>
                </div>

                <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-5">
                    <button type="submit" class="inline-flex h-11 items-center rounded-xl bg-brand px-6 text-sm font-semibold text-brand-ink hover:bg-brand-hover disabled:opacity-50" :disabled="!hasOwnerRekenings || loadingRekenings || loadingSaldos">Kirim pengajuan</button>
                    <button type="button" class="inline-flex h-11 items-center rounded-xl border border-gray-200 px-6 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showModal = false">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('ownerPengambilanDividen', (cfg) => ({
        showModal: cfg.openModal,
        hasOwnerRekenings: cfg.hasOwnerRekenings !== false,
        speedshopId: cfg.oldSpeedshopId ? String(cfg.oldSpeedshopId) : '',
        speedshopRekeningId: cfg.oldSpeedshopRekId ? String(cfg.oldSpeedshopRekId) : '',
        ownerRekeningId: cfg.oldOwnerRekId ? String(cfg.oldOwnerRekId) : '',
        tanggal: cfg.defaultTanggal || '',
        nominal: @js(old('nominal', '')),
        speedshopRekenings: [],
        loadingRekenings: false,
        loadingSaldos: false,
        saldoSpeedshop: null,
        saldoOwner: null,
        rekeningsUrlTemplate: cfg.rekeningsUrlTemplate,
        saldosUrl: cfg.saldosUrl,
        defaultTanggal: cfg.defaultTanggal || '',
        get nominalAngka() {
            const n = parseInt(String(this.nominal || '').replace(/\D/g, ''), 10);
            return Number.isFinite(n) && n > 0 ? n : 0;
        },
        get previewSaldoSpeedshop() {
            if (this.saldoSpeedshop === null) return null;
            return Math.max(0, this.saldoSpeedshop - this.nominalAngka);
        },
        get previewSaldoOwner() {
            if (this.saldoOwner === null) return null;
            return this.saldoOwner + this.nominalAngka;
        },
        formatRp(v) {
            if (v === null || v === undefined) return '—';
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(v);
        },
        effectiveTanggal() {
            return (this.tanggal && String(this.tanggal).trim() !== '') ? this.tanggal : this.defaultTanggal;
        },
        init() {
            if (this.speedshopId) {
                this.loadSpeedshopRekenings().then(() => this.refreshSaldos());
            }
        },
        openDividenModal() {
            this.showModal = true;
            if (!this.tanggal) this.tanggal = this.defaultTanggal;
            if (!this.hasOwnerRekenings) {
                return;
            }
            this.$nextTick(() => {
                if (this.speedshopId) {
                    this.loadSpeedshopRekenings().then(() => this.refreshSaldos());
                }
            });
        },
        onSpeedshopChange() {
            this.speedshopRekeningId = '';
            this.saldoSpeedshop = null;
            this.loadSpeedshopRekenings().then(() => this.refreshSaldos());
        },
        onSpeedshopRekChange() {
            this.refreshSaldos();
        },
        renderSpeedshopRekOptions() {
            const sel = this.$refs.speedshopRekSelect;
            if (!sel) return;
            const prev = String(this.speedshopRekeningId || '');
            const placeholder = this.loadingRekenings ? 'Memuat…' : (!this.speedshopId ? '— Pilih speedshop dulu —' : '— Pilih rekening —');
            sel.innerHTML = '';
            const empty = document.createElement('option');
            empty.value = '';
            empty.textContent = placeholder;
            sel.appendChild(empty);
            this.speedshopRekenings.forEach(r => {
                const opt = document.createElement('option');
                opt.value = String(r.id);
                opt.textContent = r.label;
                sel.appendChild(opt);
            });
            if (prev && this.speedshopRekenings.some(r => String(r.id) === prev)) {
                sel.value = prev;
                this.speedshopRekeningId = prev;
            } else {
                sel.value = '';
                this.speedshopRekeningId = '';
            }
        },
        async loadSpeedshopRekenings() {
            this.speedshopRekenings = [];
            if (!this.speedshopId) {
                this.renderSpeedshopRekOptions();
                return;
            }
            this.loadingRekenings = true;
            this.renderSpeedshopRekOptions();
            const url = this.rekeningsUrlTemplate.replace('__ID__', this.speedshopId);
            try {
                const res = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (res.ok) {
                    const data = await res.json();
                    this.speedshopRekenings = data.rekenings || [];
                }
            } catch (e) {
                this.speedshopRekenings = [];
            }
            this.loadingRekenings = false;
            this.renderSpeedshopRekOptions();
        },
        async refreshSaldos() {
            if (!this.speedshopRekeningId && !this.ownerRekeningId) {
                this.saldoSpeedshop = null;
                this.saldoOwner = null;
                return;
            }
            this.loadingSaldos = true;
            const params = new URLSearchParams({ tanggal: this.effectiveTanggal() });
            if (this.speedshopId) params.set('speedshop_id', this.speedshopId);
            if (this.speedshopRekeningId) params.set('speedshop_rekening_id', this.speedshopRekeningId);
            if (this.ownerRekeningId) params.set('owner_rekening_id', this.ownerRekeningId);
            try {
                const res = await fetch(this.saldosUrl + '?' + params.toString(), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (res.ok) {
                    const data = await res.json();
                    if (this.speedshopRekeningId && data.speedshop !== undefined) {
                        this.saldoSpeedshop = data.speedshop !== null ? Number(data.speedshop) : null;
                    }
                    if (this.ownerRekeningId && data.owner !== undefined) {
                        this.saldoOwner = data.owner !== null ? Number(data.owner) : null;
                    }
                }
            } catch (e) { /* */ }
            this.loadingSaldos = false;
        },
    }));
});
</script>
@endpush

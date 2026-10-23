@extends('layouts.speedshop')

@section('title', 'Keuangan — Setoran Modal')

@section('content')
@php
    use App\Models\SpeedshopKeuanganRekening;
    $inputClass = 'h-10 w-full rounded-xl border border-gray-200 bg-white px-3 text-sm text-gray-700 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $filterClass = 'h-8 w-full rounded-lg border border-gray-200 bg-white px-2.5 text-[13px] font-normal text-gray-700 outline-none transition focus:border-brand focus:ring-2 focus:ring-brand/40';
    $filterFormId = 'owner-setoran-modal-filters';
    $rekeningsUrlTemplate = route('speedshop.keuangan.setoran-modal.rekenings', ['speedshop' => '__ID__']);
    $saldosUrl = route('speedshop.keuangan.setoran-modal.saldos');
@endphp
<div
    class="space-y-5"
    x-data="ownerSetoranModal({
        rekeningsUrlTemplate: @js($rekeningsUrlTemplate),
        saldosUrl: @js($saldosUrl),
        ownerSaldoMap: @json($saldoPerOwnerRekening),
        openModal: @js((bool) ($openModal ?? false)),
        oldSpeedshopId: @js(old('speedshop_id', '')),
        oldSumberId: @js(old('sumber_owner_rekening_id', '')),
        oldTujuanId: @js(old('tujuan_speedshop_rekening_id', '')),
        defaultTanggal: @js(old('tanggal', $asOfYmd)),
    })"
    x-init="init()"
>
    <nav class="flex items-center gap-2 text-sm text-gray-500">
        <a href="{{ route('speedshop.dashboard') }}" class="transition hover:text-gray-700">Home</a>
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="font-medium text-gray-900">Keuangan / Setoran Modal</span>
    </nav>


    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900">Setoran modal ke speedshop</h2>
                <p class="mt-1 text-sm text-gray-500">Transfer dana dari rekening owner ke rekening cabang speedshop. Saldo rekening owner berkurang dan rekening tujuan bertambah otomatis.</p>
            </div>
            <button type="button" @click="openSetoranModal()" class="inline-flex h-10 items-center gap-2 rounded-xl bg-brand px-5 text-sm font-medium text-brand-ink shadow-lg shadow-brand/35 transition hover:bg-brand-hover">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Setoran modal
            </button>
        </div>

        @if($ownerRekenings->isEmpty())
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            Belum ada rekening owner. <a href="{{ route('speedshop.keuangan.rekening', ['buka_modal' => 'tambah']) }}" class="font-semibold text-red-600 underline">Tambah rekening</a> terlebih dahulu.
        </div>
        @else
        <div class="mt-4 flex flex-wrap gap-3">
            @foreach($ownerRekenings as $rek)
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-2 text-sm">
                <span class="font-medium text-gray-800">{{ $rek->nama }}</span>
                <span class="ml-2 tabular-nums text-gray-600">Rp {{ number_format($saldoPerOwnerRekening[$rek->id] ?? 0, 0, ',', '.') }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5">
        <h3 class="text-lg font-semibold text-gray-900">Riwayat setoran modal</h3>
        <form id="{{ $filterFormId }}" method="GET" action="{{ route('speedshop.keuangan.setoran-modal.index') }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-4">
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
                <label class="mb-1 block text-xs font-medium text-gray-500">Dari tanggal</label>
                <input type="date" name="dari" value="{{ request('dari') }}" class="{{ $filterClass }}" onchange="this.form.submit()">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Sampai tanggal</label>
                <input type="date" name="sampai" value="{{ request('sampai') }}" class="{{ $filterClass }}" onchange="this.form.submit()">
            </div>
        </form>

        <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200">
            <table class="w-full min-w-[960px] text-left text-sm">
                <thead>
                    <tr class="bg-[#f7f3f4]">
                        <th class="px-5 py-3 text-xs font-semibold uppercase text-gray-500">No.</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase text-gray-500">Tanggal</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase text-gray-500">Speedshop</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase text-gray-500">Rek. sumber (owner)</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase text-gray-500">Rek. tujuan</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-gray-500">Nominal</th>
                        <th class="px-5 py-3 text-xs font-semibold uppercase text-gray-500">Bukti</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($setorans as $i => $row)
                    <tr class="hover:bg-rose-50/40">
                        <td class="px-5 py-3 text-gray-500">{{ $setorans->firstItem() + $i }}</td>
                        <td class="px-5 py-3 text-gray-800">{{ $row->tanggal->format('d/m/Y') }}</td>
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $row->speedshop?->nama ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $row->sumberOwnerRekening?->nama ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ $row->tujuanSpeedshopRekening?->nama ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-semibold tabular-nums text-gray-900">Rp {{ number_format($row->nominal, 0, ',', '.') }}</td>
                        <td class="px-5 py-3">
                            @if($row->buktiUrl())
                            <a href="{{ $row->buktiUrl() }}" target="_blank" rel="noopener" class="text-sm font-medium text-red-600 hover:underline">Lihat bukti</a>
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-sm text-gray-500">Belum ada setoran modal.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($setorans->hasPages())
        <div class="mt-4">{{ $setorans->links() }}</div>
        @endif
    </div>

    {{-- Modal setoran --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center" @keydown.escape.window="showModal = false">
        <div class="w-full max-w-4xl overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-2xl" style="max-height:min(94vh,860px)" @click.stop>
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-gray-100 bg-white px-6 py-4">
                <h3 class="text-xl font-semibold text-gray-900">Setoran modal baru</h3>
                <button type="button" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" @click="showModal = false" aria-label="Tutup">&times;</button>
            </div>
            <form method="POST" action="{{ route('speedshop.keuangan.setoran-modal.store') }}" enctype="multipart/form-data" class="space-y-5 p-6">
                @csrf
                <input type="hidden" name="open_modal" value="1">

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Speedshop tujuan <span class="text-red-500">*</span></label>
                        <select name="speedshop_id" x-model="speedshopId" @change="onSpeedshopChange()" required class="{{ $inputClass }} @error('speedshop_id') border-red-400 @enderror">
                            <option value="">— Pilih speedshop —</option>
                            @foreach($speedshops as $s)
                            <option value="{{ $s->id }}">{{ $s->nama }}</option>
                            @endforeach
                        </select>
                        @error('speedshop_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Rekening tujuan (speedshop) <span class="text-red-500">*</span></label>
                        <select name="tujuan_speedshop_rekening_id" x-ref="tujuanSelect" x-model="tujuanRekeningId" @change="onTujuanChange()" required class="{{ $inputClass }} @error('tujuan_speedshop_rekening_id') border-red-400 @enderror" :disabled="!speedshopId || loadingRekenings">
                            <option value="">{{ $speedshops->isNotEmpty() ? '— Pilih speedshop dulu —' : '—' }}</option>
                        </select>
                        @error('tujuan_speedshop_rekening_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        <div x-show="tujuanRekeningId" x-cloak class="mt-2 rounded-lg border border-violet-200 bg-violet-50/90 px-3 py-2">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-violet-800/80">Saldo rekening tujuan</p>
                            <p class="text-[11px] text-violet-900/70" x-text="nominalAngka > 0 ? 'Setelah setoran (simulasi)' : 'Per tanggal transfer'"></p>
                            <p class="text-base font-semibold tabular-nums text-violet-950" x-show="!loadingSaldos" x-text="formatRp(previewSaldoTujuan)"></p>
                            <p class="text-sm text-violet-700" x-show="loadingSaldos">Memuat saldo…</p>
                            <p class="mt-0.5 text-xs text-violet-800/70" x-show="nominalAngka > 0 && saldoTujuan !== null">
                                Semula <span class="font-medium tabular-nums" x-text="formatRp(saldoTujuan)"></span>
                                <span class="text-emerald-700">+ <span x-text="formatRp(nominalAngka)"></span></span>
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Rekening sumber (owner) <span class="text-red-500">*</span></label>
                    <select name="sumber_owner_rekening_id" x-model="sumberRekeningId" @change="onSumberChange()" required class="{{ $inputClass }} @error('sumber_owner_rekening_id') border-red-400 @enderror">
                        <option value="">— Pilih rekening owner —</option>
                        @foreach($ownerRekenings as $rek)
                        <option value="{{ $rek->id }}" @selected((string) old('sumber_owner_rekening_id') === (string) $rek->id)>
                            {{ $rek->nama }} — {{ SpeedshopKeuanganRekening::jenisLabel($rek->jenis) }}
                        </option>
                        @endforeach
                    </select>
                    @error('sumber_owner_rekening_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    <div x-show="sumberRekeningId" x-cloak class="mt-2 rounded-lg border border-sky-200 bg-sky-50/90 px-3 py-2">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-sky-800/80">Saldo rekening sumber</p>
                        <p class="text-[11px] text-sky-900/70" x-text="nominalAngka > 0 ? 'Setelah setoran (simulasi)' : 'Per tanggal transfer'"></p>
                        <p class="text-base font-semibold tabular-nums text-sky-950" x-show="!loadingSaldos" x-text="formatRp(previewSaldoSumber)"></p>
                        <p class="text-sm text-sky-700" x-show="loadingSaldos">Memuat saldo…</p>
                        <p class="mt-0.5 text-xs text-sky-800/70" x-show="nominalAngka > 0 && saldoSumber !== null">
                            Semula <span class="font-medium tabular-nums" x-text="formatRp(saldoSumber)"></span>
                            <span class="text-red-600">− <span x-text="formatRp(nominalAngka)"></span></span>
                        </p>
                    </div>
                </div>

                <p x-show="sumberRekeningId && saldoSumber !== null && nominalAngka > saldoSumber" x-cloak class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
                    Nominal melebihi saldo rekening sumber.
                </p>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Tanggal transfer <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal" x-model="tanggal" @change="onTanggalChange()" max="{{ $asOfYmd }}" required class="{{ $inputClass }} @error('tanggal') border-red-400 @enderror">
                        @error('tanggal')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Nominal (Rp) <span class="text-red-500">*</span></label>
                        <input type="number" name="nominal" x-model="nominal" min="1" step="1" required class="{{ $inputClass }} @error('nominal') border-red-400 @enderror" placeholder="0">
                        @error('nominal')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Bukti transfer <span class="text-red-500">*</span></label>
                    <input type="file" name="bukti" accept=".jpg,.jpeg,.png,.pdf" required class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-red-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-red-700">
                    <p class="mt-1 text-xs text-gray-500">JPG, PNG, atau PDF. Maks. 5 MB.</p>
                    @error('bukti')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Catatan</label>
                    <textarea name="catatan" rows="3" class="{{ $inputClass }} min-h-[5rem]" placeholder="Opsional">{{ old('catatan') }}</textarea>
                </div>

                <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-5">
                    <button type="submit" class="inline-flex h-11 items-center rounded-xl bg-brand px-6 text-sm font-semibold text-brand-ink hover:bg-brand-hover disabled:opacity-50" :disabled="loadingRekenings || loadingSaldos || (saldoSumber !== null && nominalAngka > saldoSumber)">Simpan setoran</button>
                    <button type="button" class="inline-flex h-11 items-center rounded-xl border border-gray-200 px-6 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="showModal = false">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function ownerSetoranModal(cfg) {
    return {
        showModal: cfg.openModal,
        speedshopId: cfg.oldSpeedshopId ? String(cfg.oldSpeedshopId) : '',
        sumberRekeningId: cfg.oldSumberId ? String(cfg.oldSumberId) : '',
        tujuanRekeningId: cfg.oldTujuanId ? String(cfg.oldTujuanId) : '',
        tanggal: cfg.defaultTanggal || '',
        nominal: @js(old('nominal', '')),
        tujuanRekenings: [],
        loadingRekenings: false,
        loadingSaldos: false,
        saldoSumber: null,
        saldoTujuan: null,
        rekeningsUrlTemplate: cfg.rekeningsUrlTemplate,
        saldosUrl: cfg.saldosUrl,
        ownerSaldoMap: cfg.ownerSaldoMap || {},
        defaultTanggal: cfg.defaultTanggal || '',
        get nominalAngka() {
            const n = parseInt(String(this.nominal || '').replace(/\D/g, ''), 10);
            return Number.isFinite(n) && n > 0 ? n : 0;
        },
        get previewSaldoSumber() {
            if (this.saldoSumber === null) return null;
            return Math.max(0, this.saldoSumber - this.nominalAngka);
        },
        get previewSaldoTujuan() {
            if (this.saldoTujuan === null) return null;
            return this.saldoTujuan + this.nominalAngka;
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
                this.loadTujuanRekenings().then(() => this.refreshSaldos());
            } else if (this.sumberRekeningId || this.tujuanRekeningId) {
                this.applyOwnerSaldoFromMap();
                this.refreshSaldos();
            }
        },
        openSetoranModal() {
            this.showModal = true;
            if (!this.tanggal) {
                this.tanggal = this.defaultTanggal;
            }
            this.$nextTick(() => {
                if (this.speedshopId) {
                    this.loadTujuanRekenings().then(() => this.refreshSaldos());
                } else {
                    this.refreshSaldos();
                }
            });
        },
        onSpeedshopChange() {
            this.tujuanRekeningId = '';
            this.saldoTujuan = null;
            this.loadTujuanRekenings().then(() => this.refreshSaldos());
        },
        onSumberChange() {
            this.applyOwnerSaldoFromMap();
            this.refreshSaldos();
        },
        onTujuanChange() {
            this.syncTujuanSaldoFromList();
            this.refreshSaldos();
        },
        onTanggalChange() {
            if (this.speedshopId) {
                this.loadTujuanRekenings().then(() => this.refreshSaldos());
            } else {
                this.applyOwnerSaldoFromMap();
                this.refreshSaldos();
            }
        },
        applyOwnerSaldoFromMap() {
            if (!this.sumberRekeningId) {
                this.saldoSumber = null;
                return;
            }
            const key = String(this.sumberRekeningId);
            if (Object.prototype.hasOwnProperty.call(this.ownerSaldoMap, key)) {
                this.saldoSumber = Number(this.ownerSaldoMap[key]);
            } else if (Object.prototype.hasOwnProperty.call(this.ownerSaldoMap, Number(key))) {
                this.saldoSumber = Number(this.ownerSaldoMap[Number(key)]);
            }
        },
        syncTujuanSaldoFromList() {
            const row = this.tujuanRekenings.find(r => String(r.id) === String(this.tujuanRekeningId));
            if (row && typeof row.saldo === 'number') {
                this.saldoTujuan = row.saldo;
            } else if (!this.tujuanRekeningId) {
                this.saldoTujuan = null;
            }
        },
        renderTujuanOptions() {
            const sel = this.$refs.tujuanSelect;
            if (!sel) return;
            const prev = String(this.tujuanRekeningId || '');
            const placeholder = this.loadingRekenings
                ? 'Memuat rekening…'
                : (!this.speedshopId ? '— Pilih speedshop dulu —' : '— Pilih rekening tujuan —');
            sel.innerHTML = '';
            const empty = document.createElement('option');
            empty.value = '';
            empty.textContent = placeholder;
            sel.appendChild(empty);
            this.tujuanRekenings.forEach(r => {
                const opt = document.createElement('option');
                opt.value = String(r.id);
                opt.textContent = r.label;
                sel.appendChild(opt);
            });
            if (prev && this.tujuanRekenings.some(r => String(r.id) === prev)) {
                sel.value = prev;
                this.tujuanRekeningId = prev;
            } else {
                sel.value = '';
                this.tujuanRekeningId = '';
            }
        },
        async loadTujuanRekenings() {
            this.tujuanRekenings = [];
            if (!this.speedshopId) {
                this.tujuanRekeningId = '';
                this.saldoTujuan = null;
                this.renderTujuanOptions();
                return;
            }
            this.loadingRekenings = true;
            this.renderTujuanOptions();
            const base = this.rekeningsUrlTemplate.replace('__ID__', this.speedshopId);
            const url = base + (base.includes('?') ? '&' : '?') + 'tanggal=' + encodeURIComponent(this.effectiveTanggal());
            try {
                const res = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                if (res.ok) {
                    const data = await res.json();
                    this.tujuanRekenings = data.rekenings || [];
                }
            } catch (e) {
                this.tujuanRekenings = [];
            }
            this.loadingRekenings = false;
            this.renderTujuanOptions();
            this.syncTujuanSaldoFromList();
        },
        async refreshSaldos() {
            if (!this.sumberRekeningId && !this.tujuanRekeningId) {
                this.saldoSumber = null;
                this.saldoTujuan = null;
                return;
            }
            this.loadingSaldos = true;
            const params = new URLSearchParams({ tanggal: this.effectiveTanggal() });
            if (this.sumberRekeningId) params.set('sumber_owner_rekening_id', this.sumberRekeningId);
            if (this.tujuanRekeningId) params.set('tujuan_speedshop_rekening_id', this.tujuanRekeningId);
            if (this.speedshopId) params.set('speedshop_id', this.speedshopId);
            try {
                const res = await fetch(this.saldosUrl + '?' + params.toString(), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (res.ok) {
                    const data = await res.json();
                    if (this.sumberRekeningId) {
                        this.saldoSumber = data.sumber !== undefined && data.sumber !== null ? Number(data.sumber) : this.saldoSumber;
                    }
                    if (this.tujuanRekeningId) {
                        this.saldoTujuan = data.tujuan !== undefined && data.tujuan !== null ? Number(data.tujuan) : this.saldoTujuan;
                    }
                }
            } catch (e) {
                /* keep previous */
            }
            this.loadingSaldos = false;
        },
    };
}
</script>
@endpush

@extends('layouts.speedshop')

@section('title', 'Tambah Setoran')

@section('content')
@push('styles')
@include('partials.speedshop.dashboard-load-animations')
@include('partials.speedshop.dashboard-manager-template-styles')
@include('partials.speedshop.work-order-create-styles')
<style>
.ss-wo-create .setoran-tx-card {
    border: 1px solid var(--ss-border, #e5e7eb);
    border-radius: 12px;
    background: #fcfafb;
    padding: 16px;
}
.ss-wo-create .wo-file-input {
    width: 100%;
    border: 1px solid var(--ss-border, #e5e7eb);
    border-radius: 9px;
    background: #fff;
    padding: 8px 12px;
    font-size: 12px;
}
.ss-wo-create .wo-file-input::file-selector-button {
    margin-right: 12px;
    border: 0;
    border-radius: 8px;
    background: #f3f4f6;
    padding: 6px 12px;
    font-size: 11px;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
}
.ss-wo-create .saldo-readonly {
    display: flex;
    min-height: 42px;
    align-items: center;
    border-radius: 9px;
    border: 1px solid rgba(245,158,11,0.35);
    background: rgba(255,251,235,0.8);
    padding: 0 12px;
    font-size: 14px;
    font-weight: 600;
    color: #78350f;
}
</style>
@endpush

@php
    $woPanelClass = 'panel-card panel-card-flush overflow-visible ss-dash-enter-child';
    $cfg = [
        'snapshotUrl' => $snapshotUrl,
        'defaultTanggal' => $defaultTanggal,
        'banksRequired' => (bool) $banksRequired,
        'hasKasBesarSetoranCol' => (bool) ($hasKasBesarSetoranCol ?? false),
        'hasKasBesarBanks' => (bool) $hasKasBesarBanks,
        'buktiSplitEnabled' => (bool) ($buktiSplitEnabled ?? false),
        'isAccounting' => (bool) $isAccounting,
        'oldRekeningUtamaId' => old('speedshop_keuangan_rekening_id'),
        'oldKasBesarId' => old('speedshop_keuangan_kas_besar_id'),
        'oldSetorTunai' => old('setor_tunai_actual'),
        'oldSetorDigital' => old('setor_digital_actual'),
        'oldSetorKkKeKb' => old('setor_kk_ke_kb_actual'),
        'hasSetorKkKeKbCol' => (bool) ($hasSetorKkKeKbCol ?? false),
        'oldTransactions' => old('transactions', []),
        'rekeningsUtama' => $rekeningsUtamaJson ?? [],
        'rekeningsKasBesar' => $rekeningsKasBesarJson ?? [],
    ];
@endphp
<script type="application/json" id="setoran-create-cfg">@json($cfg)</script>

<div
    x-data="setoranCreateForm(JSON.parse(document.getElementById('setoran-create-cfg').textContent))"
    x-init="$nextTick(() => { requestAnimationFrame(() => $el.classList.add('is-ready')); })"
    class="ss-dashboard ss-dash-page ss-wo-create"
>
<div class="ss-dash-content space-y-5">
    <div class="flex flex-col gap-3 ss-dash-enter sm:flex-row sm:items-start sm:justify-between" style="--ss-delay: 0ms">
        <div>
            <h1 class="text-[20px] font-semibold leading-tight tracking-tight text-gray-900">Tambah Setoran</h1>
            <p class="mt-1 text-[13px] text-gray-500">Catat perpindahan kas ke rekening tujuan — menunggu verifikasi Accounting</p>
        </div>
        <a href="{{ route('speedshop.keuangan.setoran.index') }}" class="btn-secondary shrink-0">← Kembali</a>
    </div>


    <form x-ref="form" @submit.prevent="confirmSubmit($event)" method="POST" action="{{ route('speedshop.keuangan.setoran.store') }}" enctype="multipart/form-data" class="grid grid-cols-1 gap-5 lg:grid-cols-12 ss-dash-enter" style="--ss-delay: 80ms">
        @csrf

        <div class="space-y-5 lg:col-span-8">
            <div class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 0">
                <div class="border-b border-gray-100 px-5 py-4">
                    <div class="section-hd" style="margin-bottom:0">
                        <h2>Setoran Kas</h2>
                        <p>Pilih tanggal setor — saldo buku Kas Kecil dimuat otomatis</p>
                    </div>
                </div>
                <div class="panel-card-body">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="wo-label">Tanggal Setor <span class="req">*</span></label>
                            <input type="date" name="tanggal_setor" x-model="tanggal" @change="fetchSnap()" class="wo-input font-semibold" required>
                        </div>
                        <div>
                            <label class="wo-label">Saldo Kas Kecil (buku)</label>
                            <div class="saldo-readonly tabular-nums" x-text="snap ? ('Rp ' + formatRp(snap.saldo_kas_kecil)) : (loading ? '…' : '—')"></div>
                        </div>
                    </div>

                    <div x-show="!loading && snap" x-cloak class="mt-4 rounded-xl border border-gray-100 bg-gray-50/80 px-4 py-3 text-[12.5px] text-gray-700">
                        <p class="font-semibold text-gray-900">Rincian kas tunai hari ini</p>
                        <div class="mt-2 grid gap-1.5 sm:grid-cols-2">
                            <div class="flex justify-between gap-3"><span class="text-gray-500">Transaksi tunai</span><span class="font-semibold tabular-nums" x-text="'Rp ' + formatRp(snap.pendapatan_tunai || 0)"></span></div>
                            <div class="flex justify-between gap-3"><span class="text-gray-500">+ Permintaan kas</span><span class="font-semibold tabular-nums text-sky-700" x-text="'Rp ' + formatRp(snap.permintaan_kas || 0)"></span></div>
                            <div class="flex justify-between gap-3"><span class="text-gray-500">− BOP tunai</span><span class="font-semibold tabular-nums text-amber-700" x-text="'Rp ' + formatRp(snap.bop_kas_tunai || 0)"></span></div>
                            <div class="flex justify-between gap-3"><span class="text-gray-500">Sisa setelah BOP</span><span class="font-semibold tabular-nums text-emerald-700" x-text="'Rp ' + formatRp(snap.sisa_setelah_bop || snap.saldo_kas_kecil || 0)"></span></div>
                            <div class="flex justify-between gap-3"><span class="text-gray-500">Saran setor</span><span class="font-semibold tabular-nums text-red-600" x-text="'Rp ' + formatRp(snap.wajib_setor_tunai || 0)"></span></div>
                            <div class="flex justify-between gap-3"><span class="text-gray-500">Kas disimpan besok</span><span class="font-semibold tabular-nums text-indigo-700" x-text="'Rp ' + formatRp(snap.kas_disimpan_besok || snap.sisa_laci_setelah_setor || 0)"></span></div>
                        </div>
                    </div>

                    <template x-if="errorMsg">
                        <p class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" x-text="errorMsg"></p>
                    </template>

                    <div x-show="loading" x-cloak class="mt-4 flex items-center gap-3 rounded-xl border border-gray-100 bg-gray-50 px-4 py-3.5 text-sm text-gray-600">
                        <svg class="h-4 w-4 shrink-0 animate-spin text-red-600" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        Memuat saldo buku…
                    </div>

                    <template x-if="!loading && snap && missingBefore && missingBefore.length">
                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                            Tidak dapat mengajukan setoran untuk tanggal ini karena masih ada tanggal sebelumnya yang belum disetor (Silahkan disetor terlebih dahulu untuk tanggal: <span class="font-semibold" x-text="formatTanggal(missingBefore[0])"></span>).
                        </div>
                    </template>

                    <div x-show="!loading && !snap && !errorMsg" class="mt-4 rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">
                        Pilih tanggal setor untuk memuat saldo buku.
                    </div>
                </div>
            </div>

            <div x-show="!loading && snap" x-cloak class="{{ $woPanelClass }}" style="--ss-delay: 120ms; --ss-stagger: 1">
                <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="section-hd" style="margin-bottom:0">
                        <h2>Transaksi Setoran</h2>
                        <p>Minimal 1 baris — sumber dan tujuan tidak boleh sama</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <span class="wo-badge" x-text="transactions.length + ' baris'"></span>
                        <button type="button" @click="addTx()" class="btn-primary inline-flex h-10 items-center gap-2 px-4">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            Tambah Baris
                        </button>
                    </div>
                </div>

                <div class="panel-card-body space-y-3">
                    <template x-for="(tx, idx) in transactions" :key="`card-${tx._k}`">
                        <div class="setoran-tx-card">
                            <input type="hidden" :name="`transactions[${idx}][_idx]`" :value="idx">
                            <div class="flex items-start justify-between gap-3">
                                <div class="text-sm font-semibold text-gray-900">Transaksi #<span x-text="idx + 1"></span></div>
                                <button type="button" @click="removeTx(idx)"
                                        class="wo-icon-btn"
                                        :disabled="transactions.length <= 1"
                                        :class="transactions.length <= 1 && 'opacity-40 pointer-events-none'"
                                        title="Hapus baris">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>

                            <div class="mt-3 grid grid-cols-1 gap-3">
                                <div>
                                    <label class="wo-label">Sumber <span class="req">*</span></label>
                                    <select x-model="tx.source_key"
                                            @change="if (tx.target_key === tx.source_key) tx.target_key = ''; afterTxPairChange(idx);"
                                            class="wo-native">
                                        <option value="">— pilih sumber —</option>
                                        <option value="kas_kecil">Kas Kecil (Tunai)</option>
                                        <optgroup label="Kas Besar (Bank)">
                                            <template x-for="r in cfg.rekeningsKasBesar" :key="`kb-src-m-${r.id}`">
                                                <option :value="`kas_besar:${r.id}`" x-text="`${r.nama} · ${r.nama_bank} · ${r.nomor_rekening}`"></option>
                                            </template>
                                        </optgroup>
                                        <optgroup label="Rekening Utama">
                                            <template x-for="r in cfg.rekeningsUtama" :key="`ru-src-m-${r.id}`">
                                                <option :value="`rekening_utama:${r.id}`" x-text="`${r.nama} · ${r.nama_bank} · ${r.nomor_rekening}`"></option>
                                            </template>
                                        </optgroup>
                                    </select>
                                    <input type="hidden" :name="`transactions[${idx}][source]`" :value="parseKey(tx.source_key).type">
                                    <input type="hidden" :name="`transactions[${idx}][source_bank_id]`" :value="parseKey(tx.source_key).bankId">
                                </div>

                                <div>
                                    <label class="wo-label">Tujuan <span class="req">*</span></label>
                                    <select x-model="tx.target_key"
                                            @change="afterTxPairChange(idx)"
                                            class="wo-native">
                                        <option value="">— pilih tujuan —</option>
                                        <option value="kas_kecil" :disabled="tx.source_key === 'kas_kecil'">Kas Kecil</option>
                                        <optgroup label="Kas Besar (Bank)">
                                            <template x-for="r in cfg.rekeningsKasBesar" :key="`kb-dst-m-${r.id}`">
                                                <option :value="`kas_besar:${r.id}`" :disabled="tx.source_key === `kas_besar:${r.id}`" x-text="`${r.nama} · ${r.nama_bank} · ${r.nomor_rekening}`"></option>
                                            </template>
                                        </optgroup>
                                        <optgroup label="Rekening Utama">
                                            <template x-for="r in cfg.rekeningsUtama" :key="`ru-dst-m-${r.id}`">
                                                <option :value="`rekening_utama:${r.id}`" :disabled="tx.source_key === `rekening_utama:${r.id}`" x-text="`${r.nama} · ${r.nama_bank} · ${r.nomor_rekening}`"></option>
                                            </template>
                                        </optgroup>
                                    </select>
                                    <input type="hidden" :name="`transactions[${idx}][target]`" :value="parseKey(tx.target_key).type">
                                    <input type="hidden" :name="`transactions[${idx}][target_bank_id]`" :value="parseKey(tx.target_key).bankId">
                                </div>

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    <div>
                                        <label class="wo-label">Saldo (Sumber)</label>
                                        <div class="flex min-h-[42px] items-center justify-end rounded-xl border border-gray-200 bg-gray-50 px-3 text-right text-sm font-semibold tabular-nums"
                                             :class="remainingSaldo(tx) < 0 ? 'border-red-200 bg-red-50 text-red-700' : 'text-gray-900'">
                                            <span x-show="sourceSaldo(tx) !== null" x-text="'Rp ' + formatRp(remainingSaldo(tx))"></span>
                                            <span x-show="sourceSaldo(tx) === null" class="text-gray-400">—</span>
                                        </div>
                                        <div class="mt-1 text-[11px] text-gray-400" x-show="sourceSaldo(tx) !== null">
                                            Saldo awal: <span class="font-medium text-gray-600" x-text="'Rp ' + formatRp(sourceSaldo(tx))"></span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="wo-label">Nominal <span class="req">*</span></label>
                                        <input type="hidden" :name="`transactions[${idx}][amount]`" :value="tx.amount">
                                        <div class="relative">
                                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-medium text-gray-400">Rp</span>
                                            <input type="text" inputmode="numeric" autocomplete="off" placeholder="0"
                                                   class="wo-input pl-10 text-right tabular-nums"
                                                   :value="formatRp(tx.amount)"
                                                   @input="tx.amount = Math.max(0, parseDigits($event.target.value))">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="wo-label">
                                            Bukti
                                            <span class="req" x-show="Number(tx.amount) > 0">*</span>
                                            <span class="text-gray-400 normal-case tracking-normal" x-show="Number(tx.amount) <= 0">(opsional jika nominal 0)</span>
                                        </label>
                                        <input type="file"
                                               :name="`bukti[${idx}]`"
                                               :required="Number(tx.amount) > 0"
                                               accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
                                               class="wo-file-input">
                                    </div>
                                </div>

                                <div>
                                    <label class="wo-label">Keterangan <span class="text-gray-400 normal-case tracking-normal">(opsional)</span></label>
                                    <input type="text" x-model="tx.note" maxlength="255"
                                           :name="`transactions[${idx}][note]`"
                                           class="wo-input"
                                           placeholder="Opsional">
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="transactions.length === 0">
                        <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-5 py-12 text-center text-gray-400">
                            <p class="text-sm font-medium">Belum ada transaksi</p>
                            <p class="mt-1 text-xs">Klik Tambah Baris untuk mulai input</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="space-y-4 lg:col-span-4">
            <div class="panel-card panel-card-flush summary-card ss-dash-enter-child" style="--ss-delay: 120ms; --ss-stagger: 2">
                <div class="section-hd">
                    <h2>Ringkasan</h2>
                    <p>Otomatis dari transaksi yang diinput</p>
                </div>

                <div class="mt-2 mb-2">
                    <div class="summary-row">
                        <span>KK → Kas Besar</span>
                        <span class="tabular-nums" x-text="'Rp ' + formatRp(sumKkToKb())"></span>
                    </div>
                    <div class="summary-row">
                        <span>KK → Rekening Utama</span>
                        <span class="tabular-nums" x-text="'Rp ' + formatRp(sumKkToRu())"></span>
                    </div>
                    <div class="summary-row">
                        <span>Kas Besar → RU</span>
                        <span class="tabular-nums" x-text="'Rp ' + formatRp(sumKbToRu())"></span>
                    </div>
                    <div class="summary-total">
                        <span>Total Tercatat</span>
                        <span x-text="'Rp ' + formatRp(totalAmount())"></span>
                    </div>
                </div>

                <div x-show="!loading && snap" x-cloak class="border-t border-gray-100 px-5 py-4">
                    <label class="wo-label">Catatan <span class="text-gray-400 normal-case tracking-normal">(opsional)</span></label>
                    <textarea name="catatan" rows="3" class="wo-textarea" placeholder="Catatan tambahan…">{{ old('catatan') }}</textarea>
                </div>

                <div class="space-y-3 border-t border-gray-100 px-5 pb-5 pt-5">
                    <button type="submit"
                            class="btn-primary flex h-11 w-full disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="!canSubmit()">
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span x-text="cfg.isAccounting ? 'Simpan dan selesai' : 'Kirim untuk verifikasi'"></span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
</div>
@endsection

@push('scripts')
<script>
function setoranCreateForm(cfg) {
    const oldUtama = cfg.oldRekeningUtamaId;
    const initialUtama = (oldUtama !== undefined && oldUtama !== null && String(oldUtama) !== '')
        ? String(oldUtama)
        : '';
    const oldKb = cfg.oldKasBesarId;
    const initialKb = (oldKb !== undefined && oldKb !== null && String(oldKb) !== '')
        ? String(oldKb)
        : '';

    return {
        cfg,
        rekeningUtamaTs: null,
        kasBesarTs: null,
        tanggal: cfg.defaultTanggal,
        selectedUtamaId: initialUtama,
        selectedKasBesarId: initialKb,
        transactions: [],
        loading: false,
        snap: null,
        missingBefore: [],
        errorMsg: null,
        formatTanggal(ymd) {
            const s = String(ymd || '');
            if (!s) return '—';
            try {
                const [y, m, d] = s.split('-').map((x) => parseInt(x, 10));
                if (!y || !m || !d) return s;
                return String(d).padStart(2, '0') + '/' + String(m).padStart(2, '0') + '/' + String(y);
            } catch (e) {
                return s;
            }
        },
        formatRp(n) {
            return new Intl.NumberFormat('id-ID').format(Number(n) || 0);
        },
        parseDigits(str) {
            const digits = String(str ?? '').replace(/\D/g, '');
            if (digits === '') {
                return 0;
            }
            const n = parseInt(digits, 10);
            return Number.isFinite(n) ? n : 0;
        },
        findRekeningById(list, id) {
            const rid = String(id || '');
            if (!rid) return null;
            const rows = Array.isArray(list) ? list : [];
            return rows.find((r) => String(r?.id ?? '') === rid) || null;
        },
        sourceSig(tx) {
            const s = this.parseKey(tx?.source_key);
            if (!s.type) return null;
            if (s.type === 'kas_kecil') return 'kas_kecil:0';
            const bid = String(s.bankId || '');
            if (!bid) return null;
            return `${s.type}:${bid}`;
        },
        sourceSaldo(tx) {
            if (!this.snap) return null;
            const s = this.parseKey(tx?.source_key);
            if (!s.type) return null;
            if (s.type === 'kas_kecil') {
                const n = Number(this.snap?.saldo_kas_kecil);
                return Number.isFinite(n) ? n : 0;
            }
            const bid = String(s.bankId || '');
            if (!bid) return null;
            if (s.type === 'kas_besar') {
                const r = this.findRekeningById(this.cfg?.rekeningsKasBesar, bid);
                return r ? (Number(r.saldo_buku) || 0) : null;
            }
            if (s.type === 'rekening_utama') {
                const r = this.findRekeningById(this.cfg?.rekeningsUtama, bid);
                return r ? (Number(r.saldo_buku) || 0) : null;
            }
            return null;
        },
        remainingSaldo(tx) {
            const base = this.sourceSaldo(tx);
            const sig = this.sourceSig(tx);
            if (base === null || !sig) return 0;
            const used = (Array.isArray(this.transactions) ? this.transactions : [])
                .filter((t) => this.sourceSig(t) === sig)
                .reduce((acc, t) => acc + (Number(t?.amount) || 0), 0);
            return (Number(base) || 0) - used;
        },
        pairSignature(tx) {
            const s = this.parseKey(tx?.source_key);
            const d = this.parseKey(tx?.target_key);
            if (!s.type || !d.type) {
                return null;
            }
            const sb = s.type === 'kas_kecil' ? '0' : String(s.bankId || '');
            const db = d.type === 'kas_kecil' ? '0' : String(d.bankId || '');
            if (s.type !== 'kas_kecil' && sb === '') {
                return null;
            }
            if (d.type !== 'kas_kecil' && db === '') {
                return null;
            }
            return `${s.type}:${sb}|${d.type}:${db}`;
        },
        hasDuplicatePairs() {
            const seen = new Set();
            const list = Array.isArray(this.transactions) ? this.transactions : [];
            for (let i = 0; i < list.length; i++) {
                const sig = this.pairSignature(list[i]);
                if (!sig) {
                    continue;
                }
                if (seen.has(sig)) {
                    return true;
                }
                seen.add(sig);
            }
            return false;
        },
        async afterTxPairChange(idx) {
            const tx = this.transactions[idx];
            if (!tx) {
                return;
            }
            const sig = this.pairSignature(tx);
            if (!sig) {
                return;
            }
            const dupAt = this.transactions.findIndex((t, i) => i !== idx && this.pairSignature(t) === sig);
            if (dupAt === -1) {
                return;
            }
            tx.target_key = '';
            if (typeof Swal !== 'undefined') {
                await Swal.fire({
                    icon: 'warning',
                    title: 'Sumber dan tujuan sudah dipakai',
                    text: `Sumber ${this.parseKey(tx.source_key).type} dan tujuan ${this.parseKey(tx.target_key).type} sudah ada di baris lain. Gabungkan nominal dalam satu baris atau pilih kombinasi lain.`,
                    confirmButtonText: 'Ya, ganti',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#9ca3af',
                    reverseButtons: true,
                });
            }
        },
        parseKey(key) {
            const k = String(key ?? '');
            if (k === '' || k === 'kas_kecil') {
                return { type: k || '', bankId: '' };
            }
            const parts = k.split(':');
            if (parts.length !== 2) {
                return { type: '', bankId: '' };
            }
            const type = parts[0] || '';
            const bankId = parts[1] || '';
            return { type, bankId };
        },
        addTx() {
            const k = Math.random().toString(16).slice(2);
            this.transactions.push({
                _k: k,
                source_key: '',
                target_key: '',
                amount: 0,
                note: '',
            });
        },
        removeTx(idx) {
            this.transactions.splice(idx, 1);
            if (this.transactions.length === 0) {
                this.addTx();
            }
        },
        totalAmount() {
            return (Array.isArray(this.transactions) ? this.transactions : [])
                .reduce((acc, t) => acc + (Number(t?.amount) || 0), 0);
        },
        sumKkToKb() {
            return (Array.isArray(this.transactions) ? this.transactions : [])
                .filter((t) => this.parseKey(t?.source_key).type === 'kas_kecil' && this.parseKey(t?.target_key).type === 'kas_besar')
                .reduce((acc, t) => acc + (Number(t?.amount) || 0), 0);
        },
        sumKkToRu() {
            return (Array.isArray(this.transactions) ? this.transactions : [])
                .filter((t) => this.parseKey(t?.source_key).type === 'kas_kecil' && this.parseKey(t?.target_key).type === 'rekening_utama')
                .reduce((acc, t) => acc + (Number(t?.amount) || 0), 0);
        },
        sumKbToRu() {
            return (Array.isArray(this.transactions) ? this.transactions : [])
                .filter((t) => this.parseKey(t?.source_key).type === 'kas_besar' && this.parseKey(t?.target_key).type === 'rekening_utama')
                .reduce((acc, t) => acc + (Number(t?.amount) || 0), 0);
        },
        rekeningUtamaOk() { return true; },
        needsKasBesarSelect() { return false; },
        kasBesarOk() { return true; },
        hasInvoiceDay() {
            return this.snap && (this.snap.pendapatan_tunai > 0 || this.snap.pendapatan_digital > 0);
        },
        buktiKasKecilOk() { return true; },
        buktiKasBesarOk() { return true; },
        canProceedNominal() {
            if (this.loading || !this.snap) return false;
            if (Array.isArray(this.missingBefore) && this.missingBefore.length > 0) return false;
            return this.totalAmount() >= 0;
        },
        txRowsValid() {
            const list = Array.isArray(this.transactions) ? this.transactions : [];
            return list.every((tx) => {
                const src = this.parseKey(tx?.source_key).type;
                const dst = this.parseKey(tx?.target_key).type;
                if (!src || !dst || src === dst) {
                    return false;
                }
                const amt = Number(tx?.amount) || 0;
                if (amt > 0) {
                    return true;
                }
                return amt === 0;
            });
        },
        canSubmit() {
            return this.canProceedNominal() && this.txRowsValid() && !this.hasDuplicatePairs() && this.buktiKasKecilOk() && this.buktiKasBesarOk();
        },
        async confirmSubmit(e) {
            if (!this.canProceedNominal()) {
                return;
            }
            if (this.hasDuplicatePairs()) {
                if (typeof Swal !== 'undefined') {
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Pasangan sumber–tujuan duplikat',
                        text: 'Ada lebih dari satu baris dengan sumber dan tujuan yang sama. Hapus duplikat atau gabungkan nominalnya.',
                    });
                }
                return;
            }
            if (!this.canSubmit()) {
                return;
            }
            const form = e.target;
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            if (typeof Swal === 'undefined') {
                form.submit();
                return;
            }
            const total = this.totalAmount();
            const isZero = total <= 0;
            const result = await Swal.fire({
                title: isZero ? 'Kirim setoran tanpa tunai?' : 'Kirim setoran untuk verifikasi?',
                html: isZero
                    ? `<div style="text-align:left;font-size:14px;">
                    <div style="margin-top:6px;">Tidak ada nominal tunai yang disetor (<strong>Rp 0</strong>).</div>
                    <div style="margin-top:6px;color:#6b7280;">Setoran tetap dicatat agar hari ini dianggap selesai, lalu menunggu verifikasi Accounting.</div>
                </div>`
                    : `<div style="text-align:left;font-size:14px;">
                    <div style="margin-top:6px;">Total: <strong>Rp ${this.formatRp(total)}</strong></div>
                    <div style="margin-top:6px;color:#6b7280;">Setoran akan berstatus <strong>Menunggu verifikasi</strong> sampai Accounting menyetujui.</div>
                </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, kirim',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#9ca3af',
                reverseButtons: true,
            });
            if (result.isConfirmed) {
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }
                form.submit();
            }
        },
        applyAmountDefaultsFromSnap() {
            if (!this.snap || Number(this.snap.saldo_kas_kecil) !== 0) {
                return;
            }
            const tx = this.transactions[0];
            if (!tx) {
                return;
            }
            const utama = Array.isArray(this.cfg.rekeningsUtama) ? this.cfg.rekeningsUtama[0] : null;
            if (!tx.source_key) {
                tx.source_key = 'kas_kecil';
            }
            if (!tx.target_key && utama) {
                tx.target_key = `rekening_utama:${utama.id}`;
            }
        },
        destroyTomSelect(ts) {
            if (!ts) return;
            try {
                ts.destroy();
            } catch (e) {
            }
        },
        buildTomSelectOptions(rows) {
            const fmtRp = (n) => new Intl.NumberFormat('id-ID').format(Number(n) || 0);
            return (Array.isArray(rows) ? rows : []).map((r) => ({
                id: String(r.id),
                nama: r.nama || '',
                nama_bank: r.nama_bank || '',
                nomor_rekening: r.nomor_rekening || '',
                saldo_buku: Number(r.saldo_buku) || 0,
                text: [r.nama, r.nama_bank, r.nomor_rekening].filter(Boolean).join(' '),
                _fmtRp: fmtRp,
            }));
        },
        initRekeningUtamaTomSelect() {
            const el = this.$refs.rekeningUtamaSelect;
            if (!el || !cfg.banksRequired || typeof TomSelect === 'undefined') {
                return;
            }
            this.destroyTomSelect(this.rekeningUtamaTs);
            this.rekeningUtamaTs = null;
            const tsOptions = this.buildTomSelectOptions(cfg.rekeningsUtama);
            const fmtRp = (n) => new Intl.NumberFormat('id-ID').format(Number(n) || 0);
            this.rekeningUtamaTs = new TomSelect(el, {
                placeholder: el.dataset.placeholder || 'Pilih rekening utama…',
                allowEmptyOption: true,
                maxOptions: null,
                create: false,
                valueField: 'id',
                labelField: 'text',
                searchField: ['text', 'nama', 'nama_bank', 'nomor_rekening'],
                options: tsOptions,
                dropdownParent: 'body',
                onChange: (val) => {
                    this.selectedUtamaId = val ? String(val) : '';
                },
                render: {
                    option: (data, escape) => {
                        const rp = fmtRp(data.saldo_buku);
                        const bank = data.nama_bank
                            ? escape(data.nama_bank)
                            : '<span class="text-gray-400">—</span>';
                        return `<div class="flex max-w-[min(100vw-2rem,32rem)] items-start justify-between gap-4 border-b border-gray-100 py-2.5 pl-1 pr-2 last:border-0">
                            <div class="min-w-0 flex-1 text-left">
                                <div class="text-sm font-semibold text-gray-900">${escape(data.nama)}</div>
                                <div class="mt-0.5 text-xs text-gray-600">${bank} <span class="text-gray-300">·</span> <span class="font-mono tabular-nums">${escape(data.nomor_rekening)}</span></div>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Saldo</div>
                                <div class="text-sm font-bold tabular-nums text-emerald-900">Rp ${escape(rp)}</div>
                            </div>
                        </div>`;
                    },
                    item: (data, escape) => {
                        const rp = fmtRp(data.saldo_buku);
                        return `<div class="truncate text-sm text-gray-900">${escape(data.nama)} <span class="text-gray-400">·</span> <span class="font-semibold tabular-nums text-emerald-800">Rp ${escape(rp)}</span></div>`;
                    },
                },
            });
            if (this.selectedUtamaId) {
                this.rekeningUtamaTs.setValue(this.selectedUtamaId, true);
            }
        },
        initKasBesarTomSelect() {
            const el = this.$refs.kasBesarSelect;
            if (!el || !this.needsKasBesarSelect() || typeof TomSelect === 'undefined') {
                return;
            }
            this.destroyTomSelect(this.kasBesarTs);
            this.kasBesarTs = null;
            const tsOptions = this.buildTomSelectOptions(cfg.rekeningsKasBesar);
            const fmtRp = (n) => new Intl.NumberFormat('id-ID').format(Number(n) || 0);
            this.kasBesarTs = new TomSelect(el, {
                placeholder: el.dataset.placeholder || 'Cari atau pilih rekening Kas Besar…',
                allowEmptyOption: true,
                maxOptions: null,
                create: false,
                valueField: 'id',
                labelField: 'text',
                searchField: ['text', 'nama', 'nama_bank', 'nomor_rekening'],
                options: tsOptions,
                dropdownParent: 'body',
                onChange: (val) => {
                    this.selectedKasBesarId = val ? String(val) : '';
                },
                render: {
                    option: (data, escape) => {
                        const rp = fmtRp(data.saldo_buku);
                        const bank = data.nama_bank
                            ? escape(data.nama_bank)
                            : '<span class="text-gray-400">—</span>';
                        return `<div class="flex max-w-[min(100vw-2rem,32rem)] items-start justify-between gap-4 border-b border-gray-100 py-2.5 pl-1 pr-2 last:border-0">
                            <div class="min-w-0 flex-1 text-left">
                                <div class="text-sm font-semibold text-gray-900">${escape(data.nama)}</div>
                                <div class="mt-0.5 text-xs text-gray-600">${bank} <span class="text-gray-300">·</span> <span class="font-mono tabular-nums">${escape(data.nomor_rekening)}</span></div>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Saldo</div>
                                <div class="text-sm font-bold tabular-nums text-emerald-900">Rp ${escape(rp)}</div>
                            </div>
                        </div>`;
                    },
                    item: (data, escape) => {
                        const rp = fmtRp(data.saldo_buku);
                        return `<div class="truncate text-sm text-gray-900">${escape(data.nama)} <span class="text-gray-400">·</span> <span class="font-semibold tabular-nums text-emerald-800">Rp ${escape(rp)}</span></div>`;
                    },
                },
            });
            if (this.selectedKasBesarId) {
                this.kasBesarTs.setValue(this.selectedKasBesarId, true);
            }
        },
        async fetchSnap() {
            if (!this.tanggal) return;
            this.loading = true;
            this.snap = null;
            this.missingBefore = [];
            this.errorMsg = null;
            try {
                const url = cfg.snapshotUrl + '?tanggal=' + encodeURIComponent(this.tanggal);
                const res = await fetch(url, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Gagal memuat data');
                this.snap = data.snap;
                this.missingBefore = Array.isArray(data.missing_before) ? data.missing_before : [];
                this.applyAmountDefaultsFromSnap();
            } catch (e) {
                this.errorMsg = e.message || 'Terjadi kesalahan';
            } finally {
                this.loading = false;
            }
        },
        init() {
            this.$nextTick(() => {
                this.initRekeningUtamaTomSelect();
                this.initKasBesarTomSelect();
                this.fetchSnap();
            });
            const olds = Array.isArray(this.cfg.oldTransactions) ? this.cfg.oldTransactions : [];
            if (olds.length > 0) {
                this.transactions = olds.map((t) => ({
                    _k: Math.random().toString(16).slice(2),
                    source_key: (t.source === 'kas_kecil' || !t.source) ? 'kas_kecil' : `${t.source}:${t.source_bank_id || ''}`,
                    target_key: (t.target === 'kas_kecil' || !t.target) ? 'kas_kecil' : `${t.target}:${t.target_bank_id || ''}`,
                    amount: Number(t.amount) || 0,
                    note: t.note || '',
                }));
            } else {
                this.addTx();
            }
        },
    };
}
</script>
@endpush

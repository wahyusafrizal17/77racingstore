<div
    x-show="openModal"
    x-cloak
    class="fixed inset-0 z-[100] overflow-y-auto"
    style="display: none;"
    @keydown.escape.window="close()"
>
    <div x-show="openModal" x-transition.opacity class="fixed inset-0 bg-black/50" @click="close()"></div>
    <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
        <div
            x-show="openModal"
            x-transition
            class="relative flex max-h-[min(92vh,860px)] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl"
            @click.stop
        >
            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 px-5 py-4 sm:px-6">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Ringkasan Omset</p>
                    <h3 class="mt-0.5 text-xl font-bold tracking-tight text-gray-900" x-text="title"></h3>
                    <p class="mt-1 text-sm text-gray-500" x-text="subtitle"></p>
                </div>
                <button type="button" @click="close()" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-gray-200 text-gray-500 transition hover:bg-gray-50 hover:text-gray-800" aria-label="Tutup">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
                <div x-show="loading" class="flex items-center justify-center gap-3 py-16 text-sm text-gray-500">
                    <span class="ss-dash-spinner !h-6 !w-6" aria-hidden="true"></span>
                    Memuat rincian…
                </div>
                <div x-show="!loading && error" x-cloak class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" x-text="error"></div>
                <div x-show="!loading && !error && compareEnabled" x-cloak class="omset-compare-summary mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-gray-200 bg-gray-50/80 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Periode ini</p>
                        <p class="mt-1 text-lg font-bold text-gray-900" x-text="rupiah(total)"></p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50/80 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500" x-text="comparePeriodLabel ? ('Periode lalu · ' + comparePeriodLabel) : 'Periode lalu'"></p>
                        <p class="mt-1 text-lg font-bold text-gray-700" x-text="rupiah(compareTotal)"></p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50/80 px-4 py-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Perubahan</p>
                        <p class="mt-1 text-lg font-bold" :class="compareDeltaPercent >= 0 ? 'text-emerald-700' : 'text-red-600'" x-text="formatPercent(compareDeltaPercent)"></p>
                    </div>
                </div>
                <div x-show="!loading && !error && tableHtml" x-cloak>
                    <div class="omset-detail-table-wrap overflow-x-auto rounded-xl border border-gray-200" x-html="tableHtml"></div>
                    <div class="mt-4 flex justify-end border-t border-gray-100 pt-3">
                        <div class="flex w-full max-w-sm justify-between gap-6 text-sm">
                            <span class="font-medium text-gray-600" x-text="totalLabel"></span>
                            <span class="font-bold text-gray-900" x-text="rupiah(total)"></span>
                        </div>
                    </div>
                </div>
                <div x-show="!loading && !error && !tableHtml" x-cloak class="rounded-xl border border-gray-200 px-4 py-10 text-center text-sm text-gray-400">
                    Belum ada data pada periode ini.
                </div>
            </div>
        </div>
    </div>
</div>

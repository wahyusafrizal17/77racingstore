@php
    $speedshopOptions = collect();
    $selectedScope = null;
    if (auth()->check() && auth()->user()->section === 'speedshop' && ! auth()->user()->isOwner()) {
        $speedshopIds = auth()->user()->activeSpeedshopIds();
        if (count($speedshopIds) > 1) {
            $speedshopOptions = \App\Models\Speedshop::query()
                ->whereIn('id', $speedshopIds)
                ->orderBy('nama')
                ->get(['id', 'nama']);
            $selectedScope = auth()->user()->speedshopScopeId();
        }
    }
@endphp
<header class="flex h-14 items-center justify-between gap-2 border-b border-gray-200 bg-white px-3 sm:h-16 sm:gap-3 sm:px-4">
    <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
        <button type="button" @click="sidebarOpen = !sidebarOpen" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900" :title="sidebarOpen ? 'Tutup menu sidebar' : 'Buka menu sidebar'">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="min-w-0 sm:w-52 sm:shrink-0">
            @include('partials.global-product-stock-search')
        </div>
    </div>

    <div class="flex shrink-0 items-center justify-end gap-2 sm:gap-3">
        @if(auth()->user()?->isModifikasiOriHeadAudit())
            @include('partials.head-audit-scope-filter')
        @elseif($speedshopOptions->isNotEmpty())
            <form method="POST" action="{{ route('speedshop.scope.update') }}" class="min-w-0 max-w-[8.5rem] sm:max-w-none">
                @csrf
                <label for="speedshop-scope" class="sr-only">Filter Speedshop</label>
                <select id="speedshop-scope" name="scope" onchange="this.form.submit()"
                    class="h-10 w-full rounded-xl border border-gray-200 bg-white px-2 text-xs font-medium text-gray-700 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40 sm:px-3 sm:text-sm">
                    @if(auth()->user()->isManager() || auth()->user()->isSpeedshopAccounting())
                        <option value="all" {{ $selectedScope === null ? 'selected' : '' }}>Semua Speedshop</option>
                    @endif
                    @foreach($speedshopOptions as $speedshopOption)
                        <option value="{{ $speedshopOption->id }}" {{ (int) $selectedScope === (int) $speedshopOption->id ? 'selected' : '' }}>
                            {{ $speedshopOption->nama }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif

        @include('partials.notification-category-dropdowns')

        @include('partials.shared.nav-user-menu', ['navUserCompact' => true])
    </div>
</header>

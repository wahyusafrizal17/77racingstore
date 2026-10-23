@php
    $selectedScope = auth()->user()?->headAuditScopeSelection() ?? 'modifikasi';
    $speedshopOptions = \App\Models\Speedshop::query()->orderBy('nama')->get(['id', 'nama']);
@endphp
<form method="POST" action="{{ route('head-audit.scope.update') }}" class="min-w-0 max-w-[10rem] sm:max-w-none">
    @csrf
    <label for="head-audit-scope" class="sr-only">Filter 77racing / Speedshop</label>
    <select id="head-audit-scope" name="scope" onchange="this.form.submit()"
        class="h-10 w-full rounded-xl border border-gray-200 bg-white px-2 text-xs font-medium text-gray-700 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/40 sm:px-3 sm:text-sm">
        <option value="modifikasi" @selected($selectedScope === 'modifikasi')>77racing</option>
        <option value="all" @selected($selectedScope === 'all')>Semua Speedshop</option>
        @foreach($speedshopOptions as $speedshopOption)
            <option value="{{ $speedshopOption->id }}" @selected($selectedScope === (string) $speedshopOption->id)>
                {{ $speedshopOption->nama }}
            </option>
        @endforeach
    </select>
</form>

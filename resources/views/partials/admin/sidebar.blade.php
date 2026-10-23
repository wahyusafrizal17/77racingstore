<aside class="sticky top-0 flex hidden h-screen w-[270px] flex-shrink-0 flex-col overflow-hidden border-r border-white/10 bg-[#0b0d12] text-white md:flex">
    <div class="flex flex-shrink-0 items-center gap-3 border-b border-white/10 px-5 py-4">
        <img src="{{ asset('images/logo-w.png') }}" alt="77racing" class="h-9 w-9 object-contain">
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold tracking-wide text-white">Panel HR</p>
            <p class="text-[10px] uppercase tracking-[0.16em] text-white/45">{{ auth()->user()?->role ?? '77racing' }}</p>
        </div>
    </div>

    <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-4 pb-4 pt-3">
        @if(auth()->user()?->isModifikasiOriHrAdmin())
            @include('partials.admin.sidebar-nav-items-hr')
        @else
            @include('partials.admin.sidebar-nav-items')
        @endif
    </nav>
</aside>

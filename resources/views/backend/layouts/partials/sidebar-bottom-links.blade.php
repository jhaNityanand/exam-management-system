@php
    $bottomLinks = [
        ['route' => 'admin.logs.index', 'label' => 'Logs', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['route' => 'admin.settings.index', 'label' => 'Settings', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
        ['route' => 'admin.profile.edit', 'label' => 'Profile', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
    ];
@endphp

<div class="space-y-1">
    <button type="button" data-sidebar-toggle class="sidebar-link hidden lg:flex w-full shrink-0 items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium text-slate-300 transition hover:bg-white/10 hover:text-white">
        <svg data-sidebar-toggle-icon class="h-5 w-5 shrink-0 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
        </svg>
        <span class="truncate" data-sidebar-label>Collapse Sidebar</span>
    </button>

    @foreach ($bottomLinks as $link)
        @php
            $active = false;
            try {
                $active = ($link['route'] !== '#') && request()->routeIs(rtrim($link['route'], '.*') . '*');
            } catch (\Throwable) {
                $active = false;
            }
        @endphp
        <a href="{{ $link['route'] !== '#' ? route($link['route']) : '#' }}"
           class="sidebar-link flex shrink-0 items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition {{ $active ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/>
            </svg>
            <span class="truncate" data-sidebar-label>{{ $link['label'] }}</span>
        </a>
    @endforeach

    <form method="POST" action="{{ route('logout') }}" class="shrink-0 w-full mb-0 block !mb-1">
        @csrf
        <button type="submit"
                class="sidebar-link flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium text-slate-300 transition hover:bg-white/10 hover:text-white">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
            </svg>
            <span class="truncate" data-sidebar-label>Logout</span>
        </button>
    </form>
</div>

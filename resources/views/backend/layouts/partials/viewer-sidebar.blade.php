@php
$links = [
    ['route' => 'viewer.dashboard',       'label' => 'Dashboard',    'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    ['route' => 'viewer.exams.index',     'label' => 'Available Exams','icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
    ['route' => 'viewer.attempts.index',  'label' => 'My Results',   'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
    ['route' => 'profile.edit',           'label' => 'Profile',      'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
];
@endphp

@foreach ($links as $link)
    @php
        $active = false;
        try { $active = request()->routeIs(rtrim($link['route'], '.*') . '*'); } catch (\Throwable) {}
    @endphp
    <a href="{{ route($link['route']) }}"
       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
              {{ $active ? 'bg-violet-600 text-white' : 'text-violet-200 hover:bg-violet-800 hover:text-white' }}">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/>
        </svg>
        <span data-sidebar-label>{{ $link['label'] }}</span>
    </a>
@endforeach

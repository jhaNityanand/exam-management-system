@php
$links = [
    ['route' => 'org-admin.dashboard',    'label' => 'Dashboard', 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    ['route' => 'org-admin.exams.index',  'label' => 'Exams',     'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
    ['route' => 'org-admin.members.index','label' => 'Members',   'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
    ['route' => 'profile.edit',           'label' => 'Profile',   'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
];
@endphp

@foreach ($links as $link)
    @php
        $active = false;
        try { $active = request()->routeIs(rtrim($link['route'], '.*') . '*'); } catch (\Throwable) {}
    @endphp
    <a href="{{ route($link['route']) }}"
       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
              {{ $active ? 'bg-blue-600 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/>
        </svg>
        <span data-sidebar-label>{{ $link['label'] }}</span>
    </a>
@endforeach

@php
    $topLinks = [
        [
            'route' => 'admin.dashboard',
            'label' => 'Dashboard',
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'
        ],
        [
            'label' => 'Question',
            'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'parentRoute' => 'admin.questions.index',
            'children' => [
                ['route' => 'admin.questions.index', 'label' => 'Questions'],
                ['route' => 'admin.questions.categories.index', 'label' => 'Categories'],
            ]
        ],
        [
            'label' => 'Exam',
            'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
            'parentRoute' => 'admin.exams.index',
            'children' => [
                ['route' => 'admin.exams.index', 'label' => 'Exams'],
                ['route' => 'admin.exams.index', 'label' => 'All Exams'],
            ]
        ],
        [
            'route' => 'admin.candidates.index',
            'label' => 'Candidates',
            'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'
        ],
        [
            'route' => 'admin.notifications.index',
            'label' => 'Notifications',
            'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'
        ],
    ];
@endphp

@foreach ($topLinks as $link)
    @php
        $active = false;
        $childActive = false;
        if (isset($link['route'])) {
            try {
                $active = ($link['route'] !== '#') && request()->routeIs(rtrim($link['route'], '.*') . '*');
            } catch (\Throwable) {
                $active = false;
            }
        } elseif (isset($link['children'])) {
            foreach ($link['children'] as $child) {
                try {
                    if (($child['route'] !== '#') && request()->routeIs(rtrim($child['route'], '.*') . '*')) {
                        $childActive = true;
                        break;
                    }
                } catch (\Throwable) {}
            }
        }
    @endphp

    @if (isset($link['children']))
        <div x-data="{ expanded: {{ $childActive ? 'true' : 'false' }} }" class="space-y-1">
            <button @click="if(document.getElementById('app-sidebar').offsetWidth < 150) { window.location.href = '{{ route($link['parentRoute'] ?? 'admin.dashboard') }}'; } else { expanded = !expanded; }"
                    type="button"
                    data-bs-tooltip="{{ $link['label'] }}"
                    class="sidebar-link w-full flex items-center justify-between gap-3 shrink-0 rounded-2xl px-3 py-3 text-sm font-medium transition {{ $childActive ? 'bg-slate-900 text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <div class="flex items-center gap-3 truncate">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/>
                    </svg>
                    <span class="truncate" data-sidebar-label>{{ $link['label'] }}</span>
                </div>
                <svg :class="{ 'rotate-180': expanded }" class="h-4 w-4 shrink-0 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" data-sidebar-label>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="expanded" x-transition.opacity.duration.200ms data-sidebar-label class="pl-11 pr-3 space-y-1 mt-1">
                @foreach ($link['children'] as $child)
                    @php
                        $isChildActive = false;
                        try {
                            $isChildActive = ($child['route'] !== '#') && request()->routeIs(rtrim($child['route'], '.*') . '*');
                        } catch (\Throwable) {}
                    @endphp
                    <a href="{{ $child['route'] !== '#' ? route($child['route']) : '#' }}"
                       class="block rounded-xl px-3 py-2 text-sm font-medium transition {{ $isChildActive ? 'text-white font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                        {{ $child['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @else
        <a href="{{ $link['route'] !== '#' ? route($link['route']) : '#' }}"
           data-bs-tooltip="{{ $link['label'] }}"
           class="sidebar-link flex items-center gap-3 shrink-0 rounded-2xl px-3 py-3 text-sm font-medium transition {{ $active ? 'bg-white text-slate-950 shadow-sm' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/>
            </svg>
            <span class="truncate" data-sidebar-label>{{ $link['label'] }}</span>
        </a>
    @endif
@endforeach

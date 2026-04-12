@php
$links = [
    ['route' => 'editor.dashboard',        'label' => 'Dashboard',  'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
    ['route' => 'editor.questions.index',  'label' => 'Questions',  'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
    ['route' => 'profile.edit',            'label' => 'Profile',    'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
];
@endphp

@foreach ($links as $link)
    @php
        $active = false;
        try { $active = request()->routeIs(rtrim($link['route'], '.*') . '*'); } catch (\Throwable) {}
    @endphp
    <a href="{{ route($link['route']) }}"
       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all
              {{ $active ? 'bg-emerald-600 text-white' : 'text-emerald-200 hover:bg-emerald-800 hover:text-white' }}">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/>
        </svg>
        <span data-sidebar-label>{{ $link['label'] }}</span>
    </a>
@endforeach

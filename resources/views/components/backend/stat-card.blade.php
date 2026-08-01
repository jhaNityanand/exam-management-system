@props([
    'label',
    'value',
    'href' => null,
    'icon' => '',
    'tone' => 'sky',
])

@php
    $tones = [
        'sky' => [
            'wrap' => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400',
            'blob' => 'from-sky-100 to-sky-50 dark:from-sky-900/40 dark:to-sky-900/10',
        ],
        'blue' => [
            'wrap' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400',
            'blob' => 'from-blue-100 to-blue-50 dark:from-blue-900/40 dark:to-blue-900/10',
        ],
        'indigo' => [
            'wrap' => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400',
            'blob' => 'from-indigo-100 to-indigo-50 dark:from-indigo-900/40 dark:to-indigo-900/10',
        ],
        'violet' => [
            'wrap' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400',
            'blob' => 'from-violet-100 to-violet-50 dark:from-violet-900/40 dark:to-violet-900/10',
        ],
        'fuchsia' => [
            'wrap' => 'bg-fuchsia-50 text-fuchsia-600 dark:bg-fuchsia-500/10 dark:text-fuchsia-400',
            'blob' => 'from-fuchsia-100 to-fuchsia-50 dark:from-fuchsia-900/40 dark:to-fuchsia-900/10',
        ],
        'rose' => [
            'wrap' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400',
            'blob' => 'from-rose-100 to-rose-50 dark:from-rose-900/40 dark:to-rose-900/10',
        ],
        'orange' => [
            'wrap' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400',
            'blob' => 'from-orange-100 to-orange-50 dark:from-orange-900/40 dark:to-orange-900/10',
        ],
        'amber' => [
            'wrap' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
            'blob' => 'from-amber-100 to-amber-50 dark:from-amber-900/40 dark:to-amber-900/10',
        ],
        'emerald' => [
            'wrap' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
            'blob' => 'from-emerald-100 to-emerald-50 dark:from-emerald-900/40 dark:to-emerald-900/10',
        ],
        'teal' => [
            'wrap' => 'bg-teal-50 text-teal-600 dark:bg-teal-500/10 dark:text-teal-400',
            'blob' => 'from-teal-100 to-teal-50 dark:from-teal-900/40 dark:to-teal-900/10',
        ],
        'cyan' => [
            'wrap' => 'bg-cyan-50 text-cyan-600 dark:bg-cyan-500/10 dark:text-cyan-400',
            'blob' => 'from-cyan-100 to-cyan-50 dark:from-cyan-900/40 dark:to-cyan-900/10',
        ],
        'slate' => [
            'wrap' => 'bg-slate-100 text-slate-600 dark:bg-slate-500/10 dark:text-slate-300',
            'blob' => 'from-slate-200 to-slate-50 dark:from-slate-800/60 dark:to-slate-900/10',
        ],
    ];

    $palette = $tones[$tone] ?? $tones['sky'];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class([
        'dashboard-stat-card relative overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-5 sm:p-6 shadow-sm transition group block',
        'hover:shadow-md' => (bool) $href,
    ]) }}
>
    <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-gradient-to-br {{ $palette['blob'] }} opacity-50 group-hover:scale-110 transition-transform duration-500"></div>
    <div class="relative flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm font-medium leading-snug text-slate-500 dark:text-slate-400">{{ $label }}</p>
            <p class="mt-2 text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tabular-nums">{{ $value }}</p>
        </div>
        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $palette['wrap'] }} shadow-inner">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/>
            </svg>
        </div>
    </div>
</{{ $tag }}>

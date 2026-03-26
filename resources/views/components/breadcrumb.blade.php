@props([
    'items' => [],
])

<nav aria-label="Breadcrumb" class="overflow-x-auto">
    <ol class="flex min-w-max items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
        @foreach ($items as $item)
            <li class="flex items-center gap-2">
                @if (! $loop->first)
                    <span class="text-slate-300 dark:text-slate-600">/</span>
                @endif

                @if (! empty($item['url']) && ! $loop->last)
                    <a href="{{ $item['url'] }}" class="transition hover:text-slate-900 dark:hover:text-white">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="{{ $loop->last ? 'font-semibold text-slate-900 dark:text-white' : '' }}">
                        {{ $item['label'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

<li
    class="category-tree-node hierarchy-node {{ $level > 0 ? 'hierarchy-node--nested' : '' }}"
    data-node-name="{{ strtolower($node['name']) }}"
    data-node-description="{{ strtolower($node['description'] ?? '') }}"
    style="--hierarchy-color: {{ $levelColors[$level % count($levelColors)] }}; --hierarchy-soft: {{ $levelSoftColors[$level % count($levelSoftColors)] }};"
>
    <div class="hierarchy-row">
        <div class="hierarchy-gutter" aria-hidden="true"></div>

        <div class="category-tree-item flex-1 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm transition hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-3">
                    @if(!empty($node['children']))
                        <button
                            type="button"
                            class="toggle-node-btn inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-indigo-200 bg-indigo-50 text-indigo-700 transition hover:border-indigo-300 hover:bg-indigo-100 hover:text-indigo-800 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300 dark:hover:border-indigo-500/40 dark:hover:bg-indigo-500/20 dark:hover:text-indigo-200"
                            aria-expanded="false"
                            title="Expand category"
                        >
                            <svg class="h-4 w-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    @else
                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-300 dark:border-slate-700 dark:bg-slate-800/70 dark:text-slate-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 12h14"/>
                            </svg>
                        </span>
                    @endif

                        <h3 class="truncate text-base font-medium text-slate-900 dark:text-white">{{ $node['name'] }}</h3>
                    </div>

                    <div class="mt-2 pl-11">
                        <p class="text-sm leading-6 text-slate-600 dark:text-slate-300">
                            {{ \Illuminate\Support\Str::limit($node['description'] ?: 'No description added yet.', 90) }}
                            @if(!empty($node['description']) && strlen($node['description']) > 90)
                                <button
                                    type="button"
                                    class="view-desc-btn ml-2 inline-flex items-center text-sm font-medium text-sky-600 transition hover:text-sky-700 dark:text-sky-300 dark:hover:text-sky-200"
                                    data-name="{{ $node['name'] }}"
                                    data-desc="{{ $node['description'] }}"
                                >
                                    View
                                </button>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <a
                        href="{{ route('admin.categories.edit', ['category' => $node['id']]) }}"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:border-sky-300 hover:bg-sky-100 hover:text-sky-800 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300 dark:hover:border-sky-500/40 dark:hover:bg-sky-500/20 dark:hover:text-sky-200"
                        title="Edit category"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </a>
                    <button
                        type="button"
                        class="delete-node-btn inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 transition hover:border-rose-300 hover:bg-rose-100 hover:text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300 dark:hover:border-rose-500/40 dark:hover:bg-rose-500/20 dark:hover:text-rose-200"
                        title="Delete category"
                        data-category-name="{{ $node['name'] }}"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($node['children']))
        <ul
            class="category-tree-children hierarchy-children hidden space-y-2"
            style="--branch-color: {{ $levelColors[($level + 1) % count($levelColors)] }};"
        >
            @foreach($node['children'] as $child)
                @include('backend.categories.partials.tree-node', [
                    'node' => $child,
                    'level' => $level + 1,
                    'levelColors' => $levelColors,
                    'levelSoftColors' => $levelSoftColors,
                ])
            @endforeach
        </ul>
    @endif
</li>

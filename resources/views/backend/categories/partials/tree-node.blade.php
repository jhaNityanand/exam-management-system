<li class="tree-node relative" x-data="{ open: false }">
    <div class="flex items-center group bg-slate-50 dark:bg-slate-800/20 hover:bg-slate-100 dark:hover:bg-slate-800 transition py-2 px-3 rounded-lg border border-transparent hover:border-slate-200 dark:hover:border-slate-700">
        
        {{-- Indentation spacing depending on level --}}
        <div class="shrink-0" style="width: calc({{ $level }} * clamp(12px, 2vw, 24px))"></div>
        
        {{-- Collapse/Expand Icon --}}
        <div class="w-5 sm:w-6 flex justify-center mr-1 sm:mr-2">
            @if(!empty($node['children']))
                <button type="button" class="toggle-node-btn h-5 w-5 sm:h-6 sm:w-6 flex items-center justify-center rounded text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                    <svg class="h-3 w-3 sm:h-4 sm:w-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            @endif
        </div>
        
        {{-- Content --}}
        <div class="flex-1 flex items-center gap-4 min-w-0">
            <span class="font-medium text-slate-800 dark:text-slate-200 truncate">{{ $node['name'] }}</span>
            
            <div class="flex-1 min-w-0 flex items-center gap-2">
                @if(!empty($node['description']))
                    <span class="text-sm text-slate-500 dark:text-slate-400 truncate max-w-xs">{{ $node['description'] }}</span>
                    @if(strlen($node['description']) > 40)
                        <button type="button" class="view-desc-btn text-xs text-sky-500 hover:text-sky-600 whitespace-nowrap" data-name="{{ $node['name'] }}" data-desc="{{ $node['description'] }}">View</button>
                    @endif
                @endif
            </div>
        </div>
        
        {{-- Actions --}}
        <div class="flex items-center gap-1.5 sm:gap-2 ml-auto sm:ml-4">
            <a href="{{ route('admin.categories.edit', ['category' => $node['id']]) }}" class="h-8 w-8 sm:h-9 sm:w-9 flex items-center justify-center rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-sky-100 hover:text-sky-600 dark:hover:bg-sky-900/50 dark:hover:text-sky-400 transition" title="Edit">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            </a>
            <button type="button" class="h-8 w-8 sm:h-9 sm:w-9 flex items-center justify-center rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-rose-100 hover:text-rose-600 dark:hover:bg-rose-900/50 dark:hover:text-rose-400 transition" title="Delete">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>
    </div>
    
    @if(!empty($node['children']))
        <ul class="hidden ml-5 pl-2 mt-1 border-l border-slate-200 dark:border-slate-700 space-y-1">
            @foreach($node['children'] as $child)
                @include('backend.categories.partials.tree-node', ['node' => $child, 'level' => $level + 1])
            @endforeach
        </ul>
    @endif
</li>

<li class="border-l border-gray-200 dark:border-gray-600 pl-3" style="margin-left: {{ min($depth, 6) * 0.75 }}rem">
    <div class="py-1 flex flex-wrap items-center gap-2">
        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $node->name }}</span>
        <span class="text-xs text-gray-400">{{ $node->status }}</span>
        @orgCan('category.update')
            <a href="{{ route('workspace.categories.edit', $node) }}" class="text-xs text-blue-600">Edit</a>
        @endorgCan
    </div>
    @if($node->children->isNotEmpty())
        <ul class="mt-1 space-y-1">
            @foreach ($node->children as $child)
                @include('workspace.categories.partials.tree-node', ['node' => $child, 'depth' => $depth + 1])
            @endforeach
        </ul>
    @endif
</li>

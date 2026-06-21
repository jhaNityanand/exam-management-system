{{--
    Partial: question-categories/partials/child-node.blade.php

    Renders a single child category row in the Edit page's "Child Categories" panel.

    Variables:
      $node   QuestionCategory model
      $depth  int — indentation depth (0 = direct child)
--}}

{{-- Hidden DELETE form --}}
<form id="delete-form-{{ $node->id }}"
      action="{{ route('admin.questions.categories.destroy', $node->id) }}"
      method="POST" style="display:none">
    @csrf
    @method('DELETE')
</form>

<li class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm transition hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
    style="{{ $depth > 0 ? 'margin-left: ' . ($depth * 1.5) . 'rem;' : '' }}">

    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
                  style="background: #e0e7ff; color: #4f46e5;">
                {{ $depth > 0 ? '↳' : 'L' . ($depth + 1) }}
            </span>
            <span class="font-medium text-slate-900 dark:text-white">{{ $node->name }}</span>
            <span class="qcat-status-badge qcat-status-badge--{{ $node->status }}">{{ ucfirst($node->status) }}</span>
            @if ($node->ai_generated)
                <span class="qcat-ai-badge" title="AI-generated">AI</span>
            @endif
        </div>
        @if ($node->description)
            <p class="mt-1 pl-8 text-xs text-slate-500 dark:text-slate-400 truncate">
                {{ \Illuminate\Support\Str::limit($node->description, 80) }}
            </p>
        @endif
    </div>

    <div class="flex shrink-0 items-center gap-2">
        <a href="{{ route('admin.questions.categories.edit', $node->id) }}"
           class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:bg-sky-100 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-300"
           title="Edit">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"
                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
            </svg>
        </a>
        <button type="button"
                class="child-delete-btn inline-flex h-8 w-8 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300"
                data-id="{{ $node->id }}"
                data-name="{{ $node->name }}"
                title="Delete">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </button>
    </div>
</li>

{{-- Recursive grandchildren --}}
@foreach ($node->children as $grandchild)
    @include('backend.question-categories.partials.child-node', [
        'node'  => $grandchild,
        'depth' => $depth + 1,
    ])
@endforeach

@extends($panelLayout)

@section('title', 'Category tree')
@section('page-title', 'Category tree')

@section('header-actions')
    <a href="{{ route('workspace.categories.index') }}" class="panel-button-secondary">List view</a>
@endsection

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Home', 'url' => route('dashboard')],
        ['label' => 'Categories', 'url' => route('workspace.categories.index')],
        ['label' => 'Tree'],
    ]" />
@endsection

@section('content')
    <x-page-card>
        <ul class="space-y-2 text-sm">
            @foreach ($tree as $node)
                @include('workspace.categories.partials.tree-node', ['node' => $node, 'depth' => 0])
            @endforeach
        </ul>
        @if($tree->isEmpty())
            <p class="text-sm text-slate-500 dark:text-slate-400">No categories yet.</p>
        @endif
    </x-page-card>
@endsection

@extends($panelLayout)

@section('title', 'Categories')
@section('page-title', 'Categories')

@section('header-actions')
    <div class="flex flex-wrap gap-2">
        @orgCan('category.create')
            <a href="{{ route('workspace.categories.create') }}" class="panel-button-primary">New category</a>
        @endorgCan
        <a href="{{ route('workspace.categories.tree') }}" class="panel-button-secondary">Tree view</a>
    </div>
@endsection

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Home', 'url' => route('dashboard')],
        ['label' => 'Categories'],
    ]" />
@endsection

@section('content')
    @php
        $endpoint = route('workspace.internal-api.categories-table');
        $baseUrl = url('workspace/categories');
    @endphp

    <x-page-card>
        <div class="hidden" data-categories-config data-categories-endpoint="{{ $endpoint }}" data-categories-base="{{ $baseUrl }}"></div>
        <div class="ems-toolbar">
            <input type="search" id="cat-search" class="ems-search" placeholder="Search..." autocomplete="off">
            <label class="inline-flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                <input type="checkbox" id="cat-main-only" value="1" class="rounded border-slate-300 dark:border-slate-600">
                <span>Main categories only</span>
            </label>
        </div>
        <div class="ems-table-wrap">
            <table class="ems-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Parent</th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="cat-table-body"></tbody>
            </table>
        </div>
        <div id="cat-pagination" class="mt-4"></div>
    </x-page-card>
@endsection

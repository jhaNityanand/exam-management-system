@extends('layouts.app')

@section('title', 'Questions')
@section('page-title', 'Question bank')

@section('header-actions')
    @orgCan('question.create')
        <a href="{{ route('workspace.questions.create') }}" class="panel-button-primary">New question</a>
    @endorgCan
@endsection

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Home', 'url' => route('dashboard')],
        ['label' => 'Questions'],
    ]" />
@endsection

@section('content')
    @php
        $endpoint = route('workspace.internal-api.questions-table');
        $baseUrl = rtrim(route('workspace.questions.index'), '/');
    @endphp

    <x-page-card>
        <div class="hidden" data-questions-config data-questions-endpoint="{{ $endpoint }}" data-questions-base="{{ $baseUrl }}"></div>
        <div class="ems-toolbar">
            <input type="search" id="q-search" class="ems-search" placeholder="Search question text..." autocomplete="off">
            <select name="filters[category_id]" id="q-filter-cat" class="panel-input max-w-[14rem] px-3 py-2">
                <option value="">All categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            <select id="q-sort" class="panel-input max-w-[12rem] px-3 py-2">
                <option value="id:desc">Newest</option>
                <option value="difficulty:asc">Difficulty</option>
                <option value="type:asc">Type</option>
            </select>
        </div>
        <div class="ems-table-wrap">
            <table class="ems-table">
                <thead>
                    <tr>
                        <th>Preview</th>
                        <th>Type</th>
                        <th>Difficulty</th>
                        <th>Category</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="q-table-body"></tbody>
            </table>
        </div>
        <div id="q-pagination" class="mt-4"></div>
    </x-page-card>
@endsection


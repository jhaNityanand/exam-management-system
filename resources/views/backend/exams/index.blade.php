@extends('backend.layouts.app')

@section('title', 'Exams')
@section('page-title', 'Exams')

@section('header-actions')
    @orgCan('exam.create')
        <a href="{{ route('admin.exams.create') }}" class="panel-button-primary">New exam</a>
    @endorgCan
@endsection

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Exams'],
    ]" />
@endsection

@section('content')
    @php
        $endpoint = route('admin.internal-api.exams-table');
        $baseUrl = rtrim(route('admin.exams.index'), '/');
    @endphp

    <x-page-card>
        <div class="hidden" data-exams-config data-exams-endpoint="{{ $endpoint }}" data-exams-base="{{ $baseUrl }}"></div>
        <div class="ems-toolbar">
            <input type="search" id="exam-search" class="ems-search" placeholder="Search title..." autocomplete="off">
            <select id="exam-sort" class="panel-input max-w-[12rem] px-3 py-2">
                <option value="id:desc">Newest</option>
                <option value="title:asc">Title A-Z</option>
                <option value="status:asc">Status</option>
            </select>
        </div>
        <div class="ems-table-wrap">
            <table class="ems-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Duration</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="exam-table-body"></tbody>
            </table>
        </div>
        <div id="exam-pagination" class="mt-4"></div>
    </x-page-card>
@endsection


@extends('layouts.app')

@section('title', 'Organizations')
@section('page-title', 'Organizations')

@section('header-actions')
    <a href="{{ route('admin.organizations.create') }}" class="panel-button-primary">New organization</a>
@endsection

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Home', 'url' => route('dashboard')],
        ['label' => 'Organizations'],
    ]" />
@endsection

@section('content')
    @php
        $endpoint = route('admin.internal-api.organizations-table');
        $baseUrl = url('/admin/organizations');
    @endphp

    <x-page-card>
        <div class="hidden" data-organizations-config data-organizations-endpoint="{{ $endpoint }}" data-organizations-base="{{ $baseUrl }}"></div>
        <div class="ems-toolbar">
            <input type="search" id="org-search" class="ems-search" placeholder="Search name, slug..." autocomplete="off">
            <select id="org-sort" class="panel-input max-w-[12rem] px-3 py-2">
                <option value="id:desc">Newest</option>
                <option value="name:asc">Name A-Z</option>
                <option value="status:asc">Status</option>
            </select>
        </div>
        <div class="ems-table-wrap">
            <table class="ems-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Members</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="org-table-body"></tbody>
            </table>
        </div>
        <div id="org-pagination" class="mt-4"></div>
    </x-page-card>
@endsection

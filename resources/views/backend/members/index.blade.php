@extends('backend.layouts.app')

@section('title', 'Members')
@section('page-title', 'Organization members')

@section('header-actions')
    @orgCan('member.manage')
        <button type="button" data-toggle-target="#add-member" class="panel-button-primary">Add member</button>
    @endorgCan
@endsection

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Candidates'],
    ]" />
@endsection

@section('content')
    @php
        $endpoint = route('admin.internal-api.members-table');
        $baseUrl = url('org-admin/members');
    @endphp

    <div class="space-y-6">
        <x-page-card id="add-member" class="hidden max-w-2xl">
            <h3 class="mb-4 text-base font-semibold text-slate-900 dark:text-white">Add or update member</h3>
            <form method="POST" action="{{ route('org-admin.members.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">User email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="panel-input">
                    @error('email')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Role in this organization</label>
                    <select name="role" class="panel-input">
                        @foreach (['org_admin' => 'Org admin', 'editor' => 'Editor', 'viewer' => 'Viewer'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="panel-button-primary">Save</button>
            </form>
        </x-page-card>

        <x-page-card>
            <div class="hidden" data-members-config data-members-endpoint="{{ $endpoint }}" data-members-base="{{ $baseUrl }}" data-current-user-id="{{ auth()->id() }}"></div>
            <div class="ems-toolbar">
                <input type="search" id="mem-search" class="ems-search" placeholder="Search name or email..." autocomplete="off">
            </div>
            <div class="ems-table-wrap">
                <table class="ems-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="mem-table-body"></tbody>
                </table>
            </div>
            <div id="mem-pagination" class="mt-4"></div>
        </x-page-card>
    </div>
@endsection

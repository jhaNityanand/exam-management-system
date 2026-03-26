@extends('layouts.base')

@section('body')
<div id="panel-root"
     class="flex h-screen overflow-hidden"
     data-theme="{{ $userThemeSetting ?? 'system' }}"
     data-sidebar-collapsed="{{ ($sidebarCollapsedSetting ?? false) ? '1' : '0' }}">

    <aside id="app-sidebar"
           class="flex-shrink-0 bg-violet-900 text-white flex flex-col transition-all duration-200 {{ ($sidebarCollapsedSetting ?? false) ? 'w-16' : 'w-64' }}">
        <div class="flex items-center gap-3 px-4 py-5 border-b border-violet-800">
            <div class="w-8 h-8 rounded-lg bg-violet-400 flex items-center justify-center font-bold text-sm text-violet-900 flex-shrink-0">EX</div>
            <span class="font-semibold text-lg tracking-tight truncate" data-sidebar-label>ExamMS</span>
            <span class="ml-auto text-xs bg-violet-600 text-violet-100 px-2 py-0.5 rounded-full whitespace-nowrap hidden sm:inline" data-sidebar-label>Candidate</span>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 flex flex-col">
            <div class="space-y-1 flex-1">
                @include('layouts.partials.viewer-sidebar')
            </div>
            @include('layouts.partials.org-panel-footer')
        </nav>

        <div class="px-3 py-4 border-t border-violet-800 flex items-center gap-2">
            <div class="w-9 h-9 rounded-full bg-violet-500 flex items-center justify-center text-sm font-bold flex-shrink-0">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0" data-sidebar-label>
                <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-violet-300 truncate">Candidate</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" data-sidebar-label>
                @csrf
                <button type="submit" title="Logout" class="text-violet-300 hover:text-white transition-colors p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-4 sm:px-6 py-3 flex items-center justify-between flex-shrink-0 gap-3">
            <div class="min-w-0">
                <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-100 truncate">@yield('page-title', 'My Dashboard')</h1>
                @hasSection('breadcrumbs')
                    <nav class="text-xs text-gray-500 mt-0.5 truncate">@yield('breadcrumbs')</nav>
                @endif
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                @include('layouts.partials.panel-topbar')
                @yield('header-actions')
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6">
            @yield('content')
        </main>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/core/layout.js') }}"></script>
@endpush
@endsection

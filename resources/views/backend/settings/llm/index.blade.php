@extends('backend.layouts.app')

@section('title', 'LLM Management')
@section('page-title', 'LLM Management')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Settings', 'url' => route('admin.settings.index')],
        ['label' => 'LLM Management'],
    ]" />
@endsection

@section('content')
<div class="space-y-6" x-data="{ activeTab: 'accounts' }">

    <!-- Top Stats Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 p-3.5 shadow-xs">
            <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 dark:text-slate-500">Providers</p>
            <p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $stats['total_providers'] }}</p>
            <span class="text-[11px] text-slate-500 dark:text-slate-400 truncate block">Mistral, Groq, Gemini, OpenRouter</span>
        </div>

        <div class="rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200/60 dark:border-emerald-500/20 p-3.5 shadow-xs">
            <p class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Active Accounts</p>
            <p class="mt-1 text-2xl font-black text-emerald-900 dark:text-emerald-300">{{ $stats['active_accounts'] }}</p>
            <span class="text-[11px] text-emerald-600/80 dark:text-emerald-400/80 block">Ready for dispatch</span>
        </div>

        <div class="rounded-2xl bg-amber-50/50 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-500/20 p-3.5 shadow-xs">
            <p class="text-[10px] font-extrabold uppercase tracking-wider text-amber-600 dark:text-amber-400">In Cooldown</p>
            <p class="mt-1 text-2xl font-black text-amber-900 dark:text-amber-300">{{ $stats['cooldown_accounts'] }}</p>
            <span class="text-[11px] text-amber-600/80 dark:text-amber-400/80 block">Auto 24h pause</span>
        </div>

        <div class="rounded-2xl bg-rose-50/50 dark:bg-rose-950/20 border border-rose-200/60 dark:border-rose-500/20 p-3.5 shadow-xs">
            <p class="text-[10px] font-extrabold uppercase tracking-wider text-rose-600 dark:text-rose-400">Failed / Errors</p>
            <p class="mt-1 text-2xl font-black text-rose-900 dark:text-rose-300">{{ $stats['failed_accounts'] }}</p>
            <span class="text-[11px] text-rose-600/80 dark:text-rose-400/80 block">Requires attention</span>
        </div>

        <div class="rounded-2xl bg-sky-50/50 dark:bg-sky-950/20 border border-sky-200/60 dark:border-sky-500/20 p-3.5 shadow-xs">
            <p class="text-[10px] font-extrabold uppercase tracking-wider text-sky-600 dark:text-sky-400">Today's Requests</p>
            <p class="mt-1 text-2xl font-black text-sky-900 dark:text-sky-300">{{ number_format($stats['requests_today']) }}</p>
            <span class="text-[11px] text-sky-600/80 dark:text-sky-400/80 block">Across all providers</span>
        </div>

        <div class="rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-200/60 dark:border-indigo-500/20 p-3.5 shadow-xs">
            <p class="text-[10px] font-extrabold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Today's Tokens</p>
            <p class="mt-1 text-2xl font-black text-indigo-900 dark:text-indigo-300">{{ number_format($stats['tokens_today']) }}</p>
            <span class="text-[11px] text-indigo-600/80 dark:text-indigo-400/80 block">API tokens consumed</span>
        </div>
    </div>

    <!-- Main Navigation & Header Toolbar -->
    <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-3">
        <!-- Tabs -->
        <nav class="flex space-x-1 sm:space-x-2 overflow-x-auto" aria-label="Tabs">
            <button @click="activeTab = 'accounts'"
                    :class="activeTab === 'accounts' 
                        ? 'bg-indigo-600 text-white shadow-sm font-semibold' 
                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800'"
                    class="rounded-xl px-4 py-2 text-sm transition flex items-center gap-2 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span>LLM Accounts</span>
                <span class="ml-1 rounded-full px-2 py-0.5 text-xs font-bold"
                      :class="activeTab === 'accounts' ? 'bg-indigo-500/40 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'">
                    {{ $stats['total_accounts'] }}
                </span>
            </button>

            <button @click="activeTab = 'seo_logs'"
                    :class="activeTab === 'seo_logs' 
                        ? 'bg-indigo-600 text-white shadow-sm font-semibold' 
                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800'"
                    class="rounded-xl px-4 py-2 text-sm transition flex items-center gap-2 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>SEO Logs</span>
            </button>

            <button @click="activeTab = 'sitemap_logs'"
                    :class="activeTab === 'sitemap_logs' 
                        ? 'bg-indigo-600 text-white shadow-sm font-semibold' 
                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800'"
                    class="rounded-xl px-4 py-2 text-sm transition flex items-center gap-2 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                </svg>
                <span>Sitemap Logs</span>
            </button>

            <button @click="activeTab = 'error_logs'"
                    :class="activeTab === 'error_logs' 
                        ? 'bg-indigo-600 text-white shadow-sm font-semibold' 
                        : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800'"
                    class="rounded-xl px-4 py-2 text-sm transition flex items-center gap-2 whitespace-nowrap">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Error History</span>
            </button>
        </nav>

        <button type="button" onclick="openAddAccountModal()" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 text-sm font-semibold shadow-sm transition shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Add Provider Account</span>
        </button>
    </div>

    <!-- TAB 1: ACCOUNTS LIST -->
    <div x-show="activeTab === 'accounts'" class="space-y-4">
        <!-- Minimal Borderless Filter Toolbar -->
        <form method="GET" action="{{ route('admin.settings.llm.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search by account name or model..." class="panel-input border-0 bg-slate-100 dark:bg-slate-800/80 pl-9 pr-3.5 py-2 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 rounded-xl" />
                <svg class="w-4 h-4 absolute left-3 top-2.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <div class="w-full sm:w-48">
                <select name="provider" class="panel-input border-0 bg-slate-100 dark:bg-slate-800/80 text-sm text-slate-900 dark:text-white rounded-xl">
                    <option value="">All Providers</option>
                    @foreach($supportedProviders as $key => $name)
                        <option value="{{ $key }}" {{ $filters['provider'] === $key ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-44">
                <select name="status" class="panel-input border-0 bg-slate-100 dark:bg-slate-800/80 text-sm text-slate-900 dark:text-white rounded-xl">
                    <option value="">All Statuses</option>
                    <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="cooldown" {{ $filters['status'] === 'cooldown' ? 'selected' : '' }}>In Cooldown</option>
                    <option value="paused" {{ $filters['status'] === 'paused' ? 'selected' : '' }}>Limit Reached</option>
                    <option value="disabled" {{ $filters['status'] === 'disabled' ? 'selected' : '' }}>Disabled</option>
                    <option value="error" {{ $filters['status'] === 'error' ? 'selected' : '' }}>Error</option>
                </select>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button type="submit" class="rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-indigo-600 dark:hover:bg-indigo-500 text-white py-2 px-4 text-sm font-semibold shadow-sm transition">Filter</button>
                <a href="{{ route('admin.settings.llm.index') }}" class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-3 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">Reset</a>
            </div>
        </form>

        <!-- Table Card -->
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/50 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-4 py-3.5">Priority</th>
                            <th class="px-4 py-3.5">Provider</th>
                            <th class="px-4 py-3.5">Account &amp; Model</th>
                            <th class="px-4 py-3.5">API Key</th>
                            <th class="px-4 py-3.5">Usage Today</th>
                            <th class="px-4 py-3.5">Status</th>
                            <th class="px-4 py-3.5">Last Used</th>
                            <th class="px-4 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-900">
                        @forelse($accounts as $account)
                            @php
                                $badge = $account->statusBadge();
                                $providerBadges = [
                                    'mistral' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
                                    'groq' => 'bg-purple-500/10 text-purple-600 dark:text-purple-400',
                                    'gemini' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
                                    'openrouter' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
                                ];
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                                <td class="px-4 py-3.5 font-bold text-slate-900 dark:text-white">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 border border-slate-200/80 dark:border-slate-700">
                                        #{{ $account->priority }}
                                    </span>
                                </td>

                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold {{ $providerBadges[$account->provider] ?? 'bg-slate-100 text-slate-700' }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        {{ $supportedProviders[$account->provider] ?? ucfirst($account->provider) }}
                                    </span>
                                </td>

                                <td class="px-4 py-3.5">
                                    <div class="font-bold text-slate-900 dark:text-white text-sm">{{ $account->account_name }}</div>
                                    <div class="text-xs font-mono text-indigo-600 dark:text-indigo-400 mt-0.5">{{ $account->model }}</div>
                                </td>

                                <td class="px-4 py-3.5 font-mono text-xs text-slate-500 dark:text-slate-400">
                                    {{ $account->masked_api_key }}
                                </td>

                                <td class="px-4 py-3.5 text-xs">
                                    <div><span class="font-bold text-slate-800 dark:text-slate-200">{{ number_format($account->requests_today) }}</span> reqs @if($account->daily_request_limit) <span class="text-slate-400">/ {{ number_format($account->daily_request_limit) }}</span> @endif</div>
                                    <div class="text-slate-400 mt-0.5"><span class="font-bold text-slate-700 dark:text-slate-300">{{ number_format($account->tokens_today) }}</span> tokens @if($account->daily_token_limit) <span class="text-slate-400">/ {{ number_format($account->daily_token_limit) }}</span> @endif</div>
                                </td>

                                <td class="px-4 py-3.5">
                                    @if($badge === 'active')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Active
                                        </span>
                                    @elseif($badge === 'cooldown')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400" title="Cooldown until {{ $account->cooldown_until?->format('Y-m-d H:i') }}">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Cooldown
                                        </span>
                                    @elseif($badge === 'paused')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-sky-500/10 text-sky-600 dark:text-sky-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span> Limit Reached
                                        </span>
                                    @elseif($badge === 'disabled')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-500/10 text-slate-500 dark:text-slate-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Disabled
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500/10 text-rose-600 dark:text-rose-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Error
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3.5 text-xs text-slate-500 dark:text-slate-400">
                                    {{ $account->last_used_at ? $account->last_used_at->diffForHumans() : 'Never' }}
                                </td>

                                <td class="px-4 py-3.5 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" onclick="testAccountConnection({{ $account->id }})" title="Test API Connection" class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 dark:text-slate-400 dark:hover:text-indigo-400 dark:hover:bg-indigo-950/50 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                            </svg>
                                        </button>

                                        @if($account->isInCooldown() || $account->error_count > 0)
                                            <button type="button" onclick="resetAccountCooldown({{ $account->id }})" title="Reset Cooldown" class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-950/50 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                            </button>
                                        @endif

                                        <button type="button" onclick="editAccount({{ json_encode($account) }})" title="Edit Account" class="p-1.5 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-white dark:hover:bg-slate-800 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>

                                        <button type="button" onclick="deleteAccount({{ $account->id }}, '{{ addslashes($account->account_name) }}')" title="Delete Account" class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10 text-center text-slate-500 dark:text-slate-400">
                                    No LLM accounts configured yet. Click <strong>Add Provider Account</strong> above.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($accounts->hasPages())
                <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-800">
                    {{ $accounts->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- TAB 2: SEO LOGS -->
    <div x-show="activeTab === 'seo_logs'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-xs">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800">
                <h3 class="font-bold text-slate-900 dark:text-white text-base">SEO Queue Execution Logs</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/50 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-4 py-3.5">Run Time</th>
                            <th class="px-4 py-3.5">Content Type</th>
                            <th class="px-4 py-3.5">Processed / Successful / Failed</th>
                            <th class="px-4 py-3.5">Provider &amp; Account</th>
                            <th class="px-4 py-3.5">Execution Time</th>
                            <th class="px-4 py-3.5">Processed IDs</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-900">
                        @forelse($seoLogs as $log)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3.5 text-xs font-medium text-slate-900 dark:text-white">
                                    {{ $log->run_at?->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-4 py-3.5 text-xs font-mono font-bold uppercase text-indigo-600 dark:text-indigo-400">
                                    {{ $log->seo_type }}
                                </td>
                                <td class="px-4 py-3.5 text-xs">
                                    <span class="font-bold text-slate-900 dark:text-white">{{ $log->processed_records_count }}</span> total
                                    (<span class="text-emerald-600 dark:text-emerald-400 font-bold">{{ $log->successful_count }} ok</span>,
                                    <span class="text-rose-600 dark:text-rose-400 font-bold">{{ $log->failed_count }} fail</span>)
                                </td>
                                <td class="px-4 py-3.5 text-xs">
                                    <span class="font-bold text-slate-900 dark:text-white">{{ ucfirst($log->provider_used) }}</span>
                                    <span class="text-slate-400">({{ $log->account_used }})</span>
                                </td>
                                <td class="px-4 py-3.5 text-xs font-mono">
                                    {{ number_format($log->execution_time_ms, 2) }} ms
                                </td>
                                <td class="px-4 py-3.5 text-xs font-mono">
                                    <button type="button" onclick="showRecordIdsModal('{{ json_encode($log->processed_record_ids) }}')" class="text-indigo-600 dark:text-indigo-400 hover:underline font-bold">
                                        View [{{ count($log->processed_record_ids ?? []) }} IDs]
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">No SEO queue runs logged yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($seoLogs->hasPages())
                <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-800">
                    {{ $seoLogs->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- TAB 3: SITEMAP LOGS -->
    <div x-show="activeTab === 'sitemap_logs'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-xs">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800">
                <h3 class="font-bold text-slate-900 dark:text-white text-base">Sitemap Generation Logs</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/50 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-4 py-3.5">Run Date</th>
                            <th class="px-4 py-3.5">Records Processed</th>
                            <th class="px-4 py-3.5">URLs Generated</th>
                            <th class="px-4 py-3.5">Processing Time</th>
                            <th class="px-4 py-3.5">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-900">
                        @forelse($sitemapLogs as $log)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3.5 text-xs font-medium text-slate-900 dark:text-white">
                                    {{ $log->run_at?->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-4 py-3.5 text-xs font-bold">{{ number_format($log->total_records_processed) }}</td>
                                <td class="px-4 py-3.5 text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($log->total_urls_generated) }}</td>
                                <td class="px-4 py-3.5 text-xs font-mono">{{ number_format($log->processing_time_ms, 2) }} ms</td>
                                <td class="px-4 py-3.5 text-xs">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                        {{ strtoupper($log->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">No sitemap generation runs logged yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($sitemapLogs->hasPages())
                <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-800">
                    {{ $sitemapLogs->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- TAB 4: ERROR LOGS -->
    <div x-show="activeTab === 'error_logs'" class="space-y-4">
        <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden shadow-xs">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800">
                <h3 class="font-bold text-slate-900 dark:text-white text-base">LLM API Error History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/50 text-[11px] font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                        <tr>
                            <th class="px-4 py-3.5">Failed At</th>
                            <th class="px-4 py-3.5">Provider / Account</th>
                            <th class="px-4 py-3.5">Model</th>
                            <th class="px-4 py-3.5">Request Type</th>
                            <th class="px-4 py-3.5">Error Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-slate-900">
                        @forelse($errorLogs as $err)
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-3.5 text-xs font-medium text-slate-900 dark:text-white whitespace-nowrap">
                                    {{ $err->failed_at?->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-4 py-3.5 text-xs">
                                    <span class="font-bold uppercase text-slate-800 dark:text-slate-200">{{ $err->provider }}</span>
                                    <span class="block text-slate-400">{{ $err->account_name ?: 'Default' }}</span>
                                </td>
                                <td class="px-4 py-3.5 text-xs font-mono text-slate-500 dark:text-slate-400">{{ $err->model }}</td>
                                <td class="px-4 py-3.5 text-xs font-semibold text-slate-700 dark:text-slate-300">{{ $err->request_type }}</td>
                                <td class="px-4 py-3.5 text-xs text-rose-600 dark:text-rose-400 max-w-md truncate" title="{{ $err->error_message }}">
                                    {{ $err->error_message }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">No error logs recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($errorLogs->hasPages())
                <div class="px-4 py-3 border-t border-slate-200 dark:border-slate-800">
                    {{ $errorLogs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@include('backend.settings.llm.partials.modals')
@endsection

@push('scripts')
<script src="{{ asset('assets/js/llm-management.js') }}"></script>
@endpush

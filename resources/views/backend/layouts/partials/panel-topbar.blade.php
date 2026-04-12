@php
    $user = auth()->user();
    $userName = $user->name ?? 'User';
    $nameParts = explode(' ', trim($userName));
    if (count($nameParts) >= 2) {
        $userInitials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
    } else {
        $userInitials = strtoupper(substr($userName, 0, 2));
    }
@endphp

<div class="flex items-center gap-3">
    <button id="theme-toggle-btn" type="button"
        class="inline-flex h-9 w-9 sm:h-11 sm:w-11 items-center justify-center rounded-xl sm:rounded-2xl border border-slate-200 text-slate-600 transition hover:border-slate-300 hover:text-slate-950 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:text-white">
        <!-- Sun icon (shows in dark mode) -->
        <svg class="hidden h-5 w-5 dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 3v1m0 16v1m8.66-10h-1M4.34 12h-1m15.02 6.36l-.7-.7M6.34 6.34l-.7-.7m12.72 0l-.7.7M6.34 17.66l-.7.7M12 7a5 5 0 100 10 5 5 0 000-10z" />
        </svg>
        <!-- Moon icon (shows in light mode) -->
        <svg class="block h-5 w-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M20.354 15.354A9 9 0 018.646 3.646 9 9 0 1012 21a8.96 8.96 0 008.354-5.646z" />
        </svg>
    </button>

    <div data-dropdown data-open="0" class="relative">
        <button type="button" data-dropdown-trigger
            class="relative inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-slate-200 text-slate-600 transition hover:border-slate-300 hover:text-slate-950 dark:border-slate-700 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:text-white">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span class="absolute top-2.5 right-2.5 flex h-2.5 w-2.5">
                <span
                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-rose-500"></span>
            </span>
        </button>

        <div data-dropdown-menu
            class="absolute right-0 mt-3 hidden rounded-2xl border border-slate-200 bg-white p-3 shadow-xl shadow-slate-200/70 dark:border-slate-700 dark:bg-slate-900 dark:shadow-none"
            style="width: 320px; z-index: 50;">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Notifications</h3>
                <span
                    class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">5
                    New</span>
            </div>
            <div class="mt-2 flex flex-col gap-1 max-h-64 overflow-y-auto">
                <div class="rounded-xl p-2 transition hover:bg-slate-50 dark:hover:bg-slate-800/50">
                    <p class="text-sm font-medium text-slate-900 dark:text-white">System Update</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">The system has been correctly updated to
                        version 1.2.</p>
                    <p class="mt-1 text-[10px] text-slate-400">10 mins ago</p>
                </div>
                <div class="rounded-xl p-2 transition hover:bg-slate-50 dark:hover:bg-slate-800/50">
                    <p class="text-sm font-medium text-slate-900 dark:text-white">New User Registered</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">John Doe has created an account.</p>
                    <p class="mt-1 text-[10px] text-slate-400">1 hour ago</p>
                </div>
                <div class="rounded-xl p-2 transition hover:bg-slate-50 dark:hover:bg-slate-800/50">
                    <p class="text-sm font-medium text-slate-900 dark:text-white">Database Backup</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Automated backup completed successfully.</p>
                    <p class="mt-1 text-[10px] text-slate-400">5 hours ago</p>
                </div>
            </div>
            <a href="#"
                class="mt-2 block rounded-xl bg-slate-50 px-3 py-2 text-center text-xs font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-white">View
                All Notifications</a>
        </div>
    </div>

    <div data-dropdown data-open="0" class="relative">
        <button type="button" data-dropdown-trigger
            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white p-1 transition hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-500">
            <span
                class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-950 text-sm font-bold uppercase text-white dark:bg-white dark:text-slate-950">
                {{ $userInitials }}
            </span>
        </button>

        <div data-dropdown-menu
            class="absolute right-0 mt-3 hidden w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-200/70 dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
            <a href="{{ route('admin.profile.edit') }}"
                class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-indigo-600 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-indigo-400 rounded-lg">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>My Profile</span>
            </a>

            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                @csrf
                <button type="submit"
                    class="flex w-full items-center gap-3 rounded-2xl px-3 py-2 text-left text-sm text-rose-600 transition hover:bg-rose-50 hover:text-rose-700 dark:text-rose-400 dark:hover:bg-slate-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h6a2 2 0 012 2v1" />
                    </svg>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>
    </div>
</div>
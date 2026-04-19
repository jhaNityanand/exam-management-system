@php
    $user = auth()->user();
    $userName = $user->name ?? 'User';
    $notificationCount = 5;
    $notificationBadge = $notificationCount > 99 ? '99+' : (string) $notificationCount;
    $topbarIconButtonClasses = 'relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:text-slate-950 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 dark:hover:border-slate-500 dark:hover:text-white';
    $topbarIconShellClasses = 'pointer-events-none flex h-8 w-8 items-center justify-center rounded-lg text-current';
    $nameParts = explode(' ', trim($userName));
    if (count($nameParts) >= 2) {
        $userInitials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
    } else {
        $userInitials = strtoupper(substr($userName, 0, 2));
    }
@endphp

<div class="flex items-center gap-2 sm:gap-3">
    <!-- Theme Toggle -->
    <button id="theme-toggle-btn" type="button"
        class="{{ $topbarIconButtonClasses }} overflow-visible p-0.5"
        aria-label="Toggle theme">
        <!-- Sun icon (shows in dark mode) -->
        <span class="{{ $topbarIconShellClasses }} hidden dark:flex">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] overflow-visible" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
            </svg>
        </span>
        <!-- Moon icon (shows in light mode) -->
        <span class="{{ $topbarIconShellClasses }} dark:hidden">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px] overflow-visible" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
            </svg>
        </span>
    </button>

    <!-- Notifications -->
    <div data-dropdown data-open="0" class="relative">
        <button type="button" data-dropdown-trigger
            class="{{ $topbarIconButtonClasses }} overflow-visible"
            aria-label="Open notifications">

            <span class="{{ $topbarIconShellClasses }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <path d="M12 2.25a4.5 4.5 0 0 0-4.5 4.5v.794a5.25 5.25 0 0 1-1.537 3.712l-.815.815a2.25 2.25 0 0 0 1.591 3.84h10.522a2.25 2.25 0 0 0 1.591-3.84l-.815-.815A5.25 5.25 0 0 1 16.5 7.544V6.75A4.5 4.5 0 0 0 12 2.25Zm0 19.5a3 3 0 0 0 2.815-1.965.75.75 0 0 0-.703-1.035H9.888a.75.75 0 0 0-.703 1.035A3 3 0 0 0 12 21.75Z" />
                </svg>
            </span>

            <span class="pointer-events-none absolute bottom-0 right-0 inline-flex min-h-[1.15rem] min-w-[1.15rem] translate-x-1/4 translate-y-1/4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold leading-none text-white ring-2 ring-white shadow-sm dark:ring-slate-950">
                {{ $notificationBadge }}
            </span>
        </button>

        <div data-dropdown-menu
            class="absolute right-0 mt-3 hidden rounded-2xl border border-slate-200 bg-white p-3 shadow-xl shadow-slate-200/70 dark:border-slate-700 dark:bg-slate-900 dark:shadow-none"
            style="width: 320px; z-index: 50;">
            <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Notifications</h3>
                <span
                    class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $notificationBadge }}
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
            class="{{ $topbarIconButtonClasses }} p-1"
            aria-label="User menu">
            <span
                class="flex h-full w-full items-center justify-center rounded-lg bg-slate-950 text-sm font-bold uppercase text-white dark:bg-white dark:text-slate-950">
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

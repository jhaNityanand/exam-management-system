<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Exam Management System') }} - Public</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 min-h-screen flex items-center justify-center">
    <div class="text-center space-y-6 max-w-lg p-6">
        <div class="mb-4 inline-flex h-20 w-20 items-center justify-center rounded-full bg-slate-200 dark:bg-slate-800">
            <svg class="h-10 w-10 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/></svg>
        </div>
        <h1 class="text-4xl font-extrabold tracking-tight">Welcome to {{ config('app.name') }}</h1>
        <p class="text-lg text-slate-500 dark:text-slate-400">
            This is the public frontend of the exam portal. Candidates will interact here.
        </p>
        <div class="flex justify-center gap-4 pt-4">
            @auth
                <a href="{{ route('admin.dashboard') }}" class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">Go to Dashboard</a>
            @else
                <a href="{{ route('login') }}" class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">Log in</a>
            @endauth
        </div>
    </div>
</body>
</html>

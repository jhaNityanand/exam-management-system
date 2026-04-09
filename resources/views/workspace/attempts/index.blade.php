@extends('layouts.app')

@section('title', 'My results')
@section('page-title', 'My exam attempts')

@section('content')
<div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 dark:bg-gray-700/50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Result</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">When</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse ($attempts as $attempt)
                <tr>
                    <td class="px-4 py-3">{{ $attempt->exam?->title ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $attempt->score ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if($attempt->passed === true)
                            <span class="text-green-600 text-xs font-medium">Passed</span>
                        @elseif($attempt->passed === false)
                            <span class="text-red-600 text-xs font-medium">Failed</span>
                        @else
                            <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $attempt->created_at->diffForHumans() }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No attempts yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">{{ $attempts->links() }}</div>
</div>
@endsection

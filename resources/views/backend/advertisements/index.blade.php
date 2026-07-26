@extends('backend.layouts.app')

@section('title', 'Advertisements')
@section('page-title', 'Advertisements')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Settings', 'url' => route('admin.settings.index')],
        ['label' => 'Advertisements'],
    ]" />
@endsection

@section('content')
<div class="space-y-6">
    <x-page-card>
        <div class="px-4 py-5 sm:p-6 space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-end gap-4 justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-slate-900 dark:text-white">Advertisement management</h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Manage Google Ads, custom HTML, iframes, and image banners with visual placement slots.
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                    <a href="{{ route('admin.advertisements.create') }}" class="panel-button-primary text-sm whitespace-nowrap self-start sm:self-auto">Create advertisement</a>
                <form method="get" class="flex flex-wrap gap-2 items-end">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Search</label>
                        <input type="search" name="search" value="{{ $filters['search'] }}" class="panel-input text-sm" placeholder="Name…">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Type</label>
                        <select name="type" class="panel-input text-sm">
                            <option value="">All</option>
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}" @selected($filters['type'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                        <select name="status" class="panel-input text-sm">
                            <option value="">All</option>
                            @foreach(['active','inactive','draft'] as $st)
                                <option value="{{ $st }}" @selected($filters['status'] === $st)>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="panel-button-secondary text-sm">Filter</button>
                </form>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-900/60 text-left text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Name</th>
                            <th class="px-4 py-3 font-medium">Type</th>
                            <th class="px-4 py-3 font-medium">Placement</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Order</th>
                            <th class="px-4 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($ads as $ad)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-white">{{ $ad->name }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $ad->typeLabel() }}</td>
                                <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ $ad->placementLabel() }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                                        {{ $ad->status === 'active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                        {{ ucfirst($ad->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $ad->sort_order }}</td>
                                <td class="px-4 py-3 text-right space-x-2">
                                    <a href="{{ route('admin.advertisements.edit', $ad) }}" class="text-indigo-600 dark:text-indigo-400 font-medium hover:underline">Edit</a>
                                    <form action="{{ route('admin.advertisements.destroy', $ad) }}" method="POST" class="inline" onsubmit="return confirm('Delete this advertisement?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 dark:text-red-400 font-medium hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">No advertisements yet. Create your first ad.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-page-card>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <x-page-card>
            <div class="px-4 py-5 sm:p-6 space-y-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">Placement settings</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Configure how often inline ads appear in the question list.</p>
                </div>
                <form id="ad-settings-form" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label for="question_list_every_n" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Insert ad every N questions
                        </label>
                        <input type="number" id="question_list_every_n" name="question_list_every_n" min="0" max="50"
                               value="{{ $questionEveryN }}" class="panel-input w-32">
                        <p class="mt-1 text-xs text-slate-500">Use 0 to disable inline question ads. Example: 2 = Q, Q, Ad, Q, Q, Ad…</p>
                    </div>
                    <button type="submit" class="panel-button-primary text-sm">Save</button>
                </form>
            </div>
        </x-page-card>

        <x-page-card>
            <div class="px-4 py-5 sm:p-6 space-y-3">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">Visual placement map</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Where ads can appear on each frontend surface.</p>
                <div class="space-y-4 max-h-[28rem] overflow-y-auto pr-1">
                    @foreach($placementGroups as $groupKey => $group)
                        <div class="rounded-xl border border-slate-200 dark:border-slate-700 p-3">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $group['label'] }}</p>
                            <p class="text-xs text-slate-500 mb-2">{{ $group['page'] }}</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach($group['slots'] as $slotKey => $slotLabel)
                                    @php $count = $ads->where('placement', $slotKey)->count(); @endphp
                                    <div class="rounded-lg border border-dashed px-2.5 py-2 text-xs
                                        {{ $count ? 'border-indigo-300 bg-indigo-50 text-indigo-800 dark:border-indigo-500/40 dark:bg-indigo-500/10 dark:text-indigo-200' : 'border-slate-200 text-slate-500 dark:border-slate-700 dark:text-slate-400' }}">
                                        <span class="font-medium">{{ $slotLabel }}</span>
                                        <span class="block opacity-80">{{ $count ? $count.' assigned' : 'Empty' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-page-card>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('ad-settings-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const n = Number(document.getElementById('question_list_every_n')?.value || 0);
            try {
                const res = await fetch(@json(route('admin.advertisements.settings')), {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': @json(csrf_token()),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ question_list_every_n: n }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Save failed');
                window.EmsToast?.success?.(data.message || 'Saved');
                window.Swal?.fire?.({ icon: 'success', title: 'Saved', text: data.message, timer: 1600, showConfirmButton: false });
            } catch (err) {
                window.Swal?.fire?.({ icon: 'error', title: 'Save failed', text: err.message });
            }
        });
    </script>
@endpush

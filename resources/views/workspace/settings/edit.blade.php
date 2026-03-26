@extends($panelLayout)

@section('title', 'Settings')
@section('page-title', 'Application settings')

@section('content')
<form method="POST" action="{{ route('workspace.settings.update') }}" class="max-w-xl space-y-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-6">
    @csrf
    @method('PUT')
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Theme</label>
        <select name="theme" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
            @foreach (['light' => 'Light', 'dark' => 'Dark', 'system' => 'System'] as $v => $l)
                <option value="{{ $v }}" @selected(old('theme', $settings->theme) === $v)>{{ $l }}</option>
            @endforeach
        </select>
        @error('theme')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="sidebar_collapsed" value="1" @checked(old('sidebar_collapsed', $settings->sidebar_collapsed)) class="rounded border-gray-300">
            Collapse sidebar by default
        </label>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Default organization</label>
        <select name="default_organization_id" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
            <option value="">— None —</option>
            @foreach ($organizations as $o)
                <option value="{{ $o->id }}" @selected(old('default_organization_id', $profile->default_organization_id) == $o->id)>{{ $o->name }}</option>
            @endforeach
        </select>
        @error('default_organization_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-indigo-700">Save settings</button>
</form>
@endsection

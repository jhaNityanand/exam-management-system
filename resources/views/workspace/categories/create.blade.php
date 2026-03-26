@extends($panelLayout)

@section('title', 'New category')
@section('page-title', 'Create category')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Home', 'url' => route('dashboard')],
        ['label' => 'Categories', 'url' => route('workspace.categories.index')],
        ['label' => 'Create'],
    ]" />
@endsection

@section('content')
    <x-page-card class="max-w-3xl">
        <form action="{{ route('workspace.categories.store') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" class="panel-input">
                @error('name')<p class="mt-1 text-xs text-rose-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Parent</label>
                <select name="parent_id" class="panel-input">
                    <option value="">- Main category -</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id') == $parent->id)>{{ $parent->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Description</label>
                <textarea id="cat-description" name="description" rows="4" class="panel-input" data-rich-text data-editor-height="200" data-editor-toolbar="undo redo | bold italic | bullist numlist">{{ old('description') }}</textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                <select name="status" class="panel-input">
                    @foreach (['active', 'inactive', 'suspended'] as $status)
                        <option value="{{ $status }}" @selected(old('status', 'active') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="panel-button-primary">Save</button>
                <a href="{{ route('workspace.categories.index') }}" class="panel-button-secondary">Cancel</a>
            </div>
        </form>
    </x-page-card>
@endsection

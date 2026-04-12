@extends('backend.layouts.app')

@section('title', 'Edit organization')
@section('page-title', 'Edit: '.$organization->name)

@section('content')
<form action="{{ route('admin.organizations.update', $organization) }}" method="POST" enctype="multipart/form-data" class="max-w-2xl space-y-5">
    @csrf
    @method('PUT')
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
        <input type="text" name="name" value="{{ old('name', $organization->name) }}"
               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
        @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label>
        <input type="text" name="slug" value="{{ old('slug', $organization->slug) }}"
               class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
        @error('slug')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
        <textarea name="description" rows="4" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">{{ old('description', $organization->description) }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
        <select name="status" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-3 py-2 text-sm">
            @foreach (['active', 'inactive', 'suspended'] as $v)
                <option value="{{ $v }}" @selected(old('status', $organization->status) === $v)>{{ ucfirst($v) }}</option>
            @endforeach
        </select>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Logo</label>
            @if($organization->logo)
                <p class="text-xs text-gray-500 mb-1">Current: {{ $organization->logo }}</p>
            @endif
            <input type="file" name="logo" accept="image/*" class="text-sm w-full">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Banner</label>
            <input type="file" name="banner" accept="image/*" class="text-sm w-full">
        </div>
    </div>
    <div class="flex gap-3">
        <button type="submit" class="bg-indigo-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg hover:bg-indigo-700">Update</button>
        <a href="{{ route('admin.organizations.index') }}" class="text-sm text-gray-500 py-2.5">Back</a>
    </div>
</form>
@endsection

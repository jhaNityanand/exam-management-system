@extends('backend.layouts.app')

@section('title', 'Categories')
@section('page-title', 'Category List')
@section('content-container-class', 'max-w-none')

@section('breadcrumbs')
    <x-breadcrumb :items="[
        ['label' => 'Admin', 'url' => route('admin.dashboard')],
        ['label' => 'Categories'],
    ]" />
@endsection

@section('content')
@php
    $levelColors = ['#4f46e5', '#0f766e', '#d97706', '#dc2626', '#7c3aed', '#2563eb'];
    $levelSoftColors = ['#e0e7ff', '#dff6f3', '#ffedd5', '#ffe4e6', '#ede9fe', '#dbeafe'];

    $categories = [
        [
            'id' => 1,
            'name' => 'Science',
            'description' => 'All science-related topics including physics, chemistry, biology, and interdisciplinary research branches.',
            'children' => [
                [
                    'id' => 2,
                    'name' => 'Physics',
                    'description' => 'Mechanics, optics, thermodynamics, and modern physics modules.',
                    'children' => [
                        [
                            'id' => 3,
                            'name' => 'Classical Mechanics',
                            'description' => 'Motion, force, energy, and Newtonian systems.',
                            'children' => [],
                        ],
                        [
                            'id' => 4,
                            'name' => 'Quantum Physics',
                            'description' => 'Wave functions, uncertainty, quantum states, and atomic models.',
                            'children' => [
                                [
                                    'id' => 5,
                                    'name' => 'Quantum Computing Basics',
                                    'description' => 'Qubits, gates, measurement, and simple quantum circuit concepts.',
                                    'children' => [
                                        [
                                            'id' => 6,
                                            'name' => 'Quantum Computing Basics - Part 2',
                                            'description' => 'Qubits, gates, measurement, and simple quantum circuit concepts.',
                                            'children' => [
                                                [
                                                    'id' => 7,
                                                    'name' => 'Quantum Computing Basics - Part 3',
                                                    'description' => 'Qubits, gates, measurement, and simple quantum circuit concepts.',
                                                    'children' => [
                                                        [
                                                            'id' => 8,
                                                            'name' => 'Quantum Computing Basics - Part 4',
                                                            'description' => 'Qubits, gates, measurement, and simple quantum circuit concepts.',
                                                            'children' => [
                                                                [
                                                                    'id' => 9,
                                                                    'name' => 'Quantum Computing Basics - Part 5',
                                                                    'description' => 'Qubits, gates, measurement, and simple quantum circuit concepts.',
                                                                    'children' => []
                                                                ]
                                                            ],
                                                        ]
                                                    ]
                                                ]
                                            ],
                                        ],
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 6,
                    'name' => 'Biology',
                    'description' => 'Cell biology, genetics, anatomy, ecology, and life sciences fundamentals.',
                    'children' => [
                        [
                            'id' => 7,
                            'name' => 'Microbiology',
                            'description' => 'Microorganisms, bacteria, fungi, viruses, and laboratory basics.',
                            'children' => [],
                        ],
                    ],
                ],
                [
                    'id' => 8,
                    'name' => 'Chemistry',
                    'description' => 'Organic, inorganic, and physical chemistry topics with lab-ready modules.',
                    'children' => [],
                ],
            ],
        ],
        [
            'id' => 9,
            'name' => 'Mathematics',
            'description' => 'Core mathematics subjects for analytical thinking and problem solving.',
            'children' => [
                [
                    'id' => 10,
                    'name' => 'Algebra',
                    'description' => 'Equations, identities, linear systems, and algebraic reasoning.',
                    'children' => [],
                ],
                [
                    'id' => 11,
                    'name' => 'Geometry',
                    'description' => 'Shapes, Euclidean constructions, coordinate geometry, and proofs.',
                    'children' => [
                        [
                            'id' => 12,
                            'name' => 'Trigonometry',
                            'description' => 'Angles, ratios, circular functions, and applications.',
                            'children' => [],
                        ],
                    ],
                ],
                [
                    'id' => 13,
                    'name' => 'Statistics',
                    'description' => 'Data interpretation, distributions, probability, and inference.',
                    'children' => [],
                ],
            ],
        ],
        [
            'id' => 14,
            'name' => 'Computer Science',
            'description' => 'Programming, systems, data, networking, and software engineering categories.',
            'children' => [
                [
                    'id' => 15,
                    'name' => 'Programming',
                    'description' => 'Foundations of coding, syntax, algorithms, and practical software development.',
                    'children' => [
                        [
                            'id' => 16,
                            'name' => 'Web Development',
                            'description' => 'Frontend, backend, APIs, frameworks, and deployment topics.',
                            'children' => [],
                        ],
                        [
                            'id' => 17,
                            'name' => 'Data Structures',
                            'description' => 'Arrays, trees, linked lists, queues, stacks, and hashing.',
                            'children' => [],
                        ],
                    ],
                ],
                [
                    'id' => 18,
                    'name' => 'Networking',
                    'description' => 'Protocols, routing, switching, and secure communication basics.',
                    'children' => [],
                ],
            ],
        ],
        [
            'id' => 19,
            'name' => 'Commerce',
            'description' => 'Business, accounting, finance, and market-focused learning categories.',
            'children' => [
                [
                    'id' => 20,
                    'name' => 'Accounting',
                    'description' => 'Bookkeeping, ledgers, statements, and balance sheet understanding.',
                    'children' => [],
                ],
                [
                    'id' => 21,
                    'name' => 'Economics',
                    'description' => 'Macro, micro, production, demand, and market systems.',
                    'children' => [],
                ],
            ],
        ],
    ];
@endphp

<div class="space-y-6">
    <section class="panel-card overflow-hidden">
        <div class="border-b border-slate-200/80 px-4 py-4 sm:px-6 dark:border-slate-800">
            <div class="flex flex-col gap-4">

                <!-- Header -->
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-slate-950 dark:text-white">
                        Category Explorer
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Browse, search, and manage parent-child category structures from one place.
                    </p>
                </div>

                <!-- ONE ROW: Search + Buttons -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">

                    <!-- Search (flex grow) -->
                    <div class="relative w-full sm:max-w-md">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex w-10 items-center justify-center text-slate-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>

                        <input
                            id="category-search"
                            type="search"
                            placeholder="Search categories, descriptions, or labels..."
                            class="panel-input w-full pr-4"
                            style="padding-left: 2.5rem;"
                        >
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center gap-2 shrink-0">

                        <!-- Expand All -->
                        <button
                            id="expand-all-btn"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-medium text-indigo-700 hover:bg-indigo-100 transition"
                        >
                            <svg id="expand-all-icon"
                                class="h-4 w-4 transition-transform duration-300"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"/>
                            </svg>
                            <span>Expand All</span>
                        </button>

                        <!-- Create -->
                        <a href="{{ route('admin.categories.create') }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-indigo-700 transition">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Create Category</span>
                        </a>

                    </div>
                </div>
            </div>
        </div>

        <div class="px-4 py-4 sm:px-6 sm:py-6">
            <ul id="category-tree-root" class="space-y-4">
                @foreach($categories as $category)
                    @include('backend.categories.partials.tree-node', [
                        'node' => $category,
                        'level' => 0,
                        'levelColors' => $levelColors,
                        'levelSoftColors' => $levelSoftColors,
                    ])
                @endforeach
            </ul>

            <div id="category-empty-state" class="hidden rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-900/40">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm dark:bg-slate-800 dark:text-slate-500">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <h3 class="mt-4 text-base font-semibold text-slate-900 dark:text-white">No matching categories found</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Try a different keyword to explore your category structure.</p>
            </div>
        </div>
    </section>
</div>

{{-- Description Modal (Bootstrap JS controls show/hide; all visual styles are custom) --}}
<div id="descModal" tabindex="-1" aria-labelledby="descModalLabel" aria-hidden="true"
     class="cat-modal-overlay" role="dialog">
    <div class="cat-modal-dialog">
        <div class="cat-modal-card">
            <div class="cat-modal-head">
                <div>
                    <p class="category-desc-modal-eyebrow">Category Details</p>
                    <h5 class="category-desc-modal-title" id="descModalLabel">Category</h5>
                </div>
                <button type="button" class="category-desc-modal-close" data-bs-dismiss="modal" aria-label="Close">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="cat-modal-body">
                <p id="descModalContent" class="category-desc-modal-text"></p>
            </div>
            <div class="cat-modal-foot">
                <button type="button" class="category-desc-modal-btn-close" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Toast stays — it's used after SweetAlert2 confirms delete --}}
<div id="category-toast" class="pointer-events-none fixed right-4 top-4 z-[60] hidden translate-y-2 opacity-0 transition duration-200">
    <div class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-medium text-white shadow-xl shadow-slate-900/30 dark:bg-white dark:text-slate-950">
        <span id="category-toast-text">Category deleted successfully.</span>
    </div>
</div>

@push('styles')
    {{-- Custom Category Styles (no Bootstrap CSS — avoids Tailwind conflict) --}}
    <link rel="stylesheet" href="{{ asset('css/backend/category-hierarchy.css') }}">
    <link rel="stylesheet" href="{{ asset('css/backend/category-list.css') }}">
@endpush

@push('scripts')
    {{-- SweetAlert2 (JS bundle includes its own styles) --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    {{-- Category List Logic --}}
    <script src="{{ asset('js/backend/category-list.js') }}"></script>
@endpush
@endsection

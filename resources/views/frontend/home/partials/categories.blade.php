@php
    $groups = [
        [
            'key' => 'exam',
            'title' => 'Exam Categories',
            'items' => $page['examCategories'] ?? collect(),
            'urlFn' => fn ($c) => route('frontend.categories.show', $c->slug),
        ],
        [
            'key' => 'blog',
            'title' => 'Blog Categories',
            'items' => $page['blogCategories'] ?? collect(),
            'urlFn' => fn ($c) => route('frontend.blogs.category', $c->slug),
        ],
        [
            'key' => 'news',
            'title' => 'News Categories',
            'items' => $page['newsCategories'] ?? collect(),
            'urlFn' => fn ($c) => route('frontend.news.category', $c->slug),
        ],
        [
            'key' => 'question',
            'title' => 'Question Categories',
            'items' => $page['questionCategories'] ?? collect(),
            'urlFn' => fn ($c) => route('frontend.questions.category', $c->slug),
        ],
    ];
    $groups = array_values(array_filter($groups, fn ($g) => $g['items']->isNotEmpty()));
@endphp
<section class="et-section et-section--alt" data-reveal>
    <div class="et-container">
        @include('frontend.components.section-heading', [
            'title' => 'Browse by category',
            'subtitle' => 'Explore exams, blogs, news, and questions by topic — one group at a time.',
            'actionUrl' => route('frontend.categories.index'),
            'actionLabel' => 'All categories',
        ])

        @if(count($groups) === 0)
            @include('frontend.partials.empty-state', ['title' => 'Categories coming soon', 'message' => ''])
        @else
            <div class="et-cat-slider" data-cat-slider data-pause-ms="2000" data-slide-ms="650">
                <div class="et-cat-slider__viewport">
                    <div class="et-cat-slider__track" data-cat-slider-track>
                        @foreach($groups as $index => $group)
                            <div class="et-cat-slider__group {{ $index === 0 ? 'is-active' : '' }}" data-cat-slider-group>
                                <div class="et-cat-slider__head">
                                    <h3>{{ $group['title'] }}</h3>
                                    <span class="et-cat-slider__count">{{ $group['items']->count() }} topics</span>
                                </div>
                                <div class="et-cat-slider__pills">
                                    @foreach($group['items'] as $category)
                                        <a href="{{ ($group['urlFn'])($category) }}" class="et-cat-pill">
                                            <span class="et-cat-pill__icon">{{ strtoupper(mb_substr($category->name, 0, 1)) }}</span>
                                            <span class="et-cat-pill__label">{{ $category->name }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @if(count($groups) > 1)
                    <div class="et-cat-slider__controls">
                        <button type="button" class="et-icon-btn" data-cat-slider-prev aria-label="Previous category group">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>
                        <div class="et-cat-slider__dots" role="tablist" aria-label="Category groups">
                            @foreach($groups as $index => $group)
                                <button
                                    type="button"
                                    class="et-cat-slider__dot {{ $index === 0 ? 'is-active' : '' }}"
                                    data-cat-slider-dot
                                    aria-label="{{ $group['title'] }}"
                                    aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                                ></button>
                            @endforeach
                        </div>
                        <button type="button" class="et-icon-btn" data-cat-slider-next aria-label="Next category group">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>
</section>

<aside class="et-qd__aside et-article-aside" aria-label="Question resources">
    <section class="et-article-aside__card et-qd__overview-card">
        <p class="et-article-aside__label">Question details</p>
        <h2 class="et-qd__overview-title">Practice overview</h2>
        <div class="et-article-aside__meta et-qd__aside-meta">
            @if($question->category)
                <a class="et-article-aside__meta-item et-article-aside__meta-item--accent"
                   href="{{ route('frontend.questions.category', $question->category->slug) }}">
                    {{ $question->category->name }}
                </a>
            @endif
            <span class="et-article-aside__meta-item">{{ $question->typeLabel() }}</span>
        </div>
        <dl class="et-qd__overview-facts">
            <div>
                <dt>Marks</dt>
                <dd>{{ (float) ($question->marks ?? 0) == (int) ($question->marks ?? 0) ? (int) ($question->marks ?? 0) : $question->marks }}</dd>
            </div>
            <div>
                <dt>Views</dt>
                <dd>{{ number_format((int) $question->view_count) }}</dd>
            </div>
        </dl>
        <p class="et-qd__aside-note">Review the correct answer and explanation, then continue with related practice.</p>
    </section>

    @if($publicTags->isNotEmpty())
        <section class="et-article-aside__card">
            <div class="et-article-aside__head">
                <h2 class="et-article-aside__heading">Tags</h2>
            </div>
            <div class="et-article-aside__tags">
                @foreach($publicTags as $tag)
                    <span class="et-article-aside__tag et-qd__aside-tag">#{{ $tag }}</span>
                @endforeach
            </div>
        </section>
    @endif

    @if(($relatedQuestions ?? collect())->isNotEmpty())
        <section class="et-article-aside__card et-qd__related-questions">
            <div class="et-article-aside__head">
                <h2 class="et-article-aside__heading">Related questions</h2>
                <a class="et-article-aside__all" href="{{ route('frontend.questions.index') }}">View all</a>
            </div>
            <ul class="et-article-aside__latest">
                @foreach($relatedQuestions as $relatedQuestion)
                    <li>
                        <a class="et-article-aside__latest-item et-article-aside__latest-item--text"
                           href="{{ route('frontend.questions.show', $relatedQuestion) }}">
                            <span class="et-article-aside__index" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="et-article-aside__latest-body">
                                <span class="et-article-aside__latest-kicker">
                                    {{ $relatedQuestion->category?->name ?: $relatedQuestion->difficultyLabel() }}
                                </span>
                                <span class="et-article-aside__latest-title">{{ $relatedQuestion->publicTitle() }}</span>
                                <span class="et-article-aside__latest-meta">{{ $relatedQuestion->typeLabel() }} · {{ $relatedQuestion->difficultyLabel() }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if(($questionCategories ?? collect())->isNotEmpty())
        <section class="et-article-aside__card">
            <div class="et-article-aside__head">
                <h2 class="et-article-aside__heading">Categories</h2>
                <a class="et-article-aside__all" href="{{ route('frontend.questions.categories') }}">View all</a>
            </div>
            <ul class="et-article-aside__cats">
                @foreach($questionCategories as $category)
                    <li>
                        <a class="et-article-aside__cat{{ (int) $question->category_id === (int) $category->id ? ' is-current' : '' }}"
                           href="{{ route('frontend.questions.category', $category->slug) }}"
                           @if((int) $question->category_id === (int) $category->id) aria-current="page" @endif>
                            <span class="et-article-aside__cat-main">
                                <span class="et-article-aside__cat-name">{{ $category->name }}</span>
                                <span class="et-article-aside__cat-desc">
                                    {{ number_format((int) $category->questions_count) }} question{{ (int) $category->questions_count === 1 ? '' : 's' }}
                                </span>
                            </span>
                            <span class="et-article-aside__cat-arrow" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                    <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</aside>

@php
    /** @var \Illuminate\Support\Collection<int, array<string, mixed>> $previousAttempts */
    $hasAttempts = $previousAttempts->isNotEmpty();
@endphp

<section class="et-card pa-section" id="previous-attempts" aria-labelledby="previous-attempts-title">
    <div class="pa-section__head">
        <div>
            <h2 id="previous-attempts-title">Previous attempts</h2>
            <p class="pa-section__lead">
                @if($hasAttempts)
                    Review your recent attempts for this exam, including score, timing, and submission details.
                @else
                    Your attempt history for this exam will appear here.
                @endif
            </p>
        </div>
        @if($hasAttempts)
            <span class="et-badge et-badge--slate">{{ $previousAttempts->count() }} shown</span>
        @endif
    </div>

    @if(! $hasAttempts)
        <div class="pa-empty">
            @include('frontend.partials.empty-state', [
                'title' => 'No previous attempts found.',
                'message' => 'Start your first attempt to track your exam history here.',
                'actionUrl' => route('frontend.exams.rules', $exam),
                'actionLabel' => 'Attempt Exam',
            ])
        </div>
    @else
        <div class="pa-list">
            @foreach($previousAttempts as $card)
                @php
                    $panelId = 'pa-panel-'.$card['id'];
                    $timingRows = [
                        ['Started', $card['started_at_label']],
                        ['Ended', $card['ended_at_label']],
                        ['Timezone', $card['timezone']],
                        ['Time taken', $card['time_spent_label']],
                        ['Allowed duration', $card['allowed_duration_label']],
                    ];
                    $questionRows = [
                        ['Total questions', $card['total_questions']],
                        ['Attempted', $card['attempted_label']],
                        ['Unattempted', $card['unattempted']],
                        ['Correct', $card['correct']],
                        ['Incorrect', $card['incorrect']],
                        ['Skipped', $card['skipped']],
                    ];
                    if ((int) $card['marked_for_review'] > 0) {
                        $questionRows[] = ['Marked for review', $card['marked_for_review']];
                    }
                    $marksRows = [
                        ['Total marks', $card['total_marks'] > 0 ? number_format((float) $card['total_marks'], 0) : '—'],
                        ['Marks obtained', $card['score'] !== null ? number_format((float) $card['score'], 2) : '—'],
                        ['Passing marks', $card['passing_marks'] > 0 ? number_format((float) $card['passing_marks'], 0) : '—'],
                        ['Percentage', $card['percentage'] !== null ? number_format((float) $card['percentage'], 1).'%' : '—'],
                    ];
                    if (! empty($card['negative_marking_enabled'])) {
                        $marksRows[] = [
                            'Negative marks',
                            $card['negative_marks_deducted'] !== null ? number_format((float) $card['negative_marks_deducted'], 2) : '0.00',
                        ];
                    }
                    $submissionRows = [
                        ['Submission type', $card['submission_type']],
                        ['Reason', $card['submission_detail']],
                        ['Submitted at', $card['submitted_at_label']],
                        ['Exam mode', $card['exam_mode']],
                    ];
                    if (! empty($card['paper_set'])) {
                        $submissionRows[] = ['Question set', '#'.$card['paper_set']];
                    }
                    $submissionRows[] = ['Device', $card['device_type']];
                    $submissionRows[] = ['Browser', $card['browser']];
                    if ((int) $card['violations_count'] > 0) {
                        $submissionRows[] = ['Rule violations', $card['violations_count']];
                    }
                    $sections = [
                        ['Timing', $timingRows],
                        ['Questions', $questionRows],
                        ['Marks & performance', $marksRows],
                        ['Submission', $submissionRows],
                    ];
                @endphp

                <article class="pa-card is-collapsed{{ ! empty($card['is_latest']) ? ' is-latest' : '' }}"
                         data-pa-card
                         aria-label="Attempt #{{ $card['attempt_no'] }}">
                    <header class="pa-card__header">
                        <div class="pa-card__title-wrap">
                            <div class="pa-card__title-row">
                                <h3 class="pa-card__title">Attempt #{{ $card['attempt_no'] }}</h3>
                                @if(! empty($card['is_latest']))
                                    <span class="et-badge">Latest</span>
                                @endif
                            </div>
                            <div class="pa-card__badges">
                                <span class="et-badge et-badge--{{ $card['status_tone'] }}">{{ $card['status_label'] }}</span>
                                @if(! empty($card['result_label']))
                                    <span class="et-badge et-badge--{{ $card['result_tone'] === 'pass' ? 'success' : 'danger' }}">{{ $card['result_label'] }}</span>
                                @endif
                                <span class="et-badge et-badge--{{ $card['submission_type_tone'] }}">{{ $card['submission_type'] }}</span>
                            </div>
                        </div>

                        <div class="pa-card__aside">
                            <div class="pa-card__score" aria-label="Score summary">
                                <div class="pa-card__score-value">
                                    {{ $card['percentage'] !== null ? number_format((float) $card['percentage'], 1).'%' : '—' }}
                                </div>
                                <div class="pa-card__score-meta">
                                    @if($card['score'] !== null)
                                        {{ number_format((float) $card['score'], 2) }}
                                        @if($card['total_marks'] > 0)
                                            / {{ number_format((float) $card['total_marks'], 0) }}
                                        @endif
                                        marks
                                    @else
                                        Score unavailable
                                    @endif
                                </div>
                            </div>

                            <button type="button"
                                    class="pa-toggle et-btn et-btn--ghost et-btn--sm"
                                    data-pa-toggle
                                    aria-expanded="false"
                                    aria-controls="{{ $panelId }}">
                                <span class="pa-toggle__label" data-pa-toggle-label>Show details</span>
                                <span class="pa-toggle__icon" aria-hidden="true"></span>
                            </button>
                        </div>
                    </header>

                    <div class="pa-card__body" id="{{ $panelId }}" hidden>
                        <div class="pa-progress" aria-hidden="true">
                            <div class="pa-progress__track">
                                <span class="pa-progress__fill" style="width: {{ max(0, min(100, (int) $card['progress_percent'])) }}%"></span>
                            </div>
                            <span class="pa-progress__label">{{ $card['attempted_label'] }} attempted</span>
                        </div>

                        <div class="pa-summary">
                            <div class="pa-summary__item">
                                <span class="pa-summary__label">Time taken</span>
                                <strong class="pa-summary__value">{{ $card['time_spent_label'] }}</strong>
                            </div>
                            <div class="pa-summary__item">
                                <span class="pa-summary__label">Correct</span>
                                <strong class="pa-summary__value">{{ $card['correct'] }}</strong>
                            </div>
                            <div class="pa-summary__item">
                                <span class="pa-summary__label">Incorrect</span>
                                <strong class="pa-summary__value">{{ $card['incorrect'] }}</strong>
                            </div>
                            <div class="pa-summary__item">
                                <span class="pa-summary__label">Unattempted</span>
                                <strong class="pa-summary__value">{{ $card['unattempted'] }}</strong>
                            </div>
                            <div class="pa-summary__item">
                                <span class="pa-summary__label">Violations</span>
                                <strong class="pa-summary__value">{{ $card['violations_count'] }}</strong>
                            </div>
                        </div>

                        <div class="pa-rows">
                            @foreach($sections as [$sectionTitle, $rows])
                                <section class="pa-row-group">
                                    <h4 class="pa-row-group__title">{{ $sectionTitle }}</h4>
                                    <dl class="pa-rows__list">
                                        @foreach($rows as [$label, $value])
                                            <div class="pa-row">
                                                <dt>{{ $label }}</dt>
                                                <dd>{{ $value }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </section>
                            @endforeach
                        </div>
                    </div>

                    <footer class="pa-card__actions">
                        <a href="{{ $card['result_url'] }}" class="et-btn et-btn--primary et-btn--sm">View attempt details</a>
                        @if(! empty($card['review_url']))
                            <a href="{{ $card['review_url'] }}" class="et-btn et-btn--ghost et-btn--sm">Review answers</a>
                        @endif
                        @if(! empty($card['download_enabled']))
                            <a href="#" class="et-btn et-btn--ghost et-btn--sm" aria-disabled="true">Download result</a>
                        @endif
                    </footer>
                </article>
            @endforeach
        </div>
    @endif
</section>

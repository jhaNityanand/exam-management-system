@extends('frontend.account.layout')

@php
    $seo = ['title' => 'Dashboard'];
    $charts = $charts ?? ['pie' => [], 'bars' => [], 'line' => [], 'categories' => [], 'demo' => true];
    $pie = $charts['pie'] ?? [];
    $bars = $charts['bars'] ?? [];
    $line = $charts['line'] ?? [];
    $categories = $charts['categories'] ?? [];
    $pieTotal = max(1, collect($pie)->sum('value'));
    $barMax = max(1, collect($bars)->max('value') ?: 100);

    // Build conic-gradient stops for donut
    $cursor = 0;
    $conicStops = [];
    foreach ($pie as $slice) {
        $start = $cursor;
        $cursor += ((float) $slice['value'] / $pieTotal) * 100;
        $conicStops[] = ($slice['color'] ?? '#0f766e').' '.$start.'% '.$cursor.'%';
    }
    $conic = $conicStops ? 'conic-gradient('.implode(', ', $conicStops).')' : 'conic-gradient(#0f766e 0 100%)';

    // SVG line chart points
    $lineCount = max(1, count($line));
    $lineMax = max(1, max($line ?: [100]));
    $lineMin = min($line ?: [0]);
    $range = max(1, $lineMax - $lineMin);
    $points = [];
    $areaPoints = [];
    foreach ($line as $i => $value) {
        $x = $lineCount === 1 ? 50 : ($i / ($lineCount - 1)) * 100;
        $y = 100 - ((($value - $lineMin) / $range) * 78 + 10);
        $points[] = round($x, 2).','.round($y, 2);
        $areaPoints[] = round($x, 2).','.round($y, 2);
    }
    $polyline = implode(' ', $points);
    $area = '0,100 '.implode(' ', $areaPoints).' 100,100';
@endphp

@section('account-eyebrow', 'Overview')
@section('account-title', 'Dashboard')
@section('account-lead', 'Track your practice journey with live stats and performance visuals.')

@section('account-actions')
    <a href="{{ route('frontend.exams.index') }}" class="et-btn et-btn--primary et-btn--sm">Browse exams</a>
@endsection

@section('account-content')
    <section class="ca-stats ca-stats--hero">
        <div class="ca-stat ca-stat--accent">
            <span class="ca-stat__label">Attempts</span>
            <span class="ca-stat__value">{{ (int) ($stats['attempts'] ?? 0) }}</span>
            <span class="ca-stat__hint">Total exam starts</span>
        </div>
        <div class="ca-stat">
            <span class="ca-stat__label">Completed</span>
            <span class="ca-stat__value">{{ (int) ($stats['completed'] ?? 0) }}</span>
            <span class="ca-stat__hint">Submitted attempts</span>
        </div>
        <div class="ca-stat ca-stat--success">
            <span class="ca-stat__label">Passed</span>
            <span class="ca-stat__value">{{ (int) ($stats['passed'] ?? 0) }}</span>
            <span class="ca-stat__hint">Successful results</span>
        </div>
        <div class="ca-stat ca-stat--info">
            <span class="ca-stat__label">Avg score</span>
            <span class="ca-stat__value">{{ (int) ($stats['avg_score'] ?? 0) }}%</span>
            <span class="ca-stat__hint">Across graded attempts</span>
        </div>
    </section>

    @if(! empty($charts['demo']))
        <p class="ca-chart-note">Showing sample analytics preview until you complete more exams — charts will switch to your real data automatically.</p>
    @endif

    <section class="ca-chart-grid">
        <article class="ca-card ca-chart-card">
            <div class="ca-card__head">
                <div>
                    <h2>Result breakdown</h2>
                    <p>Pass / fail / in-progress distribution</p>
                </div>
            </div>
            <div class="ca-donut-wrap">
                <div class="ca-donut" style="background: {{ $conic }}" role="img" aria-label="Result breakdown pie chart">
                    <div class="ca-donut__hole">
                        <strong>{{ (int) ($stats['attempts'] ?: collect($pie)->sum('value')) }}</strong>
                        <span>Total</span>
                    </div>
                </div>
                <ul class="ca-legend">
                    @foreach($pie as $slice)
                        <li>
                            <span class="ca-legend__swatch" style="background:{{ $slice['color'] }}"></span>
                            <span>{{ $slice['label'] }}</span>
                            <strong>{{ (int) $slice['value'] }}</strong>
                        </li>
                    @endforeach
                </ul>
            </div>
        </article>

        <article class="ca-card ca-chart-card">
            <div class="ca-card__head">
                <div>
                    <h2>Score trend</h2>
                    <p>Recent percentage performance</p>
                </div>
            </div>
            <div class="ca-line-chart" role="img" aria-label="Score trend line chart">
                <svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                    <defs>
                        <linearGradient id="caLineFill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#0f766e" stop-opacity="0.28"/>
                            <stop offset="100%" stop-color="#0f766e" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <line x1="0" y1="25" x2="100" y2="25" class="ca-line-grid"/>
                    <line x1="0" y1="50" x2="100" y2="50" class="ca-line-grid"/>
                    <line x1="0" y1="75" x2="100" y2="75" class="ca-line-grid"/>
                    <polygon points="{{ $area }}" fill="url(#caLineFill)"/>
                    <polyline points="{{ $polyline }}" class="ca-line-path"/>
                    @foreach($points as $point)
                        @php [$px, $py] = array_map('floatval', explode(',', $point)); @endphp
                        <circle cx="{{ $px }}" cy="{{ $py }}" r="1.6" class="ca-line-dot"/>
                    @endforeach
                </svg>
                <div class="ca-line-meta">
                    <span>Low {{ (int) ($lineMin ?: min($line ?: [0])) }}%</span>
                    <span>High {{ (int) ($lineMax ?: max($line ?: [0])) }}%</span>
                </div>
            </div>
        </article>
    </section>

    <section class="ca-chart-grid ca-chart-grid--bottom">
        <article class="ca-card ca-chart-card">
            <div class="ca-card__head">
                <div>
                    <h2>Weekly performance</h2>
                    <p>Score intensity across recent sessions</p>
                </div>
            </div>
            <div class="ca-bars" style="grid-template-columns: repeat({{ max(1, count($bars)) }}, minmax(0, 1fr))" role="img" aria-label="Weekly performance bar chart">
                @foreach($bars as $bar)
                    @php $height = max(8, ((int) $bar['value'] / $barMax) * 100); @endphp
                    <div class="ca-bars__col">
                        <div class="ca-bars__value">{{ (int) $bar['value'] }}%</div>
                        <div class="ca-bars__track">
                            <div class="ca-bars__fill" style="--ca-bar-h: {{ $height }}%"></div>
                        </div>
                        <div class="ca-bars__label">{{ $bar['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="ca-card ca-chart-card">
            <div class="ca-card__head">
                <div>
                    <h2>Skill radar snapshot</h2>
                    <p>Category readiness (preview)</p>
                </div>
            </div>
            <div class="ca-meter-list">
                @foreach($categories as $cat)
                    <div class="ca-meter">
                        <div class="ca-meter__head">
                            <span>{{ $cat['label'] }}</span>
                            <strong>{{ (int) $cat['value'] }}%</strong>
                        </div>
                        <div class="ca-meter__track">
                            <div class="ca-meter__fill" style="width: {{ (int) $cat['value'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="ca-card">
        <div class="ca-card__head">
            <div>
                <h2>Profile completion</h2>
                <p>{{ $completion['filled'] ?? 0 }} of {{ $completion['total'] ?? 0 }} fields complete</p>
            </div>
            <a href="{{ route('frontend.account.profile') }}" class="et-btn et-btn--ghost et-btn--sm">Complete profile</a>
        </div>
        <div class="ca-progress">
            <div class="ca-progress__meta">
                <span>Progress</span>
                <span>{{ (int) ($completion['percent'] ?? 0) }}%</span>
            </div>
            <div class="ca-progress__track">
                <div class="ca-progress__fill" style="width: {{ (int) ($completion['percent'] ?? 0) }}%"></div>
            </div>
        </div>
    </section>

    <section class="ca-card">
        <div class="ca-card__head">
            <div>
                <h2>Quick links</h2>
                <p>Jump into exams, results, or account settings.</p>
            </div>
        </div>
        <div class="ca-actions">
            <a href="{{ route('frontend.exams.index') }}" class="et-btn et-btn--primary et-btn--sm">Browse exams</a>
            <a href="{{ route('frontend.account.exams') }}" class="et-btn et-btn--ghost et-btn--sm">My exams</a>
            <a href="{{ route('frontend.account.results') }}" class="et-btn et-btn--ghost et-btn--sm">Results</a>
            <a href="{{ route('frontend.account.invoices') }}" class="et-btn et-btn--ghost et-btn--sm">Invoices</a>
            <a href="{{ route('frontend.account.profile') }}" class="et-btn et-btn--ghost et-btn--sm">Profile</a>
        </div>
    </section>

    <section class="ca-card">
        <div class="ca-card__head">
            <div>
                <h2>Recent activity</h2>
                <p>Your latest exam attempts.</p>
            </div>
            <a href="{{ route('frontend.account.exams') }}" class="et-btn et-btn--ghost et-btn--sm">View all</a>
        </div>
        @if(($recent ?? collect())->isEmpty())
            <div class="ca-empty">No attempts yet. Start practicing to see activity here.</div>
        @else
            <div class="ca-list">
                @foreach($recent as $attempt)
                    <div class="ca-list-item">
                        <div>
                            <strong>{{ $attempt->exam?->title ?: 'Exam' }}</strong>
                            <span>{{ ucfirst(str_replace('_', ' ', $attempt->status)) }} · {{ optional($attempt->started_at)->format('d M Y') ?: '—' }}</span>
                        </div>
                        <div class="ca-actions">
                            @if(in_array($attempt->status, ['active', 'in_progress'], true) && $attempt->exam)
                                <a class="et-btn et-btn--primary et-btn--sm" href="{{ route('frontend.exams.started', $attempt->exam) }}">Continue</a>
                            @else
                                <a class="et-btn et-btn--ghost et-btn--sm" href="{{ route('frontend.attempts.result', $attempt) }}">Result</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection

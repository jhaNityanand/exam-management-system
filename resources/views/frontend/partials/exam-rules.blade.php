@php
    $categoryMeta = [
        'integrity' => [
            'label' => 'Conduct & integrity',
            'hint' => 'Stay honest — violations can cancel your attempt.',
            'tone' => 'danger',
        ],
        'environment' => [
            'label' => 'Tech & environment',
            'hint' => 'Keep your setup stable before and during the exam.',
            'tone' => 'info',
        ],
        'monitoring' => [
            'label' => 'Proctoring & monitoring',
            'hint' => 'Camera, fullscreen, and focus rules may apply.',
            'tone' => 'warn',
        ],
        'submission' => [
            'label' => 'Timing & submission',
            'hint' => 'Know how scoring, timer, and final submit work.',
            'tone' => 'accent',
        ],
        'other' => [
            'label' => 'General guidance',
            'hint' => 'Extra tips to help you perform your best.',
            'tone' => 'soft',
        ],
    ];

    $iconPaths = [
        'shield' => 'M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-3z',
        'phone-off' => 'M2 2l20 20M7 7c-1.2 1.4-2 3.2-2 5.2 0 1.4.3 2.7.9 3.9M17 17.5c-1.4 1.2-3.1 2-5 2.3M10 5.1A8.5 8.5 0 0120.5 14',
        'clock' => 'M12 7v5l3 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'wifi' => 'M5 12.5a9.5 9.5 0 0114 0M8.5 15.5a5 5 0 017 0M12 19h.01',
        'wifi-off' => 'M2 2l20 20M8.5 15.5a5 5 0 014.2-1.4M5 12.5c1-.9 2.2-1.6 3.5-2M16.5 10.5c1.5.5 2.8 1.3 4 2.5M12 19h.01',
        'refresh' => 'M4 4v6h6M20 20v-6h-6M5 14a7 7 0 0012.2 2.5M19 10A7 7 0 006.8 7.5',
        'users' => 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75',
        'book' => 'M4 19.5A2.5 2.5 0 016.5 17H20M4 4.5A2.5 2.5 0 016.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15z',
        'minus-circle' => 'M12 21a9 9 0 100-18 9 9 0 000 18zM8 12h8',
        'lock' => 'M7 11V8a5 5 0 0110 0v3M6 11h12v10H6V11z',
        'ban' => 'M12 21a9 9 0 100-18 9 9 0 000 18zM6.5 6.5l11 11',
        'tabs' => 'M4 6h12v12H4zM8 2h12v12',
        'maximize' => 'M9 3H3v6M15 3h6v6M9 21H3v-6M21 15v6h-6',
        'clipboard' => 'M9 4h6a2 2 0 012 2v1H7V6a2 2 0 012-2zM7 7h10v13H7z',
        'camera' => 'M4 8h3l2-2h6l2 2h3v11H4V8zM12 17a3.5 3.5 0 100-7 3.5 3.5 0 000 7z',
        'camera-off' => 'M2 2l20 20M7 7H4v11h11M10 7l2-2h4l2 2h2v7',
        'one' => 'M10 8l2-2v12M9 18h6',
        'id' => 'M4 6h16v12H4zM8 12h4M8 9h8M16 14.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z',
        'alert' => 'M12 9v4M12 17h.01M10.3 4.3L2.8 18a2 2 0 001.7 3h15a2 2 0 001.7-3L13.7 4.3a2 2 0 00-3.4 0z',
    ];

    $grouped = ($rules ?? collect())->groupBy(fn ($rule) => $rule->category ?: 'other');
    $order = ['integrity', 'environment', 'monitoring', 'submission', 'other'];
    $orderedKeys = collect($order)->filter(fn ($key) => $grouped->has($key))
        ->merge($grouped->keys()->diff($order))
        ->values();
    $requiredCount = ($rules ?? collect())->where('is_required', true)->count();
@endphp

@if(($rules ?? collect())->isNotEmpty())
    <section class="et-rules" aria-labelledby="et-rules-heading">
        <div class="et-rules__head">
            <div>
                <p class="et-rules__eyebrow">Must-read before you continue</p>
                <h3 id="et-rules-heading">Rules &amp; regulations</h3>
                <p class="et-rules__intro">
                    {{ $rules->count() }} rules across {{ $orderedKeys->count() }} areas
                    @if($requiredCount > 0)
                        · <strong>{{ $requiredCount }} required</strong>
                    @endif
                </p>
            </div>
            <div class="et-rules__legend" aria-hidden="true">
                <span class="et-rules__pill et-rules__pill--required">Required</span>
                <span class="et-rules__pill">Guidance</span>
            </div>
        </div>

        <div class="et-rules__groups">
            @foreach($orderedKeys as $categoryKey)
                @php
                    $meta = $categoryMeta[$categoryKey] ?? $categoryMeta['other'];
                    $items = $grouped->get($categoryKey, collect());
                @endphp
                <div class="et-rules__group et-rules__group--{{ $meta['tone'] }}">
                    <div class="et-rules__group-head">
                        <h4>{{ $meta['label'] }}</h4>
                        <p>{{ $meta['hint'] }}</p>
                        <span class="et-rules__count">{{ $items->count() }}</span>
                    </div>

                    <ul class="et-rules__grid">
                        @foreach($items as $rule)
                            @php
                                $iconKey = $rule->icon ?: 'alert';
                                $path = $iconPaths[$iconKey] ?? $iconPaths['alert'];
                            @endphp
                            <li class="et-rule-card {{ $rule->is_required ? 'et-rule-card--required' : '' }}">
                                <div class="et-rule-card__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="{{ $path }}"/>
                                    </svg>
                                </div>
                                <div class="et-rule-card__body">
                                    <div class="et-rule-card__title-row">
                                        <strong>{{ $rule->title }}</strong>
                                        @if($rule->is_required)
                                            <span class="et-rules__pill et-rules__pill--required">Required</span>
                                        @endif
                                    </div>
                                    @if($rule->description)
                                        <p>{{ $rule->description }}</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </section>
@endif

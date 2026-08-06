@php
    $items = collect($page['testimonials'] ?? []);
    $palette = ['#2563eb', '#0d9488', '#db2777', '#ea580c', '#7c3aed', '#0891b2', '#ca8a04', '#dc2626', '#4f46e5', '#059669', '#d97706', '#0284c8'];

    $cards = $items->map(function ($row, $index) use ($palette) {
        if (is_array($row)) {
            return $row;
        }

        $name = (string) ($row->name ?? 'Learner');
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
        if ($initials === '') {
            $initials = 'ET';
        }

        $role = trim(implode(' — ', array_filter([
            $row->role ?? null,
            $row->company ?? null,
        ])));

        return [
            'quote' => (string) ($row->quote ?? ''),
            'name' => $name,
            'role' => $role !== '' ? $role : 'Examtube learner',
            'rating' => max(1, min(5, (int) ($row->rating ?? 5))),
            'color' => $palette[$index % count($palette)],
            'initials' => $initials,
            'avatar_url' => $row->avatar?->file_url,
        ];
    })->filter(fn ($card) => filled($card['quote'] ?? null))->values();
@endphp
@if($cards->isNotEmpty())
<section class="et-section" data-reveal>
    <div class="et-container">
        @include('frontend.components.section-heading', [
            'title' => filled($section?->title) ? $section->title : 'Stories from learners',
            'subtitle' => filled($section?->subtitle) ? $section->subtitle : 'Real outcomes from students and job seekers preparing with Examtube.',
        ])
        <div
            class="et-testimonial-slider"
            data-testimonial-slider
            data-interval="5000"
            aria-roledescription="carousel"
            aria-label="Learner stories"
        >
            <div class="et-testimonial-slider__viewport">
                <div class="et-testimonial-slider__track">
                    @foreach($cards as $item)
                        <article class="et-testimonial et-testimonial--lift et-testimonial-slider__item">
                            <div class="et-testimonial__rating" aria-label="{{ $item['rating'] }} out of 5 stars">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="{{ $i <= $item['rating'] ? 'is-on' : '' }}">★</span>
                                @endfor
                            </div>
                            <p class="et-testimonial__quote">“{{ $item['quote'] }}”</p>
                            <div class="et-testimonial__person">
                                @if(! empty($item['avatar_url']))
                                    <div class="et-testimonial__avatar">
                                        <img src="{{ $item['avatar_url'] }}" alt="" loading="lazy">
                                    </div>
                                @else
                                    <div class="et-testimonial__avatar" style="background:{{ $item['color'] }};color:#fff">{{ $item['initials'] }}</div>
                                @endif
                                <div>
                                    <div class="et-testimonial__name">{{ $item['name'] }}</div>
                                    <div class="et-testimonial__role">{{ $item['role'] }}</div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @include('frontend.partials.ad-placement', ['page' => 'home', 'position' => 'after_testimonials'])
</section>
@endif

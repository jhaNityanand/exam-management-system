@php $partners = $page['partners'] ?? collect(); @endphp
@if($partners->isNotEmpty())
<section class="et-section et-section--alt" data-reveal>
    <div class="et-container">
        @include('frontend.components.section-heading', [
            'title' => $section?->title ?? 'Partners & sponsors',
            'subtitle' => $section?->subtitle ?? 'Institutes and brands supporting quality preparation',
        ])
        <div class="et-partners">
            @foreach($partners as $partner)
                @php $href = $partner->url ?: null; @endphp
                @if($href)
                    <a class="et-partner" href="{{ $href }}" target="_blank" rel="noopener noreferrer">
                        @if($partner->logo && $partner->logo->file_url)
                            <img src="{{ $partner->logo->file_url }}" alt="{{ $partner->name }}" loading="lazy">
                        @else
                            {{ $partner->name }}
                        @endif
                    </a>
                @else
                    <span class="et-partner">
                        @if($partner->logo && $partner->logo->file_url)
                            <img src="{{ $partner->logo->file_url }}" alt="{{ $partner->name }}" loading="lazy">
                        @else
                            {{ $partner->name }}
                        @endif
                    </span>
                @endif
            @endforeach
        </div>
    </div>
</section>
@endif

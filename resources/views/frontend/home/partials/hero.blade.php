@include('frontend.components.hero-slider', [
    'banners' => $page['banners'] ?? collect(),
])

@php
    $siteName = 'Examtube.in';
    $defaultTitle = $seo['title'] ?? ($seoTitle ?? 'Examtube.in — Practice smarter. Score higher.');
    if (! empty($seo['title'] ?? $seoTitle ?? null)) {
        $pageTitle = $seo['title'] ?? $seoTitle;
        $defaultTitle = str_contains($pageTitle, $siteName) ? $pageTitle : ($pageTitle.' | '.$siteName);
    }
    $defaultDesc = $seo['description'] ?? ($seoDescription ?? 'Timed mocks, curated questions, mentor blogs, and education news for serious aspirants.');
    $defaultKeywords = $seo['keywords'] ?? 'exams, mock tests, questions, blogs, news, competitive exams';
    $defaultImage = asset('frontend/images/banner.svg');

    $pageTitle = $seo['title'] ?? ($seoTitle ?? null);
    $pageDesc = $seo['description'] ?? ($seoDescription ?? null);
    $title = $pageTitle
        ? (str_contains($pageTitle, $siteName) ? $pageTitle : ($pageTitle.' | '.$siteName))
        : 'Examtube.in — Practice smarter. Score higher.';
    $description = $pageDesc ?: 'Timed mocks, curated questions, mentor blogs, and education news for serious aspirants.';
    $canonical = $seo['canonical'] ?? url()->current();
    $ogImage = $seo['image'] ?? $defaultImage;
    $ogTitle = $seo['og_title'] ?? ($pageTitle ?? $title);
    $ogDescription = $seo['og_description'] ?? $description;
    $keywords = $seo['keywords'] ?? $defaultKeywords;
    $robots = $seo['robots'] ?? 'index, follow';
    $ogType = $seo['type'] ?? 'website';
    $locale = str_replace('_', '-', app()->getLocale());

    $faviconUrl = asset('frontend/images/favicon.svg');

    $breadcrumbs = $seo['breadcrumbs'] ?? ($breadcrumbs ?? null);
    $schema = $seo['schema'] ?? null;

    $organizationSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $siteName,
        'url' => url('/'),
        'logo' => asset('frontend/images/logo.svg'),
    ];
    if (! empty($siteSettings['contact.email'] ?? null)) {
        $organizationSchema['email'] = $siteSettings['contact.email'];
    }
    if (! empty($siteSettings['contact.phone'] ?? null)) {
        $organizationSchema['telephone'] = $siteSettings['contact.phone'];
    }

    $websiteSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $siteName,
        'url' => url('/'),
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => route('frontend.search').'?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ];

    $breadcrumbSchema = null;
    if (is_array($breadcrumbs) && count($breadcrumbs) > 0) {
        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($breadcrumbs)->values()->map(function ($item, $index) {
                $entry = [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['label'] ?? ($item['name'] ?? ''),
                ];
                if (! empty($item['url'])) {
                    $entry['item'] = $item['url'];
                }

                return $entry;
            })->all(),
        ];
    }
@endphp
<title>{{ $title }}</title>
<link rel="icon" href="{{ $faviconUrl }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ $faviconUrl }}">
<meta name="description" content="{{ $description }}">
@if($keywords)
    <meta name="keywords" content="{{ is_array($keywords) ? implode(', ', $keywords) : $keywords }}">
@endif
<meta name="robots" content="{{ $robots }}">
<meta name="author" content="{{ $siteName }}">
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="{{ $locale }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDescription }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $ogImage }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $ogTitle }}">
<meta name="twitter:description" content="{{ $ogDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">

<script type="application/ld+json">{!! json_encode($organizationSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($websiteSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@if($breadcrumbSchema)
    <script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endif
@if(is_array($schema))
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endif

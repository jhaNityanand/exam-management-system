@php
    /** @var array<string, mixed> $seo */
    $seo = is_array($seo ?? null) ? $seo : [];
    $siteName = $siteBrand['name'] ?? ($siteSettings['brand.site_name'] ?? 'Examtube.in');
    $siteTagline = $siteBrand['tagline']
        ?? ($siteSettings['brand.tagline'] ?? 'Practice smarter. Score higher.');
    $defaultDesc = $siteSettings['seo.default_description']
        ?? ($siteBrand['description'] ?? 'Timed mocks, curated questions, mentor blogs, and education news for serious aspirants.');
    $defaultKeywords = $siteSettings['seo.default_keywords']
        ?? 'exams, mock tests, questions, blogs, news, competitive exams, Examtube';

    $pageTitleRaw = $seo['title'] ?? ($seoTitle ?? null);
    $pageTitleRaw = is_string($pageTitleRaw) ? trim($pageTitleRaw) : null;
    $title = $pageTitleRaw
        ? (str_contains($pageTitleRaw, $siteName) ? $pageTitleRaw : ($pageTitleRaw.' | '.$siteName))
        : ($siteName.' — '.$siteTagline);

    $pageDesc = $seo['description'] ?? ($seoDescription ?? null);
    $description = filled($pageDesc)
        ? \Illuminate\Support\Str::limit(trim(strip_tags((string) $pageDesc)), 160, '')
        : \Illuminate\Support\Str::limit($defaultDesc, 160, '');

    $canonical = $seo['canonical'] ?? url()->current();
    if (is_string($canonical) && $canonical !== '' && ! str_starts_with($canonical, 'http')) {
        $canonical = url($canonical);
    }

    $imageType = $seo['image_type'] ?? 'home';
    $siteOg = $siteBrand['og_image_url'] ?? ($siteSettings['seo.og_image'] ?? null);
    $rawImage = $seo['image'] ?? null;
    if (! filled($rawImage) && filled($siteOg) && in_array($imageType, ['home', 'organization', 'default'], true)) {
        $rawImage = $siteOg;
    }
    $ogImage = seo_image(is_string($rawImage) ? $rawImage : null, (string) $imageType);
    $ogImageAlt = $seo['image_alt'] ?? ($pageTitleRaw ?: $siteName);

    $ogTitle = filled($seo['og_title'] ?? null)
        ? (string) $seo['og_title']
        : ($pageTitleRaw ?: $title);
    $ogDescription = filled($seo['og_description'] ?? null)
        ? \Illuminate\Support\Str::limit(trim(strip_tags((string) $seo['og_description'])), 160, '')
        : $description;

    $keywords = $seo['keywords'] ?? $defaultKeywords;
    $robots = $seo['robots'] ?? 'index, follow';
    $ogType = $seo['type'] ?? 'website';
    $locale = str_replace('_', '-', app()->getLocale());
    $twitterHandle = $siteSettings['social.twitter'] ?? ($siteSettings['seo.twitter_handle'] ?? null);
    if (is_string($twitterHandle) && str_starts_with($twitterHandle, 'http')) {
        $twitterHandle = null; // prefer @handle, not full URL
    }

    $faviconUrl = $siteBrand['favicon_url'] ?? null;
    if (! filled($faviconUrl)) {
        $faviconUrl = asset('frontend/images/favicon.svg');
    }
    $logoUrl = $siteBrand['logo_url'] ?? asset('frontend/images/logo.svg');

    $breadcrumbs = $seo['breadcrumbs'] ?? ($breadcrumbs ?? null);
    $schema = $seo['schema'] ?? null;

    $organizationSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $siteName,
        'url' => url('/'),
        'logo' => [
            '@type' => 'ImageObject',
            'url' => $logoUrl,
        ],
        'image' => seo_default_image('organization'),
    ];
    if (! empty($siteSettings['contact.email'] ?? null)) {
        $organizationSchema['email'] = $siteSettings['contact.email'];
    }
    if (! empty($siteSettings['contact.phone'] ?? null)) {
        $organizationSchema['telephone'] = $siteSettings['contact.phone'];
    }
    if (! empty($siteBrand['description'] ?? null)) {
        $organizationSchema['description'] = $siteBrand['description'];
    }

    $websiteSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => $siteName,
        'url' => url('/'),
        'description' => $defaultDesc,
        'publisher' => [
            '@type' => 'Organization',
            'name' => $siteName,
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $logoUrl,
            ],
        ],
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => route('frontend.search').'?q={search_term_string}',
            ],
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

    $webPageSchema = [
        '@context' => 'https://schema.org',
        '@type' => $ogType === 'article' ? 'Article' : ($ogType === 'profile' ? 'ProfilePage' : 'WebPage'),
        'name' => $ogTitle,
        'headline' => $ogTitle,
        'description' => $ogDescription,
        'url' => $canonical,
        'image' => [
            '@type' => 'ImageObject',
            'url' => $ogImage,
            'width' => \App\Support\SeoImage::WIDTH,
            'height' => \App\Support\SeoImage::HEIGHT,
        ],
        'isPartOf' => [
            '@type' => 'WebSite',
            'name' => $siteName,
            'url' => url('/'),
        ],
    ];
@endphp
<title>{{ $title }}</title>
<link rel="icon" href="{{ $faviconUrl }}" type="{{ str_ends_with(parse_url($faviconUrl, PHP_URL_PATH) ?? '', '.svg') ? 'image/svg+xml' : 'image/png' }}">
<link rel="apple-touch-icon" href="{{ $faviconUrl }}">
<meta name="description" content="{{ $description }}">
@if($keywords)
    <meta name="keywords" content="{{ is_array($keywords) ? implode(', ', $keywords) : $keywords }}">
@endif
<meta name="robots" content="{{ $robots }}">
<meta name="author" content="{{ $siteName }}">
<meta name="theme-color" content="#0f766e">
<link rel="canonical" href="{{ $canonical }}">

<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="{{ $locale }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDescription }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:secure_url" content="{{ $ogImage }}">
<meta property="og:image:alt" content="{{ $ogImageAlt }}">
<meta property="og:image:width" content="{{ \App\Support\SeoImage::WIDTH }}">
<meta property="og:image:height" content="{{ \App\Support\SeoImage::HEIGHT }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $ogTitle }}">
<meta name="twitter:description" content="{{ $ogDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">
<meta name="twitter:image:alt" content="{{ $ogImageAlt }}">
@if(filled($twitterHandle))
    <meta name="twitter:site" content="{{ str_starts_with((string) $twitterHandle, '@') ? $twitterHandle : '@'.$twitterHandle }}">
@endif

<script type="application/ld+json">{!! json_encode($organizationSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($websiteSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($webPageSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@if($breadcrumbSchema)
    <script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endif
@if(is_array($schema))
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endif

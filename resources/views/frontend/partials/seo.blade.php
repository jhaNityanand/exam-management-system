@php
    $defaultTitle = $siteSettings['seo.default_title'] ?? ($siteSettings['default_title'] ?? ($siteSettings['site_name'] ?? config('app.name', 'Examtube.in')));
    $defaultDesc = $siteSettings['seo.default_description'] ?? ($siteSettings['default_description'] ?? '');
    $pageTitle = $seo['title'] ?? ($seoTitle ?? null);
    $pageDesc = $seo['description'] ?? ($seoDescription ?? null);
    $title = $pageTitle ? ($pageTitle.' | '.($siteSettings['site_name'] ?? 'Examtube.in')) : $defaultTitle;
    $description = $pageDesc ?: $defaultDesc;
    $canonical = $seo['canonical'] ?? url()->current();
    $ogImage = $seo['image'] ?? ($siteSettings['seo.og_image'] ?? null);
    $ogTitle = $seo['og_title'] ?? $pageTitle ?? $defaultTitle;
    $ogDescription = $seo['og_description'] ?? $description;
    $keywords = $seo['keywords'] ?? ($siteSettings['seo.default_keywords'] ?? null);
@endphp
<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
@if($keywords)
    <meta name="keywords" content="{{ is_array($keywords) ? implode(', ', $keywords) : $keywords }}">
@endif
<link rel="canonical" href="{{ $canonical }}">
<meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDescription }}">
<meta property="og:url" content="{{ $canonical }}">
@if($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $ogTitle }}">
<meta name="twitter:description" content="{{ $ogDescription }}">
@if($ogImage)
    <meta name="twitter:image" content="{{ $ogImage }}">
@endif

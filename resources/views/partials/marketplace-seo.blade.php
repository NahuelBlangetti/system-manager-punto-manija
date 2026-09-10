<title>{{ $seo['title'] }}</title>
<meta name="description" content="{{ $seo['description'] }}">
<meta name="robots" content="{{ $seo['robots'] }}">
<meta name="geo.region" content="{{ $seo['geo_region'] }}">
<meta name="geo.placename" content="{{ $seo['geo_placename'] }}">
<link rel="canonical" href="{{ $seo['canonical'] }}">
<meta property="og:type" content="{{ $seo['og_type'] }}">
<meta property="og:locale" content="{{ $seo['locale'] }}">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:url" content="{{ $seo['og_url'] }}">
<meta property="og:site_name" content="{{ $seo['site_name'] }}">
<meta property="og:image" content="{{ $seo['og_image'] }}">
<meta property="og:image:alt" content="{{ $seo['og_image_alt'] }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
<meta name="twitter:image" content="{{ $seo['og_image'] }}">
@foreach($seo['json_ld'] ?? [] as $graph)
<script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endforeach

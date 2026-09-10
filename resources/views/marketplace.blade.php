<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.marketplace-seo')
    <link rel="icon" type="image/png" href="{{ asset('images/punto-manija-mascot.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    @if(config('services.google_maps.key'))
        <script>
            function onGoogleMapsReady() { window.dispatchEvent(new Event('google-maps-ready')); }
        </script>
        <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&language=es&region=AR&callback=onGoogleMapsReady" defer></script>
    @endif
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            /* Dark mode — local nocturno */
            --surface: #140f0c;
            --surface-container: #221c14;
            --surface-container-low: #2f271c;
            --on-surface: #fbf0d5;
            --on-surface-variant: #d4b8c0;
            --outline: #8b7078;
            --primary: #d63484;
            --primary-bright: #ffb0cc;
            --tertiary: #60d4ff;
            --tertiary-fixed: #004d63;
            --border-color: #fbf0d5;
            --navy: #fbf0d5;
            --error: #ff6b6b;
            --ok: #4ade80;
            --ok-soft: #14532d;
            --shadow: 4px 4px 0 0 var(--primary);
            --shadow-sm: 3px 3px 0 0 var(--primary);
            --border: 2px solid var(--border-color);
            --radius: 0.5rem;
            --radius-lg: 1rem;
            --placeholder-bg: #2a2218;
        }

        * { box-sizing: border-box; }

        [x-cloak] { display: none !important; }

        body {
            background:
                radial-gradient(ellipse 90% 55% at 12% -10%, rgba(214, 52, 132, 0.22), transparent 55%),
                radial-gradient(ellipse 70% 45% at 92% 8%, rgba(96, 212, 255, 0.08), transparent 50%),
                radial-gradient(ellipse 60% 40% at 50% 100%, rgba(214, 52, 132, 0.08), transparent 55%),
                var(--surface);
            color: var(--on-surface);
            font-family: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif;
            color-scheme: dark;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            opacity: 0.045;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        }

        body > * { position: relative; z-index: 1; }

        .font-headline { font-family: 'Anton', ui-sans-serif, sans-serif; letter-spacing: 0.04em; }
        .font-label { font-family: 'Space Grotesk', ui-sans-serif, sans-serif; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; }

        .text-on-surface { color: var(--on-surface); }
        .text-muted { color: var(--on-surface-variant); }
        .text-primary { color: var(--primary); }
        .text-tertiary { color: var(--tertiary); }
        .border-accent { border-color: var(--border-color); }
        .bg-surface-container { background-color: var(--surface-container); }
        .pm-overlay { background: rgba(20, 15, 12, 0.78); backdrop-filter: blur(2px); }
        .pm-sticker-shadow { box-shadow: var(--shadow-sm); }
        .hover-surface:hover { background-color: var(--surface-container-low); }
        .text-outline { color: var(--outline); }
        .text-error { color: var(--error); }
        .text-ok { color: var(--ok); }
        .border-muted { border-color: var(--outline); }
        .bg-disabled { background-color: var(--surface-container); color: var(--outline); }

        @keyframes pm-fade-up {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pm-fade-in {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes pm-pulse-dot {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.25); opacity: 0.7; }
        }
        .pm-reveal {
            animation: pm-fade-up 0.55s ease both;
        }
        .pm-reveal-delay-1 { animation-delay: 0.08s; }
        .pm-reveal-delay-2 { animation-delay: 0.16s; }
        .pm-reveal-delay-3 { animation-delay: 0.24s; }

        /* ── Header ── */
        .pm-header {
            background: color-mix(in srgb, var(--surface) 88%, transparent);
            backdrop-filter: blur(12px);
            border-bottom: var(--border);
            position: sticky;
            top: 0;
            z-index: 40;
        }

        .pm-logo-img {
            height: 2.75rem;
            width: auto;
            object-fit: contain;
        }

        .pm-cart-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            background: var(--surface);
            color: var(--on-surface);
            border: var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }
        .pm-cart-btn:hover {
            transform: translate(2px, 2px);
            box-shadow: none;
        }
        .pm-cart-btn__badge {
            position: absolute;
            top: -0.4rem;
            right: -0.4rem;
            min-width: 1.25rem;
            height: 1.25rem;
            padding: 0 0.3rem;
            border-radius: 9999px;
            background: var(--primary);
            border: 2px solid var(--border-color);
            color: #fff;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.65rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pm-input {
            background: var(--surface);
            border: var(--border);
            border-radius: var(--radius-lg);
            color: var(--on-surface);
            font-family: 'Bricolage Grotesque', sans-serif;
            transition: box-shadow 0.15s ease;
        }
        .pm-input::placeholder { color: var(--outline); }
        .pm-input:focus {
            outline: none;
            box-shadow: var(--shadow-sm);
        }

        /* Google Places autocomplete — dark theme + encima del drawer */
        .pac-container {
            z-index: 10000 !important;
            background: var(--surface-container);
            border: var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-top: 0.35rem;
            font-family: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif;
            color: var(--on-surface);
            padding: 0.25rem 0;
        }
        .pac-container:after {
            display: none;
        }
        .pac-item {
            border-top: 1px solid color-mix(in srgb, var(--outline) 45%, transparent);
            color: var(--on-surface-variant);
            padding: 0.65rem 0.9rem;
            line-height: 1.35;
            cursor: pointer;
        }
        .pac-item:first-child {
            border-top: none;
        }
        .pac-item:hover,
        .pac-item-selected,
        .pac-item-selected:hover {
            background: var(--surface-container-low);
        }
        .pac-item-query {
            color: var(--on-surface);
            font-size: 0.9rem;
        }
        .pac-matched {
            color: var(--primary-bright);
            font-weight: 600;
        }
        .pac-icon {
            display: none;
        }
        .pac-item span:not(.pac-item-query):not(.pac-matched) {
            color: var(--outline);
            font-size: 0.75rem;
        }

        .pm-btn-primary {
            background: var(--primary);
            color: #fff;
            border: var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }
        .pm-btn-primary:hover {
            transform: translate(3px, 3px);
            box-shadow: none;
        }
        .pm-btn-primary:disabled {
            opacity: 0.45;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .pm-btn-secondary {
            background: var(--surface);
            color: var(--on-surface);
            border: var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 600;
            letter-spacing: 0.03em;
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }
        .pm-btn-secondary:hover {
            transform: translate(2px, 2px);
            box-shadow: none;
        }

        /* ── Hero (full-bleed) ── */
        .pm-hero {
            position: relative;
            width: 100%;
            min-height: clamp(22rem, 62vh, 34rem);
            display: grid;
            grid-template-columns: 1fr;
            overflow: hidden;
            border-bottom: var(--border);
            background:
                linear-gradient(105deg, rgba(20, 15, 12, 0.92) 0%, rgba(20, 15, 12, 0.55) 48%, rgba(20, 15, 12, 0.25) 100%),
                radial-gradient(ellipse 80% 70% at 78% 45%, rgba(214, 52, 132, 0.35), transparent 60%),
                var(--surface-container-low);
        }
        @media (min-width: 768px) {
            .pm-hero {
                grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
                align-items: stretch;
            }
        }

        .pm-hero__content {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 2rem 1.25rem 1.5rem;
            max-width: 40rem;
        }
        @media (min-width: 640px) {
            .pm-hero__content { padding: 3rem 2rem 3.25rem; }
        }
        @media (min-width: 1024px) {
            .pm-hero__content {
                padding-left: max(2rem, calc((100vw - 80rem) / 2 + 2rem));
            }
        }

        .pm-hero__visual {
            position: relative;
            min-height: 11rem;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            overflow: hidden;
            padding-bottom: 0.5rem;
        }
        @media (min-width: 768px) {
            .pm-hero__visual {
                min-height: 100%;
                align-items: center;
                justify-content: flex-end;
                padding-right: max(1rem, calc((100vw - 80rem) / 2));
            }
        }

        .pm-hero__visual::before {
            content: '';
            position: absolute;
            width: min(28rem, 90%);
            height: min(28rem, 90%);
            border-radius: 9999px;
            background: radial-gradient(circle, rgba(214, 52, 132, 0.45) 0%, transparent 68%);
            filter: blur(8px);
            animation: pm-pulse-dot 4.5s ease-in-out infinite;
        }

        .pm-hero-mascot {
            position: relative;
            z-index: 1;
            width: min(72vw, 18rem);
            height: auto;
            max-height: 22rem;
            object-fit: contain;
            filter: drop-shadow(6px 10px 0 rgba(214, 52, 132, 0.55));
            animation: pm-fade-up 0.7s ease 0.12s both;
        }
        @media (min-width: 768px) {
            .pm-hero-mascot {
                width: min(42vw, 22rem);
                max-height: none;
            }
        }

        .pm-hero__cta {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            margin-top: 1.5rem;
            width: fit-content;
        }

        /* ── Cards ── */
        .pm-card {
            background: color-mix(in srgb, var(--surface) 92%, var(--surface-container));
            border: var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .pm-card:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0 0 var(--primary);
        }

        .product-card:hover .product-img { transform: scale(1.06); }
        .product-img { transition: transform 0.35s ease; }
        .product-card__media {
            position: relative;
            aspect-ratio: 1;
            overflow: hidden;
            background: var(--placeholder-bg);
            border-bottom: 2px solid var(--border-color);
        }
        .product-card__placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at 30% 20%, rgba(214, 52, 132, 0.18), transparent 55%),
                var(--placeholder-bg);
            color: var(--outline);
        }
        .product-card__price {
            font-family: 'Anton', sans-serif;
            font-size: 1.2rem;
            letter-spacing: 0.02em;
            color: var(--on-surface);
            line-height: 1;
        }

        .pm-pdp {
            display: grid;
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        @media (min-width: 768px) {
            .pm-pdp { grid-template-columns: 1fr 1fr; align-items: start; gap: 2rem; }
        }
        .pm-pdp__media {
            aspect-ratio: 1;
            overflow: hidden;
            background: var(--placeholder-bg);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
        }
        .pm-pdp__media img { width: 100%; height: 100%; object-fit: cover; }
        .pm-pdp__title { font-size: clamp(1.75rem, 4vw, 2.5rem); line-height: 1.05; }

        /* ── Chips ── */
        .pm-chip {
            background: var(--tertiary-fixed);
            color: var(--tertiary);
            border: 1.5px solid var(--tertiary);
            border-radius: 9999px;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .pm-chip--category {
            background: var(--surface-container);
            color: var(--on-surface-variant);
        }

        .pm-chip--stock {
            background: var(--ok-soft);
            color: var(--ok);
            border-color: var(--ok);
        }

        .pm-chip--out {
            background: #3d1515;
            color: var(--error);
            border-color: var(--error);
        }

        .filter-chip {
            background: var(--surface);
            color: var(--on-surface);
            border: var(--border);
            border-radius: 9999px;
            box-shadow: var(--shadow-sm);
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            transition: transform 0.1s ease, box-shadow 0.1s ease, background 0.15s;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .filter-chip:hover {
            transform: translate(2px, 2px);
            box-shadow: none;
        }
        .filter-chip--active {
            background: var(--primary);
            color: #fff;
            box-shadow: var(--shadow);
        }
        .filter-chip--active:hover {
            transform: translate(3px, 3px);
            box-shadow: none;
        }

        .pm-chip-scroll {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.35rem;
            margin-bottom: 2rem;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) transparent;
            -webkit-overflow-scrolling: touch;
        }
        .pm-chip-scroll::-webkit-scrollbar { height: 4px; }
        .pm-chip-scroll::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 9999px;
        }

        /* ── Benefit / step cards ── */
        .pm-benefit-card {
            background: transparent;
            border: none;
            border-radius: 0;
            box-shadow: none;
            padding: 0.25rem 0;
            border-top: 2px solid var(--border-color);
            padding-top: 1.1rem;
        }

        .pm-step-num {
            width: 2rem;
            height: 2rem;
            border-radius: 9999px;
            background: var(--primary);
            border: 2px solid var(--navy);
            color: #fff;
            font-family: 'Anton', sans-serif;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 2px 2px 0 0 var(--navy);
        }

        .pm-divider {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .pm-divider::before,
        .pm-divider::after {
            content: '';
            height: 2px;
            background: var(--border-color);
            flex: 1;
        }
        .pm-divider-dot {
            width: 8px;
            height: 8px;
            border-radius: 9999px;
            background: var(--primary);
            border: 2px solid var(--border-color);
            flex-shrink: 0;
        }

        /* ── Cart drawer ── */
        .pm-drawer {
            background: var(--surface);
            border-left: var(--border);
            box-shadow: -8px 0 0 0 var(--primary);
        }

        .pm-drawer-item {
            background: var(--surface-container-low);
            border: var(--border);
            border-radius: var(--radius);
        }

        .pm-drawer-thumb-fallback {
            background:
                radial-gradient(circle at 30% 20%, rgba(214, 52, 132, 0.2), transparent 60%),
                var(--placeholder-bg);
        }

        /* ── Footer ── */
        .pm-footer {
            background: color-mix(in srgb, var(--surface-container) 90%, #000);
            border-top: var(--border);
        }

        .pm-fab {
            background: var(--primary);
            border: var(--border);
            box-shadow: var(--shadow);
            transition: transform 0.1s ease, box-shadow 0.1s ease, opacity 0.2s ease;
        }
        .pm-fab:hover {
            transform: translate(3px, 3px);
            box-shadow: none;
        }

        .pm-fab-badge {
            background: var(--tertiary);
            border: 2px solid var(--border-color);
            color: #140f0c;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
        }

        /* ── Category browse cards ── */
        .pm-category-card {
            display: flex;
            flex-direction: column;
            background: var(--surface);
            border: var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            height: 100%;
        }
        .pm-category-card:hover {
            transform: translate(-3px, -3px);
            box-shadow: 7px 7px 0 0 var(--primary);
        }
        .pm-category-card__img-wrap {
            position: relative;
            aspect-ratio: 1;
            background: var(--placeholder-bg);
            border-bottom: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .pm-category-card__img-wrap::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(20, 15, 12, 0.55) 0%, transparent 45%);
            opacity: 0;
            transition: opacity 0.25s ease;
            pointer-events: none;
        }
        .pm-category-card:hover .pm-category-card__img-wrap::after { opacity: 1; }
        .pm-category-card__img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.35s ease;
        }
        .pm-category-card:hover .pm-category-card__img { transform: scale(1.07); }
        .pm-category-card__placeholder {
            font-family: 'Anton', sans-serif;
            font-size: 2rem;
            color: var(--primary);
            opacity: 0.55;
            letter-spacing: 0.06em;
        }
        .pm-category-card__body {
            padding: 0.9rem 1rem 1.05rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .pm-category-card__name {
            font-family: 'Anton', sans-serif;
            font-size: 0.98rem;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: var(--on-surface);
            line-height: 1.15;
        }
        .pm-category-card__desc {
            font-size: 0.72rem;
            color: var(--on-surface-variant);
            margin-top: 0.35rem;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex: 1;
        }
        .pm-category-card__meta {
            margin-top: 0.7rem;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--primary);
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .pm-category-card__meta-arrow {
            transition: transform 0.2s ease;
        }
        .pm-category-card:hover .pm-category-card__meta-arrow {
            transform: translateX(3px);
        }

        .pm-brand-title {
            font-family: 'Anton', sans-serif;
            font-size: clamp(3rem, 11vw, 5.5rem);
            line-height: 0.9;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .pm-hero-kicker {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--tertiary);
            margin-bottom: 0.85rem;
        }

        .pm-hero-lead {
            margin-top: 1rem;
            font-size: 1.05rem;
            color: var(--on-surface-variant);
            max-width: 28rem;
            line-height: 1.55;
        }

        .pm-section-head h2 {
            font-size: clamp(1.5rem, 4vw, 2rem);
        }

        @media (prefers-reduced-motion: reduce) {
            .pm-reveal,
            .pm-hero-mascot,
            .pm-category-card,
            .pm-card,
            .product-img,
            .pm-category-card__img {
                animation: none !important;
                transition: none !important;
            }
            .pm-hero__visual::before { animation: none; }
        }
    </style>
</head>
@php
    $storeDisplayName = 'PUNTO MANIJA';
    $catalog = config('store.catalog', []);
    $showHero = ($browsingCategories ?? false) && ! ($productDetail ?? null);
    $categoryImages = $catalog['category_images'] ?? [];
    $shippingRates = \App\Services\Shipping\ShippingSettings::all();
    $shippingConfig = [
        'basePrice' => $shippingRates['base_price'],
        'pricePerKm' => $shippingRates['price_per_km'],
        'maxDistanceKm' => $shippingRates['max_distance_km'],
        'roundingStep' => $shippingRates['rounding_step'],
        'hasMapsKey' => (bool) config('services.google_maps.key'),
        'storeAddress' => config('store.address', ''),
        'storeLat' => (float) config('store.lat'),
        'storeLng' => (float) config('store.lng'),
        'ordersUrl' => route('marketplace.orders.store'),
        'redZones' => \App\Models\RedZone::query()->active()->get(['name', 'polygon'])->values(),
    ];
@endphp
<body class="min-h-screen" x-data="cartStore(@js($shippingConfig))">

{{-- CART DRAWER --}}
<div x-show="open" class="fixed inset-0 z-50 flex justify-end" x-cloak>
    <div class="absolute inset-0 pm-overlay" x-on:click="open = false"></div>
    <div class="relative w-full max-w-md pm-drawer flex flex-col h-full"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full">

        <div class="flex items-center justify-between px-5 py-4 border-b-2 border-accent">
            <h2 class="font-headline text-xl uppercase text-on-surface flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Carrito
                <span class="font-label text-xs normal-case tracking-normal text-muted" x-text="'(' + itemCount + ' ' + (itemCount === 1 ? 'ítem' : 'ítems') + ')'"></span>
            </h2>
            <button x-on:click="open = false" class="p-2 rounded-lg border-2 border-accent hover-surface transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
            <template x-if="items.length === 0">
                <div class="flex flex-col items-center justify-center h-full text-center py-16">
                    <img src="{{ asset('images/punto-manija-mascot.png') }}" alt="" class="w-20 h-20 rounded-xl border-2 border-accent mb-4 object-cover pm-sticker-shadow">
                    <p class="font-headline text-lg uppercase text-on-surface">Tu carrito está vacío</p>
                    <p class="text-muted text-sm mt-1">Agregá productos para comenzar</p>
                </div>
            </template>

            <template x-for="item in items" :key="item.id">
                <div class="flex gap-3 pm-drawer-item p-3">
                    <img :src="item.image || ''" :alt="item.name"
                         class="w-16 h-16 object-cover rounded-lg flex-shrink-0 border-2 border-accent pm-drawer-thumb-fallback"
                         onerror="this.onerror=null; this.removeAttribute('src'); this.classList.add('pm-drawer-thumb-fallback');">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-on-surface leading-tight truncate" x-text="item.name"></p>
                        <p class="text-xs text-muted mt-0.5" x-text="'$' + formatPrice(item.price) + ' c/u'"></p>
                        <div class="flex items-center gap-2 mt-2">
                            <button x-on:click="decrement(item.id)"
                                    class="w-7 h-7 rounded-full border-2 border-accent flex items-center justify-center text-primary hover-surface text-sm font-bold">−</button>
                            <span class="text-sm font-bold w-5 text-center" x-text="item.qty"></span>
                            <button x-on:click="increment(item.id)"
                                    :disabled="item.qty >= item.maxStock"
                                    class="w-7 h-7 rounded-full border-2 border-accent flex items-center justify-center text-primary hover-surface text-sm font-bold disabled:opacity-40">+</button>
                            <span class="ml-auto text-sm font-bold font-headline" x-text="'$' + formatPrice(item.price * item.qty)"></span>
                        </div>
                    </div>
                    <button x-on:click="remove(item.id)" class="self-start text-outline hover:text-error p-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </template>
        </div>

        <div class="border-t-2 border-accent px-5 py-5 space-y-4" x-show="items.length > 0">
            <div>
                <label class="font-label text-xs text-on-surface flex items-center gap-2 mb-2">
                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Entrega
                </label>
                <div class="flex gap-2">
                    <button type="button" x-on:click="deliveryType = 'pickup'"
                            :class="deliveryType === 'pickup' ? 'filter-chip--active' : ''"
                            class="filter-chip flex-1 px-3 py-2 text-[11px]">Retiro en el local</button>
                    <button type="button" x-on:click="deliveryType = 'delivery'"
                            :class="deliveryType === 'delivery' ? 'filter-chip--active' : ''"
                            class="filter-chip flex-1 px-3 py-2 text-[11px]">Envío a domicilio</button>
                </div>
            </div>

            <div class="space-y-2.5">
                <input type="text" x-model="customerName" x-on:input="save()" placeholder="Tu nombre"
                       class="pm-input w-full px-3 py-2.5 text-sm">
                <input type="tel" x-model="customerPhone" x-on:input="save()" placeholder="Tu teléfono"
                       class="pm-input w-full px-3 py-2.5 text-sm">
            </div>

            <template x-if="deliveryType === 'delivery'">
                <div class="space-y-2">
                    <input type="text" x-ref="addressInput" x-model="address"
                           x-on:input="onAddressTyped()"
                           x-init="$nextTick(() => initAutocomplete($el))"
                           x-on:google-maps-ready.window="initAutocomplete($refs.addressInput)"
                           placeholder="Tu dirección (calle y altura)"
                           class="pm-input w-full px-3 py-2.5 text-sm">
                    <p class="text-[11px] text-muted" x-show="!shippingConfig.hasMapsKey">
                        Escribí tu dirección — coordinamos el costo de envío por WhatsApp.
                    </p>
                    <p class="text-[11px] text-muted" x-show="quoting">Calculando distancia…</p>
                    <p class="text-[11px] text-on-surface" x-show="!quoting && distanceKm !== null && !outOfRange">
                        <span x-text="(distanceKm ?? 0).toFixed(1)"></span> km del local — Envío:
                        <span class="font-semibold" x-text="'$' + formatPrice(shippingCost)"></span>
                    </p>
                    <p class="text-[11px] font-semibold text-error" x-show="!quoting && inRedZone">
                        No realizamos envíos a esa zona. Podés elegir "Retiro en el local" o coordinar por WhatsApp.
                    </p>
                    <p class="text-[11px] text-tertiary" x-show="!quoting && !inRedZone && shippingConfig.hasMapsKey && outOfRange && address">
                        Envío a coordinar por WhatsApp (fuera de zona automática o dirección sin confirmar del listado).
                    </p>
                </div>
            </template>

            <p class="text-[11px] text-error" x-show="errorMessage" x-text="errorMessage"></p>

            <div class="flex justify-between font-headline text-lg uppercase">
                <span>Total estimado</span>
                <span class="text-primary" x-text="'$' + formatPrice(total)"></span>
            </div>
            <button type="button" x-on:click="confirmOrder()" :disabled="submitting || (deliveryType === 'delivery' && inRedZone)"
               class="pm-btn-primary flex items-center justify-center gap-2 w-full py-3.5 text-sm">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.091.539 4.057 1.484 5.77L.057 23.273a.75.75 0 00.92.92l5.503-1.427A11.956 11.956 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22.007a10.01 10.01 0 01-5.104-1.399l-.366-.217-3.793.984.999-3.707-.237-.381A9.989 9.989 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10.007-10 10.007z"/>
                </svg>
                <span x-text="submitting ? 'Enviando…' : 'Confirmar pedido y continuar a WhatsApp'"></span>
            </button>
            <button x-on:click="clear()" class="w-full text-xs text-muted hover:text-error font-label">
                Vaciar carrito
            </button>
        </div>
    </div>
</div>

{{-- HEADER --}}
<header class="pm-header">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-[4.25rem] gap-4">
            <a href="/" class="flex items-center gap-3 flex-shrink-0">
                <img src="{{ asset('images/punto-manija-logo.png') }}" alt="Punto Manija" class="pm-logo-img">
            </a>

            <form method="GET" action="{{ ($prettyCategory ?? false) && $activeCategory ? route('marketplace.category', $activeCategory) : url('/') }}" class="flex-1 max-w-md">
                <div class="relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Buscar vodka, fernet, vino, perfume, combo..."
                           class="pm-input w-full pl-10 pr-4 py-2.5 text-sm">
                </div>
            </form>

            <div class="flex items-center gap-2.5 sm:gap-3 flex-shrink-0">
                @if(config('store.whatsapp'))
                    <a href="https://wa.me/{{ config('store.whatsapp') }}" target="_blank"
                       class="hidden sm:flex items-center gap-2 pm-btn-secondary px-4 py-2 text-sm">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.091.539 4.057 1.484 5.77L.057 23.273a.75.75 0 00.92.92l5.503-1.427A11.956 11.956 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22.007a10.01 10.01 0 01-5.104-1.399l-.366-.217-3.793.984.999-3.707-.237-.381A9.989 9.989 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10.007-10 10.007z"/>
                        </svg>
                        WhatsApp
                    </a>
                @endif
                <button type="button" x-on:click="open = true" class="pm-cart-btn" title="Abrir carrito" aria-label="Abrir carrito">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span x-show="itemCount > 0" x-text="itemCount" class="pm-cart-btn__badge" x-cloak></span>
                </button>
            </div>
        </div>
    </div>
</header>

@if($showHero)
<section class="pm-hero">
    <div class="pm-hero__content">
        <p class="pm-hero-kicker pm-reveal">{{ $catalog['tagline'] ?? '' }}</p>
        <h1 class="pm-brand-title pm-reveal pm-reveal-delay-1">
            <span class="text-primary">Punto</span><span class="text-on-surface"> Manija</span>
        </h1>
        <p class="font-headline text-xl sm:text-2xl uppercase text-on-surface mt-4 leading-tight pm-reveal pm-reveal-delay-2">
            {{ $catalog['hero_title'] ?? 'Tu noche empieza acá' }}
        </p>
        <p class="pm-hero-lead pm-reveal pm-reveal-delay-3">
            {{ $catalog['hero_subtitle'] ?? '' }}
        </p>
        <a href="#catalogo" class="pm-btn-primary pm-hero__cta px-5 py-3 text-sm pm-reveal pm-reveal-delay-3">
            Ver catálogo
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </a>
    </div>
    <div class="pm-hero__visual" aria-hidden="true">
        <img src="{{ asset('images/punto-manija-mascot.png') }}" alt="" class="pm-hero-mascot">
    </div>
</section>
@endif

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

@if($productDetail ?? null)
    @php
        $pdpJson = json_encode([
            'id' => $productDetail->id,
            'name' => $productDetail->name,
            'price' => (float) $productDetail->sale_price,
            'maxStock' => $productDetail->stock,
            'image' => $productDetail->image_url ?? '',
        ]);
        $pdpBack = $productDetail->category?->public_url ?? url('/');
    @endphp
    <div class="mb-6">
        <a href="{{ $pdpBack }}" class="inline-flex items-center gap-2 text-sm font-label text-primary hover:underline">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ $productDetail->category?->name ? 'Volver a '.$productDetail->category->name : 'Volver a Punto Manija' }}
        </a>
    </div>
    <article class="pm-pdp">
        <div class="pm-pdp__media">
            @if($productDetail->image_url)
                <img src="{{ $productDetail->image_url }}" alt="{{ $productDetail->name }}">
            @else
                <div class="product-card__placeholder">
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            @endif
        </div>
        <div>
            @if($productDetail->category)
                <a href="{{ $productDetail->category->public_url }}" class="pm-chip pm-chip--category px-2 py-0.5 mb-3 inline-block">{{ $productDetail->category->name }}</a>
            @endif
            <h1 class="font-headline uppercase text-on-surface pm-pdp__title">{{ $productDetail->name }}</h1>
            @if($productDetail->description)
                <p class="text-sm text-muted mt-4 leading-relaxed">{{ $productDetail->description }}</p>
            @endif
            <div class="mt-6 flex items-center justify-between gap-3">
                <span class="product-card__price" style="font-size:1.85rem">${{ number_format($productDetail->sale_price, 0, ',', '.') }}</span>
                @if($productDetail->stock <= 0)
                    <span class="pm-chip pm-chip--out px-2 py-0.5">Sin stock</span>
                @else
                    <span class="pm-chip pm-chip--stock px-2 py-0.5">En stock</span>
                @endif
            </div>
            @if($productDetail->stock > 0)
                <button x-on:click="add({{ $pdpJson }})"
                        class="pm-btn-primary mt-6 w-full flex items-center justify-center gap-2 text-sm py-3.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Agregar al pedido
                </button>
            @else
                <button disabled class="mt-6 w-full text-xs font-label py-3.5 rounded-lg bg-disabled cursor-not-allowed border-2 border-muted">
                    Sin stock
                </button>
            @endif
        </div>
    </article>
    @if(($related ?? collect())->isNotEmpty())
        <div class="pm-section-head mb-5">
            <h2 class="font-headline uppercase text-on-surface">También te puede gustar</h2>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6">
            @foreach($related as $product)
                @include('marketplace.partials.product-card', ['product' => $product])
            @endforeach
        </div>
    @endif
@else

    {{-- Vista principal: explorar por categoría --}}
    @if($browsingCategories)
    <section id="catalogo" class="mb-10 scroll-mt-24">
        <div class="pm-divider mb-8">
            <span class="pm-divider-dot"></span>
            <span class="font-label text-xs text-primary">Catálogo Punto Manija</span>
            <span class="pm-divider-dot"></span>
        </div>
        <div class="pm-section-head flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
            <div>
                <h2 class="font-headline uppercase text-on-surface">¿Qué estás buscando?</h2>
                <p class="text-sm text-muted mt-1">Elegí una categoría y armá tu pedido.</p>
            </div>
            <p class="font-label text-[11px] text-outline">{{ $catalogTotal }} productos · {{ $catalogInStock }} en stock</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-5">
            @foreach($categories as $category)
                @php
                    $thumb = $categoryThumbnails[$category->id] ?? ($categoryImages[$category->name] ?? null);
                @endphp
                <a href="{{ $category->public_url }}"
                   class="pm-category-card group">
                    <div class="pm-category-card__img-wrap">
                        @if($thumb)
                            <img src="{{ $thumb }}" alt="{{ $category->name }}"
                                 class="pm-category-card__img"
                                 loading="lazy"
                                 onerror="this.parentElement.innerHTML='<span class=\'pm-category-card__placeholder\'>PM</span>'">
                        @else
                            <span class="pm-category-card__placeholder">PM</span>
                        @endif
                    </div>
                    <div class="pm-category-card__body">
                        <h3 class="pm-category-card__name">{{ $category->name }}</h3>
                        @if($category->description)
                            <p class="pm-category-card__desc">{{ $category->description }}</p>
                        @endif
                        <span class="pm-category-card__meta">
                            {{ $category->products_count }} productos
                            <span class="pm-category-card__meta-arrow" aria-hidden="true">→</span>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
    @else

    {{-- Navegación secundaria (categoría o búsqueda) --}}
    <div class="mb-6">
        <a href="/" class="inline-flex items-center gap-2 text-sm font-label text-primary hover:underline mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver a Punto Manija
        </a>
    </div>

    {{-- Título contextual --}}
    @if($search || $activeCategory)
    <div class="mb-6">
        @if($search)
            <h1 class="font-headline text-2xl uppercase text-on-surface">Resultados para «{{ $search }}»</h1>
            <p class="text-sm text-muted mt-1">{{ $products->count() }} producto{{ $products->count() !== 1 ? 's' : '' }} encontrado{{ $products->count() !== 1 ? 's' : '' }}</p>
        @elseif($activeCategory)
            <h1 class="font-headline text-2xl uppercase text-on-surface">{{ $activeCategory->name }}</h1>
            @if($activeCategory->description)
                <p class="text-sm text-muted mt-1 max-w-2xl leading-relaxed">{{ $activeCategory->description }}</p>
            @else
                <p class="text-sm text-muted mt-1">{{ $products->count() }} producto{{ $products->count() !== 1 ? 's' : '' }} en esta categoría</p>
            @endif
        @endif
    </div>
    @endif

    {{-- Filtros de categoría (solo dentro de una categoría o búsqueda) --}}
    @if($activeCategory || $search)
    <div class="pm-chip-scroll">
        @foreach($categories as $category)
            <a href="{{ \App\Support\MarketplaceSeo::catalogUrl($category, null) }}"
               class="filter-chip px-4 py-2 text-xs {{ $selectedCategory == $category->id ? 'filter-chip--active' : '' }}">
                {{ $category->name }}
                <span class="ml-1 opacity-70">{{ $category->products_count }}</span>
            </a>
        @endforeach
    </div>
    @endif

    {{-- Grid de productos --}}
    @if($products->isEmpty())
        <div class="text-center py-16 px-6 pm-card">
            <img src="{{ asset('images/punto-manija-mascot.png') }}" alt="" class="w-20 h-20 rounded-xl border-2 border-accent mx-auto mb-4 object-cover pm-sticker-shadow">
            @if($search)
                <p class="font-headline text-xl uppercase text-on-surface">Sin resultados para «{{ $search }}»</p>
                <p class="text-muted text-sm mt-2 max-w-md mx-auto">Probá con otro nombre o revisá otra categoría.</p>
            @elseif($activeCategory)
                <p class="font-headline text-xl uppercase text-on-surface">Sin productos en {{ $activeCategory->name }}</p>
                <p class="text-muted text-sm mt-2">Volvé al catálogo completo o elegí otra categoría.</p>
            @else
                <p class="font-headline text-xl uppercase text-on-surface">Catálogo vacío por ahora</p>
                <p class="text-muted text-sm mt-2">Pronto sumamos más productos.</p>
            @endif
            <a href="/" class="mt-6 inline-block font-label text-xs text-primary underline">Volver a Punto Manija</a>
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-6">
            @foreach($products as $product)
                @include('marketplace.partials.product-card', ['product' => $product])
            @endforeach
        </div>
    @endif

    @endif {{-- fin browsingCategories --}}
@endif {{-- fin productDetail --}}

</main>

{{-- INFO DE TIENDA --}}
<section class="mt-16 pm-footer">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="pm-divider mb-8">
            <span class="pm-divider-dot"></span>
            <span class="font-label text-xs text-primary">Información y atención</span>
            <span class="pm-divider-dot"></span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

            <div class="space-y-4">
                <img src="{{ asset('images/punto-manija-logo.png') }}" alt="Punto Manija" class="h-14 w-auto object-contain">
                @if(!empty($catalog['about']))
                    @if(is_array($catalog['about']))
                        <div class="space-y-2">
                            <h3 class="font-headline text-xl uppercase text-primary leading-tight">
                                {{ $catalog['about']['headline'] ?? '' }}
                            </h3>
                            <p class="text-on-surface text-sm leading-relaxed font-medium">
                                {{ $catalog['about']['lead'] ?? '' }}
                            </p>
                            @if(!empty($catalog['about']['body']))
                                <p class="text-muted text-sm leading-relaxed">
                                    {{ $catalog['about']['body'] }}
                                </p>
                            @endif
                        </div>
                    @else
                        <p class="text-muted text-sm leading-relaxed">{{ $catalog['about'] }}</p>
                    @endif
                @endif
                @if(config('store.whatsapp'))
                <a href="https://wa.me/{{ config('store.whatsapp') }}" target="_blank"
                   class="inline-flex items-center gap-2 pm-btn-primary text-sm px-4 py-2.5">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.091.539 4.057 1.484 5.77L.057 23.273a.75.75 0 00.92.92l5.503-1.427A11.956 11.956 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22.007a10.01 10.01 0 01-5.104-1.399l-.366-.217-3.793.984.999-3.707-.237-.381A9.989 9.989 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10.007-10 10.007z"/>
                    </svg>
                    Consultanos
                </a>
                @endif
            </div>

            <div>
                <h3 class="font-label text-xs text-on-surface mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Horarios
                </h3>
                <div class="space-y-2">
                    @foreach(config('store.schedule') as $slot)
                        <div class="flex justify-between text-sm border-b border-accent/20 pb-1">
                            <span class="text-muted">{{ $slot['label'] }}</span>
                            <span class="{{ $slot['hours'] === 'Cerrado' ? 'text-error' : 'text-on-surface' }} font-semibold">
                                {{ $slot['hours'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="font-label text-xs text-on-surface mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Ubicación
                </h3>
                @if(config('store.address'))
                    <p class="text-sm text-muted mb-3">{{ config('store.address') }}</p>
                    <a href="{{ config('store.maps_url') ?: 'https://maps.google.com/?q=' . urlencode(config('store.address')) }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 text-sm text-primary font-semibold underline">
                        Ver en Google Maps
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a>
                @else
                    <p class="text-sm text-outline italic">Próximamente...</p>
                @endif
                @if(config('store.instagram'))
                    <a href="https://instagram.com/{{ ltrim(config('store.instagram'), '@') }}" target="_blank"
                       class="mt-4 inline-flex items-center gap-2 text-sm text-muted hover:text-primary transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                        {{ config('store.instagram') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>

<footer class="border-t-2 border-accent bg-surface-container">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row items-center justify-between gap-2">
        <span class="font-label text-[10px] text-muted">© {{ date('Y') }} Punto Manija</span>
        <span class="font-label text-[10px] text-muted">Catálogo oficial</span>
    </div>
</footer>

{{-- FAB CARRITO (solo con ítems; el header siempre tiene acceso) --}}
<button x-show="itemCount > 0" x-cloak x-on:click="open = true"
   class="fixed bottom-6 right-6 z-30 pm-fab text-white w-14 h-14 rounded-full flex items-center justify-center sm:hidden"
   title="Abrir carrito"
   aria-label="Abrir carrito">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
    </svg>
    <span x-text="itemCount"
          class="absolute -top-1.5 -right-1.5 pm-fab-badge text-[11px] min-w-[20px] h-5 px-1 rounded-full flex items-center justify-center"></span>
</button>

<script>
function cartStore(shippingConfig) {
    const savedCustomer = JSON.parse(localStorage.getItem('punto_manija_customer') || '{}');

    return {
        open: false,
        items: JSON.parse(localStorage.getItem('punto_manija_cart') || '[]'),
        shippingConfig: shippingConfig || {},

        deliveryType: savedCustomer.deliveryType || 'delivery',
        customerName: savedCustomer.customerName || '',
        customerPhone: savedCustomer.customerPhone || '',
        address: '',
        lat: null,
        lng: null,
        distanceKm: null,
        shippingCost: null,
        outOfRange: false,
        inRedZone: false,
        redZoneName: '',
        quoting: false,
        submitting: false,
        errorMessage: '',
        autocompleteInitialized: false,

        get itemCount() {
            return this.items.reduce((sum, i) => sum + i.qty, 0);
        },

        get total() {
            const subtotal = this.items.reduce((sum, i) => sum + i.price * i.qty, 0);
            const shipping = this.deliveryType === 'delivery' ? (this.shippingCost || 0) : 0;
            return subtotal + shipping;
        },

        add(product) {
            const existing = this.items.find(i => i.id === product.id);
            if (existing) {
                if (existing.qty < existing.maxStock) existing.qty++;
            } else {
                this.items.push({ ...product, qty: 1 });
            }
            this.save();
            this.open = true;
        },

        remove(id) {
            this.items = this.items.filter(i => i.id !== id);
            this.save();
        },

        increment(id) {
            const item = this.items.find(i => i.id === id);
            if (item && item.qty < item.maxStock) item.qty++;
            this.save();
        },

        decrement(id) {
            const item = this.items.find(i => i.id === id);
            if (!item) return;
            if (item.qty <= 1) this.remove(id);
            else { item.qty--; this.save(); }
        },

        clear() {
            this.items = [];
            this.address = '';
            this.lat = null;
            this.lng = null;
            this.distanceKm = null;
            this.shippingCost = null;
            this.outOfRange = false;
            this.errorMessage = '';
            this.save();
        },

        save() {
            localStorage.setItem('punto_manija_cart', JSON.stringify(this.items));
            localStorage.setItem('punto_manija_customer', JSON.stringify({
                deliveryType: this.deliveryType,
                customerName: this.customerName,
                customerPhone: this.customerPhone,
            }));
        },

        formatPrice(value) {
            return Math.round(value).toLocaleString('es-AR');
        },

        initAutocomplete(inputEl) {
            if (this.autocompleteInitialized || typeof google === 'undefined' || !google.maps || !google.maps.places || !inputEl) {
                return;
            }
            this.autocompleteInitialized = true;

            const options = {
                componentRestrictions: { country: 'ar' },
                types: ['address'],
                fields: ['formatted_address', 'geometry'],
            };

            const lat = Number(this.shippingConfig.storeLat);
            const lng = Number(this.shippingConfig.storeLng);
            if (Number.isFinite(lat) && Number.isFinite(lng)) {
                // ~0.18° ≈ 20 km: prioriza Córdoba / zona de cobertura sin bloquear otras ciudades.
                const delta = 0.18;
                options.bounds = new google.maps.LatLngBounds(
                    { lat: lat - delta, lng: lng - delta },
                    { lat: lat + delta, lng: lng + delta },
                );
                options.strictBounds = false;
            }

            const autocomplete = new google.maps.places.Autocomplete(inputEl, options);

            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();
                if (!place.geometry) return;
                this.address = place.formatted_address;
                this.lat = place.geometry.location.lat();
                this.lng = place.geometry.location.lng();

                if (this.checkRedZone()) return;

                this.computeShipping();
            });
        },

        onAddressTyped() {
            this.lat = null;
            this.lng = null;
            this.distanceKm = null;
            this.shippingCost = null;
            this.outOfRange = true;
            this.inRedZone = false;
            this.redZoneName = '';
        },

        // Ray casting: mismo algoritmo que RedZone::containsPoint() en el backend,
        // que es la validación autoritativa al confirmar el pedido.
        pointInPolygon(lat, lng, polygon) {
            let inside = false;

            for (let i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
                const yi = Number(polygon[i].lat);
                const xi = Number(polygon[i].lng);
                const yj = Number(polygon[j].lat);
                const xj = Number(polygon[j].lng);

                const intersects = (yi > lat) !== (yj > lat)
                    && lng < ((xj - xi) * (lat - yi)) / (yj - yi) + xi;

                if (intersects) inside = !inside;
            }

            return inside;
        },

        checkRedZone() {
            const zones = this.shippingConfig.redZones || [];
            const hit = zones.find((zone) => this.pointInPolygon(this.lat, this.lng, zone.polygon || []));

            this.inRedZone = !!hit;
            this.redZoneName = hit ? hit.name : '';

            if (this.inRedZone) {
                this.distanceKm = null;
                this.shippingCost = null;
                this.outOfRange = true;
            }

            return this.inRedZone;
        },

        priceForDistance(km) {
            if (km === null || km > this.shippingConfig.maxDistanceKm) {
                return { cost: null, outOfRange: true };
            }
            const raw = this.shippingConfig.basePrice + this.shippingConfig.pricePerKm * km;
            const step = this.shippingConfig.roundingStep || 1;
            return { cost: Math.round(raw / step) * step, outOfRange: false };
        },

        computeShipping() {
            if (this.inRedZone) {
                this.distanceKm = null;
                this.shippingCost = null;
                this.outOfRange = true;
                return;
            }

            if (this.lat === null || this.lng === null || !this.shippingConfig.storeAddress || typeof google === 'undefined' || !google.maps) {
                this.distanceKm = null;
                this.shippingCost = null;
                this.outOfRange = true;
                return;
            }

            this.quoting = true;
            new google.maps.DistanceMatrixService().getDistanceMatrix({
                origins: [this.shippingConfig.storeAddress],
                destinations: [{ lat: this.lat, lng: this.lng }],
                travelMode: 'DRIVING',
                unitSystem: google.maps.UnitSystem.METRIC,
            }, (response, status) => {
                this.quoting = false;
                const element = status === 'OK' ? response?.rows?.[0]?.elements?.[0] : null;

                if (!element || element.status !== 'OK') {
                    this.distanceKm = null;
                    this.shippingCost = null;
                    this.outOfRange = true;
                    return;
                }

                this.distanceKm = element.distance.value / 1000;
                const quote = this.priceForDistance(this.distanceKm);
                this.shippingCost = quote.cost;
                this.outOfRange = quote.outOfRange;
            });
        },

        async confirmOrder() {
            this.errorMessage = '';

            if (!this.items.length) return;

            if (!this.customerName || !this.customerPhone) {
                this.errorMessage = 'Completá tu nombre y teléfono.';
                return;
            }

            if (this.deliveryType === 'delivery' && !this.address) {
                this.errorMessage = 'Completá tu dirección para el envío.';
                return;
            }

            if (this.deliveryType === 'delivery' && this.inRedZone) {
                this.errorMessage = 'No realizamos envíos a esa dirección. Podés retirar en el local o coordinar por WhatsApp.';
                return;
            }

            this.submitting = true;

            try {
                const response = await fetch(this.shippingConfig.ordersUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        customer_name: this.customerName,
                        customer_phone: this.customerPhone,
                        delivery_type: this.deliveryType,
                        address: this.deliveryType === 'delivery' ? this.address : null,
                        lat: this.deliveryType === 'delivery' ? this.lat : null,
                        lng: this.deliveryType === 'delivery' ? this.lng : null,
                        distance_km: this.deliveryType === 'delivery' ? this.distanceKm : null,
                        items: this.items.map(i => ({ id: i.id, qty: i.qty })),
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    this.errorMessage = data.message || 'No pudimos procesar el pedido, intentá de nuevo.';
                    return;
                }

                window.open(data.whatsapp_url, '_blank');
                this.clear();
                this.open = false;
            } catch (e) {
                this.errorMessage = 'Error de conexión, intentá de nuevo.';
            } finally {
                this.submitting = false;
            }
        },
    };
}
</script>

</body>
</html>

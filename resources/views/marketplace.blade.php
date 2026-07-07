<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punto Manija — Catálogo</title>
    <link rel="icon" type="image/png" href="{{ asset('images/punto-manija-mascot.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,500;12..96,600;12..96,700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            /* Dark mode — local nocturno */
            --surface: #1a1510;
            --surface-container: #2a2415;
            --surface-container-low: #36301e;
            --on-surface: #fbf0d5;
            --on-surface-variant: #debec8;
            --outline: #8b7078;
            --primary: #d63484;
            --primary-bright: #ffb0cc;
            --tertiary: #60d4ff;
            --tertiary-fixed: #004d63;
            --secondary: #c2b4ff;
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
        }

        * { box-sizing: border-box; }

        body {
            background: var(--surface);
            color: var(--on-surface);
            font-family: 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif;
            color-scheme: dark;
        }

        .font-headline { font-family: 'Anton', ui-sans-serif, sans-serif; letter-spacing: 0.04em; }
        .font-label { font-family: 'Space Grotesk', ui-sans-serif, sans-serif; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; }

        .text-on-surface { color: var(--on-surface); }
        .text-muted { color: var(--on-surface-variant); }
        .text-primary { color: var(--primary); }
        .text-tertiary { color: var(--tertiary); }
        .border-accent { border-color: var(--border-color); }
        .bg-surface-container { background-color: var(--surface-container); }
        .pm-overlay { background: rgba(26, 21, 16, 0.75); }
        .pm-sticker-shadow { box-shadow: var(--shadow-sm); }
        .hover-surface:hover { background-color: var(--surface-container-low); }
        .text-outline { color: var(--outline); }
        .text-error { color: var(--error); }
        .text-ok { color: var(--ok); }
        .border-muted { border-color: var(--outline); }
        .bg-disabled { background-color: var(--surface-container); color: var(--outline); }

        /* ── Header ── */
        .pm-header {
            background: var(--surface);
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

        /* ── Hero ── */
        .pm-hero {
            background: var(--surface-container-low);
            border: var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .pm-hero-mascot {
            width: 7rem;
            height: 7rem;
            border: var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            object-fit: cover;
            flex-shrink: 0;
        }

        .pm-stat-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--surface);
            border: var(--border);
            border-radius: var(--radius-lg);
            padding: 0.45rem 0.85rem;
            box-shadow: var(--shadow-sm);
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .pm-stat-pill strong {
            font-family: 'Anton', sans-serif;
            font-size: 1rem;
            letter-spacing: 0.02em;
            color: var(--primary);
        }

        /* ── Cards ── */
        .pm-card {
            background: var(--surface);
            border: var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .pm-card:hover {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0 0 var(--primary);
        }

        .product-card:hover .product-img { transform: scale(1.05); }
        .product-img { transition: transform 0.3s ease; }

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

        /* ── Benefit / step cards ── */
        .pm-benefit-card {
            background: var(--surface);
            border: var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            padding: 1rem 1.1rem;
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

        /* ── Footer ── */
        .pm-footer {
            background: var(--surface-container);
            border-top: var(--border);
        }

        .pm-fab {
            background: var(--primary);
            border: var(--border);
            box-shadow: var(--shadow);
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }
        .pm-fab:hover {
            transform: translate(3px, 3px);
            box-shadow: none;
        }

        .pm-fab-badge {
            background: var(--tertiary);
            border: 2px solid var(--border-color);
            color: #1a1510;
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
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0 0 var(--primary);
        }
        .pm-category-card__img-wrap {
            aspect-ratio: 1;
            background: var(--surface-container);
            border-bottom: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .pm-category-card__img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .pm-category-card__placeholder {
            font-family: 'Anton', sans-serif;
            font-size: 2rem;
            color: var(--primary);
            opacity: 0.5;
        }
        .pm-category-card__body {
            padding: 0.85rem 1rem 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .pm-category-card__name {
            font-family: 'Anton', sans-serif;
            font-size: 0.95rem;
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
            margin-top: 0.65rem;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--primary);
        }

        .pm-brand-title {
            font-family: 'Anton', sans-serif;
            font-size: clamp(2.5rem, 8vw, 4rem);
            line-height: 0.95;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }
    </style>
</head>
<body class="min-h-screen" x-data="cartStore()" x-cloak>
@php
    $storeDisplayName = 'PUNTO MANIJA';
    $catalog = config('store.catalog', []);
    $showHero = $browsingCategories ?? (! $search && ! $activeCategory);
    $categoryImages = $catalog['category_images'] ?? [];
@endphp

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
                    <img :src="item.image" :alt="item.name"
                         class="w-16 h-16 object-cover rounded-lg flex-shrink-0 border-2 border-accent bg-surface-container"
                         onerror="this.src='https://placehold.co/64x64/f8edd2/584048?text=?'">
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
            <div class="flex justify-between font-headline text-lg uppercase">
                <span>Total estimado</span>
                <span class="text-primary" x-text="'$' + formatPrice(total)"></span>
            </div>
            <a :href="whatsappUrl()" target="_blank"
               class="pm-btn-primary flex items-center justify-center gap-2 w-full py-3.5 text-sm">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.091.539 4.057 1.484 5.77L.057 23.273a.75.75 0 00.92.92l5.503-1.427A11.956 11.956 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22.007a10.01 10.01 0 01-5.104-1.399l-.366-.217-3.793.984.999-3.707-.237-.381A9.989 9.989 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10.007-10 10.007z"/>
                </svg>
                Pedir por WhatsApp
            </a>
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

            <form method="GET" action="/" class="flex-1 max-w-md">
                @if($selectedCategory)
                    <input type="hidden" name="category" value="{{ $selectedCategory }}">
                @endif
                <div class="relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Buscar vodka, fernet, vino, perfume, combo..."
                           class="pm-input w-full pl-10 pr-4 py-2.5 text-sm">
                </div>
            </form>

            <div class="flex items-center gap-3 flex-shrink-0">
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
            </div>
        </div>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- HERO (solo en vista principal) --}}
    @if($showHero)
    <section class="pm-hero mb-10">
        <div class="p-6 md:p-8 lg:p-10">
            <div class="flex flex-col md:flex-row items-start md:items-center gap-6 md:gap-8">
                <img src="{{ asset('images/punto-manija-mascot.png') }}" alt="Punto Manija" class="pm-hero-mascot hidden sm:block">
                <div class="flex-1">
                    <p class="font-label text-xs text-tertiary mb-3">{{ $catalog['tagline'] ?? '' }}</p>
                    <h1 class="pm-brand-title">
                        <span class="text-primary">Punto</span><span class="text-on-surface"> Manija</span>
                    </h1>
                    <p class="font-headline text-xl sm:text-2xl uppercase text-on-surface mt-3 leading-tight">
                        {{ $catalog['hero_title'] ?? 'Tu noche empieza acá' }}
                    </p>
                    <p class="mt-3 text-base text-muted max-w-2xl leading-relaxed">
                        {{ $catalog['hero_subtitle'] ?? '' }}
                    </p>
                    <div class="flex flex-wrap gap-3 mt-5">
                        <span class="pm-stat-pill"><strong>{{ $catalogTotal }}</strong> productos</span>
                        @if(config('store.whatsapp'))
                        <a href="https://wa.me/{{ config('store.whatsapp') }}" target="_blank" class="pm-stat-pill hover-surface transition-colors">
                            <svg class="w-4 h-4 text-ok" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.091.539 4.057 1.484 5.77L.057 23.273a.75.75 0 00.92.92l5.503-1.427A11.956 11.956 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22.007a10.01 10.01 0 01-5.104-1.399l-.366-.217-3.793.984.999-3.707-.237-.381A9.989 9.989 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10.007-10 10.007z"/></svg>
                            Pedí por WhatsApp
                        </a>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </section>
    @endif

    {{-- Vista principal: explorar por categoría --}}
    @if($browsingCategories)
    <section class="mb-10">
        <div class="pm-divider mb-8">
            <span class="pm-divider-dot"></span>
            <span class="font-label text-xs text-primary">Catálogo Punto Manija</span>
            <span class="pm-divider-dot"></span>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-6">
            <div>
                <h2 class="font-headline text-2xl uppercase text-on-surface">¿Qué estás buscando?</h2>
                <p class="text-sm text-muted mt-1">Elegí una categoría para ver los {{ $catalogTotal }} productos de Punto Manija.</p>
            </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 md:gap-5">
            @foreach($categories as $category)
                @php
                    $thumb = $categoryThumbnails[$category->id] ?? ($categoryImages[$category->name] ?? null);
                @endphp
                <a href="{{ request()->fullUrlWithQuery(['category' => $category->id, 'search' => null]) }}"
                   class="pm-category-card group">
                    <div class="pm-category-card__img-wrap">
                        @if($thumb)
                            <img src="{{ $thumb }}" alt="{{ $category->name }}"
                                 class="pm-category-card__img group-hover:scale-105 transition-transform duration-300"
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
                        <span class="pm-category-card__meta">{{ $category->products_count }} productos →</span>
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
            <h2 class="font-headline text-2xl uppercase text-on-surface">Resultados para «{{ $search }}»</h2>
            <p class="text-sm text-muted mt-1">{{ $products->count() }} producto{{ $products->count() !== 1 ? 's' : '' }} encontrado{{ $products->count() !== 1 ? 's' : '' }}</p>
        @elseif($activeCategory)
            <h2 class="font-headline text-2xl uppercase text-on-surface">{{ $activeCategory->name }}</h2>
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
    <div class="flex flex-wrap gap-2 mb-8">
        @foreach($categories as $category)
            <a href="{{ request()->fullUrlWithQuery(['category' => $category->id, 'search' => null, 'page' => null]) }}"
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
                @php $productJson = json_encode([
                    'id'       => $product->id,
                    'name'     => $product->name,
                    'price'    => (float) $product->sale_price,
                    'maxStock' => $product->stock,
                    'image'    => $product->image_url ?? '',
                ]) @endphp
                <div class="product-card pm-card overflow-hidden flex flex-col">

                    <div class="aspect-square overflow-hidden bg-surface-container border-b-2 border-accent">
                        @if($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                 class="product-img w-full h-full object-cover"
                                 onerror="this.src='https://placehold.co/400x400/f8edd2/584048?text=Sin+imagen'">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-outline">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                    </div>

                    <div class="p-3 flex flex-col flex-1">
                        @if($product->category)
                            <span class="pm-chip pm-chip--category px-2 py-0.5 mb-1.5">{{ $product->category->name }}</span>
                        @endif
                        <h3 class="text-sm font-semibold text-on-surface leading-tight">{{ $product->name }}</h3>
                        @if($product->description)
                            <p class="text-[11px] text-muted mt-1 line-clamp-2 leading-snug flex-1">{{ $product->description }}</p>
                        @endif

                        <div class="mt-2 flex items-center justify-between gap-2">
                            <span class="font-headline text-lg text-on-surface">
                                ${{ number_format($product->sale_price, 0, ',', '.') }}
                            </span>
                            @if($product->stock <= 0)
                                <span class="pm-chip pm-chip--out px-2 py-0.5">Sin stock</span>
                            @else
                                <span class="pm-chip pm-chip--stock px-2 py-0.5">En stock</span>
                            @endif
                        </div>

                        @if($product->stock > 0)
                            <button x-on:click="add({{ $productJson }})"
                                    class="pm-btn-primary mt-3 w-full flex items-center justify-center gap-2 text-xs py-2.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                Agregar
                            </button>
                        @else
                            <button disabled class="mt-3 w-full text-xs font-label py-2.5 rounded-lg bg-disabled cursor-not-allowed border-2 border-muted">
                                Sin stock
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @endif {{-- fin browsingCategories --}}

    {{-- Cómo comprar --}}
    @if($showHero && !empty($catalog['how_to_buy']))
    <section class="mt-14">
        <div class="pm-divider mb-8">
            <span class="pm-divider-dot"></span>
            <span class="font-label text-xs text-primary">Cómo comprar</span>
            <span class="pm-divider-dot"></span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($catalog['how_to_buy'] as $step)
            <div class="pm-benefit-card flex gap-3 items-start">
                <span class="pm-step-num">{{ $step['step'] }}</span>
                <div>
                    <h3 class="font-headline text-sm uppercase text-on-surface">{{ $step['title'] }}</h3>
                    <p class="text-sm text-muted mt-1">{{ $step['text'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

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

{{-- FAB CARRITO --}}
<button x-on:click="open = true"
   class="fixed bottom-6 right-6 z-30 pm-fab text-white w-14 h-14 rounded-full flex items-center justify-center"
   title="Abrir carrito">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
    </svg>
    <span x-show="itemCount > 0" x-text="itemCount"
          class="absolute -top-1.5 -right-1.5 pm-fab-badge text-[11px] min-w-[20px] h-5 px-1 rounded-full flex items-center justify-center"></span>
</button>

<script>
function cartStore() {
    return {
        open: false,
        items: JSON.parse(localStorage.getItem('punto_manija_cart') || '[]'),

        get itemCount() {
            return this.items.reduce((sum, i) => sum + i.qty, 0);
        },

        get total() {
            return this.items.reduce((sum, i) => sum + i.price * i.qty, 0);
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
            this.save();
        },

        save() {
            localStorage.setItem('punto_manija_cart', JSON.stringify(this.items));
        },

        formatPrice(value) {
            return Math.round(value).toLocaleString('es-AR');
        },

        whatsappUrl() {
            const waNumber = '{{ config("store.whatsapp") }}';
            if (!this.items.length || !waNumber) return '#';
            let msg = 'Hola *{{ $storeDisplayName }}*! 👋 Me gustaría hacer el siguiente pedido:\n\n';
            this.items.forEach(item => {
                msg += `• ${item.name} (x${item.qty}) — $${this.formatPrice(item.price * item.qty)}\n`;
            });
            msg += `\n💰 *Total estimado: $${this.formatPrice(this.total)}*`;
            msg += '\n\n¿Podría confirmar disponibilidad? ¡Muchas gracias!';
            return 'https://wa.me/' + waNumber + '?text=' + encodeURIComponent(msg);
        },
    };
}
</script>

</body>
</html>

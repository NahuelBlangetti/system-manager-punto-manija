<?php

return [
    'name' => env('APP_NAME', 'Punto Manija'),
    'whatsapp' => env('STORE_WHATSAPP', ''),
    'address' => env('STORE_ADDRESS', ''),
    'maps_url' => env('STORE_MAPS_URL', ''),
    'instagram' => env('STORE_INSTAGRAM', ''),

    // Coordenadas del local (bias del autocomplete de direcciones + referencia).
    // Default: Av. Fuerza Aérea 3423, Córdoba.
    'lat' => filled(env('STORE_LAT')) ? (float) env('STORE_LAT') : -31.4128,
    'lng' => filled(env('STORE_LNG')) ? (float) env('STORE_LNG') : -64.2705,

    // Valores por defecto / fallback de tarifas de envío. El administrador puede
    // overridearlos desde /admin/tarifas-envio (tabla settings). Fórmula:
    // base_price + price_per_km * distancia, redondeado a rounding_step. Más allá de
    // max_distance_km no se cotiza automático (se coordina por WhatsApp).
    'shipping' => [
        'base_price' => (float) env('SHIPPING_BASE_PRICE', 800),
        'price_per_km' => (float) env('SHIPPING_PRICE_PER_KM', 300),
        'max_distance_km' => (float) env('SHIPPING_MAX_DISTANCE_KM', 15),
        'rounding_step' => (float) env('SHIPPING_ROUNDING_STEP', 50),
    ],

    'schedule' => [
        ['label' => 'Lunes a Sábado', 'hours' => env('STORE_HOURS_WEEKDAY', '9:00 - 18:00')],
        ['label' => 'Domingo',         'hours' => env('STORE_HOURS_SUNDAY', 'Cerrado')],
    ],

    'catalog' => [
        'tagline' => 'Córdoba · Ruta 20 · Abierto cuando la noche lo pide',
        'hero_title' => 'Tu noche empieza acá',
        'hero_subtitle' => 'Elegí tu categoría, armá el carrito y mandanos un WhatsApp. Sin vueltas, sin filas, directo a tu juntada.',
        'about' => [
            'headline' => 'Más que un kiosco',
            'lead' => 'Somos Punto Manija: el lugar donde la previa se arma sola y el after también.',
            'body' => 'Combos listos, tragos de verdad, perfumes que viste en TikTok y todo lo que le falta a tu noche. Pasá, elegí en el catálogo y escribinos — te respondemos al toque.',
        ],
        'how_to_buy' => [
            ['step' => '1', 'title' => 'Elegí tu categoría', 'text' => 'Vodkas, fernet, combos, vinos, perfumes árabes y más.'],
            ['step' => '2', 'title' => 'Armá el carrito', 'text' => 'Agregá botellas, combos o adicionales para tu juntada.'],
            ['step' => '3', 'title' => 'Pedí por WhatsApp', 'text' => 'Te confirmamos stock, precio y coordinamos entrega o retiro.'],
        ],
        'category_order' => [
            'COMBOS MANIJA',
            'VODKAS',
            'FERNET',
            'GIN',
            'WHISKY',
            'LICORES',
            'RON Y TEKILAS',
            'CHAMPAGNE Y ESPUMANTES',
            'CERVEZAS',
            'VINOS BLANCOS, TINTOS Y ROSADOS',
            'PERFUMES ARABES',
            'PUCHOS Y VAPES',
            'CRISTALERIA Y REGALERIA',
            'ADICIONALES A TU COMBO',
            'MAYORISTA',
        ],
        'category_images' => [
            'PERFUMES ARABES' => 'https://cdn.pedix.app/O90JGczQKWz2K0XzRqS7/categories/1748161082433.png?size=250&quality=80',
            'MAYORISTA' => 'https://cdn.pedix.app/O90JGczQKWz2K0XzRqS7/categories/1778547933405.png?size=250&quality=80',
            'COMBOS MANIJA' => 'https://cdn.pedix.app/O90JGczQKWz2K0XzRqS7/categories/1736489066939.png?size=250&quality=80',
            'VODKAS' => 'https://cdn.pedix.app/O90JGczQKWz2K0XzRqS7/categories/1736489099299.png?size=250&quality=80',
            'FERNET' => 'https://cdn.pedix.app/O90JGczQKWz2K0XzRqS7/categories/1736489136891.png?size=250&quality=80',
            'GIN' => 'https://cdn.pedix.app/O90JGczQKWz2K0XzRqS7/categories/1736489118906.png?size=250&quality=80',
            'LICORES' => 'https://cdn.pedix.app/O90JGczQKWz2K0XzRqS7/categories/1736491090112.png?size=250&quality=80',
            'WHISKY' => 'https://cdn.pedix.app/O90JGczQKWz2K0XzRqS7/categories/1736489128514.png?size=250&quality=80',
            'PUCHOS Y VAPES' => 'https://cdn.pedix.app/O90JGczQKWz2K0XzRqS7/categories/1737511196744.png?size=250&quality=80',
            'CHAMPAGNE Y ESPUMANTES' => 'https://cdn.pedix.app/O90JGczQKWz2K0XzRqS7/categories/1736578320901.png?size=250&quality=80',
            'CERVEZAS' => 'https://cdn.pedix.app/O90JGczQKWz2K0XzRqS7/categories/1736489146459.png?size=250&quality=80',
            'RON Y TEKILAS' => 'https://cdn.pedix.app/O90JGczQKWz2K0XzRqS7/categories/1736491136226.png?size=250&quality=80',
            'VINOS BLANCOS, TINTOS Y ROSADOS' => 'https://cdn.pedix.app/O90JGczQKWz2K0XzRqS7/categories/1736643327981.png?size=250&quality=80',
            'CRISTALERIA Y REGALERIA' => 'https://cdn.pedix.app/O90JGczQKWz2K0XzRqS7/categories/1736491165138.png?size=250&quality=80',
            'ADICIONALES A TU COMBO' => 'https://cdn.pedix.app/O90JGczQKWz2K0XzRqS7/categories/1736491148890.png?size=250&quality=80',
        ],
    ],
];

<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

final class MarketplaceSeo
{
    /**
     * Metadatos del catálogo público. Las URLs con filtros GET se marcan
     * noindex; /categoria/{slug} sin búsqueda es indexable.
     *
     * @return array{
     *     title: string,
     *     description: string,
     *     canonical: string,
     *     robots: string,
     *     og_type: string,
     *     og_url: string,
     *     og_image: string,
     *     og_image_alt: string,
     *     site_name: string,
     *     locale: string,
     *     geo_region: string,
     *     geo_placename: string,
     *     json_ld: list<array<string, mixed>>
     * }
     */
    public static function forCatalog(?string $search = null, ?Category $activeCategory = null, ?string $condition = null, bool $prettyCategory = false): array
    {
        $siteName = (string) config('store.catalog.site_name', config('store.name'));
        $homeTitle = (string) config('store.catalog.page_title', $siteName);
        $homeDescription = (string) (
            config('store.catalog.meta_description')
            ?: config('store.catalog.hero_line')
            ?: config('store.catalog.hero_subtitle')
            ?: $homeTitle
        );

        $search = is_string($search) ? trim($search) : '';
        $condition = is_string($condition) ? trim($condition) : '';
        $isSearch = $search !== '';
        $isCondition = in_array($condition, ['new', 'used'], true);
        $queryCategory = $activeCategory !== null && ! $prettyCategory;
        $noindex = $isSearch || $isCondition || $queryCategory;

        $title = $homeTitle;
        $description = $homeDescription;

        if ($isSearch) {
            $title = 'Búsqueda: '.$search.' | '.$siteName;
        } elseif ($condition === 'used') {
            $title = 'Usadas | '.$siteName;
        } elseif ($condition === 'new') {
            $title = 'Nuevas | '.$siteName;
        } elseif ($activeCategory) {
            $title = $activeCategory->name.' | '.$siteName;
            if (filled($activeCategory->description)) {
                $description = (string) Str::limit(trim(html_entity_decode(strip_tags($activeCategory->description))), 160);
            }
        }

        $canonical = url('/');
        if ($prettyCategory && $activeCategory && ! $noindex) {
            $canonical = route('marketplace.category', $activeCategory);
        }

        return [
            'title' => $title,
            'description' => (string) Str::limit($description, 160),
            'canonical' => $canonical,
            'robots' => $noindex ? 'noindex, follow' : 'index, follow',
            'og_type' => 'website',
            'og_url' => $canonical,
            'og_image' => asset((string) config('store.catalog.og_image')),
            'og_image_alt' => $siteName,
            'site_name' => $siteName,
            'locale' => 'es_AR',
            'geo_region' => (string) config('store.catalog.geo_region', 'AR-X'),
            'geo_placename' => (string) config('store.catalog.geo_placename', 'Córdoba'),
            'json_ld' => $noindex ? [] : array_values(array_filter([self::localBusiness()])),
        ];
    }

    public static function catalogUrl(?Category $category = null, ?string $search = null, ?string $condition = null): string
    {
        $query = [];
        $search = is_string($search) ? trim($search) : '';
        if ($search !== '') {
            $query['search'] = $search;
        }
        if (in_array($condition, ['new', 'used'], true)) {
            $query['condition'] = $condition;
        }

        $base = ($category && filled($category->slug))
            ? route('marketplace.category', $category)
            : url('/');

        return $query === [] ? $base : $base.'?'.http_build_query($query);
    }

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     canonical: string,
     *     robots: string,
     *     og_type: string,
     *     og_url: string,
     *     og_image: string,
     *     og_image_alt: string,
     *     site_name: string,
     *     locale: string,
     *     geo_region: string,
     *     geo_placename: string,
     *     json_ld: list<array<string, mixed>>
     * }
     */
    public static function forProduct(Product $product): array
    {
        $siteName = (string) config('store.catalog.site_name', config('store.name'));
        $description = (string) Str::limit(
            trim(html_entity_decode(strip_tags((string) ($product->description ?: $product->name)))),
            160,
        );
        $canonical = $product->public_url;
        $image = $product->image_url ?: asset((string) config('store.catalog.og_image'));

        return [
            'title' => $product->name.' | '.$siteName,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => 'index, follow',
            'og_type' => 'product',
            'og_url' => $canonical,
            'og_image' => $image,
            'og_image_alt' => $product->name,
            'site_name' => $siteName,
            'locale' => 'es_AR',
            'geo_region' => (string) config('store.catalog.geo_region', 'AR-X'),
            'geo_placename' => (string) config('store.catalog.geo_placename', 'Córdoba'),
            'json_ld' => array_values(array_filter([
                self::productGraph($product, $description, $canonical, $image),
                self::breadcrumbList($product, $canonical),
            ])),
        ];
    }

    /**
     * @return list<array{loc: string, lastmod?: string, changefreq: string, priority: string}>
     */
    public static function sitemapUrls(): array
    {
        $urls = [[
            'loc' => url('/'),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ]];

        $categories = Category::query()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->whereHas('products', fn ($q) => $q->where('active', true))
            ->orderBy('name')
            ->get(['slug', 'updated_at']);

        foreach ($categories as $category) {
            $entry = [
                'loc' => route('marketplace.category', $category),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];

            if ($category->updated_at) {
                $entry['lastmod'] = $category->updated_at->toAtomString();
            }

            $urls[] = $entry;
        }

        $products = Product::query()
            ->where('active', true)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderBy('id')
            ->get(['slug', 'updated_at']);

        foreach ($products as $product) {
            $entry = [
                'loc' => route('marketplace.product', $product->slug),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];

            if ($product->updated_at) {
                $entry['lastmod'] = $product->updated_at->toAtomString();
            }

            $urls[] = $entry;
        }

        return $urls;
    }

    public static function robotsTxt(): string
    {
        return implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            '',
            'Sitemap: '.url('/sitemap.xml'),
            '',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function localBusiness(): array
    {
        $siteName = (string) config('store.catalog.site_name', config('store.name'));
        $graph = [
            '@context' => 'https://schema.org',
            '@type' => (string) config('store.catalog.schema_type', 'Store'),
            'name' => $siteName,
            'url' => url('/'),
            'image' => asset((string) config('store.catalog.og_image')),
            'description' => (string) config('store.catalog.meta_description', config('store.catalog.hero_line')),
        ];

        $address = trim((string) config('store.address'));
        if ($address !== '') {
            $graph['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressLocality' => (string) config('store.catalog.geo_placename', 'Córdoba'),
                'addressCountry' => 'AR',
            ];
        }

        $lat = config('store.lat');
        $lng = config('store.lng');
        if (is_numeric($lat) && is_numeric($lng)) {
            $graph['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
            ];
        }

        $phone = self::telephone();
        if ($phone !== null) {
            $graph['telephone'] = $phone;
        }

        $sameAs = self::sameAs();
        if ($sameAs !== []) {
            $graph['sameAs'] = $sameAs;
        }

        $hours = self::openingHours();
        if ($hours !== []) {
            $graph['openingHours'] = $hours;
        }

        return $graph;
    }

    private static function telephone(): ?string
    {
        $raw = trim((string) (config('store.phone') ?: config('store.whatsapp')));
        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if (strlen($digits) < 8) {
            return null;
        }

        return '+'.$digits;
    }

    /**
     * @return list<string>
     */
    private static function sameAs(): array
    {
        $instagram = trim((string) config('store.instagram'));
        if ($instagram === '') {
            return [];
        }

        if (str_starts_with($instagram, 'http://') || str_starts_with($instagram, 'https://')) {
            return [$instagram];
        }

        $handle = ltrim($instagram, '@');

        return ['https://www.instagram.com/'.$handle.'/'];
    }

    /**
     * @return list<string>
     */
    private static function openingHours(): array
    {
        $hours = [];

        foreach (config('store.schedule', []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = mb_strtolower((string) ($row['label'] ?? ''));
            $raw = trim((string) ($row['hours'] ?? ''));
            if ($raw === '' || in_array(mb_strtolower($raw), ['cerrado', 'abierto'], true)) {
                continue;
            }

            $days = match (true) {
                str_contains($label, 'domingo') => 'Su',
                str_contains($label, 'lunes') && str_contains($label, 'viernes') => 'Mo-Fr',
                str_contains($label, 'lunes') && str_contains($label, 'sábado') => 'Mo-Sa',
                str_contains($label, 'sábado') => 'Sa',
                default => 'Mo-Fr',
            };

            foreach (preg_split('/\s*\|\s*/', $raw) ?: [] as $range) {
                $normalized = self::normalizeHourRange($range);
                if ($normalized !== null) {
                    $hours[] = $days.' '.$normalized;
                }
            }
        }

        return $hours;
    }

    /**
     * @return array<string, mixed>
     */
    private static function productGraph(Product $product, string $description, string $canonical, string $image): array
    {
        $siteName = (string) config('store.catalog.site_name', config('store.name'));
        $graph = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $description,
            'image' => $image,
            'url' => $canonical,
            'brand' => [
                '@type' => 'Brand',
                'name' => $siteName,
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => $canonical,
                'priceCurrency' => 'ARS',
                'price' => number_format((float) $product->sale_price, 2, '.', ''),
                'availability' => ((int) $product->stock > 0)
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'seller' => [
                    '@type' => 'Organization',
                    'name' => $siteName,
                ],
            ],
        ];

        if (filled($product->sku)) {
            $graph['sku'] = $product->sku;
        }

        if ($product->relationLoaded('category') && $product->category) {
            $graph['category'] = $product->category->name;
        }

        return $graph;
    }

    /**
     * @return array<string, mixed>
     */
    private static function breadcrumbList(Product $product, string $canonical): array
    {
        $siteName = (string) config('store.catalog.site_name', config('store.name'));
        $items = [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => $siteName,
                'item' => url('/'),
            ],
        ];
        $position = 2;

        if ($product->relationLoaded('category') && $product->category?->slug) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $product->category->name,
                'item' => route('marketplace.category', $product->category),
            ];
            $position++;
        }

        $items[] = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $product->name,
            'item' => $canonical,
        ];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    private static function normalizeHourRange(string $range): ?string
    {
        if (! preg_match('/(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})/', $range, $match)) {
            return null;
        }

        return sprintf('%02d:%s-%02d:%s', (int) $match[1], $match[2], (int) $match[3], $match[4]);
    }
}

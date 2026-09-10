<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Support\MarketplaceSeo;
use Tests\TestCase;

class MarketplaceSeoTest extends TestCase
{
    public function test_home_is_indexable_with_og_image(): void
    {
        $seo = MarketplaceSeo::forCatalog();

        $this->assertSame('index, follow', $seo['robots']);
        $this->assertSame(url('/'), $seo['canonical']);
        $this->assertSame(config('store.catalog.page_title'), $seo['title']);
        $this->assertStringContainsString('punto-manija-icon.jpg', $seo['og_image']);
        $this->assertSame('Córdoba', $seo['geo_placename']);
        $this->assertSame('ConvenienceStore', $seo['json_ld'][0]['@type']);
        $this->assertContains('Mo-Fr 09:00-18:00', $seo['json_ld'][0]['openingHours']);
        $this->assertContains('Sa 09:00-18:00', $seo['json_ld'][0]['openingHours']);
        $this->assertSame('Avenida Fuerza Aérea 3423, Córdoba', $seo['json_ld'][0]['address']['streetAddress']);
    }

    public function test_search_and_category_views_are_noindex_with_contextual_title(): void
    {
        $search = MarketplaceSeo::forCatalog('vodka');
        $this->assertSame('noindex, follow', $search['robots']);
        $this->assertSame('Búsqueda: vodka | Punto Manija', $search['title']);
        $this->assertSame([], $search['json_ld']);

        $category = new Category([
            'name' => 'Fernet',
            'slug' => 'fernet',
            'description' => 'Fernet y combos para la previa.',
        ]);
        $categorySeo = MarketplaceSeo::forCatalog(null, $category);
        $this->assertSame('noindex, follow', $categorySeo['robots']);
        $this->assertSame('Fernet | Punto Manija', $categorySeo['title']);
        $this->assertSame('Fernet y combos para la previa.', $categorySeo['description']);

        $pretty = MarketplaceSeo::forCatalog(null, $category, null, true);
        $this->assertSame('index, follow', $pretty['robots']);
        $this->assertStringContainsString('/categoria/fernet', $pretty['canonical']);
        $this->assertSame('ConvenienceStore', $pretty['json_ld'][0]['@type']);
    }

    public function test_product_page_is_indexable(): void
    {
        $product = new Product([
            'name' => 'Fernet Branca 750',
            'slug' => 'fernet-branca-750',
            'description' => 'El clásico para la previa.',
            'sale_price' => 12500,
            'stock' => 8,
        ]);

        $seo = MarketplaceSeo::forProduct($product);

        $this->assertSame('Fernet Branca 750 | Punto Manija', $seo['title']);
        $this->assertSame('product', $seo['og_type']);
        $this->assertSame('index, follow', $seo['robots']);
        $this->assertStringContainsString('/producto/fernet-branca-750', $seo['canonical']);
        $this->assertSame('Product', $seo['json_ld'][0]['@type']);
    }
}

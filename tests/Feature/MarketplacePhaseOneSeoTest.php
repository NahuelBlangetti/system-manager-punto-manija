<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplacePhaseOneSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_disallows_admin(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin', false)
            ->assertSee('Sitemap:', false)
            ->assertSee('/sitemap.xml', false);
    }

    public function test_home_includes_localbusiness_json_ld(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('application/ld+json', false)
            ->assertSee('ConvenienceStore', false)
            ->assertSee('Avenida Fuerza Aérea 3423', false);
    }

    public function test_sitemap_lists_home_category_and_product_urls(): void
    {
        $category = Category::create([
            'name' => 'Vodkas',
            'slug' => 'vodkas',
            'description' => 'Vodkas y combos.',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Vodka demo',
            'sale_price' => 1000,
            'stock' => 5,
            'active' => true,
        ]);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<urlset', $xml);
        $this->assertStringContainsString(url('/'), $xml);
        $this->assertStringContainsString('/categoria/vodkas', $xml);
        $this->assertStringContainsString('/producto/'.$product->slug, $xml);
        $this->assertStringNotContainsString('?category=', $xml);
    }

    public function test_home_exposes_meta_canonical_and_og_image(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Punto Manija | Bebidas, combos y perfumes en Córdoba</title>', false)
            ->assertSee('name="description"', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('punto-manija-icon.jpg', false)
            ->assertSee('content="index, follow"', false);
    }

    public function test_legacy_category_query_redirects_to_pretty_url(): void
    {
        $category = Category::create([
            'name' => 'Vodkas',
            'slug' => 'vodkas',
            'description' => 'Vodkas y combos.',
        ]);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Vodka demo',
            'sale_price' => 1000,
            'stock' => 5,
            'active' => true,
        ]);

        $this->get('/?category='.$category->id)
            ->assertStatus(301)
            ->assertRedirect(route('marketplace.category', $category));
    }

    public function test_pretty_category_is_indexable_with_h1(): void
    {
        $category = Category::create([
            'name' => 'Vodkas',
            'slug' => 'vodkas',
            'description' => 'Vodkas y combos.',
        ]);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Vodka demo',
            'sale_price' => 1000,
            'stock' => 5,
            'active' => true,
        ]);

        $this->get('/categoria/vodkas')
            ->assertOk()
            ->assertSee('<title>Vodkas | Punto Manija</title>', false)
            ->assertSee('content="index, follow"', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('/categoria/vodkas', false)
            ->assertSee('<h1 class="font-headline text-2xl uppercase text-on-surface">Vodkas</h1>', false);
    }

    public function test_product_page_is_indexable(): void
    {
        $category = Category::create([
            'name' => 'Vodkas',
            'slug' => 'vodkas',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Vodka Smirnoff',
            'description' => 'Botella de 750 ml.',
            'sale_price' => 8500,
            'stock' => 4,
            'active' => true,
        ]);

        $this->get('/producto/'.$product->slug)
            ->assertOk()
            ->assertSee('<title>Vodka Smirnoff | Punto Manija</title>', false)
            ->assertSee('content="product"', false)
            ->assertSee('"@type":"Product"', false)
            ->assertSee('Agregar al pedido', false);
    }

    public function test_admin_login_sends_noindex(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }
}

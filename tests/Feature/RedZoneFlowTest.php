<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\RedZone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedZoneFlowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function product(): Product
    {
        $category = Category::create([
            'name' => 'Bebidas',
            'slug' => 'bebidas',
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Cerveza',
            'sale_price' => 1000,
            'stock' => 100,
            'active' => true,
        ]);
    }

    /**
     * Cuadrado alrededor del local (config('store.lat')/('store.lng')): [-31.41, -64.27].
     */
    private function squareZone(bool $active = true): RedZone
    {
        return RedZone::create([
            'name' => 'Zona sin cobertura',
            'polygon' => [
                ['lat' => -31.40, 'lng' => -64.28],
                ['lat' => -31.40, 'lng' => -64.26],
                ['lat' => -31.42, 'lng' => -64.26],
                ['lat' => -31.42, 'lng' => -64.28],
            ],
            'active' => $active,
        ]);
    }

    public function test_red_zone_pages_render_without_errors(): void
    {
        $admin = $this->admin();
        $zone = $this->squareZone();

        $this->actingAs($admin)
            ->get('/admin/red-zones')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/red-zones/create')
            ->assertOk();

        $this->actingAs($admin)
            ->get("/admin/red-zones/{$zone->id}/edit")
            ->assertOk();
    }

    public function test_contains_point_detects_point_inside_and_outside_polygon(): void
    {
        $zone = $this->squareZone();

        $this->assertTrue($zone->containsPoint(-31.41, -64.27));
        $this->assertFalse($zone->containsPoint(-31.50, -64.27));
    }

    public function test_marketplace_order_is_blocked_when_address_falls_in_active_red_zone(): void
    {
        $this->squareZone();
        $product = $this->product();

        $response = $this->postJson(route('marketplace.orders.store'), [
            'customer_name' => 'Cliente Test',
            'customer_phone' => '3511234567',
            'delivery_type' => 'delivery',
            'address' => 'Dirección dentro de la zona roja',
            'lat' => -31.41,
            'lng' => -64.27,
            'items' => [
                ['id' => $product->id, 'qty' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('web_orders', 0);
    }

    public function test_marketplace_order_is_allowed_when_address_is_outside_red_zones(): void
    {
        $this->squareZone();
        $product = $this->product();

        $response = $this->postJson(route('marketplace.orders.store'), [
            'customer_name' => 'Cliente Test',
            'customer_phone' => '3511234567',
            'delivery_type' => 'delivery',
            'address' => 'Dirección fuera de la zona roja',
            'lat' => -31.50,
            'lng' => -64.27,
            'distance_km' => 5,
            'items' => [
                ['id' => $product->id, 'qty' => 1],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('web_orders', 1);
    }

    public function test_marketplace_order_is_allowed_when_red_zone_is_inactive(): void
    {
        $this->squareZone(active: false);
        $product = $this->product();

        $response = $this->postJson(route('marketplace.orders.store'), [
            'customer_name' => 'Cliente Test',
            'customer_phone' => '3511234567',
            'delivery_type' => 'delivery',
            'address' => 'Dirección dentro del polígono pero zona inactiva',
            'lat' => -31.41,
            'lng' => -64.27,
            'distance_km' => 5,
            'items' => [
                ['id' => $product->id, 'qty' => 1],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('web_orders', 1);
    }
}

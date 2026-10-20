<?php

namespace Tests\Feature;

use App\Filament\Pages\CrearVenta;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\PointPayment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\MercadoPago\PointPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class MercadoPagoPointFlowTest extends TestCase
{
    use RefreshDatabase;

    private const TERMINAL = 'NEWLAND_N950__N950NCB801293324';

    private const ORDER_ID = 'ORD01JQ4S4KY8HWQ6NA5PXB65B3D3';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.mercadopago.access_token' => 'APP_USR-test',
            'services.mercadopago.point_terminal_id' => self::TERMINAL,
            'services.mercadopago.webhook_secret' => null,
        ]);
    }

    private function product(int $stock = 10): Product
    {
        $category = Category::create(['name' => 'Bebidas', 'slug' => 'bebidas']);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Fernet 750',
            'sale_price' => 12000,
            'stock' => $stock,
        ]);
    }

    private function openRegister(User $user): CashRegister
    {
        return CashRegister::create([
            'user_id' => $user->id,
            'opening_amount' => 0,
            'opened_at' => now(),
            'status' => 'open',
        ]);
    }

    private function order(string $status, array $payment = []): array
    {
        return [
            'id' => self::ORDER_ID,
            'type' => 'point',
            'status' => $status,
            'status_detail' => $status === 'processed' ? 'accredited' : $status,
            'transactions' => [
                'payments' => [array_merge(['id' => 'PAY01', 'amount' => '24000.00', 'status' => $status], $payment)],
            ],
        ];
    }

    private string $mpStatus = 'created';

    private array $mpPayment = [];

    private function fakeMercadoPago(string $getStatus, array $payment = []): void
    {
        $this->mpStatus = $getStatus;
        $this->mpPayment = $payment;

        Http::fake([
            'api.mercadopago.com/v1/orders/'.self::ORDER_ID.'/cancel' => fn () => Http::response($this->order('canceled')),
            'api.mercadopago.com/v1/orders/'.self::ORDER_ID => fn () => Http::response($this->order($this->mpStatus, $this->mpPayment)),
            'api.mercadopago.com/v1/orders' => fn () => Http::response($this->order('created'), 201),
        ]);
    }

    private function setMercadoPagoStatus(string $status, array $payment = []): void
    {
        $this->mpStatus = $status;
        $this->mpPayment = $payment;
    }

    public function test_point_sale_is_sent_to_terminal_and_registered_only_when_paid(): void
    {
        $user = User::factory()->admin()->create();
        $register = $this->openRegister($user);
        $product = $this->product();

        $this->fakeMercadoPago('at_terminal');
        $this->actingAs($user);

        $page = Livewire::test(CrearVenta::class)
            ->call('addToCart', $product->id)
            ->call('addToCart', $product->id)
            ->set('paymentMethod', 'point')
            ->set('printTicket', false)
            ->call('confirmSale');

        $payment = PointPayment::sole();
        $page->assertSet('pointPaymentId', $payment->id);
        $this->assertSame(self::ORDER_ID, $payment->mp_order_id);
        $this->assertSame(0, Sale::count());
        $this->assertSame(10, $product->fresh()->stock);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.mercadopago.com/v1/orders'
            && $request['type'] === 'point'
            && $request['transactions']['payments'][0]['amount'] === '24000.00'
            && $request['config']['point']['terminal_id'] === self::TERMINAL
            && $request->hasHeader('X-Idempotency-Key', $payment->external_reference));

        // Todavía en el posnet: sigue esperando.
        $page->call('checkPointPayment')->assertSet('pointPaymentId', $payment->id);
        $this->assertSame(0, Sale::count());

        // Aprobado.
        $this->setMercadoPagoStatus('processed', [
            'payment_method' => ['type' => 'debit_card', 'id' => 'debvisa', 'installments' => 1, 'card' => ['last_digits' => '4321']],
        ]);
        $page->call('checkPointPayment')
            ->assertSet('pointPaymentId', null)
            ->assertSet('cartItems', []);

        $sale = Sale::sole();
        $this->assertSame('point', $sale->payment_method);
        $this->assertSame($register->id, $sale->cash_register_id);
        $this->assertEquals(24000, (float) $sale->total);
        $this->assertStringContainsString('****4321', $sale->notes);
        $this->assertSame(8, $product->fresh()->stock);

        $payment->refresh();
        $this->assertTrue($payment->isPaid());
        $this->assertSame($sale->id, $payment->sale_id);
        $this->assertSame('debit_card', $payment->payment_type);
        $this->assertEquals(24000, $register->fresh()->pointSalesTotal());
        $this->assertEquals(0, $register->fresh()->calculateExpectedAmount());
    }

    public function test_sale_is_registered_once_even_if_webhook_and_polling_both_arrive(): void
    {
        $user = User::factory()->admin()->create();
        $this->openRegister($user);
        $product = $this->product();
        $this->fakeMercadoPago('processed');

        $payment = app(PointPaymentService::class)->start([
            'payment_method' => 'point', 'subtotal' => 12000, 'discount' => 0, 'promo_discount' => 0,
            'total' => 12000, 'notes' => '',
            'items' => [['product_id' => $product->id, 'name' => $product->name, 'unit' => 'u', 'unit_price' => 12000, 'quantity' => 1, 'subtotal' => 12000]],
        ], CashRegister::sole(), $user->id);

        $this->postJson('/webhooks/mercadopago?data.id='.self::ORDER_ID.'&type=order', [
            'type' => 'order', 'action' => 'order.processed', 'data' => ['id' => self::ORDER_ID],
        ])->assertOk();

        app(PointPaymentService::class)->sync($payment->fresh());
        app(PointPaymentService::class)->handleOrderNotification(self::ORDER_ID);

        $this->assertSame(1, Sale::count());
        $this->assertSame(9, $product->fresh()->stock);
    }

    public function test_paid_sale_is_registered_even_if_stock_ran_out_meanwhile(): void
    {
        $user = User::factory()->admin()->create();
        $this->openRegister($user);
        $product = $this->product(stock: 1);
        $this->fakeMercadoPago('processed');

        $payment = app(PointPaymentService::class)->start([
            'payment_method' => 'point', 'subtotal' => 12000, 'discount' => 0, 'promo_discount' => 0,
            'total' => 12000, 'notes' => null,
            'items' => [['product_id' => $product->id, 'name' => $product->name, 'unit' => 'u', 'unit_price' => 12000, 'quantity' => 1, 'subtotal' => 12000]],
        ], CashRegister::sole(), $user->id);

        $product->update(['stock' => 0]);

        app(PointPaymentService::class)->sync($payment);

        $this->assertSame(1, Sale::count());
        $this->assertSame(-1, $product->fresh()->stock);
    }

    public function test_rejected_payment_keeps_cart_and_registers_nothing(): void
    {
        $user = User::factory()->admin()->create();
        $this->openRegister($user);
        $product = $this->product();
        $this->fakeMercadoPago('failed');
        $this->actingAs($user);

        Livewire::test(CrearVenta::class)
            ->call('addToCart', $product->id)
            ->set('paymentMethod', 'point')
            ->call('confirmSale')
            ->call('checkPointPayment')
            ->assertSet('pointPaymentId', null)
            ->assertCount('cartItems', 1);

        $this->assertSame(0, Sale::count());
        $this->assertSame('failed', PointPayment::sole()->status);
    }

    public function test_cancel_while_order_not_yet_taken_by_terminal(): void
    {
        $user = User::factory()->admin()->create();
        $this->openRegister($user);
        $product = $this->product();
        $this->fakeMercadoPago('created');
        $this->actingAs($user);

        Livewire::test(CrearVenta::class)
            ->call('addToCart', $product->id)
            ->set('paymentMethod', 'point')
            ->call('confirmSale')
            ->call('cancelPointPayment')
            ->assertSet('pointPaymentId', null)
            ->assertCount('cartItems', 1);

        $this->assertSame('canceled', PointPayment::sole()->status);
        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/cancel'));
    }

    public function test_webhook_rejects_invalid_signature_when_secret_is_set(): void
    {
        config(['services.mercadopago.webhook_secret' => 'secret']);

        $this->postJson('/webhooks/mercadopago?data.id='.self::ORDER_ID.'&type=order', ['type' => 'order'], [
            'x-signature' => 'ts=1704908010,v1=bad',
            'x-request-id' => 'req-1',
        ])->assertUnauthorized();

        $ts = '1704908010';
        $hash = hash_hmac('sha256', 'id:'.strtolower(self::ORDER_ID).";request-id:req-1;ts:{$ts};", 'secret');

        $this->postJson('/webhooks/mercadopago?data.id='.self::ORDER_ID.'&type=order', ['type' => 'order'], [
            'x-signature' => "ts={$ts},v1={$hash}",
            'x-request-id' => 'req-1',
        ])->assertOk();
    }

    public function test_pages_render_with_point_enabled(): void
    {
        $admin = User::factory()->admin()->create();
        $this->openRegister($admin);

        $this->actingAs($admin)->get('/admin/posnet-mercado-pago')
            ->assertOk()
            ->assertSee(self::TERMINAL);

        $product = $this->product();

        Livewire::actingAs($admin)->test(CrearVenta::class)
            ->call('addToCart', $product->id)
            ->assertSee('Posnet MP');
    }
}

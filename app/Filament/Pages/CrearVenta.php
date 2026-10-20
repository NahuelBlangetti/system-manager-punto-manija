<?php

namespace App\Filament\Pages;

use App\Enums\ProductDiscountType;
use App\Enums\PromotionType;
use App\Models\CashRegister;
use App\Models\PointPayment;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\User;
use App\Services\MercadoPago\MercadoPagoException;
use App\Services\MercadoPago\PointClient;
use App\Services\MercadoPago\PointPaymentService;
use App\Services\Sales\SaleRegistrar;
use App\Services\Stock\ComboStockService;
use App\Services\Tickets\SaleTicketEscPosBuilder;
use App\Support\EscPosPrint;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;

class CrearVenta extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'Nueva Venta';

    protected static ?string $title = 'Nueva Venta';

    protected static ?string $slug = 'nueva-venta';

    protected static string|\UnitEnum|null $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.crear-venta';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && ! $user->isDelivery();
    }

    public string $productQuery = '';

    public array $searchResults = [];

    public array $cartItems = [];

    public string $paymentMethod = '';

    public string $notes = '';

    public string $discountType = 'fixed';

    public string $discountValue = '';

    public bool $printTicket = true;

    public ?string $lastSaleNumber = null;

    /** Cobro en curso en el posnet Mercado Pago Point (bloquea el POS). */
    public ?int $pointPaymentId = null;

    public ?string $pointError = null;

    public function getSubtotal(): float
    {
        return collect($this->cartItems)->sum('subtotal');
    }

    public function getDiscountAmount(): float
    {
        if (! Auth::user()?->isAdmin()) {
            return 0.0;
        }

        $value = (float) $this->discountValue;

        if ($value <= 0) {
            return 0.0;
        }

        $subtotal = $this->getSubtotal();

        $amount = $this->discountType === ProductDiscountType::Percentage->value
            ? $subtotal * ($value / 100)
            : $value;

        return round(min(max($amount, 0), $subtotal), 2);
    }

    public function getPromoDiscount(): float
    {
        if (empty($this->cartItems)) {
            return 0.0;
        }

        $promotions = Promotion::where('active', true)->get()->filter->isCurrentlyActive();

        if ($promotions->isEmpty()) {
            return 0.0;
        }

        $products = Product::whereIn('id', collect($this->cartItems)->pluck('product_id'))
            ->get()
            ->keyBy('id');

        $discount = 0.0;

        foreach ($this->cartItems as $item) {
            $product = $products->get($item['product_id']);

            if (! $product) {
                continue;
            }

            $promotion = $promotions->first(fn (Promotion $promotion) => $promotion->appliesToProduct($product));

            if (! $promotion) {
                continue;
            }

            $discount += match ($promotion->type) {
                PromotionType::Percentage => $item['subtotal'] * ((float) $promotion->percentage_value / 100),
                PromotionType::TwoForOne => intdiv($item['quantity'], 2) * $item['unit_price'],
            };
        }

        return round($discount, 2);
    }

    public function getTotal(): float
    {
        return round($this->getSubtotal() - $this->getDiscountAmount() - $this->getPromoDiscount(), 2);
    }

    public function getCartCount(): int
    {
        return collect($this->cartItems)->sum('quantity');
    }

    #[Computed]
    public function hasCashRegisterOpen(): bool
    {
        return CashRegister::where('status', 'open')->exists();
    }

    #[Computed]
    public function pointEnabled(): bool
    {
        $client = app(PointClient::class);

        return $client->isConfigured() && filled($client->terminalId());
    }

    #[Computed]
    public function pointPayment(): ?PointPayment
    {
        return $this->pointPaymentId ? PointPayment::find($this->pointPaymentId) : null;
    }

    public function updatedProductQuery(): void
    {
        $query = trim($this->productQuery);

        if (strlen($query) < 2) {
            $this->searchResults = [];

            return;
        }

        $this->searchResults = $this->findProducts($query);
    }

    public function addProduct(): void
    {
        $query = trim($this->productQuery);

        if ($query === '') {
            return;
        }

        $exact = Product::where('active', true)
            ->where(fn ($q) => $q->where('barcode', $query)->orWhere('sku', $query))
            ->first();

        if ($exact) {
            $this->resetProductSearch();
            $this->addToCart($exact->id);
            $this->dispatch('focus-product-search');

            return;
        }

        if (strlen($query) >= 2 && empty($this->searchResults)) {
            $this->searchResults = $this->findProducts($query);
        }

        if (count($this->searchResults) === 1) {
            $productId = $this->searchResults[0]['id'];
            $this->resetProductSearch();
            $this->addToCart($productId);
            $this->dispatch('focus-product-search');

            return;
        }

        Notification::make()
            ->title('Producto no encontrado')
            ->body(count($this->searchResults) > 1
                ? 'Hay varios resultados. Seleccioná uno de la lista.'
                : "No hay productos para \"{$query}\".")
            ->warning()
            ->send();
    }

    private function findProducts(string $query): array
    {
        return Product::where('active', true)
            ->where(fn ($q) => $q
                ->where('name', 'like', "%{$query}%")
                ->orWhere('sku', 'like', "%{$query}%")
                ->orWhere('barcode', 'like', "%{$query}%")
            )
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'sale_price', 'stock', 'unit', 'sku', 'barcode', 'is_combo'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sale_price' => $product->sale_price,
                'stock' => ComboStockService::availableStock($product),
                'unit' => $product->unit,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
            ])
            ->toArray();
    }

    private function resetProductSearch(): void
    {
        $this->productQuery = '';
        $this->searchResults = [];
    }

    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);

        if (! $product) {
            return;
        }

        $existingIndex = collect($this->cartItems)
            ->search(fn ($item) => $item['product_id'] === $productId);

        if ($product->sale_price <= 0) {
            Notification::make()
                ->title("Precio en $0: {$product->name}")
                ->body('Este producto no tiene precio de venta cargado. Verificá el producto antes de vender.')
                ->warning()
                ->persistent()
                ->send();
        }

        if ($existingIndex !== false) {
            $newQty = $this->cartItems[$existingIndex]['quantity'] + 1;
            $availableStock = $this->cartItems[$existingIndex]['stock'];

            if ($newQty > $availableStock) {
                Notification::make()
                    ->title("Stock insuficiente: {$product->name}")
                    ->body("Solo hay {$availableStock} {$product->unit} disponibles.")
                    ->warning()
                    ->send();

                return;
            }

            $unitPrice = $product->unitPriceForQuantity($newQty);

            $this->cartItems[$existingIndex]['quantity'] = $newQty;
            $this->cartItems[$existingIndex]['unit_price'] = $unitPrice;
            $this->cartItems[$existingIndex]['subtotal'] = round($newQty * $unitPrice, 2);
        } else {
            $availableStock = ComboStockService::availableStock($product);

            if ($availableStock <= 0) {
                Notification::make()
                    ->title("Sin stock: {$product->name}")
                    ->body('Este producto no tiene unidades disponibles.')
                    ->warning()
                    ->send();

                return;
            }

            $unitPrice = $product->unitPriceForQuantity(1);

            $this->cartItems[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'unit' => $product->unit,
                'base_price' => (float) $product->sale_price,
                'unit_price' => $unitPrice,
                'quantity' => 1,
                'subtotal' => $unitPrice,
                'stock' => $availableStock,
            ];
        }

        $this->resetProductSearch();
        $this->dispatch('focus-product-search');

        Notification::make()
            ->title("{$product->name} agregado")
            ->success()
            ->duration(1500)
            ->send();
    }

    public function removeFromCart(int $index): void
    {
        unset($this->cartItems[$index]);
        $this->cartItems = array_values($this->cartItems);
    }

    public function updateQuantity(int $index, mixed $quantity): void
    {
        $qty = (int) $quantity;

        if ($qty <= 0) {
            $this->removeFromCart($index);

            return;
        }

        $availableStock = $this->cartItems[$index]['stock'];

        if ($qty > $availableStock) {
            Notification::make()
                ->title('Stock insuficiente')
                ->body("Solo hay {$availableStock} {$this->cartItems[$index]['unit']} disponibles.")
                ->warning()
                ->send();

            return;
        }

        $product = Product::find($this->cartItems[$index]['product_id']);
        $unitPrice = $product ? $product->unitPriceForQuantity($qty) : $this->cartItems[$index]['unit_price'];

        $this->cartItems[$index]['quantity'] = $qty;
        $this->cartItems[$index]['unit_price'] = $unitPrice;
        $this->cartItems[$index]['subtotal'] = round($qty * $unitPrice, 2);
    }

    public function clearCart(): void
    {
        if ($this->pointPaymentId) {
            return;
        }

        $this->cartItems = [];
        $this->paymentMethod = '';
        $this->notes = '';
        $this->discountType = 'fixed';
        $this->discountValue = '';
        $this->lastSaleNumber = null;
    }

    public function confirmSale(): void
    {
        if ($this->pointPaymentId) {
            return;
        }

        if (empty($this->cartItems)) {
            Notification::make()->title('El carrito está vacío')->warning()->send();

            return;
        }

        if (empty($this->paymentMethod)) {
            Notification::make()->title('Seleccioná un método de pago')->warning()->send();

            return;
        }

        $cashRegister = CashRegister::where('status', 'open')->first();

        if (! $cashRegister) {
            Notification::make()
                ->title('No hay caja abierta')
                ->body('Debés abrir una caja antes de registrar una venta.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        if ($this->paymentMethod === 'point') {
            $this->startPointPayment($cashRegister);

            return;
        }

        try {
            $sale = app(SaleRegistrar::class)->register($this->buildSalePayload(), $cashRegister, Auth::id());
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('No se pudo registrar la venta')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->afterSaleRegistered($sale);
    }

    /**
     * Envía el total al posnet. La venta se registra recién cuando Mercado
     * Pago confirma el pago (ver checkPointPayment y el webhook).
     */
    private function startPointPayment(CashRegister $cashRegister): void
    {
        foreach ($this->cartItems as $item) {
            $product = Product::find($item['product_id']);
            $available = $product ? ComboStockService::availableStock($product) : 0;

            if ($available < $item['quantity']) {
                Notification::make()
                    ->title('No se pudo enviar el cobro')
                    ->body("Stock insuficiente para \"{$item['name']}\". Disponible: {$available} {$item['unit']}.")
                    ->danger()
                    ->send();

                return;
            }
        }

        try {
            $payment = app(PointPaymentService::class)->start($this->buildSalePayload(), $cashRegister, Auth::id());
        } catch (MercadoPagoException $e) {
            Notification::make()
                ->title('No se pudo enviar el cobro al posnet')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $this->pointPaymentId = $payment->id;
        $this->pointError = null;
        unset($this->pointPayment);
    }

    /**
     * Polling desde la vista mientras el cliente paga en el posnet.
     */
    public function checkPointPayment(): void
    {
        $payment = $this->pointPayment;

        if (! $payment) {
            $this->pointPaymentId = null;

            return;
        }

        try {
            $payment = app(PointPaymentService::class)->sync($payment);
            $this->pointError = null;
        } catch (\Throwable $e) {
            report($e);
            $this->pointError = $e->getMessage();

            return;
        } finally {
            unset($this->pointPayment);
        }

        if ($payment->isPaid() && $payment->sale) {
            $this->pointPaymentId = null;
            $this->afterSaleRegistered($payment->sale, $payment);

            return;
        }

        if ($payment->isFinished()) {
            $this->pointPaymentId = null;

            Notification::make()
                ->title($payment->statusLabel())
                ->body('La venta no se registró. Podés reintentar o elegir otro medio de pago.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function cancelPointPayment(): void
    {
        $payment = $this->pointPayment;

        if (! $payment) {
            $this->pointPaymentId = null;

            return;
        }

        try {
            $payment = app(PointPaymentService::class)->cancel($payment);
        } catch (MercadoPagoException $e) {
            Notification::make()->title('No se pudo cancelar')->body($e->getMessage())->warning()->send();

            return;
        } finally {
            unset($this->pointPayment);
        }

        if ($payment->isPaid()) {
            // Se aprobó justo antes de cancelar: la venta ya está registrada.
            $this->checkPointPayment();

            return;
        }

        $this->pointPaymentId = null;

        Notification::make()->title('Cobro cancelado en el posnet')->warning()->send();
    }

    private function buildSalePayload(): array
    {
        $subtotal = $this->getSubtotal();
        $discountAmount = $this->getDiscountAmount();
        $promoDiscount = $this->getPromoDiscount();

        return [
            'payment_method' => $this->paymentMethod,
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'promo_discount' => $promoDiscount,
            'total' => round($subtotal - $discountAmount - $promoDiscount, 2),
            'notes' => $this->notes,
            'items' => collect($this->cartItems)
                ->map(fn (array $item) => collect($item)->only(['product_id', 'name', 'unit', 'unit_price', 'quantity', 'subtotal'])->all())
                ->values()
                ->all(),
        ];
    }

    private function afterSaleRegistered(Sale $sale, ?PointPayment $pointPayment = null): void
    {
        $this->lastSaleNumber = $sale->sale_number;
        $this->cartItems = [];
        $this->paymentMethod = '';
        $this->notes = '';
        $this->discountType = 'fixed';
        $this->discountValue = '';

        if ($this->printTicket) {
            $ticket = app(SaleTicketEscPosBuilder::class)->build($sale);
            EscPosPrint::dispatch($this, $ticket);
        }

        $body = 'El stock fue actualizado automáticamente.';

        if ($pointPayment) {
            $body = collect([
                'Cobrado con posnet Mercado Pago',
                $pointPayment->paymentTypeLabel(),
                $pointPayment->card_last_digits ? "****{$pointPayment->card_last_digits}" : null,
            ])->filter()->implode(' · ').'. '.$body;
        }

        Notification::make()
            ->title("¡Venta {$sale->sale_number} registrada!")
            ->body($body)
            ->success()
            ->persistent()
            ->send();
    }
}

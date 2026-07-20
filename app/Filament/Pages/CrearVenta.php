<?php

namespace App\Filament\Pages;

use App\Enums\ProductDiscountType;
use App\Filament\Actions\ConfigurePrinterAction;
use App\Models\CashRegister;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\Stock\ComboStockService;
use App\Services\Tickets\SaleTicketEscPosBuilder;
use App\Support\EscPosPrint;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            ConfigurePrinterAction::make(),
        ];
    }

    public string $productQuery = '';

    public array $searchResults = [];

    public array $cartItems = [];

    public string $paymentMethod = '';

    public string $notes = '';

    public string $discountType = 'fixed';

    public string $discountValue = '';

    public ?string $lastSaleNumber = null;

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

    public function getTotal(): float
    {
        return round($this->getSubtotal() - $this->getDiscountAmount(), 2);
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
        $this->cartItems = [];
        $this->paymentMethod = '';
        $this->notes = '';
        $this->discountType = 'fixed';
        $this->discountValue = '';
        $this->lastSaleNumber = null;
    }

    public function confirmSale(): void
    {
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

        $sale = null;

        try {
            DB::transaction(function () use ($cashRegister, &$sale) {
                $products = [];

                foreach ($this->cartItems as $item) {
                    $product = Product::lockForUpdate()->find($item['product_id']);
                    $available = $product ? ComboStockService::availableStock($product) : 0;

                    if (! $product || $available < $item['quantity']) {
                        throw new \RuntimeException(
                            "Stock insuficiente para \"{$item['name']}\". ".
                            "Disponible: {$available} {$item['unit']}. ".
                            "En carrito: {$item['quantity']}."
                        );
                    }

                    $products[$item['product_id']] = $product;
                }

                $subtotal = $this->getSubtotal();
                $discountAmount = $this->getDiscountAmount();

                $sale = Sale::create([
                    'user_id' => Auth::id(),
                    'cash_register_id' => $cashRegister->id,
                    'payment_method' => $this->paymentMethod,
                    'subtotal' => $subtotal,
                    'discount' => $discountAmount,
                    'total' => round($subtotal - $discountAmount, 2),
                    'notes' => $this->notes,
                    'status' => 'completed',
                ]);

                foreach ($this->cartItems as $item) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['name'],
                        'unit_price' => $item['unit_price'],
                        'quantity' => $item['quantity'],
                        'subtotal' => $item['subtotal'],
                    ]);

                    $product = $products[$item['product_id']];
                    ComboStockService::deduct($product, $item['quantity'], "Venta {$sale->sale_number}", $sale, Auth::id());
                }

                $this->lastSaleNumber = $sale->sale_number;
            });
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('No se pudo registrar la venta')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->cartItems = [];
        $this->paymentMethod = '';
        $this->notes = '';
        $this->discountType = 'fixed';
        $this->discountValue = '';

        $ticket = app(SaleTicketEscPosBuilder::class)->build($sale);
        EscPosPrint::dispatch($this, $ticket);

        Notification::make()
            ->title("¡Venta {$this->lastSaleNumber} registrada!")
            ->body('El stock fue actualizado automáticamente.')
            ->success()
            ->persistent()
            ->send();
    }
}

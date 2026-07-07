<?php

namespace App\Filament\Pages;

use App\Models\CashRegister;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use UnitEnum;

class PointOfSale extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Nueva Venta';

    protected static ?string $title = 'Nueva Venta';

    protected static ?string $slug = 'nueva-venta';

    protected static string|UnitEnum|null $navigationGroup = 'Operaciones';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.point-of-sale';

    public int $step = 1;

    // Step 1
    public string $barcodeInput = '';

    public string $searchQuery = '';

    public array $searchResults = [];

    public array $cart = [];

    // Step 2
    public string $paymentMethod = '';

    public float $discount = 0;

    public string $notes = '';

    public ?int $cashRegisterId = null;

    // Result
    public bool $saleCreated = false;

    public ?string $saleNumber = null;

    public ?int $saleId = null;

    #[Computed]
    public function subtotal(): float
    {
        return round(collect($this->cart)->sum('subtotal'), 2);
    }

    #[Computed]
    public function total(): float
    {
        return round(max(0, $this->subtotal - $this->discount), 2);
    }

    // O1: resultado cacheado por ciclo de render, evita query en cada re-render de Livewire
    #[Computed]
    public function hasCashRegisterOpen(): bool
    {
        return CashRegister::where('status', 'open')->exists();
    }

    #[Computed]
    public function openCashRegisters()
    {
        return CashRegister::where('status', 'open')->get();
    }

    public function scanBarcode(): void
    {
        $code = trim($this->barcodeInput);
        if (! $code) {
            return;
        }

        $product = Product::where(function ($q) use ($code) {
            $q->where('barcode', $code)->orWhere('sku', $code);
        })->where('active', true)->first();

        if ($product) {
            $this->addToCart($product->id);
            Notification::make()
                ->title($product->name.' agregado')
                ->success()
                ->duration(1500)
                ->send();
        } else {
            Notification::make()
                ->title('Código no encontrado')
                ->body("Sin producto para: {$code}")
                ->warning()
                ->send();
        }

        $this->barcodeInput = '';
        $this->dispatch('focus-barcode');
    }

    public function updatedSearchQuery(): void
    {
        $q = trim($this->searchQuery);

        if (strlen($q) < 2) {
            $this->searchResults = [];

            return;
        }

        $this->searchResults = Product::where('active', true)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%")
                    ->orWhere('barcode', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'sku', 'sale_price', 'stock'])
            ->toArray();
    }

    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        // V3: bloquear si no hay stock
        if ($product->stock <= 0) {
            Notification::make()
                ->title('Sin stock')
                ->body("{$product->name} no tiene stock disponible.")
                ->warning()
                ->send();

            return;
        }

        // F4: advertir precio $0 (no bloquea, puede ser intencional)
        if ((float) $product->sale_price <= 0) {
            Notification::make()
                ->title('Precio $0')
                ->body("{$product->name} tiene precio \$0. Verificá el precio antes de continuar.")
                ->warning()
                ->persistent()
                ->send();
        }

        foreach ($this->cart as $i => $item) {
            if ($item['product_id'] === $productId) {
                // V4: limitar cantidad al stock disponible
                $newQty = $this->cart[$i]['quantity'] + 1;
                if ($newQty > $this->cart[$i]['stock']) {
                    Notification::make()
                        ->title('Stock insuficiente')
                        ->body("Máximo disponible: {$this->cart[$i]['stock']} unidades.")
                        ->warning()
                        ->send();
                    $this->clearSearch();

                    return;
                }

                $this->cart[$i]['quantity'] = $newQty;
                $this->cart[$i]['subtotal'] = round($newQty * $this->cart[$i]['unit_price'], 2);
                $this->clearSearch();

                return;
            }
        }

        $this->cart[] = [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku ?? '',
            'unit_price' => (float) $product->sale_price,
            'quantity' => 1,
            'subtotal' => (float) $product->sale_price,
            'stock' => $product->stock,
        ];

        $this->clearSearch();
    }

    public function removeFromCart(int $index): void
    {
        array_splice($this->cart, $index, 1);
        $this->cart = array_values($this->cart);
    }

    public function incrementQty(int $index): void
    {
        $item = $this->cart[$index];

        // V4: limitar al stock disponible
        if ($item['quantity'] >= $item['stock']) {
            Notification::make()
                ->title('Stock insuficiente')
                ->body("Máximo disponible: {$item['stock']} unidades.")
                ->warning()
                ->send();

            return;
        }

        $this->cart[$index]['quantity']++;
        $this->cart[$index]['subtotal'] = round(
            $this->cart[$index]['quantity'] * $this->cart[$index]['unit_price'], 2
        );
    }

    public function decrementQty(int $index): void
    {
        if ($this->cart[$index]['quantity'] <= 1) {
            $this->removeFromCart($index);

            return;
        }

        $this->cart[$index]['quantity']--;
        $this->cart[$index]['subtotal'] = round(
            $this->cart[$index]['quantity'] * $this->cart[$index]['unit_price'], 2
        );
    }

    public function goToStep2(): void
    {
        if (empty($this->cart)) {
            Notification::make()
                ->title('Carrito vacío')
                ->body('Agregá al menos un producto para continuar.')
                ->warning()
                ->send();

            return;
        }
        $this->step = 2;
    }

    public function goToStep3(): void
    {
        if (! $this->paymentMethod) {
            Notification::make()
                ->title('Seleccioná un medio de pago')
                ->warning()
                ->send();

            return;
        }

        // V7: ventas en efectivo deben quedar registradas en una caja abierta para el arqueo
        if ($this->paymentMethod === 'cash' && ! $this->cashRegisterId) {
            $openRegister = CashRegister::where('status', 'open')->first();
            if ($openRegister) {
                $this->cashRegisterId = $openRegister->id;
            } else {
                Notification::make()
                    ->title('Sin caja asignada')
                    ->body('Las ventas en efectivo requieren una caja abierta.')
                    ->danger()
                    ->send();

                return;
            }
        }

        $this->step = 3;
    }

    public function prevStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function createSale(): void
    {
        // V1: bloquear si no hay caja abierta
        if (! $this->hasCashRegisterOpen) {
            Notification::make()
                ->title('No hay caja abierta')
                ->body('Abrí una caja antes de registrar una venta.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        try {
            DB::transaction(function () {
                // V5: lockForUpdate en un solo loop — guarda los modelos para reutilizarlos
                $lockedProducts = [];
                foreach ($this->cart as $item) {
                    $product = Product::lockForUpdate()->find($item['product_id']);
                    if (! $product || $product->stock < $item['quantity']) {
                        $available = $product?->stock ?? 0;
                        throw new \RuntimeException(
                            "Stock insuficiente para \"{$item['product_name']}\". Disponible: {$available}, solicitado: {$item['quantity']}."
                        );
                    }
                    $lockedProducts[$item['product_id']] = $product;
                }

                $sale = Sale::create([
                    'user_id' => Auth::id(),
                    'cash_register_id' => $this->cashRegisterId,
                    'payment_method' => $this->paymentMethod,
                    'subtotal' => $this->subtotal,
                    'discount' => $this->discount,
                    'total' => $this->total,
                    'notes' => $this->notes ?: null,
                    'status' => 'completed',
                ]);

                foreach ($this->cart as $item) {
                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'unit_price' => $item['unit_price'],
                        'quantity' => $item['quantity'],
                        'subtotal' => $item['subtotal'],
                    ]);

                    $product = $lockedProducts[$item['product_id']];
                    StockMovement::create([
                        'product_id' => $product->id,
                        'user_id' => Auth::id(),
                        'type' => 'out',
                        'quantity' => $item['quantity'],
                        'stock_before' => $product->stock,
                        'stock_after' => $product->stock - $item['quantity'],
                        'notes' => "Venta {$sale->sale_number}",
                        'reference_type' => Sale::class,
                        'reference_id' => $sale->id,
                    ]);

                    $product->decrement('stock', $item['quantity']);
                }

                $this->saleNumber = $sale->sale_number;
                $this->saleId = $sale->id;
            });

            $this->saleCreated = true;

        } catch (\RuntimeException $e) {
            // B1: refrescar stock en el carrito con valores reales de la BD
            foreach ($this->cart as $i => $item) {
                $fresh = Product::find($item['product_id']);
                if ($fresh) {
                    $this->cart[$i]['stock'] = $fresh->stock;
                    if ($this->cart[$i]['quantity'] > $fresh->stock) {
                        $this->cart[$i]['quantity'] = max(0, $fresh->stock);
                        $this->cart[$i]['subtotal'] = round(
                            $this->cart[$i]['quantity'] * $this->cart[$i]['unit_price'], 2
                        );
                    }
                }
            }
            $this->cart = array_values(array_filter($this->cart, fn ($item) => $item['quantity'] > 0));
            $this->step = 1;

            Notification::make()
                ->title('No se pudo confirmar la venta')
                ->body($e->getMessage().' Revisá el carrito.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function newSale(): void
    {
        $this->step = 1;
        $this->barcodeInput = '';
        $this->searchQuery = '';
        $this->searchResults = [];
        $this->cart = [];
        $this->paymentMethod = '';
        $this->discount = 0;
        $this->notes = '';
        $this->cashRegisterId = null;
        $this->saleCreated = false;
        $this->saleNumber = null;
        $this->saleId = null;
    }

    public function clearSearch(): void
    {
        $this->searchQuery = '';
        $this->searchResults = [];
    }
}

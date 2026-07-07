<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/pos.css') }}">

    {{-- V2: Banner de advertencia cuando no hay caja abierta --}}
    @if (! $this->hasCashRegisterOpen)
    <div class="flex items-center gap-3 rounded-xl border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-950/40 px-4 py-3 mb-2">
        <x-heroicon-o-exclamation-triangle class="w-5 h-5 shrink-0 text-red-600 dark:text-red-400"/>
        <p class="text-sm text-red-700 dark:text-red-300 font-medium">
            No hay caja abierta. Las ventas no se podrán confirmar.
            <a href="{{ route('filament.admin.resources.cash-registers.create') }}"
               class="underline underline-offset-2 hover:opacity-80">Abrir caja ahora →</a>
        </p>
    </div>
    @endif

    @if ($saleCreated)
    {{-- ==================== VENTA CREADA ==================== --}}
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-5">
            <x-heroicon-o-check-circle class="w-12 h-12 text-green-600 dark:text-green-400"/>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">¡Venta registrada!</h2>
        <p class="text-base text-gray-400 dark:text-gray-500 mb-1">{{ $saleNumber }}</p>
        <p class="text-4xl font-bold text-gray-900 dark:text-white mb-8">
            $ {{ number_format($this->total, 2, ',', '.') }}
        </p>
        <div class="flex gap-3">
            @if ($saleId)
            <a href="{{ route('filament.admin.resources.sales.edit', $saleId) }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <x-heroicon-o-document-text class="w-4 h-4"/> Ver detalle
            </a>
            @endif
            <button wire:click="newSale"
                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-zinc-900 dark:bg-white dark:text-gray-900 rounded-lg shadow-sm hover:opacity-90 transition-opacity">
                <x-heroicon-o-plus class="w-4 h-4"/> Nueva venta
            </button>
        </div>
    </div>

    @else
    {{-- ==================== INDICADOR DE PASOS ==================== --}}
    <div class="flex items-center justify-center mb-8 px-4">
        @php $steps = [1 => 'Productos', 2 => 'Pago', 3 => 'Confirmar']; @endphp
        @foreach ($steps as $s => $label)
        <div class="flex items-center {{ $s > 1 ? 'flex-1' : '' }}">
            @if ($s > 1)
            <div class="flex-1 h-0.5 {{ $step >= $s ? 'bg-zinc-900 dark:bg-white' : 'bg-gray-300 dark:bg-gray-600' }} transition-colors"></div>
            @endif
            <div class="flex flex-col items-center mx-2">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold border-2 transition-all
                    {{ $step > $s
                        ? 'bg-zinc-900 dark:bg-white text-white dark:text-gray-900 border-zinc-900 dark:border-white'
                        : ($step === $s
                            ? 'bg-zinc-900 dark:bg-white text-white dark:text-gray-900 border-zinc-900 dark:border-white'
                            : 'bg-white dark:bg-gray-800 text-gray-400 dark:text-gray-500 border-gray-300 dark:border-gray-600') }}">
                    @if ($step > $s)
                        <x-heroicon-o-check class="w-4 h-4"/>
                    @else
                        {{ $s }}
                    @endif
                </div>
                <span class="text-xs mt-1.5 font-medium transition-colors
                    {{ $step === $s ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                    {{ $label }}
                </span>
            </div>
            @if ($s < count($steps))
            <div class="flex-1 h-0.5 {{ $step > $s ? 'bg-zinc-900 dark:bg-white' : 'bg-gray-300 dark:bg-gray-600' }} transition-colors"></div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- ==================== PASO 1: PRODUCTOS ==================== --}}
    @if ($step === 1)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Columna izquierda: inputs --}}
        <div class="space-y-4">

            {{-- Scanner 1D --}}
            <div class="bg-white dark:bg-gray-800 border-2 border-dashed border-zinc-400 dark:border-zinc-500 rounded-xl p-4 shadow-sm">
                <label class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mb-3">
                    <x-heroicon-o-qr-code class="w-4 h-4"/> Escanear código
                </label>
                <input
                    type="text"
                    wire:model="barcodeInput"
                    wire:keydown.enter.prevent="scanBarcode"
                    x-ref="barcodeField"
                    x-init="$nextTick(() => $el.focus())"
                    @focus-barcode.window="$el.focus()"
                    placeholder="Apuntá el scanner aquí y escaneá..."
                    class="w-full rounded-lg border-2 border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-900 text-gray-900 dark:text-white px-4 py-3 font-mono text-base focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:focus:ring-zinc-400"
                    autocomplete="off"
                />
                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Presioná Enter para confirmar · También podés escribir el SKU</p>
            </div>

            {{-- Búsqueda manual --}}
            <div class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl p-4 shadow-sm"
                 x-data x-on:click.outside="$wire.clearSearch()">
                <label class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400 mb-3">
                    <x-heroicon-o-magnifying-glass class="w-4 h-4"/> Buscar manualmente
                </label>
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="searchQuery"
                        placeholder="Nombre, SKU o código de barras..."
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:focus:ring-zinc-400"
                        autocomplete="off"
                    />
                    @if (!empty($searchResults))
                    <div class="absolute top-full left-0 right-0 z-50 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-xl overflow-hidden">
                        @foreach ($searchResults as $result)
                        <button
                            type="button"
                            wire:click="addToCart({{ $result['id'] }})"
                            class="w-full flex items-center justify-between px-4 py-2.5 text-left hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-200 dark:border-gray-700 last:border-0 transition-colors">
                            <div class="min-w-0 mr-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $result['name'] }}</div>
                                @if ($result['sku'])
                                <div class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ $result['sku'] }}</div>
                                @endif
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-sm font-bold text-gray-900 dark:text-white">
                                    $ {{ number_format($result['sale_price'], 2, ',', '.') }}
                                </div>
                                <div class="text-xs {{ $result['stock'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500' }}">
                                    Stock: {{ $result['stock'] }}
                                </div>
                            </div>
                        </button>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Columna derecha: carrito --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl flex flex-col shadow-sm" style="min-height: 360px">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Carrito</h3>
                @if (!empty($cart))
                <span class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full font-medium">
                    {{ count($cart) }} {{ count($cart) === 1 ? 'producto' : 'productos' }}
                </span>
                @endif
            </div>

            @if (empty($cart))
            <div class="flex-1 flex flex-col items-center justify-center py-12 text-center">
                <x-heroicon-o-shopping-cart class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3"/>
                <p class="text-sm text-gray-500 dark:text-gray-400">El carrito está vacío</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Buscá o escaneá para agregar</p>
            </div>
            @else
            <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($cart as $i => $item)
                <div class="flex items-center gap-3 px-4 py-3">
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $item['product_name'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">$ {{ number_format($item['unit_price'], 2, ',', '.') }} c/u</div>
                    </div>
                    <div class="flex items-center gap-1 shrink-0">
                        <button wire:click="decrementQty({{ $i }})"
                            class="w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 flex items-center justify-center font-bold text-base leading-none transition-colors">
                            −
                        </button>
                        <span class="w-8 text-center text-sm font-bold text-gray-900 dark:text-white">{{ $item['quantity'] }}</span>
                        <button wire:click="incrementQty({{ $i }})"
                            class="w-7 h-7 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 flex items-center justify-center font-bold text-base leading-none transition-colors">
                            +
                        </button>
                    </div>
                    <div class="text-sm font-bold text-gray-900 dark:text-white w-24 text-right shrink-0">
                        $ {{ number_format($item['subtotal'], 2, ',', '.') }}
                    </div>
                    <button wire:click="removeFromCart({{ $i }})"
                        class="shrink-0 text-gray-400 dark:text-gray-500 hover:text-red-500 dark:hover:text-red-400 transition-colors ml-1">
                        <x-heroicon-o-x-mark class="w-4 h-4"/>
                    </button>
                </div>
                @endforeach
            </div>
            <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3 flex items-center justify-between bg-gray-50 dark:bg-gray-900/50 rounded-b-xl">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Subtotal</span>
                <span class="text-lg font-bold text-gray-900 dark:text-white">
                    $ {{ number_format($this->subtotal, 2, ',', '.') }}
                </span>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ==================== PASO 2: PAGO ==================== --}}
    @if ($step === 2)
    @php
        $paymentLabels = ['cash' => 'Efectivo', 'transfer' => 'Transferencia', 'card' => 'Tarjeta'];
        $paymentIcons  = ['cash' => '💵', 'transfer' => '📱', 'card' => '💳'];
    @endphp
    <div class="max-w-lg mx-auto space-y-5">

        <div class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl p-6 space-y-5 shadow-sm">

            {{-- Medio de pago --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                    Medio de pago <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-3 gap-3">
                    @foreach ($paymentLabels as $value => $label)
                    <button type="button" wire:click="$set('paymentMethod', '{{ $value }}')"
                        class="flex flex-col items-center gap-2 py-4 px-2 rounded-xl border-2 font-medium text-sm transition-all
                        {{ $paymentMethod === $value
                            ? 'border-zinc-900 dark:border-white bg-zinc-900 dark:bg-white text-white dark:text-gray-900 shadow-sm'
                            : 'border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-gray-400 dark:hover:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                        <span class="text-2xl">{{ $paymentIcons[$value] }}</span>
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Descuento --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Descuento</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-sm font-medium">$</span>
                    <input type="number" wire:model.live="discount" min="0" step="0.01"
                        class="w-full pl-7 pr-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-zinc-500"
                        placeholder="0"/>
                </div>
            </div>

            {{-- Caja registradora --}}
            @if ($this->openCashRegisters->isNotEmpty())
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Caja registradora</label>
                <select wire:model="cashRegisterId"
                    class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-zinc-500">
                    <option value="">Sin caja asignada</option>
                    @foreach ($this->openCashRegisters as $cr)
                    <option value="{{ $cr->id }}">
                        Caja #{{ $cr->id }} — abierta {{ $cr->opened_at?->format('d/m H:i') }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Notas --}}
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                    Notas <span class="font-normal text-gray-400">(opcional)</span>
                </label>
                <textarea wire:model="notes" rows="2"
                    class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-zinc-500 resize-none"
                    placeholder="Observaciones de la venta..."></textarea>
            </div>
        </div>

        {{-- Resumen mini --}}
        <div class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-5 py-4 flex items-center justify-between">
            <div class="text-sm text-gray-600 dark:text-gray-300">
                {{ count($cart) }} {{ count($cart) === 1 ? 'producto' : 'productos' }}
                @if ($paymentMethod) · {{ $paymentLabels[$paymentMethod] }} @endif
            </div>
            <div class="text-right">
                @if ($discount > 0)
                <div class="text-xs text-gray-400 line-through">$ {{ number_format($this->subtotal, 2, ',', '.') }}</div>
                @endif
                <div class="text-lg font-bold text-gray-900 dark:text-white">
                    $ {{ number_format($this->total, 2, ',', '.') }}
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ==================== PASO 3: CONFIRMAR ==================== --}}
    @if ($step === 3)
    @php
        $paymentLabels = ['cash' => 'Efectivo', 'transfer' => 'Transferencia', 'card' => 'Tarjeta'];
        $paymentIcons  = ['cash' => '💵', 'transfer' => '📱', 'card' => '💳'];
    @endphp
    <div class="max-w-lg mx-auto space-y-4">

        {{-- Detalle de productos --}}
        <div class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl overflow-hidden shadow-sm">
            <div class="px-5 py-3.5 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Detalle de la venta</h3>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach ($cart as $item)
                <div class="flex items-center justify-between px-5 py-3">
                    <div>
                        <span class="text-sm text-gray-900 dark:text-white">{{ $item['product_name'] }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">×{{ $item['quantity'] }}</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white">
                        $ {{ number_format($item['subtotal'], 2, ',', '.') }}
                    </span>
                </div>
                @endforeach
            </div>
            <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700 space-y-2 bg-gray-50 dark:bg-gray-900/50">
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                    <span>Subtotal</span>
                    <span>$ {{ number_format($this->subtotal, 2, ',', '.') }}</span>
                </div>
                @if ($discount > 0)
                <div class="flex justify-between text-sm text-emerald-600 dark:text-emerald-400">
                    <span>Descuento</span>
                    <span>− $ {{ number_format($discount, 2, ',', '.') }}</span>
                </div>
                @endif
                <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                    <span class="text-base font-bold text-gray-900 dark:text-white">Total</span>
                    <span class="text-xl font-bold text-gray-900 dark:text-white">
                        $ {{ number_format($this->total, 2, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Medio de pago --}}
        <div class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-5 py-3.5 flex items-center gap-3">
            <span class="text-2xl">{{ $paymentIcons[$paymentMethod] ?? '' }}</span>
            <div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Medio de pago</div>
                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                    {{ $paymentLabels[$paymentMethod] ?? '' }}
                </div>
            </div>
        </div>

        @if ($notes)
        <div class="bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl px-5 py-3.5">
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Notas</div>
            <div class="text-sm text-gray-700 dark:text-gray-300">{{ $notes }}</div>
        </div>
        @endif
    </div>
    @endif

    {{-- ==================== NAVEGACIÓN ==================== --}}
    <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-300 dark:border-gray-600">
        @if ($step > 1)
        <button wire:click="prevStep"
            class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <x-heroicon-o-arrow-left class="w-4 h-4"/> Atrás
        </button>
        @else
        <div></div>
        @endif

        @if ($step === 1)
        <button wire:click="goToStep2"
            @if(empty($cart)) disabled @endif
            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold rounded-lg transition-all shadow-sm
            {{ empty($cart)
                ? 'text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 cursor-not-allowed'
                : 'text-white bg-zinc-900 dark:bg-white dark:text-gray-900 hover:opacity-90' }}">
            Continuar <x-heroicon-o-arrow-right class="w-4 h-4"/>
        </button>

        @elseif ($step === 2)
        <button wire:click="goToStep3"
            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-zinc-900 dark:bg-white dark:text-gray-900 rounded-lg shadow-sm hover:opacity-90 transition-opacity">
            Continuar <x-heroicon-o-arrow-right class="w-4 h-4"/>
        </button>

        @elseif ($step === 3)
        <button wire:click="createSale"
            wire:loading.attr="disabled"
            class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-colors disabled:opacity-60">
            <span wire:loading.remove wire:target="createSale">
                <x-heroicon-o-check class="w-4 h-4 inline -mt-0.5 mr-0.5"/> Confirmar venta
            </span>
            <span wire:loading wire:target="createSale">Creando venta...</span>
        </button>
        @endif
    </div>

    @endif {{-- /saleCreated --}}

</x-filament-panels::page>

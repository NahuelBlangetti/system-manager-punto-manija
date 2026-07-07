<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/import-pages.css') }}">

    @if ($import)
        @php $stats = $this->stats; @endphp

        <div class="imp-page">

            <a href="{{ \App\Filament\Pages\CargarProductos::getUrl() }}" class="imp-back">
                <x-filament::icon icon="heroicon-m-arrow-left" style="width:1rem;height:1rem;" />
                Volver a Cargar productos
            </a>

            {{-- Info del archivo --}}
            <div class="imp-file-info">
                <div class="imp-file-info__icon">
                    <x-filament::icon icon="heroicon-o-document-text" style="width:1.25rem;height:1.25rem;color:#71717a;" />
                </div>
                <div>
                    <div class="imp-file-info__name">{{ $import->filename }}</div>
                    <div class="imp-file-info__meta">
                        {{ $stats['total'] }} productos extraídos
                        @if ($import->created_at)
                            · {{ $import->created_at->format('d/m/Y H:i') }}
                        @endif
                    </div>
                </div>
            </div>

            {{-- Stats --}}
            <div class="imp-stats">
                <div class="imp-stat">
                    <div class="imp-stat__value">{{ $stats['total'] }}</div>
                    <div class="imp-stat__label">Total</div>
                </div>
                <div class="imp-stat imp-stat--success">
                    <div class="imp-stat__value">{{ $stats['new'] }}</div>
                    <div class="imp-stat__label">Nuevos</div>
                </div>
                <div class="imp-stat imp-stat--warning">
                    <div class="imp-stat__value">{{ $stats['update'] }}</div>
                    <div class="imp-stat__label">A actualizar</div>
                </div>
                <div class="imp-stat imp-stat--muted">
                    <div class="imp-stat__value">{{ $stats['duplicate'] }}</div>
                    <div class="imp-stat__label">Duplicados</div>
                </div>
            </div>

            {{-- Proveedor --}}
            <div class="imp-supplier-row">
                {{ $this->form }}
            </div>

            @if ($import->error_message)
                <x-filament::callout icon="heroicon-o-exclamation-triangle" color="warning" heading="Importación parcial">
                    {{ $import->error_message }}
                </x-filament::callout>
            @endif

            @if (count($products) > 0)

                {{-- Toolbar selección --}}
                <div class="imp-toolbar">
                    <div class="imp-toolbar__links">
                        <button type="button" wire:click="toggleAll(true)">Seleccionar todos</button>
                        <button type="button" wire:click="toggleAll(false)">Deseleccionar todos</button>
                    </div>
                    <span class="imp-toolbar__count">
                        {{ $stats['selected'] }} de {{ $stats['total'] }} seleccionados
                    </span>
                </div>

                {{-- Tabla --}}
                <div class="imp-table-wrap">
                    <div class="imp-table-scroll">
                        <table class="imp-table">
                            <thead>
                                <tr>
                                    <th class="imp-col-check"></th>
                                    <th class="imp-col-name">Nombre</th>
                                    <th class="imp-col-category">Categoría</th>
                                    <th class="imp-col-unit">Unidad</th>
                                    <th class="imp-col-price">Precio venta</th>
                                    <th class="imp-col-price">Precio costo</th>
                                    <th class="imp-col-stock">Stock</th>
                                    <th class="imp-col-barcode">Cód. barras</th>
                                    <th class="imp-col-status">Estado</th>
                                    <th class="imp-col-action"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $index => $product)
                                    @php
                                        $rowClass = ! $product['selected']
                                            ? 'is-deselected'
                                            : ($product['internal_duplicate']
                                                ? 'is-duplicate'
                                                : ($product['action'] === 'update' ? 'is-update' : 'is-new'));
                                    @endphp
                                    <tr wire:key="row-{{ $index }}" class="{{ $rowClass }}">
                                        <td>
                                            <x-filament::input.checkbox wire:model.live="products.{{ $index }}.selected" />
                                        </td>
                                        <td>
                                            <x-filament::input.wrapper>
                                                <x-filament::input type="text" wire:model.live.debounce.500ms="products.{{ $index }}.name" />
                                            </x-filament::input.wrapper>
                                        </td>
                                        <td>
                                            <x-filament::input.wrapper>
                                                <x-filament::input.select wire:model="products.{{ $index }}.category_id">
                                                    <option value="">Sin categoría</option>
                                                    @foreach ($this->categoryOptions as $catId => $catName)
                                                        <option value="{{ $catId }}" {{ (string) ($product['category_id'] ?? '') === (string) $catId ? 'selected' : '' }}>
                                                            {{ $catName }}
                                                        </option>
                                                    @endforeach
                                                </x-filament::input.select>
                                            </x-filament::input.wrapper>
                                        </td>
                                        <td>
                                            <x-filament::input.wrapper>
                                                <x-filament::input.select wire:model="products.{{ $index }}.unit">
                                                    @foreach ($this->unitOptions as $unitValue => $unitLabel)
                                                        <option value="{{ $unitValue }}" {{ $product['unit'] === $unitValue ? 'selected' : '' }}>
                                                            {{ $unitLabel }}
                                                        </option>
                                                    @endforeach
                                                </x-filament::input.select>
                                            </x-filament::input.wrapper>
                                        </td>
                                        <td class="imp-col-price">
                                            <x-filament::input.wrapper>
                                                <x-filament::input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    inputmode="decimal"
                                                    placeholder="0,00"
                                                    wire:model="products.{{ $index }}.sale_price"
                                                    class="imp-price-input"
                                                />
                                            </x-filament::input.wrapper>
                                        </td>
                                        <td class="imp-col-price">
                                            <x-filament::input.wrapper>
                                                <x-filament::input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    inputmode="decimal"
                                                    placeholder="0,00"
                                                    wire:model="products.{{ $index }}.cost_price"
                                                    class="imp-price-input"
                                                />
                                            </x-filament::input.wrapper>
                                        </td>
                                        <td>
                                            <x-filament::input.wrapper>
                                                <x-filament::input type="number" step="1" min="0" wire:model="products.{{ $index }}.stock" />
                                            </x-filament::input.wrapper>
                                        </td>
                                        <td>
                                            <x-filament::input.wrapper>
                                                <x-filament::input type="text" wire:model="products.{{ $index }}.barcode" />
                                            </x-filament::input.wrapper>
                                        </td>
                                        <td>
                                            @if ($product['internal_duplicate'])
                                                <span class="imp-badge imp-badge--duplicate">Duplicado</span>
                                            @elseif ($product['action'] === 'update')
                                                <span class="imp-badge imp-badge--update">
                                                    Existe{{ match($product['price_direction'] ?? '') { 'up' => ' ↑', 'down' => ' ↓', default => '' } }}
                                                </span>
                                            @else
                                                <span class="imp-badge imp-badge--new">Nuevo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <x-filament::icon-button
                                                icon="heroicon-o-x-mark"
                                                color="danger"
                                                size="sm"
                                                wire:click="removeProduct({{ $index }})"
                                                tooltip="Quitar"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Footer sticky --}}
                <div class="imp-sticky-footer">
                    <div class="imp-sticky-footer__info">
                        Vas a guardar <strong>{{ $stats['selected'] }}</strong> de {{ $stats['total'] }} productos
                    </div>
                    <div class="imp-sticky-footer__actions">
                        <x-filament::button
                            color="gray"
                            icon="heroicon-m-x-mark"
                            wire:click="cancelImport"
                            wire:loading.attr="disabled"
                            wire:confirm="¿Cancelar esta importación? No se guardará ningún producto del archivo."
                        >
                            <span wire:loading.remove wire:target="cancelImport">Cancelar importación</span>
                            <span wire:loading wire:target="cancelImport">Cancelando…</span>
                        </x-filament::button>
                        <x-filament::button
                            icon="heroicon-m-check-circle"
                            wire:click="createProducts"
                            wire:loading.attr="disabled"
                            :disabled="$stats['selected'] === 0"
                        >
                            <span wire:loading.remove wire:target="createProducts">Confirmar y guardar</span>
                            <span wire:loading wire:target="createProducts">Guardando…</span>
                        </x-filament::button>
                    </div>
                </div>

            @else
                <div class="imp-empty" style="padding:3rem 1rem;">
                    <x-filament::icon icon="heroicon-o-inbox" style="width:2rem;height:2rem;color:#a1a1aa;margin-bottom:0.25rem;" />
                    <p class="imp-empty__title">No quedan productos en esta lista</p>
                    <p class="imp-empty__desc">Quitaste todas las filas. Volvé a Cargar productos o cancelá la importación.</p>
                    <div style="display:flex;gap:0.5rem;margin-top:0.75rem;flex-wrap:wrap;justify-content:center;">
                        <x-filament::button
                            tag="a"
                            :href="\App\Filament\Pages\CargarProductos::getUrl()"
                            color="gray"
                            size="sm"
                        >
                            Volver
                        </x-filament::button>
                        <x-filament::button
                            color="danger"
                            size="sm"
                            icon="heroicon-m-x-mark"
                            wire:click="cancelImport"
                            wire:confirm="¿Cancelar esta importación? No se guardará ningún producto."
                        >
                            Cancelar importación
                        </x-filament::button>
                    </div>
                </div>
            @endif

        </div>
    @endif

</x-filament-panels::page>

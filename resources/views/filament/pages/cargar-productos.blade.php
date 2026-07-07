<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/import-pages.css') }}">

    <div class="imp-page" @if ($this->processingImports->isNotEmpty()) wire:poll.5s @endif>

        {{-- Stepper --}}
        <div class="imp-steps">
            <div class="imp-step imp-step--active">
                <div class="imp-step__circle">1</div>
                <span class="imp-step__label">Subí el archivo</span>
                <span class="imp-step__desc">PDF o Excel</span>
            </div>
            <div class="imp-step">
                <div class="imp-step__circle">2</div>
                <span class="imp-step__label">Análisis automático</span>
                <span class="imp-step__desc">Extraemos productos</span>
            </div>
            <div class="imp-step">
                <div class="imp-step__circle">3</div>
                <span class="imp-step__label">Revisá y guardá</span>
                <span class="imp-step__desc">Confirmá en catálogo</span>
            </div>
        </div>

        @if ($hasDuplicate)
            <x-filament::callout
                icon="heroicon-o-exclamation-triangle"
                color="warning"
                heading="Este archivo ya fue procesado antes"
            >
                Podés cancelar para elegir otro archivo o volver a procesarlo de todos modos.

                <x-slot:footer>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                        <x-filament::button color="gray" size="sm" wire:click="cancelDuplicate">
                            Cancelar
                        </x-filament::button>
                        <x-filament::button color="warning" size="sm" wire:click="processAnyway">
                            Procesar de nuevo
                        </x-filament::button>
                    </div>
                </x-slot:footer>
            </x-filament::callout>
        @endif

        <div class="imp-layout">

            {{-- Columna principal: formulario --}}
            <div class="imp-card">
                <div class="imp-card__header">
                    <h2 class="imp-card__title">Subir lista de precios</h2>
                    <p class="imp-card__desc">
                        Arrastrá o seleccioná un archivo PDF, XLSX o XLS (máx. 25 MB).
                        Si el nombre coincide con un proveedor, lo asignamos automáticamente.
                    </p>
                </div>

                <div class="imp-card__body">
                    {{ $this->form }}

                    @if ($supplierAutoDetectedName)
                        <div class="imp-hint">
                            <x-filament::icon icon="heroicon-m-sparkles" style="width:1rem;height:1rem;flex-shrink:0;" />
                            <span>Proveedor detectado: <strong>{{ $supplierAutoDetectedName }}</strong></span>
                        </div>
                    @endif
                </div>

                <div class="imp-card__footer">
                    <x-filament::button
                        icon="heroicon-o-sparkles"
                        wire:click="submitImport"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="submitImport">Enviar a analizar</span>
                        <span wire:loading wire:target="submitImport">Procesando…</span>
                    </x-filament::button>
                </div>
            </div>

            {{-- Columna lateral: pendientes --}}
            <div class="imp-card">
                <div class="imp-card__header">
                    <h2 class="imp-card__title">Listas para revisar</h2>
                    <p class="imp-card__desc">
                        Archivos ya analizados, pendientes de confirmación.
                    </p>
                </div>

                <div class="imp-card__body">
                    @if ($this->processingImports->isNotEmpty())
                        <div class="imp-processing-list">
                            @foreach ($this->processingImports as $import)
                                <div class="imp-processing-item">
                                    <x-filament::loading-indicator class="h-4 w-4" />
                                    <div>
                                        <div class="imp-pending-item__name">{{ $import->filename }}</div>
                                        <div class="imp-pending-item__meta">
                                            {{ $import->status === 'pending' ? 'En cola…' : 'Analizando con IA…' }}
                                            · {{ $import->created_at?->diffForHumans() }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($this->pendingImports->isNotEmpty())
                        <div class="imp-pending-list">
                            @foreach ($this->pendingImports as $import)
                                <div class="imp-pending-item">
                                    <div class="imp-pending-item__top">
                                        <div>
                                            <div class="imp-pending-item__name" title="{{ $import->filename }}">
                                                {{ $import->filename }}
                                            </div>
                                            <div class="imp-pending-item__meta">
                                                {{ $import->created_at?->diffForHumans() }}
                                            </div>
                                        </div>
                                        <span class="imp-pending-item__count">{{ $import->product_count }}</span>
                                    </div>
                                    <a
                                        href="{{ \App\Filament\Pages\ValidarImport::getUrl(['id' => $import->id]) }}"
                                        class="imp-pending-item__link"
                                    >
                                        Revisar productos →
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @elseif ($this->processingImports->isEmpty())
                        <div class="imp-empty">
                            <x-filament::icon icon="heroicon-o-inbox" style="width:2rem;height:2rem;color:#a1a1aa;margin-bottom:0.25rem;" />
                            <p class="imp-empty__title">Nada pendiente</p>
                            <p class="imp-empty__desc">Cuando termine el análisis, la lista aparecerá acá.</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

</x-filament-panels::page>

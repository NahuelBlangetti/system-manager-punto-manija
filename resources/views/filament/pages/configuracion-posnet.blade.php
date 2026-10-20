<x-filament-panels::page>
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6">

        <x-filament::section>
            <x-slot name="heading">Estado de la integración</x-slot>

            <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Credenciales</dt>
                    <dd class="mt-1">
                        @if ($this->isConfigured)
                            <x-filament::badge color="success">Access Token cargado</x-filament::badge>
                        @else
                            <x-filament::badge color="danger">Falta MERCADOPAGO_ACCESS_TOKEN</x-filament::badge>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Posnet en uso</dt>
                    <dd class="mt-1 break-all font-mono text-xs text-gray-900 dark:text-gray-100">
                        {{ $this->currentTerminalId ?? 'Ninguno seleccionado' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Webhook (opcional)</dt>
                    <dd class="mt-1 break-all font-mono text-xs text-gray-900 dark:text-gray-100">
                        {{ $this->webhookUrl }}
                    </dd>
                    <dd class="mt-1">
                        <x-filament::badge :color="$this->hasWebhookSecret ? 'success' : 'gray'">
                            {{ $this->hasWebhookSecret ? 'Firma validada' : 'Sin clave secreta' }}
                        </x-filament::badge>
                    </dd>
                </div>
            </dl>

            @unless ($this->isConfigured)
                <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">
                    Cargá el Access Token de producción de la cuenta dueña del posnet en el <code>.env</code>
                    (<code>MERCADOPAGO_ACCESS_TOKEN</code>) y corré <code>php artisan config:clear</code>.
                </p>
            @endunless
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Posnets vinculados a la cuenta</x-slot>
            <x-slot name="description">
                El posnet tiene que estar en modo PDV para recibir cobros del sistema. Después de activarlo, reinicialo.
            </x-slot>
            <x-slot name="headerEnd">
                <x-filament::button
                    wire:click="loadTerminals"
                    icon="heroicon-o-arrow-path"
                    :disabled="! $this->isConfigured"
                >
                    Buscar posnets
                </x-filament::button>
            </x-slot>

            @if (! $terminalsLoaded)
                <p class="text-sm text-gray-500 dark:text-gray-400">Tocá “Buscar posnets” para consultar Mercado Pago.</p>
            @elseif (empty($terminals))
                <p class="text-sm text-gray-500 dark:text-gray-400">No hay posnets vinculados a esta cuenta.</p>
            @else
                <div class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($terminals as $terminal)
                        <div class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center sm:justify-between" wire:key="terminal-{{ $terminal['id'] }}">
                            <div class="min-w-0">
                                <p class="break-all font-mono text-sm text-gray-900 dark:text-gray-100">{{ $terminal['id'] }}</p>
                                <div class="mt-1 flex flex-wrap items-center gap-2">
                                    <x-filament::badge :color="($terminal['operating_mode'] ?? null) === 'PDV' ? 'success' : 'warning'">
                                        Modo {{ $terminal['operating_mode'] ?? 'desconocido' }}
                                    </x-filament::badge>
                                    @if ($this->currentTerminalId === $terminal['id'])
                                        <x-filament::badge color="primary">En uso</x-filament::badge>
                                    @endif
                                </div>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                @if (($terminal['operating_mode'] ?? null) !== 'PDV')
                                    <x-filament::button
                                        size="sm"
                                        color="warning"
                                        wire:click="activatePdvMode('{{ $terminal['id'] }}')"
                                        wire:confirm="¿Pasar este posnet a modo PDV? Solo va a cobrar lo que envíe el sistema."
                                    >
                                        Activar modo PDV
                                    </x-filament::button>
                                @endif
                                @if ($this->currentTerminalId !== $terminal['id'])
                                    <x-filament::button size="sm" color="gray" wire:click="useTerminal('{{ $terminal['id'] }}')">
                                        Usar este
                                    </x-filament::button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Últimos cobros con posnet</x-slot>

            @if ($this->recentPayments->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">Todavía no hay cobros.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead class="text-xs uppercase text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="py-2 pr-3 font-medium">Fecha</th>
                                <th class="py-2 pr-3 font-medium">Monto</th>
                                <th class="py-2 pr-3 font-medium">Estado</th>
                                <th class="py-2 pr-3 font-medium">Medio</th>
                                <th class="py-2 pr-3 font-medium">Venta</th>
                                <th class="py-2 font-medium">Orden MP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                            @foreach ($this->recentPayments as $payment)
                                <tr wire:key="point-payment-{{ $payment->id }}" class="text-gray-900 dark:text-gray-100">
                                    <td class="py-2 pr-3 whitespace-nowrap">{{ $payment->created_at->format('d/m H:i') }}</td>
                                    <td class="py-2 pr-3 whitespace-nowrap tabular-nums">$ {{ number_format((float) $payment->amount, 2, ',', '.') }}</td>
                                    <td class="py-2 pr-3">
                                        <x-filament::badge :color="$payment->isPaid() ? 'success' : ($payment->isFinished() ? 'danger' : 'warning')">
                                            {{ $payment->statusLabel() }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="py-2 pr-3 whitespace-nowrap">
                                        {{ collect([$payment->paymentTypeLabel(), $payment->card_last_digits ? '****'.$payment->card_last_digits : null])->filter()->implode(' ') ?: '—' }}
                                    </td>
                                    <td class="py-2 pr-3 whitespace-nowrap">{{ $payment->sale?->sale_number ?? '—' }}</td>
                                    <td class="py-2 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $payment->mp_order_id ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>

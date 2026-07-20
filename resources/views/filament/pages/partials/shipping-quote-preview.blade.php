@php
    $formatMoney = static fn (?float $amount): string => $amount === null
        ? '—'
        : '$ '.number_format($amount, 0, ',', '.');
@endphp

<div class="space-y-3">
    <ul class="divide-y divide-gray-200 rounded-xl border border-gray-200 dark:divide-white/10 dark:border-white/10" role="list">
        @forelse ($samples as $sample)
            <li
                @class([
                    'flex items-center justify-between gap-3 px-3.5 py-2.5 text-sm',
                    'bg-primary-50/60 dark:bg-primary-500/10' => $sample['is_boundary'] ?? false,
                ])
            >
                <span @class([
                    'font-medium text-gray-700 dark:text-gray-200',
                    'text-primary-700 dark:text-primary-300' => $sample['is_boundary'] ?? false,
                ])>
                    {{ $sample['label'] }}
                </span>

                @if ($sample['out_of_range'])
                    <span class="text-right text-xs font-medium text-amber-600 dark:text-amber-400">
                        Coordina por WhatsApp
                    </span>
                @else
                    <span class="font-semibold tabular-nums text-gray-950 dark:text-white">
                        {{ $formatMoney($sample['cost']) }}
                    </span>
                @endif
            </li>
        @empty
            <li class="px-3.5 py-4 text-sm text-gray-500 dark:text-gray-400">
                Completá los parámetros para ver ejemplos.
            </li>
        @endforelse
    </ul>

    <p class="text-xs leading-relaxed text-gray-500 dark:text-gray-400">
        Los montos son orientativos. La distancia real se mide desde el local al destino del cliente.
    </p>
</div>

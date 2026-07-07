<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #1f2937;
            font-size: 11px;
        }
        .header {
            border-bottom: 2px solid #1f2937;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0 0 2px 0;
        }
        .header p {
            margin: 0;
            color: #6b7280;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead th {
            background-color: #1f2937;
            color: #ffffff;
            text-align: left;
            padding: 6px 8px;
            font-size: 10px;
            text-transform: uppercase;
        }
        tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .img-cell img {
            width: 36px;
            height: 36px;
            object-fit: cover;
        }
        .text-right {
            text-align: right;
        }
        .muted {
            color: #6b7280;
        }
        .footer {
            margin-top: 16px;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 10px;
            color: #ffffff;
        }
        .badge-danger {
            background-color: #dc2626;
        }
        .badge-warning {
            background-color: #d97706;
        }
        .badge-success {
            background-color: #16a34a;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $storeName }} — {{ $tipo === 'proveedor' ? 'Lista para proveedor' : 'Catálogo de productos' }}</h1>
        <p>Generado el {{ $generatedAt->format('d/m/Y H:i') }} · {{ $products->count() }} producto(s)</p>
    </div>

    <table>
        <thead>
            @if ($tipo === 'proveedor')
                <tr>
                    <th>Nombre</th>
                    <th>Proveedor</th>
                    <th>Categoría</th>
                    <th>SKU / Código de barras</th>
                    <th class="text-right">Stock actual</th>
                    <th class="text-right">Precio costo</th>
                    <th class="text-right">Margen</th>
                </tr>
            @else
                <tr>
                    <th></th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th class="text-right">Precio</th>
                </tr>
            @endif
        </thead>
        <tbody>
            @foreach ($products as $product)
                @if ($tipo === 'proveedor')
                    @php
                        $stockClass = match (true) {
                            $product->stock <= 0 => 'badge-danger',
                            $product->stock <= $product->min_stock => 'badge-warning',
                            default => 'badge-success',
                        };
                        $margin = (((float) $product->sale_price / max((float) $product->cost_price, 0.01)) - 1) * 100;
                        $marginClass = match (true) {
                            $margin <= 0 => 'badge-danger',
                            $margin < 20 => 'badge-warning',
                            default => 'badge-success',
                        };
                    @endphp
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td class="muted">{{ $product->supplier?->name ?? '—' }}</td>
                        <td class="muted">{{ $product->category?->name ?? '—' }}</td>
                        <td class="muted">{{ $product->sku ?: ($product->barcode ?: '—') }}</td>
                        <td class="text-right"><span class="badge {{ $stockClass }}">{{ $product->stock }}</span></td>
                        <td class="text-right">$ {{ number_format((float) $product->cost_price, 2, ',', '.') }}</td>
                        <td class="text-right"><span class="badge {{ $marginClass }}">{{ number_format($margin, 1) }}%</span></td>
                    </tr>
                @else
                    @php
                        $imagePath = null;
                        if ($product->image && ! str_starts_with($product->image, 'http')) {
                            $candidate = \Illuminate\Support\Facades\Storage::disk('public')->path($product->image);
                            $imagePath = file_exists($candidate) ? $candidate : null;
                        }
                    @endphp
                    <tr>
                        <td class="img-cell">
                            @if ($imagePath)
                                <img src="{{ $imagePath }}">
                            @endif
                        </td>
                        <td>{{ $product->name }}</td>
                        <td class="muted">{{ $product->category?->name ?? '—' }}</td>
                        <td class="text-right">$ {{ number_format((float) $product->sale_price, 2, ',', '.') }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        {{ $storeName }} · Documento generado automáticamente desde el panel de administración
    </div>
</body>
</html>

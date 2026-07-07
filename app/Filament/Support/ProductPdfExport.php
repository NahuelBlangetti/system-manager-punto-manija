<?php

namespace App\Filament\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductPdfExport
{
    public static function download(Collection $products, string $tipo): StreamedResponse
    {
        $pdf = Pdf::loadView('pdf.products-export', [
            'products' => $products,
            'tipo' => $tipo,
            'storeName' => config('store.name'),
            'generatedAt' => now(),
        ])->setPaper('a4', $tipo === 'proveedor' ? 'landscape' : 'portrait');

        $prefix = $tipo === 'proveedor' ? 'lista-proveedor' : 'catalogo-cliente';
        $filename = $prefix.'-'.now()->format('Y-m-d').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }
}

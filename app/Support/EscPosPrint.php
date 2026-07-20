<?php

namespace App\Support;

use Livewire\Component;

/**
 * Envío de ESC/POS al navegador vía Livewire.
 *
 * El contenido se manda en base64: los bitmaps de códigos de barras
 * contienen bytes > 127 que JSON/UTF-8 corromperían si viajaran crudos.
 */
class EscPosPrint
{
    public static function dispatch(Component $livewire, string $content): void
    {
        $livewire->dispatch(
            'print-escpos-ticket',
            content: base64_encode($content),
            encoding: 'base64',
        );
    }
}

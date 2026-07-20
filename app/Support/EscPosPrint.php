<?php

namespace App\Support;

/**
 * Envío de ESC/POS al navegador vía Livewire.
 *
 * El contenido viaja como "base64:<payload>": los bitmaps de códigos de
 * barras tienen bytes > 127 que JSON/UTF-8 corromperían en crudo. El
 * print-agent detecta el prefijo y decodifica antes de mandar a la impresora.
 */
class EscPosPrint
{
    public static function dispatch(object $livewire, string $content): void
    {
        $livewire->dispatch(
            'print-escpos-ticket',
            content: 'base64:'.base64_encode($content),
        );
    }
}

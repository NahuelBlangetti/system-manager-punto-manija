<?php

namespace App\Services\Arca;

use RuntimeException;

/**
 * Falla al hablar con ARCA. El mensaje está pensado para mostrarse en el panel.
 * $sinRespuesta = no se sabe si ARCA recibió el pedido (corte de red, timeout).
 */
class ArcaException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $sinRespuesta = false, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}

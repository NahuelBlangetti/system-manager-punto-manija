<?php

namespace App\Filament\Actions;

use Filament\Actions\Action;

class ConfigurePrinterAction
{
    public static function make(): Action
    {
        return Action::make('configurePrinter')
            ->label('Configurar impresora')
            ->icon('heroicon-o-printer')
            ->color('gray')
            ->modalHeading('Configurar impresora de tickets')
            ->modalDescription('Se guarda solo en este navegador. Elegí la impresora térmica de tickets (no la Zebra).')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalWidth('md')
            ->modalContent(fn () => view('filament.partials.configure-printer'));
    }
}

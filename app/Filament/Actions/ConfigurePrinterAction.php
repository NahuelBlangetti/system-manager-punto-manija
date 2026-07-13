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
            ->modalDescription('Se guarda solo en este navegador. Cada PC con el agente de impresión instalado debe elegir su propia impresora.')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalWidth('md')
            ->modalContent(fn () => view('filament.partials.configure-printer'));
    }
}

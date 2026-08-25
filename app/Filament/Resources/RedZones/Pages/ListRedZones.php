<?php

namespace App\Filament\Resources\RedZones\Pages;

use App\Filament\Resources\RedZones\RedZoneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRedZones extends ListRecords
{
    protected static string $resource = RedZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\RedZones\Pages;

use App\Filament\Resources\RedZones\RedZoneResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRedZone extends EditRecord
{
    protected static string $resource = RedZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

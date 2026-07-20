<?php

namespace App\Filament\Resources\WebOrders\Pages;

use App\Filament\Resources\WebOrders\WebOrderResource;
use Filament\Resources\Pages\EditRecord;

class EditWebOrder extends EditRecord
{
    protected static string $resource = WebOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}

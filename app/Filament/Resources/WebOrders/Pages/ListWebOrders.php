<?php

namespace App\Filament\Resources\WebOrders\Pages;

use App\Filament\Resources\WebOrders\WebOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListWebOrders extends ListRecords
{
    protected static string $resource = WebOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}

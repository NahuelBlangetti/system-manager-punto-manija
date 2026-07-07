<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('create');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        $items = $data['items'] ?? [];
        $subtotal = collect($items)->sum(fn ($item) => (float) ($item['subtotal'] ?? 0));
        $discount = (float) ($data['discount'] ?? 0);

        $data['subtotal'] = round($subtotal, 2);
        $data['total'] = round(max(0, $subtotal - $discount), 2);

        return $data;
    }
}

<?php

namespace App\Observers;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ProductObserver
{
    public function created(Product $product): void
    {
        if ($product->stock <= 0) {
            $this->notify($product, 'Producto sin stock', "{$product->name} fue creado sin stock.", danger: true);

            return;
        }

        if ($product->min_stock > 0 && $product->stock <= $product->min_stock) {
            $this->notify($product, 'Stock bajo', "{$product->name} fue creado con stock bajo ({$product->stock} unidades, mínimo {$product->min_stock}).", danger: false);
        }
    }

    public function saved(Product $product): void
    {
        if (! $product->wasChanged('stock')) {
            return;
        }

        $previousStock = (int) $product->getOriginal('stock');
        $currentStock = (int) $product->stock;

        if ($previousStock > 0 && $currentStock <= 0) {
            $this->notify($product, 'Producto sin stock', "{$product->name} se quedó sin stock.", danger: true);

            return;
        }

        if (
            $product->min_stock > 0
            && $previousStock > $product->min_stock
            && $currentStock > 0
            && $currentStock <= $product->min_stock
        ) {
            $this->notify($product, 'Stock bajo', "{$product->name} tiene stock bajo ({$currentStock} unidades, mínimo {$product->min_stock}).", danger: false);
        }
    }

    private function notify(Product $product, string $title, string $body, bool $danger): void
    {
        $action = Action::make('ver')
            ->label('Ver producto')
            ->url(ProductResource::getUrl('edit', ['record' => $product]))
            ->button();

        User::all()->each(function (User $user) use ($title, $body, $danger, $action) {
            $notification = Notification::make()
                ->title($title)
                ->body($body)
                ->actions([$action]);

            $danger ? $notification->danger() : $notification->warning();

            $notification->sendToDatabase($user);
        });
    }
}

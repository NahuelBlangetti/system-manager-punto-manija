<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\ProductImagePath;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (blank($data['image'] ?? null)) {
            return $data;
        }

        $normalized = ProductImagePath::normalize($data['image']);

        if (! ProductImagePath::exists($normalized)) {
            $data['image'] = null;

            return $data;
        }

        $data['image'] = $normalized;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $oldImage = $this->record->image;
        $newImage = $data['image'] ?? null;

        if (filled($newImage) && ! ProductImagePath::isRemote($newImage)) {
            $data['image'] = ProductImagePath::normalize($newImage);
            $newImage = $data['image'];
        }

        if (
            filled($oldImage)
            && $oldImage !== $newImage
            && ! ProductImagePath::isRemote($oldImage)
        ) {
            $normalizedOldImage = ProductImagePath::normalize($oldImage);

            if ($normalizedOldImage) {
                Storage::disk('public')->delete($normalizedOldImage);
            }
        }

        return $data;
    }
}

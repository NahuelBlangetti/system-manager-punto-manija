<?php

use App\Filament\Support\ProductImagePath;
use App\Http\Controllers\MarketplaceController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', [MarketplaceController::class, 'index']);

Route::middleware(['web', 'auth'])->get('/admin/files/{path}', function (string $path) {
    $normalized = ProductImagePath::normalize($path);

    abort_unless(
        $normalized && Storage::disk('public')->exists($normalized),
        404,
    );

    return Storage::disk('public')->response($normalized);
})->where('path', '.*')->name('admin.files.show');

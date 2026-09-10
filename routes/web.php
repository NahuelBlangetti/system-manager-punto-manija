<?php

use App\Filament\Support\ProductImagePath;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\MarketplaceDiscoveryController;
use App\Http\Controllers\MarketplaceOrderController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', [MarketplaceController::class, 'index'])->name('marketplace.index');
Route::get('/producto/{slug}', [MarketplaceController::class, 'show'])->name('marketplace.product');
Route::get('/categoria/{category:slug}', [MarketplaceController::class, 'category'])->name('marketplace.category');
Route::get('/sitemap.xml', [MarketplaceDiscoveryController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [MarketplaceDiscoveryController::class, 'robots'])->name('robots');

Route::post('/marketplace/orders', [MarketplaceOrderController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('marketplace.orders.store');

Route::middleware(['web', 'auth'])->get('/admin/files/{path}', function (string $path) {
    $normalized = ProductImagePath::normalize($path);

    abort_unless(
        $normalized && Storage::disk('public')->exists($normalized),
        404,
    );

    return Storage::disk('public')->response($normalized);
})->where('path', '.*')->name('admin.files.show');

<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::withCount(['products' => function ($q) {
            $q->where('active', true);
        }])
            ->having('products_count', '>', 0)
            ->get()
            ->sortBy(function ($category) {
                $order = config('store.catalog.category_order', []);
                $index = array_search($category->name, $order, true);

                return $index === false ? 999 : $index;
            })
            ->values();

        $browsingCategories = ! $request->filled('category') && ! $request->filled('search');

        if ($browsingCategories) {
            $products = collect();
        } else {
            $query = Product::with('category')
                ->where('active', true)
                ->orderBy('name');

            if ($request->filled('category')) {
                $query->whereHas('category', fn ($q) => $q->where('id', $request->category));
            }

            if ($request->filled('search')) {
                $term = $request->search;
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhere('sku', 'like', "%{$term}%");
                });
            }

            $products = $query->get();
        }

        $selectedCategory = $request->category;
        $search = $request->search;
        $activeCategory = $selectedCategory
            ? $categories->firstWhere('id', (int) $selectedCategory)
            : null;

        $inStockCount = $products->where('stock', '>', 0)->count();

        $catalogTotal = Product::query()->where('active', true)->count();
        $catalogInStock = Product::query()->where('active', true)->where('stock', '>', 0)->count();

        $categoryThumbnails = $this->categoryThumbnails($categories);

        return view('marketplace', compact(
            'products',
            'categories',
            'selectedCategory',
            'search',
            'activeCategory',
            'inStockCount',
            'catalogTotal',
            'catalogInStock',
            'browsingCategories',
            'categoryThumbnails',
        ));
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @return array<int, string>
     */
    private function categoryThumbnails(Collection $categories): array
    {
        $fromProducts = Product::query()
            ->where('active', true)
            ->whereNotNull('image')
            ->whereIn('category_id', $categories->pluck('id'))
            ->orderBy('name')
            ->get(['category_id', 'image'])
            ->unique('category_id')
            ->pluck('image', 'category_id')
            ->all();

        $fromConfig = config('store.catalog.category_images', []);
        $thumbnails = [];

        foreach ($categories as $category) {
            $thumbnails[$category->id] = $fromProducts[$category->id]
                ?? ($fromConfig[$category->name] ?? null);
        }

        return $thumbnails;
    }
}

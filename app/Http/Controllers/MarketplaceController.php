<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\MarketplaceSeo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class MarketplaceController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->legacyCategoryRedirect($request)) {
            return $redirect;
        }

        return $this->catalog($request);
    }

    public function category(Request $request, Category $category): View
    {
        abort_unless(
            $category->products()->where('active', true)->exists(),
            404,
        );

        return $this->catalog($request, $category);
    }

    public function show(string $slug): View
    {
        $product = Product::with('category')
            ->where('active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        $related = Product::with('category')
            ->where('active', true)
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->limit(4)
            ->get();

        $categories = $this->catalogCategories();
        $products = collect();
        $selectedCategory = $product->category_id;
        $search = null;
        $activeCategory = $product->category;
        $inStockCount = 0;
        $catalogTotal = Product::query()->where('active', true)->count();
        $catalogInStock = Product::query()->where('active', true)->where('stock', '>', 0)->count();
        $browsingCategories = false;
        $prettyCategory = false;
        $productDetail = $product;
        $categoryThumbnails = $this->categoryThumbnails($categories);
        $seo = MarketplaceSeo::forProduct($product);

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
            'prettyCategory',
            'productDetail',
            'related',
            'categoryThumbnails',
            'seo',
        ));
    }

    private function catalog(Request $request, ?Category $prettyCategoryModel = null): View
    {
        $categories = $this->catalogCategories();
        $search = $request->search;
        $prettyCategory = $prettyCategoryModel !== null;
        $activeCategory = $prettyCategoryModel;
        $browsingCategories = ! $prettyCategory && ! $request->filled('search');

        if ($browsingCategories) {
            $products = collect();
        } else {
            $query = Product::with('category')
                ->where('active', true)
                ->orderBy('name');

            if ($activeCategory) {
                $query->where('category_id', $activeCategory->id);
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

        $selectedCategory = $activeCategory?->id;
        $inStockCount = $products->where('stock', '>', 0)->count();
        $catalogTotal = Product::query()->where('active', true)->count();
        $catalogInStock = Product::query()->where('active', true)->where('stock', '>', 0)->count();
        $categoryThumbnails = $this->categoryThumbnails($categories);
        $productDetail = null;
        $related = collect();
        $seo = MarketplaceSeo::forCatalog(
            is_string($search) ? $search : null,
            $activeCategory,
            null,
            $prettyCategory,
        );

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
            'prettyCategory',
            'productDetail',
            'related',
            'categoryThumbnails',
            'seo',
        ));
    }

    /**
     * @return Collection<int, Category>
     */
    private function catalogCategories(): Collection
    {
        return Category::withCount(['products' => function ($q) {
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
    }

    private function legacyCategoryRedirect(Request $request): ?RedirectResponse
    {
        if (! $request->filled('category')) {
            return null;
        }

        $raw = $request->query('category');
        if (! is_numeric($raw)) {
            return null;
        }

        $category = Category::query()->whereKey((int) $raw)->first();
        if (! filled($category?->slug)) {
            return null;
        }

        $query = $request->query();
        unset($query['category']);

        $url = route('marketplace.category', $category);
        if ($query !== []) {
            $url .= '?'.http_build_query($query);
        }

        return redirect()->to($url, 301);
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

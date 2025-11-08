<?php

use App\Models\Product;
use App\Models\ProductSearch;
use App\Services\EmbeddingService;
use Illuminate\Support\Facades\Route;

Route::get('/', function (EmbeddingService $embeddingService) {
    $query = request()->get('q');
    $perPage = 24;

    if ($query) {
        try {
            // Step 1: Generate embedding for the search query
            $queryEmbedding = $embeddingService->generateEmbedding($query);

            // Step 2: Search Tiger Data for matching product IDs (ordered by semantic relevance)
            // Only return products with distance < 0.7 (highly relevant results only)
            $productIds = ProductSearch::searchByEmbedding($queryEmbedding, limit: 20, maxDistance: 0.8);

            // Step 3: Fetch full product data from local database using the IDs
            if (! empty($productIds)) {
                $productsCollection = Product::query()
                    ->active()
                    ->whereIn('id', $productIds)
                    ->get();

                // Sort products by the semantic relevance order from Tiger Data (most relevant first)
                $orderedProducts = collect($productIds)
                    ->map(fn ($id) => $productsCollection->firstWhere('id', $id))
                    ->filter();

                // Paginate the ordered collection
                $currentPage = request()->get('page', 1);
                $products = new \Illuminate\Pagination\LengthAwarePaginator(
                    $orderedProducts->forPage($currentPage, $perPage),
                    $orderedProducts->count(),
                    $perPage,
                    $currentPage,
                    ['path' => request()->url(), 'query' => request()->query()]
                );
            } else {
                // No matching products found
                $products = Product::query()
                    ->whereRaw('1 = 0')
                    ->paginate($perPage);
            }
        } catch (\Exception $e) {
            // Fallback to empty results if search fails
            logger()->error('Failed to perform semantic search', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            $products = Product::query()
                ->whereRaw('1 = 0')
                ->paginate($perPage);
        }
    } else {
        // No search query, show recent active products
        $products = Product::query()
            ->active()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    return view('welcome', [
        'products' => $products,
        'searchQuery' => $query ?? '',
    ]);
});

Route::get('/products/{product}', function (Product $product) {
    // Only allow viewing active products
    abort_unless($product->status === 'active', 404);

    return view('products.show', [
        'product' => $product,
    ]);
})->name('products.show');

<?php

use App\Models\Product;
use App\Services\EmbeddingService;
use App\Services\HybridSearchService;
use Illuminate\Support\Facades\Route;

Route::get('/', function (EmbeddingService $embeddingService, HybridSearchService $hybridSearchService) {
    $query = request()->get('q');
    $perPage = 24;

    if ($query) {
        try {
            // Step 1: Generate embedding for the search query
            $queryEmbedding = $embeddingService->generateEmbedding($query);

            // Step 2: Perform hybrid search combining semantic vector search with BM25 keyword search
            // Uses Reciprocal Rank Fusion to combine results from both search methods
            // Returns product IDs ordered by combined relevance score (best of both worlds)
            $productIds = $hybridSearchService->search($query, $queryEmbedding, limit: 20);

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
            logger()->error('Failed to perform hybrid search', [
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

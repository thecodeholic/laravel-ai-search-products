<?php

namespace App\Services;

use App\Models\ProductSearch;

class HybridSearchService
{
    /**
     * Perform hybrid search combining semantic vector search with BM25 keyword search.
     *
     * Uses Reciprocal Rank Fusion (RRF) to combine results from both search methods.
     *
     * @param  string  $query  The search query text
     * @param  array  $embedding  The embedding vector for semantic search
     * @param  int  $limit  Maximum number of results to return
     * @param  float  $semanticWeight  Weight for semantic search results (0.0 to 1.0)
     * @param  float  $keywordWeight  Weight for keyword search results (0.0 to 1.0)
     * @return array Array of product IDs ordered by combined relevance score
     */
    public function search(
        string $query,
        array $embedding,
        int $limit = 20,
        float $semanticWeight = 0.6,
        float $keywordWeight = 0.4
    ): array {
        // Get results from both search methods
        $semanticResults = $this->getSemanticResults($embedding, $limit);
        $bm25Results = $this->getBM25Results($query, $limit);

        // Fuse the results using Reciprocal Rank Fusion
        $fusedResults = $this->fuseResults($semanticResults, $bm25Results, $semanticWeight, $keywordWeight);

        // Return top N product IDs
        return array_slice(array_keys($fusedResults), 0, $limit);
    }

    /**
     * Get semantic search results using vector similarity.
     *
     * @param  array  $embedding  The embedding vector
     * @param  int  $limit  Maximum number of results
     * @return array Array of product IDs with their ranks
     */
    protected function getSemanticResults(array $embedding, int $limit): array
    {
        $productIds = ProductSearch::searchByEmbedding($embedding, limit: $limit, maxDistance: 0.78);

        // Convert to rank array (product_id => rank)
        $ranked = [];
        foreach ($productIds as $rank => $productId) {
            $ranked[$productId] = $rank + 1; // Rank starts at 1
        }

        return $ranked;
    }

    /**
     * Get BM25 keyword search results.
     *
     * @param  string  $query  The search query
     * @param  int  $limit  Maximum number of results
     * @return array Array of product IDs with their ranks
     */
    protected function getBM25Results(string $query, int $limit): array
    {
        $productIds = ProductSearch::searchByBM25($query, limit: $limit);

        // Convert to rank array (product_id => rank)
        $ranked = [];
        foreach ($productIds as $rank => $productId) {
            $ranked[$productId] = $rank + 1; // Rank starts at 1
        }

        return $ranked;
    }

    /**
     * Fuse results from multiple search methods using Reciprocal Rank Fusion.
     *
     * RRF Formula: score = weight * (1.0 / (k + rank))
     * Where k=60 is the RRF constant that balances top vs lower-ranked results.
     *
     * @param  array  $semanticResults  Semantic search results (product_id => rank)
     * @param  array  $bm25Results  BM25 search results (product_id => rank)
     * @param  float  $semanticWeight  Weight for semantic results
     * @param  float  $keywordWeight  Weight for keyword results
     * @return array Array of product IDs with combined scores, sorted by score descending
     */
    protected function fuseResults(
        array $semanticResults,
        array $bm25Results,
        float $semanticWeight,
        float $keywordWeight
    ): array {
        $k = 60; // RRF constant
        $combined = [];

        // Get all unique product IDs from both result sets
        $allProductIds = array_unique(array_merge(
            array_keys($semanticResults),
            array_keys($bm25Results)
        ));

        // Calculate combined score for each product
        foreach ($allProductIds as $productId) {
            $score = 0.0;

            // Add semantic score if product appears in semantic results
            if (isset($semanticResults[$productId])) {
                $score += $semanticWeight * (1.0 / ($k + $semanticResults[$productId]));
            }

            // Add BM25 score if product appears in BM25 results
            if (isset($bm25Results[$productId])) {
                $score += $keywordWeight * (1.0 / ($k + $bm25Results[$productId]));
            }

            $combined[$productId] = $score;
        }

        // Sort by combined score (highest first)
        arsort($combined);

        return $combined;
    }
}

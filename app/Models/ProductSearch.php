<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductSearch extends Model
{
    protected $connection = 'tiger';

    protected $table = 'product_chunks';

    public $timestamps = false;

    /**
     * Perform semantic search on product chunks and return matching product IDs filtered by relevance.
     *
     * This searches across all product chunks and returns distinct product IDs,
     * using the best (closest) matching chunk for each product.
     *
     * @param  array  $queryEmbedding  The embedding vector to search for
     * @param  int  $limit  Maximum number of products to return
     * @param  float  $maxDistance  Maximum distance threshold (0.0 = identical, higher = less similar)
     * @return array Array of product IDs ordered by relevance (best chunk match)
     */
    public static function searchByEmbedding(array $queryEmbedding, int $limit = 20, float $maxDistance = 0.7): array
    {
        $embeddingString = '['.implode(',', array_map(fn ($val) => (float) $val, $queryEmbedding)).']';

        // Search product chunks and get the best matching chunk per product
        $results = DB::connection('tiger')
            ->table('product_chunks')
            ->select(
                'product_id',
                DB::raw("MIN(embeddings <=> '{$embeddingString}'::vector) as min_distance")
            )
            ->whereRaw("(embeddings <=> '{$embeddingString}'::vector) < ?", [$maxDistance])
            ->groupBy('product_id')
            ->orderBy('min_distance', 'asc')
            ->limit($limit)
            ->get();

        return $results->pluck('product_id')->toArray();
    }

    /**
     * Search product chunks and return detailed results including chunk information.
     *
     * @param  array  $queryEmbedding  The embedding vector to search for
     * @param  int  $limit  Maximum number of chunks to return
     * @param  float  $maxDistance  Maximum distance threshold
     * @return \Illuminate\Support\Collection Collection of chunks with distance and metadata
     */
    public static function searchChunksWithDetails(array $queryEmbedding, int $limit = 50, float $maxDistance = 0.7): \Illuminate\Support\Collection
    {
        $embeddingString = '['.implode(',', array_map(fn ($val) => (float) $val, $queryEmbedding)).']';

        return DB::connection('tiger')
            ->table('product_chunks')
            ->select(
                'id',
                'product_id',
                'chunk_text',
                'chunk_index',
                'metadata',
                DB::raw("(embeddings <=> '{$embeddingString}'::vector) as distance")
            )
            ->whereRaw("(embeddings <=> '{$embeddingString}'::vector) < ?", [$maxDistance])
            ->orderBy('distance', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Perform BM25 keyword search on product chunks and return matching product IDs.
     *
     * Uses Tiger Cloud's pg_textsearch extension for BM25 ranking.
     * Searches across all product chunks and returns distinct product IDs,
     * using the best (lowest score) matching chunk for each product.
     *
     * Note: BM25 scores are negative - more negative = better match.
     *
     * @param  string  $query  The search query text
     * @param  int  $limit  Maximum number of products to return
     * @param  float  $scoreThreshold  Maximum score threshold (more negative = better, e.g., -2.0)
     * @return array Array of product IDs ordered by BM25 relevance (best chunk match)
     */
    public static function searchByBM25(string $query, int $limit = 20, float $scoreThreshold = -1.0): array
    {
        // Escape the query for safe SQL usage
        $escapedQuery = addslashes($query);

        // Search product chunks using BM25 and get the best matching chunk per product
        // BM25 returns negative scores where lower (more negative) = better match
        $results = DB::connection('tiger')
            ->table('product_chunks')
            ->select(
                'product_id',
                DB::raw("MIN(chunk_text <@> to_bm25query('{$escapedQuery}', 'product_chunks_bm25_idx')) as min_score")
            )
            ->whereRaw("chunk_text <@> to_bm25query('{$escapedQuery}', 'product_chunks_bm25_idx') < ?", [$scoreThreshold])
            ->groupBy('product_id')
            ->orderBy('min_score', 'asc')
            ->limit($limit)
            ->get();

        return $results->pluck('product_id')->toArray();
    }
}

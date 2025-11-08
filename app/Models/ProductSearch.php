<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductSearch extends Model
{
    protected $connection = 'tiger';

    protected $table = 'products';

    public $timestamps = false;

    /**
     * Perform semantic search and return matching product IDs filtered by relevance.
     *
     * @param  array  $queryEmbedding  The embedding vector to search for
     * @param  int  $limit  Maximum number of results to return
     * @param  float  $maxDistance  Maximum distance threshold (0.0 = identical, higher = less similar)
     * @return array Array of product IDs ordered by relevance
     */
    public static function searchByEmbedding(array $queryEmbedding, int $limit = 20, float $maxDistance = 0.7): array
    {
        $embeddingString = '['.implode(',', array_map(fn ($val) => (float) $val, $queryEmbedding)).']';

        $results = DB::connection('tiger')
            ->table('products')
            ->select(
                'id',
                DB::raw("(embeddings <=> '{$embeddingString}'::vector) as distance")
            )
            ->whereRaw("(embeddings <=> '{$embeddingString}'::vector) < ?", [$maxDistance])
            ->orderBy('distance', 'asc')
            ->limit($limit)
            ->get();

        return $results->pluck('id')->toArray();
    }
}

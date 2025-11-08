<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductChunk extends Model
{
    protected $connection = 'tiger';

    protected $table = 'product_chunks';

    protected $fillable = [
        'product_id',
        'chunk_text',
        'chunk_index',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'chunk_index' => 'integer',
        ];
    }

    /**
     * Get the product that owns this chunk.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

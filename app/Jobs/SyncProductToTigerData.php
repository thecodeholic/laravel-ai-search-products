<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\EmbeddingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncProductToTigerData implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Product $product,
        public bool $shouldDelete = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(EmbeddingService $embeddingService): void
    {
        try {
            if ($this->shouldDelete) {
                // Delete product from Tiger Data
                DB::connection('tiger')
                    ->table('products')
                    ->where('id', $this->product->id)
                    ->delete();

                Log::info('Deleted product from Tiger Data', ['product_id' => $this->product->id]);

                return;
            }

            // Combine title and description for embedding
            $inputText = trim(($this->product->title ?? '').' '.($this->product->description ?? ''));

            if (empty($inputText)) {
                Log::warning('Skipping product with empty title and description', ['product_id' => $this->product->id]);

                return;
            }

            // Generate embeddings using OpenAI
            $embedding = $embeddingService->generateEmbedding($inputText);

            // Format embeddings as PostgreSQL vector string
            $embeddingString = '['.implode(',', array_map(fn ($val) => (float) $val, $embedding)).']';

            // Upsert into Tiger database
            DB::connection('tiger')->table('products')->updateOrInsert(
                ['id' => $this->product->id],
                [
                    'id' => $this->product->id,
                    'title' => $this->product->title,
                    'description' => $this->product->description,
                    'embeddings' => DB::raw("'{$embeddingString}'::vector"),
                    'updated_at' => now(),
                    'created_at' => $this->product->created_at ?? now(),
                ]
            );

            Log::info('Synced product to Tiger Data', ['product_id' => $this->product->id, 'title' => $this->product->title]);
        } catch (\Exception $e) {
            Log::error('Failed to sync product to Tiger Data', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

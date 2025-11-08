<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\EmbeddingService;
use App\Services\TextChunkingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class SyncProductChunks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:sync-chunks
                            {--chunk-size=500 : Size of each text chunk in characters}
                            {--overlap=50 : Number of characters to overlap between chunks}
                            {--model=text-embedding-3-small : The OpenAI embedding model to use}
                            {--fresh : Delete all existing chunks before syncing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Split product descriptions into chunks and sync them with embeddings to Tiger database';

    /**
     * Execute the console command.
     */
    public function handle(TextChunkingService $chunkingService, EmbeddingService $embeddingService): int
    {
        $model = $this->option('model');
        $chunkSize = (int) $this->option('chunk-size');
        $overlap = (int) $this->option('overlap');
        $fresh = $this->option('fresh');

        // Validate OpenAI API key is configured
        if (empty(Config::get('openai.api_key'))) {
            $this->error('OpenAI API key is not configured.');
            $this->warn('Please add OPENAI_API_KEY to your .env file.');
            $this->info('You can get your API key from: https://platform.openai.com/api-keys');

            return self::FAILURE;
        }

        $this->info('Starting product chunks synchronization...');
        $this->info("Chunk size: {$chunkSize} characters, Overlap: {$overlap} characters");
        $this->newLine();

        // Delete existing chunks if fresh option is set
        if ($fresh) {
            $this->warn('Deleting all existing product chunks...');
            DB::connection('tiger')->table('product_chunks')->truncate();
            $this->info('Existing chunks deleted.');
            $this->newLine();
        }

        $products = Product::all();
        $total = $products->count();

        if ($total === 0) {
            $this->warn('No products found in the local database.');

            return self::FAILURE;
        }

        $this->info("Found {$total} products to process.");
        $this->newLine();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $successCount = 0;
        $errorCount = 0;
        $totalChunks = 0;

        foreach ($products as $product) {
            try {
                // Skip products with empty title and description
                if (empty(trim($product->title ?? '')) && empty(trim($product->description ?? ''))) {
                    $this->newLine();
                    $this->warn("Skipping product ID {$product->id}: empty title and description.");
                    $errorCount++;
                    $bar->advance();

                    continue;
                }

                // Delete existing chunks for this product
                DB::connection('tiger')
                    ->table('product_chunks')
                    ->where('product_id', $product->id)
                    ->delete();

                // Create chunks from product title and description
                $chunks = $chunkingService->chunkProductDescription(
                    $product->title ?? '',
                    $product->description ?? '',
                    $chunkSize,
                    $overlap
                );

                // Process each chunk
                foreach ($chunks as $index => $chunkText) {
                    // Generate embeddings for this chunk
                    $embedding = $embeddingService->generateEmbedding($chunkText, $model);

                    // Format embeddings as PostgreSQL vector string
                    $embeddingString = '['.implode(',', array_map(fn ($val) => (float) $val, $embedding)).']';

                    // Prepare metadata
                    $metadata = json_encode([
                        'product_title' => $product->title,
                        'product_sku' => $product->sku,
                        'chunk_length' => strlen($chunkText),
                        'total_chunks' => count($chunks),
                    ]);

                    // Insert chunk into Tiger database
                    DB::connection('tiger')->table('product_chunks')->insert([
                        'product_id' => $product->id,
                        'chunk_text' => $chunkText,
                        'chunk_index' => $index,
                        'metadata' => $metadata,
                        'embeddings' => DB::raw("'{$embeddingString}'::vector"),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $totalChunks++;
                }

                $successCount++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Failed to process product ID {$product->id} ({$product->title}): {$e->getMessage()}");
                $errorCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Synchronization completed!');
        $this->info("Successfully processed: {$successCount} products");
        $this->info("Total chunks created: {$totalChunks}");
        $this->info('Average chunks per product: '.($successCount > 0 ? round($totalChunks / $successCount, 2) : 0));

        if ($errorCount > 0) {
            $this->warn("Errors encountered: {$errorCount}");
        }

        return self::SUCCESS;
    }
}

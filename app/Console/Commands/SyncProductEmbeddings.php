<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use OpenAI\Laravel\Facades\OpenAI;

class SyncProductEmbeddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:sync-embeddings
                            {--model=text-embedding-3-small : The OpenAI embedding model to use}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate embeddings for all products and sync them to Tiger database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $model = $this->option('model');

        // Validate OpenAI API key is configured
        if (empty(Config::get('openai.api_key'))) {
            $this->error('OpenAI API key is not configured.');
            $this->warn('Please add OPENAI_API_KEY to your .env file.');
            $this->info('You can get your API key from: https://platform.openai.com/api-keys');

            return self::FAILURE;
        }

        $this->info('Starting product embeddings synchronization...');

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

        foreach ($products as $product) {
            try {
                // Combine title and description
                $inputText = trim(($product->title ?? '').' '.($product->description ?? ''));

                if (empty($inputText)) {
                    $this->newLine();
                    $this->warn("Skipping product ID {$product->id}: empty title and description.");
                    $errorCount++;
                    $bar->advance();

                    continue;
                }

                // Generate embeddings using OpenAI
                $response = OpenAI::embeddings()->create([
                    'model' => $model,
                    'input' => $inputText,
                ]);

                $embedding = $response->embeddings[0]->embedding;

                // Format embeddings as PostgreSQL array string
                $embeddingString = '{'.implode(',', array_map(fn ($val) => (float) $val, $embedding)).'}';

                // Upsert into Tiger database (match by product ID)
                DB::connection('tiger')->table('products')->updateOrInsert(
                    ['id' => $product->id],
                    [
                        'id' => $product->id,
                        'title' => $product->title,
                        'description' => $product->description,
                        'embeddings' => DB::raw("'{$embeddingString}'::real[]"),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

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
        $this->info("Successfully processed: {$successCount}");
        if ($errorCount > 0) {
            $this->warn("Errors encountered: {$errorCount}");
        }

        return self::SUCCESS;
    }
}

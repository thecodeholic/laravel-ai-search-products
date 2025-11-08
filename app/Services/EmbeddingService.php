<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;

class EmbeddingService
{
    /**
     * Generate embeddings for the given text using OpenAI API.
     */
    public function generateEmbedding(string $text, string $model = 'text-embedding-3-small'): array
    {
        if (empty(trim($text))) {
            throw new \InvalidArgumentException('Text cannot be empty for embedding generation.');
        }

        $response = OpenAI::embeddings()->create([
            'model' => $model,
            'input' => $text,
        ]);

        return $response->embeddings[0]->embedding;
    }
}

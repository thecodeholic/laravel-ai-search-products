<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enable pgvector and pgvectorscale extensions
        DB::connection('tiger')->statement('CREATE EXTENSION IF NOT EXISTS vector');
        DB::connection('tiger')->statement('CREATE EXTENSION IF NOT EXISTS vectorscale CASCADE');

        // Convert embeddings column from real[] to vector(1536)
        DB::connection('tiger')->statement('ALTER TABLE products ALTER COLUMN embeddings TYPE vector(1536) USING embeddings::vector(1536)');

        // Create StreamingDiskANN index for fast similarity search
        // This index provides the best performance for vector similarity queries
        DB::connection('tiger')->statement('CREATE INDEX IF NOT EXISTS products_embeddings_idx ON products USING diskann (embeddings)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the index
        DB::connection('tiger')->statement('DROP INDEX IF EXISTS products_embeddings_idx');

        // Convert back to real[]
        DB::connection('tiger')->statement('ALTER TABLE products ALTER COLUMN embeddings TYPE real[] USING embeddings::real[]');
    }
};

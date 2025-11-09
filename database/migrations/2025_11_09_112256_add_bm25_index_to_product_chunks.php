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
        // Enable pg_textsearch extension for BM25 full-text search
        DB::connection('tiger')->statement('CREATE EXTENSION IF NOT EXISTS pg_textsearch');

        // Create BM25 index on chunk_text for keyword-based search
        // This enables fast full-text search with BM25 ranking algorithm
        DB::connection('tiger')->statement("
            CREATE INDEX product_chunks_bm25_idx ON product_chunks
            USING bm25(chunk_text)
            WITH (text_config='english')
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the BM25 index
        DB::connection('tiger')->statement('DROP INDEX IF EXISTS product_chunks_bm25_idx');
    }
};

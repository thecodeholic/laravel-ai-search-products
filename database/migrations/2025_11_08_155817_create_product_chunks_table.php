<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

        // Create product_chunks table
        Schema::connection('tiger')->create('product_chunks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->text('chunk_text');
            $table->integer('chunk_index')->index();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        // Add embeddings column as PostgreSQL vector(1536)
        DB::connection('tiger')->statement('ALTER TABLE product_chunks ADD COLUMN embeddings vector(1536)');

        // Create StreamingDiskANN index for fast similarity search
        DB::connection('tiger')->statement('CREATE INDEX product_chunks_embeddings_idx ON product_chunks USING diskann (embeddings)');

        // Drop the products table from Tiger Data database
        Schema::connection('tiger')->dropIfExists('products');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate products table (basic structure)
        Schema::connection('tiger')->create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Add embeddings column back to products
        DB::connection('tiger')->statement('ALTER TABLE products ADD COLUMN embeddings vector(1536)');
        DB::connection('tiger')->statement('CREATE INDEX products_embeddings_idx ON products USING diskann (embeddings)');

        // Drop product_chunks table
        Schema::connection('tiger')->dropIfExists('product_chunks');
    }
};

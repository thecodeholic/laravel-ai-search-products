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
        Schema::connection('tiger')->create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Add embeddings column as PostgreSQL real array
        DB::connection('tiger')->statement('ALTER TABLE products ADD COLUMN embeddings real[]');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tiger')->dropIfExists('products');
    }
};

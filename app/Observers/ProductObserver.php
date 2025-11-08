<?php

namespace App\Observers;

use App\Jobs\SyncProductToTigerData;
use App\Models\Product;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        // Queue job to sync new product to Tiger Data
        SyncProductToTigerData::dispatch($product);
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        // Only sync if title or description changed (fields used for embeddings)
        if ($product->wasChanged(['title', 'description'])) {
            SyncProductToTigerData::dispatch($product);
        }
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        // Queue job to delete product from Tiger Data
        SyncProductToTigerData::dispatch($product, shouldDelete: true);
    }

}

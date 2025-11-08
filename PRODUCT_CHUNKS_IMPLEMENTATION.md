# Product Chunks Implementation

This document describes the new product chunking functionality that splits product descriptions into smaller chunks for better semantic search with embeddings.

---

## Overview

The system now splits product descriptions into multiple chunks instead of embedding the entire description as one unit. This allows for:
- Better handling of large product descriptions
- More precise semantic matching on specific parts of descriptions
- Improved search relevance by matching against the most relevant chunk

---

## Files Created

### 1. **TextChunkingService** (`app/Services/TextChunkingService.php`)
Service responsible for splitting text into overlapping chunks.

**Features:**
- Configurable chunk size (default: 500 characters)
- Configurable overlap between chunks (default: 50 characters)
- Smart word boundary detection (avoids breaking words)
- Product-specific chunking that prepends product title to each chunk for context

### 2. **ProductChunk Model** (`app/Models/ProductChunk.php`)
Eloquent model for the `product_chunks` table in Tiger Data.

**Attributes:**
- `product_id` - Reference to the original product
- `chunk_text` - The actual chunk content
- `chunk_index` - Order of the chunk (0, 1, 2, ...)
- `embeddings` - Vector(1536) embedding for this chunk
- `metadata` - JSONB field storing product title, SKU, and chunk info

### 3. **SyncProductChunks Command** (`app/Console/Commands/SyncProductChunks.php`)
Artisan command to synchronize products and their chunks to Tiger Data.

**Command:** `php artisan products:sync-chunks`

**Options:**
- `--chunk-size=500` - Size of each chunk in characters
- `--overlap=50` - Characters to overlap between chunks
- `--model=text-embedding-3-small` - OpenAI embedding model
- `--fresh` - Delete all existing chunks before syncing

---

## Files Modified

### 4. **ProductSearch Model** (`app/Models/ProductSearch.php`)
Updated to search `product_chunks` table instead of `products` table.

**Changes:**
- Now searches across all product chunks
- Groups results by product_id
- Returns products ranked by their best matching chunk
- Added `searchChunksWithDetails()` method for detailed chunk results

### 5. **Search Route** (`routes/web.php`)
Updated comments to reflect that search now uses product chunks.

The search functionality itself didn't need code changes - it continues to work seamlessly through the updated `ProductSearch::searchByEmbedding()` method.

---

## Database Schema

### `product_chunks` Table (Tiger Data)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| product_id | bigint | Reference to product (nullable, indexed) |
| chunk_text | text | The chunk content |
| chunk_index | integer | Order of chunk (indexed) |
| embeddings | vector(1536) | OpenAI embedding vector |
| metadata | jsonb | Additional info (title, SKU, etc.) |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Update timestamp |

**Indexes:**
- `product_chunks_embeddings_idx` - StreamingDiskANN index on embeddings for fast similarity search
- Index on `product_id`
- Index on `chunk_index`

---

## How to Use

### Step 1: Run the Migration

First, run the migration to create the `product_chunks` table:

```bash
php artisan migrate
```

This will:
- Create the `product_chunks` table in Tiger Data
- Create the embeddings column and indexes
- Drop the old `products` table from Tiger Data

### Step 2: Sync Product Chunks

Run the sync command to chunk all products and generate embeddings:

```bash
php artisan products:sync-chunks
```

**With Options:**
```bash
# Use larger chunks (800 chars)
php artisan products:sync-chunks --chunk-size=800

# Fresh sync (delete all existing chunks first)
php artisan products:sync-chunks --fresh

# Custom chunk size and overlap
php artisan products:sync-chunks --chunk-size=600 --overlap=100
```

### Step 3: Search Works Automatically

The search functionality will automatically use the new product chunks. No additional configuration needed!

---

## How It Works

### Chunking Process

1. **Read Product**: Gets product from local SQLite database
2. **Create Chunks**: Splits `title + description` into chunks
   - Each chunk is ~500 characters (configurable)
   - Chunks overlap by 50 characters (configurable)
   - Title is prepended to each chunk for context
   - Example: "Nike Air Max - This revolutionary shoe features..."
3. **Generate Embeddings**: Each chunk gets its own embedding via OpenAI API
4. **Store in Tiger Data**: Chunks saved to `product_chunks` table with embeddings

### Search Process

1. **User Query**: User searches for "comfortable running shoes"
2. **Generate Embedding**: Query converted to embedding vector
3. **Search Chunks**: Vector similarity search across all chunks
4. **Group by Product**: Results grouped by `product_id`, using best match
5. **Fetch Products**: Product IDs used to fetch full product data from local DB
6. **Display Results**: Products shown ranked by relevance

---

## Example Output

When you run `php artisan products:sync-chunks`, you'll see:

```
Starting product chunks synchronization...
Chunk size: 500 characters, Overlap: 50 characters

Found 50 products to process.

 50/50 [████████████████████████████] 100%

Synchronization completed!
Successfully processed: 50 products
Total chunks created: 73
Average chunks per product: 1.46
```

---

## Benefits

✅ **Better Search Accuracy**: Matches specific parts of descriptions
✅ **Handles Long Descriptions**: No more truncation or information loss
✅ **Context Preserved**: Title included with each chunk
✅ **Scalable**: Works with products of any description length
✅ **Backward Compatible**: Search API remains the same

---

## Technical Notes

- **Chunk Size**: 500 characters is optimal for `text-embedding-3-small` model
- **Overlap**: 50 characters ensures context isn't lost at boundaries
- **Vector Distance**: Uses cosine distance operator (`<=>`) for similarity
- **Index Type**: StreamingDiskANN provides best performance for vector search
- **Metadata**: Stored in JSONB for flexible querying and debugging

---

## Troubleshooting

### Command not found
Run `php artisan list | grep products` to verify the command is registered.

### OpenAI API Error
Make sure `OPENAI_API_KEY` is set in your `.env` file.

### Database Connection Error
Verify Tiger Data connection settings in `.env` and that the migration ran successfully.

### No Search Results
1. Run `products:sync-chunks` to ensure chunks are created
2. Check that products exist in local database
3. Verify `product_chunks` table has data in Tiger Data

---

## Next Steps

The command is ready to use! Simply run:

```bash
php artisan products:sync-chunks
```

And your products will be chunked and synchronized to Tiger Data with embeddings.


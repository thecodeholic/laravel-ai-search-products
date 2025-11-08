<?php

namespace App\Services;

class TextChunkingService
{
    /**
     * Split text into overlapping chunks of a specified size.
     *
     * @param  string  $text  The text to chunk
     * @param  int  $chunkSize  Target size for each chunk (in characters)
     * @param  int  $overlap  Number of characters to overlap between chunks
     * @return array Array of text chunks
     */
    public function chunkText(string $text, int $chunkSize = 500, int $overlap = 50): array
    {
        $text = trim($text);

        if (empty($text)) {
            return [];
        }

        // If text is smaller than chunk size, return as single chunk
        if (strlen($text) <= $chunkSize) {
            return [$text];
        }

        $chunks = [];
        $start = 0;
        $textLength = strlen($text);

        while ($start < $textLength) {
            // Extract chunk
            $chunk = substr($text, $start, $chunkSize);

            // If this is not the last chunk, try to break at word boundary
            if ($start + $chunkSize < $textLength) {
                // Find last space in chunk to avoid breaking words
                $lastSpace = strrpos($chunk, ' ');
                if ($lastSpace !== false && $lastSpace > $chunkSize * 0.7) {
                    // Only break at word boundary if it's not too far back
                    $chunk = substr($chunk, 0, $lastSpace);
                }
            }

            $chunks[] = trim($chunk);

            // Move start position forward, accounting for overlap
            $actualChunkLength = strlen($chunk);
            $start += $actualChunkLength - $overlap;

            // Prevent infinite loop if chunk is too small
            if ($actualChunkLength <= $overlap) {
                $start += $overlap + 1;
            }
        }

        return array_filter($chunks, fn ($chunk) => ! empty(trim($chunk)));
    }

    /**
     * Create chunks with product context (title + description chunks).
     *
     * @param  string  $title  Product title
     * @param  string  $description  Product description
     * @param  int  $chunkSize  Target size for each chunk
     * @param  int  $overlap  Number of characters to overlap
     * @return array Array of chunks with title prepended
     */
    public function chunkProductDescription(
        string $title,
        string $description,
        int $chunkSize = 500,
        int $overlap = 50
    ): array {
        $title = trim($title);
        $description = trim($description);

        // If description is empty or very short, return single chunk with title
        if (empty($description) || strlen($description) < 100) {
            return [trim($title.' '.$description)];
        }

        // Split description into chunks
        $descriptionChunks = $this->chunkText($description, $chunkSize, $overlap);

        // Prepend title to each chunk for context
        return array_map(
            fn ($chunk) => trim($title.' - '.$chunk),
            $descriptionChunks
        );
    }
}

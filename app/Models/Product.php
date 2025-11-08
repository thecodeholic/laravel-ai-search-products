<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    protected $fillable = [
        'title',
        'description',
        'image',
        'price',
        'sku',
        'stock',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    /**
     * Scope a query to search products by title and description.
     */
    public function scopeSearch(Builder $query, string $searchTerm): Builder
    {
        $driver = $query->getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            // Use FULLTEXT search for MySQL/MariaDB
            return $query->whereFullText(['title', 'description'], $searchTerm)
                ->orWhere('title', 'LIKE', "%{$searchTerm}%")
                ->orWhere('description', 'LIKE', "%{$searchTerm}%");
        } elseif ($driver === 'pgsql') {
            // Use PostgreSQL full-text search
            return $query->whereFullText(['title', 'description'], $searchTerm)
                ->orWhere('title', 'ILIKE', "%{$searchTerm}%")
                ->orWhere('description', 'ILIKE', "%{$searchTerm}%");
        } else {
            // Fallback to LIKE for SQLite and others
            return $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'LIKE', "%{$searchTerm}%");
            });
        }
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}

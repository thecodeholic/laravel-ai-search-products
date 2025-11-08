<?php

use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $query = request()->get('q');
    $perPage = 24;

    $products = Product::query()
        ->active()
        ->when($query, function ($q) use ($query) {
            return $q->search($query);
        })
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);

    return view('welcome', [
        'products' => $products,
        'searchQuery' => $query ?? '',
    ]);
});

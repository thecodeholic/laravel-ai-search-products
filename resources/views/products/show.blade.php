<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $product->title }} - {{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                /* Tailwind CSS will be loaded via Vite in production */
            </style>
        @endif
    </head>
    <body class="bg-gray-900 min-h-screen">
        <div class="container mx-auto px-4 py-8 max-w-7xl">
            <!-- Back Button -->
            <div class="mb-8">
                <a
                    href="{{ url('/') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700 transition-colors"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                    </svg>
                    Back to Products
                </a>
            </div>

            <!-- Product Details -->
            <div class="bg-gray-800 rounded-lg overflow-hidden shadow-xl">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Product Image -->
                    <div class="bg-white p-8 flex items-center justify-center lg:min-h-[600px]">
                        <img
                            src="{{ $product->image ?? 'https://via.placeholder.com/600x600?text=No+Image' }}"
                            alt="{{ $product->title }}"
                            class="max-w-full max-h-full object-contain rounded-lg"
                            onerror="this.src='https://via.placeholder.com/600x600?text=No+Image'"
                        />
                    </div>

                    <!-- Product Information -->
                    <div class="p-8 flex flex-col">
                        <!-- Product Title -->
                        <h1 class="text-white font-bold text-3xl lg:text-4xl mb-4">
                            {{ $product->title }}
                        </h1>

                        <!-- Price -->
                        <div class="mb-6">
                            <span class="text-4xl lg:text-5xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-blue-400">
                                ${{ number_format($product->price, 2) }}
                            </span>
                        </div>

                        <!-- Stock and SKU Info -->
                        <div class="flex flex-wrap gap-4 mb-6">
                            <!-- Stock Status -->
                            <div class="flex items-center gap-2">
                                @if($product->stock > 0)
                                    <span class="inline-block w-3 h-3 bg-green-500 rounded-full"></span>
                                    <span class="text-green-400 font-medium">In Stock ({{ $product->stock }} available)</span>
                                @else
                                    <span class="inline-block w-3 h-3 bg-red-500 rounded-full"></span>
                                    <span class="text-red-400 font-medium">Out of Stock</span>
                                @endif
                            </div>

                            <!-- SKU -->
                            @if($product->sku)
                                <div class="text-gray-400">
                                    <span class="font-medium">SKU:</span> {{ $product->sku }}
                                </div>
                            @endif
                        </div>

                        <!-- Description Section -->
                        <div class="mb-8 flex-grow">
                            <h2 class="text-white font-semibold text-xl mb-4">Product Description</h2>
                            <div class="text-gray-300 text-base leading-relaxed whitespace-pre-line">
                                {{ $product->description }}
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 mt-auto">
                            <button
                                type="button"
                                class="flex-1 px-8 py-4 bg-gradient-to-r from-purple-600 to-blue-600 text-white text-lg font-semibold rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                                {{ $product->stock <= 0 ? 'disabled' : '' }}
                            >
                                @if($product->stock > 0)
                                    Add to Cart
                                @else
                                    Out of Stock
                                @endif
                            </button>
                            <button
                                type="button"
                                class="px-8 py-4 bg-gray-700 text-white text-lg font-semibold rounded-lg hover:bg-gray-600 transition-colors"
                            >
                                ♥ Add to Wishlist
                            </button>
                        </div>

                        <!-- Additional Info -->
                        @if($product->stock > 0 && $product->stock <= 10)
                            <div class="mt-6 p-4 bg-yellow-900/30 border border-yellow-600/50 rounded-lg">
                                <p class="text-yellow-400 text-sm font-medium">
                                    ⚠️ Only {{ $product->stock }} left in stock - order soon!
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Product Specifications (Optional Section) -->
            <div class="mt-8 bg-gray-800 rounded-lg p-8 shadow-xl">
                <h2 class="text-white font-semibold text-2xl mb-6">Product Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div class="flex justify-between border-b border-gray-700 pb-2">
                            <span class="text-gray-400">Product Name:</span>
                            <span class="text-white font-medium">{{ $product->title }}</span>
                        </div>
                        <div class="flex justify-between border-b border-gray-700 pb-2">
                            <span class="text-gray-400">Price:</span>
                            <span class="text-white font-medium">${{ number_format($product->price, 2) }}</span>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex justify-between border-b border-gray-700 pb-2">
                            <span class="text-gray-400">Availability:</span>
                            <span class="text-white font-medium">{{ $product->stock > 0 ? 'In Stock' : 'Out of Stock' }}</span>
                        </div>
                        @if($product->sku)
                            <div class="flex justify-between border-b border-gray-700 pb-2">
                                <span class="text-gray-400">SKU:</span>
                                <span class="text-white font-medium">{{ $product->sku }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>


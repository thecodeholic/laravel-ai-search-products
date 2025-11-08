<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Product Search - {{ config('app.name', 'Laravel') }}</title>

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
            <!-- Search Bar -->
            <div class="mb-8 flex justify-center">
                <form action="/" method="GET" class="w-full max-w-3xl">
                    <div class="relative">
                        <input
                            type="text"
                            name="q"
                            value="{{ $searchQuery }}"
                            placeholder="Search products..."
                            class="w-full px-6 py-4 text-lg bg-gray-800 text-white placeholder-gray-400 rounded-lg border border-gray-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        />
                        <button
                            type="submit"
                            class="absolute right-2 top-1/2 transform -translate-y-1/2 px-6 py-2 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg hover:from-purple-700 hover:to-blue-700 transition-colors"
                        >
                            Search
                        </button>
                    </div>
                </form>
            </div>

            <!-- Products Grid -->
            @if($products->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($products as $product)
                        <div class="bg-gray-800 rounded-lg overflow-hidden shadow-lg hover:shadow-xl transition-shadow">
                            <!-- Product Image -->
                            <div class="bg-white p-4 flex items-center justify-center h-64">
                                <img
                                    src="{{ $product->image ?? 'https://via.placeholder.com/400x400?text=No+Image' }}"
                                    alt="{{ $product->title }}"
                                    class="max-w-full max-h-full object-contain"
                                    onerror="this.src='https://via.placeholder.com/400x400?text=No+Image'"
                                />
                            </div>

                            <!-- Product Info -->
                            <div class="p-6">
                                <!-- Title -->
                                <h3 class="text-white font-semibold text-lg mb-2 line-clamp-2 min-h-[3.5rem]">
                                    {{ $product->title }}
                                </h3>

                                <!-- Description -->
                                <p class="text-gray-400 text-sm mb-4 line-clamp-2 min-h-[2.5rem]">
                                    {{ $product->description }}
                                </p>

                                <!-- Footer with Price and Button -->
                                <div class="flex items-center justify-between mt-4">
                                    <!-- Add to Cart Button -->
                                    <button
                                        type="button"
                                        class="px-4 py-2 bg-gradient-to-r from-purple-600 to-blue-600 text-white text-sm font-medium rounded-lg hover:from-purple-700 hover:to-blue-700 transition-colors"
                                    >
                                        Add to Cart
                                    </button>

                                    <!-- Price -->
                                    <div class="text-white text-2xl font-bold">
                                        ${{ number_format($product->price, 2) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8 flex justify-center">
                    <div class="flex gap-2 flex-wrap justify-center">
                        {{ $products->appends(request()->query())->links() }}
                    </div>
                </div>
            @else
                <div class="text-center py-16">
                    <p class="text-gray-400 text-xl">
                        @if($searchQuery)
                            No products found for "{{ $searchQuery }}"
                        @else
                            No products available
                        @endif
                    </p>
                </div>
            @endif
        </div>

        <style>
            /* Custom styles for line-clamp and pagination */
            .line-clamp-2 {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            /* Pagination styling */
            .pagination {
                display: flex;
                gap: 0.5rem;
                justify-content: center;
                flex-wrap: wrap;
            }

            .pagination a,
            .pagination span {
                padding: 0.5rem 1rem;
                background-color: #1f2937;
                color: #ffffff;
                border-radius: 0.5rem;
                text-decoration: none;
                transition: background-color 0.2s;
                display: inline-block;
            }

            .pagination a:hover {
                background-color: #374151;
            }

            .pagination .active span {
                background-color: #7c3aed;
            }

            .pagination .disabled span {
                opacity: 0.5;
                cursor: not-allowed;
            }
        </style>
    </body>
</html>

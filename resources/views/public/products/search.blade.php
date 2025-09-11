@extends('layouts.app')

@section('title', 'Search Results - Gym Machines')
@section('description', 'Search results for gym machines and fitness equipment. Find the perfect equipment for your needs.')

@section('content')
<!-- Page Header -->
<section class="bg-gray-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                Search Results
            </h1>
            @if($searchTerm)
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Results for "<span class="font-semibold">{{ $searchTerm }}</span>"
                </p>
            @else
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Browse our filtered selection of gym machines
                </p>
            @endif
        </div>
    </div>
</section>

<!-- Search and Filters -->
<section class="py-8 bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('products.search') }}" class="space-y-4">
            <!-- Search Bar -->
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <label for="search" class="sr-only">Search products</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" 
                               name="search" 
                               id="search"
                               value="{{ $searchTerm }}"
                               placeholder="Search gym machines..." 
                               class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                <button type="submit" 
                        class="bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200 flex items-center justify-center">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Search
                </button>
            </div>

            <!-- Filters -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Category Filter -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category" id="category" class="block w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Price Range -->
                <div>
                    <label for="min_price" class="block text-sm font-medium text-gray-700 mb-1">Min Price</label>
                    <input type="number" 
                           name="min_price" 
                           id="min_price"
                           value="{{ $minPrice }}"
                           min="{{ $priceRange['min'] }}"
                           max="{{ $priceRange['max'] }}"
                           placeholder="${{ $priceRange['min'] }}"
                           class="block w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="max_price" class="block text-sm font-medium text-gray-700 mb-1">Max Price</label>
                    <input type="number" 
                           name="max_price" 
                           id="max_price"
                           value="{{ $maxPrice }}"
                           min="{{ $priceRange['min'] }}"
                           max="{{ $priceRange['max'] }}"
                           placeholder="${{ $priceRange['max'] }}"
                           class="block w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Sort Options -->
                <div>
                    <label for="sort_by" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                    <select name="sort_by" id="sort_by" class="block w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="name" {{ $sortBy == 'name' ? 'selected' : '' }}>Name</option>
                        <option value="price" {{ $sortBy == 'price' ? 'selected' : '' }}>Price</option>
                        <option value="created_at" {{ $sortBy == 'created_at' ? 'selected' : '' }}>Newest</option>
                    </select>
                    <input type="hidden" name="sort_direction" value="{{ $sortDirection }}">
                </div>
            </div>

            <!-- Filter Actions -->
            <div class="flex flex-col sm:flex-row gap-2 justify-between items-center">
                <div class="flex gap-2">
                    <button type="submit" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors duration-200">
                        Apply Filters
                    </button>
                    <a href="{{ route('products.index') }}" 
                       class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-300 transition-colors duration-200">
                        Clear All
                    </a>
                </div>
                
                <!-- Sort Direction Toggle -->
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600">Sort:</span>
                    <button type="button" 
                            onclick="toggleSortDirection()"
                            class="flex items-center gap-1 text-sm text-blue-600 hover:text-blue-700">
                        <span id="sort-direction-text">{{ $sortDirection == 'desc' ? 'Descending' : 'Ascending' }}</span>
                        <svg id="sort-direction-icon" class="h-4 w-4 {{ $sortDirection == 'desc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Active Filters Display -->
@if($searchTerm || $categoryId || $minPrice || $maxPrice)
<section class="py-4 bg-blue-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-medium text-gray-700">Active filters:</span>
            
            @if($searchTerm)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                    Search: "{{ $searchTerm }}"
                    <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" class="ml-2 text-blue-600 hover:text-blue-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </a>
                </span>
            @endif

            @if($categoryId)
                @php
                    $selectedCategory = $categories->find($categoryId);
                @endphp
                @if($selectedCategory)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800">
                        Category: {{ $selectedCategory->name }}
                        <a href="{{ request()->fullUrlWithQuery(['category' => null]) }}" class="ml-2 text-green-600 hover:text-green-800">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </a>
                    </span>
                @endif
            @endif

            @if($minPrice)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-purple-100 text-purple-800">
                    Min: ${{ number_format($minPrice, 2) }}
                    <a href="{{ request()->fullUrlWithQuery(['min_price' => null]) }}" class="ml-2 text-purple-600 hover:text-purple-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </a>
                </span>
            @endif

            @if($maxPrice)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-purple-100 text-purple-800">
                    Max: ${{ number_format($maxPrice, 2) }}
                    <a href="{{ request()->fullUrlWithQuery(['max_price' => null]) }}" class="ml-2 text-purple-600 hover:text-purple-800">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </a>
                </span>
            @endif
        </div>
    </div>
</section>
@endif

<!-- Products Grid -->
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($products->count() > 0)
            <!-- Products Count -->
            <div class="mb-8">
                <p class="text-gray-600">
                    Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} products
                </p>
            </div>

            <!-- Products Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2 mb-12">
                @foreach($products as $product)
                    <x-product-card :product="$product" button-text="View Details" />
                @endforeach
            </div>


                    <!-- Product Image -->
                    <div class="relative w-full h-36 bg-gray-100 overflow-hidden rounded-t-lg flex-shrink-0">
                        @if($product->image_path)
                            <x-product-image 
                                :image-path="$product->image_path"
                                :alt="$product->name"
                                class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-300"
                                :width="400"
                                :height="300"
                                :lazy="true"
                            />
                        @else
                            <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>

                    <!-- Product Info -->
                    <div class="p-3">
                        @if($product->category)
                            <a href="{{ route('products.category', $product->category) }}" class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mb-2 hover:bg-blue-200 transition-colors duration-200">
                                {{ $product->category->name }}
                            </a>
                        @endif
                        
                        <h3 class="text-base font-semibold text-gray-900 mb-1 line-clamp-2">
                            <a href="{{ route('products.show', $product) }}" class="hover:text-blue-600 transition-colors duration-200">
                                {{ $product->name }}
                            </a>
                        </h3>
                        
                        <p class="text-gray-600 text-sm mb-2 line-clamp-2">
                            {{ $product->short_description }}
                        </p>
                        
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 product-card-actions">
                            <span class="text-xl font-bold text-blue-600">
                                ${{ number_format($product->price, 2) }}
                            </span>
                            <a href="{{ route('products.show', $product) }}" 
                               class="bg-blue-600 text-white px-4 py-3 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors duration-200 text-center touch-target min-h-[44px] flex items-center justify-center product-view-details-btn">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="flex justify-center">
                {{ $products->links() }}
            </div>
        @else
            <!-- No Results Message -->
            <div class="text-center py-16">
                <svg class="mx-auto h-24 w-24 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <h3 class="text-2xl font-semibold text-gray-900 mb-2">No Products Found</h3>
                <p class="text-gray-600 mb-6">
                    @if($searchTerm)
                        No products match your search for "<span class="font-semibold">{{ $searchTerm }}</span>". Try adjusting your search terms or filters.
                    @else
                        No products match your current filters. Try adjusting your criteria.
                    @endif
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('products.index') }}" 
                       class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200">
                        View All Products
                        <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </a>
                    <a href="{{ route('contact') }}" 
                       class="inline-flex items-center border-2 border-blue-600 text-blue-600 px-6 py-3 rounded-lg font-medium hover:bg-blue-600 hover:text-white transition-colors duration-200">
                        Contact Us for Help
                    </a>
                </div>
            </div>
        @endif
    </div>
</section>

<!-- Call to Action -->
<section class="bg-gray-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">
            Need Help Finding the Right Equipment?
        </h2>
        <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto">
            Our fitness equipment experts are here to help you find the perfect gym machines for your specific needs and budget.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('contact') }}" 
               class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors duration-200">
                Get Expert Advice
            </a>
            <a href="{{ route('home') }}" 
               class="border-2 border-blue-600 text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-blue-600 hover:text-white transition-colors duration-200">
                Learn More About Us
            </a>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush

@push('scripts')
<script>
function toggleSortDirection() {
    const sortDirectionInput = document.querySelector('input[name="sort_direction"]');
    const sortDirectionText = document.getElementById('sort-direction-text');
    const sortDirectionIcon = document.getElementById('sort-direction-icon');
    
    const currentDirection = sortDirectionInput.value;
    const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
    
    sortDirectionInput.value = newDirection;
    sortDirectionText.textContent = newDirection === 'desc' ? 'Descending' : 'Ascending';
    
    if (newDirection === 'desc') {
        sortDirectionIcon.classList.add('rotate-180');
    } else {
        sortDirectionIcon.classList.remove('rotate-180');
    }
    
    // Submit the form
    document.querySelector('form').submit();
}

// Auto-submit form when select values change
document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('#category, #sort_by');
    selects.forEach(select => {
        select.addEventListener('change', function() {
            document.querySelector('form').submit();
        });
    });
});
</script>
@endpush
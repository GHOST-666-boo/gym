@extends('layouts.app')

@section('title', 'All Gym Machines - Professional Fitness Equipment')
@section('description', 'Browse our complete collection of professional gym machines and fitness equipment. Find the perfect equipment for your commercial or home gym.')

@section('content')
<!-- Page Header -->
<section class="bg-gray-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                Our Gym Machines
            </h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Explore our comprehensive collection of professional-grade gym equipment designed to meet all your fitness needs.
            </p>
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
                               value="{{ request('search') }}"
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
                            <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
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
                           value="{{ request('min_price') }}"
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
                           value="{{ request('max_price') }}"
                           min="{{ $priceRange['min'] }}"
                           max="{{ $priceRange['max'] }}"
                           placeholder="${{ $priceRange['max'] }}"
                           class="block w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Sort Options -->
                <div>
                    <label for="sort_by" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                    <select name="sort_by" id="sort_by" class="block w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="name" {{ request('sort_by') == 'name' ? 'selected' : '' }}>Name</option>
                        <option value="price" {{ request('sort_by') == 'price' ? 'selected' : '' }}>Price</option>
                        <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Newest</option>
                    </select>
                    <input type="hidden" name="sort_direction" value="{{ request('sort_direction', 'asc') }}">
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
                        <span id="sort-direction-text">{{ request('sort_direction') == 'desc' ? 'Descending' : 'Ascending' }}</span>
                        <svg id="sort-direction-icon" class="h-4 w-4 {{ request('sort_direction') == 'desc' ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>

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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-12">
                @foreach($products as $product)
                <div class="card card-hover group animate-fade-in-up">
                    <!-- Product Image -->
                    <div class="aspect-w-16 aspect-h-12 bg-gray-200 overflow-hidden">
                        @php
                            $imageUrl = null;
                            if ($product->relationLoaded('images') && $product->images->count()) {
                                $imageUrl = $product->images->first()->url;
                            } elseif ($product->image_path) {
                                $imageUrl = asset('storage/' . $product->image_path);
                            }
                        @endphp
                        @if($imageUrl)
                            <img src="{{ $imageUrl }}" 
                                 alt="{{ $product->name }}" 
                                 class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"
                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDMwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iMjAwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0xMjUgODBIMTc1VjEyMEgxMjVWODBaIiBmaWxsPSIjOUNBM0FGIi8+Cjwvc3ZnPgo='">
                        @else
                            <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>

                    <!-- Product Info -->
                    <div class="p-4">
                        @if($product->category)
                            <a href="{{ route('products.category', $product->category) }}" class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mb-2 hover:bg-blue-200 transition-colors duration-200">
                                {{ $product->category->name }}
                            </a>
                        @endif
                        
                        <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                            <a href="{{ route('products.show', $product) }}" class="hover:text-blue-600 transition-colors duration-200">
                                {{ $product->name }}
                            </a>
                        </h3>
                        
                        <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                            {{ $product->short_description }}
                        </p>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-xl font-bold text-blue-600">
                                ${{ number_format($product->price, 2) }}
                            </span>
                            <div class="flex gap-2">
                                <button onclick="addToComparison({{ $product->id }})" 
                                        class="compare-btn bg-gray-200 text-gray-700 px-2 py-2 rounded-md text-sm font-medium hover:bg-gray-300 transition-colors duration-200"
                                        title="Add to comparison">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </button>
                                <a href="{{ route('products.show', $product) }}" 
                                   class="bg-blue-600 text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors duration-200">
                                    View Details
                                </a>
                            </div>
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
            <!-- No Products Message -->
            <div class="text-center py-16">
                <svg class="mx-auto h-24 w-24 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <h3 class="text-2xl font-semibold text-gray-900 mb-2">No Products Available</h3>
                <p class="text-gray-600 mb-6">
                    We're currently updating our product catalog. Please check back soon for our latest gym machines.
                </p>
                <a href="{{ route('contact') }}" 
                   class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200">
                    Contact Us for Information
                    <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
            </div>
        @endif
    </div>
</section>

<!-- Call to Action -->
<section class="bg-gray-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">
            Need Help Choosing the Right Equipment?
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
    
    // Initialize comparison functionality
    updateComparisonButtons();
});

// Comparison functionality
function addToComparison(productId) {
    fetch('{{ route("products.compare.add") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateComparisonButtons();
            updateComparisonWidget(data.count);
            showNotification(data.message, 'success');
        } else {
            showNotification(data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to add product to comparison', 'error');
    });
}

function updateComparisonButtons() {
    // Get current comparison products
    fetch('{{ route("products.compare.products") }}')
    .then(response => response.json())
    .then(data => {
        const comparisonIds = data.products.map(p => p.id);
        
        // Update all compare buttons
        document.querySelectorAll('.compare-btn').forEach(btn => {
            const productId = parseInt(btn.getAttribute('onclick').match(/\d+/)[0]);
            
            if (comparisonIds.includes(productId)) {
                btn.classList.remove('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');
                btn.classList.add('bg-blue-600', 'text-white', 'hover:bg-blue-700');
                btn.title = 'Added to comparison';
                btn.disabled = true;
            } else {
                btn.classList.remove('bg-blue-600', 'text-white', 'hover:bg-blue-700');
                btn.classList.add('bg-gray-200', 'text-gray-700', 'hover:bg-gray-300');
                btn.title = 'Add to comparison';
                btn.disabled = false;
            }
        });
    });
}

function updateComparisonWidget(count) {
    // Update comparison widget if it exists
    const widget = document.getElementById('comparison-widget');
    if (widget) {
        const countElement = widget.querySelector('.comparison-count');
        if (countElement) {
            countElement.textContent = count;
        }
        
        if (count > 0) {
            widget.classList.remove('hidden');
        } else {
            widget.classList.add('hidden');
        }
    }
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
</script>
@endpush
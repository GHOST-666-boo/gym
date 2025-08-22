@extends('layouts.app')

@section('title', 'Compare Products - Gym Machines')
@section('description', 'Compare gym machines side by side to find the perfect equipment for your needs.')

@section('content')
<!-- Page Header -->
<section class="bg-gray-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                Compare Products
            </h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                Compare up to 4 gym machines side by side to make the best choice for your fitness needs.
            </p>
        </div>
    </div>
</section>

<!-- Comparison Table -->
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($products->count() > 0)
            <!-- Comparison Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-center mb-8">
                <div class="mb-4 sm:mb-0">
                    <p class="text-gray-600">
                        Comparing {{ $products->count() }} of 4 products
                    </p>
                </div>
                <div class="flex gap-4">
                    <a href="{{ route('products.index') }}" 
                       class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors duration-200">
                        Add More Products
                    </a>
                    <button onclick="clearComparison()" 
                            class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-red-700 transition-colors duration-200">
                        Clear All
                    </button>
                </div>
            </div>

            <!-- Desktop Comparison Table -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="w-full bg-white rounded-lg shadow-lg overflow-hidden">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-900 w-48">
                                Features
                            </th>
                            @foreach($products as $product)
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-900 min-w-64">
                                <div class="space-y-4">
                                    <!-- Product Image -->
                                    <div class="aspect-w-16 aspect-h-12 bg-gray-200 rounded-lg overflow-hidden">
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
                                                 class="w-full h-32 object-cover">
                                        @else
                                            <div class="w-full h-32 bg-gray-200 flex items-center justify-center">
                                                <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Product Name -->
                                    <h3 class="font-semibold text-gray-900 text-sm">
                                        <a href="{{ route('products.show', $product) }}" class="hover:text-blue-600">
                                            {{ $product->name }}
                                        </a>
                                    </h3>
                                    
                                    <!-- Remove Button -->
                                    <button onclick="removeFromComparison({{ $product->id }})" 
                                            class="text-red-600 hover:text-red-800 text-xs">
                                        Remove
                                    </button>
                                </div>
                            </th>
                            @endforeach
                            
                            <!-- Empty slots for remaining products -->
                            @for($i = $products->count(); $i < 4; $i++)
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-400 min-w-64">
                                <div class="space-y-4">
                                    <div class="w-full h-32 bg-gray-100 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-300">
                                        <div class="text-center">
                                            <svg class="h-8 w-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            <p class="text-xs text-gray-500">Add Product</p>
                                        </div>
                                    </div>
                                </div>
                            </th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <!-- Price Row -->
                        <tr class="bg-blue-50">
                            <td class="px-6 py-4 font-semibold text-gray-900">Price</td>
                            @foreach($products as $product)
                            <td class="px-6 py-4 text-center">
                                <span class="text-2xl font-bold text-blue-600">
                                    ${{ number_format($product->price, 2) }}
                                </span>
                            </td>
                            @endforeach
                            @for($i = $products->count(); $i < 4; $i++)
                            <td class="px-6 py-4 text-center text-gray-400">-</td>
                            @endfor
                        </tr>
                        
                        <!-- Category Row -->
                        <tr>
                            <td class="px-6 py-4 font-semibold text-gray-900">Category</td>
                            @foreach($products as $product)
                            <td class="px-6 py-4 text-center">
                                @if($product->category)
                                    <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                        {{ $product->category->name }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            @endforeach
                            @for($i = $products->count(); $i < 4; $i++)
                            <td class="px-6 py-4 text-center text-gray-400">-</td>
                            @endfor
                        </tr>
                        
                        <!-- Description Row -->
                        <tr class="bg-gray-50">
                            <td class="px-6 py-4 font-semibold text-gray-900">Description</td>
                            @foreach($products as $product)
                            <td class="px-6 py-4 text-center text-sm text-gray-600">
                                {{ Str::limit($product->short_description, 100) }}
                            </td>
                            @endforeach
                            @for($i = $products->count(); $i < 4; $i++)
                            <td class="px-6 py-4 text-center text-gray-400">-</td>
                            @endfor
                        </tr>
                        
                        <!-- Features Row -->
                        <tr>
                            <td class="px-6 py-4 font-semibold text-gray-900">Key Features</td>
                            @foreach($products as $product)
                            <td class="px-6 py-4 text-center">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-center">
                                        <svg class="h-4 w-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span class="text-xs text-gray-700">Commercial Grade</span>
                                    </div>
                                    <div class="flex items-center justify-center">
                                        <svg class="h-4 w-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span class="text-xs text-gray-700">Safety Certified</span>
                                    </div>
                                    <div class="flex items-center justify-center">
                                        <svg class="h-4 w-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span class="text-xs text-gray-700">Durable Build</span>
                                    </div>
                                </div>
                            </td>
                            @endforeach
                            @for($i = $products->count(); $i < 4; $i++)
                            <td class="px-6 py-4 text-center text-gray-400">-</td>
                            @endfor
                        </tr>
                        
                        <!-- Actions Row -->
                        <tr class="bg-blue-50">
                            <td class="px-6 py-4 font-semibold text-gray-900">Actions</td>
                            @foreach($products as $product)
                            <td class="px-6 py-4 text-center">
                                <div class="space-y-2">
                                    <a href="{{ route('products.show', $product) }}" 
                                       class="block bg-blue-600 text-white px-3 py-2 rounded-md text-xs font-medium hover:bg-blue-700 transition-colors duration-200">
                                        View Details
                                    </a>
                                    <a href="{{ route('contact') }}" 
                                       class="block border border-blue-600 text-blue-600 px-3 py-2 rounded-md text-xs font-medium hover:bg-blue-600 hover:text-white transition-colors duration-200">
                                        Get Quote
                                    </a>
                                </div>
                            </td>
                            @endforeach
                            @for($i = $products->count(); $i < 4; $i++)
                            <td class="px-6 py-4 text-center text-gray-400">-</td>
                            @endfor
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Comparison Cards -->
            <div class="lg:hidden space-y-6">
                @foreach($products as $product)
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="p-6">
                        <!-- Product Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
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
                                         class="w-20 h-20 object-cover rounded-lg mb-3">
                                @endif
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                    <a href="{{ route('products.show', $product) }}" class="hover:text-blue-600">
                                        {{ $product->name }}
                                    </a>
                                </h3>
                                <span class="text-2xl font-bold text-blue-600">
                                    ${{ number_format($product->price, 2) }}
                                </span>
                            </div>
                            <button onclick="removeFromComparison({{ $product->id }})" 
                                    class="text-red-600 hover:text-red-800 p-2">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Product Details -->
                        <div class="space-y-4">
                            @if($product->category)
                            <div>
                                <span class="text-sm font-medium text-gray-700">Category:</span>
                                <span class="ml-2 inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                    {{ $product->category->name }}
                                </span>
                            </div>
                            @endif
                            
                            <div>
                                <span class="text-sm font-medium text-gray-700">Description:</span>
                                <p class="mt-1 text-sm text-gray-600">{{ $product->short_description }}</p>
                            </div>
                            
                            <div>
                                <span class="text-sm font-medium text-gray-700">Key Features:</span>
                                <div class="mt-2 space-y-1">
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span class="text-sm text-gray-700">Commercial Grade</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span class="text-sm text-gray-700">Safety Certified</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="h-4 w-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        <span class="text-sm text-gray-700">Durable Construction</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex space-x-3 pt-4">
                                <a href="{{ route('products.show', $product) }}" 
                                   class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium text-center hover:bg-blue-700 transition-colors duration-200">
                                    View Details
                                </a>
                                <a href="{{ route('contact') }}" 
                                   class="flex-1 border border-blue-600 text-blue-600 px-4 py-2 rounded-md text-sm font-medium text-center hover:bg-blue-600 hover:text-white transition-colors duration-200">
                                    Get Quote
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div class="text-center py-16">
                <svg class="mx-auto h-24 w-24 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <h3 class="text-2xl font-semibold text-gray-900 mb-2">No Products to Compare</h3>
                <p class="text-gray-600 mb-6">
                    Start by adding products to your comparison list. You can compare up to 4 products at once.
                </p>
                <a href="{{ route('products.index') }}" 
                   class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200">
                    Browse Products
                    <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </a>
            </div>
        @endif
    </div>
</section>

<!-- Call to Action -->
@if($products->count() > 0)
<section class="bg-gray-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-gray-900 mb-4">
            Ready to Make Your Decision?
        </h2>
        <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto">
            Our fitness equipment experts are here to help you choose the perfect gym machines for your specific needs.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('contact') }}" 
               class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors duration-200">
                Get Expert Consultation
            </a>
            <a href="{{ route('products.index') }}" 
               class="border-2 border-blue-600 text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-blue-600 hover:text-white transition-colors duration-200">
                Browse More Products
            </a>
        </div>
    </div>
</section>
@endif
@endsection

@push('scripts')
<script>
// Comparison functionality
function removeFromComparison(productId) {
    fetch('{{ route("products.compare.remove") }}', {
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
            // Reload the page to update the comparison
            window.location.reload();
        } else {
            alert(data.error || 'Failed to remove product from comparison');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to remove product from comparison');
    });
}

function clearComparison() {
    if (confirm('Are you sure you want to clear all products from comparison?')) {
        fetch('{{ route("products.compare.clear") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to products page
                window.location.href = '{{ route("products.index") }}';
            } else {
                alert(data.error || 'Failed to clear comparison');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to clear comparison');
        });
    }
}
</script>
@endpush
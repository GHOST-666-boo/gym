@extends('layouts.app')

@section('title', $category->name . ' - Gym Machines')
@section('description', $category->description ?: 'Browse our ' . $category->name . ' collection of professional gym machines and fitness equipment.')

@section('content')
<!-- Breadcrumb -->
@php
    $breadcrumbItems = [
        ['title' => 'Home', 'url' => route('home')],
        ['title' => 'Products', 'url' => route('products.index')],
        ['title' => $category->name]
    ];
@endphp

<x-breadcrumb :items="$breadcrumbItems" />

<!-- Page Header -->
<section class="bg-gray-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                {{ $category->name }}
            </h1>
            @if($category->description)
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    {{ $category->description }}
                </p>
            @endif
        </div>
    </div>
</section>

<!-- Products Grid -->
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($products->count() > 0)
            <!-- Products Count -->
            <div class="mb-8">
                <p class="text-gray-600">
                    Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} products in {{ $category->name }}
                </p>
            </div>

            <!-- Products Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-12">
                @foreach($products as $product)
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300 group">
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif
                    </div>

                    <!-- Product Info -->
                    <div class="p-4">
                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mb-2">
                            {{ $category->name }}
                        </span>
                        
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
                            <a href="{{ route('products.show', $product) }}" 
                               class="bg-blue-600 text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors duration-200">
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
            <!-- No Products Message -->
            <div class="text-center py-16">
                <svg class="mx-auto h-24 w-24 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <h3 class="text-2xl font-semibold text-gray-900 mb-2">No Products in {{ $category->name }}</h3>
                <p class="text-gray-600 mb-6">
                    We're currently updating our {{ $category->name }} catalog. Please check back soon or browse other categories.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('products.index') }}" 
                       class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200">
                        Browse All Products
                        <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </a>
                    <a href="{{ route('contact') }}" 
                       class="border-2 border-blue-600 text-blue-600 px-6 py-3 rounded-lg font-medium hover:bg-blue-600 hover:text-white transition-colors duration-200">
                        Contact Us
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
            Need Help with {{ $category->name }}?
        </h2>
        <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto">
            Our fitness equipment experts can help you choose the perfect {{ strtolower($category->name) }} for your specific needs and budget.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('contact') }}" 
               class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors duration-200">
                Get Expert Advice
            </a>
            <a href="{{ route('products.index') }}" 
               class="border-2 border-blue-600 text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-blue-600 hover:text-white transition-colors duration-200">
                Browse All Products
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
</content>
</invoke>
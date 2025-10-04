@props(['product', 'showButton' => true, 'buttonText' => 'View'])

<div
    class="product-card-component bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-300 group h-full flex flex-col">
    <!-- Product Image -->
    <div class="relative aspect-square bg-gray-100 overflow-hidden flex items-center justify-center">
        @php
            $imagePath = null;
            if ($product->relationLoaded('images') && $product->images->count()) {
                $imagePath = str_replace(asset('storage/'), '', $product->images->first()->url);
            } elseif ($product->image_path) {
                $imagePath = $product->image_path;
            }
        @endphp

        @if($imagePath)
            <x-product-image :image-path="$imagePath" :alt="$product->name"
                class="max-w-full max-h-full object-contain group-hover:scale-105 transition-transform duration-300"
                :width="400" :height="400" :lazy="true" />
        @else
            <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                <svg class="h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>
            </div>
        @endif
    </div>

    <!-- Product Info -->
    <div class="p-4 flex flex-col flex-grow">
        <!-- Brand/Category - Highlighted -->
        <div class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-1 rounded-full mb-2 w-fit">
            {{ $product->category->name ?? 'PREMIUM' }}
        </div>

        <!-- Product Name -->
        <h3 class="text-sm font-medium text-gray-900 mb-3 line-clamp-2 flex-grow">
            <a href="{{ route('products.show', $product) }}" class="hover:text-blue-600 transition-colors duration-200">
                {{ $product->name }}
            </a>
        </h3>

        <!-- Price and View Button - Fixed at bottom -->
        <div class="flex justify-between mt-auto flex-col">
            <div class="text-lg font-bold text-gray-900 mb-2">
                ${{ number_format($product->price, 2) }}
            </div>
            @if($showButton)
                <div class="flex flex-col gap-2">
                    <!-- Add to Cart Button -->
                    <button onclick="addToQuoteCart({{ $product->id }})"
                        class="product-card-btn bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center justify-center gap-2 flex-shrink-0">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 6M7 13l-1.5-6m0 0h15M17 21a2 2 0 100-4 2 2 0 000 4zM9 21a2 2 0 100-4 2 2 0 000 4z"></path>
                        </svg>
                        <span>Add to Cart</span>
                    </button>
                    
                    <!-- View Details Button -->
                    <button onclick="window.location.href='{{ route('products.show', $product) }}'"
                        class="product-card-btn bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center justify-center gap-2 flex-shrink-0">
                        <span>{{ $buttonText }}</span>
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    /* Product Card Component Specific Styles */
    .product-card-component {
        display: flex !important;
        flex-direction: column !important;
        height: 100% !important;
    }

    .product-card-component .p-4 {
        display: flex !important;
        flex-direction: column !important;
        flex-grow: 1 !important;
    }

    .product-card-component .line-clamp-2 {
        display: -webkit-box !important;
        -webkit-line-clamp: 2 !important;
        -webkit-box-orient: vertical !important;
        overflow: hidden !important;
    }

    .product-card-component .aspect-square {
        aspect-ratio: 1 / 1 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .product-card-component .aspect-square img {
        max-width: 100% !important;
        max-height: 100% !important;
        width: auto !important;
        height: auto !important;
        object-fit: contain !important;
        display: block !important;
        margin: auto !important;
    }

    /* Ensure product image component centers properly */
    .product-card-component .aspect-square .product-image {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 100% !important;
        height: 100% !important;
    }

    .product-card-component .aspect-square .product-image img {
        max-width: 100% !important;
        max-height: 100% !important;
        width: auto !important;
        height: auto !important;
        object-fit: contain !important;
    }

    .product-card-component .flex.items-center.justify-between {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        margin-top: auto !important;
    }

    .product-card-component .product-card-btn {
        display: flex !important;
        align-items: center !important;
        gap: 0.25rem !important;
        padding: 0.375rem 0.75rem !important;
        font-size: 0.875rem !important;
        line-height: 1.25rem !important;
        border-radius: 0.5rem !important;
        flex-shrink: 0 !important;
        white-space: nowrap !important;
    }

    .product-card-component .text-lg.font-bold {
        font-size: 1.125rem !important;
        line-height: 1.75rem !important;
        font-weight: 700 !important;
    }
</style>
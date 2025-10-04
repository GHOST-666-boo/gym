@props(['product'])

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-6">
    <h2 class="text-2xl font-bold text-gray-900 border-b border-gray-200 pb-3">
        Product Details
    </h2>
    
    <!-- Long Description -->
    @if($product->long_description)
        <div class="prose max-w-none">
            {!! nl2br(e($product->long_description)) !!}
        </div>
    @endif
    
    <!-- Product Specifications in Grid -->
    @if($product->dimensions || $product->material || $product->care_instructions)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-gray-200">
            @if($product->dimensions)
                <div class="text-center">
                    <div class="flex justify-center mb-2">
                        <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                        </svg>
                    </div>
                    <dt class="text-sm font-semibold text-gray-900 mb-1">Dimensions (L×W×H)</dt>
                    <dd class="text-sm text-gray-600">{{ $product->dimensions }}</dd>
                </div>
            @endif

            @if($product->material)
                <div class="text-center">
                    <div class="flex justify-center mb-2">
                        <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                        </svg>
                    </div>
                    <dt class="text-sm font-semibold text-gray-900 mb-1">Material</dt>
                    <dd class="text-sm text-gray-600">{{ $product->material }}</dd>
                </div>
            @endif

            @if($product->care_instructions)
                <div class="text-center">
                    <div class="flex justify-center mb-2">
                        <svg class="h-8 w-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <dt class="text-sm font-semibold text-gray-900 mb-1">Care Instructions</dt>
                    <dd class="text-sm text-gray-600 whitespace-pre-line">{{ $product->care_instructions }}</dd>
                </div>
            @endif
        </div>
    @endif
    
    @if(!$product->long_description && !$product->dimensions && !$product->material && !$product->care_instructions)
        <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-500">No product details available</p>
        </div>
    @endif
</div>
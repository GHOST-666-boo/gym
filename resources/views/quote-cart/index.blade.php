@extends('layouts.app')

@section('title', 'Quote Cart - ' . site_name())
@section('description', 'Review your selected products and request a bulk quote')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Quote Cart</h1>
            <p class="text-gray-600">Review your selected products and request a bulk quote</p>
        </div>

        @if($cartItems->count() > 0)
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Selected Products ({{ $cartItems->count() }})</h2>
                        </div>
                        
                        <div class="divide-y divide-gray-200">
                            @foreach($cartItems as $item)
                                <div class="p-6" id="cart-item-{{ $item->product_id }}">
                                    <div class="flex items-start space-x-4">
                                        <!-- Product Image -->
                                        <div class="flex-shrink-0">
                                            @if($item->product->image_path || $item->product->images->count() > 0)
                                                @php
                                                    $imageUrl = $item->product->images->count() > 0 
                                                        ? $item->product->images->first()->url 
                                                        : asset('storage/' . $item->product->image_path);
                                                @endphp
                                                <img src="{{ $imageUrl }}" alt="{{ $item->product->name }}" class="h-20 w-20 object-cover rounded-lg">
                                            @else
                                                <div class="h-20 w-20 bg-gray-200 rounded-lg flex items-center justify-center">
                                                    <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <!-- Product Details -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <h3 class="text-lg font-medium text-gray-900">
                                                        <a href="{{ route('products.show', $item->product) }}" class="hover:text-blue-600">
                                                            {{ $item->product->name }}
                                                        </a>
                                                    </h3>
                                                    @if($item->product->category)
                                                        <p class="text-sm text-gray-500 mt-1">{{ $item->product->category->name }}</p>
                                                    @endif
                                                    <p class="text-sm text-gray-600 mt-2 line-clamp-2">{{ $item->product->short_description }}</p>
                                                </div>
                                                
                                                <!-- Remove Button -->
                                                <button onclick="removeFromCart({{ $item->product_id }})" 
                                                        class="ml-4 text-red-600 hover:text-red-800 p-1" 
                                                        title="Remove from cart">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                            
                                            <!-- Quantity and Price -->
                                            <div class="flex items-center justify-between mt-4">
                                                <div class="flex items-center space-x-3">
                                                    <label class="text-sm font-medium text-gray-700">Quantity:</label>
                                                    <div class="flex items-center border border-gray-300 rounded-md">
                                                        <button onclick="updateQuantity({{ $item->product_id }}, {{ $item->quantity - 1 }})" 
                                                                class="px-3 py-1 text-gray-600 hover:text-gray-800 {{ $item->quantity <= 1 ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                                {{ $item->quantity <= 1 ? 'disabled' : '' }}>
                                                            -
                                                        </button>
                                                        <span class="px-4 py-1 text-sm font-medium" id="quantity-{{ $item->product_id }}">{{ $item->quantity }}</span>
                                                        <button onclick="updateQuantity({{ $item->product_id }}, {{ $item->quantity + 1 }})" 
                                                                class="px-3 py-1 text-gray-600 hover:text-gray-800">
                                                            +
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                <div class="text-right">
                                                    <p class="text-sm text-gray-500">Unit Price: ${{ number_format($item->price, 2) }}</p>
                                                    <p class="text-lg font-semibold text-gray-900" id="total-{{ $item->product_id }}">
                                                        ${{ number_format($item->total_price, 2) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <!-- Cart Actions -->
                        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <button onclick="clearCart()" 
                                        class="text-red-600 hover:text-red-800 text-sm font-medium">
                                    Clear All Items
                                </button>
                                <a href="{{ route('products.index') }}" 
                                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    Continue Shopping
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quote Summary & Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 sticky top-4">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Quote Summary</h2>
                        </div>
                        
                        <div class="p-6">
                            <!-- Summary Stats -->
                            <div class="space-y-3 mb-6">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Total Products:</span>
                                    <span class="font-medium" id="total-products">{{ $cartItems->count() }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Total Quantity:</span>
                                    <span class="font-medium" id="total-quantity">{{ $cartItems->sum('quantity') }}</span>
                                </div>
                                <div class="flex justify-between text-lg font-semibold border-t pt-3">
                                    <span>Estimated Total:</span>
                                    <span class="text-blue-600" id="cart-total">${{ number_format($cartTotal, 2) }}</span>
                                </div>
                            </div>
                            
                            <!-- Quote Request Form -->
                            <form id="bulkQuoteForm" class="space-y-4">
                                @csrf
                                
                                <div>
                                    <label for="bulk_name" class="block text-sm font-medium text-gray-700 mb-1">
                                        Full Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           id="bulk_name" 
                                           name="name" 
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="bulk_email" class="block text-sm font-medium text-gray-700 mb-1">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email" 
                                           id="bulk_email" 
                                           name="email" 
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="bulk_phone" class="block text-sm font-medium text-gray-700 mb-1">
                                        Phone Number
                                    </label>
                                    <input type="tel" 
                                           id="bulk_phone" 
                                           name="phone" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="bulk_company" class="block text-sm font-medium text-gray-700 mb-1">
                                        Company/Organization
                                    </label>
                                    <input type="text" 
                                           id="bulk_company" 
                                           name="company" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="bulk_message" class="block text-sm font-medium text-gray-700 mb-1">
                                        Additional Requirements
                                    </label>
                                    <textarea id="bulk_message" 
                                              name="message" 
                                              rows="3"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                              placeholder="Tell us about your specific requirements..."></textarea>
                                </div>

                                <button type="submit" 
                                        id="bulkQuoteSubmitBtn"
                                        class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span class="submit-text">Request Bulk Quote</span>
                                    <span class="loading-text hidden">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Sending Request...
                                    </span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- Empty Cart -->
            <div class="text-center py-16">
                <svg class="mx-auto h-24 w-24 text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 6M7 13l-1.5-6m0 0h15M17 21a2 2 0 100-4 2 2 0 000 4zM9 21a2 2 0 100-4 2 2 0 000 4z"></path>
                </svg>
                <h2 class="text-2xl font-semibold text-gray-900 mb-4">Your Quote Cart is Empty</h2>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                    Browse our products and add items to your quote cart to request bulk pricing.
                </p>
                <a href="{{ route('products.index') }}" 
                   class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors duration-200">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    Browse Products
                </a>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// Remove item from cart
async function removeFromCart(productId) {
    if (!confirm('Are you sure you want to remove this item from your cart?')) {
        return;
    }

    try {
        const response = await fetch('{{ route("quote-cart.remove") }}', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ product_id: productId })
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById(`cart-item-${productId}`).remove();
            updateCartCount(data.cart_count);
            
            // Reload page if cart is empty
            if (data.cart_count === 0) {
                location.reload();
            }
            
            showNotification('Product removed from cart', 'success');
        } else {
            showNotification(data.message || 'Failed to remove product', 'error');
        }
    } catch (error) {
        console.error('Error removing from cart:', error);
        showNotification('An error occurred. Please try again.', 'error');
    }
}

// Update quantity
async function updateQuantity(productId, newQuantity) {
    if (newQuantity < 1) return;

    try {
        const response = await fetch('{{ route("quote-cart.update-quantity") }}', {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ 
                product_id: productId, 
                quantity: newQuantity 
            })
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById(`quantity-${productId}`).textContent = newQuantity;
            updateCartCount(data.cart_count);
            
            // Update totals (you'd need to calculate this based on unit price)
            location.reload(); // Simple approach - reload to update all totals
        } else {
            showNotification(data.message || 'Failed to update quantity', 'error');
        }
    } catch (error) {
        console.error('Error updating quantity:', error);
        showNotification('An error occurred. Please try again.', 'error');
    }
}

// Clear entire cart
async function clearCart() {
    if (!confirm('Are you sure you want to clear your entire cart?')) {
        return;
    }

    try {
        const response = await fetch('{{ route("quote-cart.clear") }}', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            location.reload();
        } else {
            showNotification(data.message || 'Failed to clear cart', 'error');
        }
    } catch (error) {
        console.error('Error clearing cart:', error);
        showNotification('An error occurred. Please try again.', 'error');
    }
}

// Handle bulk quote form submission
document.getElementById('bulkQuoteForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('bulkQuoteSubmitBtn');
    const submitText = submitBtn.querySelector('.submit-text');
    const loadingText = submitBtn.querySelector('.loading-text');
    
    // Show loading state
    submitBtn.disabled = true;
    submitText.classList.add('hidden');
    loadingText.classList.remove('hidden');
    
    try {
        const formData = new FormData(this);
        
        const response = await fetch('{{ route("quote-cart.submit-quote") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Quote request submitted successfully! We\'ll get back to you soon.', 'success');
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            throw new Error(data.message || 'Failed to submit quote request');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification(error.message || 'There was an error submitting your quote request. Please try again.', 'error');
        
        // Reset button state
        submitBtn.disabled = false;
        submitText.classList.remove('hidden');
        loadingText.classList.add('hidden');
    }
});
</script>
@endpush
@endsection
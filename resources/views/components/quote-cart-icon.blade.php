@php
    $cartCount = app(\App\Services\QuoteCartService::class)->getCartCount(auth()->id());
@endphp

<div class="relative">
    <a href="{{ route('quote-cart.index') }}" 
       class="flex items-center text-gray-700 hover:text-blue-600 transition-colors duration-200"
       title="Quote Cart">
        <div class="relative">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 6M7 13l-1.5-6m0 0h15M17 21a2 2 0 100-4 2 2 0 000 4zM9 21a2 2 0 100-4 2 2 0 000 4z"></path>
            </svg>
            
            @if($cartCount > 0)
                <span id="cart-count-badge" 
                      class="absolute -top-2 -right-2 bg-blue-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium">
                    {{ $cartCount > 99 ? '99+' : $cartCount }}
                </span>
            @else
                <span id="cart-count-badge" class="hidden absolute -top-2 -right-2 bg-blue-600 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium"></span>
            @endif
        </div>
        
        <span class="ml-2 hidden sm:inline font-medium">Quote Cart</span>
    </a>
</div>

@push('scripts')
<script>
// Update cart count badge
function updateCartCount(count) {
    const badge = document.getElementById('cart-count-badge');
    if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count;
        badge.classList.remove('hidden');
    } else {
        badge.classList.add('hidden');
    }
}

// Add to cart function
async function addToQuoteCart(productId, quantity = 1) {
    try {
        const response = await fetch('{{ route("quote-cart.add") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: quantity
            })
        });

        const data = await response.json();

        if (data.success) {
            updateCartCount(data.cart_count);
            
            // Show success message
            showNotification('Product added to quote cart!', 'success');
        } else {
            showNotification(data.message || 'Failed to add product to cart', 'error');
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        showNotification('An error occurred. Please try again.', 'error');
    }
}

// Show notification function
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Check if product is in cart function
async function checkIfInCart(productId) {
    try {
        const response = await fetch(`{{ route("quote-cart.count") }}?product_id=${productId}`);
        const data = await response.json();
        return data.in_cart || false;
    } catch (error) {
        console.error('Error checking cart status:', error);
        return false;
    }
}
</script>
@endpush
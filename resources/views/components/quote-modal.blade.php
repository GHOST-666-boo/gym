@props(['product'])

<!-- Quote Modal -->
<div id="quoteModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4 py-6">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-screen overflow-y-auto">
            <!-- Modal Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Get Quote</h3>
                    <p class="text-sm text-gray-600">{{ $product->name }}</p>
                </div>
                <button onclick="closeQuoteModal()" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Left Side - Product Summary -->
                    <div class="space-y-6">
                        <!-- Product Summary -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Product Details</h4>
                            <div class="space-y-4">
                                @if($product->image_path || $product->images->count() > 0)
                                    <div class="flex justify-center">
                                        @php
                                            $imageUrl = $product->images->count() > 0 
                                                ? $product->images->first()->url 
                                                : asset('storage/' . $product->image_path);
                                        @endphp
                                        <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="h-32 w-32 object-cover rounded-lg shadow-md">
                                    </div>
                                @endif
                                <div class="text-center">
                                    <h5 class="text-lg font-medium text-gray-900">{{ $product->name }}</h5>
                                    @if($product->category)
                                        <p class="text-sm text-gray-500 mb-2">{{ $product->category->name }}</p>
                                    @endif
                                    <p class="text-2xl font-bold text-blue-600">${{ number_format($product->price, 2) }}</p>
                                </div>
                                @if($product->short_description)
                                    <div class="border-t pt-4">
                                        <p class="text-sm text-gray-600">{{ $product->short_description }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Why Choose Us -->
                        <div class="bg-blue-50 rounded-lg p-4">
                            <h5 class="text-sm font-semibold text-blue-900 mb-3">Why Choose Us?</h5>
                            <div class="space-y-2">
                                <div class="flex items-center text-sm text-blue-800">
                                    <svg class="h-4 w-4 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    Professional Grade Equipment
                                </div>
                                <div class="flex items-center text-sm text-blue-800">
                                    <svg class="h-4 w-4 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    Expert Installation Support
                                </div>
                                <div class="flex items-center text-sm text-blue-800">
                                    <svg class="h-4 w-4 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    Competitive Pricing
                                </div>
                                <div class="flex items-center text-sm text-blue-800">
                                    <svg class="h-4 w-4 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    Fast & Reliable Delivery
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side - Quote Form -->
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Request Quote</h4>
                        <form id="quoteForm" class="space-y-4">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="product_name" value="{{ $product->name }}">
                    
                            <!-- Name & Email Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="quote_name" class="block text-sm font-medium text-gray-700 mb-1">
                                        Full Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           id="quote_name" 
                                           name="name" 
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Enter your full name">
                                </div>
                                <div>
                                    <label for="quote_email" class="block text-sm font-medium text-gray-700 mb-1">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email" 
                                           id="quote_email" 
                                           name="email" 
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Enter your email address">
                                </div>
                            </div>

                            <!-- Phone & Company Row -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="quote_phone" class="block text-sm font-medium text-gray-700 mb-1">
                                        Phone Number
                                    </label>
                                    <input type="tel" 
                                           id="quote_phone" 
                                           name="phone" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Enter your phone number">
                                </div>
                                <div>
                                    <label for="quote_company" class="block text-sm font-medium text-gray-700 mb-1">
                                        Company/Organization
                                    </label>
                                    <input type="text" 
                                           id="quote_company" 
                                           name="company" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           placeholder="Enter your company name">
                                </div>
                            </div>

                            <!-- Quantity -->
                            <div>
                                <label for="quote_quantity" class="block text-sm font-medium text-gray-700 mb-1">
                                    Quantity
                                </label>
                                <select id="quote_quantity" 
                                        name="quantity" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="1">1 unit</option>
                                    <option value="2-5">2-5 units</option>
                                    <option value="6-10">6-10 units</option>
                                    <option value="11-20">11-20 units</option>
                                    <option value="20+">20+ units</option>
                                    <option value="custom">Custom quantity</option>
                                </select>
                            </div>

                    <!-- Message -->
                    <div>
                        <label for="quote_message" class="block text-sm font-medium text-gray-700 mb-1">
                            Additional Requirements
                        </label>
                        <textarea id="quote_message" 
                                  name="message" 
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Tell us about your specific requirements, installation needs, or any questions..."></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4">
                        <button type="submit" 
                                id="quoteSubmitBtn"
                                class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span class="submit-text">Send Quote Request</span>
                            <span class="loading-text hidden">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Sending Request...
                            </span>
                        </button>
                    </div>
                        </form>
                    </div>
                </div>

                <!-- Success Message -->
                <div id="quoteSuccess" class="hidden">
                    <div class="text-center py-6">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Quote Request Sent!</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Thank you for your interest. We'll get back to you within 24 hours with a detailed quote.
                        </p>
                        <button onclick="closeQuoteModal()" 
                                class="bg-green-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-green-700 transition-colors duration-200">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Quote Modal Functions
function openQuoteModal() {
    document.getElementById('quoteModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeQuoteModal() {
    document.getElementById('quoteModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    
    // Reset form
    document.getElementById('quoteForm').reset();
    document.getElementById('quoteForm').classList.remove('hidden');
    document.getElementById('quoteSuccess').classList.add('hidden');
    
    // Reset button state
    const submitBtn = document.getElementById('quoteSubmitBtn');
    submitBtn.disabled = false;
    submitBtn.querySelector('.submit-text').classList.remove('hidden');
    submitBtn.querySelector('.loading-text').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('quoteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeQuoteModal();
    }
});

// Handle form submission
document.getElementById('quoteForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('quoteSubmitBtn');
    const submitText = submitBtn.querySelector('.submit-text');
    const loadingText = submitBtn.querySelector('.loading-text');
    
    // Show loading state
    submitBtn.disabled = true;
    submitText.classList.add('hidden');
    loadingText.classList.remove('hidden');
    
    try {
        const formData = new FormData(this);
        
        const response = await fetch('{{ route("contact.store") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            // Show success message
            document.getElementById('quoteForm').classList.add('hidden');
            document.getElementById('quoteSuccess').classList.remove('hidden');
        } else {
            // Handle validation errors
            if (response.status === 422 && result.errors) {
                let errorMessage = 'Please fix the following errors:\n';
                Object.values(result.errors).forEach(errors => {
                    errors.forEach(error => {
                        errorMessage += '• ' + error + '\n';
                    });
                });
                throw new Error(errorMessage);
            } else {
                throw new Error(result.message || 'Network response was not ok');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        
        // Try to get more specific error message
        let errorMessage = 'There was an error sending your quote request. Please try again or contact us directly.';
        
        if (error.response) {
            try {
                const errorData = await error.response.json();
                errorMessage = errorData.message || errorMessage;
            } catch (e) {
                console.error('Could not parse error response:', e);
            }
        }
        
        alert(errorMessage);
        
        // Reset button state
        submitBtn.disabled = false;
        submitText.classList.remove('hidden');
        loadingText.classList.add('hidden');
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('quoteModal').classList.contains('hidden')) {
        closeQuoteModal();
    }
});
</script>
@endpush
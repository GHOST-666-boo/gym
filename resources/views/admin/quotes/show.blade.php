@extends('layouts.admin')

@section('title', 'Quote Request #' . $quote->id)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Quote Request #{{ $quote->id }}</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Submitted {{ $quote->created_at->format('M j, Y \a\t g:i A') }}
                        ({{ $quote->created_at->diffForHumans() }})
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <!-- Status Badge -->
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $quote->status_color }}">
                        {{ ucfirst($quote->status) }}
                    </span>
                    
                    <!-- Back Button -->
                    <a href="{{ route('admin.quotes.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                        ← Back to Quotes
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Customer Information -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Customer Information</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <p class="text-gray-900 font-medium">{{ $quote->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <p class="text-gray-900">
                                <a href="mailto:{{ $quote->email }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $quote->email }}
                                </a>
                            </p>
                        </div>
                        @if($quote->phone)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <p class="text-gray-900">
                                <a href="tel:{{ $quote->phone }}" class="text-blue-600 hover:text-blue-800">
                                    {{ $quote->phone }}
                                </a>
                            </p>
                        </div>
                        @endif
                        @if($quote->company)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Company/Organization</label>
                            <p class="text-gray-900 font-medium">{{ $quote->company }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Requested Products -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Requested Products ({{ count($quote->products) }})
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Product
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Category
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Quantity
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Unit Price
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($quote->products as $product)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $product['name'] }}
                                            </div>
                                            @if(isset($product['id']) && $product['id'])
                                                <div class="text-sm text-gray-500">
                                                    ID: {{ $product['id'] }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $product['category'] ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $product['quantity'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${{ number_format($product['price'], 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    ${{ number_format($product['total'], 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-right text-sm font-medium text-gray-900">
                                    Total Amount:
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-lg font-bold text-gray-900">
                                    ${{ number_format($quote->total_amount, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Customer Message -->
            @if($quote->message)
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Customer Requirements</h2>
                </div>
                <div class="p-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-gray-700 whitespace-pre-wrap">{{ $quote->message }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Status Management -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Status Management</h2>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.quotes.update-status', $quote) }}">
                        @csrf
                        @method('PATCH')
                        
                        <div class="space-y-4">
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                    Update Status
                                </label>
                                <select name="status" id="status" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="pending" {{ $quote->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="processing" {{ $quote->status === 'processing' ? 'selected' : '' }}>Processing</option>
                                    <option value="quoted" {{ $quote->status === 'quoted' ? 'selected' : '' }}>Quoted</option>
                                    <option value="completed" {{ $quote->status === 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="cancelled" {{ $quote->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>
                            
                            <button type="submit" 
                                    class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200">
                                Update Status
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Quote Details -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Quote Details</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quote ID</label>
                        <p class="text-gray-900 font-mono">#{{ $quote->id }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Status</label>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $quote->status_color }}">
                            {{ ucfirst($quote->status) }}
                        </span>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Total Products</label>
                        <p class="text-gray-900">{{ $quote->total_products }} items</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Value</label>
                        <p class="text-gray-900 font-semibold text-lg">${{ number_format($quote->total_amount, 2) }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Submitted</label>
                        <p class="text-gray-900">{{ $quote->created_at->format('M j, Y') }}</p>
                        <p class="text-sm text-gray-500">{{ $quote->created_at->diffForHumans() }}</p>
                    </div>
                    
                    @if($quote->quoted_at)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quoted Date</label>
                        <p class="text-gray-900">{{ $quote->quoted_at->format('M j, Y') }}</p>
                    </div>
                    @endif
                    
                    @if($quote->expires_at)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quote Expires</label>
                        <p class="text-gray-900">{{ $quote->expires_at->format('M j, Y') }}</p>
                        @if($quote->isExpired())
                            <p class="text-sm text-red-600 font-medium">Expired</p>
                        @else
                            <p class="text-sm text-green-600">{{ $quote->expires_at->diffForHumans() }}</p>
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
                </div>
                <div class="p-6 space-y-3">
                    <a href="mailto:{{ $quote->email }}?subject=Quote Request #{{ $quote->id }}" 
                       class="w-full bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition-colors duration-200 text-center block">
                        📧 Email Customer
                    </a>
                    
                    @if($quote->phone)
                    <a href="tel:{{ $quote->phone }}" 
                       class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200 text-center block">
                        📞 Call Customer
                    </a>
                    @endif
                    
                    <button onclick="window.print()" 
                            class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-gray-700 transition-colors duration-200">
                        🖨️ Print Quote
                    </button>
                    
                    <button onclick="deleteQuoteAndRedirect({{ $quote->id }})" 
                            class="w-full bg-red-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-red-700 transition-colors duration-200">
                        🗑️ Delete Quote
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
@media print {
    .no-print {
        display: none !important;
    }
    
    body {
        font-size: 12px;
    }
    
    .bg-white {
        background: white !important;
    }
    
    .shadow {
        box-shadow: none !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Delete function for show page
function deleteQuoteAndRedirect(quoteId) {
    if (confirm('Are you sure you want to delete this quote request? This action cannot be undone.')) {
        fetch(`/admin/quotes/${quoteId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                // Redirect to quotes index after successful delete
                window.location.href = '{{ route("admin.quotes.index") }}';
            } else {
                throw new Error('Network response was not ok');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the quote. Please try again.');
        });
    }
}
</script>
@endpush
@endsection
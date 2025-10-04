@extends('layouts.admin')

@section('title', 'Quote Requests')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Quote Requests</h1>
            <p class="text-gray-600 mt-1">Manage customer quote requests and bulk orders</p>
        </div>
        <div class="flex items-center space-x-3">
            <div class="text-sm text-gray-500">
                Total: {{ $stats['total'] }} | Pending: {{ $stats['pending'] }}
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="p-6 space-y-6">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="flex-1">
                        <div class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-1">Total</div>
                        <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['total']) }}</div>
                    </div>
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="flex-1">
                        <div class="text-xs font-semibold text-yellow-600 uppercase tracking-wider mb-1">Pending</div>
                        <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['pending']) }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="flex-1">
                        <div class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-1">Processing</div>
                        <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['processing']) }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="flex-1">
                        <div class="text-xs font-semibold text-green-600 uppercase tracking-wider mb-1">Quoted</div>
                        <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['quoted']) }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="flex-1">
                        <div class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-1">Completed</div>
                        <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['completed']) }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex items-center">
                    <div class="flex-1">
                        <div class="text-xs font-semibold text-purple-600 uppercase tracking-wider mb-1">This Week</div>
                        <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['recent']) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Filters & Search</h3>
            </div>
            <div class="p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input type="text" 
                               id="search" 
                               name="search" 
                               value="{{ request('search') }}" 
                               placeholder="Search by name, email, ID..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="quoted" {{ request('status') === 'quoted' ? 'selected' : '' }}>Quoted</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                        <input type="date" 
                               id="date_from" 
                               name="date_from" 
                               value="{{ request('date_from') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                        <input type="date" 
                               id="date_to" 
                               name="date_to" 
                               value="{{ request('date_to') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                            Search
                        </button>
                        <a href="{{ route('admin.quotes.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                            Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quotes Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Quote Requests</h3>
                @if($quotes->count() > 0)
                    <div class="relative">
                        <button type="button" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200" onclick="toggleBulkActions()">
                            Bulk Actions
                        </button>
                        <div id="bulk-actions-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-gray-200">
                            <div class="py-1">
                                <a href="#" onclick="submitBulkAction('mark_processing')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Mark as Processing</a>
                                <a href="#" onclick="submitBulkAction('mark_quoted')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Mark as Quoted</a>
                                <a href="#" onclick="submitBulkAction('mark_completed')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Mark as Completed</a>
                                <div class="border-t border-gray-100"></div>
                                <a href="#" onclick="submitBulkAction('delete')" class="block px-4 py-2 text-sm text-red-700 hover:bg-gray-100">Delete Selected</a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            
            <div class="overflow-x-auto">
                @if($quotes->count() > 0)
                    <form id="bulk-action-form" method="POST" action="{{ route('admin.quotes.bulk-action') }}">
                        @csrf
                        <input type="hidden" name="action" id="bulk-action-input">
                        <!-- Debug: Form action URL -->
                        <!-- {{ route('admin.quotes.bulk-action') }} -->
                        
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                                        <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quote ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($quotes as $quote)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" name="quotes[]" value="{{ $quote->id }}" class="quote-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">#{{ $quote->id }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $quote->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $quote->email }}</div>
                                            @if($quote->company)
                                                <div class="text-xs text-gray-400">{{ $quote->company }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">{{ count($quote->products) }} products</div>
                                            <div class="text-sm text-gray-500">{{ $quote->total_products }} items</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">${{ number_format($quote->total_amount, 2) }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $quote->status_color }}">
                                                {{ ucfirst($quote->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $quote->created_at->format('M j, Y') }}<br>
                                            {{ $quote->created_at->format('g:i A') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center space-x-2">
                                                <a href="{{ route('admin.quotes.show', $quote) }}" 
                                                   class="text-blue-600 hover:text-blue-900 p-1 rounded" title="View Details">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </a>
                                                <button onclick="deleteQuote({{ $quote->id }})" class="text-red-600 hover:text-red-900 p-1 rounded" title="Delete">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </form>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $quotes->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">No quote requests found</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            @if(request()->hasAny(['search', 'status', 'date_from', 'date_to']))
                                Try adjusting your search criteria or <a href="{{ route('admin.quotes.index') }}" class="text-blue-600 hover:text-blue-800">view all quotes</a>.
                            @else
                                When customers request quotes, they'll appear here.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
// Bulk actions functionality
function toggleBulkActions() {
    const menu = document.getElementById('bulk-actions-menu');
    menu.classList.toggle('hidden');
}

function submitBulkAction(action) {
    const checkedBoxes = document.querySelectorAll('.quote-checkbox:checked');
    
    if (checkedBoxes.length === 0) {
        alert('Please select at least one quote.');
        return;
    }
    
    let confirmMessage = '';
    switch (action) {
        case 'mark_processing':
            confirmMessage = `Mark ${checkedBoxes.length} quote(s) as processing?`;
            break;
        case 'mark_quoted':
            confirmMessage = `Mark ${checkedBoxes.length} quote(s) as quoted?`;
            break;
        case 'mark_completed':
            confirmMessage = `Mark ${checkedBoxes.length} quote(s) as completed?`;
            break;
        case 'delete':
            confirmMessage = `Delete ${checkedBoxes.length} quote(s)? This action cannot be undone.`;
            break;
    }
    
    if (confirm(confirmMessage)) {
        // Use AJAX instead of form submit to avoid CSRF issues
        const selectedQuotes = Array.from(checkedBoxes).map(cb => cb.value);
        
        fetch('{{ route("admin.quotes.bulk-action") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: action,
                quotes: selectedQuotes
            })
        })
        .then(response => {
            if (response.ok) {
                // Reload page to show updated data
                window.location.reload();
            } else {
                throw new Error('Network response was not ok');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing the bulk action. Please try again.');
        });
    }
    
    document.getElementById('bulk-actions-menu').classList.add('hidden');
}

// Individual delete function
function deleteQuote(quoteId) {
    if (confirm('Are you sure you want to delete this quote? This action cannot be undone.')) {
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
                // Reload page to show updated data
                window.location.reload();
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

// Select all functionality
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.quote-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Update select all when individual checkboxes change
document.querySelectorAll('.quote-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const checkedCount = document.querySelectorAll('.quote-checkbox:checked').length;
        const totalCount = document.querySelectorAll('.quote-checkbox').length;
        const selectAll = document.getElementById('select-all');
        
        selectAll.checked = checkedCount === totalCount;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < totalCount;
    });
});

// Close bulk actions menu when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('bulk-actions-menu');
    const button = event.target.closest('button');
    
    if (!button || !button.onclick || button.onclick.toString().indexOf('toggleBulkActions') === -1) {
        menu.classList.add('hidden');
    }
});
</script>
@endpush
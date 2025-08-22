@extends('layouts.admin')

@section('title', 'Reviews Management')

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reviews Management</h1>
            <p class="text-gray-600 mt-1">Manage customer reviews and ratings</p>
        </div>
        <div class="flex items-center space-x-4">
            <!-- Stats -->
            <div class="flex items-center space-x-6 text-sm">
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
                    <div class="text-gray-500">Total</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600">{{ $stats['pending'] }}</div>
                    <div class="text-gray-500">Pending</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ $stats['approved'] }}</div>
                    <div class="text-gray-500">Approved</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
<div class="p-6">
    <!-- Filters and Search -->
    <div class="mb-6 bg-white rounded-lg border border-gray-200 p-4">
        <form method="GET" action="{{ route('admin.reviews.index') }}" class="flex flex-wrap items-center gap-4">
            <!-- Status Filter -->
            <div class="flex items-center space-x-2">
                <label for="status" class="text-sm font-medium text-gray-700">Status:</label>
                <select name="status" id="status" class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Reviews</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                </select>
            </div>

            <!-- Rating Filter -->
            <div class="flex items-center space-x-2">
                <label for="rating" class="text-sm font-medium text-gray-700">Rating:</label>
                <select name="rating" id="rating" class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Ratings</option>
                    @for($i = 5; $i >= 1; $i--)
                        <option value="{{ $i }}" {{ request('rating') == $i ? 'selected' : '' }}>{{ $i }} Star{{ $i > 1 ? 's' : '' }}</option>
                    @endfor
                </select>
            </div>

            <!-- Search -->
            <div class="flex items-center space-x-2 flex-1">
                <label for="search" class="text-sm font-medium text-gray-700">Search:</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" 
                       placeholder="Search by product name, reviewer name, or title..."
                       class="flex-1 border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Filter Button -->
            <button type="submit" class="bg-blue-600 text-white px-4 py-1 rounded-md text-sm hover:bg-blue-700 transition-colors duration-200">
                Filter
            </button>

            <!-- Clear Filters -->
            @if(request()->hasAny(['status', 'rating', 'search']))
                <a href="{{ route('admin.reviews.index') }}" class="text-gray-600 hover:text-gray-800 text-sm">
                    Clear Filters
                </a>
            @endif
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="mb-4 bg-white rounded-lg border border-gray-200 p-4">
        <form id="bulk-actions-form" method="POST" class="flex items-center space-x-4">
            @csrf
            <div class="flex items-center space-x-2">
                <input type="checkbox" id="select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <label for="select-all" class="text-sm font-medium text-gray-700">Select All</label>
            </div>

            <div class="flex items-center space-x-2">
                <select id="bulk-action" class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Bulk Actions</option>
                    <option value="approve">Approve Selected</option>
                    <option value="reject">Reject Selected</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <button type="button" id="apply-bulk-action" class="bg-gray-600 text-white px-4 py-1 rounded-md text-sm hover:bg-gray-700 transition-colors duration-200">
                    Apply
                </button>
            </div>
        </form>
    </div>

    <!-- Reviews Table -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        @if($reviews->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Review
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Product
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Reviewer
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Rating
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($reviews as $review)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" name="review_ids[]" value="{{ $review->id }}" class="review-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="max-w-xs">
                                        <div class="font-medium text-gray-900 truncate">{{ $review->title }}</div>
                                        <div class="text-sm text-gray-500 truncate">{{ Str::limit($review->comment, 100) }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $review->product->name }}</div>
                                    <div class="text-sm text-gray-500">${{ number_format($review->product->price, 2) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $review->reviewer_name }}</div>
                                    <div class="text-sm text-gray-500">{{ $review->reviewer_email }}</div>
                                    @if($review->user)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                            Verified
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $review->rating)
                                                <svg class="h-4 w-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @else
                                                <svg class="h-4 w-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                            @endif
                                        @endfor
                                        <span class="ml-1 text-sm text-gray-600">{{ $review->rating }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($review->is_approved)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Approved
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $review->created_at->format('M j, Y') }}
                                    <div class="text-xs text-gray-400">{{ $review->created_at->diffForHumans() }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('admin.reviews.show', $review) }}" class="text-blue-600 hover:text-blue-900">
                                            View
                                        </a>
                                        @if($review->is_approved)
                                            <form action="{{ route('admin.reviews.reject', $review) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900" onclick="return confirm('Reject this review?')">
                                                    Reject
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.reviews.approve', $review) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-900">
                                                    Approve
                                                </button>
                                            </form>
                                        @endif
                                        <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this review permanently?')">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $reviews->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No reviews found</h3>
                <p class="text-gray-500">No reviews match your current filters.</p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all functionality
    const selectAllCheckbox = document.getElementById('select-all');
    const reviewCheckboxes = document.querySelectorAll('.review-checkbox');
    
    selectAllCheckbox.addEventListener('change', function() {
        reviewCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // Bulk actions
    const bulkActionSelect = document.getElementById('bulk-action');
    const applyBulkActionBtn = document.getElementById('apply-bulk-action');
    const bulkActionsForm = document.getElementById('bulk-actions-form');

    applyBulkActionBtn.addEventListener('click', function() {
        const selectedAction = bulkActionSelect.value;
        const selectedReviews = Array.from(reviewCheckboxes)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);

        if (!selectedAction) {
            alert('Please select an action.');
            return;
        }

        if (selectedReviews.length === 0) {
            alert('Please select at least one review.');
            return;
        }

        let confirmMessage = '';
        let actionUrl = '';

        switch (selectedAction) {
            case 'approve':
                confirmMessage = `Approve ${selectedReviews.length} selected review(s)?`;
                actionUrl = '{{ route("admin.reviews.bulk-approve") }}';
                break;
            case 'reject':
                confirmMessage = `Reject ${selectedReviews.length} selected review(s)?`;
                actionUrl = '{{ route("admin.reviews.bulk-reject") }}';
                break;
            case 'delete':
                confirmMessage = `Delete ${selectedReviews.length} selected review(s) permanently?`;
                actionUrl = '{{ route("admin.reviews.bulk-delete") }}';
                break;
        }

        if (confirm(confirmMessage)) {
            // Create hidden inputs for selected review IDs
            selectedReviews.forEach(reviewId => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'review_ids[]';
                input.value = reviewId;
                bulkActionsForm.appendChild(input);
            });

            bulkActionsForm.action = actionUrl;
            bulkActionsForm.submit();
        }
    });
});
</script>
@endpush
@endsection
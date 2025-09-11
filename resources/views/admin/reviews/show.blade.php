@extends('layouts.admin')

@section('title', 'Review Details')

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Review Details</h1>
            <p class="text-gray-600 mt-1">View and manage customer review</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.reviews.index') }}" 
               class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors duration-200">
                <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Reviews
            </a>
        </div>
    </div>
@endsection

@section('content')
<div class="p-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Review Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Review Content -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ $review->title }}</h2>
                        <div class="flex items-center mb-3">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $review->rating)
                                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @else
                                    <svg class="h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endif
                            @endfor
                            <span class="ml-2 text-sm text-gray-600">{{ $review->rating }}/5</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        @if($review->is_approved)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Approved
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                Pending
                            </span>
                        @endif
                    </div>
                </div>

                <div class="prose max-w-none">
                    <p class="text-gray-700 leading-relaxed">{{ $review->comment }}</p>
                </div>
            </div>

            <!-- Product Information -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Product Information</h3>
                <div class="flex items-start space-x-4">
                    @if($review->product->image_path)
                        <img src="{{ asset('storage/' . $review->product->image_path) }}" 
                             alt="{{ $review->product->name }}" 
                             class="w-20 h-20 object-cover rounded-lg">
                    @else
                        <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center">
                            <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    @endif
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-900">{{ $review->product->name }}</h4>
                        <p class="text-gray-600 text-sm mt-1">{{ $review->product->short_description }}</p>
                        <p class="text-blue-600 font-semibold mt-2">${{ number_format($review->product->price, 2) }}</p>
                        <a href="{{ route('products.show', $review->product->slug) }}" 
                           target="_blank"
                           class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 mt-2">
                            View Product
                            <svg class="h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Review Metadata -->
        <div class="space-y-6">
            <!-- Reviewer Information -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Reviewer Information</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Name</label>
                        <p class="text-gray-900">{{ $review->reviewer_name }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Email</label>
                        <p class="text-gray-900">{{ $review->reviewer_email }}</p>
                    </div>
                    @if($review->user)
                        <div>
                            <label class="text-sm font-medium text-gray-500">Account Status</label>
                            <p class="text-green-600 flex items-center">
                                <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Verified Customer
                            </p>
                        </div>
                    @else
                        <div>
                            <label class="text-sm font-medium text-gray-500">Account Status</label>
                            <p class="text-gray-600">Guest User</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Review Metadata -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Review Details</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Submitted</label>
                        <p class="text-gray-900">{{ $review->created_at->format('M j, Y \a\t g:i A') }}</p>
                        <p class="text-xs text-gray-500">{{ $review->created_at->diffForHumans() }}</p>
                    </div>
                    @if($review->is_approved && $review->approved_at)
                        <div>
                            <label class="text-sm font-medium text-gray-500">Approved</label>
                            <p class="text-gray-900">{{ $review->approved_at->format('M j, Y \a\t g:i A') }}</p>
                            @if($review->approvedBy)
                                <p class="text-xs text-gray-500">by {{ $review->approvedBy->name }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                <div class="space-y-3">
                    @if($review->is_approved)
                        <form action="{{ route('admin.reviews.reject', $review) }}" method="POST" class="w-full">
                            @csrf
                            <button type="submit" 
                                    onclick="return confirm('Are you sure you want to reject this review?')"
                                    class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors duration-200">
                                <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                Reject Review
                            </button>
                        </form>
                    @else
                        <form action="{{ route('admin.reviews.approve', $review) }}" method="POST" class="w-full">
                            @csrf
                            <button type="submit" 
                                    class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200">
                                <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Approve Review
                            </button>
                        </form>
                    @endif

                    <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" class="w-full">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                onclick="return confirm('Are you sure you want to delete this review? This action cannot be undone.')"
                                class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors duration-200">
                            <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Delete Review
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@extends('layouts.admin')

@section('title', 'Newsletter Subscriber Details')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Subscriber Details</h1>
            <p class="text-gray-600 mt-1">{{ $subscriber->email }}</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.newsletter.edit', ['newsletter' => $subscriber]) }}" 
               class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit
            </a>
            <a href="{{ route('admin.newsletter.index') }}" 
               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to List
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="p-6">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Subscriber Information -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Subscriber Information</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address:</label>
                                <p class="text-sm text-gray-900">{{ $subscriber->email }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name:</label>
                                <p class="text-sm text-gray-900">{{ $subscriber->name ?: 'Not provided' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status:</label>
                                <div>
                                    @if($subscriber->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Subscriber ID:</label>
                                <p class="text-sm text-gray-900">#{{ $subscriber->id }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subscription Timeline -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Subscription Timeline</h3>
                    </div>
                    <div class="p-6">
                        <div class="flow-root">
                            <ul class="-mb-8">
                                <li>
                                    <div class="relative pb-8">
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                                                    <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900">Subscribed</p>
                                                    <p class="text-sm text-gray-500">
                                                        {{ $subscriber->subscribed_at->format('F j, Y \a\t g:i A') }}
                                                        <span class="text-gray-400">({{ $subscriber->subscribed_at->diffForHumans() }})</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                @if($subscriber->unsubscribed_at)
                                    <li>
                                        <div class="relative pb-8">
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-yellow-500 flex items-center justify-center ring-8 ring-white">
                                                        <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5">
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900">Unsubscribed</p>
                                                        <p class="text-sm text-gray-500">
                                                            {{ $subscriber->unsubscribed_at->format('F j, Y \a\t g:i A') }}
                                                            <span class="text-gray-400">({{ $subscriber->unsubscribed_at->diffForHumans() }})</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endif

                                <li>
                                    <div class="relative">
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                                    <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                    </svg>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900">Last Updated</p>
                                                    <p class="text-sm text-gray-500">
                                                        {{ $subscriber->updated_at->format('F j, Y \a\t g:i A') }}
                                                        <span class="text-gray-400">({{ $subscriber->updated_at->diffForHumans() }})</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            @if($subscriber->is_active)
                                <form method="POST" action="{{ route('admin.newsletter.update', ['newsletter' => $subscriber]) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="email" value="{{ $subscriber->email }}">
                                    <input type="hidden" name="name" value="{{ $subscriber->name }}">
                                    <input type="hidden" name="is_active" value="0">
                                    <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center" 
                                            onclick="return confirm('Are you sure you want to deactivate this subscriber?')">
                                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18 12M6 6l12 12"></path>
                                        </svg>
                                        Deactivate Subscription
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.newsletter.update', ['newsletter' => $subscriber]) }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="email" value="{{ $subscriber->email }}">
                                    <input type="hidden" name="name" value="{{ $subscriber->name }}">
                                    <input type="hidden" name="is_active" value="1">
                                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Reactivate Subscription
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('admin.newsletter.edit', ['newsletter' => $subscriber]) }}" 
                               class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center">
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit Details
                            </a>

                            <form method="POST" action="{{ route('admin.newsletter.destroy', ['newsletter' => $subscriber]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center" 
                                        onclick="return confirm('Are you sure you want to permanently delete this subscriber? This action cannot be undone.')">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Delete Subscriber
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Subscriber Statistics -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Statistics</h3>
                    </div>
                    <div class="p-6">
                        <div class="text-center space-y-4">
                            <div class="border-b border-gray-200 pb-4">
                                <div class="text-2xl font-bold text-gray-900">{{ $subscriber->subscribed_at->diffInDays(now()) }}</div>
                                <div class="text-sm text-gray-500">Days Subscribed</div>
                            </div>
                            @if($subscriber->unsubscribed_at)
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">{{ $subscriber->subscribed_at->diffInDays($subscriber->unsubscribed_at) }}</div>
                                    <div class="text-sm text-gray-500">Days Active</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Technical Details -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Technical Details</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Unsubscribe Token:</label>
                            <code class="block p-2 bg-gray-100 rounded text-xs text-gray-800 break-all">{{ $subscriber->unsubscribe_token }}</code>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Unsubscribe URL:</label>
                            <a href="{{ $subscriber->unsubscribe_url }}" target="_blank" 
                               class="block p-2 bg-gray-100 rounded text-xs text-blue-600 hover:text-blue-800 break-all">
                                {{ $subscriber->unsubscribe_url }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
@extends('layouts.app')

@section('title', 'Newsletter Preferences - Gym Machines')
@section('description', 'Manage your newsletter preferences')

@section('content')
<div class="min-h-screen bg-gray-50 py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-2xl">
        <div class="flex justify-center">
            <div class="h-12 w-12 bg-blue-600 rounded-lg flex items-center justify-center">
                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
        </div>
        <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">
            Newsletter Preferences
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Manage your subscription settings
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-2xl">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            <!-- Subscriber Info -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Your Information</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email Address</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $subscriber->email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $subscriber->name ?: 'Not provided' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <p class="mt-1">
                                @if($subscriber->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Unsubscribed
                                    </span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Subscribed Since</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $subscriber->subscribed_at->format('M j, Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subscription Status -->
            <div class="mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Subscription Status</h3>
                @if($subscriber->is_active)
                    <div class="rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">
                                    You're subscribed to our newsletter
                                </h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <p>You'll receive updates about new gym machines, fitness tips, and exclusive offers.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="rounded-md bg-yellow-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">
                                    You're currently unsubscribed
                                </h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>You unsubscribed on {{ $subscriber->unsubscribed_at->format('M j, Y') }}. You can resubscribe anytime using the form in our website footer.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4">
                @if($subscriber->is_active)
                    <a href="{{ route('newsletter.unsubscribe', $subscriber->unsubscribe_token) }}" 
                       class="flex-1 inline-flex justify-center py-2 px-4 border border-red-300 rounded-md shadow-sm bg-white text-sm font-medium text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
                       onclick="return confirm('Are you sure you want to unsubscribe from our newsletter?')">
                        Unsubscribe
                    </a>
                @endif
                
                <a href="{{ route('home') }}" 
                   class="flex-1 inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    Return to Home
                </a>
            </div>

            <!-- Future Enhancement Notice -->
            <div class="mt-8 p-4 bg-blue-50 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            More Options Coming Soon
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>We're working on adding more preference options like frequency settings and content categories. Stay tuned!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
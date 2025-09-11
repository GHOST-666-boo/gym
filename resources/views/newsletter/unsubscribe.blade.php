@extends('layouts.app')

@section('title', 'Newsletter Unsubscribe - Gym Machines')
@section('description', 'Unsubscribe from Gym Machines newsletter')

@section('content')
<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="flex justify-center">
            <div class="h-12 w-12 bg-blue-600 rounded-lg flex items-center justify-center">
                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>
        </div>
        <h2 class="mt-6 text-center text-3xl font-bold tracking-tight text-gray-900">
            Newsletter Unsubscribe
        </h2>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
            @if($success)
                <div class="rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">
                                @if(isset($already_unsubscribed) && $already_unsubscribed)
                                    Already Unsubscribed
                                @else
                                    Successfully Unsubscribed
                                @endif
                            </h3>
                            <div class="mt-2 text-sm text-green-700">
                                <p>{{ $message }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                Unsubscribe Failed
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>{{ $message }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-6">
                <p class="text-sm text-gray-600 text-center">
                    We're sorry to see you go! If you have any feedback about our newsletter, 
                    please don't hesitate to <a href="{{ route('contact') }}" class="text-blue-600 hover:text-blue-500">contact us</a>.
                </p>
            </div>

            <div class="mt-6">
                <a href="{{ route('home') }}" 
                   class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    Return to Home
                </a>
            </div>

            @if($success && !isset($already_unsubscribed))
                <div class="mt-4">
                    <p class="text-xs text-gray-500 text-center">
                        Changed your mind? You can always subscribe again using the form in our website footer.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
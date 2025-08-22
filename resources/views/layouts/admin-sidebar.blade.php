<aside class="w-64 bg-gray-900 text-white min-h-screen">
    <!-- Logo -->
    <div class="p-6 border-b border-gray-800">
        <div class="flex items-center">
            <div class="h-8 w-8 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <div>
                <div class="text-lg font-bold">Gym Machines</div>
                <div class="text-xs text-gray-400">Admin Panel</div>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="mt-6">
        <div class="px-4">
            <!-- Dashboard -->
            <a href="{{ route('admin.dashboard') }}" 
               class="@if(request()->routeIs('admin.dashboard')) bg-blue-600 text-white @else text-gray-300 hover:bg-gray-800 hover:text-white @endif group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200">
                <svg class="@if(request()->routeIs('admin.dashboard')) text-white @else text-gray-400 group-hover:text-white @endif mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                </svg>
                Dashboard
            </a>

            <!-- Products -->
            <div class="mt-2">
                <div class="@if(request()->routeIs('admin.products.*')) bg-gray-800 @endif rounded-md">
                    <a href="{{ route('admin.products.index') }}" 
                       class="@if(request()->routeIs('admin.products.*')) bg-blue-600 text-white @else text-gray-300 hover:bg-gray-800 hover:text-white @endif group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200">
                        <svg class="@if(request()->routeIs('admin.products.*')) text-white @else text-gray-400 group-hover:text-white @endif mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        Products
                    </a>
                    
                    @if(request()->routeIs('admin.products.*'))
                        <div class="ml-8 mt-1 space-y-1">
                            <a href="{{ route('admin.products.index') }}" 
                               class="@if(request()->routeIs('admin.products.index')) text-blue-300 @else text-gray-400 hover:text-white @endif block px-3 py-1 text-xs font-medium rounded transition-colors duration-200">
                                All Products
                            </a>
                            <a href="{{ route('admin.products.create') }}" 
                               class="@if(request()->routeIs('admin.products.create')) text-blue-300 @else text-gray-400 hover:text-white @endif block px-3 py-1 text-xs font-medium rounded transition-colors duration-200">
                                Add New Product
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Categories -->
            <div class="mt-2">
                <div class="@if(request()->routeIs('admin.categories.*')) bg-gray-800 @endif rounded-md">
                    <a href="{{ route('admin.categories.index') }}" 
                       class="@if(request()->routeIs('admin.categories.*')) bg-blue-600 text-white @else text-gray-300 hover:bg-gray-800 hover:text-white @endif group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200">
                        <svg class="@if(request()->routeIs('admin.categories.*')) text-white @else text-gray-400 group-hover:text-white @endif mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        Categories
                    </a>
                    
                    @if(request()->routeIs('admin.categories.*'))
                        <div class="ml-8 mt-1 space-y-1">
                            <a href="{{ route('admin.categories.index') }}" 
                               class="@if(request()->routeIs('admin.categories.index')) text-blue-300 @else text-gray-400 hover:text-white @endif block px-3 py-1 text-xs font-medium rounded transition-colors duration-200">
                                All Categories
                            </a>
                            <a href="{{ route('admin.categories.create') }}" 
                               class="@if(request()->routeIs('admin.categories.create')) text-blue-300 @else text-gray-400 hover:text-white @endif block px-3 py-1 text-xs font-medium rounded transition-colors duration-200">
                                Add New Category
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Reviews -->
            <div class="mt-2">
                <div class="@if(request()->routeIs('admin.reviews.*')) bg-gray-800 @endif rounded-md">
                    <a href="{{ route('admin.reviews.index') }}" 
                       class="@if(request()->routeIs('admin.reviews.*')) bg-blue-600 text-white @else text-gray-300 hover:bg-gray-800 hover:text-white @endif group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200">
                        <svg class="@if(request()->routeIs('admin.reviews.*')) text-white @else text-gray-400 group-hover:text-white @endif mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        Reviews
                        @php
                            $pendingCount = \App\Models\Review::pending()->count();
                        @endphp
                        @if($pendingCount > 0)
                            <span class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">{{ $pendingCount }}</span>
                        @endif
                    </a>
                    
                    @if(request()->routeIs('admin.reviews.*'))
                        <div class="ml-8 mt-1 space-y-1">
                            <a href="{{ route('admin.reviews.index') }}" 
                               class="@if(request()->routeIs('admin.reviews.index') && !request()->has('status')) text-blue-300 @else text-gray-400 hover:text-white @endif block px-3 py-1 text-xs font-medium rounded transition-colors duration-200">
                                All Reviews
                            </a>
                            <a href="{{ route('admin.reviews.index', ['status' => 'pending']) }}" 
                               class="@if(request()->get('status') === 'pending') text-blue-300 @else text-gray-400 hover:text-white @endif block px-3 py-1 text-xs font-medium rounded transition-colors duration-200">
                                Pending Reviews
                                @if($pendingCount > 0)
                                    <span class="ml-1 bg-red-500 text-white text-xs px-1 py-0.5 rounded">{{ $pendingCount }}</span>
                                @endif
                            </a>
                            <a href="{{ route('admin.reviews.index', ['status' => 'approved']) }}" 
                               class="@if(request()->get('status') === 'approved') text-blue-300 @else text-gray-400 hover:text-white @endif block px-3 py-1 text-xs font-medium rounded transition-colors duration-200">
                                Approved Reviews
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Newsletter -->
            <div class="mt-2">
                <div class="@if(request()->routeIs('admin.newsletter.*')) bg-gray-800 @endif rounded-md">
                    <a href="{{ route('admin.newsletter.index') }}" 
                       class="@if(request()->routeIs('admin.newsletter.*')) bg-blue-600 text-white @else text-gray-300 hover:bg-gray-800 hover:text-white @endif group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200">
                        <svg class="@if(request()->routeIs('admin.newsletter.*')) text-white @else text-gray-400 group-hover:text-white @endif mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Newsletter
                        @php
                            $activeSubscribers = \App\Models\NewsletterSubscriber::active()->count();
                        @endphp
                        @if($activeSubscribers > 0)
                            <span class="ml-auto bg-green-500 text-white text-xs px-2 py-1 rounded-full">{{ $activeSubscribers }}</span>
                        @endif
                    </a>
                    
                    @if(request()->routeIs('admin.newsletter.*'))
                        <div class="ml-8 mt-1 space-y-1">
                            <a href="{{ route('admin.newsletter.index') }}" 
                               class="@if(request()->routeIs('admin.newsletter.index') && !request()->has('status')) text-blue-300 @else text-gray-400 hover:text-white @endif block px-3 py-1 text-xs font-medium rounded transition-colors duration-200">
                                All Subscribers
                            </a>
                            <a href="{{ route('admin.newsletter.index', ['status' => 'active']) }}" 
                               class="@if(request()->get('status') === 'active') text-blue-300 @else text-gray-400 hover:text-white @endif block px-3 py-1 text-xs font-medium rounded transition-colors duration-200">
                                Active Subscribers
                            </a>
                            <a href="{{ route('admin.newsletter.create') }}" 
                               class="@if(request()->routeIs('admin.newsletter.create')) text-blue-300 @else text-gray-400 hover:text-white @endif block px-3 py-1 text-xs font-medium rounded transition-colors duration-200">
                                Add Subscriber
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Analytics -->
            <div class="mt-2">
                <a href="{{ route('admin.analytics.index') }}" 
                   class="@if(request()->routeIs('admin.analytics.*')) bg-blue-600 text-white @else text-gray-300 hover:bg-gray-800 hover:text-white @endif group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200">
                    <svg class="@if(request()->routeIs('admin.analytics.*')) text-white @else text-gray-400 group-hover:text-white @endif mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Analytics
                </a>
            </div>

            <!-- Divider -->
            <div class="border-t border-gray-800 my-4"></div>

            <!-- Website Links -->
            <div class="space-y-1">
                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 py-2">
                    Website
                </div>
                <a href="{{ route('home') }}" target="_blank"
                   class="text-gray-300 hover:bg-gray-800 hover:text-white group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200">
                    <svg class="text-gray-400 group-hover:text-white mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    View Website
                    <svg class="ml-auto h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </a>
                <a href="{{ route('products.index') }}" target="_blank"
                   class="text-gray-300 hover:bg-gray-800 hover:text-white group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200">
                    <svg class="text-gray-400 group-hover:text-white mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    View Products
                    <svg class="ml-auto h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </a>
            </div>
        </div>
    </nav>

    <!-- User Info -->
    <div class="absolute bottom-0 w-64 p-4 border-t border-gray-800">
        <div class="flex items-center">
            <div class="h-8 w-8 bg-gray-600 rounded-full flex items-center justify-center">
                <svg class="h-5 w-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <div class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</div>
                <div class="text-xs text-gray-400 truncate">{{ Auth::user()->email }}</div>
            </div>
        </div>
    </div>
</aside>
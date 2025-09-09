<aside x-data="{ collapsed: false }" 
       :class="collapsed ? 'w-16' : 'w-64'" 
       class="bg-gray-900 text-white min-h-screen flex-shrink-0 flex flex-col transition-all duration-300 ease-in-out">
    
    <!-- Logo -->
    <div class="p-4 border-b border-gray-800 relative">
        <div class="flex items-center">
            @php
                $logoUrl = site_logo();
                $siteName = site_name();
            @endphp
            
            @if($logoUrl && $logoUrl !== asset('images/default-logo.png'))
                <!-- Custom Logo -->
                <div class="h-8 w-8 flex-shrink-0 rounded-lg overflow-hidden" 
                     :class="collapsed ? 'mr-0' : 'mr-3'">
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" class="h-full w-full object-contain">
                </div>
            @else
                <!-- Default Icon -->
                <div class="h-8 w-8 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0" 
                     :class="collapsed ? 'mr-0' : 'mr-3'">
                    <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            @endif
            
            <div x-show="!collapsed" class="transition-opacity duration-200">
                <div class="text-lg font-bold">{{ $siteName }}</div>
                <div class="text-xs text-gray-400">Admin Panel</div>
            </div>
        </div>
        
        <!-- Toggle Button -->
        <button @click="collapsed = !collapsed" 
                class="absolute -right-3 top-1/2 transform -translate-y-1/2 bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white rounded-full p-1.5 border border-gray-600 transition-colors duration-200">
            <svg x-show="!collapsed" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
            </svg>
            <svg x-show="collapsed" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
            </svg>
        </button>
    </div>

    <!-- Navigation Menu -->
    <nav class="mt-6 flex-1">
        <div :class="collapsed ? 'px-2' : 'px-4'">
            
            <!-- Dashboard -->
            <div class="relative group mb-2">
                <a href="{{ route('admin.dashboard') }}" 
                   :class="collapsed ? 'justify-center px-2' : 'px-3'"
                   class="@if(request()->routeIs('admin.dashboard')) bg-blue-600 text-white @else text-gray-300 hover:bg-gray-800 hover:text-white @endif flex items-center py-2 text-sm font-medium rounded-md transition-colors duration-200">
                    <svg class="@if(request()->routeIs('admin.dashboard')) text-white @else text-gray-400 group-hover:text-white @endif h-5 w-5 flex-shrink-0" 
                         :class="collapsed ? 'mr-0' : 'mr-3'" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                    </svg>
                    <span x-show="!collapsed" class="transition-opacity duration-200">Dashboard</span>
                </a>
                
                <!-- Tooltip -->
                <div x-show="collapsed" 
                     class="absolute left-full ml-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                    Dashboard
                </div>
            </div>

            <!-- Products -->
            <div class="relative group mb-2">
                <a href="{{ route('admin.products.index') }}" 
                   :class="collapsed ? 'justify-center px-2' : 'px-3'"
                   class="@if(request()->routeIs('admin.products.*')) bg-blue-600 text-white @else text-gray-300 hover:bg-gray-800 hover:text-white @endif flex items-center py-2 text-sm font-medium rounded-md transition-colors duration-200">
                    <svg class="@if(request()->routeIs('admin.products.*')) text-white @else text-gray-400 group-hover:text-white @endif h-5 w-5 flex-shrink-0" 
                         :class="collapsed ? 'mr-0' : 'mr-3'" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span x-show="!collapsed" class="transition-opacity duration-200">Products</span>
                </a>
                
                <!-- Tooltip -->
                <div x-show="collapsed" 
                     class="absolute left-full ml-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                    Products
                </div>
            </div>

            <!-- Categories -->
            <div class="relative group mb-2">
                <a href="{{ route('admin.categories.index') }}" 
                   :class="collapsed ? 'justify-center px-2' : 'px-3'"
                   class="@if(request()->routeIs('admin.categories.*')) bg-blue-600 text-white @else text-gray-300 hover:bg-gray-800 hover:text-white @endif flex items-center py-2 text-sm font-medium rounded-md transition-colors duration-200">
                    <svg class="@if(request()->routeIs('admin.categories.*')) text-white @else text-gray-400 group-hover:text-white @endif h-5 w-5 flex-shrink-0" 
                         :class="collapsed ? 'mr-0' : 'mr-3'" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                    <span x-show="!collapsed" class="transition-opacity duration-200">Categories</span>
                </a>
                
                <!-- Tooltip -->
                <div x-show="collapsed" 
                     class="absolute left-full ml-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                    Categories
                </div>
            </div>

            <!-- Reviews -->
            <div class="relative group mb-2">
                <a href="{{ route('admin.reviews.index') }}" 
                   :class="collapsed ? 'justify-center px-2' : 'px-3'"
                   class="@if(request()->routeIs('admin.reviews.*')) bg-blue-600 text-white @else text-gray-300 hover:bg-gray-800 hover:text-white @endif flex items-center py-2 text-sm font-medium rounded-md transition-colors duration-200">
                    <svg class="@if(request()->routeIs('admin.reviews.*')) text-white @else text-gray-400 group-hover:text-white @endif h-5 w-5 flex-shrink-0" 
                         :class="collapsed ? 'mr-0' : 'mr-3'" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <span x-show="!collapsed" class="transition-opacity duration-200">Reviews</span>
                </a>
                
                <!-- Tooltip -->
                <div x-show="collapsed" 
                     class="absolute left-full ml-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                    Reviews
                </div>
            </div>

            <!-- Newsletter -->
            <div class="relative group mb-2">
                <a href="{{ route('admin.newsletter.index') }}" 
                   :class="collapsed ? 'justify-center px-2' : 'px-3'"
                   class="@if(request()->routeIs('admin.newsletter.*')) bg-blue-600 text-white @else text-gray-300 hover:bg-gray-800 hover:text-white @endif flex items-center py-2 text-sm font-medium rounded-md transition-colors duration-200">
                    <svg class="@if(request()->routeIs('admin.newsletter.*')) text-white @else text-gray-400 group-hover:text-white @endif h-5 w-5 flex-shrink-0" 
                         :class="collapsed ? 'mr-0' : 'mr-3'" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <span x-show="!collapsed" class="transition-opacity duration-200">Newsletter</span>
                </a>
                
                <!-- Tooltip -->
                <div x-show="collapsed" 
                     class="absolute left-full ml-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                    Newsletter
                </div>
            </div>

            <!-- Analytics -->
            <div class="relative group mb-2">
                <a href="{{ route('admin.analytics.index') }}" 
                   :class="collapsed ? 'justify-center px-2' : 'px-3'"
                   class="@if(request()->routeIs('admin.analytics.*')) bg-blue-600 text-white @else text-gray-300 hover:bg-gray-800 hover:text-white @endif flex items-center py-2 text-sm font-medium rounded-md transition-colors duration-200">
                    <svg class="@if(request()->routeIs('admin.analytics.*')) text-white @else text-gray-400 group-hover:text-white @endif h-5 w-5 flex-shrink-0" 
                         :class="collapsed ? 'mr-0' : 'mr-3'" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span x-show="!collapsed" class="transition-opacity duration-200">Analytics</span>
                </a>
                
                <!-- Tooltip -->
                <div x-show="collapsed" 
                     class="absolute left-full ml-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                    Analytics
                </div>
            </div>

            <!-- Settings -->
            <div class="relative group mb-2">
                <a href="{{ route('admin.settings.index') }}" 
                   :class="collapsed ? 'justify-center px-2' : 'px-3'"
                   class="@if(request()->routeIs('admin.settings.*')) bg-blue-600 text-white @else text-gray-300 hover:bg-gray-800 hover:text-white @endif flex items-center py-2 text-sm font-medium rounded-md transition-colors duration-200">
                    <svg class="@if(request()->routeIs('admin.settings.*')) text-white @else text-gray-400 group-hover:text-white @endif h-5 w-5 flex-shrink-0" 
                         :class="collapsed ? 'mr-0' : 'mr-3'" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span x-show="!collapsed" class="transition-opacity duration-200">Settings</span>
                </a>
                
                <!-- Tooltip -->
                <div x-show="collapsed" 
                     class="absolute left-full ml-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                    Settings
                </div>
            </div>

            <!-- Divider -->
            <div class="border-t border-gray-800 my-4"></div>

            <!-- Website Links -->
            <div class="space-y-1">
                <div x-show="!collapsed" class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-3 py-2">
                    Website
                </div>
                
                <!-- View Website -->
                <div class="relative group mb-2">
                    <a href="{{ route('home') }}" target="_blank"
                       :class="collapsed ? 'justify-center px-2' : 'px-3'"
                       class="text-gray-300 hover:bg-gray-800 hover:text-white flex items-center py-2 text-sm font-medium rounded-md transition-colors duration-200">
                        <svg class="text-gray-400 group-hover:text-white h-5 w-5 flex-shrink-0" 
                             :class="collapsed ? 'mr-0' : 'mr-3'" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span x-show="!collapsed" class="transition-opacity duration-200">View Website</span>
                        <svg x-show="!collapsed" class="ml-auto h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    </a>
                    
                    <!-- Tooltip -->
                    <div x-show="collapsed" 
                         class="absolute left-full ml-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                        View Website
                    </div>
                </div>
                
                <!-- View Products -->
                <div class="relative group mb-2">
                    <a href="{{ route('products.index') }}" target="_blank"
                       :class="collapsed ? 'justify-center px-2' : 'px-3'"
                       class="text-gray-300 hover:bg-gray-800 hover:text-white flex items-center py-2 text-sm font-medium rounded-md transition-colors duration-200">
                        <svg class="text-gray-400 group-hover:text-white h-5 w-5 flex-shrink-0" 
                             :class="collapsed ? 'mr-0' : 'mr-3'" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span x-show="!collapsed" class="transition-opacity duration-200">View Products</span>
                        <svg x-show="!collapsed" class="ml-auto h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                    </a>
                    
                    <!-- Tooltip -->
                    <div x-show="collapsed" 
                         class="absolute left-full ml-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                        View Products
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- User Info -->
    <div class="mt-auto border-t border-gray-800" :class="collapsed ? 'p-2' : 'p-4'">
        <div class="flex items-center" :class="collapsed ? 'justify-center' : ''">
            <div class="h-8 w-8 bg-gray-600 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="h-5 w-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <div x-show="!collapsed" class="ml-3 flex-1 min-w-0 transition-opacity duration-200">
                <div class="text-sm font-medium text-white truncate">{{ Auth::user()->name }}</div>
                <div class="text-xs text-gray-400 truncate">{{ Auth::user()->email }}</div>
            </div>
        </div>
    </div>
</aside>
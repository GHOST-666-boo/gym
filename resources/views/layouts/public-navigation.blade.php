<nav x-data="{ open: false }" class="bg-white shadow-lg border-b border-gray-200">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center">
                        @if(site_logo() && site_logo() !== asset('images/default-logo.png'))
                            <img src="{{ site_logo() }}" alt="{{ site_name() }} Logo" class="h-8 w-auto mr-3">
                        @else
                            <div class="h-8 w-8 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                                <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                        @endif
                        <span class="text-xl font-bold text-gray-900">{{ site_name() }}</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:ml-10 md:flex md:space-x-8">
                    <a href="{{ route('home') }}"
                        class="@if(request()->routeIs('home')) text-blue-600 border-b-2 border-blue-600 @else text-gray-700 hover:text-blue-600 @endif px-3 py-2 text-sm font-medium transition-colors duration-200">
                        Home
                    </a>
                    <a href="{{ route('products.index') }}"
                        class="@if(request()->routeIs('products.*')) text-blue-600 border-b-2 border-blue-600 @else text-gray-700 hover:text-blue-600 @endif px-3 py-2 text-sm font-medium transition-colors duration-200">
                        Products
                    </a>

                    <!-- Categories Dropdown
                    @if(isset($categories) && $categories->count() > 0)
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="@if(request()->routeIs('products.category')) text-blue-600 border-b-2 border-blue-600 @else text-gray-700 hover:text-blue-600 @endif px-3 py-2 text-sm font-medium transition-colors duration-200 flex items-center">
                            Categories
                            <svg class="ml-1 h-4 w-4 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <div x-show="open" 
                             @click.away="open = false"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute z-50 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
                            <div class="py-1">
                                @foreach($categories as $category)
                                    <a href="{{ route('products.category', $category) }}" 
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-blue-600 transition-colors duration-200">
                                        {{ $category->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif -->


                    <a href="{{ route('contact') }}"
                        class="@if(request()->routeIs('contact.*')) text-blue-600 border-b-2 border-blue-600 @else text-gray-700 hover:text-blue-600 @endif px-3 py-2 text-sm font-medium transition-colors duration-200">
                        Contact
                    </a>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="hidden md:flex md:items-center md:flex-1 md:max-w-xs md:ml-8">
                <form method="GET" action="{{ route('products.search') }}" class="w-full">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" name="search" placeholder="Search gym machines..."
                            value="{{ request('search') }}"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </form>
            </div>

            <!-- Right Side Navigation -->
            <div class="hidden md:flex md:items-center md:space-x-4">
                <!-- Quote Cart Icon -->
                <x-quote-cart-icon />

                @auth
                    <a href="{{ route('admin.dashboard') }}"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition-colors duration-200">
                        Admin Panel
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit"
                            class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors duration-200">
                            Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}"
                        class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm font-medium transition-colors duration-200">
                        Login
                    </a>
                @endauth
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden flex items-center">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-700 hover:text-blue-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 transition-colors duration-200">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden md:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1 bg-gray-50 border-t border-gray-200">
            <!-- Mobile Search -->
            <div class="px-3 py-2">
                <form method="GET" action="{{ route('products.search') }}">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" name="search" placeholder="Search gym machines..."
                            value="{{ request('search') }}"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </form>
            </div>

            <a href="{{ route('home') }}"
                class="@if(request()->routeIs('home')) bg-blue-100 text-blue-600 @else text-gray-700 hover:bg-gray-100 hover:text-blue-600 @endif block px-3 py-2 rounded-md text-base font-medium transition-colors duration-200">
                Home
            </a>
            <a href="{{ route('products.index') }}"
                class="@if(request()->routeIs('products.*')) bg-blue-100 text-blue-600 @else text-gray-700 hover:bg-gray-100 hover:text-blue-600 @endif block px-3 py-2 rounded-md text-base font-medium transition-colors duration-200">
                Products
            </a>

            <!-- Mobile Categories -->
            @if(isset($categories) && $categories->count() > 0)
                <div class="px-3 py-2">
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Categories</div>
                    @foreach($categories as $category)
                        <a href="{{ route('products.category', $category) }}"
                            class="@if(request()->routeIs('products.category') && request()->route('category')->id == $category->id) bg-blue-100 text-blue-600 @else text-gray-600 hover:bg-gray-100 hover:text-blue-600 @endif block px-3 py-1 rounded-md text-sm font-medium transition-colors duration-200 ml-4">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            @endif


            <a href="{{ route('contact') }}"
                class="@if(request()->routeIs('contact.*')) bg-blue-100 text-blue-600 @else text-gray-700 hover:bg-gray-100 hover:text-blue-600 @endif block px-3 py-2 rounded-md text-base font-medium transition-colors duration-200">
                Contact
            </a>

            @auth
                <div class="border-t border-gray-200 pt-3 mt-3">
                    <a href="{{ route('admin.dashboard') }}"
                        class="block px-3 py-2 rounded-md text-base font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                        Admin Panel
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit"
                            class="block w-full text-left px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100 hover:text-blue-600 transition-colors duration-200">
                            Logout
                        </button>
                    </form>
                </div>
            @else
                <div class="border-t border-gray-200 pt-3 mt-3">
                    <a href="{{ route('login') }}"
                        class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100 hover:text-blue-600 transition-colors duration-200">
                        Login
                    </a>
                </div>
            @endauth
        </div>
    </div>
</nav>
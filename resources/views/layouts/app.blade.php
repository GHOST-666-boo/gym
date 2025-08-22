<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- SEO Meta Tags -->
        <title>@yield('title', 'Gym Machines - Professional Fitness Equipment')</title>
        <meta name="description" content="@yield('description', 'Discover our premium collection of gym machines and fitness equipment. Professional grade equipment for commercial and home gyms.')">
        <meta name="keywords" content="@yield('keywords', 'gym machines, fitness equipment, commercial gym, home gym, exercise equipment')">
        <meta name="author" content="Gym Machines">
        
        <!-- Open Graph Meta Tags -->
        <meta property="og:title" content="@yield('og_title', 'Gym Machines - Professional Fitness Equipment')">
        <meta property="og:description" content="@yield('og_description', 'Discover our premium collection of gym machines and fitness equipment.')">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="@yield('og_image', asset('images/gym-machines-logo.jpg'))">
        
        <!-- Twitter Card Meta Tags -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="@yield('twitter_title', 'Gym Machines - Professional Fitness Equipment')">
        <meta name="twitter:description" content="@yield('twitter_description', 'Discover our premium collection of gym machines and fitness equipment.')">
        <meta name="twitter:image" content="@yield('twitter_image', asset('images/gym-machines-logo.jpg'))">

        <!-- Canonical URL -->
        <link rel="canonical" href="{{ url()->current() }}">

        <!-- Structured Data -->
        @stack('structured-data')

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        @stack('styles')
    </head>
    <body class="font-sans antialiased bg-white">
        <!-- Skip to main content link for accessibility -->
        <a href="#main-content" class="skip-link">Skip to main content</a>
        
        <div class="min-h-screen flex flex-col">
            <!-- Public Navigation -->
            @include('layouts.public-navigation')

            <!-- Flash Messages -->
            @if(session('success') || session('error') || session('warning') || session('info'))
                <div class="flash-messages-container">
                    @if(session('success'))
                        <div class="flash-message bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mx-4 mt-4" role="alert">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>{{ session('success') }}</span>
                                </div>
                                <button type="button" class="flash-close text-green-700 hover:text-green-900" aria-label="Close">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="flash-message bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mx-4 mt-4" role="alert">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>{{ session('error') }}</span>
                                </div>
                                <button type="button" class="flash-close text-red-700 hover:text-red-900" aria-label="Close">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="flash-message bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg mx-4 mt-4" role="alert">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    <span>{{ session('warning') }}</span>
                                </div>
                                <button type="button" class="flash-close text-yellow-700 hover:text-yellow-900" aria-label="Close">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endif

                    @if(session('info'))
                        <div class="flash-message bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded-lg mx-4 mt-4" role="alert">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>{{ session('info') }}</span>
                                </div>
                                <button type="button" class="flash-close text-blue-700 hover:text-blue-900" aria-label="Close">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Page Content -->
            <main id="main-content" class="flex-grow" role="main">
                @yield('content')
            </main>

            <!-- Footer -->
            @include('layouts.footer')
        </div>

        <!-- Comparison Widget -->
        <div id="comparison-widget" class="fixed bottom-4 right-4 z-50 hidden">
            <div class="bg-blue-600 text-white rounded-lg shadow-lg p-4 max-w-sm">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="font-semibold text-sm">Product Comparison</h4>
                    <button onclick="closeComparisonWidget()" class="text-blue-200 hover:text-white">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <p class="text-xs text-blue-100 mb-3">
                    <span class="comparison-count">0</span> products selected for comparison
                </p>
                <div class="flex gap-2">
                    <a href="{{ route('products.compare') }}" 
                       class="flex-1 bg-white text-blue-600 px-3 py-2 rounded text-xs font-medium text-center hover:bg-blue-50 transition-colors duration-200">
                        Compare Now
                    </a>
                    <button onclick="clearComparisonWidget()" 
                            class="bg-blue-700 text-white px-3 py-2 rounded text-xs font-medium hover:bg-blue-800 transition-colors duration-200">
                        Clear
                    </button>
                </div>
            </div>
        </div>
        
        @stack('scripts')
        
        <!-- Lazy Loading Script -->
        <script>
            // Intersection Observer for lazy loading images
            document.addEventListener('DOMContentLoaded', function() {
                if ('IntersectionObserver' in window) {
                    const imageObserver = new IntersectionObserver((entries, observer) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                const img = entry.target;
                                if (img.dataset.src) {
                                    img.src = img.dataset.src;
                                    img.classList.remove('lazy-image');
                                    img.classList.add('loaded');
                                    observer.unobserve(img);
                                }
                            }
                        });
                    }, {
                        rootMargin: '50px 0px',
                        threshold: 0.01
                    });

                    document.querySelectorAll('.lazy-image').forEach(img => {
                        imageObserver.observe(img);
                    });
                } else {
                    // Fallback for browsers without IntersectionObserver
                    document.querySelectorAll('.lazy-image').forEach(img => {
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                        }
                    });
                }

                // Flash message close functionality
                document.querySelectorAll('.flash-close').forEach(button => {
                    button.addEventListener('click', function() {
                        this.closest('.flash-message').style.display = 'none';
                    });
                });

                // Auto-hide flash messages after 5 seconds
                setTimeout(() => {
                    document.querySelectorAll('.flash-message').forEach(message => {
                        message.style.transition = 'opacity 0.5s ease-out';
                        message.style.opacity = '0';
                        setTimeout(() => {
                            message.style.display = 'none';
                        }, 500);
                    });
                }, 5000);

                // Initialize comparison widget
                initializeComparisonWidget();
            });

            // Comparison widget functions
            function initializeComparisonWidget() {
                // Check if we're on a page that should show the comparison widget
                const currentPath = window.location.pathname;
                if (currentPath === '/compare') {
                    return; // Don't show widget on comparison page
                }

                // Get comparison count and update widget
                fetch('{{ route("products.compare.count") }}')
                .then(response => response.json())
                .then(data => {
                    updateComparisonWidget(data.count);
                })
                .catch(error => {
                    console.error('Error loading comparison count:', error);
                });
            }

            function updateComparisonWidget(count) {
                const widget = document.getElementById('comparison-widget');
                const countElement = widget.querySelector('.comparison-count');
                
                if (countElement) {
                    countElement.textContent = count;
                }
                
                if (count > 0) {
                    widget.classList.remove('hidden');
                } else {
                    widget.classList.add('hidden');
                }

                // Update navigation counters
                const navCounter = document.getElementById('nav-comparison-count');
                const mobileNavCounter = document.getElementById('mobile-nav-comparison-count');
                
                if (navCounter) {
                    navCounter.textContent = count;
                    if (count > 0) {
                        navCounter.classList.remove('hidden');
                    } else {
                        navCounter.classList.add('hidden');
                    }
                }
                
                if (mobileNavCounter) {
                    mobileNavCounter.textContent = count;
                    if (count > 0) {
                        mobileNavCounter.classList.remove('hidden');
                    } else {
                        mobileNavCounter.classList.add('hidden');
                    }
                }
            }

            function closeComparisonWidget() {
                document.getElementById('comparison-widget').classList.add('hidden');
            }

            function clearComparisonWidget() {
                if (confirm('Are you sure you want to clear all products from comparison?')) {
                    fetch('{{ route("products.compare.clear") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateComparisonWidget(0);
                            // Update comparison buttons if they exist
                            if (typeof updateComparisonButtons === 'function') {
                                updateComparisonButtons();
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }
            }
        </script>
    </body>
</html>

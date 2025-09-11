@extends('layouts.app')

@section('title', 'All Products - Premium Collection')
@section('description', 'Browse our complete collection of premium products. Find the perfect items for your needs.')

@section('content')
    <!-- Page Header -->
    <section class="bg-white py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Mobile Layout -->
            <div class="block md:hidden">
                <div class="text-center mb-4">
                    <h1 class="text-2xl font-bold text-gray-900">
                        @if(request('categories'))
                            @php
                                $selectedCategories = is_array(request('categories')) ? request('categories') : [request('categories')];
                                $selectedCategoryNames = $categories->whereIn('id', $selectedCategories)->pluck('name');
                            @endphp
                            @if($selectedCategoryNames->count() > 1)
                                {{ $selectedCategoryNames->take(2)->join(', ') }}{{ $selectedCategoryNames->count() > 2 ? ' & more' : '' }}
                            @else
                                {{ $selectedCategoryNames->first() ?? 'Our Products' }}
                            @endif
                        @else
                            Our Products
                        @endif
                    </h1>
                </div>

                <div class="flex items-center justify-between">
                    <p class="text-gray-600">
                        {{ $products->total() }} items
                    </p>

                    <!-- Grid View Toggle for Mobile -->
                    <div class="flex items-center gap-2 bg-gray-100 rounded-lg p-1">
                        <button onclick="setGridView(1)" id="grid-1-btn"
                            class="grid-toggle-btn p-2 rounded-md transition-colors duration-200 bg-white shadow-sm">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <button onclick="setGridView(2)" id="grid-2-mobile-btn"
                            class="grid-toggle-btn p-2 rounded-md transition-colors duration-200">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Desktop Layout -->
            <div class="hidden md:block">
                <div class="flex items-center justify-between w-full">
                    <div class="text-center flex-grow">
                        <h1 class="text-3xl font-bold text-gray-900">
                            @if(request('categories'))
                                @php
                                    $selectedCategories = is_array(request('categories')) ? request('categories') : [request('categories')];
                                    $selectedCategoryNames = $categories->whereIn('id', $selectedCategories)->pluck('name');
                                @endphp
                                @if($selectedCategoryNames->count() > 1)
                                    {{ $selectedCategoryNames->take(2)->join(', ') }}{{ $selectedCategoryNames->count() > 2 ? ' & more' : '' }}
                                @else
                                    {{ $selectedCategoryNames->first() ?? 'Our Products' }}
                                @endif
                            @else
                                Our Products
                            @endif
                        </h1>
                        <p class="text-gray-600 mt-2">
                            {{ $products->total() }} items
                        </p>
                    </div>

                    <!-- Grid View Toggle for Desktop -->
                    <div class="flex items-center gap-2 bg-gray-100 rounded-lg p-1">
                        <button onclick="setGridView(2)" id="grid-2-btn"
                            class="grid-toggle-btn p-2 rounded-md transition-colors duration-200 bg-white shadow-sm">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </button>
                        <button onclick="setGridView(4)" id="grid-4-btn"
                            class="grid-toggle-btn p-2 rounded-md transition-colors duration-200">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 5a1 1 0 011-1h1a1 1 0 011 1v1a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 12a1 1 0 011-1h1a1 1 0 011 1v1a1 1 0 01-1 1H5a1 1 0 01-1-1v-1zM4 19a1 1 0 011-1h1a1 1 0 011 1v1a1 1 0 01-1 1H5a1 1 0 01-1-1v-1zM11 5a1 1 0 011-1h1a1 1 0 011 1v1a1 1 0 01-1 1h-1a1 1 0 01-1-1V5zM11 12a1 1 0 011-1h1a1 1 0 011 1v1a1 1 0 01-1 1h-1a1 1 0 01-1-1v-1zM11 19a1 1 0 011-1h1a1 1 0 011 1v1a1 1 0 01-1 1h-1a1 1 0 01-1-1v-1zM18 5a1 1 0 011-1h1a1 1 0 011 1v1a1 1 0 01-1 1h-1a1 1 0 01-1-1V5zM18 12a1 1 0 011-1h1a1 1 0 011 1v1a1 1 0 01-1 1h-1a1 1 0 01-1-1v-1zM18 19a1 1 0 011-1h1a1 1 0 011 1v1a1 1 0 01-1 1h-1a1 1 0 01-1-1v-1z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Category Pills -->
    <section class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <div class="category-pills-wrapper relative">
                    <div class="flex items-center gap-4 overflow-x-auto pb-2"
                        style="scrollbar-width: none; -ms-overflow-style: none;">
                        <style>
                            .flex.items-center.gap-4.overflow-x-auto::-webkit-scrollbar {
                                display: none;
                            }
                        </style>
                        @php
                            $selectedCategories = request('categories', []);
                            if (!is_array($selectedCategories)) {
                                $selectedCategories = [$selectedCategories];
                            }
                            $selectedCategories = array_filter($selectedCategories);
                        @endphp

                        <!-- All Products -->
                        <a href="{{ route('products.index') }}"
                            class="flex-shrink-0 flex flex-col items-center gap-2 group {{ empty($selectedCategories) ? 'selected-category' : '' }}">
                            <div
                                class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center group-hover:bg-gray-200 transition-colors {{ empty($selectedCategories) ? 'bg-pink-100 border-2 border-pink-300' : '' }}">
                                @if(empty($selectedCategories))
                                    <div class="w-8 h-8 bg-pink-400 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                @else
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                @endif
                            </div>
                            <span
                                class="text-sm font-medium text-gray-900 {{ empty($selectedCategories) ? 'text-pink-600' : '' }}">
                                All Products
                            </span>
                        </a>

                        <!-- Category Pills -->
                        @foreach($categories->take(5) as $category)
                            @php
                                $isSelected = in_array($category->id, $selectedCategories);
                                $newCategories = $selectedCategories;

                                if ($isSelected) {
                                    // Remove category if already selected
                                    $newCategories = array_diff($newCategories, [$category->id]);
                                } else {
                                    // Add category if not selected
                                    $newCategories[] = $category->id;
                                }

                                $newCategories = array_values(array_filter($newCategories));
                            @endphp

                            <button type="button" onclick="toggleCategory({{ $category->id }})"
                                class="flex-shrink-0 flex flex-col items-center gap-2 group category-pill {{ $isSelected ? 'selected-category' : '' }}"
                                data-category-id="{{ $category->id }}">
                                <div
                                    class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center group-hover:bg-gray-200 transition-colors {{ $isSelected ? 'bg-pink-100 border-2 border-pink-300' : '' }}">
                                    @if($isSelected)
                                        <div class="w-8 h-8 bg-pink-400 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                    @else
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    @endif
                                </div>
                                <span class="text-sm font-medium text-gray-900 {{ $isSelected ? 'text-pink-600' : '' }}">
                                    {{ $category->name }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Clear Selection Button (only show if categories are selected) -->
                @if(!empty($selectedCategories))
                    <div class="mt-4 text-center">
                        <a href="{{ route('products.index') }}"
                            class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Clear Selection ({{ count($selectedCategories) }} selected)
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <!-- Products Grid -->
    <section class="py-8">
        <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
            @if($products->count() > 0)
                <!-- Products Grid -->
                <div id="products-grid"
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-2 gap-2 mb-8 transition-all duration-300">
                    @foreach($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="flex justify-center">
                    {{ $products->links() }}
                </div>
            @else
                <!-- No Products Message -->
                <div class="text-center py-16">
                    <svg class="mx-auto h-24 w-24 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <h3 class="text-2xl font-semibold text-gray-900 mb-2">No Products Available</h3>
                    <p class="text-gray-600 mb-6">
                        We're currently updating our product catalog. Please check back soon.
                    </p>
                    <a href="{{ route('contact') }}"
                        class="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200">
                        Contact Us for Information
                        <svg class="ml-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3">
                            </path>
                        </svg>
                    </a>
                </div>
            @endif
        </div>
    </section>


@endsection

@push('styles')
    <style>
        .selected-category {
            position: relative;
        }

        .selected-category::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            background-color: #ec4899;
            border-radius: 50%;
        }

        /* Category pills horizontal scroll */
        .flex.items-center.gap-4.overflow-x-auto {
            min-height: 80px;
            scrollbar-width: none;
            /* Firefox */
            -ms-overflow-style: none;
            /* IE and Edge */
            scroll-behavior: smooth;
        }

        /* Hide scrollbar for webkit browsers */
        .flex.items-center.gap-4.overflow-x-auto::-webkit-scrollbar {
            display: none;
        }

        /* Ensure all category items don't shrink */
        .flex.items-center.gap-4.overflow-x-auto>* {
            flex-shrink: 0;
        }

        /* Grid Toggle Styles */
        .grid-toggle-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            background: transparent;
        }

        .grid-toggle-btn:hover {
            background-color: #f3f4f6;
        }

        .grid-toggle-btn.active {
            background-color: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* Grid transition animation */
        #products-grid {
            transition: all 0.3s ease;
        }

        /* Mobile specific styles */
        @media (max-width: 768px) {
            .grid-toggle-btn {
                width: 36px;
                height: 36px;
            }

            .grid-toggle-btn svg {
                width: 16px;
                height: 16px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize grid view from localStorage or default to 2 columns
            const savedGridView = localStorage.getItem('gridView') || '2';
            setGridView(parseInt(savedGridView));

            // Category pills are now using flex-wrap, no scroll needed
        });

        function setGridView(columns) {
            const grid = document.getElementById('products-grid');

            // Desktop buttons
            const btn2 = document.getElementById('grid-2-btn');
            const btn4 = document.getElementById('grid-4-btn');

            // Mobile buttons
            const btn1 = document.getElementById('grid-1-btn');
            const btn2Mobile = document.getElementById('grid-2-mobile-btn');

            if (!grid) return;

            // Remove all grid classes
            grid.className = grid.className.replace(/grid-cols-\d+/g, '');
            grid.className = grid.className.replace(/md:grid-cols-\d+/g, '');
            grid.className = grid.className.replace(/lg:grid-cols-\d+/g, '');
            grid.className = grid.className.replace(/xl:grid-cols-\d+/g, '');

            // Reset all button styles
            [btn1, btn2Mobile, btn2, btn4].forEach(btn => {
                if (btn) {
                    btn.classList.remove('bg-white', 'shadow-sm');
                    btn.classList.add('hover:bg-gray-200');
                }
            });

            if (columns === 1) {
                // 1 column layout (mobile only)
                grid.classList.add('grid-cols-1', 'md:grid-cols-2', 'lg:grid-cols-2', 'xl:grid-cols-2');
                if (btn1) {
                    btn1.classList.add('bg-white', 'shadow-sm');
                    btn1.classList.remove('hover:bg-gray-200');
                }
            } else if (columns === 2) {
                // 2 column layout
                grid.classList.add('grid-cols-2', 'md:grid-cols-2', 'lg:grid-cols-2', 'xl:grid-cols-2');
                if (btn2) {
                    btn2.classList.add('bg-white', 'shadow-sm');
                    btn2.classList.remove('hover:bg-gray-200');
                }
                if (btn2Mobile) {
                    btn2Mobile.classList.add('bg-white', 'shadow-sm');
                    btn2Mobile.classList.remove('hover:bg-gray-200');
                }
            } else {
                // 4 column layout (desktop only)
                grid.classList.add('grid-cols-2', 'md:grid-cols-2', 'lg:grid-cols-3', 'xl:grid-cols-4');
                if (btn4) {
                    btn4.classList.add('bg-white', 'shadow-sm');
                    btn4.classList.remove('hover:bg-gray-200');
                }
            }

            // Save preference
            localStorage.setItem('gridView', columns.toString());
        }

        function toggleCategory(categoryId) {
            // Get current URL parameters
            const url = new URL(window.location);
            let categories = url.searchParams.getAll('categories[]');

            // Convert to numbers for comparison
            categories = categories.map(id => parseInt(id));
            categoryId = parseInt(categoryId);

            // Toggle category
            if (categories.includes(categoryId)) {
                // Remove category
                categories = categories.filter(id => id !== categoryId);
            } else {
                // Add category
                categories.push(categoryId);
            }

            // Clear existing categories parameters
            url.searchParams.delete('categories[]');

            // Add new categories
            categories.forEach(id => {
                url.searchParams.append('categories[]', id);
            });

            // Remove page parameter to start from page 1
            url.searchParams.delete('page');

            // Navigate to new URL
            window.location.href = url.toString();
        }
    </script>
@endpush
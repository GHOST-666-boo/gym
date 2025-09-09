@extends('layouts.admin')

@section('title', 'Dashboard')

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-600 mt-1">Welcome to the Gym Machines admin panel</p>
        </div>
        <div class="text-sm text-gray-500">
            {{ now()->format('l, F j, Y') }}
        </div>
    </div>
@endsection

@section('content')
    <div class="p-6">
        <!-- Image Protection Status Banner -->
        @if(!$imageProtectionStatus['protection_enabled'] && !$imageProtectionStatus['watermark_enabled'])
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-medium text-yellow-800">Image Protection Disabled</h3>
                        <p class="text-sm text-yellow-700 mt-1">Your product images are not protected. Consider enabling image protection and watermarking for better security.</p>
                    </div>
                    <a href="{{ route('admin.settings.index') }}#watermark-tab" class="ml-4 bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        Enable Protection
                    </a>
                </div>
            </div>
        @endif

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <!-- Total Products -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Total Products</p>
                        <p class="text-3xl font-bold">{{ $stats['total_products'] }}</p>
                    </div>
                    <div class="bg-blue-400 bg-opacity-30 rounded-full p-3">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="{{ route('admin.products.index') }}" class="text-blue-100 hover:text-white text-sm font-medium">
                        View all products →
                    </a>
                </div>
            </div>

            <!-- Total Categories -->
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium">Total Categories</p>
                        <p class="text-3xl font-bold">{{ $stats['total_categories'] }}</p>
                    </div>
                    <div class="bg-green-400 bg-opacity-30 rounded-full p-3">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <span class="text-green-100 text-sm font-medium">
                        Categories system
                    </span>
                </div>
            </div>

            <!-- Low Stock Alert -->
            <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-100 text-sm font-medium">Low Stock Items</p>
                        <p class="text-3xl font-bold">{{ $stats['low_stock_products'] }}</p>
                    </div>
                    <div class="bg-yellow-400 bg-opacity-30 rounded-full p-3">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    @if($stats['low_stock_products'] > 0)
                        <span class="text-yellow-100 text-sm font-medium">
                            Needs attention
                        </span>
                    @else
                        <span class="text-yellow-100 text-sm font-medium">
                            All good!
                        </span>
                    @endif
                </div>
            </div>

            <!-- Out of Stock Alert -->
            <div class="bg-gradient-to-r from-red-500 to-red-600 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-red-100 text-sm font-medium">Out of Stock</p>
                        <p class="text-3xl font-bold">{{ $stats['out_of_stock_products'] }}</p>
                    </div>
                    <div class="bg-red-400 bg-opacity-30 rounded-full p-3">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    @if($stats['out_of_stock_products'] > 0)
                        <span class="text-red-100 text-sm font-medium">
                            Urgent attention needed
                        </span>
                    @else
                        <span class="text-red-100 text-sm font-medium">
                            All in stock
                        </span>
                    @endif
                </div>
            </div>

            <!-- Image Protection Status -->
            <div class="bg-gradient-to-r from-{{ $imageProtectionStatus['protection_enabled'] || $imageProtectionStatus['watermark_enabled'] ? 'green' : 'gray' }}-500 to-{{ $imageProtectionStatus['protection_enabled'] || $imageProtectionStatus['watermark_enabled'] ? 'green' : 'gray' }}-600 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-{{ $imageProtectionStatus['protection_enabled'] || $imageProtectionStatus['watermark_enabled'] ? 'green' : 'gray' }}-100 text-sm font-medium">Image Protection</p>
                        <p class="text-2xl font-bold">
                            @if($imageProtectionStatus['protection_enabled'] && $imageProtectionStatus['watermark_enabled'])
                                Full
                            @elseif($imageProtectionStatus['protection_enabled'] || $imageProtectionStatus['watermark_enabled'])
                                Partial
                            @else
                                Disabled
                            @endif
                        </p>
                    </div>
                    <div class="bg-{{ $imageProtectionStatus['protection_enabled'] || $imageProtectionStatus['watermark_enabled'] ? 'green' : 'gray' }}-400 bg-opacity-30 rounded-full p-3">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="{{ route('admin.settings.index') }}#watermark-tab" class="text-{{ $imageProtectionStatus['protection_enabled'] || $imageProtectionStatus['watermark_enabled'] ? 'green' : 'gray' }}-100 hover:text-white text-sm font-medium">
                        Manage Settings →
                    </a>
                </div>
            </div>
        </div>

        <!-- Analytics Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
            <!-- Today's Analytics -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Today's Activity</h3>
                    <a href="{{ route('admin.analytics.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        View Analytics →
                    </a>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Page Views</span>
                        <span class="font-semibold text-gray-900">{{ number_format($realTimeAnalytics['views_today']) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Unique Visitors</span>
                        <span class="font-semibold text-gray-900">{{ number_format($realTimeAnalytics['unique_visitors_today']) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Contact Forms</span>
                        <span class="font-semibold text-gray-900">{{ number_format($realTimeAnalytics['contacts_today']) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">This Hour</span>
                        <span class="font-semibold text-blue-600">{{ number_format($realTimeAnalytics['views_this_hour']) }}</span>
                    </div>
                </div>
            </div>

            <!-- Weekly Trends -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">7-Day Trends</h3>
                    @if(isset($analytics['trends']['views_trend']))
                        <span class="text-sm text-{{ $analytics['trends']['views_trend']['direction'] === 'up' ? 'green' : ($analytics['trends']['views_trend']['direction'] === 'down' ? 'red' : 'gray') }}-600 font-medium">
                            @if($analytics['trends']['views_trend']['direction'] === 'up')
                                ↗ +{{ $analytics['trends']['views_trend']['percentage'] }}%
                            @elseif($analytics['trends']['views_trend']['direction'] === 'down')
                                ↘ -{{ $analytics['trends']['views_trend']['percentage'] }}%
                            @else
                                → No change
                            @endif
                        </span>
                    @endif
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Total Views</span>
                        <span class="font-semibold text-gray-900">{{ number_format($analytics['overview']['total_views']) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Unique Views</span>
                        <span class="font-semibold text-gray-900">{{ number_format($analytics['overview']['unique_views']) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Contact Success Rate</span>
                        <span class="font-semibold text-green-600">{{ $analytics['contact_analytics']['success_rate'] }}%</span>
                    </div>
                </div>
            </div>

            <!-- Popular Products -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Top Products</h3>
                    <span class="text-sm text-gray-500">Last 7 days</span>
                </div>
                <div class="space-y-3">
                    @forelse($analytics['popular_products']['popular_products']->take(4) as $productView)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                @if($productView->product->primary_image_url)
                                    <img src="{{ $productView->product->primary_image_url }}" 
                                         alt="{{ $productView->product->name }}" 
                                         class="w-8 h-8 object-cover rounded mr-2">
                                @else
                                    <div class="w-8 h-8 bg-gray-200 rounded mr-2"></div>
                                @endif
                                <span class="text-sm text-gray-900 truncate max-w-32">{{ $productView->product->name }}</span>
                            </div>
                            <span class="text-sm font-semibold text-blue-600">{{ $productView->total_views }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 text-center py-2">No views yet</p>
                    @endforelse
                </div>
            </div>

            <!-- Image Protection Details -->
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Protection Status</h3>
                    <a href="{{ route('admin.settings.index') }}#watermark-tab" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        Configure →
                    </a>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Image Protection</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $imageProtectionStatus['protection_enabled'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $imageProtectionStatus['protection_enabled'] ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Watermarking</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $imageProtectionStatus['watermark_enabled'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $imageProtectionStatus['watermark_enabled'] ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                    @if($imageProtectionStatus['protection_enabled'])
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Right-click Block</span>
                            <span class="text-sm font-semibold {{ $imageProtectionStatus['protection_methods']['right_click'] ? 'text-green-600' : 'text-gray-400' }}">
                                {{ $imageProtectionStatus['protection_methods']['right_click'] ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Drag Protection</span>
                            <span class="text-sm font-semibold {{ $imageProtectionStatus['protection_methods']['drag_drop'] ? 'text-green-600' : 'text-gray-400' }}">
                                {{ $imageProtectionStatus['protection_methods']['drag_drop'] ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    @endif
                    @if($imageProtectionStatus['watermark_enabled'])
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Watermark Text</span>
                            <span class="text-sm font-semibold {{ $imageProtectionStatus['has_watermark_text'] ? 'text-green-600' : 'text-gray-400' }}">
                                {{ $imageProtectionStatus['has_watermark_text'] ? 'Set' : 'Not Set' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Watermark Logo</span>
                            <span class="text-sm font-semibold {{ $imageProtectionStatus['has_watermark_logo'] ? 'text-green-600' : 'text-gray-400' }}">
                                {{ $imageProtectionStatus['has_watermark_logo'] ? 'Uploaded' : 'None' }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        @if($stats['low_stock_alerts']->count() > 0)
            <div class="bg-white rounded-lg border border-yellow-200 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-yellow-200 bg-yellow-50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            <h2 class="text-lg font-semibold text-yellow-800">Low Stock Alerts</h2>
                        </div>
                        <span class="text-yellow-700 text-sm font-medium">{{ $stats['low_stock_alerts']->count() }} items need attention</span>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead class="bg-yellow-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-yellow-700 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-yellow-700 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-yellow-700 uppercase tracking-wider">Current Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-yellow-700 uppercase tracking-wider">Threshold</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-yellow-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-yellow-100">
                            @foreach($stats['low_stock_alerts'] as $product)
                                <tr class="hover:bg-yellow-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($product->image_path)
                                                <img class="h-10 w-10 rounded-lg object-cover mr-4" 
                                                     src="{{ asset('storage/' . $product->image_path) }}" 
                                                     alt="{{ $product->name }}">
                                            @else
                                                <div class="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center mr-4">
                                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                                <div class="text-sm text-gray-500">{{ Str::limit($product->short_description, 40) }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($product->category)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $product->category->name }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-sm">No category</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            {{ $product->stock_quantity }} units
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $product->low_stock_threshold }} units
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('admin.products.edit', $product) }}" 
                                           class="text-blue-600 hover:text-blue-900">Update Stock</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Recent Products -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Products</h2>
                    <a href="{{ route('admin.products.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        View all
                    </a>
                </div>
            </div>
            
            @if($stats['recent_products']->count() > 0)
                <div class="overflow-x-auto">
                    <table class="admin-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($stats['recent_products'] as $product)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($product->image_path)
                                                <img class="h-10 w-10 rounded-lg object-cover mr-4" 
                                                     src="{{ asset('storage/' . $product->image_path) }}" 
                                                     alt="{{ $product->name }}">
                                            @else
                                                <div class="h-10 w-10 rounded-lg bg-gray-200 flex items-center justify-center mr-4">
                                                    <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                                <div class="text-sm text-gray-500">{{ Str::limit($product->short_description, 50) }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($product->category)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $product->category->name }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-sm">No category</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        ${{ number_format($product->price, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $product->created_at->diffForHumans() }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('admin.products.edit', $product) }}" 
                                           class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                        <a href="{{ route('products.show', $product) }}" 
                                           target="_blank"
                                           class="text-green-600 hover:text-green-900">View</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No products</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating your first product.</p>
                    <div class="mt-6">
                        <a href="{{ route('admin.products.create') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Product
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
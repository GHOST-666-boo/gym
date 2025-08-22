@extends('layouts.admin')

@section('title', 'Analytics Dashboard')

@section('header')
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Analytics Dashboard</h1>
            <p class="text-gray-600 mt-1">Comprehensive insights into your website performance</p>
        </div>
        <div class="flex items-center space-x-3">
            <!-- Period Selector -->
            <select id="period-selector" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="7days" {{ $period === '7days' ? 'selected' : '' }}>Last 7 Days</option>
                <option value="30days" {{ $period === '30days' ? 'selected' : '' }}>Last 30 Days</option>
                <option value="90days" {{ $period === '90days' ? 'selected' : '' }}>Last 90 Days</option>
                <option value="year" {{ $period === 'year' ? 'selected' : '' }}>Last Year</option>
            </select>
            
            <!-- Export Button -->
            <a href="{{ route('admin.analytics.export', ['period' => $period]) }}" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export CSV
            </a>
            
            <!-- Refresh Button -->
            <button id="refresh-analytics" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
        </div>
    </div>
@endsection

@section('content')
    <!-- Overview Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Views -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Views</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($analytics['overview']['total_views']) }}</p>
                    @if(isset($analytics['trends']['views_trend']))
                        <p class="text-sm text-{{ $analytics['trends']['views_trend']['direction'] === 'up' ? 'green' : ($analytics['trends']['views_trend']['direction'] === 'down' ? 'red' : 'gray') }}-600">
                            @if($analytics['trends']['views_trend']['direction'] === 'up')
                                ↗ +{{ $analytics['trends']['views_trend']['percentage'] }}%
                            @elseif($analytics['trends']['views_trend']['direction'] === 'down')
                                ↘ -{{ $analytics['trends']['views_trend']['percentage'] }}%
                            @else
                                → No change
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Unique Visitors -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Unique Visitors</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($analytics['overview']['unique_views']) }}</p>
                    <p class="text-sm text-gray-500">{{ round(($analytics['overview']['unique_views'] / max($analytics['overview']['total_views'], 1)) * 100, 1) }}% of total</p>
                </div>
            </div>
        </div>

        <!-- Contact Submissions -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Contact Submissions</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($analytics['contact_analytics']['total_submissions']) }}</p>
                    @if(isset($analytics['trends']['contacts_trend']))
                        <p class="text-sm text-{{ $analytics['trends']['contacts_trend']['direction'] === 'up' ? 'green' : ($analytics['trends']['contacts_trend']['direction'] === 'down' ? 'red' : 'gray') }}-600">
                            @if($analytics['trends']['contacts_trend']['direction'] === 'up')
                                ↗ +{{ $analytics['trends']['contacts_trend']['percentage'] }}%
                            @elseif($analytics['trends']['contacts_trend']['direction'] === 'down')
                                ↘ -{{ $analytics['trends']['contacts_trend']['percentage'] }}%
                            @else
                                → No change
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Success Rate -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Email Success Rate</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $analytics['contact_analytics']['success_rate'] }}%</p>
                    <p class="text-sm text-gray-500">{{ $analytics['contact_analytics']['successful_submissions'] }}/{{ $analytics['contact_analytics']['total_submissions'] }} sent</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Views Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Daily Views</h3>
                <div class="text-sm text-gray-500">
                    Total: {{ number_format($analytics['product_views']['total_period_views']) }}
                </div>
            </div>
            <div class="h-64">
                <canvas id="viewsChart"></canvas>
            </div>
        </div>

        <!-- Contact Submissions Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Daily Contact Submissions</h3>
                <div class="text-sm text-gray-500">
                    Total: {{ number_format($analytics['contact_analytics']['total_submissions']) }}
                </div>
            </div>
            <div class="h-64">
                <canvas id="contactsChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Popular Products -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Most Popular Products</h3>
            <div class="space-y-4">
                @forelse($analytics['popular_products']['popular_products'] as $productView)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            @if($productView->product->primary_image_url)
                                <img src="{{ $productView->product->primary_image_url }}" 
                                     alt="{{ $productView->product->name }}" 
                                     class="w-10 h-10 object-cover rounded-lg mr-3">
                            @else
                                <div class="w-10 h-10 bg-gray-300 rounded-lg mr-3 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                            @endif
                            <div>
                                <p class="font-medium text-gray-900">{{ $productView->product->name }}</p>
                                <p class="text-sm text-gray-500">${{ number_format($productView->product->price, 2) }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-900">{{ number_format($productView->total_views) }}</p>
                            <p class="text-sm text-gray-500">{{ number_format($productView->unique_views) }} unique</p>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No product views in this period</p>
                @endforelse
            </div>
        </div>

        <!-- Category Performance -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Category Performance</h3>
            <div class="space-y-4">
                @forelse($analytics['category_performance']['category_performance'] as $category)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900">{{ $category->category_name ?? 'Uncategorized' }}</p>
                            <p class="text-sm text-gray-500">{{ $category->products_viewed }} products viewed</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-900">{{ number_format($category->total_views) }}</p>
                            <p class="text-sm text-gray-500">{{ number_format($category->unique_views) }} unique</p>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No category data available</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Contact Submissions -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Contact Submissions</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($analytics['contact_analytics']['recent_contacts'] as $contact)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $contact->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $contact->email }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <div class="max-w-xs truncate">{{ $contact->message }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($contact->email_sent)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Sent
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Failed
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $contact->submitted_at->format('M j, Y g:i A') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                No contact submissions in this period
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Period selector change handler
    document.getElementById('period-selector').addEventListener('change', function() {
        const period = this.value;
        window.location.href = `{{ route('admin.analytics.index') }}?period=${period}`;
    });

    // Refresh button handler
    document.getElementById('refresh-analytics').addEventListener('click', function() {
        window.location.reload();
    });

    // Views Chart
    const viewsCtx = document.getElementById('viewsChart').getContext('2d');
    const viewsData = @json($analytics['product_views']['daily_views']);
    
    new Chart(viewsCtx, {
        type: 'line',
        data: {
            labels: viewsData.map(item => new Date(item.date).toLocaleDateString()),
            datasets: [{
                label: 'Total Views',
                data: viewsData.map(item => item.total_views),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.1
            }, {
                label: 'Unique Views',
                data: viewsData.map(item => item.unique_views),
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Contacts Chart
    const contactsCtx = document.getElementById('contactsChart').getContext('2d');
    const contactsData = @json($analytics['contact_analytics']['daily_contacts']);
    
    new Chart(contactsCtx, {
        type: 'bar',
        data: {
            labels: contactsData.map(item => new Date(item.date).toLocaleDateString()),
            datasets: [{
                label: 'Total Submissions',
                data: contactsData.map(item => item.total_submissions),
                backgroundColor: 'rgba(147, 51, 234, 0.8)',
                borderColor: 'rgb(147, 51, 234)',
                borderWidth: 1
            }, {
                label: 'Successful',
                data: contactsData.map(item => item.successful_submissions),
                backgroundColor: 'rgba(16, 185, 129, 0.8)',
                borderColor: 'rgb(16, 185, 129)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
@endpush
@extends('layouts.admin')

@section('title', 'Cache Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Cache Management</h1>
                <div>
                    <button type="button" class="btn btn-outline-primary" onclick="refreshStats()">
                        <i class="fas fa-sync-alt"></i> Refresh Stats
                    </button>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Cache Statistics -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-bar"></i> Cache Statistics
                            </h5>
                        </div>
                        <div class="card-body">
                            @if(isset($stats['error']))
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    {{ $stats['error'] }}
                                </div>
                            @else
                                <div class="row">
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card bg-primary text-white">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <h6 class="card-title">Hit Rate</h6>
                                                        <h4 class="mb-0">{{ $stats['hit_rate'] ?? 'N/A' }}</h4>
                                                    </div>
                                                    <div class="align-self-center">
                                                        <i class="fas fa-bullseye fa-2x"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card bg-success text-white">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <h6 class="card-title">Memory Used</h6>
                                                        <h4 class="mb-0">{{ $stats['used_memory'] ?? 'N/A' }}</h4>
                                                    </div>
                                                    <div class="align-self-center">
                                                        <i class="fas fa-memory fa-2x"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card bg-info text-white">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <h6 class="card-title">Redis Version</h6>
                                                        <h4 class="mb-0">{{ $stats['redis_version'] ?? 'N/A' }}</h4>
                                                    </div>
                                                    <div class="align-self-center">
                                                        <i class="fas fa-server fa-2x"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card bg-warning text-white">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <h6 class="card-title">Connected Clients</h6>
                                                        <h4 class="mb-0">{{ $stats['connected_clients'] ?? 'N/A' }}</h4>
                                                    </div>
                                                    <div class="align-self-center">
                                                        <i class="fas fa-users fa-2x"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>Keyspace Hits:</strong></td>
                                                <td>{{ number_format($stats['keyspace_hits'] ?? 0) }}</td>
                                            </tr>
                                            <tr>
                                                <td><strong>Keyspace Misses:</strong></td>
                                                <td>{{ number_format($stats['keyspace_misses'] ?? 0) }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <table class="table table-sm">
                                            <tr>
                                                <td><strong>Total Commands:</strong></td>
                                                <td>{{ number_format($stats['total_commands_processed'] ?? 0) }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cache Management Actions -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-trash-alt"></i> Clear Cache
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Clear different types of cached data.</p>
                            
                            <!-- Clear All -->
                            <form action="{{ route('admin.cache.clear-all') }}" method="POST" class="mb-3">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm" 
                                        onclick="return confirm('Are you sure you want to clear all caches?')">
                                    <i class="fas fa-trash-alt"></i> Clear All Caches
                                </button>
                            </form>

                            <!-- Clear by Tags -->
                            <form action="{{ route('admin.cache.clear-tags') }}" method="POST" class="mb-3">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label">Select cache types to clear:</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="tags[]" value="products" id="tag-products">
                                    <label class="form-check-label" for="tag-products">Products</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="tags[]" value="categories" id="tag-categories">
                                    <label class="form-check-label" for="tag-categories">Categories</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="tags[]" value="analytics" id="tag-analytics">
                                    <label class="form-check-label" for="tag-analytics">Analytics</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="tags[]" value="static" id="tag-static">
                                    <label class="form-check-label" for="tag-static">Static</label>
                                </div>
                                <div class="mt-2">
                                    <button type="submit" class="btn btn-warning btn-sm">
                                        <i class="fas fa-tags"></i> Clear Selected
                                    </button>
                                </div>
                            </form>

                            <!-- Clear Full-Page Cache -->
                            <form action="{{ route('admin.cache.clear-full-page') }}" method="POST" class="mb-3">
                                @csrf
                                <button type="submit" class="btn btn-info btn-sm">
                                    <i class="fas fa-file-alt"></i> Clear Full-Page Cache
                                </button>
                            </form>

                            <!-- Clear Image Cache -->
                            <form action="{{ route('admin.cache.clear-images') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-images"></i> Clear Image Cache
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-fire"></i> Warm Up Cache
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Pre-populate caches with frequently accessed data to improve performance.</p>
                            
                            <form action="{{ route('admin.cache.warm-up') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-fire"></i> Warm Up All Caches
                                </button>
                            </form>

                            <div class="mt-3">
                                <small class="text-muted">
                                    This will pre-load:
                                    <ul class="mt-2 mb-0">
                                        <li>Featured products</li>
                                        <li>Popular products</li>
                                        <li>Category listings</li>
                                        <li>Product aggregations</li>
                                        <li>Sitemap data</li>
                                    </ul>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cache Information -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle"></i> Cache Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Cache Types</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Products:</strong> Product listings, featured products, related products</li>
                                        <li><strong>Categories:</strong> Category listings and counts</li>
                                        <li><strong>Analytics:</strong> Popular products, view statistics</li>
                                        <li><strong>Static:</strong> Sitemap, static pages</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Cache Durations</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Short:</strong> 15 minutes (frequently changing data)</li>
                                        <li><strong>Medium:</strong> 1 hour (moderately changing data)</li>
                                        <li><strong>Long:</strong> 24 hours (rarely changing data)</li>
                                        <li><strong>Static:</strong> 1 week (static content)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function refreshStats() {
    fetch('{{ route("admin.cache.stats") }}')
        .then(response => response.json())
        .then(data => {
            // Reload the page to show updated stats
            location.reload();
        })
        .catch(error => {
            console.error('Error refreshing stats:', error);
            alert('Failed to refresh statistics');
        });
}
</script>
@endsection
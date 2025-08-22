@extends('layouts.admin')

@section('title', 'Newsletter Subscribers')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Newsletter Subscribers</h1>
            <p class="text-muted">Manage your newsletter subscriber list</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.newsletter.export', request()->query()) }}" 
               class="btn btn-outline-success btn-sm">
                <i class="fas fa-download"></i> Export CSV
            </a>
            <a href="{{ route('admin.newsletter.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add Subscriber
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Subscribers
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['total']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active Subscribers
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['active']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Inactive Subscribers
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['inactive']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-times fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                New This Month
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($stats['recent']) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-plus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filters & Search</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.newsletter.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" 
                           class="form-control" 
                           id="search" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Search by email or name...">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">All Subscribers</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active Only</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive Only</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="sort" class="form-label">Sort By</label>
                    <select class="form-control" id="sort" name="sort">
                        <option value="subscribed_at" {{ request('sort') === 'subscribed_at' ? 'selected' : '' }}>Subscription Date</option>
                        <option value="email" {{ request('sort') === 'email' ? 'selected' : '' }}>Email</option>
                        <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Name</option>
                        <option value="unsubscribed_at" {{ request('sort') === 'unsubscribed_at' ? 'selected' : '' }}>Unsubscribe Date</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="{{ route('admin.newsletter.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Subscribers Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Subscribers List</h6>
            @if($subscribers->count() > 0)
                <div class="dropdown">
                    <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="bulkActions" data-toggle="dropdown">
                        Bulk Actions
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="#" onclick="submitBulkAction('activate')">
                            <i class="fas fa-check text-success"></i> Activate Selected
                        </a>
                        <a class="dropdown-item" href="#" onclick="submitBulkAction('deactivate')">
                            <i class="fas fa-times text-warning"></i> Deactivate Selected
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="#" onclick="submitBulkAction('delete')">
                            <i class="fas fa-trash"></i> Delete Selected
                        </a>
                    </div>
                </div>
            @endif
        </div>
        <div class="card-body">
            @if($subscribers->count() > 0)
                <form id="bulk-action-form" method="POST" action="{{ route('admin.newsletter.bulk-action') }}">
                    @csrf
                    <input type="hidden" name="action" id="bulk-action-input">
                    
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="30">
                                        <input type="checkbox" id="select-all">
                                    </th>
                                    <th>Email</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Subscribed</th>
                                    <th>Unsubscribed</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subscribers as $subscriber)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="subscribers[]" value="{{ $subscriber->id }}" class="subscriber-checkbox">
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.newsletter.show', ['newsletter' => $subscriber]) }}" class="text-decoration-none">
                                                {{ $subscriber->email }}
                                            </a>
                                        </td>
                                        <td>{{ $subscriber->name ?: '-' }}</td>
                                        <td>
                                            @if($subscriber->is_active)
                                                <span class="badge badge-success">Active</span>
                                            @else
                                                <span class="badge badge-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $subscriber->subscribed_at->format('M j, Y') }}
                                                <br>
                                                {{ $subscriber->subscribed_at->format('g:i A') }}
                                            </small>
                                        </td>
                                        <td>
                                            @if($subscriber->unsubscribed_at)
                                                <small class="text-muted">
                                                    {{ $subscriber->unsubscribed_at->format('M j, Y') }}
                                                    <br>
                                                    {{ $subscriber->unsubscribed_at->format('g:i A') }}
                                                </small>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.newsletter.show', ['newsletter' => $subscriber]) }}" 
                                                   class="btn btn-info btn-sm" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('admin.newsletter.edit', ['newsletter' => $subscriber]) }}" 
                                                   class="btn btn-warning btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" 
                                                      action="{{ route('admin.newsletter.destroy', ['newsletter' => $subscriber]) }}" 
                                                      class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete this subscriber?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </form>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted">
                        Showing {{ $subscribers->firstItem() }} to {{ $subscribers->lastItem() }} of {{ $subscribers->total() }} results
                    </div>
                    {{ $subscribers->links() }}
                </div>
            @else
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-gray-300 mb-3"></i>
                    <h5 class="text-gray-600">No subscribers found</h5>
                    <p class="text-muted">
                        @if(request()->hasAny(['search', 'status']))
                            Try adjusting your search criteria or <a href="{{ route('admin.newsletter.index') }}">view all subscribers</a>.
                        @else
                            When visitors subscribe to your newsletter, they'll appear here.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all checkbox functionality
    const selectAllCheckbox = document.getElementById('select-all');
    const subscriberCheckboxes = document.querySelectorAll('.subscriber-checkbox');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            subscriberCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
    
    // Update select all checkbox when individual checkboxes change
    subscriberCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedCount = document.querySelectorAll('.subscriber-checkbox:checked').length;
            selectAllCheckbox.checked = checkedCount === subscriberCheckboxes.length;
            selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < subscriberCheckboxes.length;
        });
    });
});

function submitBulkAction(action) {
    const checkedBoxes = document.querySelectorAll('.subscriber-checkbox:checked');
    
    if (checkedBoxes.length === 0) {
        alert('Please select at least one subscriber.');
        return;
    }
    
    let confirmMessage = '';
    switch (action) {
        case 'activate':
            confirmMessage = `Are you sure you want to activate ${checkedBoxes.length} subscriber(s)?`;
            break;
        case 'deactivate':
            confirmMessage = `Are you sure you want to deactivate ${checkedBoxes.length} subscriber(s)?`;
            break;
        case 'delete':
            confirmMessage = `Are you sure you want to delete ${checkedBoxes.length} subscriber(s)? This action cannot be undone.`;
            break;
    }
    
    if (confirm(confirmMessage)) {
        document.getElementById('bulk-action-input').value = action;
        document.getElementById('bulk-action-form').submit();
    }
}
</script>
@endpush
@endsection
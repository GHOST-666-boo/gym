@extends('layouts.admin')

@section('title', 'Newsletter Subscriber Details')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Subscriber Details</h1>
            <p class="text-muted">{{ $subscriber->email }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.newsletter.edit', ['newsletter' => $subscriber]) }}" class="btn btn-warning btn-sm">
                <i class="fas fa-edit"></i> Edit
            </a>
            <a href="{{ route('admin.newsletter.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Subscriber Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Subscriber Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Email Address:</label>
                                <p class="mb-0">{{ $subscriber->email }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Name:</label>
                                <p class="mb-0">{{ $subscriber->name ?: 'Not provided' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Status:</label>
                                <p class="mb-0">
                                    @if($subscriber->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">Subscriber ID:</label>
                                <p class="mb-0">#{{ $subscriber->id }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subscription Timeline -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Subscription Timeline</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Subscribed</h6>
                                <p class="timeline-text">
                                    {{ $subscriber->subscribed_at->format('F j, Y \a\t g:i A') }}
                                    <small class="text-muted">({{ $subscriber->subscribed_at->diffForHumans() }})</small>
                                </p>
                            </div>
                        </div>

                        @if($subscriber->unsubscribed_at)
                            <div class="timeline-item">
                                <div class="timeline-marker bg-warning"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Unsubscribed</h6>
                                    <p class="timeline-text">
                                        {{ $subscriber->unsubscribed_at->format('F j, Y \a\t g:i A') }}
                                        <small class="text-muted">({{ $subscriber->unsubscribed_at->diffForHumans() }})</small>
                                    </p>
                                </div>
                            </div>
                        @endif

                        <div class="timeline-item">
                            <div class="timeline-marker bg-info"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Last Updated</h6>
                                <p class="timeline-text">
                                    {{ $subscriber->updated_at->format('F j, Y \a\t g:i A') }}
                                    <small class="text-muted">({{ $subscriber->updated_at->diffForHumans() }})</small>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($subscriber->is_active)
                            <form method="POST" action="{{ route('admin.newsletter.update', ['newsletter' => $subscriber]) }}" class="d-inline">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="email" value="{{ $subscriber->email }}">
                                <input type="hidden" name="name" value="{{ $subscriber->name }}">
                                <input type="hidden" name="is_active" value="0">
                                <button type="submit" class="btn btn-warning btn-sm w-100" 
                                        onclick="return confirm('Are you sure you want to deactivate this subscriber?')">
                                    <i class="fas fa-user-times"></i> Deactivate Subscription
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.newsletter.update', ['newsletter' => $subscriber]) }}" class="d-inline">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="email" value="{{ $subscriber->email }}">
                                <input type="hidden" name="name" value="{{ $subscriber->name }}">
                                <input type="hidden" name="is_active" value="1">
                                <button type="submit" class="btn btn-success btn-sm w-100">
                                    <i class="fas fa-user-check"></i> Reactivate Subscription
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('admin.newsletter.edit', ['newsletter' => $subscriber]) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-edit"></i> Edit Details
                        </a>

                        <form method="POST" action="{{ route('admin.newsletter.destroy', ['newsletter' => $subscriber]) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm w-100" 
                                    onclick="return confirm('Are you sure you want to permanently delete this subscriber? This action cannot be undone.')">
                                <i class="fas fa-trash"></i> Delete Subscriber
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Subscriber Statistics -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Subscriber Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-12 mb-3">
                            <div class="border-bottom pb-2">
                                <h5 class="mb-0">{{ $subscriber->subscribed_at->diffInDays(now()) }}</h5>
                                <small class="text-muted">Days Subscribed</small>
                            </div>
                        </div>
                        @if($subscriber->unsubscribed_at)
                            <div class="col-12">
                                <div>
                                    <h5 class="mb-0">{{ $subscriber->subscribed_at->diffInDays($subscriber->unsubscribed_at) }}</h5>
                                    <small class="text-muted">Days Active</small>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Technical Details -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-secondary">Technical Details</h6>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        <strong>Unsubscribe Token:</strong><br>
                        <code class="text-break">{{ $subscriber->unsubscribe_token }}</code>
                    </small>
                    <hr>
                    <small class="text-muted">
                        <strong>Unsubscribe URL:</strong><br>
                        <a href="{{ $subscriber->unsubscribe_url }}" target="_blank" class="text-break">
                            {{ $subscriber->unsubscribe_url }}
                        </a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e3e6f0;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e3e6f0;
}

.timeline-content {
    background: #f8f9fc;
    padding: 15px;
    border-radius: 5px;
    border-left: 3px solid #e3e6f0;
}

.timeline-title {
    margin-bottom: 5px;
    font-weight: 600;
}

.timeline-text {
    margin-bottom: 0;
    color: #5a5c69;
}
</style>
@endpush
@endsection
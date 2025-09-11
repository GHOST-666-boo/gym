@extends('layouts.admin')

@section('title', 'Edit Newsletter Subscriber')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Edit Newsletter Subscriber</h1>
            <p class="text-muted">Update subscriber information</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.newsletter.show', ['newsletter' => $subscriber]) }}" class="btn btn-info btn-sm">
                <i class="fas fa-eye"></i> View Details
            </a>
            <a href="{{ route('admin.newsletter.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Subscriber Information</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.newsletter.update', ['newsletter' => $subscriber]) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $subscriber->email) }}" 
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                The subscriber's email address. Must be unique.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $subscriber->name) }}">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Optional. The subscriber's name for personalization.
                            </small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Subscription Status</label>
                            <div class="form-check">
                                <input class="form-check-input @error('is_active') is-invalid @enderror" 
                                       type="checkbox" 
                                       id="is_active" 
                                       name="is_active" 
                                       value="1"
                                       {{ old('is_active', $subscriber->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active subscription
                                </label>
                            </div>
                            @error('is_active')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Uncheck to deactivate the subscription. The subscriber will not receive future newsletters.
                            </small>
                        </div>

                        <div class="form-group">
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Subscriber
                                </button>
                                <div>
                                    <a href="{{ route('admin.newsletter.show', ['newsletter' => $subscriber]) }}" class="btn btn-outline-secondary me-2">
                                        Cancel
                                    </a>
                                    <form method="POST" 
                                          action="{{ route('admin.newsletter.destroy', ['newsletter' => $subscriber]) }}" 
                                          class="d-inline"
                                          onsubmit="return confirm('Are you sure you want to delete this subscriber? This action cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Subscriber Summary -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Current Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Current Status:</strong>
                        @if($subscriber->is_active)
                            <span class="badge badge-success ml-2">Active</span>
                        @else
                            <span class="badge badge-secondary ml-2">Inactive</span>
                        @endif
                    </div>
                    
                    <div class="mb-3">
                        <strong>Subscribed:</strong>
                        <br>
                        <small class="text-muted">
                            {{ $subscriber->subscribed_at->format('F j, Y \a\t g:i A') }}
                            <br>
                            ({{ $subscriber->subscribed_at->diffForHumans() }})
                        </small>
                    </div>

                    @if($subscriber->unsubscribed_at)
                        <div class="mb-3">
                            <strong>Unsubscribed:</strong>
                            <br>
                            <small class="text-muted">
                                {{ $subscriber->unsubscribed_at->format('F j, Y \a\t g:i A') }}
                                <br>
                                ({{ $subscriber->unsubscribed_at->diffForHumans() }})
                            </small>
                        </div>
                    @endif

                    <div class="mb-3">
                        <strong>Last Updated:</strong>
                        <br>
                        <small class="text-muted">
                            {{ $subscriber->updated_at->format('F j, Y \a\t g:i A') }}
                            <br>
                            ({{ $subscriber->updated_at->diffForHumans() }})
                        </small>
                    </div>
                </div>
            </div>

            <!-- Important Notes -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">Important Notes</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Email Changes:</strong> Changing the email address will affect the subscriber's ability to unsubscribe using their existing unsubscribe link.
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Status Changes:</strong> Deactivating a subscription will prevent the subscriber from receiving future newsletters, but they can still resubscribe through the website.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
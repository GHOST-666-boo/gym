@extends('layouts.admin')

@section('title', 'Add Newsletter Subscriber')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Add Newsletter Subscriber</h1>
            <p class="text-muted">Manually add a new subscriber to the newsletter</p>
        </div>
        <a href="{{ route('admin.newsletter.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Subscribers
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Subscriber Information</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.newsletter.store') }}">
                        @csrf
                        
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
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
                                   value="{{ old('name') }}">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Optional. The subscriber's name for personalization.
                            </small>
                        </div>

                        <div class="form-group">
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Add Subscriber
                                </button>
                                <a href="{{ route('admin.newsletter.index') }}" class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Information</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> When you add a subscriber manually, they will be automatically marked as active and will receive future newsletters.
                    </div>
                    
                    <h6 class="font-weight-bold">Automatic Fields:</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success"></i> Subscription date: Current timestamp</li>
                        <li><i class="fas fa-check text-success"></i> Status: Active</li>
                        <li><i class="fas fa-check text-success"></i> Unsubscribe token: Auto-generated</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
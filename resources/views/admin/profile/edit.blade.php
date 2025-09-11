@extends('layouts.admin')

@section('title', 'Profile Settings')

@section('page-title', 'Profile Settings')

@section('content')
    <div class="max-w-4xl mx-auto p-6 space-y-6">
        <!-- Profile Information -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Profile Information</h3>
                <p class="mt-1 text-sm text-gray-600">Update your account's profile information and email address.</p>
            </div>
            <div class="p-6">
                @include('admin.profile.partials.update-profile-information-form')
            </div>
        </div>

        <!-- Update Password -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Update Password</h3>
                <p class="mt-1 text-sm text-gray-600">Ensure your account is using a long, random password to stay secure.</p>
            </div>
            <div class="p-6">
                @include('admin.profile.partials.update-password-form')
            </div>
        </div>

        <!-- Delete Account -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Delete Account</h3>
                <p class="mt-1 text-sm text-gray-600">Once your account is deleted, all of its resources and data will be permanently deleted.</p>
            </div>
            <div class="p-6">
                @include('admin.profile.partials.delete-user-form')
            </div>
        </div>
    </div>
@endsection
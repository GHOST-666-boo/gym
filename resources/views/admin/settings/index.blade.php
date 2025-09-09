@extends('layouts.admin')

@section('title', 'Site Settings')

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Site Settings</h1>
            <p class="text-gray-600 mt-1">Manage your website's configuration and appearance</p>
        </div>
    </div>
@endsection

@section('content')
<div class="p-6">
    <!-- Validation Errors (only show validation errors here, success/error handled by layout) -->
    
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h3 class="text-red-800 font-medium mb-2">Please fix the following errors:</h3>
                    <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6" id="settings-form">
        @csrf
        @method('PATCH')
        
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button type="button" 
                        class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                        data-tab="general">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    General
                </button>
                
                <button type="button" 
                        class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                        data-tab="contact">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    Contact
                </button>
                
                <button type="button" 
                        class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                        data-tab="social">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m-9 0h10m-10 0a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V6a2 2 0 00-2-2M9 12l2 2 4-4"></path>
                    </svg>
                    Social Media
                </button>
                
                <button type="button" 
                        class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                        data-tab="seo">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    SEO
                </button>
                
                <button type="button" 
                        class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                        data-tab="watermark">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    Image Protection
                </button>
                
                <button type="button" 
                        class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                        data-tab="advanced">
                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                    </svg>
                    Advanced
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="mt-6">
            <!-- General Settings Tab -->
            <div id="general-tab" class="tab-content">
                @include('admin.settings.partials.general')
            </div>

            <!-- Contact Information Tab -->
            <div id="contact-tab" class="tab-content hidden">
                @include('admin.settings.partials.contact')
            </div>

            <!-- Social Media Tab -->
            <div id="social-tab" class="tab-content hidden">
                @include('admin.settings.partials.social')
            </div>

            <!-- SEO Settings Tab -->
            <div id="seo-tab" class="tab-content hidden">
                @include('admin.settings.partials.seo')
            </div>

            <!-- Image Protection & Watermark Tab -->
            <div id="watermark-tab" class="tab-content hidden">
                @include('admin.settings.partials.watermark')
            </div>

            <!-- Advanced Settings Tab -->
            <div id="advanced-tab" class="tab-content hidden">
                @include('admin.settings.partials.advanced')
            </div>
        </div>

        <!-- System Status Panel -->
        <div id="system-status-panel" class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-md font-medium text-gray-900">System Status</h3>
                <button type="button" 
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                        onclick="refreshSystemStatus()">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>
            </div>
            <div id="status-content" class="text-sm text-gray-600">
                Loading system status...
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-between items-center pt-6 border-t border-gray-200">
            <div class="flex items-center space-x-4">
                <button type="button" 
                        class="text-gray-600 hover:text-gray-800 text-sm font-medium"
                        onclick="validateSettings()">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Validate Settings
                </button>
                <div id="validation-status" class="text-sm"></div>
            </div>
            <button type="submit" 
                    id="save-settings-btn"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Save Settings
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    // Set first tab as active by default
    if (tabButtons.length > 0) {
        tabButtons[0].classList.add('border-blue-500', 'text-blue-600');
        tabButtons[0].classList.remove('border-transparent', 'text-gray-500');
    }
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active classes from all tabs
            tabButtons.forEach(btn => {
                btn.classList.remove('border-blue-500', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Add active classes to clicked tab
            this.classList.add('border-blue-500', 'text-blue-600');
            this.classList.remove('border-transparent', 'text-gray-500');
            
            // Hide all tab contents
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });
            
            // Show target tab content
            const targetContent = document.getElementById(targetTab + '-tab');
            if (targetContent) {
                targetContent.classList.remove('hidden');
            }
        });
    });
    
    // File upload functionality
    function setupFileUpload() {
        // Logo upload
        const logoInput = document.getElementById('logo-upload');
        const logoBtn = document.getElementById('upload-logo-btn');
        const logoPreview = document.getElementById('logo-preview');
        const logoStatus = document.getElementById('logo-upload-status');
        const currentLogoDisplay = document.getElementById('current-logo-display');
        
        if (logoInput && logoBtn) {
            logoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    logoBtn.disabled = false;
                    
                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        logoPreview.src = e.target.result;
                        logoPreview.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                } else {
                    logoBtn.disabled = true;
                    logoPreview.classList.add('hidden');
                }
            });
            
            logoBtn.addEventListener('click', function() {
                const file = logoInput.files[0];
                if (!file) return;
                
                const formData = new FormData();
                formData.append('logo', file);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                
                logoBtn.disabled = true;
                logoBtn.textContent = 'Uploading...';
                logoStatus.className = 'text-blue-600';
                logoStatus.textContent = 'Uploading logo...';
                logoStatus.classList.remove('hidden');
                
                fetch('{{ route("admin.settings.upload-logo") }}', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        logoStatus.className = 'text-green-600';
                        logoStatus.textContent = 'Logo uploaded successfully!';
                        
                        // Update current logo display
                        currentLogoDisplay.innerHTML = `
                            <div class="flex items-center space-x-3">
                                <img src="${data.url}" alt="Current Logo" class="h-12 w-auto object-contain border border-gray-200 rounded">
                                <span class="text-sm text-gray-600">Current logo</span>
                            </div>
                        `;
                        
                        // Reset form
                        logoInput.value = '';
                        logoPreview.classList.add('hidden');
                        
                        // Show success message
                        setTimeout(() => {
                            logoStatus.classList.add('hidden');
                        }, 3000);
                    } else {
                        logoStatus.className = 'text-red-600';
                        logoStatus.textContent = data.message || 'Upload failed';
                    }
                })
                .catch(error => {
                    logoStatus.className = 'text-red-600';
                    logoStatus.textContent = 'Upload failed. Please try again.';
                })
                .finally(() => {
                    logoBtn.disabled = false;
                    logoBtn.textContent = 'Upload Logo';
                });
            });
        }
        
        // Watermark logo upload
        const watermarkLogoInput = document.getElementById('watermark-logo-upload');
        const watermarkLogoBtn = document.getElementById('upload-watermark-logo-btn');
        const watermarkLogoPreview = document.getElementById('watermark-logo-preview');
        const watermarkLogoStatus = document.getElementById('watermark-logo-upload-status');
        const currentWatermarkLogoDisplay = document.getElementById('current-watermark-logo-display');
        
        if (watermarkLogoInput && watermarkLogoBtn) {
            watermarkLogoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    watermarkLogoBtn.disabled = false;
                    
                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        watermarkLogoPreview.src = e.target.result;
                        watermarkLogoPreview.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                } else {
                    watermarkLogoBtn.disabled = true;
                    watermarkLogoPreview.classList.add('hidden');
                }
            });
            
            watermarkLogoBtn.addEventListener('click', function() {
                const file = watermarkLogoInput.files[0];
                if (!file) return;
                
                const formData = new FormData();
                formData.append('watermark_logo', file);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                
                watermarkLogoBtn.disabled = true;
                watermarkLogoBtn.textContent = 'Uploading...';
                watermarkLogoStatus.className = 'text-blue-600';
                watermarkLogoStatus.textContent = 'Uploading watermark logo...';
                watermarkLogoStatus.classList.remove('hidden');
                
                fetch('{{ route("admin.settings.upload-watermark-logo") }}', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        watermarkLogoStatus.className = 'text-green-600';
                        watermarkLogoStatus.textContent = 'Watermark logo uploaded successfully!';
                        
                        // Update current watermark logo display
                        currentWatermarkLogoDisplay.innerHTML = `
                            <div class="flex items-center space-x-3">
                                <img src="${data.url}" alt="Current Watermark Logo" class="h-12 w-auto object-contain border border-gray-200 rounded bg-gray-100 p-2">
                                <span class="text-sm text-gray-600">Current watermark logo</span>
                            </div>
                        `;
                        
                        // Reset form
                        watermarkLogoInput.value = '';
                        watermarkLogoPreview.classList.add('hidden');
                        
                        // Show success message
                        setTimeout(() => {
                            watermarkLogoStatus.classList.add('hidden');
                        }, 3000);
                    } else {
                        watermarkLogoStatus.className = 'text-red-600';
                        watermarkLogoStatus.textContent = data.message || 'Upload failed';
                    }
                })
                .catch(error => {
                    watermarkLogoStatus.className = 'text-red-600';
                    watermarkLogoStatus.textContent = 'Upload failed. Please try again.';
                })
                .finally(() => {
                    watermarkLogoBtn.disabled = false;
                    watermarkLogoBtn.textContent = 'Upload Logo';
                });
            });
        }
        
        // Favicon upload
        const faviconInput = document.getElementById('favicon-upload');
        const faviconBtn = document.getElementById('upload-favicon-btn');
        const faviconPreview = document.getElementById('favicon-preview');
        const faviconStatus = document.getElementById('favicon-upload-status');
        const currentFaviconDisplay = document.getElementById('current-favicon-display');
        
        if (faviconInput && faviconBtn) {
            faviconInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    faviconBtn.disabled = false;
                    
                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        faviconPreview.src = e.target.result;
                        faviconPreview.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                } else {
                    faviconBtn.disabled = true;
                    faviconPreview.classList.add('hidden');
                }
            });
            
            faviconBtn.addEventListener('click', function() {
                const file = faviconInput.files[0];
                if (!file) return;
                
                const formData = new FormData();
                formData.append('favicon', file);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                
                faviconBtn.disabled = true;
                faviconBtn.textContent = 'Uploading...';
                faviconStatus.className = 'text-blue-600';
                faviconStatus.textContent = 'Uploading favicon...';
                faviconStatus.classList.remove('hidden');
                
                fetch('{{ route("admin.settings.upload-favicon") }}', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        faviconStatus.className = 'text-green-600';
                        faviconStatus.textContent = 'Favicon uploaded successfully!';
                        
                        // Update current favicon display
                        currentFaviconDisplay.innerHTML = `
                            <div class="flex items-center space-x-3">
                                <img src="${data.url}" alt="Current Favicon" class="h-8 w-8 object-contain border border-gray-200 rounded">
                                <span class="text-sm text-gray-600">Current favicon</span>
                            </div>
                        `;
                        
                        // Reset form
                        faviconInput.value = '';
                        faviconPreview.classList.add('hidden');
                        
                        // Show success message
                        setTimeout(() => {
                            faviconStatus.classList.add('hidden');
                        }, 3000);
                    } else {
                        faviconStatus.className = 'text-red-600';
                        faviconStatus.textContent = data.message || 'Upload failed';
                    }
                })
                .catch(error => {
                    faviconStatus.className = 'text-red-600';
                    faviconStatus.textContent = 'Upload failed. Please try again.';
                })
                .finally(() => {
                    faviconBtn.disabled = false;
                    faviconBtn.textContent = 'Upload Favicon';
                });
            });
        }
    }
    
    // Setup file uploads
    setupFileUpload();
    
    // Load initial system status
    refreshSystemStatus();
    
    // Debug form submission
    const settingsForm = document.getElementById('settings-form');
    const saveBtn = document.getElementById('save-settings-btn');
    
    if (settingsForm && saveBtn) {
        // Add form submit event listener for debugging
        settingsForm.addEventListener('submit', function(e) {
            console.log('Form submission started...');
            console.log('Form action:', this.action);
            console.log('Form method:', this.method);
            

            
            // Check CSRF token
            const csrfToken = this.querySelector('input[name="_token"]');
            console.log('CSRF token present:', !!csrfToken);
            if (csrfToken) {
                console.log('CSRF token value:', csrfToken.value);
            }
            
            // Check form data
            const formData = new FormData(this);
            console.log('Form data entries:');
            for (let [key, value] of formData.entries()) {
                console.log(`  ${key}: ${value}`);
            }
            
            // Disable button to prevent double submission
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<svg class="w-4 h-4 inline-block mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Saving...';
        });
        
        // Re-enable button if form submission fails
        window.addEventListener('pageshow', function() {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Save Settings';
        });
    }
});

// System status and validation functions
function refreshSystemStatus() {
    const statusContent = document.getElementById('status-content');
    statusContent.innerHTML = '<div class="flex items-center"><svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Checking system status...</div>';
    
    fetch('{{ route("admin.settings.test-protection") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySystemStatus(data);
            } else {
                statusContent.innerHTML = '<div class="text-red-600">Failed to load system status: ' + data.message + '</div>';
            }
        })
        .catch(error => {
            statusContent.innerHTML = '<div class="text-red-600">Error loading system status</div>';
        });
}

function displaySystemStatus(data) {
    const statusContent = document.getElementById('status-content');
    const tests = data.tests;
    const recommendations = data.recommendations;
    
    let html = '<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-3">';
    
    // System tests
    html += `<div class="flex items-center">
        <div class="w-3 h-3 rounded-full mr-2 ${tests.gd_extension || tests.imagick_extension ? 'bg-green-500' : 'bg-red-500'}"></div>
        <span class="text-xs">Image Processing</span>
    </div>`;
    
    html += `<div class="flex items-center">
        <div class="w-3 h-3 rounded-full mr-2 ${tests.storage_writable ? 'bg-green-500' : 'bg-red-500'}"></div>
        <span class="text-xs">Storage Writable</span>
    </div>`;
    
    html += `<div class="flex items-center">
        <div class="w-3 h-3 rounded-full mr-2 ${tests.watermark_cache_dir ? 'bg-green-500' : 'bg-yellow-500'}"></div>
        <span class="text-xs">Cache Directory</span>
    </div>`;
    
    html += `<div class="flex items-center">
        <div class="w-3 h-3 rounded-full mr-2 ${tests.protection_enabled ? 'bg-green-500' : 'bg-gray-400'}"></div>
        <span class="text-xs">Protection Active</span>
    </div>`;
    
    html += `<div class="flex items-center">
        <div class="w-3 h-3 rounded-full mr-2 ${tests.watermark_enabled ? 'bg-green-500' : 'bg-gray-400'}"></div>
        <span class="text-xs">Watermark Active</span>
    </div>`;
    
    html += `<div class="flex items-center">
        <div class="w-3 h-3 rounded-full mr-2 ${data.all_tests_passed ? 'bg-green-500' : 'bg-yellow-500'}"></div>
        <span class="text-xs">Overall Status</span>
    </div>`;
    
    html += '</div>';
    
    // Recommendations
    if (recommendations.length > 0) {
        html += '<div class="border-t border-gray-200 pt-3 mt-3">';
        html += '<div class="text-xs font-medium text-gray-700 mb-2">Recommendations:</div>';
        html += '<ul class="text-xs text-gray-600 space-y-1">';
        recommendations.forEach(rec => {
            html += `<li>• ${rec}</li>`;
        });
        html += '</ul></div>';
    }
    
    statusContent.innerHTML = html;
}

function validateSettings() {
    const validationStatus = document.getElementById('validation-status');
    validationStatus.innerHTML = '<span class="text-blue-600">Validating...</span>';
    
    fetch('{{ route("admin.settings.validation-summary") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayValidationSummary(data.summary);
            } else {
                validationStatus.innerHTML = '<span class="text-red-600">Validation failed</span>';
            }
        })
        .catch(error => {
            validationStatus.innerHTML = '<span class="text-red-600">Validation error</span>';
        });
}

function displayValidationSummary(summary) {
    const validationStatus = document.getElementById('validation-status');
    
    if (summary.is_valid) {
        validationStatus.innerHTML = '<span class="text-green-600">✓ Settings valid</span>';
    } else {
        const errorCount = Object.keys(summary.errors).length;
        validationStatus.innerHTML = `<span class="text-red-600">✗ ${errorCount} validation error(s)</span>`;
    }
    
    // Show warnings if any
    if (summary.warnings.length > 0) {
        setTimeout(() => {
            const warningHtml = summary.warnings.map(w => `<div class="text-yellow-600 text-xs mt-1">⚠ ${w}</div>`).join('');
            validationStatus.innerHTML += warningHtml;
        }, 1000);
    }
}
</script>
@endpush
@endsection
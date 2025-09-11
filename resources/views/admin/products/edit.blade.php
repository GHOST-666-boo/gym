@extends('layouts.admin')

@section('title', 'Edit Product')

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Product</h1>
            <p class="text-gray-600 mt-1">Update "{{ $product->name }}"</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('products.show', $product->slug) }}" 
               target="_blank"
               class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                View Product
            </a>
            <a href="{{ route('admin.products.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Products
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="p-6">
        <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Product Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Product Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Product Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name" 
                               value="{{ old('name', $product->name) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                               placeholder="Enter product name"
                               required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Price -->
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-2">
                            Price <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input type="number" 
                                   name="price" 
                                   id="price" 
                                   step="0.01"
                                   min="0"
                                   value="{{ old('price', $product->price) }}"
                                   class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('price') border-red-500 @enderror"
                                   placeholder="0.00"
                                   required>
                        </div>
                        @error('price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Category -->
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Category
                        </label>
                        <select name="category_id" 
                                id="category_id" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('category_id') border-red-500 @enderror">
                            <option value="">Select a category (optional)</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" 
                                        {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Inventory Management -->
                    <div class="bg-blue-50 rounded-lg p-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Inventory Management</h3>
                            <div class="flex items-center space-x-2">
                                @if($product->track_inventory)
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $product->stock_status_color }}">
{{ $product->stock_status }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Not Tracked
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Track Inventory Toggle -->
                        <div class="flex items-center">
                            <input type="hidden" name="track_inventory" value="0">
                            <input type="checkbox" 
                                   name="track_inventory" 
                                   id="track_inventory" 
                                   value="1"
                                   {{ old('track_inventory', $product->track_inventory) ? 'checked' : '' }}
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="track_inventory" class="ml-2 block text-sm text-gray-700">
                                Track inventory for this product
                            </label>
                        </div>

                        <div id="inventory-fields" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Stock Quantity -->
                            <div>
                                <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-2">
                                    Stock Quantity <span class="text-red-500">*</span>
                                </label>
                                <input type="number" 
                                       name="stock_quantity" 
                                       id="stock_quantity" 
                                       min="0"
                                       value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('stock_quantity') border-red-500 @enderror"
                                       placeholder="0"
                                       required>
                                @error('stock_quantity')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Low Stock Threshold -->
                            <div>
                                <label for="low_stock_threshold" class="block text-sm font-medium text-gray-700 mb-2">
                                    Low Stock Alert <span class="text-red-500">*</span>
                                </label>
                                <input type="number" 
                                       name="low_stock_threshold" 
                                       id="low_stock_threshold" 
                                       min="0"
                                       value="{{ old('low_stock_threshold', $product->low_stock_threshold ?? 10) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('low_stock_threshold') border-red-500 @enderror"
                                       placeholder="10"
                                       required>
                                @error('low_stock_threshold')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500">Alert when stock falls to or below this number</p>
                            </div>
                        </div>
                    </div>

                    <!-- Short Description -->
                    <div>
                        <label for="short_description" class="block text-sm font-medium text-gray-700 mb-2">
                            Short Description <span class="text-red-500">*</span>
                        </label>
                        <textarea name="short_description" 
                                  id="short_description" 
                                  rows="3"
                                  maxlength="500"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('short_description') border-red-500 @enderror"
                                  placeholder="Brief description for product listings (max 500 characters)"
                                  required>{{ old('short_description', $product->short_description) }}</textarea>
                        <div class="mt-1 flex justify-between">
                            @error('short_description')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @else
                                <p class="text-sm text-gray-500">Brief description for product listings</p>
                            @enderror
                            <span id="short_desc_count" class="text-sm text-gray-400">0/500</span>
                        </div>
                    </div>

                    <!-- Long Description -->
                    <div>
                        <label for="long_description" class="block text-sm font-medium text-gray-700 mb-2">
                            Detailed Description <span class="text-red-500">*</span>
                        </label>
                        <textarea name="long_description" 
                                  id="long_description" 
                                  rows="8"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('long_description') border-red-500 @enderror"
                                  placeholder="Detailed product description, features, benefits, usage instructions, etc."
                                  required>{{ old('long_description', $product->long_description) }}</textarea>
                        @error('long_description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @else
                            <p class="mt-1 text-sm text-gray-500">Detailed description for the product page</p>
                        @enderror
                    </div>
                </div>

                <!-- Image Gallery Management Section -->
                <div class="lg:col-span-1">
                    <div class="bg-gray-50 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Product Images</h3>
                            <span class="text-sm text-gray-500" id="image-count">{{ $product->images->count() }} images</span>
                        </div>
                        
                        <!-- Image Gallery -->
                        <div id="image-gallery" class="space-y-4 mb-6">
                            @forelse($product->images as $image)
                                <div class="image-item bg-white rounded-lg border border-gray-200 p-3" data-image-id="{{ $image->id }}">
                                    <div class="flex items-start space-x-3">
                                        <!-- Image Thumbnail -->
                                        <div class="flex-shrink-0 relative">
                                            <img src="{{ $image->url }}" 
                                                 alt="{{ $image->alt_text }}" 
                                                 class="w-16 h-16 object-cover rounded-md">
                                            @if($image->is_primary)
                                                <div class="absolute -top-1 -right-1">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        Primary
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <!-- Image Details -->
                                        <div class="flex-1 min-w-0">
                                            <input type="text" 
                                                   class="image-alt-text w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" 
                                                   value="{{ $image->alt_text }}" 
                                                   placeholder="Alt text for accessibility">
                                            <div class="flex items-center justify-between mt-2">
                                                <div class="flex items-center space-x-2">
                                                    @if(!$image->is_primary)
                                                        <button type="button" 
                                                                class="set-primary-btn text-xs text-blue-600 hover:text-blue-800"
                                                                data-image-id="{{ $image->id }}">
                                                            Set as Primary
                                                        </button>
                                                    @endif
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <!-- Drag Handle -->
                                                    <div class="drag-handle cursor-move text-gray-400 hover:text-gray-600">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                                        </svg>
                                                    </div>
                                                    <!-- Delete Button -->
                                                    <button type="button" 
                                                            class="delete-image-btn text-red-600 hover:text-red-800"
                                                            data-image-id="{{ $image->id }}">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-8 text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="mt-2 text-sm">No images uploaded yet</p>
                                </div>
                            @endforelse
                        </div>
                        
                        <!-- Upload New Images -->
                        <div class="border-t border-gray-200 pt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Add More Images
                            </label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="gallery-images" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                            <span>Upload images</span>
                                        </label>
                                        <p class="pl-1">or drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">PNG, JPG, GIF, WebP up to 10MB each (max 10 images)</p>
                                </div>
                            </div>
                            <input id="gallery-images" 
                                   name="gallery_images[]" 
                                   type="file" 
                                   accept="image/*"
                                   multiple
                                   class="sr-only">
                        </div>

                        

                        <div class="mt-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Image Guidelines</h4>
                            <ul class="text-xs text-gray-500 space-y-1">
                                
                                <li>• File formats: JPG, PNG, GIF</li>
                                <li>• Maximum file size: 10MB per image</li>
                                <li>• Drag images to reorder them</li>
                                <li>• First image is used as primary by default</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('admin.products.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Update Product
                    </button>
                </div>
                
                <!-- Delete Button -->
                <button type="button" 
                        onclick="confirmDelete('{{ $product->slug }}', '{{ addslashes($product->name) }}')"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <svg class="-ml-1 mr-2 h-4 w-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Product
                </button>
            </div>
        </form>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mt-4">Delete Product</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Are you sure you want to delete "<span id="productName" class="font-medium"></span>"? 
                        This action cannot be undone and will remove the product from your website.
                    </p>
                </div>
                <div class="items-center px-4 py-3">
                    <form id="deleteForm" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                            Delete
                        </button>
                    </form>
                    <button onclick="closeDeleteModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-24 hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    // Character counter for short description
    const shortDescTextarea = document.getElementById('short_description');
    const shortDescCounter = document.getElementById('short_desc_count');
    
    function updateShortDescCounter() {
        const length = shortDescTextarea.value.length;
        shortDescCounter.textContent = `${length}/500`;
        
        if (length > 450) {
            shortDescCounter.classList.add('text-red-500');
            shortDescCounter.classList.remove('text-gray-400');
        } else {
            shortDescCounter.classList.add('text-gray-400');
            shortDescCounter.classList.remove('text-red-500');
        }
    }
    
    shortDescTextarea.addEventListener('input', updateShortDescCounter);
    updateShortDescCounter(); // Initialize counter

    // Inventory tracking toggle
    const trackInventoryCheckbox = document.getElementById('track_inventory');
    const inventoryFields = document.getElementById('inventory-fields');
    const stockQuantityInput = document.getElementById('stock_quantity');
    const lowStockThresholdInput = document.getElementById('low_stock_threshold');

    function toggleInventoryFields() {
        if (trackInventoryCheckbox.checked) {
            inventoryFields.style.opacity = '1';
            stockQuantityInput.disabled = false;
            lowStockThresholdInput.disabled = false;
            stockQuantityInput.required = true;
            lowStockThresholdInput.required = true;
        } else {
            inventoryFields.style.opacity = '0.5';
            stockQuantityInput.disabled = true;
            lowStockThresholdInput.disabled = true;
            stockQuantityInput.required = false;
            lowStockThresholdInput.required = false;
        }
    }

    trackInventoryCheckbox.addEventListener('change', toggleInventoryFields);
    toggleInventoryFields(); // Initialize on page load

    // Image Gallery Management
    const productId = @json($product->slug);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Initialize sortable for image reordering
    const imageGallery = document.getElementById('image-gallery');
    if (imageGallery) {
        new Sortable(imageGallery, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function(evt) {
                updateImageOrder();
            }
        });
    }

    // Gallery image upload
    const galleryImagesInput = document.getElementById('gallery-images');
    galleryImagesInput.addEventListener('change', function(e) {
        if (e.target.files.length > 0) {
            uploadGalleryImages(e.target.files);
        }
    });

    // Upload multiple images to gallery
    function uploadGalleryImages(files) {
        const formData = new FormData();
        
        for (let i = 0; i < files.length; i++) {
            formData.append('images[]', files[i]);
        }
        
        // Show loading state
        showLoadingState();
        
        fetch(`/admin/products/${productId}/images`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoadingState();
            
            if (data.success) {
                // Add new images to gallery
                data.images.forEach(image => {
                    addImageToGallery(image);
                });
                
                updateImageCount();
                showNotification('Images uploaded successfully!', 'success');
                
                if (data.errors.length > 0) {
                    data.errors.forEach(error => {
                        showNotification(error, 'error');
                    });
                }
            } else {
                showNotification('Failed to upload images', 'error');
            }
            
            // Clear the input
            galleryImagesInput.value = '';
        })
        .catch(error => {
            hideLoadingState();
            showNotification('Error uploading images', 'error');
            console.error('Error:', error);
        });
    }

    // Add image to gallery DOM
    function addImageToGallery(image) {
        const imageItem = document.createElement('div');
        imageItem.className = 'image-item bg-white rounded-lg border border-gray-200 p-3';
        imageItem.setAttribute('data-image-id', image.id);
        
        imageItem.innerHTML = `
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0 relative">
                    <img src="${image.url}" 
                         alt="${image.alt_text}" 
                         class="w-16 h-16 object-cover rounded-md">
                    ${image.is_primary ? `
                        <div class="absolute -top-1 -right-1">
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Primary
                            </span>
                        </div>
                    ` : ''}
                </div>
                <div class="flex-1 min-w-0">
                    <input type="text" 
                           class="image-alt-text w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500" 
                           value="${image.alt_text}" 
                           placeholder="Alt text for accessibility">
                    <div class="flex items-center justify-between mt-2">
                        <div class="flex items-center space-x-2">
                            ${!image.is_primary ? `
                                <button type="button" 
                                        class="set-primary-btn text-xs text-blue-600 hover:text-blue-800"
                                        data-image-id="${image.id}">
                                    Set as Primary
                                </button>
                            ` : ''}
                        </div>
                        <div class="flex items-center space-x-2">
                            <div class="drag-handle cursor-move text-gray-400 hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                </svg>
                            </div>
                            <button type="button" 
                                    class="delete-image-btn text-red-600 hover:text-red-800"
                                    data-image-id="${image.id}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        imageGallery.appendChild(imageItem);
        attachImageEventListeners(imageItem);
    }

    // Attach event listeners to image items
    function attachImageEventListeners(imageItem) {
        // Alt text update
        const altTextInput = imageItem.querySelector('.image-alt-text');
        altTextInput.addEventListener('blur', function() {
            updateImageAltText(imageItem.dataset.imageId, this.value);
        });

        // Set primary button
        const setPrimaryBtn = imageItem.querySelector('.set-primary-btn');
        if (setPrimaryBtn) {
            setPrimaryBtn.addEventListener('click', function() {
                setPrimaryImage(this.dataset.imageId);
            });
        }

        // Delete button
        const deleteBtn = imageItem.querySelector('.delete-image-btn');
        deleteBtn.addEventListener('click', function() {
            deleteImage(this.dataset.imageId);
        });
    }

    // Initialize event listeners for existing images
    document.querySelectorAll('.image-item').forEach(attachImageEventListeners);

    // Update image alt text
    function updateImageAltText(imageId, altText) {
        fetch(`/admin/products/${productId}/images/${imageId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                alt_text: altText
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Alt text updated', 'success');
            }
        })
        .catch(error => {
            console.error('Error updating alt text:', error);
        });
    }

    // Set primary image
    function setPrimaryImage(imageId) {
        fetch(`/admin/products/${productId}/images/${imageId}/primary`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI to reflect new primary image
                document.querySelectorAll('.image-item').forEach(item => {
                    const primaryBadge = item.querySelector('.bg-blue-100');
                    const setPrimaryBtn = item.querySelector('.set-primary-btn');
                    
                    if (item.dataset.imageId === imageId) {
                        // Add primary badge
                        if (!primaryBadge) {
                            const thumbnail = item.querySelector('.flex-shrink-0');
                            thumbnail.insertAdjacentHTML('beforeend', `
                                <div class="absolute -top-1 -right-1">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Primary
                                    </span>
                                </div>
                            `);
                        }
                        // Remove set primary button
                        if (setPrimaryBtn) {
                            setPrimaryBtn.remove();
                        }
                    } else {
                        // Remove primary badge from other images
                        if (primaryBadge) {
                            primaryBadge.parentElement.remove();
                        }
                        // Add set primary button if not exists
                        if (!setPrimaryBtn) {
                            const buttonContainer = item.querySelector('.flex.items-center.space-x-2');
                            buttonContainer.insertAdjacentHTML('afterbegin', `
                                <button type="button" 
                                        class="set-primary-btn text-xs text-blue-600 hover:text-blue-800"
                                        data-image-id="${item.dataset.imageId}">
                                    Set as Primary
                                </button>
                            `);
                            // Reattach event listener
                            const newBtn = item.querySelector('.set-primary-btn');
                            newBtn.addEventListener('click', function() {
                                setPrimaryImage(this.dataset.imageId);
                            });
                        }
                    }
                });
                
                showNotification('Primary image updated', 'success');
            }
        })
        .catch(error => {
            console.error('Error setting primary image:', error);
            showNotification('Error setting primary image', 'error');
        });
    }

    // Delete image
    function deleteImage(imageId) {
        if (!confirm('Are you sure you want to delete this image?')) {
            return;
        }

        fetch(`/admin/products/${productId}/images/${imageId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove image from DOM
                const imageItem = document.querySelector(`[data-image-id="${imageId}"]`);
                if (imageItem) {
                    imageItem.remove();
                }
                
                updateImageCount();
                showNotification('Image deleted successfully', 'success');
            } else {
                showNotification('Error deleting image', 'error');
            }
        })
        .catch(error => {
            console.error('Error deleting image:', error);
            showNotification('Error deleting image', 'error');
        });
    }

    // Update image order
    function updateImageOrder() {
        const imageItems = document.querySelectorAll('.image-item');
        const images = [];
        
        imageItems.forEach((item, index) => {
            images.push({
                id: parseInt(item.dataset.imageId),
                sort_order: index
            });
        });

        fetch(`/admin/products/${productId}/images/order`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                images: images
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Image order updated', 'success');
            }
        })
        .catch(error => {
            console.error('Error updating image order:', error);
        });
    }

    // Update image count
    function updateImageCount() {
        const count = document.querySelectorAll('.image-item').length;
        document.getElementById('image-count').textContent = `${count} images`;
    }

    // Show loading state
    function showLoadingState() {
        // You can add a loading spinner here
    }

    // Hide loading state
    function hideLoadingState() {
        // Hide loading spinner
    }

    // Show notification
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-4 py-2 rounded-md text-white z-50 ${
            type === 'success' ? 'bg-green-500' : 
            type === 'error' ? 'bg-red-500' : 'bg-blue-500'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    // Delete confirmation functionality
    function confirmDelete(productSlug, productName) {
        document.getElementById('productName').textContent = productName;
        document.getElementById('deleteForm').action = `/admin/products/${productSlug}`;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDeleteModal();
        }
    });
</script>
@endpush
@extends('layouts.admin')

@section('title', 'Add New Product')

@section('header')
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Add New Product</h1>
            <p class="text-gray-600 mt-1">Create a new gym machine product</p>
        </div>
        <a href="{{ route('admin.products.index') }}" 
           class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Products
        </a>
    </div>
@endsection

@section('content')
    <div class="p-6">
        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            
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
                               value="{{ old('name') }}"
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
                                   value="{{ old('price') }}"
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
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
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
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Inventory Management</h3>
                        
                        <!-- Track Inventory Toggle -->
                        <div class="flex items-center">
                            <input type="hidden" name="track_inventory" value="0">
                            <input type="checkbox" 
                                   name="track_inventory" 
                                   id="track_inventory" 
                                   value="1"
                                   {{ old('track_inventory', true) ? 'checked' : '' }}
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
                                       value="{{ old('stock_quantity', 0) }}"
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
                                       value="{{ old('low_stock_threshold', 10) }}"
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
                                  required>{{ old('short_description') }}</textarea>
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
                                  required>{{ old('long_description') }}</textarea>
                        @error('long_description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @else
                            <p class="mt-1 text-sm text-gray-500">Detailed description for the product page</p>
                        @enderror
                    </div>

                    <!-- Product Details Section -->
                    <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Product Details</h3>
                        
                        <!-- Dimensions -->
                        <div>
                            <label for="dimensions" class="block text-sm font-medium text-gray-700 mb-2">
                                Dimensions (L×W×H)
                            </label>
                            <input type="text" 
                                   name="dimensions" 
                                   id="dimensions" 
                                   value="{{ old('dimensions') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('dimensions') border-red-500 @enderror"
                                   placeholder="e.g., 120cm × 80cm × 150cm">
                            @error('dimensions')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @else
                                <p class="mt-1 text-sm text-gray-500">Product dimensions in Length × Width × Height format</p>
                            @enderror
                        </div>

                        <!-- Material -->
                        <div>
                            <label for="material" class="block text-sm font-medium text-gray-700 mb-2">
                                Material
                            </label>
                            <input type="text" 
                                   name="material" 
                                   id="material" 
                                   value="{{ old('material') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('material') border-red-500 @enderror"
                                   placeholder="e.g., Steel frame with vinyl padding">
                            @error('material')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @else
                                <p class="mt-1 text-sm text-gray-500">Materials used in product construction</p>
                            @enderror
                        </div>

                        <!-- Care Instructions -->
                        <div>
                            <label for="care_instructions" class="block text-sm font-medium text-gray-700 mb-2">
                                Care Instructions
                            </label>
                            <textarea name="care_instructions" 
                                      id="care_instructions" 
                                      rows="4"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('care_instructions') border-red-500 @enderror"
                                      placeholder="Instructions for cleaning and maintaining the product...">{{ old('care_instructions') }}</textarea>
                            @error('care_instructions')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @else
                                <p class="mt-1 text-sm text-gray-500">How to clean and maintain this product</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Image Gallery Management Section -->
                <div class="lg:col-span-1">
                    <div class="bg-gray-50 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Product Images</h3>
                            <span class="text-sm text-gray-500" id="image-count">0 images</span>
                        </div>
                        
                        <!-- Image Gallery (empty for new product) -->
                        <div id="image-gallery" class="space-y-4 mb-6">
                            <div class="text-center py-8 text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <p class="mt-2 text-sm">No images uploaded yet</p>
                            </div>
                        </div>
                        
                        <!-- Upload New Images -->
                        <div class="border-t border-gray-200 pt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Add Images
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
                            @error('gallery_images')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @error('gallery_images.*')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
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
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="{{ route('admin.products.index') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </a>
                <button type="submit" 
                        id="submit-btn"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span class="submit-text">Create Product</span>
                    <span class="loading-text hidden">
                        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Creating Product...
                    </span>
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
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

    // Simple image upload functionality
    const galleryInput = document.getElementById('gallery-images');
    const imageGallery = document.getElementById('image-gallery');
    const imageCount = document.getElementById('image-count');

    // Handle file selection
    galleryInput.addEventListener('change', function(e) {
        const files = e.target.files;
        if (files.length > 0) {
            updateImagePreview(files);
        }
    });

    function updateImagePreview(files) {
        // Clear the "no images" message
        const noImagesMessage = imageGallery.querySelector('.text-center.py-8');
        if (noImagesMessage) {
            noImagesMessage.remove();
        }

        // Clear existing previews
        imageGallery.innerHTML = '';

        // Create previews for each file
        Array.from(files).forEach((file, index) => {
            if (index >= 10) return; // Max 10 images

            const reader = new FileReader();
            reader.onload = function(e) {
                const imageItem = document.createElement('div');
                imageItem.className = 'image-item bg-white rounded-lg border border-gray-200 p-3';
                
                imageItem.innerHTML = `
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 relative">
                            <img src="${e.target.result}" alt="${file.name}" class="w-16 h-16 object-cover rounded-md">
                            ${index === 0 ? `
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
                                   value="${file.name.replace(/\.[^/.]+$/, '')}" 
                                   placeholder="Alt text for accessibility">
                            <div class="mt-2 text-xs text-gray-500">
                                ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                            </div>
                        </div>
                    </div>
                `;
                
                imageGallery.appendChild(imageItem);
            };
            reader.readAsDataURL(file);
        });

        // Update count
        imageCount.textContent = `${Math.min(files.length, 10)} images`;
    }

    // Drag and drop functionality
    const dropZone = galleryInput.closest('.border-dashed');
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        dropZone.classList.add('border-blue-400', 'bg-blue-50');
    }

    function unhighlight(e) {
        dropZone.classList.remove('border-blue-400', 'bg-blue-50');
    }

    dropZone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        if (files.length > 0) {
            // Filter only image files
            const imageFiles = Array.from(files).filter(file => file.type.startsWith('image/'));
            if (imageFiles.length > 0) {
                // Create a new FileList-like object
                const dataTransfer = new DataTransfer();
                imageFiles.forEach(file => dataTransfer.items.add(file));
                galleryInput.files = dataTransfer.files;
                
                updateImagePreview(dataTransfer.files);
            }
        }
    }

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

    // Form submission loading state
    const form = document.querySelector('form');
    const submitBtn = document.getElementById('submit-btn');
    const submitText = submitBtn.querySelector('.submit-text');
    const loadingText = submitBtn.querySelector('.loading-text');

    form.addEventListener('submit', function(e) {
        // Show loading state
        submitBtn.disabled = true;
        submitText.classList.add('hidden');
        loadingText.classList.remove('hidden');
        
        // Disable form inputs to prevent changes during submission
        const inputs = form.querySelectorAll('input, textarea, select, button');
        inputs.forEach(input => {
            if (input !== submitBtn) {
                input.disabled = true;
            }
        });
    });
</script>
@endpush
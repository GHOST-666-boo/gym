@extends('layouts.app')

@section('title', $product->name . ' - Gym Machines')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Left Side - Product Images -->
        <div class="space-y-4">
            @php 
                $images = $product->images && $product->images->count() ? $product->images : collect();
                $allImages = [];
                
                // Add gallery images
                foreach($images as $image) {
                    $allImages[] = [
                        'url' => $image->url,
                        'alt' => $image->alt_text ?? $product->name
                    ];
                }
                
                // Add legacy image if no gallery images
                if (empty($allImages) && $product->image_path) {
                    $allImages[] = [
                        'url' => asset('storage/' . $product->image_path),
                        'alt' => $product->name
                    ];
                }
            @endphp
            
            @if(count($allImages) > 0)
                <!-- Image Carousel -->
                <div class="relative">
                    <!-- Main Image Container -->
                    <div class="relative w-full bg-gray-100 rounded-lg overflow-hidden cursor-pointer product-gallery shadow-md" id="imageCarousel" onclick="openLightbox(currentImageIndex)" style="min-height: 300px;">
                        @foreach($allImages as $index => $image)
                            <div class="carousel-slide {{ $index === 0 ? 'active' : '' }} absolute inset-0 flex items-center justify-center transition-opacity duration-500 ease-in-out {{ $index === 0 ? 'opacity-100' : 'opacity-0' }}">
                                @php
                                    // Extract image path from URL for watermarking
                                    $imagePath = str_replace(asset('storage/'), '', $image['url']);
                                @endphp
                                <x-product-image 
                                    :image-path="$imagePath"
                                    :alt="$image['alt']"
                                    class="w-auto h-auto max-w-full max-h-full object-contain"
                                    :width="800"
                                    :height="600"
                                    :lazy="false"
                                    
                                />
                            </div>
                        @endforeach
                        
                        <!-- Click to enlarge indicator -->
                        <div class="absolute top-4 left-4 bg-black bg-opacity-50 text-white px-2 py-1 rounded text-sm flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                            </svg>
                            Click to enlarge
                        </div>
                        
                        @if(count($allImages) > 1)
                            <!-- Previous Button -->
                            <button onclick="event.stopPropagation(); previousImage()" 
                                    class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-75 transition-all duration-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            
                            <!-- Next Button -->
                            <button onclick="event.stopPropagation(); nextImage()" 
                                    class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-75 transition-all duration-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                            
                            <!-- Image Counter -->
                            <div class="absolute bottom-4 right-4 bg-black bg-opacity-50 text-white px-3 py-1 rounded-full text-sm">
                                <span id="currentImageNumber">1</span> / {{ count($allImages) }}
                            </div>
                        @endif
                    </div>
                    
                    @if(count($allImages) > 1)
                        <!-- Thumbnail Navigation -->
                        <div class="mt-4 flex space-x-2 overflow-x-auto pb-2">
                            @foreach($allImages as $index => $image)
                                <button onclick="showImage({{ $index }})" 
                                        ondblclick="openLightbox({{ $index }})"
                                        class="thumbnail-btn flex-shrink-0 w-20 h-20 rounded-md overflow-hidden border-2 transition-all duration-200 {{ $index === 0 ? 'border-blue-500' : 'border-gray-300' }} relative group product-gallery"
                                        data-index="{{ $index }}"
                                        title="Click to select, double-click to enlarge">
                                    @php
                                        $imagePath = str_replace(asset('storage/'), '', $image['url']);
                                    @endphp
                                    <x-product-image 
                                        :image-path="$imagePath"
                                        :alt="$image['alt']"
                                        class="w-full h-full object-cover"
                                        :width="80"
                                        :height="80"
                                        :lazy="false"
                                        :watermarked="false"
                                    />
                                    <!-- Hover overlay -->
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 flex items-center justify-center">
                                        <svg class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                        </svg>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                        
                        <!-- Dots Indicator -->
                        <div class="mt-4 flex justify-center space-x-2">
                            @foreach($allImages as $index => $image)
                                <button onclick="showImage({{ $index }})" 
                                        class="dot w-3 h-3 rounded-full transition-all duration-200 {{ $index === 0 ? 'bg-blue-500' : 'bg-gray-300' }}"
                                        data-index="{{ $index }}"></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="w-full h-80 sm:h-96 bg-gray-100 rounded-lg flex items-center justify-center shadow-md">
                    <div class="text-center text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span class="text-sm">No image available</span>
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Right Side - Product Information -->
        <div class="space-y-6">
            <!-- Product Title -->
            <div>
                <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-2">{{ $product->name }}</h1>
                
                <!-- Category -->
                @if($product->category)
                    <div class="mb-4">
                        <a href="{{ route('products.category', $product->category) }}" 
                           class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition-colors duration-200">
                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            {{ $product->category->name }}
                        </a>
                    </div>
                @endif
            </div>

            <!-- Short Description -->
            <div>
                <p class="text-lg text-gray-600 leading-relaxed">{{ $product->short_description }}</p>
            </div>

            <!-- Price -->
            <div class="border-t border-b border-gray-200 py-6">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-500 uppercase tracking-wider">Price</span>
                    <span class="text-3xl font-bold text-blue-600">${{ number_format($product->price, 2) }}</span>
                </div>
            </div>

            <!-- Stock Status -->
            @if($product->track_inventory)
                <div class="flex items-center space-x-2">
                    <div class="flex items-center">
                        @if($product->isInStock())
                            <div class="h-3 w-3 bg-green-500 rounded-full mr-2"></div>
                            <span class="text-sm font-medium text-green-700">In Stock</span>
                            @if($product->isLowStock())
                                <span class="ml-2 text-xs text-yellow-600">({{ $product->stock_quantity }} remaining)</span>
                            @endif
                        @else
                            <div class="h-3 w-3 bg-red-500 rounded-full mr-2"></div>
                            <span class="text-sm font-medium text-red-700">Out of Stock</span>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="space-y-4 pt-4">
                <!-- Add to Quote Cart Button -->
                <button onclick="addToQuoteCart({{ $product->id }})" 
                        class="block w-full bg-green-600 text-white text-center px-8 py-4 rounded-lg font-semibold text-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 shadow-lg hover:shadow-xl">
                    <svg class="h-5 w-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 6M7 13l-1.5-6m0 0h15M17 21a2 2 0 100-4 2 2 0 000 4zM9 21a2 2 0 100-4 2 2 0 000 4z"></path>
                    </svg>
                    Add to Quote Cart
                </button>
                
                <!-- Get Instant Quote Button -->
                <button onclick="openQuoteModal()" 
                        class="block w-full bg-blue-600 text-white text-center px-8 py-4 rounded-lg font-semibold text-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-lg hover:shadow-xl">
                    <svg class="h-5 w-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    Get Instant Quote
                </button>
                
                <!-- Additional Info -->
                <div class="text-center">
                    <p class="text-sm text-gray-500">
                        Add multiple products to cart for bulk quotes or get instant quote for this product
                    </p>
                </div>
            </div>

            <!-- Quick Features -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Why Choose This Product?</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Professional Grade
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Expert Support
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Fast Delivery
                    </div>
                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Quality Guarantee
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lightbox Modal -->
    @if(count($allImages ?? []) > 0)
    <div id="lightbox" class="fixed inset-0 bg-black bg-opacity-95 z-50 hidden">
        <!-- Close Button -->
        <button onclick="closeLightbox()" 
                class="absolute top-4 right-4 z-30 bg-black bg-opacity-70 text-white p-3 rounded-full hover:bg-opacity-90 transition-all duration-200">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        
        <!-- Main Image Container -->
        <div class="absolute inset-0 flex items-center justify-center pb-24 px-16 pt-16">
            @foreach($allImages as $index => $image)
                <div class="lightbox-slide {{ $index === 0 ? 'active' : '' }} absolute inset-0 flex items-center justify-center transition-opacity duration-500 ease-in-out {{ $index === 0 ? 'opacity-100' : 'opacity-0' }}" style="padding: 80px 80px 120px 80px;">
                    @php
                        $imagePath = str_replace(asset('storage/'), '', $image['url']);
                    @endphp
                    <x-product-image 
                        :image-path="$imagePath"
                        :alt="$image['alt']"
                        class="max-w-full max-h-full object-contain"
                        :width="1200"
                        :height="800"
                        :lazy="false"
                        style="width: auto; height: auto; max-width: calc(100vw - 160px); max-height: calc(100vh - 200px);"
                    />
                </div>
            @endforeach
        </div>
        
        @if(count($allImages) > 1)
            <!-- Navigation Buttons -->
            <button onclick="previousLightboxImage()" 
                    class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-70 text-white p-3 rounded-full hover:bg-opacity-90 transition-all duration-200 z-20">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
            
            <button onclick="nextLightboxImage()" 
                    class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-70 text-white p-3 rounded-full hover:bg-opacity-90 transition-all duration-200 z-20">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
            
            <!-- Bottom Controls -->
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-6">
                <!-- Thumbnail Navigation -->
                <div class="flex justify-center space-x-2 mb-4 overflow-x-auto pb-2">
                    @foreach($allImages as $index => $image)
                        <button onclick="showLightboxImage({{ $index }})" 
                                class="lightbox-thumbnail-btn flex-shrink-0 w-16 h-16 rounded-md overflow-hidden border-2 transition-all duration-200 {{ $index === 0 ? 'border-white' : 'border-gray-500' }}"
                                data-index="{{ $index }}">
                            @php
                                $imagePath = str_replace(asset('storage/'), '', $image['url']);
                            @endphp
                            <x-product-image 
                                :image-path="$imagePath"
                                :alt="$image['alt']"
                                class="w-full h-full object-cover"
                                :width="64"
                                :height="64"
                                :lazy="false"
                                :watermarked="false"
                            />
                        </button>
                    @endforeach
                </div>
                
                <!-- Image Counter -->
                <div class="flex justify-center">
                    <div class="bg-black bg-opacity-70 text-white px-4 py-2 rounded-full text-lg font-medium">
                        <span id="lightboxImageNumber">1</span> / {{ count($allImages) }}
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Click outside to close -->
        <div class="absolute inset-0 -z-10" onclick="closeLightbox()"></div>
    </div>
    @endif
    
    <!-- Quote Modal Component -->
    <x-quote-modal :product="$product" />
    
    <!-- Product Details Section -->
    <div class="mt-12">
        <x-product-details :product="$product" />
    </div>
</div>

@push('styles')
<style>
/* ONLY for lightbox (full screen) - proper aspect ratio */
#lightbox .lightbox-slide img {
    width: auto !important;
    height: auto !important;
    max-width: calc(100vw - 160px) !important;
    max-height: calc(100vh - 200px) !important;
    object-fit: contain !important;
    object-position: center !important;
    image-rendering: -webkit-optimize-contrast;
    image-rendering: crisp-edges;
}

/* Keep main carousel as it was - object-cover for consistent layout */
.carousel-slide img {
    width: auto;
    height: auto;
    max-width: 100%;
    object-fit: contain;
}
</style>
@endpush

@push('scripts')
<script>
let currentImageIndex = 0;
let currentLightboxIndex = 0;
const totalImages = {{ count($allImages ?? []) }};

function showImage(index) {
    if (totalImages <= 1) return;
    
    // Hide current image
    const currentSlide = document.querySelector('.carousel-slide.active');
    if (currentSlide) {
        currentSlide.classList.remove('active', 'opacity-100');
        currentSlide.classList.add('opacity-0');
    }
    
    // Show new image
    const slides = document.querySelectorAll('.carousel-slide');
    if (slides[index]) {
        slides[index].classList.add('active', 'opacity-100');
        slides[index].classList.remove('opacity-0');
    }
    
    // Update thumbnail borders
    document.querySelectorAll('.thumbnail-btn').forEach((btn, i) => {
        if (i === index) {
            btn.classList.remove('border-gray-300');
            btn.classList.add('border-blue-500');
        } else {
            btn.classList.remove('border-blue-500');
            btn.classList.add('border-gray-300');
        }
    });
    
    // Update dots
    document.querySelectorAll('.dot').forEach((dot, i) => {
        if (i === index) {
            dot.classList.remove('bg-gray-300');
            dot.classList.add('bg-blue-500');
        } else {
            dot.classList.remove('bg-blue-500');
            dot.classList.add('bg-gray-300');
        }
    });
    
    // Update counter
    currentImageIndex = index;
    const counter = document.getElementById('currentImageNumber');
    if (counter) {
        counter.textContent = index + 1;
    }
}

function nextImage() {
    if (totalImages <= 1) return;
    const nextIndex = (currentImageIndex + 1) % totalImages;
    showImage(nextIndex);
}

function previousImage() {
    if (totalImages <= 1) return;
    const prevIndex = (currentImageIndex - 1 + totalImages) % totalImages;
    showImage(prevIndex);
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (totalImages <= 1) return;
    
    if (e.key === 'ArrowLeft') {
        previousImage();
    } else if (e.key === 'ArrowRight') {
        nextImage();
    }
});

// Touch/swipe support for mobile
let startX = null;
let startY = null;

// Carousel swipe support
document.getElementById('imageCarousel')?.addEventListener('touchstart', function(e) {
    const firstTouch = e.touches[0];
    startX = firstTouch.clientX;
    startY = firstTouch.clientY;
});

document.getElementById('imageCarousel')?.addEventListener('touchend', function(e) {
    if (!startX || !startY) return;
    
    const endX = e.changedTouches[0].clientX;
    const endY = e.changedTouches[0].clientY;
    
    const diffX = startX - endX;
    const diffY = startY - endY;
    
    // Only trigger swipe if horizontal movement is greater than vertical
    if (Math.abs(diffX) > Math.abs(diffY)) {
        if (Math.abs(diffX) > 50) { // Minimum swipe distance
            if (diffX > 0) {
                nextImage(); // Swipe left = next image
            } else {
                previousImage(); // Swipe right = previous image
            }
        }
    }
    
    startX = null;
    startY = null;
});

// Lightbox swipe support
document.getElementById('lightbox')?.addEventListener('touchstart', function(e) {
    const firstTouch = e.touches[0];
    startX = firstTouch.clientX;
    startY = firstTouch.clientY;
});

document.getElementById('lightbox')?.addEventListener('touchend', function(e) {
    if (!startX || !startY) return;
    
    const endX = e.changedTouches[0].clientX;
    const endY = e.changedTouches[0].clientY;
    
    const diffX = startX - endX;
    const diffY = startY - endY;
    
    // Only trigger swipe if horizontal movement is greater than vertical
    if (Math.abs(diffX) > Math.abs(diffY)) {
        if (Math.abs(diffX) > 50) { // Minimum swipe distance
            if (diffX > 0) {
                nextLightboxImage(); // Swipe left = next image
            } else {
                previousLightboxImage(); // Swipe right = previous image
            }
        }
    }
    
    startX = null;
    startY = null;
});

// Auto-play (optional) - uncomment to enable
// setInterval(function() {
//     if (totalImages > 1) {
//         nextImage();
//     }
// }, 5000); // Change image every 5 seconds

// LIGHTBOX FUNCTIONS
function openLightbox(index = null) {
    if (index !== null) {
        currentLightboxIndex = index;
        showLightboxImage(index);
    } else {
        currentLightboxIndex = currentImageIndex;
        showLightboxImage(currentImageIndex);
    }
    
    const lightbox = document.getElementById('lightbox');
    if (lightbox) {
        lightbox.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
        
        // Ensure proper aspect ratio after opening
        setTimeout(() => {
            ensureProperAspectRatio();
        }, 100);
    }
}

// Function to ensure ONLY lightbox images maintain proper aspect ratio
function ensureProperAspectRatio() {
    // Only apply to lightbox images, not main carousel
    const lightboxImages = document.querySelectorAll('#lightbox img');
    
    lightboxImages.forEach(img => {
        if (img.complete && img.naturalWidth > 0) {
            adjustLightboxImageSize(img);
        } else {
            img.addEventListener('load', () => adjustLightboxImageSize(img));
        }
    });
}

function adjustLightboxImageSize(img) {
    const container = img.closest('.lightbox-slide');
    if (!container) return;
    
    // Only adjust if it's in lightbox
    if (!container.closest('#lightbox')) return;
    
    const maxWidth = window.innerWidth - 160;
    const maxHeight = window.innerHeight - 200;
    
    // Determine which dimension is the limiting factor
    const widthRatio = maxWidth / img.naturalWidth;
    const heightRatio = maxHeight / img.naturalHeight;
    const scale = Math.min(widthRatio, heightRatio, 1); // Don't scale up
    
    if (scale < 1) {
        img.style.width = Math.floor(img.naturalWidth * scale) + 'px';
        img.style.height = Math.floor(img.naturalHeight * scale) + 'px';
    } else {
        img.style.width = img.naturalWidth + 'px';
        img.style.height = img.naturalHeight + 'px';
    }
    
    img.style.maxWidth = 'none';
    img.style.maxHeight = 'none';
}

function closeLightbox() {
    const lightbox = document.getElementById('lightbox');
    if (lightbox) {
        lightbox.classList.add('hidden');
        document.body.style.overflow = 'auto'; // Restore scrolling
    }
}

function showLightboxImage(index) {
    if (totalImages <= 1) return;
    
    // Hide current lightbox image
    const currentSlide = document.querySelector('.lightbox-slide.active');
    if (currentSlide) {
        currentSlide.classList.remove('active', 'opacity-100');
        currentSlide.classList.add('opacity-0');
    }
    
    // Show new lightbox image
    const slides = document.querySelectorAll('.lightbox-slide');
    if (slides[index]) {
        slides[index].classList.add('active', 'opacity-100');
        slides[index].classList.remove('opacity-0');
    }
    
    // Update lightbox thumbnail borders
    document.querySelectorAll('.lightbox-thumbnail-btn').forEach((btn, i) => {
        if (i === index) {
            btn.classList.remove('border-gray-500');
            btn.classList.add('border-white');
        } else {
            btn.classList.remove('border-white');
            btn.classList.add('border-gray-500');
        }
    });
    
    // Update lightbox counter
    currentLightboxIndex = index;
    const counter = document.getElementById('lightboxImageNumber');
    if (counter) {
        counter.textContent = index + 1;
    }
}

function nextLightboxImage() {
    if (totalImages <= 1) return;
    const nextIndex = (currentLightboxIndex + 1) % totalImages;
    showLightboxImage(nextIndex);
}

function previousLightboxImage() {
    if (totalImages <= 1) return;
    const prevIndex = (currentLightboxIndex - 1 + totalImages) % totalImages;
    showLightboxImage(prevIndex);
}

// Enhanced keyboard navigation for lightbox
document.addEventListener('keydown', function(e) {
    const lightbox = document.getElementById('lightbox');
    const isLightboxOpen = lightbox && !lightbox.classList.contains('hidden');
    
    if (isLightboxOpen) {
        if (e.key === 'Escape') {
            closeLightbox();
        } else if (e.key === 'ArrowLeft') {
            previousLightboxImage();
        } else if (e.key === 'ArrowRight') {
            nextLightboxImage();
        }
    } else if (totalImages > 1) {
        // Regular carousel navigation when lightbox is closed
        if (e.key === 'ArrowLeft') {
            previousImage();
        } else if (e.key === 'ArrowRight') {
            nextImage();
        }
    }
});

// Handle window resize ONLY for lightbox
window.addEventListener('resize', () => {
    const lightbox = document.getElementById('lightbox');
    if (lightbox && !lightbox.classList.contains('hidden')) {
        setTimeout(() => {
            ensureProperAspectRatio(); // Only affects lightbox images
        }, 100);
    }
});

// Initialize aspect ratio ONLY for lightbox when it opens
// Main carousel keeps its original behavior
</script>
@endpush

@endsection
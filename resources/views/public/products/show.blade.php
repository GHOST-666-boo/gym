@extends('layouts.app')

@section('title', $product->name . ' - Gym Machines')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-4">{{ $product->name }}</h1>
    
    @if($product->category)
        <p class="text-gray-600 mb-4">Category: {{ $product->category->name }}</p>
    @endif
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div>
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
                    <div class="relative w-full h-96 bg-gray-200 rounded-lg overflow-hidden cursor-pointer" id="imageCarousel" onclick="openLightbox(currentImageIndex)">
                        @foreach($allImages as $index => $image)
                            <div class="carousel-slide {{ $index === 0 ? 'active' : '' }} absolute inset-0 transition-opacity duration-500 ease-in-out {{ $index === 0 ? 'opacity-100' : 'opacity-0' }}">
                                <img src="{{ $image['url'] }}" 
                                     alt="{{ $image['alt'] }}" 
                                     class="w-full h-full object-cover">
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
                                        class="thumbnail-btn flex-shrink-0 w-20 h-20 rounded-md overflow-hidden border-2 transition-all duration-200 {{ $index === 0 ? 'border-blue-500' : 'border-gray-300' }} relative group"
                                        data-index="{{ $index }}"
                                        title="Click to select, double-click to enlarge">
                                    <img src="{{ $image['url'] }}" 
                                         alt="{{ $image['alt'] }}" 
                                         class="w-full h-full object-cover">
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
                <div class="w-full h-96 bg-gray-200 rounded-lg flex items-center justify-center">
                    <span class="text-gray-500">No image available</span>
                </div>
            @endif
        </div>
        
        <div>
            <p class="text-xl text-gray-600 mb-6">{{ $product->short_description }}</p>
            <p class="text-3xl font-bold text-blue-600 mb-6">${{ number_format($product->price, 2) }}</p>
            
            <div class="space-y-4">
                <a href="{{ route('contact') }}" 
                   class="block w-full bg-blue-600 text-white text-center px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors duration-200">
                    Get Quote
                </a>
            </div>
        </div>
    </div>

    <!-- Lightbox Modal -->
    @if(count($allImages ?? []) > 0)
    <div id="lightbox" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center p-4">
        <!-- Modal Content -->
        <div class="relative max-w-7xl max-h-full w-full h-full flex items-center justify-center">
            <!-- Close Button -->
            <button onclick="closeLightbox()" 
                    class="absolute top-4 right-4 z-10 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-75 transition-all duration-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            
            <!-- Main Image -->
            <div class="relative w-full h-full flex items-center justify-center">
                @foreach($allImages as $index => $image)
                    <div class="lightbox-slide {{ $index === 0 ? 'active' : '' }} absolute inset-0 flex items-center justify-center transition-opacity duration-500 ease-in-out {{ $index === 0 ? 'opacity-100' : 'opacity-0' }}">
                        <img src="{{ $image['url'] }}" 
                             alt="{{ $image['alt'] }}" 
                             class="max-w-full max-h-full object-contain">
                    </div>
                @endforeach
                
                @if(count($allImages) > 1)
                    <!-- Previous Button -->
                    <button onclick="previousLightboxImage()" 
                            class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-3 rounded-full hover:bg-opacity-75 transition-all duration-200">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    
                    <!-- Next Button -->
                    <button onclick="nextLightboxImage()" 
                            class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-3 rounded-full hover:bg-opacity-75 transition-all duration-200">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                @endif
            </div>
            
            @if(count($allImages) > 1)
                <!-- Image Counter -->
                <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-50 text-white px-4 py-2 rounded-full text-lg">
                    <span id="lightboxImageNumber">1</span> / {{ count($allImages) }}
                </div>
                
                <!-- Thumbnail Navigation -->
                <div class="absolute bottom-4 left-4 right-4 flex justify-center space-x-2 overflow-x-auto pb-2">
                    @foreach($allImages as $index => $image)
                        <button onclick="showLightboxImage({{ $index }})" 
                                class="lightbox-thumbnail-btn flex-shrink-0 w-16 h-16 rounded-md overflow-hidden border-2 transition-all duration-200 {{ $index === 0 ? 'border-white' : 'border-gray-500' }}"
                                data-index="{{ $index }}">
                            <img src="{{ $image['url'] }}" 
                                 alt="{{ $image['alt'] }}" 
                                 class="w-full h-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
        
        <!-- Click outside to close -->
        <div class="absolute inset-0 -z-10" onclick="closeLightbox()"></div>
    </div>
    @endif
    
    <div class="mt-12">
        <h2 class="text-2xl font-bold mb-6">Product Details</h2>
        <div class="prose max-w-none">
            {!! nl2br(e($product->long_description)) !!}
        </div>
    </div>
</div>

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
    }
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
</script>
@endpush

@endsection
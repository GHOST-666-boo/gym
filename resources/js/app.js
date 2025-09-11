import './bootstrap';
import './form-feedback';
import './admin-enhancements';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Image Gallery and Zoom Functionality
class ImageGallery {
    constructor() {
        this.currentImageIndex = 0;
        this.images = [];
        this.modal = null;
        this.init();
    }

    init() {
        this.createModal();
        this.bindEvents();
    }

    createModal() {
        // Create modal if it doesn't exist
        if (!document.getElementById('imageModal')) {
            const modal = document.createElement('div');
            modal.id = 'imageModal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center p-4';
            modal.innerHTML = `
                <div class="relative max-w-7xl max-h-full w-full h-full flex items-center justify-center">
                    <!-- Close Button -->
                    <button id="closeModal" class="absolute top-4 right-4 text-white hover:text-gray-300 z-10 bg-black bg-opacity-50 rounded-full p-2 transition-colors duration-200">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    
                    <!-- Previous Button -->
                    <button id="prevImage" class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 z-10 bg-black bg-opacity-50 rounded-full p-3 transition-all duration-200 hover:bg-opacity-70">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    
                    <!-- Next Button -->
                    <button id="nextImage" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white hover:text-gray-300 z-10 bg-black bg-opacity-50 rounded-full p-3 transition-all duration-200 hover:bg-opacity-70">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <!-- Image Container -->
                    <div class="relative max-w-full max-h-full">
                        <img id="modalImage" src="" alt="" class="max-w-full max-h-full object-contain transition-opacity duration-300">
                        
                        <!-- Loading Spinner -->
                        <div id="imageLoader" class="absolute inset-0 flex items-center justify-center">
                            <div class="loading-spinner"></div>
                        </div>
                        
                        <!-- Image Counter -->
                        <div id="imageCounter" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-50 text-white px-3 py-1 rounded-full text-sm">
                            <span id="currentImageNum">1</span> / <span id="totalImages">1</span>
                        </div>
                    </div>
                    
                    <!-- Image Title -->
                    <div id="imageTitle" class="absolute bottom-4 left-4 right-4 text-center text-white bg-black bg-opacity-50 rounded-lg p-3 max-w-md mx-auto">
                        <h3 class="font-semibold"></h3>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            this.modal = modal;
        } else {
            this.modal = document.getElementById('imageModal');
        }
    }

    bindEvents() {
        // Close modal events
        document.getElementById('closeModal')?.addEventListener('click', () => this.closeModal());
        document.getElementById('imageModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'imageModal') this.closeModal();
        });

        // Navigation events
        document.getElementById('prevImage')?.addEventListener('click', () => this.previousImage());
        document.getElementById('nextImage')?.addEventListener('click', () => this.nextImage());

        // Keyboard events
        document.addEventListener('keydown', (e) => {
            if (!this.modal?.classList.contains('hidden')) {
                switch(e.key) {
                    case 'Escape':
                        this.closeModal();
                        break;
                    case 'ArrowLeft':
                        this.previousImage();
                        break;
                    case 'ArrowRight':
                        this.nextImage();
                        break;
                }
            }
        });

        // Bind click events to images with zoom functionality
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-zoom]') || e.target.closest('[data-zoom]')) {
                e.preventDefault();
                const img = e.target.matches('[data-zoom]') ? e.target : e.target.closest('[data-zoom]');
                this.openModal(img.src, img.alt, this.getImageGallery(img));
            }
        });
    }

    getImageGallery(clickedImage) {
        // Get all images in the same gallery
        const gallery = clickedImage.closest('[data-gallery]');
        if (gallery) {
            return Array.from(gallery.querySelectorAll('[data-zoom]'));
        }
        return [clickedImage];
    }

    openModal(src, alt = '', images = []) {
        this.images = images.map(img => ({
            src: img.src,
            alt: img.alt || '',
            title: img.dataset.title || img.alt || ''
        }));
        
        this.currentImageIndex = this.images.findIndex(img => img.src === src);
        if (this.currentImageIndex === -1) {
            this.images = [{src, alt, title: alt}];
            this.currentImageIndex = 0;
        }

        this.showImage();
        this.modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    closeModal() {
        this.modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    showImage() {
        const image = this.images[this.currentImageIndex];
        const modalImage = document.getElementById('modalImage');
        const imageLoader = document.getElementById('imageLoader');
        const imageTitle = document.getElementById('imageTitle');
        const currentImageNum = document.getElementById('currentImageNum');
        const totalImages = document.getElementById('totalImages');

        // Show loader
        imageLoader.style.display = 'flex';
        modalImage.style.opacity = '0';

        // Load image
        const img = new Image();
        img.onload = () => {
            modalImage.src = image.src;
            modalImage.alt = image.alt;
            modalImage.style.opacity = '1';
            imageLoader.style.display = 'none';
        };
        img.src = image.src;

        // Update title
        if (image.title) {
            imageTitle.querySelector('h3').textContent = image.title;
            imageTitle.style.display = 'block';
        } else {
            imageTitle.style.display = 'none';
        }

        // Update counter
        currentImageNum.textContent = this.currentImageIndex + 1;
        totalImages.textContent = this.images.length;

        // Show/hide navigation buttons
        const prevBtn = document.getElementById('prevImage');
        const nextBtn = document.getElementById('nextImage');
        
        if (this.images.length > 1) {
            prevBtn.style.display = 'block';
            nextBtn.style.display = 'block';
        } else {
            prevBtn.style.display = 'none';
            nextBtn.style.display = 'none';
        }
    }

    previousImage() {
        if (this.images.length > 1) {
            this.currentImageIndex = (this.currentImageIndex - 1 + this.images.length) % this.images.length;
            this.showImage();
        }
    }

    nextImage() {
        if (this.images.length > 1) {
            this.currentImageIndex = (this.currentImageIndex + 1) % this.images.length;
            this.showImage();
        }
    }
}

// Smooth Scrolling
class SmoothScroll {
    constructor() {
        this.init();
    }

    init() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', (e) => {
                const href = anchor.getAttribute('href');
                if (href === '#') return;
                
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    }
}

// Form Enhancements
class FormEnhancements {
    constructor() {
        this.init();
    }

    init() {
        this.addLoadingStates();
        this.addFormValidation();
    }

    addLoadingStates() {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', (e) => {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !form.dataset.noLoading) {
                    submitBtn.disabled = true;
                    const originalText = submitBtn.textContent;
                    submitBtn.innerHTML = `
                        <div class="flex items-center">
                            <div class="loading-spinner mr-2 h-4 w-4"></div>
                            Processing...
                        </div>
                    `;
                    
                    // Reset after 10 seconds as fallback
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    }, 10000);
                }
            });
        });
    }

    addFormValidation() {
        // Add real-time validation feedback
        document.querySelectorAll('input, textarea, select').forEach(field => {
            field.addEventListener('blur', () => {
                this.validateField(field);
            });
        });
    }

    validateField(field) {
        const isValid = field.checkValidity();
        const errorElement = field.parentNode.querySelector('.field-error');
        
        if (!isValid) {
            field.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
            field.classList.remove('border-gray-300', 'focus:border-blue-500', 'focus:ring-blue-500');
            
            if (!errorElement) {
                const error = document.createElement('p');
                error.className = 'field-error text-red-500 text-sm mt-1';
                error.textContent = field.validationMessage;
                field.parentNode.appendChild(error);
            }
        } else {
            field.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
            field.classList.add('border-gray-300', 'focus:border-blue-500', 'focus:ring-blue-500');
            
            if (errorElement) {
                errorElement.remove();
            }
        }
    }
}

// Lazy Loading for Images
class LazyLoading {
    constructor() {
        this.init();
    }

    init() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }
}

// Notification System
class NotificationSystem {
    constructor() {
        this.container = null;
        this.init();
    }

    init() {
        this.createContainer();
    }

    createContainer() {
        if (!document.getElementById('notification-container')) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'fixed top-4 right-4 z-50 space-y-2';
            document.body.appendChild(container);
            this.container = container;
        }
    }

    show(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        const typeClasses = {
            success: 'bg-green-500 text-white',
            error: 'bg-red-500 text-white',
            warning: 'bg-yellow-500 text-black',
            info: 'bg-blue-500 text-white'
        };

        notification.className = `${typeClasses[type]} px-4 py-3 rounded-lg shadow-lg max-w-sm transform translate-x-full transition-transform duration-300`;
        notification.innerHTML = `
            <div class="flex items-center justify-between">
                <span>${message}</span>
                <button class="ml-4 text-current opacity-70 hover:opacity-100" onclick="this.parentElement.parentElement.remove()">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;

        this.container.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto remove
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }, duration);
    }
}

// Initialize all components when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ImageGallery();
    new SmoothScroll();
    new FormEnhancements();
    new LazyLoading();
    window.notifications = new NotificationSystem();
});

// Global functions for backward compatibility
window.openImageModal = function(src, alt = '') {
    const gallery = new ImageGallery();
    gallery.openModal(src, alt);
};

window.closeImageModal = function() {
    const modal = document.getElementById('imageModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
};

// Utility functions
window.utils = {
    // Copy to clipboard
    copyToClipboard: async function(text) {
        try {
            await navigator.clipboard.writeText(text);
            window.notifications?.show('Copied to clipboard!', 'success', 2000);
        } catch (err) {
            console.error('Failed to copy: ', err);
            window.notifications?.show('Failed to copy to clipboard', 'error');
        }
    },

    // Format currency
    formatCurrency: function(amount, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },

    // Debounce function
    debounce: function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    // Throttle function
    throttle: function(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
};

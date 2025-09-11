// Enhanced Image Viewer with Real Size Display

class ImageViewer {
    constructor() {
        this.currentMode = 'fit'; // 'fit', 'real', 'fill'
        this.init();
    }

    init() {
        this.addViewModeControls();
        this.addImageInfo();
        this.bindEvents();
    }

    addViewModeControls() {
        const imageContainers = document.querySelectorAll('.product-gallery, #lightbox');
        
        imageContainers.forEach(container => {
            if (container.querySelector('.view-mode-toggle')) return;
            
            const controls = document.createElement('div');
            controls.className = 'view-mode-toggle';
            controls.innerHTML = `
                <button class="view-mode-btn active" data-mode="fit">Fit to Container</button>
                <button class="view-mode-btn" data-mode="real">Real Size</button>
                <button class="view-mode-btn" data-mode="fill">Fill Container</button>
            `;
            
            container.insertBefore(controls, container.firstChild);
        });
    }

    addImageInfo() {
        const images = document.querySelectorAll('.product-gallery img, #lightbox img');
        
        images.forEach(img => {
            img.addEventListener('load', () => {
                this.showImageDimensions(img);
            });
        });
    }

    showImageDimensions(img) {
        // Remove existing dimension display
        const existingDimensions = img.parentElement.querySelector('.image-dimensions');
        if (existingDimensions) {
            existingDimensions.remove();
        }

        // Create dimension display
        const dimensions = document.createElement('div');
        dimensions.className = 'image-dimensions';
        dimensions.textContent = `${img.naturalWidth} × ${img.naturalHeight}px`;
        
        img.parentElement.style.position = 'relative';
        img.parentElement.appendChild(dimensions);
    }

    bindEvents() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('view-mode-btn')) {
                this.changeViewMode(e.target);
            }
        });

        // Add keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === '1') this.setMode('fit');
            if (e.key === '2') this.setMode('real');
            if (e.key === '3') this.setMode('fill');
        });
    }

    changeViewMode(button) {
        const mode = button.dataset.mode;
        const container = button.closest('.product-gallery, #lightbox');
        
        // Update button states
        container.querySelectorAll('.view-mode-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        button.classList.add('active');
        
        this.setMode(mode, container);
    }

    setMode(mode, container = null) {
        this.currentMode = mode;
        const containers = container ? [container] : document.querySelectorAll('.product-gallery, #lightbox');
        
        containers.forEach(cont => {
            const images = cont.querySelectorAll('img');
            
            images.forEach(img => {
                this.applyImageMode(img, mode);
            });
        });
    }

    applyImageMode(img, mode) {
        // Remove existing classes
        img.classList.remove('object-contain', 'object-cover', 'object-fill');
        img.style.removeProperty('width');
        img.style.removeProperty('height');
        img.style.removeProperty('max-width');
        img.style.removeProperty('max-height');

        switch (mode) {
            case 'fit':
                img.classList.add('object-contain');
                img.style.maxWidth = '100%';
                img.style.maxHeight = '100%';
                img.style.width = 'auto';
                img.style.height = 'auto';
                break;
                
            case 'real':
                img.style.width = 'auto';
                img.style.height = 'auto';
                img.style.maxWidth = 'none';
                img.style.maxHeight = 'none';
                this.centerImageInContainer(img);
                break;
                
            case 'fill':
                img.classList.add('object-cover');
                img.style.width = '100%';
                img.style.height = '100%';
                break;
        }
        
        this.addZoomIndicator(img, mode);
    }

    centerImageInContainer(img) {
        const container = img.parentElement;
        container.style.display = 'flex';
        container.style.alignItems = 'center';
        container.style.justifyContent = 'center';
        container.style.overflow = 'auto';
    }

    addZoomIndicator(img, mode) {
        // Remove existing indicator
        const existingIndicator = img.parentElement.querySelector('.zoom-indicator');
        if (existingIndicator) {
            existingIndicator.remove();
        }

        if (mode === 'real') {
            const indicator = document.createElement('div');
            indicator.className = 'zoom-indicator';
            indicator.textContent = '100% (Real Size)';
            img.parentElement.appendChild(indicator);
        }
    }

    getImageInfo(img) {
        return {
            naturalWidth: img.naturalWidth,
            naturalHeight: img.naturalHeight,
            displayWidth: img.offsetWidth,
            displayHeight: img.offsetHeight,
            aspectRatio: (img.naturalWidth / img.naturalHeight).toFixed(2),
            fileSize: this.getImageFileSize(img.src)
        };
    }

    async getImageFileSize(src) {
        try {
            const response = await fetch(src, { method: 'HEAD' });
            const size = response.headers.get('content-length');
            return size ? this.formatFileSize(parseInt(size)) : 'Unknown';
        } catch {
            return 'Unknown';
        }
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    showImageInfoPanel(img) {
        const info = this.getImageInfo(img);
        const panel = document.createElement('div');
        panel.className = 'image-info';
        panel.innerHTML = `
            <div class="image-info-item">
                <span>Original Size:</span>
                <span>${info.naturalWidth} × ${info.naturalHeight}px</span>
            </div>
            <div class="image-info-item">
                <span>Display Size:</span>
                <span>${info.displayWidth} × ${info.displayHeight}px</span>
            </div>
            <div class="image-info-item">
                <span>Aspect Ratio:</span>
                <span>${info.aspectRatio}:1</span>
            </div>
            <div class="image-info-item">
                <span>File Size:</span>
                <span id="file-size-${Date.now()}">Loading...</span>
            </div>
        `;
        
        return panel;
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new ImageViewer();
    
    // Ensure proper aspect ratio on page load
    setTimeout(() => {
        if (typeof ensureProperAspectRatio === 'function') {
            ensureProperAspectRatio();
        }
    }, 500);
});

// Add CSS styles dynamically
const style = document.createElement('style');
style.textContent = `
    .image-real-size {
        width: auto !important;
        height: auto !important;
        max-width: none !important;
        max-height: none !important;
        object-fit: none !important;
    }
    
    .image-container-scrollable {
        overflow: auto;
        max-height: 80vh;
        border: 1px solid #ddd;
        border-radius: 8px;
    }
`;
document.head.appendChild(style);
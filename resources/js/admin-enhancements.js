/**
 * Admin panel enhancements for better user experience
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize admin enhancements
    initializeDeleteConfirmations();
    initializeFormEnhancements();
    initializeTableEnhancements();
    initializeImagePreview();
});

/**
 * Add confirmation dialogs for delete operations
 */
function initializeDeleteConfirmations() {
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const itemName = this.getAttribute('data-item-name') || 'this item';
            const message = `Are you sure you want to delete ${itemName}? This action cannot be undone.`;
            
            if (confirm(message)) {
                // If it's a form, submit it
                const form = this.closest('form');
                if (form) {
                    form.submit();
                } else {
                    // If it's a link, follow it
                    window.location.href = this.href;
                }
            }
        });
    });
}

/**
 * Enhanced form handling for admin forms
 */
function initializeFormEnhancements() {
    const adminForms = document.querySelectorAll('form[data-admin-form]');
    
    adminForms.forEach(form => {
        // Add loading state on submit
        form.addEventListener('submit', function() {
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                addLoadingState(submitButton);
            }
            
            // Disable all form inputs to prevent double submission
            const inputs = form.querySelectorAll('input, textarea, select, button');
            inputs.forEach(input => {
                if (input.type !== 'submit') {
                    input.disabled = true;
                }
            });
        });
        
        // Auto-save draft functionality (optional)
        if (form.hasAttribute('data-auto-save')) {
            initializeAutoSave(form);
        }
    });
}

/**
 * Add loading state to button
 */
function addLoadingState(button) {
    const originalText = button.textContent;
    button.setAttribute('data-original-text', originalText);
    button.disabled = true;
    
    button.innerHTML = `
        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Processing...
    `;
}

/**
 * Remove loading state from button
 */
function removeLoadingState(button) {
    const originalText = button.getAttribute('data-original-text');
    if (originalText) {
        button.textContent = originalText;
        button.removeAttribute('data-original-text');
    }
    button.disabled = false;
}

/**
 * Enhanced table functionality
 */
function initializeTableEnhancements() {
    // Sortable columns
    const sortableHeaders = document.querySelectorAll('[data-sortable]');
    sortableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const column = this.getAttribute('data-sortable');
            const currentUrl = new URL(window.location);
            const currentSort = currentUrl.searchParams.get('sort');
            const currentDirection = currentUrl.searchParams.get('direction');
            
            let newDirection = 'asc';
            if (currentSort === column && currentDirection === 'asc') {
                newDirection = 'desc';
            }
            
            currentUrl.searchParams.set('sort', column);
            currentUrl.searchParams.set('direction', newDirection);
            window.location.href = currentUrl.toString();
        });
    });
    
    // Row selection
    const selectAllCheckbox = document.querySelector('#select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    
    if (selectAllCheckbox && rowCheckboxes.length > 0) {
        selectAllCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActions();
        });
        
        rowCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateSelectAllState();
                updateBulkActions();
            });
        });
    }
}

/**
 * Update select all checkbox state
 */
function updateSelectAllState() {
    const selectAllCheckbox = document.querySelector('#select-all');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    
    if (selectAllCheckbox && rowCheckboxes.length > 0) {
        const checkedCount = Array.from(rowCheckboxes).filter(cb => cb.checked).length;
        
        if (checkedCount === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedCount === rowCheckboxes.length) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }
    }
}

/**
 * Update bulk actions visibility
 */
function updateBulkActions() {
    const bulkActions = document.querySelector('.bulk-actions');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');
    
    if (bulkActions && rowCheckboxes.length > 0) {
        const checkedCount = Array.from(rowCheckboxes).filter(cb => cb.checked).length;
        
        if (checkedCount > 0) {
            bulkActions.classList.remove('hidden');
            const countElement = bulkActions.querySelector('.selected-count');
            if (countElement) {
                countElement.textContent = checkedCount;
            }
        } else {
            bulkActions.classList.add('hidden');
        }
    }
}

/**
 * Enhanced image preview functionality
 */
function initializeImagePreview() {
    const imageInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
    
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const previewContainer = document.querySelector(`[data-preview-for="${input.id}"]`);
            
            if (file && previewContainer) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let img = previewContainer.querySelector('img');
                    if (!img) {
                        img = document.createElement('img');
                        img.className = 'max-w-full h-auto rounded-lg';
                        previewContainer.appendChild(img);
                    }
                    img.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });
    });
}

/**
 * Auto-save functionality for forms
 */
function initializeAutoSave(form) {
    const formId = form.id || 'form-' + Date.now();
    const saveKey = `autosave-${formId}`;
    let saveTimeout;
    
    // Load saved data
    const savedData = localStorage.getItem(saveKey);
    if (savedData) {
        try {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(name => {
                const field = form.querySelector(`[name="${name}"]`);
                if (field && field.type !== 'file') {
                    field.value = data[name];
                }
            });
        } catch (e) {
            console.warn('Failed to load auto-saved data:', e);
        }
    }
    
    // Save data on input
    form.addEventListener('input', function(e) {
        if (e.target.type === 'file') return;
        
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            const formData = new FormData(form);
            const data = {};
            
            for (let [name, value] of formData.entries()) {
                if (name !== '_token' && name !== '_method') {
                    data[name] = value;
                }
            }
            
            localStorage.setItem(saveKey, JSON.stringify(data));
        }, 1000);
    });
    
    // Clear saved data on successful submit
    form.addEventListener('submit', function() {
        localStorage.removeItem(saveKey);
    });
}

/**
 * Show success notification
 */
function showSuccessNotification(message) {
    showNotification(message, 'success');
}

/**
 * Show error notification
 */
function showErrorNotification(message) {
    showNotification(message, 'error');
}

/**
 * Show notification
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const typeClasses = {
        success: 'bg-green-500 text-white',
        error: 'bg-red-500 text-white',
        warning: 'bg-yellow-500 text-black',
        info: 'bg-blue-500 text-white'
    };
    
    notification.className = `fixed top-4 right-4 ${typeClasses[type]} px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300`;
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
    
    document.body.appendChild(notification);
    
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
    }, 5000);
}

// Export functions for global use
window.AdminEnhancements = {
    showSuccessNotification,
    showErrorNotification,
    addLoadingState,
    removeLoadingState
};
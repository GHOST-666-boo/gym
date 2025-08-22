/**
 * Form feedback and loading states functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize form loading states
    initializeFormLoadingStates();
    
    // Initialize flash message handling
    initializeFlashMessages();
    
    // Initialize form validation feedback
    initializeFormValidation();
});

/**
 * Add loading states to forms
 */
function initializeFormLoadingStates() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
            
            if (submitButton && !form.hasAttribute('data-no-loading')) {
                // Disable the submit button
                submitButton.disabled = true;
                
                // Store original text
                const originalText = submitButton.textContent || submitButton.value;
                submitButton.setAttribute('data-original-text', originalText);
                
                // Add loading text and spinner
                if (submitButton.tagName === 'BUTTON') {
                    submitButton.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Processing...
                    `;
                } else {
                    submitButton.value = 'Processing...';
                }
                
                // Add loading class to form
                form.classList.add('form-loading');
                
                // Re-enable button after timeout (fallback)
                setTimeout(() => {
                    resetFormButton(submitButton);
                    form.classList.remove('form-loading');
                }, 30000); // 30 seconds timeout
            }
        });
    });
}

/**
 * Reset form button to original state
 */
function resetFormButton(button) {
    const originalText = button.getAttribute('data-original-text');
    if (originalText) {
        if (button.tagName === 'BUTTON') {
            button.innerHTML = originalText;
        } else {
            button.value = originalText;
        }
        button.removeAttribute('data-original-text');
    }
    button.disabled = false;
}

/**
 * Handle flash message interactions
 */
function initializeFlashMessages() {
    // Auto-hide flash messages after 5 seconds
    const flashMessages = document.querySelectorAll('.flash-message');
    flashMessages.forEach(message => {
        setTimeout(() => {
            hideFlashMessage(message);
        }, 5000);
    });
    
    // Handle close button clicks
    const closeButtons = document.querySelectorAll('.flash-close');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const message = this.closest('.flash-message');
            hideFlashMessage(message);
        });
    });
}

/**
 * Hide flash message with animation
 */
function hideFlashMessage(message) {
    if (message) {
        message.style.opacity = '0';
        message.style.transform = 'translateY(-10px)';
        message.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        
        setTimeout(() => {
            message.remove();
        }, 300);
    }
}

/**
 * Initialize form validation feedback
 */
function initializeFormValidation() {
    // Real-time validation for common fields
    const emailInputs = document.querySelectorAll('input[type="email"]');
    const requiredInputs = document.querySelectorAll('input[required], textarea[required], select[required]');
    
    emailInputs.forEach(input => {
        input.addEventListener('blur', validateEmail);
        input.addEventListener('input', clearValidationError);
    });
    
    requiredInputs.forEach(input => {
        input.addEventListener('blur', validateRequired);
        input.addEventListener('input', clearValidationError);
    });
}

/**
 * Validate email format
 */
function validateEmail(e) {
    const input = e.target;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (input.value && !emailRegex.test(input.value)) {
        showFieldError(input, 'Please enter a valid email address.');
    }
}

/**
 * Validate required fields
 */
function validateRequired(e) {
    const input = e.target;
    
    if (!input.value.trim()) {
        showFieldError(input, 'This field is required.');
    }
}

/**
 * Show field validation error
 */
function showFieldError(input, message) {
    clearValidationError({ target: input });
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error text-red-600 text-sm mt-1';
    errorDiv.textContent = message;
    errorDiv.setAttribute('data-field-error', input.name || input.id);
    
    input.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
    input.parentNode.appendChild(errorDiv);
}

/**
 * Clear field validation error
 */
function clearValidationError(e) {
    const input = e.target;
    const fieldName = input.name || input.id;
    const existingError = document.querySelector(`[data-field-error="${fieldName}"]`);
    
    if (existingError) {
        existingError.remove();
    }
    
    input.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
}

/**
 * Show success message
 */
function showSuccessMessage(message, container = null) {
    const successDiv = document.createElement('div');
    successDiv.className = 'flash-message bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4';
    successDiv.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>${message}</span>
            </div>
            <button type="button" class="flash-close text-green-700 hover:text-green-900" aria-label="Close">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    
    const targetContainer = container || document.querySelector('.flash-messages-container') || document.querySelector('main');
    if (targetContainer) {
        targetContainer.insertBefore(successDiv, targetContainer.firstChild);
        
        // Add close functionality
        const closeButton = successDiv.querySelector('.flash-close');
        closeButton.addEventListener('click', () => hideFlashMessage(successDiv));
        
        // Auto-hide after 5 seconds
        setTimeout(() => hideFlashMessage(successDiv), 5000);
    }
}

/**
 * Show error message
 */
function showErrorMessage(message, container = null) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'flash-message bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4';
    errorDiv.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>${message}</span>
            </div>
            <button type="button" class="flash-close text-red-700 hover:text-red-900" aria-label="Close">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    
    const targetContainer = container || document.querySelector('.flash-messages-container') || document.querySelector('main');
    if (targetContainer) {
        targetContainer.insertBefore(errorDiv, targetContainer.firstChild);
        
        // Add close functionality
        const closeButton = errorDiv.querySelector('.flash-close');
        closeButton.addEventListener('click', () => hideFlashMessage(errorDiv));
        
        // Auto-hide after 8 seconds (longer for errors)
        setTimeout(() => hideFlashMessage(errorDiv), 8000);
    }
}

// Export functions for use in other scripts
window.FormFeedback = {
    showSuccessMessage,
    showErrorMessage,
    hideFlashMessage,
    resetFormButton
};
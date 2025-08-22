<footer class="bg-gray-900 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Company Info -->
            <div class="col-span-1 md:col-span-2">
                <div class="flex items-center mb-4">
                    <div class="h-8 w-8 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                        <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <span class="text-xl font-bold">Gym Machines</span>
                </div>
                <p class="text-gray-300 mb-4 max-w-md">
                    Your trusted partner for professional fitness equipment. We provide high-quality gym machines 
                    for commercial and home gyms, helping you achieve your fitness goals with reliable, 
                    durable equipment.
                </p>
                
                <!-- Newsletter Signup -->
                <div class="mb-6">
                    <h4 class="text-lg font-semibold mb-3">Stay Updated</h4>
                    <p class="text-gray-300 text-sm mb-3">
                        Subscribe to our newsletter for the latest fitness equipment updates and exclusive offers.
                    </p>
                    <form id="newsletter-form" class="flex flex-col sm:flex-row gap-2">
                        @csrf
                        <div class="flex-1">
                            <input type="email" 
                                   id="newsletter-email" 
                                   name="email" 
                                   placeholder="Enter your email" 
                                   required
                                   class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <input type="text" 
                                   id="newsletter-name" 
                                   name="name" 
                                   placeholder="Your name (optional)" 
                                   class="w-full px-3 py-2 mt-2 bg-gray-800 border border-gray-700 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <button type="submit" 
                                id="newsletter-submit"
                                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span class="newsletter-submit-text">Subscribe</span>
                            <span class="newsletter-loading hidden">
                                <svg class="animate-spin h-4 w-4 inline" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                    </form>
                    <div id="newsletter-message" class="mt-2 text-sm hidden"></div>
                </div>
                
                <div class="flex space-x-4">
                    <!-- Social Media Links (placeholder) -->
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z"/>
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                <ul class="space-y-2">
                    <li>
                        <a href="{{ route('home') }}" class="text-gray-300 hover:text-white transition-colors duration-200">
                            Home
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('products.index') }}" class="text-gray-300 hover:text-white transition-colors duration-200">
                            All Products
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('contact') }}" class="text-gray-300 hover:text-white transition-colors duration-200">
                            Contact Us
                        </a>
                    </li>
                    @auth
                    <li>
                        <a href="{{ route('admin.dashboard') }}" class="text-gray-300 hover:text-white transition-colors duration-200">
                            Admin Panel
                        </a>
                    </li>
                    @endauth
                </ul>
            </div>

            <!-- Contact Info -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Contact Info</h3>
                <div class="space-y-2 text-gray-300">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>info@gymmachines.com</span>
                    </div>
                    <div class="flex items-center">
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        <span>+1 (555) 123-4567</span>
                    </div>
                    <div class="flex items-start">
                        <svg class="h-5 w-5 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>123 Fitness Street<br>Gym City, GC 12345</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="border-t border-gray-800 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
            <div class="text-gray-400 text-sm">
                © {{ date('Y') }} Gym Machines. All rights reserved.
            </div>
            <div class="flex space-x-6 mt-4 md:mt-0">
                <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors duration-200">
                    Privacy Policy
                </a>
                <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors duration-200">
                    Terms of Service
                </a>
                <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors duration-200">
                    Cookie Policy
                </a>
            </div>
        </div>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const newsletterForm = document.getElementById('newsletter-form');
    const submitButton = document.getElementById('newsletter-submit');
    const submitText = submitButton.querySelector('.newsletter-submit-text');
    const loadingSpinner = submitButton.querySelector('.newsletter-loading');
    const messageDiv = document.getElementById('newsletter-message');
    const emailInput = document.getElementById('newsletter-email');
    const nameInput = document.getElementById('newsletter-name');

    if (newsletterForm) {
        newsletterForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Reset previous states
            messageDiv.classList.add('hidden');
            messageDiv.className = 'mt-2 text-sm hidden';
            
            // Show loading state
            submitButton.disabled = true;
            submitText.classList.add('hidden');
            loadingSpinner.classList.remove('hidden');
            
            try {
                const formData = new FormData();
                formData.append('email', emailInput.value);
                formData.append('name', nameInput.value);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                
                const response = await fetch('{{ route("newsletter.subscribe") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                // Show message
                messageDiv.textContent = data.message;
                messageDiv.classList.remove('hidden');
                
                if (data.success) {
                    messageDiv.classList.add('text-green-400');
                    // Reset form on success
                    emailInput.value = '';
                    nameInput.value = '';
                } else {
                    messageDiv.classList.add('text-red-400');
                }
                
            } catch (error) {
                console.error('Newsletter subscription error:', error);
                messageDiv.textContent = 'An error occurred. Please try again later.';
                messageDiv.classList.remove('hidden');
                messageDiv.classList.add('text-red-400');
            } finally {
                // Reset button state
                submitButton.disabled = false;
                submitText.classList.remove('hidden');
                loadingSpinner.classList.add('hidden');
            }
        });
    }
});
</script>
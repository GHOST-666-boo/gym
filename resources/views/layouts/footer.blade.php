<footer class="bg-gray-900 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Company Info -->
            <div class="col-span-1 md:col-span-2">
                <div class="flex items-center mb-4">
                    @if(site_logo() && site_logo() !== asset('images/default-logo.png'))
                        <img src="{{ site_logo() }}" alt="{{ site_name() }} Logo" class="h-8 w-auto mr-3">
                    @else
                        <div class="h-8 w-8 bg-blue-600 rounded-lg flex items-center justify-center mr-3">
                            <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                    @endif
                    <span class="text-xl font-bold">{{ site_name() }}</span>
                </div>
                <p class="text-gray-300 mb-4 max-w-md">
                    {{ site_tagline() ?: 'Your trusted partner for professional fitness equipment. We provide high-quality gym machines for commercial and home gyms, helping you achieve your fitness goals with reliable, durable equipment.' }}
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
                    <!-- Social Media Links -->
                    @if(facebook_url())
                        <a href="{{ facebook_url() }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition-colors duration-200" aria-label="Facebook">
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                    @endif
                    
                    @if(instagram_url())
                        <a href="{{ instagram_url() }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition-colors duration-200" aria-label="Instagram">
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987 6.62 0 11.987-5.367 11.987-11.987C24.014 5.367 18.637.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.49-3.323-1.297C4.198 14.895 3.708 13.744 3.708 12.447s.49-2.448 1.418-3.323c.875-.807 2.026-1.297 3.323-1.297s2.448.49 3.323 1.297c.928.875 1.418 2.026 1.418 3.323s-.49 2.448-1.418 3.244c-.875.807-2.026 1.297-3.323 1.297zm7.83-9.404h-1.297V6.287h1.297v1.297zm-1.297 2.448c-.875-.807-2.026-1.297-3.323-1.297s-2.448.49-3.323 1.297c-.928.875-1.418 2.026-1.418 3.323s.49 2.448 1.418 3.244c.875.807 2.026 1.297 3.323 1.297s2.448-.49 3.323-1.297c.928-.796 1.418-1.947 1.418-3.244s-.49-2.448-1.418-3.323z"/>
                            </svg>
                        </a>
                    @endif
                    
                    @if(twitter_url())
                        <a href="{{ twitter_url() }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition-colors duration-200" aria-label="Twitter">
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                    @endif
                    
                    @if(youtube_url())
                        <a href="{{ youtube_url() }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition-colors duration-200" aria-label="YouTube">
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                            </svg>
                        </a>
                    @endif
                    
                    @if(linkedin_url())
                        <a href="{{ linkedin_url() }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition-colors duration-200" aria-label="LinkedIn">
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                            </svg>
                        </a>
                    @endif
                    
                    @if(tiktok_url())
                        <a href="{{ tiktok_url() }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-white transition-colors duration-200" aria-label="TikTok">
                            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                            </svg>
                        </a>
                    @endif
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
                    @if(business_email())
                        <div class="flex items-center">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <a href="mailto:{{ business_email() }}" class="hover:text-white transition-colors duration-200">{{ business_email() }}</a>
                        </div>
                    @endif
                    
                    @if(business_phone())
                        <div class="flex items-center">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                            <a href="tel:{{ business_phone() }}" class="hover:text-white transition-colors duration-200">{{ business_phone() }}</a>
                        </div>
                    @endif
                    
                    @if(business_address())
                        <div class="flex items-start">
                            <svg class="h-5 w-5 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>{!! nl2br(e(business_address())) !!}</span>
                        </div>
                    @endif
                    
                    @if(business_hours())
                        <div class="flex items-start">
                            <svg class="h-5 w-5 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{!! nl2br(e(business_hours())) !!}</span>
                        </div>
                    @endif
                    
                    @if(!business_email() && !business_phone() && !business_address())
                        <!-- Fallback contact info if no settings are configured -->
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
                    @endif
                </div>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="border-t border-gray-800 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
            <div class="text-gray-400 text-sm">
                © {{ date('Y') }} {{ site_name() }}. All rights reserved.
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
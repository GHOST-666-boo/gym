<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ site_name() }} - Under Maintenance</title>
    <meta name="description" content="We're currently performing scheduled maintenance. Please check back soon.">
    
    <!-- Favicon -->
    @if(site_favicon())
        <link rel="icon" type="image/x-icon" href="{{ site_favicon() }}">
    @endif
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        .maintenance-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .pulse-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .5;
            }
        }
        
        .bounce-animation {
            animation: bounce 1s infinite;
        }
        
        @keyframes bounce {
            0%, 100% {
                transform: translateY(-25%);
                animation-timing-function: cubic-bezier(0.8,0,1,1);
            }
            50% {
                transform: none;
                animation-timing-function: cubic-bezier(0,0,0.2,1);
            }
        }
    </style>
</head>
<body class="maintenance-bg min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-2xl shadow-2xl p-8 text-center">
            <!-- Logo -->
            @if(site_logo())
                <div class="mb-6">
                    <img src="{{ site_logo() }}" 
                         alt="{{ site_name() }}" 
                         class="h-16 mx-auto">
                </div>
            @endif
            
            <!-- Maintenance Icon -->
            <div class="mb-6">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-yellow-100 rounded-full mb-4">
                    <svg class="w-10 h-10 text-yellow-600 bounce-animation" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
            </div>
            
            <!-- Title -->
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Under Maintenance</h1>
            
            <!-- Description -->
            <p class="text-gray-600 mb-6 leading-relaxed">
                We're currently performing scheduled maintenance to improve your experience. 
                We'll be back online shortly!
            </p>
            
            <!-- Progress Bar -->
            <div class="mb-6">
                <div class="bg-gray-200 rounded-full h-2 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-full rounded-full pulse-animation" style="width: 75%"></div>
                </div>
                <p class="text-sm text-gray-500 mt-2">Maintenance in progress...</p>
            </div>
            
            <!-- Contact Information -->
            @if(business_email() || business_phone())
                <div class="border-t border-gray-200 pt-6">
                    <p class="text-sm text-gray-600 mb-3">Need immediate assistance?</p>
                    <div class="space-y-2">
                        @if(business_email())
                            <div class="flex items-center justify-center text-sm text-gray-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <a href="mailto:{{ business_email() }}" class="hover:text-blue-600 transition-colors">
                                    {{ business_email() }}
                                </a>
                            </div>
                        @endif
                        
                        @if(business_phone())
                            <div class="flex items-center justify-center text-sm text-gray-700">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg>
                                <a href="tel:{{ business_phone() }}" class="hover:text-blue-600 transition-colors">
                                    {{ business_phone() }}
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
            
            <!-- Social Media Links -->
            @if(facebook_url() || instagram_url() || twitter_url() || youtube_url())
                <div class="border-t border-gray-200 pt-6 mt-6">
                    <p class="text-sm text-gray-600 mb-3">Follow us for updates:</p>
                    <div class="flex justify-center space-x-4">
                        @if(facebook_url())
                            <a href="{{ facebook_url() }}" target="_blank" rel="noopener noreferrer" 
                               class="text-gray-400 hover:text-blue-600 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </a>
                        @endif
                        
                        @if(instagram_url())
                            <a href="{{ instagram_url() }}" target="_blank" rel="noopener noreferrer" 
                               class="text-gray-400 hover:text-pink-600 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987 6.62 0 11.987-5.367 11.987-11.987C24.014 5.367 18.637.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.49-3.323-1.297C4.198 14.895 3.708 13.744 3.708 12.447s.49-2.448 1.297-3.323c.875-.807 2.026-1.297 3.323-1.297s2.448.49 3.323 1.297c.807.875 1.297 2.026 1.297 3.323s-.49 2.448-1.297 3.323c-.875.807-2.026 1.297-3.323 1.297z"/>
                                </svg>
                            </a>
                        @endif
                        
                        @if(twitter_url())
                            <a href="{{ twitter_url() }}" target="_blank" rel="noopener noreferrer" 
                               class="text-gray-400 hover:text-blue-400 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                </svg>
                            </a>
                        @endif
                        
                        @if(youtube_url())
                            <a href="{{ youtube_url() }}" target="_blank" rel="noopener noreferrer" 
                               class="text-gray-400 hover:text-red-600 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-6">
            <p class="text-white text-sm opacity-75">
                © {{ date('Y') }} {{ site_name() }}. All rights reserved.
            </p>
        </div>
    </div>
    
    <!-- Auto-refresh script -->
    <script>
        // Auto-refresh the page every 30 seconds to check if maintenance is over
        setTimeout(function() {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
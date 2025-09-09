<div class="space-y-6">
    <div>
        <h3 class="text-lg font-medium text-gray-900 mb-4">Social Media</h3>
        <p class="text-sm text-gray-600 mb-6">Add your social media profiles to display links in your website footer and contact sections.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Facebook -->
        <div>
            <label for="facebook_url" class="block text-sm font-medium text-gray-700 mb-2">
                Facebook Page URL
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </div>
                <input type="url" 
                       id="facebook_url" 
                       name="facebook_url" 
                       value="{{ old('facebook_url', $settings['facebook_url'] ?? '') }}"
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('facebook_url') border-red-500 @enderror"
                       placeholder="https://facebook.com/yourpage">
            </div>
            @error('facebook_url')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Full URL to your Facebook business page.</p>
        </div>

        <!-- Instagram -->
        <div>
            <label for="instagram_url" class="block text-sm font-medium text-gray-700 mb-2">
                Instagram Profile URL
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-pink-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 6.62 5.367 11.987 11.988 11.987 6.62 0 11.987-5.367 11.987-11.987C24.014 5.367 18.637.001 12.017.001zM8.449 16.988c-1.297 0-2.448-.49-3.323-1.297C4.198 14.895 3.708 13.744 3.708 12.447s.49-2.448 1.418-3.323C6.001 8.198 7.152 7.708 8.449 7.708s2.448.49 3.323 1.416c.875.875 1.365 2.026 1.365 3.323s-.49 2.448-1.365 3.323c-.875.807-2.026 1.218-3.323 1.218zm7.718-1.297c-.875.807-2.026 1.297-3.323 1.297s-2.448-.49-3.323-1.297c-.875-.875-1.365-2.026-1.365-3.323s.49-2.448 1.365-3.323c.875-.926 2.026-1.416 3.323-1.416s2.448.49 3.323 1.416c.875.875 1.365 2.026 1.365 3.323s-.49 2.448-1.365 3.323z"/>
                    </svg>
                </div>
                <input type="url" 
                       id="instagram_url" 
                       name="instagram_url" 
                       value="{{ old('instagram_url', $settings['instagram_url'] ?? '') }}"
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('instagram_url') border-red-500 @enderror"
                       placeholder="https://instagram.com/yourusername">
            </div>
            @error('instagram_url')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Full URL to your Instagram business profile.</p>
        </div>

        <!-- Twitter -->
        <div>
            <label for="twitter_url" class="block text-sm font-medium text-gray-700 mb-2">
                Twitter Profile URL
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                    </svg>
                </div>
                <input type="url" 
                       id="twitter_url" 
                       name="twitter_url" 
                       value="{{ old('twitter_url', $settings['twitter_url'] ?? '') }}"
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('twitter_url') border-red-500 @enderror"
                       placeholder="https://twitter.com/yourusername">
            </div>
            @error('twitter_url')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Full URL to your Twitter business profile.</p>
        </div>

        <!-- YouTube -->
        <div>
            <label for="youtube_url" class="block text-sm font-medium text-gray-700 mb-2">
                YouTube Channel URL
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                </div>
                <input type="url" 
                       id="youtube_url" 
                       name="youtube_url" 
                       value="{{ old('youtube_url', $settings['youtube_url'] ?? '') }}"
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('youtube_url') border-red-500 @enderror"
                       placeholder="https://youtube.com/channel/yourchannel">
            </div>
            @error('youtube_url')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Full URL to your YouTube business channel.</p>
        </div>

        <!-- LinkedIn -->
        <div>
            <label for="linkedin_url" class="block text-sm font-medium text-gray-700 mb-2">
                LinkedIn Profile URL
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-blue-700" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                    </svg>
                </div>
                <input type="url" 
                       id="linkedin_url" 
                       name="linkedin_url" 
                       value="{{ old('linkedin_url', $settings['linkedin_url'] ?? '') }}"
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('linkedin_url') border-red-500 @enderror"
                       placeholder="https://linkedin.com/company/yourcompany">
            </div>
            @error('linkedin_url')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Full URL to your LinkedIn business page.</p>
        </div>

        <!-- TikTok -->
        <div>
            <label for="tiktok_url" class="block text-sm font-medium text-gray-700 mb-2">
                TikTok Profile URL
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-black" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
                    </svg>
                </div>
                <input type="url" 
                       id="tiktok_url" 
                       name="tiktok_url" 
                       value="{{ old('tiktok_url', $settings['tiktok_url'] ?? '') }}"
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('tiktok_url') border-red-500 @enderror"
                       placeholder="https://tiktok.com/@yourusername">
            </div>
            @error('tiktok_url')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Full URL to your TikTok business profile.</p>
        </div>
    </div>

    <!-- Social Media Display Settings -->
    <div class="border-t border-gray-200 pt-6">
        <h4 class="text-md font-medium text-gray-900 mb-4">Display Settings</h4>
        
        <div class="space-y-4">
            <!-- Show Social Icons in Footer -->
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <label for="show_social_footer" class="block text-sm font-medium text-gray-700">
                        Show Social Icons in Footer
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Display social media icons in the website footer</p>
                </div>
                <div class="ml-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="show_social_footer" value="0">
                        <input type="checkbox" 
                               id="show_social_footer"
                               name="show_social_footer" 
                               value="1"
                               {{ old('show_social_footer', (bool)($settings['show_social_footer'] ?? true)) ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>

            <!-- Show Social Icons in Contact Page -->
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <label for="show_social_contact" class="block text-sm font-medium text-gray-700">
                        Show Social Icons in Contact Page
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Display social media icons on the contact page</p>
                </div>
                <div class="ml-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="show_social_contact" value="0">
                        <input type="checkbox" 
                               id="show_social_contact"
                               name="show_social_contact" 
                               value="1"
                               {{ old('show_social_contact', (bool)($settings['show_social_contact'] ?? true)) ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>

            <!-- Open Links in New Tab -->
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <label for="social_links_new_tab" class="block text-sm font-medium text-gray-700">
                        Open Social Links in New Tab
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Social media links will open in a new browser tab</p>
                </div>
                <div class="ml-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="social_links_new_tab" value="0">
                        <input type="checkbox" 
                               id="social_links_new_tab"
                               name="social_links_new_tab" 
                               value="1"
                               {{ old('social_links_new_tab', (bool)($settings['social_links_new_tab'] ?? true)) ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
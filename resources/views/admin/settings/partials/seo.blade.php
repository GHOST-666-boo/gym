<div class="space-y-6">
    <div>
        <h3 class="text-lg font-medium text-gray-900 mb-4">SEO Settings</h3>
        <p class="text-sm text-gray-600 mb-6">Configure search engine optimization settings to improve your website's visibility in search results.</p>
    </div>

    <div class="grid grid-cols-1 gap-6">
        <!-- Default Meta Title -->
        <div>
            <label for="default_meta_title" class="block text-sm font-medium text-gray-700 mb-2">
                Default Meta Title
            </label>
            <div class="relative">
                <input type="text" 
                       id="default_meta_title" 
                       name="default_meta_title" 
                       value="{{ old('default_meta_title', $settings['default_meta_title'] ?? '') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('default_meta_title') border-red-500 @enderror"
                       placeholder="Your Website Name - Professional Gym Equipment"
                       maxlength="60">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span id="meta-title-count" class="text-xs text-gray-400">0/60</span>
                </div>
            </div>
            @error('default_meta_title')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">This appears in search results and browser tabs. Keep it under 60 characters for best results.</p>
        </div>

        <!-- Default Meta Description -->
        <div>
            <label for="default_meta_description" class="block text-sm font-medium text-gray-700 mb-2">
                Default Meta Description
            </label>
            <div class="relative">
                <textarea id="default_meta_description" 
                          name="default_meta_description" 
                          rows="3"
                          maxlength="160"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('default_meta_description') border-red-500 @enderror"
                          placeholder="Discover premium gym equipment for your fitness journey. Quality machines, competitive prices, and expert support.">{{ old('default_meta_description', $settings['default_meta_description'] ?? '') }}</textarea>
                <div class="absolute bottom-2 right-2 pointer-events-none">
                    <span id="meta-description-count" class="text-xs text-gray-400">0/160</span>
                </div>
            </div>
            @error('default_meta_description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">This appears in search results below the title. Keep it under 160 characters for best results.</p>
        </div>

        <!-- Meta Keywords -->
        <div>
            <label for="meta_keywords" class="block text-sm font-medium text-gray-700 mb-2">
                Meta Keywords
            </label>
            <input type="text" 
                   id="meta_keywords" 
                   name="meta_keywords" 
                   value="{{ old('meta_keywords', $settings['meta_keywords'] ?? '') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('meta_keywords') border-red-500 @enderror"
                   placeholder="gym equipment, fitness machines, exercise equipment, commercial gym">
            @error('meta_keywords')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Comma-separated keywords relevant to your business. While less important for modern SEO, some search engines still use them.</p>
        </div>

        <!-- Favicon Upload -->
        <div>
            <label for="favicon" class="block text-sm font-medium text-gray-700 mb-2">
                Favicon
            </label>
            <div class="space-y-3">
                <!-- Current Favicon Display -->
                @if(isset($settings['favicon_path']) && $settings['favicon_path'])
                    <div class="flex items-center space-x-3">
                        <img src="{{ asset('storage/' . $settings['favicon_path']) }}" 
                             alt="Current Favicon" 
                             class="h-8 w-8 object-contain border border-gray-200 rounded">
                        <span class="text-sm text-gray-600">Current favicon</span>
                    </div>
                @endif
                
                <!-- File Input -->
                <div class="flex items-center space-x-3">
                    <input type="file" 
                           id="favicon" 
                           name="favicon" 
                           accept="image/*,.ico"
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 @error('favicon') border-red-500 @enderror">
                </div>
                
                <!-- Preview -->
                <img id="favicon-preview" 
                     class="hidden h-8 w-8 object-contain border border-gray-200 rounded" 
                     alt="Favicon Preview">
                
                @error('favicon')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="text-xs text-gray-500">Small icon that appears in browser tabs and bookmarks. Recommended size: 32x32px or 16x16px. Supported formats: ICO, PNG (max 1MB)</p>
            </div>
        </div>
    </div>

    <!-- Advanced SEO Settings -->
    <div class="border-t border-gray-200 pt-6">
        <h4 class="text-md font-medium text-gray-900 mb-4">Advanced SEO Settings</h4>
        
        <div class="space-y-6">
            <!-- Open Graph Settings -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h5 class="text-sm font-medium text-gray-900 mb-3">Open Graph (Social Media Sharing)</h5>
                <div class="space-y-4">
                    <!-- OG Title -->
                    <div>
                        <label for="og_title" class="block text-sm font-medium text-gray-700 mb-2">
                            Open Graph Title
                        </label>
                        <input type="text" 
                               id="og_title" 
                               name="og_title" 
                               value="{{ old('og_title', $settings['og_title'] ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('og_title') border-red-500 @enderror"
                               placeholder="Leave empty to use default meta title">
                        @error('og_title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Title that appears when your site is shared on social media.</p>
                    </div>

                    <!-- OG Description -->
                    <div>
                        <label for="og_description" class="block text-sm font-medium text-gray-700 mb-2">
                            Open Graph Description
                        </label>
                        <textarea id="og_description" 
                                  name="og_description" 
                                  rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('og_description') border-red-500 @enderror"
                                  placeholder="Leave empty to use default meta description">{{ old('og_description', $settings['og_description'] ?? '') }}</textarea>
                        @error('og_description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Description that appears when your site is shared on social media.</p>
                    </div>

                    <!-- OG Image -->
                    <div>
                        <label for="og_image" class="block text-sm font-medium text-gray-700 mb-2">
                            Open Graph Image
                        </label>
                        <div class="space-y-3">
                            <!-- Current OG Image Display -->
                            @if(isset($settings['og_image_path']) && $settings['og_image_path'])
                                <div class="flex items-center space-x-3">
                                    <img src="{{ asset('storage/' . $settings['og_image_path']) }}" 
                                         alt="Current OG Image" 
                                         class="h-16 w-auto object-contain border border-gray-200 rounded">
                                    <span class="text-sm text-gray-600">Current social sharing image</span>
                                </div>
                            @endif
                            
                            <!-- File Input -->
                            <input type="file" 
                                   id="og_image" 
                                   name="og_image" 
                                   accept="image/*"
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 @error('og_image') border-red-500 @enderror">
                            
                            @error('og_image')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-500">Image that appears when your site is shared on social media. Recommended size: 1200x630px. Supported formats: JPG, PNG (max 5MB)</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Engine Settings -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Allow Search Engine Indexing -->
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <label for="allow_indexing" class="block text-sm font-medium text-gray-700">
                            Allow Search Engine Indexing
                        </label>
                        <p class="text-xs text-gray-500 mt-1">Allow search engines to index and display your website in search results</p>
                    </div>
                    <div class="ml-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="allow_indexing" value="0">
                            <input type="checkbox" 
                                   id="allow_indexing"
                                   name="allow_indexing" 
                                   value="1"
                                   {{ old('allow_indexing', $settings['allow_indexing'] ?? true) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Generate Sitemap -->
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <label for="generate_sitemap" class="block text-sm font-medium text-gray-700">
                            Generate XML Sitemap
                        </label>
                        <p class="text-xs text-gray-500 mt-1">Automatically generate and update XML sitemap for search engines</p>
                    </div>
                    <div class="ml-4">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="generate_sitemap" value="0">
                            <input type="checkbox" 
                                   id="generate_sitemap"
                                   name="generate_sitemap" 
                                   value="1"
                                   {{ old('generate_sitemap', $settings['generate_sitemap'] ?? true) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Analytics and Tracking -->
            <div>
                <h5 class="text-sm font-medium text-gray-900 mb-3">Analytics and Tracking</h5>
                <div class="space-y-4">
                    <!-- Google Analytics ID -->
                    <div>
                        <label for="google_analytics_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Google Analytics Measurement ID
                        </label>
                        <input type="text" 
                               id="google_analytics_id" 
                               name="google_analytics_id" 
                               value="{{ old('google_analytics_id', $settings['google_analytics_id'] ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('google_analytics_id') border-red-500 @enderror"
                               placeholder="G-XXXXXXXXXX">
                        @error('google_analytics_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Your Google Analytics 4 Measurement ID (starts with G-).</p>
                    </div>

                    <!-- Google Search Console -->
                    <div>
                        <label for="google_site_verification" class="block text-sm font-medium text-gray-700 mb-2">
                            Google Search Console Verification Code
                        </label>
                        <input type="text" 
                               id="google_site_verification" 
                               name="google_site_verification" 
                               value="{{ old('google_site_verification', $settings['google_site_verification'] ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('google_site_verification') border-red-500 @enderror"
                               placeholder="Enter verification code only (without meta tag)">
                        @error('google_site_verification')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Verification code from Google Search Console (content attribute value only).</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counters
    function setupCharacterCounter(inputId, counterId, maxLength) {
        const input = document.getElementById(inputId);
        const counter = document.getElementById(counterId);
        
        if (input && counter) {
            function updateCounter() {
                const length = input.value.length;
                counter.textContent = length + '/' + maxLength;
                
                if (length > maxLength * 0.9) {
                    counter.classList.add('text-red-500');
                    counter.classList.remove('text-gray-400');
                } else {
                    counter.classList.add('text-gray-400');
                    counter.classList.remove('text-red-500');
                }
            }
            
            input.addEventListener('input', updateCounter);
            updateCounter(); // Initial count
        }
    }
    
    setupCharacterCounter('default_meta_title', 'meta-title-count', 60);
    setupCharacterCounter('default_meta_description', 'meta-description-count', 160);
});
</script>
@endpush
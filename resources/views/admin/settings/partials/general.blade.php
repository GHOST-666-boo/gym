<div class="space-y-6">
    <div>
        <h3 class="text-lg font-medium text-gray-900 mb-4">General Settings</h3>
        <p class="text-sm text-gray-600 mb-6">Configure your website's basic information and branding.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Site Name -->
        <div class="lg:col-span-2">
            <label for="site_name" class="block text-sm font-medium text-gray-700 mb-2">
                Site Name <span class="text-red-500">*</span>
            </label>
            <input type="text" 
                   id="site_name" 
                   name="site_name" 
                   value="{{ old('site_name', $settings['site_name'] ?? 'Gym Machines') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('site_name') border-red-500 @enderror"
                   placeholder="Enter your website name"
                   required>
            @error('site_name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">This will appear in the browser title and throughout your website.</p>
        </div>

        <!-- Site Tagline -->
        <div class="lg:col-span-2">
            <label for="site_tagline" class="block text-sm font-medium text-gray-700 mb-2">
                Site Tagline
            </label>
            <input type="text" 
                   id="site_tagline" 
                   name="site_tagline" 
                   value="{{ old('site_tagline', $settings['site_tagline'] ?? '') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('site_tagline') border-red-500 @enderror"
                   placeholder="Enter a brief description of your website">
            @error('site_tagline')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">A short description that appears in search results and page headers.</p>
        </div>

        <!-- Logo Upload -->
        <div>
            <label for="logo" class="block text-sm font-medium text-gray-700 mb-2">
                Site Logo
            </label>
            <div class="space-y-3">
                <!-- Current Logo Display -->
                <div id="current-logo-display">
                    @if(isset($settings['logo_path']) && $settings['logo_path'])
                        <div class="flex items-center space-x-3">
                            <img src="{{ asset('storage/' . $settings['logo_path']) }}" 
                                 alt="Current Logo" 
                                 class="h-12 w-auto object-contain border border-gray-200 rounded">
                            <span class="text-sm text-gray-600">Current logo</span>
                        </div>
                    @endif
                </div>
                
                <!-- File Input -->
                <div class="flex items-center space-x-3">
                    <input type="file" 
                           id="logo-upload" 
                           accept="image/*"
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <button type="button" 
                            id="upload-logo-btn" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                            disabled>
                        Upload Logo
                    </button>
                </div>
                
                <!-- Upload Status -->
                <div id="logo-upload-status" class="hidden"></div>
                
                <!-- Preview -->
                <img id="logo-preview" 
                     class="hidden h-12 w-auto object-contain border border-gray-200 rounded" 
                     alt="Logo Preview">
                
                <p class="text-xs text-gray-500">Recommended size: 200x60px. Supported formats: JPG, PNG, SVG (max 5MB)</p>
            </div>
        </div>

        <!-- Favicon Upload -->
        <div>
            <label for="favicon" class="block text-sm font-medium text-gray-700 mb-2">
                Favicon
            </label>
            <div class="space-y-3">
                <!-- Current Favicon Display -->
                <div id="current-favicon-display">
                    @if(isset($settings['favicon_path']) && $settings['favicon_path'])
                        <div class="flex items-center space-x-3">
                            <img src="{{ asset('storage/' . $settings['favicon_path']) }}" 
                                 alt="Current Favicon" 
                                 class="h-8 w-8 object-contain border border-gray-200 rounded">
                            <span class="text-sm text-gray-600">Current favicon</span>
                        </div>
                    @endif
                </div>
                
                <!-- File Input -->
                <div class="flex items-center space-x-3">
                    <input type="file" 
                           id="favicon-upload" 
                           accept="image/*,.ico"
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <button type="button" 
                            id="upload-favicon-btn" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                            disabled>
                        Upload Favicon
                    </button>
                </div>
                
                <!-- Upload Status -->
                <div id="favicon-upload-status" class="hidden"></div>
                
                <!-- Preview -->
                <img id="favicon-preview" 
                     class="hidden h-8 w-8 object-contain border border-gray-200 rounded" 
                     alt="Favicon Preview">
                
                <p class="text-xs text-gray-500">Recommended size: 32x32px or 16x16px. Supported formats: ICO, PNG (max 2MB)</p>
            </div>
        </div>
    </div>

    <!-- Additional Options -->
    <div class="border-t border-gray-200 pt-6">
        <h4 class="text-md font-medium text-gray-900 mb-4">Additional Options</h4>
        
        <div class="space-y-4">
            <!-- Maintenance Mode -->
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <label for="maintenance_mode" class="block text-sm font-medium text-gray-700">
                        Maintenance Mode
                    </label>
                    <p class="text-xs text-gray-500 mt-1">When enabled, only administrators can access the website</p>
                </div>
                <div class="ml-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="maintenance_mode" value="0">
                        <input type="checkbox" 
                               id="maintenance_mode"
                               name="maintenance_mode" 
                               value="1"
                               {{ old('maintenance_mode', (bool)($settings['maintenance_mode'] ?? false)) ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>

            <!-- Allow Registration -->
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <label for="allow_registration" class="block text-sm font-medium text-gray-700">
                        Allow User Registration
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Allow new users to register on your website</p>
                </div>
                <div class="ml-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="allow_registration" value="0">
                        <input type="checkbox" 
                               id="allow_registration"
                               name="allow_registration" 
                               value="1"
                               {{ old('allow_registration', (bool)($settings['allow_registration'] ?? true)) ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
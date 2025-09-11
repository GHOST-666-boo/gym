<div class="space-y-6">
    <div>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Image Protection & Watermark Settings</h3>
            <button type="button" 
                    class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                    onclick="toggleHelpPanel()">
                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Help & Tips
            </button>
        </div>
        <p class="text-sm text-gray-600 mb-6">Configure image protection features and watermark settings for your product images.</p>
        
        <!-- Help Panel -->
        <div id="help-panel" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-start justify-between">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="text-sm text-blue-800">
                        <h4 class="font-medium mb-2">How Image Protection & Watermarking Works</h4>
                        <ul class="space-y-1 text-blue-700">
                            <li><strong>Image Protection:</strong> Prevents users from easily saving images through right-click, drag & drop, and keyboard shortcuts.</li>
                            <li><strong>Watermarking:</strong> Adds your text and/or logo overlay to all product images automatically.</li>
                            <li><strong>Performance:</strong> Watermarked images are cached for optimal loading speed.</li>
                            <li><strong>Compatibility:</strong> Works across all modern browsers and mobile devices.</li>
                            <li><strong>Limitations:</strong> Determined users can still access images through browser developer tools or by disabling JavaScript.</li>
                        </ul>
                    </div>
                </div>
                <button type="button" 
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium ml-4"
                        onclick="openDetailedHelp()">
                    Detailed Guide →
                </button>
            </div>
        </div>
    </div>

    <!-- Main Feature Toggles -->
    <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="text-md font-medium text-gray-900 mb-4">Main Features</h4>
        
        <div class="space-y-4">
            <!-- Image Protection Toggle -->
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center">
                        <label for="image_protection_enabled" class="block text-sm font-medium text-gray-700">
                            Enable Image Protection
                        </label>
                        <div class="ml-2 relative group">
                            <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                                Enables client-side protection methods to discourage image saving
                                <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Prevent users from easily saving images through right-click and other methods</p>
                </div>
                <div class="ml-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="image_protection_enabled" value="0">
                        <input type="checkbox" 
                               id="image_protection_enabled"
                               name="image_protection_enabled" 
                               value="1"
                               {{ old('image_protection_enabled', (bool)($settings['image_protection_enabled'] ?? false)) ? 'checked' : '' }}
                               class="sr-only peer"
                               onchange="toggleProtectionOptions()">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>

            <!-- Watermark Toggle -->
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="flex items-center">
                        <label for="watermark_enabled" class="block text-sm font-medium text-gray-700">
                            Enable Watermarks
                        </label>
                        <div class="ml-2 relative group">
                            <svg class="w-4 h-4 text-gray-400 cursor-help" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                                Automatically adds your text/logo overlay to all product images
                                <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Add watermarks to all product images for brand protection</p>
                </div>
                <div class="ml-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="watermark_enabled" value="0">
                        <input type="checkbox" 
                               id="watermark_enabled"
                               name="watermark_enabled" 
                               value="1"
                               {{ old('watermark_enabled', (bool)($settings['watermark_enabled'] ?? false)) ? 'checked' : '' }}
                               class="sr-only peer"
                               onchange="toggleWatermarkOptions()">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Protection Options -->
    <div id="protection-options" class="border border-gray-200 rounded-lg p-4 {{ old('image_protection_enabled', (bool)($settings['image_protection_enabled'] ?? false)) ? '' : 'opacity-50' }}">
        <h4 class="text-md font-medium text-gray-900 mb-4">Protection Options</h4>
        
        <div class="space-y-4">
            <!-- Right-click Protection -->
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <label for="right_click_protection" class="block text-sm font-medium text-gray-700">
                        Right-click Protection
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Disable right-click context menu on product images</p>
                </div>
                <div class="ml-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="right_click_protection" value="0">
                        <input type="checkbox" 
                               id="right_click_protection"
                               name="right_click_protection" 
                               value="1"
                               {{ old('right_click_protection', (bool)($settings['right_click_protection'] ?? true)) ? 'checked' : '' }}
                               class="sr-only peer protection-option">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>

            <!-- Drag & Drop Protection -->
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <label for="drag_drop_protection" class="block text-sm font-medium text-gray-700">
                        Drag & Drop Protection
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Prevent dragging images to save them</p>
                </div>
                <div class="ml-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="drag_drop_protection" value="0">
                        <input type="checkbox" 
                               id="drag_drop_protection"
                               name="drag_drop_protection" 
                               value="1"
                               {{ old('drag_drop_protection', (bool)($settings['drag_drop_protection'] ?? true)) ? 'checked' : '' }}
                               class="sr-only peer protection-option">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>

            <!-- Keyboard Shortcuts Protection -->
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <label for="keyboard_protection" class="block text-sm font-medium text-gray-700">
                        Keyboard Shortcuts Protection
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Block Ctrl+S, F12, and other shortcuts</p>
                </div>
                <div class="ml-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="keyboard_protection" value="0">
                        <input type="checkbox" 
                               id="keyboard_protection"
                               name="keyboard_protection" 
                               value="1"
                               {{ old('keyboard_protection', (bool)($settings['keyboard_protection'] ?? true)) ? 'checked' : '' }}
                               class="sr-only peer protection-option">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Watermark Configuration -->
    <div id="watermark-options" class="border border-gray-200 rounded-lg p-4 {{ old('watermark_enabled', (bool)($settings['watermark_enabled'] ?? false)) ? '' : 'opacity-50' }}">
        <h4 class="text-md font-medium text-gray-900 mb-4">Watermark Configuration</h4>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Watermark Text -->
            <div class="lg:col-span-2">
                <label for="watermark_text" class="block text-sm font-medium text-gray-700 mb-2">
                    Watermark Text
                </label>
                <input type="text" 
                       id="watermark_text" 
                       name="watermark_text" 
                       value="{{ old('watermark_text', $settings['watermark_text'] ?? 'Gym Machines Website') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 watermark-option @error('watermark_text') border-red-500 @enderror"
                       placeholder="Enter watermark text"
                       onchange="updateWatermarkPreview()">
                @error('watermark_text')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Text that will appear on all product images. Leave empty to use site name.</p>
            </div>

            <!-- Watermark Logo Upload -->
            <div class="lg:col-span-2">
                <label for="watermark_logo" class="block text-sm font-medium text-gray-700 mb-2">
                    Watermark Logo
                </label>
                <div class="space-y-3">
                    <!-- Current Logo Display -->
                    <div id="current-watermark-logo-display">
                        @if(isset($settings['watermark_logo_path']) && $settings['watermark_logo_path'])
                            <div class="flex items-center space-x-3">
                                <img src="{{ asset('storage/' . $settings['watermark_logo_path']) }}" 
                                     alt="Current Watermark Logo" 
                                     class="h-12 w-auto object-contain border border-gray-200 rounded bg-gray-100 p-2">
                                <span class="text-sm text-gray-600">Current watermark logo</span>
                            </div>
                        @endif
                    </div>
                    
                    <!-- File Input -->
                    <div class="flex items-center space-x-3">
                        <input type="file" 
                               id="watermark-logo-upload" 
                               accept="image/*"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 watermark-option">
                        <button type="button" 
                                id="upload-watermark-logo-btn" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                                disabled>
                            Upload Logo
                        </button>
                    </div>
                    
                    <!-- Upload Status -->
                    <div id="watermark-logo-upload-status" class="hidden"></div>
                    
                    <!-- Preview -->
                    <img id="watermark-logo-preview" 
                         class="hidden h-12 w-auto object-contain border border-gray-200 rounded bg-gray-100 p-2" 
                         alt="Watermark Logo Preview">
                    
                    <p class="text-xs text-gray-500">Recommended size: 100x100px or smaller. Supported formats: PNG, JPG, SVG (max 2MB). PNG with transparency recommended.</p>
                </div>
            </div>

            <!-- Watermark Position -->
            <div>
                <label for="watermark_position" class="block text-sm font-medium text-gray-700 mb-2">
                    Position
                </label>
                <select id="watermark_position" 
                        name="watermark_position" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 watermark-option @error('watermark_position') border-red-500 @enderror"
                        onchange="updateWatermarkPreview()">
                    <option value="top-left" {{ old('watermark_position', $settings['watermark_position'] ?? 'bottom-right') == 'top-left' ? 'selected' : '' }}>Top Left</option>
                    <option value="top-center" {{ old('watermark_position', $settings['watermark_position'] ?? 'bottom-right') == 'top-center' ? 'selected' : '' }}>Top Center</option>
                    <option value="top-right" {{ old('watermark_position', $settings['watermark_position'] ?? 'bottom-right') == 'top-right' ? 'selected' : '' }}>Top Right</option>
                    <option value="center-left" {{ old('watermark_position', $settings['watermark_position'] ?? 'bottom-right') == 'center-left' ? 'selected' : '' }}>Center Left</option>
                    <option value="center" {{ old('watermark_position', $settings['watermark_position'] ?? 'bottom-right') == 'center' ? 'selected' : '' }}>Center</option>
                    <option value="center-right" {{ old('watermark_position', $settings['watermark_position'] ?? 'bottom-right') == 'center-right' ? 'selected' : '' }}>Center Right</option>
                    <option value="bottom-left" {{ old('watermark_position', $settings['watermark_position'] ?? 'bottom-right') == 'bottom-left' ? 'selected' : '' }}>Bottom Left</option>
                    <option value="bottom-center" {{ old('watermark_position', $settings['watermark_position'] ?? 'bottom-right') == 'bottom-center' ? 'selected' : '' }}>Bottom Center</option>
                    <option value="bottom-right" {{ old('watermark_position', $settings['watermark_position'] ?? 'bottom-right') == 'bottom-right' ? 'selected' : '' }}>Bottom Right</option>
                </select>
                @error('watermark_position')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Where the watermark will appear on images</p>
            </div>

            <!-- Watermark Size -->
            <div>
                <label for="watermark_size" class="block text-sm font-medium text-gray-700 mb-2">
                    Size
                </label>
                <select id="watermark_size" 
                        name="watermark_size" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 watermark-option @error('watermark_size') border-red-500 @enderror"
                        onchange="updateWatermarkPreview()">
                    <option value="small" {{ old('watermark_size', $settings['watermark_size'] ?? 'medium') == 'small' ? 'selected' : '' }}>Small</option>
                    <option value="medium" {{ old('watermark_size', $settings['watermark_size'] ?? 'medium') == 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="large" {{ old('watermark_size', $settings['watermark_size'] ?? 'medium') == 'large' ? 'selected' : '' }}>Large</option>
                </select>
                @error('watermark_size')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Relative size of the watermark</p>
            </div>

            <!-- Watermark Opacity -->
            <div>
                <label for="watermark_opacity" class="block text-sm font-medium text-gray-700 mb-2">
                    Opacity: <span id="opacity-value">{{ old('watermark_opacity', $settings['watermark_opacity'] ?? 50) }}%</span>
                </label>
                <input type="range" 
                       id="watermark_opacity" 
                       name="watermark_opacity" 
                       min="10" 
                       max="90" 
                       step="5"
                       value="{{ old('watermark_opacity', $settings['watermark_opacity'] ?? 50) }}"
                       class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer watermark-option"
                       oninput="updateOpacityValue(); updateWatermarkPreview()">
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                    <span>10%</span>
                    <span>50%</span>
                    <span>90%</span>
                </div>
                <p class="mt-1 text-xs text-gray-500">Transparency level of the watermark</p>
            </div>

            <!-- Watermark Text Color -->
            <div>
                <label for="watermark_text_color" class="block text-sm font-medium text-gray-700 mb-2">
                    Text Color
                </label>
                <div class="flex items-center space-x-3">
                    <input type="color" 
                           id="watermark_text_color" 
                           name="watermark_text_color" 
                           value="{{ old('watermark_text_color', $settings['watermark_text_color'] ?? '#ffffff') }}"
                           class="h-10 w-16 border border-gray-300 rounded-md cursor-pointer watermark-option"
                           onchange="updateWatermarkPreview()">
                    <input type="text" 
                           id="watermark_text_color_hex" 
                           value="{{ old('watermark_text_color', $settings['watermark_text_color'] ?? '#ffffff') }}"
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-sm watermark-option"
                           placeholder="#ffffff"
                           onchange="updateColorFromHex()">
                </div>
                @error('watermark_text_color')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Color for watermark text (does not affect logo)</p>
            </div>
        </div>

        <!-- Watermark Preview -->
        <div class="mt-6 border-t border-gray-200 pt-6">
            <h5 class="text-sm font-medium text-gray-900 mb-3">Preview</h5>
            <div class="relative bg-gray-100 rounded-lg p-4 min-h-[200px] flex items-center justify-center">
                <div class="relative">
                    <!-- Sample product image placeholder -->
                    <div class="w-48 h-32 bg-gradient-to-br from-blue-100 to-blue-200 rounded-lg flex items-center justify-center text-gray-600 text-sm">
                        Sample Product Image
                    </div>
                    
                    <!-- Watermark preview overlay -->
                    <div id="watermark-preview-overlay" 
                         class="absolute inset-0 pointer-events-none"
                         style="opacity: {{ (old('watermark_opacity', $settings['watermark_opacity'] ?? 50)) / 100 }}">
                        <div id="watermark-preview-content" 
                             class="absolute text-xs font-medium px-2 py-1 rounded"
                             style="color: {{ old('watermark_text_color', $settings['watermark_text_color'] ?? '#ffffff') }}; 
                                    background-color: rgba(0,0,0,0.3);
                                    {{ getWatermarkPositionStyle(old('watermark_position', $settings['watermark_position'] ?? 'bottom-right')) }}">
                            {{ old('watermark_text', $settings['watermark_text'] ?? 'Gym Machines Website') }}
                        </div>
                    </div>
                </div>
            </div>
            <p class="mt-2 text-xs text-gray-500">This is a simplified preview. Actual watermarks may appear differently based on image content and size.</p>
        </div>
    </div>
</div>

@php
function getWatermarkPositionStyle($position) {
    switch($position) {
        case 'top-left': return 'top: 8px; left: 8px;';
        case 'top-center': return 'top: 8px; left: 50%; transform: translateX(-50%);';
        case 'top-right': return 'top: 8px; right: 8px;';
        case 'center-left': return 'top: 50%; left: 8px; transform: translateY(-50%);';
        case 'center': return 'top: 50%; left: 50%; transform: translate(-50%, -50%);';
        case 'center-right': return 'top: 50%; right: 8px; transform: translateY(-50%);';
        case 'bottom-left': return 'bottom: 8px; left: 8px;';
        case 'bottom-center': return 'bottom: 8px; left: 50%; transform: translateX(-50%);';
        case 'bottom-right': 
        default: return 'bottom: 8px; right: 8px;';
    }
}
@endphp

<script>
function toggleHelpPanel() {
    const panel = document.getElementById('help-panel');
    panel.classList.toggle('hidden');
}

function toggleProtectionOptions() {
    const enabled = document.getElementById('image_protection_enabled').checked;
    const options = document.getElementById('protection-options');
    const inputs = options.querySelectorAll('.protection-option');
    
    if (enabled) {
        options.classList.remove('opacity-50');
        inputs.forEach(input => input.disabled = false);
    } else {
        options.classList.add('opacity-50');
        inputs.forEach(input => input.disabled = true);
    }
}

function toggleWatermarkOptions() {
    const enabled = document.getElementById('watermark_enabled').checked;
    const options = document.getElementById('watermark-options');
    const inputs = options.querySelectorAll('.watermark-option');
    
    if (enabled) {
        options.classList.remove('opacity-50');
        inputs.forEach(input => input.disabled = false);
    } else {
        options.classList.add('opacity-50');
        inputs.forEach(input => input.disabled = true);
    }
}

function updateOpacityValue() {
    const slider = document.getElementById('watermark_opacity');
    const display = document.getElementById('opacity-value');
    display.textContent = slider.value + '%';
}

function updateColorFromHex() {
    const hexInput = document.getElementById('watermark_text_color_hex');
    const colorInput = document.getElementById('watermark_text_color');
    colorInput.value = hexInput.value;
    updateWatermarkPreview();
}

function updateWatermarkPreview() {
    const text = document.getElementById('watermark_text').value || 'Gym Machines Website';
    const position = document.getElementById('watermark_position').value;
    const opacity = document.getElementById('watermark_opacity').value;
    const color = document.getElementById('watermark_text_color').value;
    
    const overlay = document.getElementById('watermark-preview-overlay');
    const content = document.getElementById('watermark-preview-content');
    
    // Update opacity
    overlay.style.opacity = opacity / 100;
    
    // Update text and color
    content.textContent = text;
    content.style.color = color;
    
    // Update position
    content.className = 'absolute text-xs font-medium px-2 py-1 rounded';
    content.style.backgroundColor = 'rgba(0,0,0,0.3)';
    
    switch(position) {
        case 'top-left':
            content.style.top = '8px';
            content.style.left = '8px';
            content.style.right = 'auto';
            content.style.bottom = 'auto';
            content.style.transform = 'none';
            break;
        case 'top-center':
            content.style.top = '8px';
            content.style.left = '50%';
            content.style.right = 'auto';
            content.style.bottom = 'auto';
            content.style.transform = 'translateX(-50%)';
            break;
        case 'top-right':
            content.style.top = '8px';
            content.style.right = '8px';
            content.style.left = 'auto';
            content.style.bottom = 'auto';
            content.style.transform = 'none';
            break;
        case 'center-left':
            content.style.top = '50%';
            content.style.left = '8px';
            content.style.right = 'auto';
            content.style.bottom = 'auto';
            content.style.transform = 'translateY(-50%)';
            break;
        case 'center':
            content.style.top = '50%';
            content.style.left = '50%';
            content.style.right = 'auto';
            content.style.bottom = 'auto';
            content.style.transform = 'translate(-50%, -50%)';
            break;
        case 'center-right':
            content.style.top = '50%';
            content.style.right = '8px';
            content.style.left = 'auto';
            content.style.bottom = 'auto';
            content.style.transform = 'translateY(-50%)';
            break;
        case 'bottom-left':
            content.style.bottom = '8px';
            content.style.left = '8px';
            content.style.right = 'auto';
            content.style.top = 'auto';
            content.style.transform = 'none';
            break;
        case 'bottom-center':
            content.style.bottom = '8px';
            content.style.left = '50%';
            content.style.right = 'auto';
            content.style.top = 'auto';
            content.style.transform = 'translateX(-50%)';
            break;
        case 'bottom-right':
        default:
            content.style.bottom = '8px';
            content.style.right = '8px';
            content.style.left = 'auto';
            content.style.top = 'auto';
            content.style.transform = 'none';
            break;
    }
}

function openDetailedHelp() {
    document.getElementById('detailed-help-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeDetailedHelp() {
    document.getElementById('detailed-help-modal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleProtectionOptions();
    toggleWatermarkOptions();
    updateWatermarkPreview();
    
    // Sync color inputs
    document.getElementById('watermark_text_color').addEventListener('change', function() {
        document.getElementById('watermark_text_color_hex').value = this.value;
        updateWatermarkPreview();
    });
    
    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDetailedHelp();
        }
    });
});
</script>

<!-- Detailed Help Modal -->
<div id="detailed-help-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-gray-900">Image Protection & Watermarking - Complete Guide</h3>
            <button type="button" 
                    class="text-gray-400 hover:text-gray-600"
                    onclick="closeDetailedHelp()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="max-h-96 overflow-y-auto">
            @include('admin.settings.partials.watermark-help')
        </div>
        <div class="flex justify-end mt-4 pt-4 border-t border-gray-200">
            <button type="button" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors"
                    onclick="closeDetailedHelp()">
                Close Guide
            </button>
        </div>
    </div>
</div>
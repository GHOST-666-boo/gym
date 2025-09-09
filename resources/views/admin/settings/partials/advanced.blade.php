<div class="space-y-6">
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Advanced Settings</h3>
        
        <!-- Maintenance Mode -->
        <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <div>
                        <h4 class="text-sm font-medium text-yellow-800">Maintenance Mode</h4>
                        <p class="text-sm text-yellow-700">Put the site in maintenance mode for updates</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="maintenance_mode" value="0">
                    <input type="checkbox" 
                           name="maintenance_mode" 
                           value="1" 
                           class="sr-only peer"
                           {{ old('maintenance_mode', (bool)($settings['maintenance_mode'] ?? false)) ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>
            
            <!-- User Registration -->
            <div class="flex items-center justify-between p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                    <div>
                        <h4 class="text-sm font-medium text-blue-800">User Registration</h4>
                        <p class="text-sm text-blue-700">Allow new users to register on the site</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="allow_registration" value="0">
                    <input type="checkbox" 
                           name="allow_registration" 
                           value="1" 
                           class="sr-only peer"
                           {{ old('allow_registration', (bool)($settings['allow_registration'] ?? true)) ? 'checked' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>
        </div>
    </div>

    <!-- Email Configuration -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Email Configuration (SMTP)</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="smtp_host" class="block text-sm font-medium text-gray-700 mb-2">SMTP Host</label>
                <input type="text" 
                       id="smtp_host" 
                       name="smtp_host" 
                       value="{{ old('smtp_host', $settings['smtp_host'] ?? '') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="smtp.gmail.com">
                @error('smtp_host')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="smtp_port" class="block text-sm font-medium text-gray-700 mb-2">SMTP Port</label>
                <input type="number" 
                       id="smtp_port" 
                       name="smtp_port" 
                       value="{{ old('smtp_port', $settings['smtp_port'] ?? '587') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="587">
                @error('smtp_port')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="smtp_username" class="block text-sm font-medium text-gray-700 mb-2">SMTP Username</label>
                <input type="text" 
                       id="smtp_username" 
                       name="smtp_username" 
                       value="{{ old('smtp_username', $settings['smtp_username'] ?? '') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="your-email@gmail.com">
                @error('smtp_username')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="smtp_password" class="block text-sm font-medium text-gray-700 mb-2">SMTP Password</label>
                <input type="password" 
                       id="smtp_password" 
                       name="smtp_password" 
                       value="{{ old('smtp_password', $settings['smtp_password'] ?? '') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Your app password">
                @error('smtp_password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        
        <div class="mt-4 p-4 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-600">
                <strong>Note:</strong> For Gmail, use your app password instead of your regular password. 
                For other providers, check their SMTP configuration documentation.
            </p>
        </div>
    </div>

    <!-- Currency and Pricing Settings -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Currency & Pricing Display</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="currency_symbol" class="block text-sm font-medium text-gray-700 mb-2">Currency Symbol</label>
                <input type="text" 
                       id="currency_symbol" 
                       name="currency_symbol" 
                       value="{{ old('currency_symbol', $settings['currency_symbol'] ?? '$') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                       placeholder="$">
                @error('currency_symbol')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="currency_position" class="block text-sm font-medium text-gray-700 mb-2">Currency Position</label>
                <select id="currency_position" 
                        name="currency_position" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="before" {{ old('currency_position', $settings['currency_position'] ?? 'before') === 'before' ? 'selected' : '' }}>
                        Before amount ($100)
                    </option>
                    <option value="after" {{ old('currency_position', $settings['currency_position'] ?? 'before') === 'after' ? 'selected' : '' }}>
                        After amount (100$)
                    </option>
                </select>
                @error('currency_position')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        
        <div class="mt-4 p-4 bg-blue-50 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-sm text-blue-700">
                    <strong>Preview:</strong> 
                    <span id="currency-preview">
                        {{ ($settings['currency_position'] ?? 'before') === 'after' ? '100' . ($settings['currency_symbol'] ?? '$') : ($settings['currency_symbol'] ?? '$') . '100' }}
                    </span>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Currency preview update
    const currencySymbol = document.getElementById('currency_symbol');
    const currencyPosition = document.getElementById('currency_position');
    const currencyPreview = document.getElementById('currency-preview');
    
    function updateCurrencyPreview() {
        const symbol = currencySymbol.value || '$';
        const position = currencyPosition.value;
        const amount = '100';
        
        if (position === 'after') {
            currencyPreview.textContent = amount + symbol;
        } else {
            currencyPreview.textContent = symbol + amount;
        }
    }
    
    if (currencySymbol && currencyPosition && currencyPreview) {
        currencySymbol.addEventListener('input', updateCurrencyPreview);
        currencyPosition.addEventListener('change', updateCurrencyPreview);
    }
});
</script>
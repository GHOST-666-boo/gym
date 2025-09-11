<div class="space-y-6">
    <div>
        <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
        <p class="text-sm text-gray-600 mb-6">Manage your business contact details that will be displayed throughout your website.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Business Phone -->
        <div>
            <label for="business_phone" class="block text-sm font-medium text-gray-700 mb-2">
                Business Phone
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                </div>
                <input type="tel" 
                       id="business_phone" 
                       name="business_phone" 
                       value="{{ old('business_phone', $settings['business_phone'] ?? '') }}"
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('business_phone') border-red-500 @enderror"
                       placeholder="+1 (555) 123-4567">
            </div>
            @error('business_phone')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">This will appear in your website footer and contact page.</p>
        </div>

        <!-- Business Email -->
        <div>
            <label for="business_email" class="block text-sm font-medium text-gray-700 mb-2">
                Business Email
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <input type="email" 
                       id="business_email" 
                       name="business_email" 
                       value="{{ old('business_email', $settings['business_email'] ?? '') }}"
                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('business_email') border-red-500 @enderror"
                       placeholder="contact@example.com">
            </div>
            @error('business_email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Primary contact email for customer inquiries.</p>
        </div>

        <!-- Business Address -->
        <div class="lg:col-span-2">
            <label for="business_address" class="block text-sm font-medium text-gray-700 mb-2">
                Business Address
            </label>
            <div class="relative">
                <div class="absolute top-3 left-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <textarea id="business_address" 
                          name="business_address" 
                          rows="3"
                          class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('business_address') border-red-500 @enderror"
                          placeholder="123 Main Street&#10;Suite 100&#10;City, State 12345">{{ old('business_address', $settings['business_address'] ?? '') }}</textarea>
            </div>
            @error('business_address')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Full business address including street, city, state, and postal code.</p>
        </div>

        <!-- Business Hours -->
        <div class="lg:col-span-2">
            <label for="business_hours" class="block text-sm font-medium text-gray-700 mb-2">
                Business Hours
            </label>
            <div class="relative">
                <div class="absolute top-3 left-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <textarea id="business_hours" 
                          name="business_hours" 
                          rows="4"
                          class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('business_hours') border-red-500 @enderror"
                          placeholder="Monday - Friday: 9:00 AM - 6:00 PM&#10;Saturday: 10:00 AM - 4:00 PM&#10;Sunday: Closed">{{ old('business_hours', $settings['business_hours'] ?? '') }}</textarea>
            </div>
            @error('business_hours')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">Operating hours that will be displayed to customers.</p>
        </div>
    </div>

    <!-- Contact Form Settings -->
    <div class="border-t border-gray-200 pt-6">
        <h4 class="text-md font-medium text-gray-900 mb-4">Contact Form Settings</h4>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Contact Form Email -->
            <div>
                <label for="contact_form_email" class="block text-sm font-medium text-gray-700 mb-2">
                    Contact Form Recipient Email
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                        </svg>
                    </div>
                    <input type="email" 
                           id="contact_form_email" 
                           name="contact_form_email" 
                           value="{{ old('contact_form_email', $settings['contact_form_email'] ?? $settings['business_email'] ?? '') }}"
                           class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('contact_form_email') border-red-500 @enderror"
                           placeholder="admin@example.com">
                </div>
                @error('contact_form_email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Email address where contact form submissions will be sent.</p>
            </div>

            <!-- Auto-reply Settings -->
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <label for="contact_auto_reply" class="block text-sm font-medium text-gray-700">
                        Send Auto-reply to Customers
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Automatically send a confirmation email to customers who submit the contact form</p>
                </div>
                <div class="ml-4">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="contact_auto_reply" value="0">
                        <input type="checkbox" 
                               id="contact_auto_reply"
                               name="contact_auto_reply" 
                               value="1"
                               {{ old('contact_auto_reply', $settings['contact_auto_reply'] ?? true) ? 'checked' : '' }}
                               class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
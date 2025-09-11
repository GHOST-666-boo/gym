<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use App\Services\ImageProtectionValidationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    protected SettingsService $settingsService;
    protected ImageProtectionValidationService $validationService;

    public function __construct(SettingsService $settingsService, ImageProtectionValidationService $validationService)
    {
        $this->settingsService = $settingsService;
        $this->validationService = $validationService;
    }

    /**
     * Display the settings management page.
     */
    public function index(): View
    {
        $settings = $this->settingsService->getAll();
        
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update multiple settings with comprehensive validation.
     */
    public function update(Request $request): RedirectResponse
    {
        try {
            // Comprehensive validation including watermark settings
            $validationRules = $this->getValidationRules();
            $validationMessages = $this->getValidationMessages();
            
            $request->validate($validationRules, $validationMessages);
            
            // Validate watermark-specific business rules
            $this->validateWatermarkBusinessRules($request);
            
            // Prepare settings data for update
            $settingsData = $this->prepareSettingsData($request);
            
            // Update all settings
            foreach ($settingsData as $key => $data) {
                $this->settingsService->set(
                    $key, 
                    $data['value'], 
                    $data['type'], 
                    $data['group']
                );
            }
            
            // Force clear all caches to ensure fresh data on next load
            $this->settingsService->clearCache();
            
            return redirect()->route('admin.settings.index')
                ->with('success', 'Settings updated successfully.');
                
        } catch (ValidationException $e) {
            return redirect()->route('admin.settings.index')
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.index')
                ->with('error', 'Failed to update settings: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Upload logo file.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'logo' => 'required|file|mimes:jpeg,jpg,png,gif,webp,svg|max:5120' // 5MB limit
            ]);

            $result = $this->settingsService->uploadLogo($request->file('logo'));
            return response()->json($result);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload favicon file.
     */
    public function uploadFavicon(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'favicon' => 'required|file|mimes:ico,png,jpeg,jpg,gif|max:2048' // 2MB limit
            ]);

            $result = $this->settingsService->uploadFavicon($request->file('favicon'));
            return response()->json($result);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload watermark logo file with comprehensive validation.
     */
    public function uploadWatermarkLogo(Request $request): JsonResponse
    {
        try {
            $this->validationService->validateWatermarkLogo($request->file('watermark_logo'));
            $result = $this->settingsService->uploadWatermarkLogo($request->file('watermark_logo'));
            return response()->json($result);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get validation summary for current settings
     */
    public function getValidationSummary(): JsonResponse
    {
        try {
            $settings = $this->settingsService->getAll();
            
            // Extract image protection settings
            $imageProtectionSettings = array_filter($settings, function($key) {
                return str_starts_with($key, 'image_protection_') || 
                       str_starts_with($key, 'watermark_') || 
                       in_array($key, ['right_click_protection', 'drag_drop_protection', 'keyboard_protection']);
            }, ARRAY_FILTER_USE_KEY);
            
            $summary = $this->validationService->getValidationSummary($imageProtectionSettings);
            
            return response()->json([
                'success' => true,
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get validation summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test image protection features
     */
    public function testProtectionFeatures(): JsonResponse
    {
        try {
            $settings = $this->settingsService->getAll();
            
            $tests = [
                'gd_extension' => extension_loaded('gd'),
                'imagick_extension' => extension_loaded('imagick'),
                'storage_writable' => is_writable(storage_path('app/public')),
                'watermark_cache_dir' => is_dir(storage_path('app/public/watermarks/cache')),
                'protection_enabled' => (bool)($settings['image_protection_enabled'] ?? false),
                'watermark_enabled' => (bool)($settings['watermark_enabled'] ?? false),
            ];
            
            $allPassed = array_reduce($tests, function($carry, $test) {
                return $carry && $test;
            }, true);
            
            return response()->json([
                'success' => true,
                'all_tests_passed' => $allPassed,
                'tests' => $tests,
                'recommendations' => $this->getSystemRecommendations($tests)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to run tests: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get validation rules for settings update.
     */
    protected function getValidationRules(): array
    {
        return [
            // General settings
            'site_name' => 'required|string|max:255',
            'site_tagline' => 'nullable|string|max:500',
            'maintenance_mode' => 'boolean',
            'allow_registration' => 'boolean',
            
            // Contact settings
            'business_phone' => 'nullable|string|max:50',
            'business_email' => 'nullable|email|max:255',
            'business_address' => 'nullable|string|max:1000',
            'business_hours' => 'nullable|string|max:1000',
            'contact_form_email' => 'nullable|email|max:255',
            'contact_auto_reply' => 'boolean',
            
            // Social Media settings
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'tiktok_url' => 'nullable|url|max:255',
            'show_social_footer' => 'boolean',
            'show_social_contact' => 'boolean',
            'social_links_new_tab' => 'boolean',
            
            // SEO settings
            'default_meta_title' => 'nullable|string|max:60',
            'default_meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string|max:255',
            'og_title' => 'nullable|string|max:60',
            'og_description' => 'nullable|string|max:160',
            'allow_indexing' => 'boolean',
            'generate_sitemap' => 'boolean',
            'google_analytics_id' => 'nullable|string|max:50',
            'google_site_verification' => 'nullable|string|max:100',
            
            // Advanced settings
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'currency_symbol' => 'nullable|string|max:10',
            'currency_position' => 'nullable|in:before,after',
            
            // Image Protection & Watermark settings
            'image_protection_enabled' => 'boolean',
            'watermark_enabled' => 'boolean',
            'right_click_protection' => 'boolean',
            'drag_drop_protection' => 'boolean',
            'keyboard_protection' => 'boolean',
            'watermark_text' => 'nullable|string|max:100',
            'watermark_position' => 'nullable|in:top-left,top-center,top-right,center-left,center,center-right,bottom-left,bottom-center,bottom-right',
            'watermark_opacity' => 'nullable|integer|min:10|max:90',
            'watermark_size' => 'nullable|in:small,medium,large',
            'watermark_text_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ];
    }

    /**
     * Get validation messages for settings update.
     */
    protected function getValidationMessages(): array
    {
        return [
            // General settings
            'site_name.required' => 'Site name is required.',
            'site_name.max' => 'Site name cannot exceed 255 characters.',
            'site_tagline.max' => 'Site tagline cannot exceed 500 characters.',
            
            // Contact settings
            'business_email.email' => 'Please enter a valid email address.',
            'business_phone.max' => 'Phone number cannot exceed 50 characters.',
            
            // Social Media settings
            'facebook_url.url' => 'Please enter a valid Facebook URL.',
            'instagram_url.url' => 'Please enter a valid Instagram URL.',
            'twitter_url.url' => 'Please enter a valid Twitter URL.',
            'youtube_url.url' => 'Please enter a valid YouTube URL.',
            'linkedin_url.url' => 'Please enter a valid LinkedIn URL.',
            'tiktok_url.url' => 'Please enter a valid TikTok URL.',
            
            // SEO settings
            'default_meta_title.max' => 'Meta title should not exceed 60 characters.',
            'default_meta_description.max' => 'Meta description should not exceed 160 characters.',
            'og_title.max' => 'Open Graph title should not exceed 60 characters.',
            'og_description.max' => 'Open Graph description should not exceed 160 characters.',
            
            // Image Protection & Watermark settings
            'watermark_text.max' => 'Watermark text cannot exceed 100 characters.',
            'watermark_position.in' => 'Please select a valid watermark position.',
            'watermark_opacity.integer' => 'Watermark opacity must be a number.',
            'watermark_opacity.min' => 'Watermark opacity must be at least 10%.',
            'watermark_opacity.max' => 'Watermark opacity cannot exceed 90%.',
            'watermark_size.in' => 'Please select a valid watermark size.',
            'watermark_text_color.regex' => 'Watermark text color must be a valid hex color (e.g., #ffffff).',
        ];
    }

    /**
     * Validate watermark-specific business rules.
     */
    protected function validateWatermarkBusinessRules(Request $request): void
    {
        // If image protection is enabled, at least one protection method must be enabled
        if ($request->boolean('image_protection_enabled')) {
            $hasProtection = $request->boolean('right_click_protection') ||
                           $request->boolean('drag_drop_protection') ||
                           $request->boolean('keyboard_protection');
            
            if (!$hasProtection) {
                throw ValidationException::withMessages([
                    'image_protection_enabled' => 'At least one protection method must be enabled when image protection is active.'
                ]);
            }
        }
        
        // If watermark is enabled, either text or logo should be provided
        if ($request->boolean('watermark_enabled')) {
            $hasText = !empty($request->input('watermark_text'));
            $hasLogo = !empty($this->settingsService->get('watermark_logo_path'));
            
            if (!$hasText && !$hasLogo) {
                throw ValidationException::withMessages([
                    'watermark_text' => 'Either watermark text or logo must be provided when watermarking is enabled.'
                ]);
            }
        }
    }

    /**
     * Prepare settings data for update
     */
    protected function prepareSettingsData(Request $request): array
    {
        return [
            // General settings
            'site_name' => [
                'value' => $request->input('site_name', 'Gym Machines'),
                'type' => 'string',
                'group' => 'general'
            ],
            'site_tagline' => [
                'value' => $request->input('site_tagline', ''),
                'type' => 'string',
                'group' => 'general'
            ],
            'maintenance_mode' => [
                'value' => $request->boolean('maintenance_mode') ? '1' : '0',
                'type' => 'boolean',
                'group' => 'general'
            ],
            'allow_registration' => [
                'value' => $request->boolean('allow_registration') ? '1' : '0',
                'type' => 'boolean',
                'group' => 'general'
            ],
            
            // Contact settings
            'business_phone' => [
                'value' => $request->input('business_phone', ''),
                'type' => 'string',
                'group' => 'contact'
            ],
            'business_email' => [
                'value' => $request->input('business_email', ''),
                'type' => 'string',
                'group' => 'contact'
            ],
            'business_address' => [
                'value' => $request->input('business_address', ''),
                'type' => 'string',
                'group' => 'contact'
            ],
            'business_hours' => [
                'value' => $request->input('business_hours', ''),
                'type' => 'string',
                'group' => 'contact'
            ],
            'contact_form_email' => [
                'value' => $request->input('contact_form_email', ''),
                'type' => 'string',
                'group' => 'contact'
            ],
            'contact_auto_reply' => [
                'value' => $request->boolean('contact_auto_reply') ? '1' : '0',
                'type' => 'boolean',
                'group' => 'contact'
            ],
            
            // Social Media settings
            'facebook_url' => [
                'value' => $request->input('facebook_url', ''),
                'type' => 'string',
                'group' => 'social'
            ],
            'instagram_url' => [
                'value' => $request->input('instagram_url', ''),
                'type' => 'string',
                'group' => 'social'
            ],
            'twitter_url' => [
                'value' => $request->input('twitter_url', ''),
                'type' => 'string',
                'group' => 'social'
            ],
            'youtube_url' => [
                'value' => $request->input('youtube_url', ''),
                'type' => 'string',
                'group' => 'social'
            ],
            'linkedin_url' => [
                'value' => $request->input('linkedin_url', ''),
                'type' => 'string',
                'group' => 'social'
            ],
            'tiktok_url' => [
                'value' => $request->input('tiktok_url', ''),
                'type' => 'string',
                'group' => 'social'
            ],
            'show_social_footer' => [
                'value' => $request->boolean('show_social_footer') ? '1' : '0',
                'type' => 'boolean',
                'group' => 'social'
            ],
            'show_social_contact' => [
                'value' => $request->boolean('show_social_contact') ? '1' : '0',
                'type' => 'boolean',
                'group' => 'social'
            ],
            'social_links_new_tab' => [
                'value' => $request->boolean('social_links_new_tab') ? '1' : '0',
                'type' => 'boolean',
                'group' => 'social'
            ],
            
            // SEO settings
            'default_meta_title' => [
                'value' => $request->input('default_meta_title', ''),
                'type' => 'string',
                'group' => 'seo'
            ],
            'default_meta_description' => [
                'value' => $request->input('default_meta_description', ''),
                'type' => 'string',
                'group' => 'seo'
            ],
            'meta_keywords' => [
                'value' => $request->input('meta_keywords', ''),
                'type' => 'string',
                'group' => 'seo'
            ],
            'og_title' => [
                'value' => $request->input('og_title', ''),
                'type' => 'string',
                'group' => 'seo'
            ],
            'og_description' => [
                'value' => $request->input('og_description', ''),
                'type' => 'string',
                'group' => 'seo'
            ],
            'allow_indexing' => [
                'value' => $request->boolean('allow_indexing') ? '1' : '0',
                'type' => 'boolean',
                'group' => 'seo'
            ],
            'generate_sitemap' => [
                'value' => $request->boolean('generate_sitemap') ? '1' : '0',
                'type' => 'boolean',
                'group' => 'seo'
            ],
            'google_analytics_id' => [
                'value' => $request->input('google_analytics_id', ''),
                'type' => 'string',
                'group' => 'seo'
            ],
            'google_site_verification' => [
                'value' => $request->input('google_site_verification', ''),
                'type' => 'string',
                'group' => 'seo'
            ],
            
            // Advanced settings
            'smtp_host' => [
                'value' => $request->input('smtp_host', ''),
                'type' => 'string',
                'group' => 'advanced'
            ],
            'smtp_port' => [
                'value' => (string)$request->input('smtp_port', 587),
                'type' => 'integer',
                'group' => 'advanced'
            ],
            'smtp_username' => [
                'value' => $request->input('smtp_username', ''),
                'type' => 'string',
                'group' => 'advanced'
            ],
            'smtp_password' => [
                'value' => $request->input('smtp_password', ''),
                'type' => 'string',
                'group' => 'advanced'
            ],
            'currency_symbol' => [
                'value' => $request->input('currency_symbol', '$'),
                'type' => 'string',
                'group' => 'advanced'
            ],
            'currency_position' => [
                'value' => $request->input('currency_position', 'before'),
                'type' => 'string',
                'group' => 'advanced'
            ],
            
            // Image Protection & Watermark settings
            'image_protection_enabled' => [
                'value' => $request->boolean('image_protection_enabled') ? '1' : '0',
                'type' => 'boolean',
                'group' => 'image_protection'
            ],
            'watermark_enabled' => [
                'value' => $request->boolean('watermark_enabled') ? '1' : '0',
                'type' => 'boolean',
                'group' => 'image_protection'
            ],
            'right_click_protection' => [
                'value' => $request->boolean('right_click_protection') ? '1' : '0',
                'type' => 'boolean',
                'group' => 'image_protection'
            ],
            'drag_drop_protection' => [
                'value' => $request->boolean('drag_drop_protection') ? '1' : '0',
                'type' => 'boolean',
                'group' => 'image_protection'
            ],
            'keyboard_protection' => [
                'value' => $request->boolean('keyboard_protection') ? '1' : '0',
                'type' => 'boolean',
                'group' => 'image_protection'
            ],
            'watermark_text' => [
                'value' => $request->input('watermark_text', ''),
                'type' => 'string',
                'group' => 'watermark'
            ],
            'watermark_position' => [
                'value' => $request->input('watermark_position', 'bottom-right'),
                'type' => 'string',
                'group' => 'watermark'
            ],
            'watermark_opacity' => [
                'value' => (string)$request->input('watermark_opacity', 50),
                'type' => 'integer',
                'group' => 'watermark'
            ],
            'watermark_size' => [
                'value' => $request->input('watermark_size', 'medium'),
                'type' => 'string',
                'group' => 'watermark'
            ],
            'watermark_text_color' => [
                'value' => $request->input('watermark_text_color', '#ffffff'),
                'type' => 'string',
                'group' => 'watermark'
            ],
        ];
    }

    /**
     * Get system recommendations based on test results
     */
    private function getSystemRecommendations(array $tests): array
    {
        $recommendations = [];
        
        if (!$tests['gd_extension'] && !$tests['imagick_extension']) {
            $recommendations[] = 'Install GD or Imagick PHP extension for watermark generation.';
        }
        
        if (!$tests['storage_writable']) {
            $recommendations[] = 'Make storage/app/public directory writable for image caching.';
        }
        
        if (!$tests['watermark_cache_dir']) {
            $recommendations[] = 'Create watermarks/cache directory in storage/app/public.';
        }
        
        if (!$tests['protection_enabled'] && !$tests['watermark_enabled']) {
            $recommendations[] = 'Enable image protection or watermarking for better security.';
        }
        
        return $recommendations;
    }
}
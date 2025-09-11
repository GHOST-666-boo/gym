<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class ImageProtectionValidationService
{
    /**
     * Validate image protection and watermark settings
     */
    public function validateSettings(array $data): array
    {
        $rules = $this->getValidationRules();
        $messages = $this->getValidationMessages();
        
        $validator = Validator::make($data, $rules, $messages);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        $validated = $validator->validated();
        
        // Apply business rule validations
        $this->validateBusinessRules($validated);
        
        return $validated;
    }
    
    /**
     * Get validation rules for image protection settings
     */
    protected function getValidationRules(): array
    {
        return [
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
            'watermark_logo_path' => 'nullable|string|max:255',
        ];
    }
    
    /**
     * Get validation messages
     */
    protected function getValidationMessages(): array
    {
        return [
            'watermark_text.max' => 'Watermark text cannot exceed 100 characters.',
            'watermark_position.in' => 'Please select a valid watermark position.',
            'watermark_opacity.integer' => 'Watermark opacity must be a number.',
            'watermark_opacity.min' => 'Watermark opacity must be at least 10%.',
            'watermark_opacity.max' => 'Watermark opacity cannot exceed 90%.',
            'watermark_size.in' => 'Please select a valid watermark size (small, medium, or large).',
            'watermark_text_color.regex' => 'Watermark text color must be a valid hex color (e.g., #ffffff).',
            'watermark_logo_path.max' => 'Watermark logo path cannot exceed 255 characters.',
        ];
    }
    
    /**
     * Validate business rules
     */
    protected function validateBusinessRules(array $data): void
    {
        $errors = [];
        
        // If image protection is enabled, at least one protection method must be enabled
        if (($data['image_protection_enabled'] ?? false)) {
            $hasProtection = ($data['right_click_protection'] ?? false) ||
                           ($data['drag_drop_protection'] ?? false) ||
                           ($data['keyboard_protection'] ?? false);
            
            if (!$hasProtection) {
                $errors['image_protection_enabled'] = 'At least one protection method must be enabled when image protection is active.';
            }
        }
        
        // If watermark is enabled, validate watermark configuration
        if (($data['watermark_enabled'] ?? false)) {
            $hasText = !empty($data['watermark_text'] ?? '');
            $hasLogo = !empty($data['watermark_logo_path'] ?? '');
            
            if (!$hasText && !$hasLogo) {
                $errors['watermark_text'] = 'Either watermark text or logo must be provided when watermarking is enabled.';
            }
            
            // Validate watermark opacity is set when watermarking is enabled
            if (!isset($data['watermark_opacity']) || $data['watermark_opacity'] < 10 || $data['watermark_opacity'] > 90) {
                $errors['watermark_opacity'] = 'Watermark opacity must be between 10% and 90% when watermarking is enabled.';
            }
            
            // Validate watermark position is set
            if (empty($data['watermark_position'] ?? '')) {
                $errors['watermark_position'] = 'Watermark position must be selected when watermarking is enabled.';
            }
            
            // Validate watermark size is set
            if (empty($data['watermark_size'] ?? '')) {
                $errors['watermark_size'] = 'Watermark size must be selected when watermarking is enabled.';
            }
        }
        
        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
    
    /**
     * Validate watermark logo upload
     */
    public function validateWatermarkLogo($file): void
    {
        $rules = [
            'watermark_logo' => 'required|file|mimes:jpeg,jpg,png,gif,webp,svg|max:2048'
        ];
        
        $messages = [
            'watermark_logo.required' => 'Please select a watermark logo file.',
            'watermark_logo.file' => 'The uploaded file is not valid.',
            'watermark_logo.mimes' => 'Watermark logo must be a JPEG, PNG, GIF, WebP, or SVG image.',
            'watermark_logo.max' => 'Watermark logo file size cannot exceed 2MB.',
        ];
        
        $validator = Validator::make(['watermark_logo' => $file], $rules, $messages);
        
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        
        // Additional file validation
        $this->validateImageFile($file);
    }
    
    /**
     * Validate image file properties
     */
    protected function validateImageFile($file): void
    {
        // Skip validation for SVG files
        if ($file->getMimeType() === 'image/svg+xml') {
            $this->validateSvgFile($file);
            return;
        }
        
        $imageInfo = getimagesize($file->getPathname());
        
        if ($imageInfo === false) {
            throw ValidationException::withMessages([
                'watermark_logo' => 'The uploaded file is not a valid image.'
            ]);
        }
        
        // Check image dimensions
        if ($imageInfo[0] > 500 || $imageInfo[1] > 500) {
            throw ValidationException::withMessages([
                'watermark_logo' => 'Watermark logo dimensions cannot exceed 500x500 pixels.'
            ]);
        }
        
        if ($imageInfo[0] < 20 || $imageInfo[1] < 20) {
            throw ValidationException::withMessages([
                'watermark_logo' => 'Watermark logo dimensions must be at least 20x20 pixels.'
            ]);
        }
    }
    
    /**
     * Validate SVG file for security
     */
    protected function validateSvgFile($file): void
    {
        $content = file_get_contents($file->getPathname());
        
        // Check for potentially dangerous elements/attributes
        $dangerousPatterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript:/i',
            '/on\w+\s*=/i', // onclick, onload, etc.
            '/<iframe\b/i',
            '/<object\b/i',
            '/<embed\b/i',
            '/<link\b/i',
            '/<meta\b/i',
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw ValidationException::withMessages([
                    'watermark_logo' => 'SVG file contains potentially unsafe content and cannot be uploaded.'
                ]);
            }
        }
        
        // Validate that it's actually an SVG
        if (!str_contains($content, '<svg') || !str_contains($content, '</svg>')) {
            throw ValidationException::withMessages([
                'watermark_logo' => 'Invalid SVG file format.'
            ]);
        }
    }
    
    /**
     * Get comprehensive validation summary
     */
    public function getValidationSummary(array $settings): array
    {
        $summary = [
            'is_valid' => true,
            'warnings' => [],
            'errors' => [],
            'recommendations' => []
        ];
        
        try {
            $this->validateSettings($settings);
        } catch (ValidationException $e) {
            $summary['is_valid'] = false;
            $summary['errors'] = $e->errors();
        }
        
        // Add warnings and recommendations
        if (($settings['image_protection_enabled'] ?? false) && !($settings['watermark_enabled'] ?? false)) {
            $summary['warnings'][] = 'Image protection is enabled but watermarking is disabled. Consider enabling watermarking for better brand protection.';
        }
        
        if (($settings['watermark_enabled'] ?? false) && !($settings['image_protection_enabled'] ?? false)) {
            $summary['warnings'][] = 'Watermarking is enabled but image protection is disabled. Consider enabling image protection for comprehensive security.';
        }
        
        if (($settings['watermark_enabled'] ?? false) && empty($settings['watermark_text'] ?? '') && empty($settings['watermark_logo_path'] ?? '')) {
            $summary['recommendations'][] = 'Add watermark text or upload a logo to make watermarks more effective.';
        }
        
        if (($settings['watermark_opacity'] ?? 50) > 70) {
            $summary['recommendations'][] = 'Consider reducing watermark opacity for better user experience while maintaining protection.';
        }
        
        return $summary;
    }
}
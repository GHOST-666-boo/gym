<?php

namespace App\Helpers;

use App\Services\SettingsService;
use Illuminate\Support\Facades\App;

class SettingsHelper
{
    /**
     * Get settings service instance
     */
    protected static function getService(): SettingsService
    {
        return App::make(SettingsService::class);
    }

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        return static::getService()->get($key, $default);
    }

    /**
     * Get site name
     */
    public static function siteName(): string
    {
        return static::get('site_name', config('app.name', 'Gym Machines'));
    }

    /**
     * Get site tagline
     */
    public static function siteTagline(): string
    {
        return static::get('site_tagline', 'Your fitness equipment destination');
    }

    /**
     * Get logo URL
     */
    public static function logoUrl(): string
    {
        return static::getService()->getLogoUrl();
    }

    /**
     * Get favicon URL
     */
    public static function faviconUrl(): string
    {
        return static::getService()->getFaviconUrl();
    }

    /**
     * Get business phone
     */
    public static function businessPhone(): ?string
    {
        return static::get('business_phone');
    }

    /**
     * Get business email
     */
    public static function businessEmail(): ?string
    {
        return static::get('business_email');
    }

    /**
     * Get business address
     */
    public static function businessAddress(): ?string
    {
        return static::get('business_address');
    }

    /**
     * Get business hours
     */
    public static function businessHours(): ?string
    {
        return static::get('business_hours');
    }

    /**
     * Get social media URLs with optimized caching
     */
    public static function socialMedia(): array
    {
        // Use getMultiple for better performance
        $keys = ['facebook_url', 'instagram_url', 'twitter_url', 'youtube_url'];
        $values = static::getService()->getMultiple($keys);
        
        return [
            'facebook' => $values['facebook_url'],
            'instagram' => $values['instagram_url'],
            'twitter' => $values['twitter_url'],
            'youtube' => $values['youtube_url'],
        ];
    }

    /**
     * Get Facebook URL
     */
    public static function facebookUrl(): ?string
    {
        $url = static::get('facebook_url');
        return !empty($url) ? $url : null;
    }

    /**
     * Get Instagram URL
     */
    public static function instagramUrl(): ?string
    {
        $url = static::get('instagram_url');
        return !empty($url) ? $url : null;
    }

    /**
     * Get Twitter URL
     */
    public static function twitterUrl(): ?string
    {
        $url = static::get('twitter_url');
        return !empty($url) ? $url : null;
    }

    /**
     * Get YouTube URL
     */
    public static function youtubeUrl(): ?string
    {
        $url = static::get('youtube_url');
        return !empty($url) ? $url : null;
    }

    /**
     * Get LinkedIn URL
     */
    public static function linkedinUrl(): ?string
    {
        $url = static::get('linkedin_url');
        return !empty($url) ? $url : null;
    }

    /**
     * Get TikTok URL
     */
    public static function tiktokUrl(): ?string
    {
        $url = static::get('tiktok_url');
        return !empty($url) ? $url : null;
    }

    /**
     * Get SEO meta title
     */
    public static function metaTitle(): ?string
    {
        return static::get('default_meta_title');
    }

    /**
     * Get SEO meta description
     */
    public static function metaDescription(): ?string
    {
        return static::get('default_meta_description');
    }

    /**
     * Get SEO meta keywords
     */
    public static function metaKeywords(): ?string
    {
        return static::get('meta_keywords');
    }

    /**
     * Check if maintenance mode is enabled
     */
    public static function isMaintenanceMode(): bool
    {
        return (bool) static::get('maintenance_mode', false);
    }

    /**
     * Check if user registration is allowed
     */
    public static function allowRegistration(): bool
    {
        return (bool) static::get('allow_registration', true);
    }

    /**
     * Get currency symbol
     */
    public static function currencySymbol(): string
    {
        return static::get('currency_symbol', '$');
    }

    /**
     * Get currency position (before/after)
     */
    public static function currencyPosition(): string
    {
        return static::get('currency_position', 'before');
    }

    /**
     * Format price with currency
     */
    public static function formatPrice(float $price): string
    {
        $symbol = static::currencySymbol();
        $position = static::currencyPosition();
        $formattedPrice = number_format($price, 2);

        return $position === 'before' 
            ? $symbol . $formattedPrice 
            : $formattedPrice . $symbol;
    }

    /**
     * Get all contact information with optimized caching
     */
    public static function contactInfo(): array
    {
        // Use getMultiple for better performance
        $keys = ['business_phone', 'business_email', 'business_address', 'business_hours'];
        $values = static::getService()->getMultiple($keys);
        
        return [
            'phone' => $values['business_phone'],
            'email' => $values['business_email'],
            'address' => $values['business_address'],
            'hours' => $values['business_hours'],
        ];
    }

    /**
     * Get all SEO settings with optimized caching
     */
    public static function seoSettings(): array
    {
        // Use getMultiple for better performance
        $keys = ['default_meta_title', 'default_meta_description', 'meta_keywords'];
        $values = static::getService()->getMultiple($keys);
        
        return [
            'title' => $values['default_meta_title'],
            'description' => $values['default_meta_description'],
            'keywords' => $values['meta_keywords'],
            'favicon' => static::faviconUrl(),
        ];
    }

    /**
     * Check if a social media URL is set
     */
    public static function hasSocialMedia(string $platform): bool
    {
        $url = static::get($platform . '_url');
        return !empty($url);
    }

    /**
     * Get all non-empty social media URLs
     */
    public static function activeSocialMedia(): array
    {
        $social = static::socialMedia();
        return array_filter($social, function($url) {
            return !empty($url);
        });
    }
}
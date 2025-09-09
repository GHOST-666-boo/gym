<?php

use App\Helpers\SettingsHelper;

if (!function_exists('setting')) {
    /**
     * Get a setting value by key
     */
    function setting(string $key, $default = null)
    {
        return SettingsHelper::get($key, $default);
    }
}

if (!function_exists('site_name')) {
    /**
     * Get site name
     */
    function site_name(): string
    {
        return SettingsHelper::siteName();
    }
}

if (!function_exists('site_tagline')) {
    /**
     * Get site tagline
     */
    function site_tagline(): string
    {
        return SettingsHelper::siteTagline();
    }
}

if (!function_exists('site_logo')) {
    /**
     * Get logo URL
     */
    function site_logo(): string
    {
        return SettingsHelper::logoUrl();
    }
}

if (!function_exists('site_favicon')) {
    /**
     * Get favicon URL
     */
    function site_favicon(): string
    {
        return SettingsHelper::faviconUrl();
    }
}

if (!function_exists('business_phone')) {
    /**
     * Get business phone
     */
    function business_phone(): ?string
    {
        return SettingsHelper::businessPhone();
    }
}

if (!function_exists('business_email')) {
    /**
     * Get business email
     */
    function business_email(): ?string
    {
        return SettingsHelper::businessEmail();
    }
}

if (!function_exists('business_address')) {
    /**
     * Get business address
     */
    function business_address(): ?string
    {
        return SettingsHelper::businessAddress();
    }
}

if (!function_exists('business_hours')) {
    /**
     * Get business hours
     */
    function business_hours(): ?string
    {
        return SettingsHelper::businessHours();
    }
}

if (!function_exists('social_media')) {
    /**
     * Get all social media URLs
     */
    function social_media(): array
    {
        return SettingsHelper::socialMedia();
    }
}

if (!function_exists('active_social_media')) {
    /**
     * Get all non-empty social media URLs
     */
    function active_social_media(): array
    {
        return SettingsHelper::activeSocialMedia();
    }
}

if (!function_exists('facebook_url')) {
    /**
     * Get Facebook URL
     */
    function facebook_url(): ?string
    {
        return SettingsHelper::facebookUrl();
    }
}

if (!function_exists('instagram_url')) {
    /**
     * Get Instagram URL
     */
    function instagram_url(): ?string
    {
        return SettingsHelper::instagramUrl();
    }
}

if (!function_exists('twitter_url')) {
    /**
     * Get Twitter URL
     */
    function twitter_url(): ?string
    {
        return SettingsHelper::twitterUrl();
    }
}

if (!function_exists('youtube_url')) {
    /**
     * Get YouTube URL
     */
    function youtube_url(): ?string
    {
        return SettingsHelper::youtubeUrl();
    }
}

if (!function_exists('linkedin_url')) {
    /**
     * Get LinkedIn URL
     */
    function linkedin_url(): ?string
    {
        return SettingsHelper::linkedinUrl();
    }
}

if (!function_exists('tiktok_url')) {
    /**
     * Get TikTok URL
     */
    function tiktok_url(): ?string
    {
        return SettingsHelper::tiktokUrl();
    }
}

if (!function_exists('meta_title')) {
    /**
     * Get default meta title
     */
    function meta_title(): ?string
    {
        return SettingsHelper::metaTitle();
    }
}

if (!function_exists('meta_description')) {
    /**
     * Get default meta description
     */
    function meta_description(): ?string
    {
        return SettingsHelper::metaDescription();
    }
}

if (!function_exists('meta_keywords')) {
    /**
     * Get meta keywords
     */
    function meta_keywords(): ?string
    {
        return SettingsHelper::metaKeywords();
    }
}

if (!function_exists('is_maintenance_mode')) {
    /**
     * Check if maintenance mode is enabled
     */
    function is_maintenance_mode(): bool
    {
        return SettingsHelper::isMaintenanceMode();
    }
}

if (!function_exists('allow_registration')) {
    /**
     * Check if user registration is allowed
     */
    function allow_registration(): bool
    {
        return SettingsHelper::allowRegistration();
    }
}

if (!function_exists('format_price')) {
    /**
     * Format price with currency
     */
    function format_price(float $price): string
    {
        return SettingsHelper::formatPrice($price);
    }
}

if (!function_exists('currency_symbol')) {
    /**
     * Get currency symbol
     */
    function currency_symbol(): string
    {
        return SettingsHelper::currencySymbol();
    }
}

if (!function_exists('contact_info')) {
    /**
     * Get all contact information
     */
    function contact_info(): array
    {
        return SettingsHelper::contactInfo();
    }
}

if (!function_exists('seo_settings')) {
    /**
     * Get all SEO settings
     */
    function seo_settings(): array
    {
        return SettingsHelper::seoSettings();
    }
}
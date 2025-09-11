<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Image Protection Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the image protection
    | and watermarking system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Token Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for secure image token generation and validation.
    |
    */
    'token' => [
        'algorithm' => env('IMAGE_PROTECTION_ALGORITHM', 'sha256'),
        'expires_in' => env('IMAGE_PROTECTION_TOKEN_EXPIRES', 24 * 60 * 60), // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Referers
    |--------------------------------------------------------------------------
    |
    | List of allowed referer domains for protected images. Leave empty
    | to disable referer checking.
    |
    */
    'allowed_referers' => [
        // Add your domain(s) here
        // 'yourdomain.com',
        // 'www.yourdomain.com',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting settings for protected image access.
    |
    */
    'rate_limiting' => [
        'max_attempts' => env('IMAGE_PROTECTION_MAX_ATTEMPTS', 100),
        'decay_minutes' => env('IMAGE_PROTECTION_DECAY_MINUTES', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Image Paths
    |--------------------------------------------------------------------------
    |
    | Directories that are allowed to serve protected images.
    |
    */
    'allowed_paths' => [
        'products/',
        'uploads/',
        'images/',
        'watermarks/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME Types
    |--------------------------------------------------------------------------
    |
    | MIME types that are allowed for protected images.
    |
    */
    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configuration for error handling and fallbacks.
    |
    */
    'error_handling' => [
        'max_notifications_per_day' => env('IMAGE_PROTECTION_MAX_NOTIFICATIONS', 10),
        'notification_retention_days' => env('IMAGE_PROTECTION_NOTIFICATION_RETENTION', 7),
        'enable_admin_notifications' => env('IMAGE_PROTECTION_ADMIN_NOTIFICATIONS', true),
        'log_protection_attempts' => env('IMAGE_PROTECTION_LOG_ATTEMPTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Options
    |--------------------------------------------------------------------------
    |
    | Configuration for fallback mechanisms when primary protection fails.
    |
    */
    'fallbacks' => [
        'enable_css_watermarks' => env('IMAGE_PROTECTION_CSS_FALLBACK', true),
        'enable_noscript_protection' => env('IMAGE_PROTECTION_NOSCRIPT', true),
        'placeholder_image' => env('IMAGE_PROTECTION_PLACEHOLDER', 'images/placeholder.svg'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for watermark and protection caching.
    |
    */
    'cache' => [
        'watermark_ttl' => env('WATERMARK_CACHE_TTL', 24 * 60 * 60), // 24 hours
        'protection_script_ttl' => env('PROTECTION_SCRIPT_CACHE_TTL', 60 * 60), // 1 hour
        'cleanup_old_files_days' => env('WATERMARK_CLEANUP_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Settings to optimize performance of the protection system.
    |
    */
    'performance' => [
        'enable_range_requests' => env('IMAGE_PROTECTION_RANGE_REQUESTS', true),
        'chunk_size' => env('IMAGE_PROTECTION_CHUNK_SIZE', 8192), // 8KB
        'max_file_size' => env('IMAGE_PROTECTION_MAX_FILE_SIZE', 100 * 1024 * 1024), // 100MB
    ],
];
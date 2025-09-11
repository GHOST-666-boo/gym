<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SiteSetting extends Model
{
    /**
     * Cache TTL for settings (24 hours)
     */
    const CACHE_TTL = 86400;
    
    /**
     * Cache key prefix
     */
    const CACHE_PREFIX = 'site_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group'
    ];

    protected $casts = [
        'value' => 'string',
    ];

    /**
     * Get a setting value by key with enhanced caching
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = "site_setting_{$key}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }
            
            return static::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value with optimized cache invalidation
     */
    public static function set(string $key, $value, string $type = 'string', string $group = 'general')
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => static::prepareValue($value, $type),
                'type' => $type,
                'group' => $group
            ]
        );

        // Clear related caches
        static::invalidateSettingCache($key, $group);
        
        return $setting;
    }

    /**
     * Get all settings by group with optimized query
     */
    public static function getByGroup(string $group)
    {
        $cacheKey = self::CACHE_PREFIX . "_group_{$group}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($group) {
            // Single optimized query to get all settings for the group
            $settings = static::where('group', $group)
                ->select('key', 'value', 'type')
                ->get();
            
            $result = [];
            foreach ($settings as $setting) {
                $result[$setting->key] = static::castValue($setting->value, $setting->type);
            }
            
            return collect($result);
        });
    }

    /**
     * Clear all settings cache with optimized queries
     */
    public static function clearCache()
    {
        // Get all keys and groups in single queries
        $keys = static::pluck('key');
        $groups = static::distinct('group')->pluck('group');
        
        // Clear individual setting caches
        foreach ($keys as $key) {
            Cache::forget("site_setting_{$key}");
        }
        
        // Clear group caches
        foreach ($groups as $group) {
            Cache::forget(self::CACHE_PREFIX . "_group_{$group}");
        }
        
        // Clear the all settings cache
        Cache::forget(self::CACHE_PREFIX . '_all');
    }
    
    /**
     * Invalidate cache for a specific setting and its group
     */
    protected static function invalidateSettingCache(string $key, string $group): void
    {
        // Clear individual setting cache
        Cache::forget("site_setting_{$key}");
        
        // Clear group cache
        Cache::forget(self::CACHE_PREFIX . "_group_{$group}");
        
        // Clear all settings cache
        Cache::forget(self::CACHE_PREFIX . '_all');
    }

    /**
     * Cast value to appropriate type (made public for service access)
     */
    public static function castValue($value, string $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            default:
                return $value;
        }
    }

    /**
     * Prepare value for storage
     */
    protected static function prepareValue($value, string $type)
    {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'json':
                return json_encode($value);
            default:
                return (string) $value;
        }
    }

    /**
     * Boot method to clear cache on model events with optimized invalidation
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            static::invalidateSettingCache($setting->key, $setting->group);
            
            // Fire event for watermark cache invalidation
            event('site_setting.saved', $setting);
        });

        static::deleted(function ($setting) {
            static::invalidateSettingCache($setting->key, $setting->group);
            
            // Fire event for watermark cache invalidation
            event('site_setting.deleted', $setting);
        });
    }
}

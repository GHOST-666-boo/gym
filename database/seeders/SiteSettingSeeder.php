<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SiteSetting;

class SiteSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSettings = [
            // General Settings
            [
                'key' => 'site_name',
                'value' => 'Gym Machines Website',
                'type' => 'string',
                'group' => 'general'
            ],
            [
                'key' => 'site_tagline',
                'value' => 'Your Premier Destination for Quality Gym Equipment',
                'type' => 'string',
                'group' => 'general'
            ],
            [
                'key' => 'logo_path',
                'value' => '',
                'type' => 'file',
                'group' => 'general'
            ],
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'general'
            ],
            [
                'key' => 'allow_registration',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'general'
            ],

            // Contact Information
            [
                'key' => 'business_phone',
                'value' => '+1 (555) 123-4567',
                'type' => 'string',
                'group' => 'contact'
            ],
            [
                'key' => 'business_email',
                'value' => 'info@gymmachines.com',
                'type' => 'string',
                'group' => 'contact'
            ],
            [
                'key' => 'business_address',
                'value' => '123 Fitness Street, Gym City, GC 12345',
                'type' => 'string',
                'group' => 'contact'
            ],
            [
                'key' => 'business_hours',
                'value' => 'Mon-Fri: 9AM-6PM, Sat: 10AM-4PM, Sun: Closed',
                'type' => 'string',
                'group' => 'contact'
            ],

            // Social Media
            [
                'key' => 'facebook_url',
                'value' => '',
                'type' => 'string',
                'group' => 'social'
            ],
            [
                'key' => 'instagram_url',
                'value' => '',
                'type' => 'string',
                'group' => 'social'
            ],
            [
                'key' => 'twitter_url',
                'value' => '',
                'type' => 'string',
                'group' => 'social'
            ],
            [
                'key' => 'youtube_url',
                'value' => '',
                'type' => 'string',
                'group' => 'social'
            ],

            // SEO Settings
            [
                'key' => 'default_meta_title',
                'value' => 'Gym Machines Website - Quality Fitness Equipment',
                'type' => 'string',
                'group' => 'seo'
            ],
            [
                'key' => 'default_meta_description',
                'value' => 'Discover premium gym equipment and fitness machines. Quality products for your home gym or commercial fitness center.',
                'type' => 'string',
                'group' => 'seo'
            ],
            [
                'key' => 'meta_keywords',
                'value' => 'gym equipment, fitness machines, home gym, commercial fitness, exercise equipment',
                'type' => 'string',
                'group' => 'seo'
            ],
            [
                'key' => 'favicon_path',
                'value' => '',
                'type' => 'file',
                'group' => 'seo'
            ],

            // Advanced Settings
            [
                'key' => 'smtp_host',
                'value' => '',
                'type' => 'string',
                'group' => 'advanced'
            ],
            [
                'key' => 'smtp_port',
                'value' => '587',
                'type' => 'string',
                'group' => 'advanced'
            ],
            [
                'key' => 'smtp_username',
                'value' => '',
                'type' => 'string',
                'group' => 'advanced'
            ],
            [
                'key' => 'smtp_password',
                'value' => '',
                'type' => 'string',
                'group' => 'advanced'
            ],
            [
                'key' => 'currency_symbol',
                'value' => '$',
                'type' => 'string',
                'group' => 'advanced'
            ],
            [
                'key' => 'currency_position',
                'value' => 'before',
                'type' => 'string',
                'group' => 'advanced'
            ],

            // Image Protection Settings
            [
                'key' => 'image_protection_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'image_protection'
            ],
            [
                'key' => 'watermark_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'image_protection'
            ],
            [
                'key' => 'right_click_protection',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'image_protection'
            ],
            [
                'key' => 'drag_drop_protection',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'image_protection'
            ],
            [
                'key' => 'keyboard_protection',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'image_protection'
            ],

            // Watermark Configuration
            [
                'key' => 'watermark_text',
                'value' => 'Gym Machines Website',
                'type' => 'string',
                'group' => 'watermark'
            ],
            [
                'key' => 'watermark_logo_path',
                'value' => '',
                'type' => 'file',
                'group' => 'watermark'
            ],
            [
                'key' => 'watermark_position',
                'value' => 'bottom-right',
                'type' => 'string',
                'group' => 'watermark'
            ],
            [
                'key' => 'watermark_opacity',
                'value' => '50',
                'type' => 'integer',
                'group' => 'watermark'
            ],
            [
                'key' => 'watermark_size',
                'value' => 'medium',
                'type' => 'string',
                'group' => 'watermark'
            ],
            [
                'key' => 'watermark_text_color',
                'value' => '#ffffff',
                'type' => 'string',
                'group' => 'watermark'
            ],
        ];

        foreach ($defaultSettings as $setting) {
            SiteSetting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'group' => $setting['group']
                ]
            );
        }
    }
}

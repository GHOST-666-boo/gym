<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\SiteSetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The site_settings table structure already supports our watermark settings
        // We just need to add the default watermark settings data
        $watermarkSettings = [
            // Core toggles
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

            // Protection options
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

            // Watermark configuration
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

        foreach ($watermarkSettings as $setting) {
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove watermark settings
        $watermarkKeys = [
            'image_protection_enabled',
            'watermark_enabled',
            'right_click_protection',
            'drag_drop_protection',
            'keyboard_protection',
            'watermark_text',
            'watermark_logo_path',
            'watermark_position',
            'watermark_opacity',
            'watermark_size',
            'watermark_text_color',
        ];

        SiteSetting::whereIn('key', $watermarkKeys)->delete();
    }
};

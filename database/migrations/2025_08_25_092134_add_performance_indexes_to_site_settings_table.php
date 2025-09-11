<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            // Add individual index on group column for group-based queries
            $table->index('group', 'idx_site_settings_group');
            
            // Add index on type column for type-based filtering
            $table->index('type', 'idx_site_settings_type');
            
            // Add composite index for group and type queries
            $table->index(['group', 'type'], 'idx_site_settings_group_type');
            
            // Add index on updated_at for cache invalidation queries
            $table->index('updated_at', 'idx_site_settings_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropIndex('idx_site_settings_group');
            $table->dropIndex('idx_site_settings_type');
            $table->dropIndex('idx_site_settings_group_type');
            $table->dropIndex('idx_site_settings_updated_at');
        });
    }
};

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
        Schema::table('products', function (Blueprint $table) {
            // Add indexes for frequently queried fields
            $table->index('name'); // For search functionality
            $table->index('price'); // For price sorting/filtering
            $table->index(['category_id', 'created_at']); // For category listings with sorting
            $table->index('created_at'); // For latest products queries
        });

        Schema::table('categories', function (Blueprint $table) {
            // Add index for name field (for search/filtering)
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['price']);
            $table->dropIndex(['category_id', 'created_at']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
    }
};
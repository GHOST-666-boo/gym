<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Product;
use App\Models\ProductImage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing product images to the new product_images table
        $products = Product::whereNotNull('image_path')->get();
        
        foreach ($products as $product) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $product->image_path,
                'alt_text' => $product->name,
                'sort_order' => 0,
                'is_primary' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove migrated images (only those that match existing product image_path)
        $products = Product::whereNotNull('image_path')->get();
        
        foreach ($products as $product) {
            ProductImage::where('product_id', $product->id)
                ->where('image_path', $product->image_path)
                ->delete();
        }
    }
};
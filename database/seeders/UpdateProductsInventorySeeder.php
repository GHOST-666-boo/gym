<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateProductsInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Updating existing products with inventory data...');
        
        $products = Product::whereNull('stock_quantity')->get();
        
        foreach ($products as $product) {
            $product->update([
                'stock_quantity' => fake()->numberBetween(0, 100),
                'low_stock_threshold' => fake()->numberBetween(5, 15),
                'track_inventory' => fake()->boolean(80), // 80% chance of tracking inventory
            ]);
        }
        
        $this->command->info("Updated {$products->count()} products with inventory data.");
        
        // Show some statistics
        $totalProducts = Product::count();
        $trackedProducts = Product::where('track_inventory', true)->count();
        $lowStockProducts = Product::lowStock()->count();
        $outOfStockProducts = Product::outOfStock()->count();
        
        $this->command->info("Inventory Statistics:");
        $this->command->info("- Total products: {$totalProducts}");
        $this->command->info("- Products with inventory tracking: {$trackedProducts}");
        $this->command->info("- Low stock products: {$lowStockProducts}");
        $this->command->info("- Out of stock products: {$outOfStockProducts}");
    }
}

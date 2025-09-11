<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Console\Command;

class CreateTestProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:create-test {count=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create test products with inventory data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = $this->argument('count');
        
        $this->info("Creating {$count} test products with inventory data...");
        
        // Create some categories first if they don't exist
        if (Category::count() === 0) {
            $categories = [
                ['name' => 'Cardio Equipment', 'slug' => 'cardio-equipment', 'description' => 'Treadmills, bikes, and other cardio machines'],
                ['name' => 'Strength Training', 'slug' => 'strength-training', 'description' => 'Weight machines and strength equipment'],
                ['name' => 'Free Weights', 'slug' => 'free-weights', 'description' => 'Dumbbells, barbells, and weight plates'],
                ['name' => 'Functional Training', 'slug' => 'functional-training', 'description' => 'Cable machines and functional trainers'],
            ];
            
            foreach ($categories as $categoryData) {
                Category::create($categoryData);
            }
            
            $this->info('Created 4 categories');
        }
        
        // Create products with various inventory scenarios
        $products = [];
        
        for ($i = 0; $i < $count; $i++) {
            $stockScenario = rand(1, 4);
            
            switch ($stockScenario) {
                case 1: // Out of stock
                    $stockQuantity = 0;
                    $trackInventory = true;
                    break;
                case 2: // Low stock
                    $stockQuantity = rand(1, 5);
                    $trackInventory = true;
                    break;
                case 3: // Good stock
                    $stockQuantity = rand(20, 100);
                    $trackInventory = true;
                    break;
                case 4: // Not tracked
                    $stockQuantity = 0;
                    $trackInventory = false;
                    break;
            }
            
            $product = Product::factory()->create([
                'category_id' => Category::inRandomOrder()->first()->id,
                'stock_quantity' => $stockQuantity,
                'low_stock_threshold' => rand(5, 15),
                'track_inventory' => $trackInventory,
            ]);
            
            $products[] = $product;
        }
        
        $this->info("Created {$count} test products");
        
        // Show statistics
        $totalProducts = Product::count();
        $trackedProducts = Product::where('track_inventory', true)->count();
        $lowStockProducts = Product::lowStock()->count();
        $outOfStockProducts = Product::outOfStock()->count();
        
        $this->info("Inventory Statistics:");
        $this->info("- Total products: {$totalProducts}");
        $this->info("- Products with inventory tracking: {$trackedProducts}");
        $this->info("- Low stock products: {$lowStockProducts}");
        $this->info("- Out of stock products: {$outOfStockProducts}");
        
        return 0;
    }
}

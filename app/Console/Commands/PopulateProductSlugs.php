<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class PopulateProductSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:populate-slugs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate slugs for existing products that don\'t have them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $products = Product::whereNull('slug')->orWhere('slug', '')->get();
        
        if ($products->isEmpty()) {
            $this->info('All products already have slugs.');
            return;
        }

        $this->info("Found {$products->count()} products without slugs. Generating...");

        foreach ($products as $product) {
            $product->slug = $product->generateSlug();
            $product->save();
            $this->line("Generated slug '{$product->slug}' for product: {$product->name}");
        }

        $this->info('Slug generation completed!');
    }
}

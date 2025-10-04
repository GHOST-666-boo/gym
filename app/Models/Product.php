<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'price',
        'short_description',
        'long_description',
        'image_path',
        'category_id',
        'stock_quantity',
        'low_stock_threshold',
        'track_inventory',
        'dimensions',
        'material',
        'care_instructions',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'track_inventory' => 'boolean',
        ];
    }

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the images for the product.
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class)->ordered();
    }

    /**
     * Get the primary image for the product.
     */
    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->primary();
    }

    /**
     * Get the reviews for the product.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get only approved reviews for the product.
     */
    public function approvedReviews()
    {
        return $this->hasMany(Review::class)->approved();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Generate a unique slug from the product name.
     */
    public function generateSlug(): string
    {
        $slug = Str::slug($this->name);
        $originalSlug = $slug;
        $counter = 1;

        // Ensure slug is unique
        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Scope for optimized product listings (select only necessary fields)
     */
    public function scopeForListing($query)
    {
        return $query->select(['id', 'name', 'slug', 'price', 'short_description', 'image_path', 'category_id', 'created_at']);
    }

    /**
     * Scope for featured products with eager loading
     */
    public function scopeFeatured($query, $limit = 6)
    {
        return $query->with(['category:id,name,slug', 'images'])
            ->forListing()
            ->latest()
            ->take($limit);
    }

    /**
     * Scope for products by category with optimization
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId)
            ->with(['category:id,name,slug', 'images'])
            ->forListing();
    }

    /**
     * Scope for related products
     */
    public function scopeRelated($query, $categoryId, $excludeId, $limit = 4)
    {
        return $query->where('category_id', $categoryId)
            ->where('id', '!=', $excludeId)
            ->with('category:id,name,slug')
            ->forListing()
            ->take($limit);
    }

    /**
     * Scope for searching products by text
     */
    public function scopeSearch($query, $searchTerm)
    {
        if (empty($searchTerm)) {
            return $query;
        }

        return $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('short_description', 'LIKE', "%{$searchTerm}%")
                ->orWhere('long_description', 'LIKE', "%{$searchTerm}%")
                ->orWhereHas('category', function ($categoryQuery) use ($searchTerm) {
                    $categoryQuery->where('name', 'LIKE', "%{$searchTerm}%");
                });
        });
    }

    /**
     * Scope for filtering by price range
     */
    public function scopePriceRange($query, $minPrice = null, $maxPrice = null)
    {
        if ($minPrice !== null) {
            $query->where('price', '>=', $minPrice);
        }

        if ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }

        return $query;
    }

    /**
     * Scope for filtering by category
     */
    public function scopeFilterByCategory($query, $categoryId)
    {
        if (empty($categoryId)) {
            return $query;
        }

        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope for sorting products
     */
    public function scopeSortBy($query, $sortBy = 'name', $sortDirection = 'asc')
    {
        $allowedSorts = ['name', 'price', 'created_at'];
        $allowedDirections = ['asc', 'desc'];

        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'name';
        }

        if (!in_array($sortDirection, $allowedDirections)) {
            $sortDirection = 'asc';
        }

        return $query->orderBy($sortBy, $sortDirection);
    }

    /**
     * Get the average rating for the product.
     */
    public function getAverageRatingAttribute(): float
    {
        return $this->approvedReviews()->avg('rating') ?? 0;
    }

    /**
     * Get the total number of approved reviews.
     */
    public function getReviewsCountAttribute(): int
    {
        return $this->approvedReviews()->count();
    }

    /**
     * Get the rating distribution (count of each rating 1-5).
     */
    public function getRatingDistributionAttribute(): array
    {
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $this->approvedReviews()->where('rating', $i)->count();
        }
        return $distribution;
    }

    /**
     * Get formatted star rating display.
     */
    public function getStarRatingAttribute(): string
    {
        $rating = round($this->average_rating);
        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
    }

    /**
     * Check if product is in stock.
     */
    public function isInStock(): bool
    {
        if (!$this->track_inventory) {
            return true;
        }

        return $this->stock_quantity > 0;
    }

    /**
     * Check if product is low on stock.
     */
    public function isLowStock(): bool
    {
        if (!$this->track_inventory) {
            return false;
        }

        return $this->stock_quantity <= $this->low_stock_threshold && $this->stock_quantity > 0;
    }

    /**
     * Check if product is out of stock.
     */
    public function isOutOfStock(): bool
    {
        if (!$this->track_inventory) {
            return false;
        }

        return $this->stock_quantity <= 0;
    }

    /**
     * Get stock status as a string.
     */
    public function getStockStatusAttribute(): string
    {
        if (!$this->track_inventory) {
            return 'Available';
        }

        if ($this->isOutOfStock()) {
            return 'Out of Stock';
        }

        if ($this->isLowStock()) {
            return 'Low Stock';
        }

        return 'In Stock';
    }

    /**
     * Get stock status color for display.
     */
    public function getStockStatusColorAttribute(): string
    {
        if (!$this->track_inventory) {
            return 'text-green-600';
        }

        if ($this->isOutOfStock()) {
            return 'text-red-600';
        }

        if ($this->isLowStock()) {
            return 'text-yellow-600';
        }

        return 'text-green-600';
    }

    /**
     * Scope for products that are in stock.
     */
    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_inventory', false)
                ->orWhere('stock_quantity', '>', 0);
        });
    }

    /**
     * Scope for products that are low on stock.
     */
    public function scopeLowStock($query)
    {
        return $query->where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('stock_quantity', '>', 0);
    }

    /**
     * Scope for products that are out of stock.
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('track_inventory', true)
            ->where('stock_quantity', '<=', 0);
    }

    /**
     * Get the primary image URL with fallback to legacy image_path.
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        // First try to get from new images relationship
        if ($this->primaryImage) {
            return $this->primaryImage->url;
        }

        // Fallback to legacy image_path
        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }

        return null;
    }

    /**
     * Get all image URLs including legacy image_path.
     */
    public function getAllImageUrlsAttribute(): array
    {
        $urls = [];

        // Add images from new relationship
        foreach ($this->images as $image) {
            $urls[] = $image->url;
        }

        // Add legacy image if it exists and not already in new images
        if ($this->image_path && empty($urls)) {
            $urls[] = asset('storage/' . $this->image_path);
        }

        return $urls;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = $product->generateSlug();
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = $product->generateSlug();
            }
        });
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'image_path',
        'alt_text',
        'sort_order',
        'is_primary',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the product that owns the image.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope for ordered images.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    /**
     * Scope for primary image.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Get the full URL for the image.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->image_path);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // When setting an image as primary, unset other primary images for the same product
        static::saving(function ($productImage) {
            if ($productImage->is_primary) {
                static::where('product_id', $productImage->product_id)
                    ->where('id', '!=', $productImage->id ?? 0)
                    ->update(['is_primary' => false]);
            }
        });

        // If this is the first image for a product, make it primary
        static::created(function ($productImage) {
            $imageCount = static::where('product_id', $productImage->product_id)->count();
            if ($imageCount === 1) {
                $productImage->update(['is_primary' => true]);
            }
        });
    }
}
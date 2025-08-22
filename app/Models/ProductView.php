<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductView extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'ip_address',
        'user_agent',
        'referrer',
        'session_id',
        'viewed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    /**
     * Get the product that was viewed.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope for views within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('viewed_at', [$startDate, $endDate]);
    }

    /**
     * Scope for views today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('viewed_at', today());
    }

    /**
     * Scope for views this week.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('viewed_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * Scope for views this month.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('viewed_at', now()->month)
                    ->whereYear('viewed_at', now()->year);
    }

    /**
     * Scope for unique views (by IP address).
     */
    public function scopeUniqueViews($query)
    {
        return $query->distinct('ip_address');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'message',
        'ip_address',
        'user_agent',
        'referrer',
        'email_sent',
        'submitted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_sent' => 'boolean',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * Scope for submissions within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('submitted_at', [$startDate, $endDate]);
    }

    /**
     * Scope for submissions today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('submitted_at', today());
    }

    /**
     * Scope for submissions this week.
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('submitted_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * Scope for submissions this month.
     */
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('submitted_at', now()->month)
                    ->whereYear('submitted_at', now()->year);
    }

    /**
     * Scope for successfully sent emails.
     */
    public function scopeEmailSent($query)
    {
        return $query->where('email_sent', true);
    }

    /**
     * Scope for failed email submissions.
     */
    public function scopeEmailFailed($query)
    {
        return $query->where('email_sent', false);
    }
}

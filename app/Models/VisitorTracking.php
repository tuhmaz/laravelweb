<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitorTracking extends Model
{
    protected $table = 'visitors_tracking';

    protected $fillable = [
        'ip_address',
        'user_agent',
        'country',
        'city',
        'user_id',
        'last_activity'
    ];

    protected $casts = [
        'last_activity' => 'datetime'
    ];

    /**
     * Get the user associated with the visitor.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the page visits for the visitor.
     */
    public function pageVisits(): HasMany
    {
        return $this->hasMany(PageVisit::class, 'visitor_id');
    }

    /**
     * Scope a query to only include online visitors.
     */
    public function scopeOnline($query)
    {
        return $query->where('last_activity', '>=', now()->subMinutes(5));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Membership extends Model
{
    protected $fillable = [
        'school_id',
        'membership_type',
        'status',
        'expires_at',
        'billing_period',
        'stripe_subscription_id',
        'stripe_customer_id',
        'purchased_at',
        'renewal_date',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'purchased_at' => 'datetime',
            'renewal_date' => 'datetime',
        ];
    }

    /**
     * Get the school that owns the membership.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get payments for this membership.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'school_id', 'school_id')
            ->where('membership_type', $this->membership_type);
    }

    /**
     * Check if membership is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               ($this->expires_at === null || $this->expires_at->isFuture());
    }
}

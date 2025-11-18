<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'school_id',
        'membership_type',
        'amount',
        'currency',
        'stripe_payment_intent_id',
        'stripe_customer_id',
        'billing_period',
        'status',
        'coupon_code',
        'discount_amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    /**
     * Get the school that made the payment.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the coupon used for this payment.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_code', 'code');
    }
}

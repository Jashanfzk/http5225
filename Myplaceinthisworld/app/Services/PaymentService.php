<?php

namespace App\Services;

use App\Models\School;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\Membership;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe Checkout Session for membership purchase.
     */
    public function createCheckoutSession(School $school, string $membershipType, string $billingPeriod, ?string $couponCode = null): Session
    {
        $prices = $this->getPrices();
        $priceId = $prices[$membershipType][$billingPeriod] ?? null;

        if (!$priceId) {
            throw new \Exception("Price not configured for {$membershipType} - {$billingPeriod}");
        }

        $amount = $this->getAmount($membershipType, $billingPeriod);
        $discountAmount = 0;

        // Validate and apply coupon if provided
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();
            if ($coupon && $coupon->isValid()) {
                $discountAmount = $coupon->calculateDiscount($amount);
                $amount -= $discountAmount;
            }
        }

        $lineItems = [
            [
                'price' => $priceId,
                'quantity' => 1,
            ],
        ];

        $sessionData = [
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => $billingPeriod === 'monthly' ? 'subscription' : 'payment',
            'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payment.cancel'),
            'metadata' => [
                'school_id' => $school->id,
                'membership_type' => $membershipType,
                'billing_period' => $billingPeriod,
                'coupon_code' => $couponCode ?? '',
            ],
        ];

        if ($billingPeriod === 'monthly') {
            $sessionData['subscription_data'] = [
                'metadata' => [
                    'school_id' => $school->id,
                    'membership_type' => $membershipType,
                ],
            ];
        }

        if ($couponCode && isset($coupon)) {
            $sessionData['discounts'] = [[
                'coupon' => $couponCode,
            ]];
        }

        return Session::create($sessionData);
    }

    /**
     * Get membership prices.
     * TODO: These should be configured in database or config file.
     */
    protected function getPrices(): array
    {
        return [
            'primary' => [
                'annual' => env('STRIPE_PRICE_PRIMARY_ANNUAL'),
                'monthly' => env('STRIPE_PRICE_PRIMARY_MONTHLY'),
            ],
            'junior_intermediate' => [
                'annual' => env('STRIPE_PRICE_JUNIOR_ANNUAL'),
                'monthly' => env('STRIPE_PRICE_JUNIOR_MONTHLY'),
            ],
        ];
    }

    /**
     * Get membership amount in dollars.
     */
    protected function getAmount(string $membershipType, string $billingPeriod): float
    {
        if ($billingPeriod === 'annual') {
            return 399.99;
        }

        // Monthly price (approximately $399.99 / 12, rounded)
        return 33.33;
    }

    /**
     * Handle successful payment.
     */
    public function handleSuccessfulPayment(string $sessionId): Payment
    {
        $session = Session::retrieve($sessionId);
        
        $schoolId = $session->metadata->school_id;
        $membershipType = $session->metadata->membership_type;
        $billingPeriod = $session->metadata->billing_period;
        $couponCode = $session->metadata->coupon_code ?? null;

        $school = School::findOrFail($schoolId);
        $amount = $this->getAmount($membershipType, $billingPeriod);
        $discountAmount = 0;

        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();
            if ($coupon) {
                $discountAmount = $coupon->calculateDiscount($amount);
                $coupon->increment('used_count');
            }
        }

        // Create payment record
        $payment = Payment::create([
            'school_id' => $school->id,
            'membership_type' => $membershipType,
            'amount' => $amount,
            'currency' => 'USD',
            'stripe_payment_intent_id' => $session->payment_intent ?? null,
            'stripe_customer_id' => $session->customer ?? null,
            'billing_period' => $billingPeriod,
            'status' => 'completed',
            'coupon_code' => $couponCode,
            'discount_amount' => $discountAmount,
        ]);

        // Create or update membership
        $expiresAt = $billingPeriod === 'annual' 
            ? now()->addYear() 
            : now()->addMonth();

        $renewalDate = $billingPeriod === 'monthly' 
            ? now()->addMonth() 
            : now()->addYear();

        $membership = Membership::updateOrCreate(
            [
                'school_id' => $school->id,
                'membership_type' => $membershipType,
            ],
            [
                'status' => 'active',
                'expires_at' => $expiresAt,
                'billing_period' => $billingPeriod,
                'stripe_customer_id' => $session->customer ?? null,
                'stripe_subscription_id' => $session->subscription ?? null,
                'purchased_at' => now(),
                'renewal_date' => $renewalDate,
            ]
        );

        return $payment;
    }

    /**
     * Create Stripe Customer Portal session.
     */
    public function createCustomerPortalSession(string $customerId, string $returnUrl): string
    {
        $session = \Stripe\BillingPortal\Session::create([
            'customer' => $customerId,
            'return_url' => $returnUrl,
        ]);

        return $session->url;
    }
}


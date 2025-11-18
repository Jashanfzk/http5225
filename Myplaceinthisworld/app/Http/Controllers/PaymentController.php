<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use App\Models\School;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->middleware('auth');
        $this->paymentService = $paymentService;
    }

    /**
     * Create checkout session and redirect to Stripe.
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'membership_type' => ['required', 'in:primary,junior_intermediate'],
            'billing_period' => ['required', 'in:monthly,annual'],
            'coupon_code' => ['nullable', 'string'],
        ]);

        $user = Auth::user();
        $school = $user->school;

        if (!$school) {
            return redirect()->route('membership.index')
                ->with('error', 'No school associated with your account.');
        }

        // Check if membership already exists and is active
        $existingMembership = $school->memberships()
            ->where('membership_type', $validated['membership_type'])
            ->where('status', 'active')
            ->where(function($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($existingMembership) {
            return redirect()->route('membership.index')
                ->with('error', 'You already have an active membership for this division.');
        }

        // Validate coupon if provided
        if ($request->coupon_code) {
            $coupon = Coupon::where('code', $request->coupon_code)->first();
            if (!$coupon || !$coupon->isValid()) {
                return redirect()->route('membership.index')
                    ->with('error', 'Invalid or expired coupon code.');
            }
        }

        try {
            $session = $this->paymentService->createCheckoutSession(
                $school,
                $validated['membership_type'],
                $validated['billing_period'],
                $request->coupon_code
            );

            return redirect($session->url);
        } catch (\Exception $e) {
            Log::error('Payment checkout error: ' . $e->getMessage());
            return redirect()->route('membership.index')
                ->with('error', 'Failed to create checkout session. Please try again.');
        }
    }

    /**
     * Handle successful payment.
     */
    public function success(Request $request)
    {
        $sessionId = $request->query('session_id');

        if (!$sessionId) {
            return redirect()->route('membership.index')
                ->with('error', 'Invalid payment session.');
        }

        try {
            $payment = $this->paymentService->handleSuccessfulPayment($sessionId);

            return redirect()->route('membership.index')
                ->with('success', 'Payment successful! Your membership has been activated.');
        } catch (\Exception $e) {
            Log::error('Payment success handling error: ' . $e->getMessage());
            return redirect()->route('membership.index')
                ->with('error', 'Payment processed but there was an error activating your membership. Please contact support.');
        }
    }

    /**
     * Handle cancelled payment.
     */
    public function cancel()
    {
        return redirect()->route('membership.index')
            ->with('info', 'Payment was cancelled. You can try again anytime.');
    }

    /**
     * Handle Stripe webhooks.
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle different event types
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event->data->object);
                break;

            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;

            default:
                Log::info('Unhandled Stripe webhook event: ' . $event->type);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Handle checkout session completed.
     */
    protected function handleCheckoutSessionCompleted($session)
    {
        // Payment already handled in success callback, but webhook ensures reliability
        Log::info('Checkout session completed: ' . $session->id);
    }

    /**
     * Handle subscription updated.
     */
    protected function handleSubscriptionUpdated($subscription)
    {
        $membership = \App\Models\Membership::where('stripe_subscription_id', $subscription->id)->first();

        if ($membership) {
            $membership->update([
                'status' => $subscription->status === 'active' ? 'active' : 'cancelled',
                'expires_at' => $subscription->current_period_end ? \Carbon\Carbon::createFromTimestamp($subscription->current_period_end) : null,
                'renewal_date' => $subscription->current_period_end ? \Carbon\Carbon::createFromTimestamp($subscription->current_period_end) : null,
            ]);
        }
    }

    /**
     * Handle subscription deleted.
     */
    protected function handleSubscriptionDeleted($subscription)
    {
        $membership = \App\Models\Membership::where('stripe_subscription_id', $subscription->id)->first();

        if ($membership) {
            $membership->update([
                'status' => 'cancelled',
            ]);
        }
    }

    /**
     * Handle payment failed.
     */
    protected function handlePaymentFailed($invoice)
    {
        $subscriptionId = $invoice->subscription;
        $membership = \App\Models\Membership::where('stripe_subscription_id', $subscriptionId)->first();

        if ($membership) {
            // You can add logic here to notify the user or admin
            Log::warning('Payment failed for membership: ' . $membership->id);
        }
    }

    /**
     * Create customer portal session.
     */
    public function customerPortal(Request $request)
    {
        $user = Auth::user();
        $school = $user->school;

        if (!$school || !$user->is_owner) {
            return redirect()->route('profile.index')
                ->with('error', 'Only school owners can manage payment methods.');
        }

        $membership = $school->memberships()
            ->whereNotNull('stripe_customer_id')
            ->first();

        if (!$membership || !$membership->stripe_customer_id) {
            return redirect()->route('profile.index')
                ->with('error', 'No payment method on file.');
        }

        try {
            $portalUrl = $this->paymentService->createCustomerPortalSession(
                $membership->stripe_customer_id,
                route('profile.index')
            );

            return redirect($portalUrl);
        } catch (\Exception $e) {
            Log::error('Customer portal error: ' . $e->getMessage());
            return redirect()->route('profile.index')
                ->with('error', 'Failed to access customer portal. Please try again.');
        }
    }
}


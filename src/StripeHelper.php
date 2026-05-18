<?php

namespace App;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Charge;

class StripeHelper
{
    private static $initialized = false;

    public static function init()
    {
        if (self::$initialized) return;

        $secretKey = Config::get('STRIPE_SECRET_KEY');
        if (!$secretKey) {
            throw new \Exception('STRIPE_SECRET_KEY not configured in .env');
        }

        Stripe::setApiKey($secretKey);
        self::$initialized = true;
    }

    /**
     * Create a payment intent (for modal checkout)
     */
    public static function createPaymentIntent($amount, $currency = 'eur', $metadata = [])
    {
        try {
            self::init();

            $intent = PaymentIntent::create([
                'amount' => intval($amount * 100), // Convert to cents
                'currency' => strtolower($currency),
                'metadata' => $metadata
            ]);

            return [
                'success' => true,
                'clientSecret' => $intent->client_secret,
                'intentId' => $intent->id
            ];
        } catch (\Exception $e) {
            error_log('Stripe PaymentIntent error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieve payment intent status
     */
    public static function getPaymentIntent($intentId)
    {
        try {
            self::init();
            $intent = PaymentIntent::retrieve($intentId);

            return [
                'id' => $intent->id,
                'status' => $intent->status,
                'amount' => $intent->amount / 100,
                'currency' => strtoupper($intent->currency)
            ];
        } catch (\Exception $e) {
            error_log('Stripe retrieve error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a charge (for testing without payment intent)
     * Note: This is for test mode only, card token from Stripe.js
     */
    public static function createCharge($amount, $token, $metadata = [])
    {
        try {
            self::init();

            // In test mode, use test token
            $charge = Charge::create([
                'amount' => intval($amount * 100), // cents
                'currency' => 'eur',
                'source' => $token,
                'metadata' => $metadata
            ]);

            return [
                'success' => true,
                'chargeId' => $charge->id,
                'status' => $charge->status
            ];
        } catch (\Exception $e) {
            error_log('Stripe charge error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a checkout session (for redirect checkout)
     * Note: Requires success/cancel URLs
     */
    public static function createCheckoutSession($items, $successUrl, $cancelUrl)
    {
        try {
            self::init();

            // For test mode simulation, just return mock session
            // Real implementation would use \Stripe\Checkout\Session::create()

            return [
                'success' => true,
                'sessionId' => 'cs_test_' . bin2hex(random_bytes(16)),
                'url' => null // In real usage: $session->url
            ];
        } catch (\Exception $e) {
            error_log('Stripe checkout session error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get Stripe publishable key (safe for client)
     */
    public static function getPublishableKey()
    {
        return Config::get('STRIPE_PUBLISHABLE_KEY');
    }

    /**
     * Check if in test mode (starts with pk_test or sk_test)
     */
    public static function isTestMode()
    {
        $key = Config::get('STRIPE_SECRET_KEY');
        return strpos($key, 'sk_test') === 0;
    }
}

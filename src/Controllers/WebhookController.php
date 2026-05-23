<?php

namespace App\Controllers;

use App\Firebase;

class WebhookController
{
    public function handle($params = [], $post = [], $get = [])
    {
        header('Content-Type: application/json');

        // Read raw request body
        $payload = file_get_contents('php://input');
        $event = json_decode($payload, true);

        // Validate event structure
        if (!$event || !isset($event['type'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid event structure']);
            return;
        }

        // TODO: Validate webhook signature when STRIPE_WEBHOOK_SECRET is set
        // For now, accept all events (development mode)
        // In production:
        // $sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        // if (!$this->verifySignature($payload, $sig)) {
        //     http_response_code(401);
        //     return;
        // }

        try {
            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSucceeded($event);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event);
                    break;

                default:
                    // Acknowledge other event types
                    error_log('Webhook: Unhandled event type: ' . $event['type']);
                    break;
            }

            http_response_code(200);
            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            error_log('Webhook error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Webhook processing failed']);
        }
    }

    private function handlePaymentSucceeded($event)
    {
        $paymentIntent = $event['data']['object'] ?? [];
        $paymentIntentId = $paymentIntent['id'] ?? null;

        if (!$paymentIntentId) {
            error_log('Webhook: Missing paymentIntentId in succeeded event');
            return;
        }

        // Find order by paymentIntentId
        $order = Firebase::getOrderByPaymentIntentId($paymentIntentId);
        if (!$order) {
            error_log('Webhook: Order not found for paymentIntentId: ' . $paymentIntentId);
            return;
        }

        // Update order status to 'paid'
        $orderId = $order['id'] ?? null;
        if ($orderId) {
            Firebase::updateOrder($orderId, ['status' => 'paid']);
            error_log('Webhook: Order ' . $orderId . ' marked as paid');
        }
    }

    private function handlePaymentFailed($event)
    {
        $paymentIntent = $event['data']['object'] ?? [];
        $paymentIntentId = $paymentIntent['id'] ?? null;

        if (!$paymentIntentId) {
            error_log('Webhook: Missing paymentIntentId in failed event');
            return;
        }

        // Find order by paymentIntentId
        $order = Firebase::getOrderByPaymentIntentId($paymentIntentId);
        if (!$order) {
            error_log('Webhook: Order not found for paymentIntentId: ' . $paymentIntentId);
            return;
        }

        // Update order status to 'failed'
        $orderId = $order['id'] ?? null;
        if ($orderId) {
            Firebase::updateOrder($orderId, ['status' => 'failed']);
            error_log('Webhook: Order ' . $orderId . ' marked as failed');
        }
    }

    // TODO: Implement signature verification when STRIPE_WEBHOOK_SECRET is available
    // private function verifySignature($payload, $signature)
    // {
    //     $secret = Config::get('STRIPE_WEBHOOK_SECRET');
    //     if (!$secret) {
    //         return true; // Skip verification in development
    //     }
    //
    //     $expectedSig = hash_hmac('sha256', $payload, $secret, false);
    //     $timestamp = explode(',', $signature)[0] ?? '';
    //     $sig = explode(',', $signature)[1] ?? '';
    //
    //     return hash_equals($expectedSig, $sig);
    // }
}

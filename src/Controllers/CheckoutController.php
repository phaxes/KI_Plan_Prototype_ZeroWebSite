<?php

namespace App\Controllers;

use App\StripeHelper;
use App\Firebase;
use App\Auth;
use App\View;
use App\Middleware\AuthMiddleware;

class CheckoutController
{
    public function index($params = [], $post = [], $get = [])
    {
        $title = 'Checkout';
        $pageTitle = 'Checkout';
        $stripeKey = StripeHelper::getPublishableKey();
        $isTestMode = StripeHelper::isTestMode();
        $email = Auth::isLoggedIn() ? View::escape($_SESSION['email']) : '';

        echo View::render('checkout/index', [
            'pageTitle' => $pageTitle,
            'stripeKey' => $stripeKey,
            'isTestMode' => $isTestMode,
            'email' => $email,
            'title' => $title,
        ]);
    }

    public function process($params = [], $post = [], $get = [])
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $items = $input['items'] ?? [];
            $total = floatval($input['total'] ?? 0);
            $email = $input['email'] ?? '';
            $paymentIntentId = $input['paymentIntentId'] ?? '';
            $testMode = $input['testMode'] ?? false;

            if (empty($items) || $total <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid order data']);
                return;
            }

            $userId = Auth::getCurrentUserId() ?? 'guest_' . uniqid();

            // Create order
            $orderData = [
                'userId' => $userId,
                'items' => $items,
                'total' => $total,
                'email' => $email,
                'paymentIntentId' => $paymentIntentId,
                'status' => $testMode ? 'test' : 'pending',
                'createdAt' => new \DateTime()
            ];

            $orderId = Firebase::createOrder($orderData);

            if ($orderId) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'orderId' => $orderId
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Failed to create order']);
            }

        } catch (\Exception $e) {
            error_log('Checkout error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function success($params = [], $post = [], $get = [])
    {
        $title = 'Danke!';
        $pageTitle = 'Bestellung erfolgreich';
        $orderId = $_GET['orderId'] ?? 'ORDER_' . uniqid();
        $isLoggedIn = Auth::isLoggedIn();

        // Try to load order details from Firestore
        $order = Firebase::getOrderById($orderId);
        $orderTotal = $order['total'] ?? null;
        $orderEmail = $order['email'] ?? '';

        echo View::render('checkout/success', [
            'orderId' => $orderId,
            'orderTotal' => $orderTotal,
            'orderEmail' => $orderEmail,
            'isLoggedIn' => $isLoggedIn,
            'title' => $title,
        ]);
    }
}

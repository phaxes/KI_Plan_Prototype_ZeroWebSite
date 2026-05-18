<?php

namespace App\Controllers;

use App\StripeHelper;
use App\Firebase;
use App\Auth;
use App\Middleware\AuthMiddleware;

class CheckoutController
{
    public function index($params = [], $post = [], $get = [])
    {
        $title = 'Checkout';
        $pageTitle = 'Checkout';
        $stripeKey = StripeHelper::getPublishableKey();
        $isTestMode = StripeHelper::isTestMode();

        ob_start();
        ?>
        <section class="py-12">
            <div class="container mx-auto px-4 max-w-2xl">
                <h1 class="text-4xl font-bold mb-8"><?= htmlspecialchars($pageTitle) ?></h1>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Cart Summary -->
                    <div class="bg-gray-50 p-6 rounded">
                        <h2 class="text-xl font-bold mb-4">Bestellübersicht</h2>
                        <div id="checkout-summary">
                            <p class="text-gray-600">Wird geladen...</p>
                        </div>
                    </div>

                    <!-- Checkout Form -->
                    <div class="bg-white rounded shadow p-8" data-stripe-key="<?= htmlspecialchars($stripeKey) ?>" data-stripe-test-mode="<?= $isTestMode ? 'true' : 'false' ?>">
                        <h2 class="text-xl font-bold mb-6">Zahlungsmethode</h2>
                        <form id="checkout-form">
                            <div class="mb-6">
                                <label class="block text-gray-700 font-semibold mb-2">E-Mail</label>
                                <input type="email" name="email" required class="w-full px-4 py-2 border rounded" placeholder="deine@email.de" value="<?= Auth::isLoggedIn() ? htmlspecialchars($_SESSION['email']) : '' ?>">
                            </div>

                            <div class="mb-6">
                                <label class="block text-gray-700 font-semibold mb-2">Kartendaten</label>
                                <div id="card-element" class="w-full px-4 py-2 border rounded bg-white" style="min-height: 40px;"></div>
                                <div id="card-errors" class="text-red-600 text-sm mt-2"></div>
                            </div>

                            <button type="submit" class="w-full bg-primary text-white px-6 py-3 rounded hover:bg-blue-600 transition font-semibold">
                                Bestellung aufgeben (<?= $isTestMode ? 'TEST' : 'LIVE' ?>)
                            </button>

                            <p class="text-xs text-gray-500 mt-4">
                                <?php if ($isTestMode): ?>
                                    💡 <strong>Test-Modus:</strong> Verwende Karte 4242 4242 4242 4242, beliebiges Ablaufdatum/CVC
                                <?php else: ?>
                                    🔒 Sichere Zahlung via Stripe
                                <?php endif; ?>
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <script>
            function renderCheckoutSummary() {
                const cart = JSON.parse(localStorage.getItem('cart')) || [];
                const container = document.getElementById('checkout-summary');

                if (cart.length === 0) {
                    window.location.href = '/shop';
                    return;
                }

                let html = '<div class="space-y-3">';
                let total = 0;

                cart.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;
                    html += `
                        <div class="flex justify-between text-sm">
                            <span>${item.name} × ${item.quantity}</span>
                            <span>${itemTotal.toFixed(2).replace('.', ',')} €</span>
                        </div>
                    `;
                });

                html += `
                    <div class="border-t pt-3 mt-3 font-bold flex justify-between">
                        <span>Gesamtsumme:</span>
                        <span>${total.toFixed(2).replace('.', ',')} €</span>
                    </div>
                `;

                html += '</div>';
                container.innerHTML = html;
            }

            renderCheckoutSummary();
        </script>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/base.php';
    }

    public function process($params = [], $post = [], $get = [])
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $items = $input['items'] ?? [];
            $total = floatval($input['total'] ?? 0);
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

        ob_start();
        ?>
        <section class="py-12">
            <div class="container mx-auto px-4 max-w-2xl">
                <div class="bg-green-50 border border-green-200 rounded p-8 text-center">
                    <div class="text-5xl mb-4">✅</div>
                    <h1 class="text-4xl font-bold text-green-700 mb-4">Bestellung aufgegeben!</h1>
                    <p class="text-gray-700 mb-4">Danke für deine Bestellung. Wir verarbeiten sie in Kürze.</p>

                    <div class="bg-white p-6 rounded my-6">
                        <p class="text-gray-600 text-sm">Bestellnummer:</p>
                        <p class="text-2xl font-bold font-mono"><?= htmlspecialchars($orderId) ?></p>
                    </div>

                    <p class="text-gray-600 mb-4">Eine Bestätigung wurde an deine E-Mail gesendet.</p>

                    <div class="flex gap-4 justify-center flex-wrap">
                        <a href="/shop" class="bg-primary text-white px-6 py-3 rounded hover:bg-blue-600 transition">Weitershoppen</a>
                        <?php if (Auth::isLoggedIn()): ?>
                            <a href="/profile" class="border border-primary text-primary px-6 py-3 rounded hover:bg-gray-100 transition">Meine Bestellungen</a>
                        <?php endif; ?>
                        <a href="/" class="border border-gray-300 text-gray-700 px-6 py-3 rounded hover:bg-gray-100 transition">Startseite</a>
                    </div>
                </div>
            </div>
        </section>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/base.php';
    }
}

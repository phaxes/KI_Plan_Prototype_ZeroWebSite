<?php

namespace App\Controllers;

class CartController
{
    public function index($params = [], $post = [], $get = [])
    {
        $title = 'Warenkorb';
        $pageTitle = 'Dein Warenkorb';

        ob_start();
        ?>
        <section class="py-12">
            <div class="container mx-auto px-4 max-w-2xl">
                <h1 class="text-4xl font-bold mb-8"><?= htmlspecialchars($pageTitle) ?></h1>

                <div class="bg-white rounded shadow p-8">
                    <div id="cart-items-container">
                        <p class="text-gray-600 text-center py-8">Warenkorb wird geladen...</p>
                    </div>

                    <div id="cart-summary" class="hidden border-t mt-8 pt-8">
                        <div class="text-2xl font-bold mb-8">
                            Gesamtsumme: <span id="cart-total">0,00 €</span>
                        </div>
                        <a href="/checkout" class="w-full bg-primary text-white px-6 py-3 rounded hover:bg-blue-600 transition font-semibold text-center block">
                            Zum Checkout
                        </a>
                    </div>

                    <div id="cart-empty" class="text-center py-8">
                        <p class="text-gray-600 mb-4">Dein Warenkorb ist leer</p>
                        <a href="/shop" class="text-primary hover:text-blue-600 transition font-semibold">Zum Shop</a>
                    </div>
                </div>
            </div>
        </section>

        <script>
            function renderCart() {
                const cart = JSON.parse(localStorage.getItem('cart')) || [];
                const container = document.getElementById('cart-items-container');
                const summary = document.getElementById('cart-summary');
                const empty = document.getElementById('cart-empty');

                if (cart.length === 0) {
                    empty.classList.remove('hidden');
                    summary.classList.add('hidden');
                    container.innerHTML = '';
                    return;
                }

                empty.classList.add('hidden');
                summary.classList.remove('hidden');

                let html = '<div class="space-y-4">';
                let total = 0;

                cart.forEach((item, index) => {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;

                    html += `
                        <div class="flex justify-between items-center p-4 border-b">
                            <div>
                                <h3 class="font-semibold">${item.name}</h3>
                                <p class="text-gray-600 text-sm">${item.price.toFixed(2).replace('.', ',')} € × ${item.quantity}</p>
                            </div>
                            <div class="text-right">
                                <div class="font-bold mb-2">${itemTotal.toFixed(2).replace('.', ',')} €</div>
                                <button onclick="removeFromCart(${index})" class="text-red-500 hover:text-red-700 transition text-sm">Entfernen</button>
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
                container.innerHTML = html;
                document.getElementById('cart-total').textContent = total.toFixed(2).replace('.', ',') + ' €';
            }

            function removeFromCart(index) {
                let cart = JSON.parse(localStorage.getItem('cart')) || [];
                cart.splice(index, 1);
                localStorage.setItem('cart', JSON.stringify(cart));
                App.updateCartCount();
                renderCart();
            }

            renderCart();
        </script>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/base.php';
    }

    public function add($params = [], $post = [], $get = [])
    {
        header('Content-Type: application/json');
        try {
            $productId = $post['productId'] ?? null;
            $quantity = intval($post['quantity'] ?? 1);

            if (!$productId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing productId']);
                return;
            }

            echo json_encode(['success' => true]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function remove($params = [], $post = [], $get = [])
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    public function update($params = [], $post = [], $get = [])
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
}

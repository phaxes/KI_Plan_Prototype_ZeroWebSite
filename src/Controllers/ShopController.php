<?php

namespace App\Controllers;

use App\Firebase;

class ShopController
{
    public function index($params = [], $post = [], $get = [])
    {
        $page = intval($get['page'] ?? 1);
        $limit = 9;
        $offset = ($page - 1) * $limit;

        $products = Firebase::getProducts(true, $limit, $offset);
        $title = 'Shop';
        $pageTitle = 'Unser Shop';

        ob_start();
        ?>
        <section class="py-12">
            <div class="container mx-auto px-4">
                <h1 class="text-4xl font-bold mb-8"><?= htmlspecialchars($pageTitle) ?></h1>

                <?php if (!empty($products)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                        <?php foreach ($products as $product): ?>
                            <article class="bg-white rounded shadow hover:shadow-lg transition overflow-hidden">
                                <?php if (!empty($product['imageUrl'])): ?>
                                    <img src="<?= htmlspecialchars($product['imageUrl']) ?>" alt="Produkt" class="w-full h-40 object-cover">
                                <?php else: ?>
                                    <div class="w-full h-40 bg-gray-200 flex items-center justify-center">
                                        <span class="text-gray-400">Kein Bild</span>
                                    </div>
                                <?php endif; ?>
                                <div class="p-6">
                                    <h3 class="text-xl font-bold mb-2 line-clamp-2"><?= htmlspecialchars($product['name']) ?></h3>
                                    <p class="text-gray-600 line-clamp-2 mb-4"><?= htmlspecialchars($product['description'] ?? '') ?></p>
                                    <div class="flex justify-between items-center">
                                        <span class="text-2xl font-bold text-primary"><?= number_format($product['price'], 2, ',', '.') ?> €</span>
                                        <a href="/shop/<?= htmlspecialchars($product['id']) ?>" class="bg-primary text-white px-4 py-2 rounded hover:bg-blue-600 transition">Details</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <div class="flex justify-center gap-2 mt-12">
                        <?php if ($page > 1): ?>
                            <a href="/shop?page=<?= $page - 1 ?>" class="px-4 py-2 border rounded hover:bg-gray-100">← Zurück</a>
                        <?php endif; ?>
                        <span class="px-4 py-2">Seite <?= $page ?></span>
                        <?php if (count($products) >= $limit): ?>
                            <a href="/shop?page=<?= $page + 1 ?>" class="px-4 py-2 border rounded hover:bg-gray-100">Weiter →</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-gray-100 p-12 rounded text-center">
                        <p class="text-gray-600 text-lg">Noch keine Produkte verfügbar</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/base.php';
    }

    public function show($params = [], $post = [], $get = [])
    {
        $productId = $params['id'] ?? null;

        if (!$productId) {
            http_response_code(404);
            require __DIR__ . '/../../templates/errors/404.php';
            return;
        }

        $product = Firebase::getProductById($productId);

        if (!$product || !$product['active']) {
            http_response_code(404);
            require __DIR__ . '/../../templates/errors/404.php';
            return;
        }

        $title = htmlspecialchars($product['name']);
        $pageTitle = 'Produkt';

        ob_start();
        ?>
        <section class="py-12">
            <div class="container mx-auto px-4 max-w-4xl">
                <a href="/shop" class="text-primary hover:text-blue-600 transition mb-8 inline-block">← Zurück zum Shop</a>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Product Image -->
                    <div>
                        <?php if (!empty($product['imageUrl'])): ?>
                            <img src="<?= htmlspecialchars($product['imageUrl']) ?>" alt="Produkt" class="w-full rounded shadow">
                        <?php else: ?>
                            <div class="w-full h-96 bg-gray-200 rounded flex items-center justify-center">
                                <span class="text-gray-400 text-xl">Kein Bild verfügbar</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Product Info -->
                    <div>
                        <h1 class="text-4xl font-bold mb-4"><?= htmlspecialchars($product['name']) ?></h1>

                        <?php if (!empty($product['category'])): ?>
                            <p class="text-gray-600 mb-4">Kategorie: <strong><?= htmlspecialchars($product['category']) ?></strong></p>
                        <?php endif; ?>

                        <div class="text-3xl font-bold text-primary mb-6"><?= number_format($product['price'], 2, ',', '.') ?> €</div>

                        <p class="text-gray-700 leading-relaxed mb-8">
                            <?= nl2br(htmlspecialchars($product['description'])) ?>
                        </p>

                        <div class="flex items-center gap-4 mb-8">
                            <input type="number" id="quantity" min="1" value="1" class="w-20 px-4 py-2 border rounded">
                            <button onclick="addToCart('<?= htmlspecialchars($product['id']) ?>', '<?= htmlspecialchars($product['name']) ?>', <?= $product['price'] ?>)"
                                    class="bg-primary text-white px-6 py-2 rounded hover:bg-blue-600 transition font-semibold">
                                In den Warenkorb
                            </button>
                        </div>

                        <div class="bg-blue-50 p-4 rounded mb-8">
                            <p class="text-sm text-gray-700">
                                <strong>Verfügbarkeit:</strong> <?= $product['stock'] > 0 ? 'Verfügbar (' . $product['stock'] . ' Stück)' : 'Nicht verfügbar' ?>
                            </p>
                        </div>

                        <div class="border-t pt-8">
                            <a href="/shop" class="text-primary hover:text-blue-600 transition font-semibold">← Alle Produkte</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <script>
            function addToCart(productId, productName, price) {
                const quantity = parseInt(document.getElementById('quantity').value);
                const cart = JSON.parse(localStorage.getItem('cart')) || [];

                const existingItem = cart.find(item => item.id === productId);
                if (existingItem) {
                    existingItem.quantity += quantity;
                } else {
                    cart.push({ id: productId, name: productName, price: price, quantity: quantity });
                }

                localStorage.setItem('cart', JSON.stringify(cart));
                App.updateCartCount();
                App.showNotification(`${productName} wurde zum Warenkorb hinzugefügt!`, 'success');
                document.getElementById('quantity').value = 1;
            }
        </script>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/base.php';
    }
}

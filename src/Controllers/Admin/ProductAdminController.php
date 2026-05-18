<?php

namespace App\Controllers\Admin;

use App\Firebase;

class ProductAdminController
{
    protected function checkAdmin()
    {
        if (!isset($_SESSION['isAdmin']) || !$_SESSION['isAdmin']) {
            header('Location: /login');
            exit;
        }
    }

    public function index($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();
        $products = Firebase::getProducts(false, 50, 0);
        $pageTitle = 'Produkte verwalten';

        ob_start();
        ?>
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold"><?= htmlspecialchars($pageTitle) ?></h1>
            <a href="/admin/products/create" class="bg-primary text-white px-6 py-2 rounded hover:bg-blue-600 transition">+ Neues Produkt</a>
        </div>

        <div class="bg-white rounded shadow overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left">Name</th>
                        <th class="px-6 py-3 text-left">Kategorie</th>
                        <th class="px-6 py-3 text-right">Preis</th>
                        <th class="px-6 py-3 text-center">Bestand</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-right">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4"><?= htmlspecialchars(substr($product['name'], 0, 30)) ?></td>
                                <td class="px-6 py-4"><?= htmlspecialchars($product['category'] ?? '-') ?></td>
                                <td class="px-6 py-4 text-right"><?= number_format($product['price'], 2, ',', '.') ?> €</td>
                                <td class="px-6 py-4 text-center"><?= $product['stock'] ?? 0 ?></td>
                                <td class="px-6 py-4 text-center">
                                    <span class="<?= $product['active'] ? 'text-green-600' : 'text-gray-400' ?>">
                                        <?= $product['active'] ? '✓' : '-' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <a href="/admin/products/<?= htmlspecialchars($product['id']) ?>/edit" class="text-primary hover:text-blue-600 mr-4">Bearbeiten</a>
                                    <button onclick="deleteProduct('<?= htmlspecialchars($product['id']) ?>')" class="text-red-600 hover:text-red-700">Löschen</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-600">Keine Produkte vorhanden</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <script>
            function deleteProduct(id) {
                if (confirm('Produkt wirklich löschen?')) {
                    alert('Implementierung folgt');
                }
            }
        </script>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/admin.php';
    }

    public function create($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();
        $pageTitle = 'Neues Produkt';

        ob_start();
        ?>
        <div class="max-w-3xl">
            <h1 class="text-3xl font-bold mb-8"><?= htmlspecialchars($pageTitle) ?></h1>

            <form method="POST" action="/admin/products/store" class="bg-white rounded shadow p-8">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Name *</label>
                        <input type="text" name="name" required class="w-full px-4 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Kategorie</label>
                        <input type="text" name="category" class="w-full px-4 py-2 border rounded">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mt-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Preis *</label>
                        <input type="number" name="price" step="0.01" required class="w-full px-4 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Bestand</label>
                        <input type="number" name="stock" value="0" class="w-full px-4 py-2 border rounded">
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-gray-700 font-semibold mb-2">Beschreibung</label>
                    <textarea name="description" rows="4" class="w-full px-4 py-2 border rounded"></textarea>
                </div>

                <div class="mt-6">
                    <label class="block text-gray-700 font-semibold mb-2">Bild-URL</label>
                    <input type="url" name="imageUrl" class="w-full px-4 py-2 border rounded">
                </div>

                <div class="mt-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="active" value="1" checked class="w-4 h-4 text-primary rounded">
                        <span class="ml-2 text-gray-700">Aktiv</span>
                    </label>
                </div>

                <div class="mt-8 flex gap-4">
                    <button type="submit" class="bg-primary text-white px-6 py-2 rounded hover:bg-blue-600 transition">Speichern</button>
                    <a href="/admin/products" class="px-6 py-2 border rounded hover:bg-gray-100 transition">Abbrechen</a>
                </div>
            </form>
        </div>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/admin.php';
    }

    public function store($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();
        try {
            $data = [
                'name' => $post['name'] ?? '',
                'price' => floatval($post['price'] ?? 0),
                'category' => $post['category'] ?? '',
                'description' => $post['description'] ?? '',
                'imageUrl' => $post['imageUrl'] ?? '',
                'stock' => intval($post['stock'] ?? 0),
                'active' => isset($post['active'])
            ];

            if (Firebase::createProduct($data)) {
                header('Location: /admin/products');
                exit;
            }
        } catch (\Exception $e) {
            error_log('Product create error: ' . $e->getMessage());
        }
    }

    public function edit($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();
        $product = Firebase::getProductById($params['id'] ?? null);

        if (!$product) {
            http_response_code(404);
            return;
        }

        $pageTitle = 'Produkt bearbeiten';
        ob_start();
        ?>
        <div class="max-w-3xl">
            <h1 class="text-3xl font-bold mb-8"><?= htmlspecialchars($pageTitle) ?></h1>

            <form method="POST" action="/admin/products/<?= htmlspecialchars($product['id']) ?>/update" class="bg-white rounded shadow p-8">
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Name *</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($product['name']) ?>" class="w-full px-4 py-2 border rounded">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2">Preis *</label>
                    <input type="number" name="price" step="0.01" required value="<?= $product['price'] ?>" class="w-full px-4 py-2 border rounded">
                </div>

                <div class="mt-8 flex gap-4">
                    <button type="submit" class="bg-primary text-white px-6 py-2 rounded">Speichern</button>
                    <a href="/admin/products" class="px-6 py-2 border rounded">Abbrechen</a>
                </div>
            </form>
        </div>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/admin.php';
    }

    public function update($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();
        try {
            $data = [
                'name' => $post['name'] ?? '',
                'price' => floatval($post['price'] ?? 0)
            ];
            Firebase::updateProduct($params['id'], $data);
            header('Location: /admin/products');
        } catch (\Exception $e) {
            error_log('Product update error: ' . $e->getMessage());
        }
    }

    public function delete($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();
        try {
            Firebase::deleteProduct($params['id']);
            header('Location: /admin/products');
        } catch (\Exception $e) {
            error_log('Product delete error: ' . $e->getMessage());
        }
    }
}

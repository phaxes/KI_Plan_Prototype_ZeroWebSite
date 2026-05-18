<?php
namespace App\Controllers\Admin;

use App\Firebase;
use App\View;

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
        $page = intval($get['page'] ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $products = Firebase::getProducts(false, $limit, $offset);
        $title = 'Produkt Management';
        $pageTitle = 'Produkte verwalten';

        echo View::render('admin/products/index', [
            'products' => $products,
            'pageTitle' => $pageTitle,
            'title' => $title,
        ], 'layouts/admin');
    }

    public function create($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();
        $title = 'Neues Produkt';
        $pageTitle = 'Neues Produkt erstellen';

        echo View::render('admin/products/create', [
            'pageTitle' => $pageTitle,
            'title' => $title,
        ], 'layouts/admin');
    }

    public function edit($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();

        $productId = $params['id'] ?? null;
        $product = Firebase::getProductById($productId);

        if (!$product) {
            http_response_code(404);
            return;
        }

        $title = 'Produkt bearbeiten';
        $pageTitle = 'Produkt bearbeiten';

        echo View::render('admin/products/edit', [
            'product' => $product,
            'productId' => $productId,
            'pageTitle' => $pageTitle,
            'title' => $title,
        ], 'layouts/admin');
    }

    public function store($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();

        try {
            $data = [
                'name' => $post['name'] ?? '',
                'description' => $post['description'] ?? '',
                'price' => floatval($post['price'] ?? 0),
                'imageUrl' => $post['imageUrl'] ?? '',
                'category' => $post['category'] ?? '',
                'stock' => intval($post['stock'] ?? 0),
                'active' => isset($post['active'])
            ];

            $productId = Firebase::createProduct($data);

            if ($productId) {
                header('Location: /admin/products');
                exit;
            }
        } catch (\Exception $e) {
            error_log('Product create error: ' . $e->getMessage());
        }
    }

    public function update($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();

        $productId = $params['id'] ?? null;

        try {
            $data = [
                'name' => $post['name'] ?? '',
                'description' => $post['description'] ?? '',
                'price' => floatval($post['price'] ?? 0),
                'imageUrl' => $post['imageUrl'] ?? '',
                'category' => $post['category'] ?? '',
                'stock' => intval($post['stock'] ?? 0),
                'active' => isset($post['active'])
            ];

            if (Firebase::updateProduct($productId, $data)) {
                header('Location: /admin/products');
                exit;
            }
        } catch (\Exception $e) {
            error_log('Product update error: ' . $e->getMessage());
        }
    }

    public function delete($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();

        $productId = $params['id'] ?? null;

        try {
            if (Firebase::deleteProduct($productId)) {
                header('Location: /admin/products');
                exit;
            }
        } catch (\Exception $e) {
            error_log('Product delete error: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Controllers;

use App\View;
use App\Firebase;

class CartController
{
    public function index($params = [], $post = [], $get = [])
    {
        $title = 'Warenkorb';
        $pageTitle = 'Dein Warenkorb';

        echo View::render('cart/index', [
            'pageTitle' => $pageTitle,
            'title' => $title,
        ]);
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

    public function stock($params = [], $post = [], $get = [])
    {
        header('Content-Type: application/json');
        $productId = $params['id'] ?? null;
        if (!$productId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing product id']);
            return;
        }
        $product = Firebase::getProductById($productId);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            return;
        }
        echo json_encode(['stock' => (int)($product['stock'] ?? 0)]);
    }
}

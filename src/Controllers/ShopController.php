<?php

namespace App\Controllers;

use App\Firebase;
use App\View;

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

        echo View::render('layouts/base', [
            'content' => View::render('shop/index', [
                'products' => $products,
                'page' => $page,
                'limit' => $limit,
                'pageTitle' => $pageTitle,
            ], false),
            'title' => $title,
        ]);
    }

    public function show($params = [], $post = [], $get = [])
    {
        $productId = $params['id'] ?? null;

        if (!$productId) {
            http_response_code(404);
            echo View::render('errors/404', [], false);
            return;
        }

        $product = Firebase::getProductById($productId);

        if (!$product || !$product['active']) {
            http_response_code(404);
            echo View::render('errors/404', [], false);
            return;
        }

        $title = View::escape($product['name']);
        $pageTitle = 'Produkt';

        echo View::render('layouts/base', [
            'content' => View::render('shop/show', [
                'product' => $product,
                'pageTitle' => $pageTitle,
            ], false),
            'title' => $title,
        ]);
    }
}

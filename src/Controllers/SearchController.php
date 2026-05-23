<?php

namespace App\Controllers;

use App\Firebase;

class SearchController
{
    public function search($params = [], $post = [], $get = [])
    {
        header('Content-Type: application/json');

        try {
            $q = trim($get['q'] ?? '');

            if (mb_strlen($q) < 2) {
                http_response_code(400);
                echo json_encode(['error' => 'Mindestens 2 Zeichen erforderlich', 'code' => 'TOO_SHORT']);
                return;
            }

            $posts    = Firebase::searchPosts($q, 6);
            $products = Firebase::searchProducts($q, 6);

            echo json_encode([
                'success' => true,
                'query'   => $q,
                'results' => [
                    'posts'    => $posts,
                    'products' => $products,
                ],
            ]);

        } catch (\Exception $e) {
            error_log('SearchController: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Interner Serverfehler']);
        }
    }
}

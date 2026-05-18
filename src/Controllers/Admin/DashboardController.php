<?php

namespace App\Controllers\Admin;

use App\Firebase;
use App\View;

class DashboardController
{
    public function index($params = [], $post = [], $get = [])
    {
        \App\Middleware\AuthMiddleware::requireAdmin();

        $news = Firebase::getPosts('news', null, 5, 0);
        $blog = Firebase::getPosts('blog', null, 5, 0);
        $products = Firebase::getProducts(false, 5, 0);

        $title = 'Admin Dashboard';
        $pageTitle = 'Dashboard';

        echo View::render('layouts/admin', [
            'content' => View::render('admin/dashboard', [
                'news' => $news,
                'blog' => $blog,
                'newsCount' => count($news),
                'blogCount' => count($blog),
                'productCount' => count($products),
            ], false),
            'title' => $title,
        ]);
    }
}

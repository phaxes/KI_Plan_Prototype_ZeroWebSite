<?php

namespace App\Controllers;

use App\Firebase;
use App\View;

class HomeController
{
    public function index($params = [], $post = [], $get = [])
    {
        $latestNews = Firebase::getPosts('news', true, 3, 0);
        $latestBlog = Firebase::getPosts('blog', true, 3, 0);
        $featuredProducts = Firebase::getProducts(true, 3, 0);

        echo View::render('home/index', [
            'latestNews' => $latestNews,
            'latestBlog' => $latestBlog,
            'featuredProducts' => $featuredProducts,
            'title' => 'Home',
        ]);
    }
}

<?php

namespace App\Controllers;

use App\Firebase;

class HomeController
{
    public function index($params = [], $post = [], $get = [])
    {
        $latestNews = Firebase::getPosts('news', true, 3, 0);
        $latestBlog = Firebase::getPosts('blog', true, 3, 0);
        $featuredProducts = Firebase::getProducts(true, 3, 0);

        require __DIR__ . '/../../templates/home/index.php';
    }
}

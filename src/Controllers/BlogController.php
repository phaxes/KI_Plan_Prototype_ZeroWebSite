<?php

namespace App\Controllers;

use App\Firebase;
use App\View;

class BlogController
{
    public function index($params = [], $post = [], $get = [])
    {
        $page = intval($get['page'] ?? 1);
        $limit = 9;
        $offset = ($page - 1) * $limit;

        $articles = Firebase::getPosts('blog', true, $limit, $offset);
        $title = 'Blog';
        $pageTitle = 'Blog-Artikel';

        echo View::render('layouts/base', [
            'content' => View::render('blog/index', [
                'articles' => $articles,
                'page' => $page,
                'limit' => $limit,
                'pageTitle' => $pageTitle,
            ], false),
            'title' => $title,
        ]);
    }

    public function show($params = [], $post = [], $get = [])
    {
        $postId = $params['id'] ?? null;

        if (!$postId) {
            http_response_code(404);
            echo View::render('errors/404', [], false);
            return;
        }

        $article = Firebase::getPostById($postId);

        if (!$article || $article['type'] !== 'blog' || !$article['published']) {
            http_response_code(404);
            echo View::render('errors/404', [], false);
            return;
        }

        $title = View::escape($article['title']);
        $pageTitle = 'Blog';

        echo View::render('layouts/base', [
            'content' => View::render('blog/show', [
                'article' => $article,
                'pageTitle' => $pageTitle,
            ], false),
            'title' => $title,
        ]);
    }
}

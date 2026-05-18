<?php

namespace App\Controllers;

use App\Firebase;
use App\View;

class NewsController
{
    public function index($params = [], $post = [], $get = [])
    {
        $page = intval($get['page'] ?? 1);
        $limit = 9;
        $offset = ($page - 1) * $limit;

        $news = Firebase::getPosts('news', true, $limit, $offset);
        $title = 'News';
        $pageTitle = 'Neueste News';

        echo View::render('layouts/base', [
            'content' => View::render('news/index', [
                'news' => $news,
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

        if (!$article || $article['type'] !== 'news' || !$article['published']) {
            http_response_code(404);
            echo View::render('errors/404', [], false);
            return;
        }

        $title = View::escape($article['title']);
        $pageTitle = 'News';

        echo View::render('layouts/base', [
            'content' => View::render('news/show', [
                'article' => $article,
                'pageTitle' => $pageTitle,
            ], false),
            'title' => $title,
        ]);
    }
}

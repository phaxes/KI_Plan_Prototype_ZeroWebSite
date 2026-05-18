<?php

namespace App\Controllers\Admin;

use App\Firebase;
use App\View;

class NewsAdminController
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

        $news = Firebase::getPosts('news', null, $limit, $offset);
        $title = 'News Management';
        $pageTitle = 'News verwalten';

        echo View::render('admin/news/index', [
            'news' => $news,
            'pageTitle' => $pageTitle,
            'title' => $title,
        ], 'layouts/admin');
    }

    public function create($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();
        $title = 'Neue News';
        $pageTitle = 'Neue News erstellen';

        echo View::render('admin/news/create', [
            'pageTitle' => $pageTitle,
            'title' => $title,
        ], 'layouts/admin');
    }

    public function store($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();

        try {
            $data = [
                'title' => $post['title'] ?? '',
                'content' => $post['content'] ?? '',
                'type' => 'news',
                'published' => isset($post['published']),
                'imageUrl' => $post['imageUrl'] ?? '',
                'tags' => array_filter(array_map('trim', explode(',', $post['tags'] ?? ''))),
                'authorId' => $_SESSION['email'] ?? 'admin'
            ];

            $newsId = Firebase::createPost($data);

            if ($newsId) {
                header('Location: /admin/news');
                exit;
            } else {
                App::showNotification('Fehler beim Speichern', 'error');
            }
        } catch (\Exception $e) {
            error_log('News create error: ' . $e->getMessage());
        }
    }

    public function edit($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();

        $newsId = $params['id'] ?? null;
        $article = Firebase::getPostById($newsId);

        if (!$article) {
            http_response_code(404);
            return;
        }

        $title = 'News bearbeiten';
        $pageTitle = 'News bearbeiten';

        echo View::render('admin/news/edit', [
            'article' => $article,
            'newsId' => $newsId,
            'pageTitle' => $pageTitle,
            'title' => $title,
        ], 'layouts/admin');
    }

    public function update($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();

        $newsId = $params['id'] ?? null;

        try {
            $data = [
                'title' => $post['title'] ?? '',
                'content' => $post['content'] ?? '',
                'published' => isset($post['published']),
                'imageUrl' => $post['imageUrl'] ?? '',
                'tags' => array_filter(array_map('trim', explode(',', $post['tags'] ?? '')))
            ];

            if (Firebase::updatePost($newsId, $data)) {
                header('Location: /admin/news');
                exit;
            }
        } catch (\Exception $e) {
            error_log('News update error: ' . $e->getMessage());
        }
    }

    public function delete($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();

        $newsId = $params['id'] ?? null;

        try {
            if (Firebase::deletePost($newsId)) {
                header('Location: /admin/news');
                exit;
            }
        } catch (\Exception $e) {
            error_log('News delete error: ' . $e->getMessage());
        }
    }
}

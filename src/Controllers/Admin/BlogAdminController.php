<?php
namespace App\Controllers\Admin;

use App\Firebase;
use App\View;

class BlogAdminController
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

        $articles = Firebase::getPosts('blog', null, $limit, $offset);
        $title = 'Blog Management';
        $pageTitle = 'Blog verwalten';

        echo View::render('admin/blog/index', [
            'articles' => $articles,
            'pageTitle' => $pageTitle,
            'title' => $title,
        ], 'layouts/admin');
    }

    public function create($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();
        $title = 'Neuer Blog-Artikel';
        $pageTitle = 'Blog-Artikel erstellen';

        echo View::render('admin/blog/create', [
            'pageTitle' => $pageTitle,
            'title' => $title,
        ], 'layouts/admin');
    }

    public function edit($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();

        $articleId = $params['id'] ?? null;
        $article = Firebase::getPostById($articleId);

        if (!$article) {
            http_response_code(404);
            return;
        }

        $title = 'Blog-Artikel bearbeiten';
        $pageTitle = 'Blog-Artikel bearbeiten';

        echo View::render('admin/blog/edit', [
            'article' => $article,
            'articleId' => $articleId,
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
                'type' => 'blog',
                'published' => isset($post['published']),
                'imageUrl' => $post['imageUrl'] ?? '',
                'tags' => array_filter(array_map('trim', explode(',', $post['tags'] ?? ''))),
                'authorId' => $_SESSION['email'] ?? 'admin'
            ];

            $articleId = Firebase::createPost($data);

            if ($articleId) {
                header('Location: /admin/blog');
                exit;
            }
        } catch (\Exception $e) {
            error_log('Blog create error: ' . $e->getMessage());
        }
    }

    public function update($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();

        $articleId = $params['id'] ?? null;

        try {
            $data = [
                'title' => $post['title'] ?? '',
                'content' => $post['content'] ?? '',
                'published' => isset($post['published']),
                'imageUrl' => $post['imageUrl'] ?? '',
                'tags' => array_filter(array_map('trim', explode(',', $post['tags'] ?? '')))
            ];

            if (Firebase::updatePost($articleId, $data)) {
                header('Location: /admin/blog');
                exit;
            }
        } catch (\Exception $e) {
            error_log('Blog update error: ' . $e->getMessage());
        }
    }

    public function delete($params = [], $post = [], $get = [])
    {
        $this->checkAdmin();

        $articleId = $params['id'] ?? null;

        try {
            if (Firebase::deletePost($articleId)) {
                header('Location: /admin/blog');
                exit;
            }
        } catch (\Exception $e) {
            error_log('Blog delete error: ' . $e->getMessage());
        }
    }
}

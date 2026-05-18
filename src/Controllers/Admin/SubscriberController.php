<?php

namespace App\Controllers\Admin;

use App\View;

class SubscriberController
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

        $pageTitle = 'Newsletter-Abonnenten';

        echo View::render('admin/subscribers/index', [
            'pageTitle' => $pageTitle,
            'title' => 'Admin - Newsletter',
        ], 'layouts/admin');
    }
}

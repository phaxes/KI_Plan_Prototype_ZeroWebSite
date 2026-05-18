<?php

namespace App\Controllers;

use App\View;
use App\Middleware\AuthMiddleware;

class ProfileController
{
    public function index($params = [], $post = [], $get = [])
    {
        AuthMiddleware::require();

        $title = 'Profil';
        $pageTitle = 'Mein Profil';

        echo View::render('layouts/base', [
            'content' => View::render('profile/index', [
                'pageTitle' => $pageTitle,
                'displayName' => $_SESSION['displayName'] ?? '',
                'email' => $_SESSION['email'] ?? '',
            ], false),
            'title' => $title,
        ]);
    }
}

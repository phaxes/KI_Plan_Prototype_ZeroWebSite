<?php

namespace App\Middleware;

use App\Auth;

class AuthMiddleware
{
    /**
     * Check if user is authenticated, redirect to login if not
     */
    public static function require()
    {
        if (!Auth::isLoggedIn()) {
            header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
            exit;
        }
    }

    /**
     * Check if user is admin, redirect to home if not
     */
    public static function requireAdmin()
    {
        if (!Auth::isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        if (!Auth::isAdmin()) {
            http_response_code(403);
            echo 'Access Denied';
            exit;
        }
    }

    /**
     * Check if user is guest (not logged in)
     */
    public static function requireGuest()
    {
        if (Auth::isLoggedIn()) {
            header('Location: /profile');
            exit;
        }
    }
}

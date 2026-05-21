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
        // If coming from logout, ensure session is truly empty
        if (isset($_GET['from']) && $_GET['from'] === 'logout') {
            // Force start fresh session
            session_write_close();
            session_unset();
            session_destroy();
            session_start();
        }

        if (Auth::isLoggedIn()) {
            header('Location: /profile');
            exit;
        }
    }
}

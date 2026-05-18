<?php

namespace App\Controllers;

use App\Auth;
use App\View;
use App\Middleware\AuthMiddleware;

class AuthController
{
    public function login($params = [], $post = [], $get = [])
    {
        AuthMiddleware::requireGuest();

        $title = 'Login';
        $pageTitle = 'Anmelden';

        echo View::render('layouts/base', [
            'content' => View::render('auth/login', [
                'pageTitle' => $pageTitle,
            ], false),
            'title' => $title,
        ]);
    }

    public function register($params = [], $post = [], $get = [])
    {
        AuthMiddleware::requireGuest();

        $title = 'Registrieren';
        $pageTitle = 'Registrieren';

        echo View::render('layouts/base', [
            'content' => View::render('auth/register', [
                'pageTitle' => $pageTitle,
            ], false),
            'title' => $title,
        ]);
    }

    public function verify($params = [], $post = [], $get = [])
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $idToken = $input['idToken'] ?? null;
            $email = $input['email'] ?? null;
            $displayName = $input['displayName'] ?? '';
            $uid = $input['uid'] ?? null;

            if (!$idToken || !$email || !$uid) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }

            // Verify token with Firebase
            $tokenData = Auth::verifyToken($idToken);

            if (!$tokenData) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid token']);
                return;
            }

            // Setup PHP session
            Auth::setupSession($uid, $email, $displayName);

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Session established']);

        } catch (\Exception $e) {
            error_log('Token verification error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Verification failed']);
        }
    }

    public function logout($params = [], $post = [], $get = [])
    {
        Auth::logout();
        header('Location: /');
        exit;
    }
}

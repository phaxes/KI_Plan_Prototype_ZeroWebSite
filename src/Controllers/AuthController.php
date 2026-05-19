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

        echo View::render('auth/login', [
            'pageTitle' => $pageTitle,
            'title' => $title,
        ]);
    }

    public function register($params = [], $post = [], $get = [])
    {
        AuthMiddleware::requireGuest();

        $title = 'Registrieren';
        $pageTitle = 'Registrieren';

        echo View::render('auth/register', [
            'pageTitle' => $pageTitle,
            'title' => $title,
        ]);
    }

    public function verify($params = [], $post = [], $get = [])
    {
        header('Content-Type: application/json');

        try {
            // Try multiple header formats (reverse proxies might transform them)
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ??
                          $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ??
                          $_SERVER['X_AUTHORIZATION'] ?? '';

            error_log('Looking for auth header...');
            error_log('HTTP_AUTHORIZATION: ' . ($_SERVER['HTTP_AUTHORIZATION'] ?? 'not set'));
            error_log('REDIRECT_HTTP_AUTHORIZATION: ' . ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 'not set'));
            error_log('X_AUTHORIZATION: ' . ($_SERVER['X_AUTHORIZATION'] ?? 'not set'));

            if (!$authHeader) {
                error_log('No Authorization header found. All headers: ' . json_encode(getallheaders() ?: $_SERVER));
                http_response_code(400);
                echo json_encode(['error' => 'Missing or invalid Authorization header']);
                return;
            }

            if (!preg_match('/Bearer\s+(.+)$/', $authHeader, $matches)) {
                error_log('Invalid auth header format: ' . substr($authHeader, 0, 50));
                http_response_code(400);
                echo json_encode(['error' => 'Missing or invalid Authorization header']);
                return;
            }

            $idToken = $matches[1];
            error_log('Token extracted, length: ' . strlen($idToken));

            // Get user data from request body
            $rawInput = file_get_contents('php://input');
            error_log('Raw input: ' . $rawInput);

            $input = json_decode($rawInput, true) ?? [];
            $email = $input['email'] ?? null;
            $displayName = $input['displayName'] ?? '';
            $uid = $input['uid'] ?? null;

            error_log('Parsed - email: ' . ($email ? 'set' : 'null') . ', uid: ' . ($uid ? 'set' : 'null'));

            if (!$email || !$uid) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields: email, uid', 'received' => ['email' => $email, 'uid' => $uid]]);
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

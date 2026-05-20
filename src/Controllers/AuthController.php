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

    public function debugToken($params = [], $post = [], $get = [])
    {
        header('Content-Type: application/json');

        try {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true) ?? [];

            $idToken = $input['idToken'] ?? null;

            if (!$idToken) {
                echo json_encode(['error' => 'No token provided']);
                return;
            }

            $parts = explode('.', $idToken);
            $result = [
                'tokenLength' => strlen($idToken),
                'numParts' => count($parts),
                'partLengths' => array_map('strlen', $parts)
            ];

            if (count($parts) === 3) {
                try {
                    $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/')), true);
                    $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                    $result['header'] = $header;
                    $result['payload'] = $payload;
                    $result['timestamp'] = time();
                } catch (\Exception $e) {
                    $result['decodeError'] = $e->getMessage();
                }
            }

            echo json_encode($result, JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function verify($params = [], $post = [], $get = [])
    {
        header('Content-Type: application/json');

        try {
            // Get data from request body (Authorization header may be stripped on Render)
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true) ?? [];

            $idToken = $input['idToken'] ?? null;
            $email = $input['email'] ?? null;
            $displayName = $input['displayName'] ?? '';
            $uid = $input['uid'] ?? null;

            error_log("Auth verify - uid: $uid, email: $email, token length: " . strlen($idToken ?? ''));
            error_log("Token format check: parts = " . count(explode('.', $idToken ?? '')));

            if ($idToken && strlen($idToken) < 500) {
                error_log("Token preview: " . substr($idToken, 0, 100));
            }

            if (!$idToken || !$email || !$uid) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields: idToken, email, uid']);
                return;
            }

            // Verify token with Firebase
            $tokenData = Auth::verifyToken($idToken);

            if (!$tokenData) {
                error_log("Auth verify: token verification returned null");
                http_response_code(401);

                // Try to provide more specific error message
                $parts = explode('.', $idToken);
                $errorMsg = 'Invalid token';

                if (count($parts) === 3) {
                    try {
                        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
                        $iss = $payload['iss'] ?? '';

                        if (strpos($iss, 'firebase-adminsdk') !== false) {
                            $errorMsg = 'Wrong token type: Admin SDK token received. Client must use user.getIdToken() for ID tokens.';
                        } elseif (isset($payload['exp']) && $payload['exp'] < time()) {
                            $errorMsg = 'Token expired';
                        }
                    } catch (\Exception $e) {
                        // Ignore decode errors, use generic message
                    }
                }

                echo json_encode(['error' => $errorMsg]);
                return;
            }

            // Setup PHP session
            Auth::setupSession($uid, $email, $displayName);

            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Session established']);

        } catch (\Exception $e) {
            error_log('Token verification error: ' . $e->getMessage());
            error_log('Token verification stack trace: ' . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['error' => 'Verification failed: ' . $e->getMessage()]);
        }
    }

    public function logout($params = [], $post = [], $get = [])
    {
        Auth::logout();
        header('Location: /');
        exit;
    }
}

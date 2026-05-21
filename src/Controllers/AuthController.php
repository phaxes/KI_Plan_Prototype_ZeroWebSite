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

        // Clear any authentication-related local storage on client side
        // by redirecting to login page
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Location: /login');
        exit;
    }

    public function changePassword($params = [], $post = [], $get = [])
    {
        header('Content-Type: application/json');

        // Require authentication
        if (!Auth::isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['error' => 'Not authenticated']);
            return;
        }

        try {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true) ?? [];

            $currentPassword = $input['currentPassword'] ?? '';
            $newPassword = $input['newPassword'] ?? '';
            $confirmPassword = $input['confirmPassword'] ?? '';

            // Validation
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                http_response_code(400);
                echo json_encode(['error' => 'All fields are required']);
                return;
            }

            if ($newPassword !== $confirmPassword) {
                http_response_code(400);
                echo json_encode(['error' => 'Passwords do not match']);
                return;
            }

            if (strlen($newPassword) < 6) {
                http_response_code(400);
                echo json_encode(['error' => 'Password must be at least 6 characters']);
                return;
            }

            // The actual password change happens client-side via Firebase SDK
            // This endpoint can be used for validation or logging in the future
            // Return success - client will handle Firebase password update

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Password change request validated. Please complete the change in your browser.'
            ]);

        } catch (\Exception $e) {
            error_log('Password change error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to process password change']);
        }
    }
}

<?php

namespace App\Controllers;

use App\Auth;
use App\Middleware\AuthMiddleware;

class AuthController
{
    public function login($params = [], $post = [], $get = [])
    {
        // Redirect if already logged in
        AuthMiddleware::requireGuest();

        $title = 'Login';
        $pageTitle = 'Anmelden';

        ob_start();
        ?>
        <section class="py-12">
            <div class="container mx-auto px-4 max-w-md">
                <h1 class="text-4xl font-bold mb-8"><?= htmlspecialchars($pageTitle) ?></h1>

                <div class="bg-white rounded shadow p-8">
                    <form id="login-form">
                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-2">E-Mail</label>
                            <input type="email" id="email" required class="w-full px-4 py-2 border rounded" placeholder="deine@email.de">
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-2">Passwort</label>
                            <input type="password" id="password" required class="w-full px-4 py-2 border rounded" placeholder="••••••••">
                        </div>

                        <button type="submit" class="w-full bg-primary text-white px-6 py-3 rounded hover:bg-blue-600 transition font-semibold">
                            Anmelden
                        </button>

                        <div class="mt-4 text-sm text-gray-600">
                            <p>Test-Konto:</p>
                            <p><code>test@example.com</code></p>
                            <p><code>password123</code></p>
                        </div>
                    </form>

                    <div class="border-t mt-6 pt-6">
                        <p class="text-gray-600">Noch kein Konto?</p>
                        <a href="/register" class="text-primary hover:text-blue-600 transition font-semibold">Hier registrieren</a>
                    </div>
                </div>
            </div>
        </section>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/base.php';
    }

    public function register($params = [], $post = [], $get = [])
    {
        // Redirect if already logged in
        AuthMiddleware::requireGuest();

        $title = 'Registrieren';
        $pageTitle = 'Registrieren';

        ob_start();
        ?>
        <section class="py-12">
            <div class="container mx-auto px-4 max-w-md">
                <h1 class="text-4xl font-bold mb-8"><?= htmlspecialchars($pageTitle) ?></h1>

                <div class="bg-white rounded shadow p-8">
                    <form id="register-form">
                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-2">Name</label>
                            <input type="text" id="name" required class="w-full px-4 py-2 border rounded" placeholder="Dein Name">
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-2">E-Mail</label>
                            <input type="email" id="email" required class="w-full px-4 py-2 border rounded" placeholder="deine@email.de">
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-2">Passwort</label>
                            <input type="password" id="password" required class="w-full px-4 py-2 border rounded" placeholder="••••••••">
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-2">Passwort wiederholen</label>
                            <input type="password" id="password-confirm" required class="w-full px-4 py-2 border rounded" placeholder="••••••••">
                        </div>

                        <button type="submit" class="w-full bg-primary text-white px-6 py-3 rounded hover:bg-blue-600 transition font-semibold">
                            Registrieren
                        </button>
                    </form>

                    <div class="border-t mt-6 pt-6">
                        <p class="text-gray-600">Bereits registriert?</p>
                        <a href="/login" class="text-primary hover:text-blue-600 transition font-semibold">Hier anmelden</a>
                    </div>
                </div>
            </div>
        </section>
        <?php
        $content = ob_get_clean();
        require __DIR__ . '/../../templates/layouts/base.php';
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

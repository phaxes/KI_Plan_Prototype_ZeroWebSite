<?php

namespace App\Controllers;

use App\View;
use App\Middleware\AuthMiddleware;
use App\Firebase;
use App\Config;
use App\FirestoreRest;

class ProfileController
{
    public function index($params = [], $post = [], $get = [])
    {
        AuthMiddleware::require();

        $title = 'Profil';
        $pageTitle = 'Mein Profil';

        echo View::render('profile/index', [
            'pageTitle' => $pageTitle,
            'displayName' => $_SESSION['displayName'] ?? '',
            'email' => $_SESSION['email'] ?? '',
            'userId' => $_SESSION['userId'] ?? '',
            'title' => $title,
        ]);
    }

    public function updateProfile($params = [], $post = [], $get = [])
    {
        AuthMiddleware::require();
        header('Content-Type: application/json');

        try {
            $userId = $_SESSION['userId'] ?? null;
            if (!$userId) {
                http_response_code(401);
                echo json_encode(['error' => 'Not authenticated']);
                return;
            }

            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true) ?? [];

            $displayName = trim($input['displayName'] ?? '');

            if (empty($displayName)) {
                http_response_code(400);
                echo json_encode(['error' => 'Display name is required']);
                return;
            }

            if (strlen($displayName) < 2) {
                http_response_code(400);
                echo json_encode(['error' => 'Name must be at least 2 characters']);
                return;
            }

            // Update Firestore via REST API if configured
            try {
                $projectId = Config::get('FIREBASE_PROJECT_ID');
                $serviceAccountJson = Config::get('FIREBASE_SERVICE_ACCOUNT_JSON');

                if ($projectId && $serviceAccountJson) {
                    if (file_exists($serviceAccountJson)) {
                        $serviceAccountJson = file_get_contents($serviceAccountJson);
                    }

                    $restClient = FirestoreRest::getInstance($projectId, $serviceAccountJson);
                    $restClient->setDocument('users', $userId, [
                        'displayName' => $displayName,
                        'updatedAt' => new \DateTime()
                    ]);
                }
            } catch (\Exception $e) {
                error_log('Profile update: Firestore unavailable - ' . $e->getMessage());
            }

            // Update session
            $_SESSION['displayName'] = $displayName;

            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully',
                'displayName' => $displayName
            ]);

        } catch (\Exception $e) {
            error_log('Profile update error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update profile']);
        }
    }

    public function changePasswordForm($params = [], $post = [], $get = [])
    {
        AuthMiddleware::require();

        $title = 'Passwort ändern';
        $pageTitle = 'Passwort ändern';

        echo View::render('profile/change-password', [
            'pageTitle' => $pageTitle,
            'title' => $title,
        ]);
    }

    public function newsletterForm($params = [], $post = [], $get = [])
    {
        AuthMiddleware::require();

        $userId = $_SESSION['userId'] ?? null;
        if (!$userId) {
            http_response_code(401);
            header('Location: /login');
            exit;
        }
        $preferences = [];

        try {
            $projectId = Config::get('FIREBASE_PROJECT_ID');
            $serviceAccountJson = Config::get('FIREBASE_SERVICE_ACCOUNT_JSON');

            if ($projectId && $serviceAccountJson) {
                if (file_exists($serviceAccountJson)) {
                    $serviceAccountJson = file_get_contents($serviceAccountJson);
                }

                $restClient = FirestoreRest::getInstance($projectId, $serviceAccountJson);
                $preferences = $restClient->getDocument('userNewsletterPreferences', $userId) ?? [];
            }
        } catch (\Exception $e) {
            error_log('Newsletter form: ' . $e->getMessage());
        }

        $title = 'Newsletter-Einstellungen';
        $pageTitle = 'Newsletter-Einstellungen';

        echo View::render('profile/newsletter', [
            'pageTitle' => $pageTitle,
            'title' => $title,
            'subscribed' => $preferences['subscribed'] ?? false,
            'categories' => $preferences['categories'] ?? [],
        ]);
    }

    public function updateNewsletterPreference($params = [], $post = [], $get = [])
    {
        AuthMiddleware::require();
        header('Content-Type: application/json');

        try {
            $userId = $_SESSION['userId'] ?? null;
            if (!$userId) {
                http_response_code(401);
                echo json_encode(['error' => 'Not authenticated']);
                return;
            }

            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true) ?? [];

            $subscribed = (bool) ($input['subscribed'] ?? false);
            $categories = (array) ($input['categories'] ?? []);

            $projectId = Config::get('FIREBASE_PROJECT_ID');
            $serviceAccountJson = Config::get('FIREBASE_SERVICE_ACCOUNT_JSON');

            if ($projectId && $serviceAccountJson) {
                if (file_exists($serviceAccountJson)) {
                    $serviceAccountJson = file_get_contents($serviceAccountJson);
                }

                $restClient = FirestoreRest::getInstance($projectId, $serviceAccountJson);
                $restClient->setDocument('userNewsletterPreferences', $userId, [
                    'subscribed' => $subscribed,
                    'categories' => $categories,
                    'updatedAt' => new \DateTime()
                ]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Newsletter preferences updated'
                ]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Firestore not configured']);
            }

        } catch (\Exception $e) {
            error_log('Newsletter preference update error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update preferences']);
        }
    }
}

<?php

namespace App\Controllers;

use App\View;
use App\Middleware\AuthMiddleware;
use App\Firebase;

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

            // Update Firestore (with null check)
            $firestore = Firebase::firestore();
            if ($firestore !== null) {
                $firestore->collection('users')->document($userId)->update([
                    'displayName' => $displayName,
                    'updatedAt' => new \DateTime()
                ]);
            } else {
                error_log('Profile update: Firestore unavailable, skipping');
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
        $preferences = [];

        try {
            // Get current preferences (with null check)
            $firestore = Firebase::firestore();
            if ($firestore !== null) {
                $doc = $firestore->collection('userNewsletterPreferences')->document($userId)->snapshot();
                if ($doc->exists()) {
                    $preferences = $doc->data() ?? [];
                }
            } else {
                error_log('Newsletter form: Firestore unavailable');
            }
        } catch (\Exception $e) {
            error_log('Newsletter preferences fetch error: ' . $e->getMessage());
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

            $subscribed = $input['subscribed'] ?? false;
            $categories = $input['categories'] ?? [];

            // Update Firestore (with null check)
            $firestore = Firebase::firestore();
            if ($firestore !== null) {
                $firestore->collection('userNewsletterPreferences')->document($userId)->set([
                    'subscribed' => (bool) $subscribed,
                    'categories' => (array) $categories,
                    'updatedAt' => new \DateTime()
                ], ['merge' => true]);
            } else {
                error_log('Newsletter preference update: Firestore unavailable, skipping');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Newsletter preferences updated'
            ]);

        } catch (\Exception $e) {
            error_log('Newsletter preference update error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update preferences']);
        }
    }
}

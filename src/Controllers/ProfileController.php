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
                $serviceAccountPath = Config::get('FIREBASE_SERVICE_ACCOUNT_JSON');

                if ($projectId && $serviceAccountPath) {
                    // Try to load service account JSON from file or use as-is if it's a JSON string
                    $serviceAccountJson = null;
                    if (file_exists($serviceAccountPath)) {
                        $serviceAccountJson = file_get_contents($serviceAccountPath);
                    } else {
                        $projectRoot = dirname(__DIR__, 2);
                        $resolvedPath = $projectRoot . '/' . $serviceAccountPath;
                        if (file_exists($resolvedPath)) {
                            $serviceAccountJson = file_get_contents($resolvedPath);
                        } else {
                            $serviceAccountJson = $serviceAccountPath;
                        }
                    }

                    if ($serviceAccountJson) {
                        $restClient = FirestoreRest::getInstance($projectId, $serviceAccountJson);
                        $restClient->setDocument('users', $userId, [
                            'displayName' => $displayName,
                            'updatedAt' => new \DateTime()
                        ]);
                    }
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

        // Try to load from session first (for immediate updates after save)
        $preferences = [];
        if (isset($_SESSION['newsletterSubscribed'])) {
            $preferences['subscribed'] = $_SESSION['newsletterSubscribed'];
            $preferences['categories'] = $_SESSION['newsletterCategories'] ?? [];
        } else {
            // Fall back to Firestore if not in session
            try {
                $projectId = Config::get('FIREBASE_PROJECT_ID');
                $serviceAccountPath = Config::get('FIREBASE_SERVICE_ACCOUNT_JSON');

                if ($projectId && $serviceAccountPath) {
                    // Try to load service account JSON from file or use as-is
                    $serviceAccountJson = null;
                    if (file_exists($serviceAccountPath)) {
                        $serviceAccountJson = file_get_contents($serviceAccountPath);
                    } else {
                        $projectRoot = dirname(__DIR__, 2);
                        $resolvedPath = $projectRoot . '/' . $serviceAccountPath;
                        if (file_exists($resolvedPath)) {
                            $serviceAccountJson = file_get_contents($resolvedPath);
                        } else {
                            $serviceAccountJson = $serviceAccountPath;
                        }
                    }

                    if ($serviceAccountJson) {
                        $restClient = FirestoreRest::getInstance($projectId, $serviceAccountJson);
                        $preferences = $restClient->getDocument('userNewsletterPreferences', $userId) ?? [];
                    }
                }
            } catch (\Exception $e) {
                error_log('Newsletter form: ' . $e->getMessage());
            }
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

            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON input']);
                return;
            }

            $subscribed = (bool) ($input['subscribed'] ?? false);
            $categories = (array) ($input['categories'] ?? []);

            error_log('Newsletter preference update: userId=' . $userId . ', subscribed=' . ($subscribed ? 'true' : 'false') . ', categories=' . json_encode($categories));

            $projectId = Config::get('FIREBASE_PROJECT_ID');
            $serviceAccountPath = Config::get('FIREBASE_SERVICE_ACCOUNT_JSON');

            if (!$projectId || !$serviceAccountPath) {
                http_response_code(500);
                echo json_encode(['error' => 'Firestore not configured']);
                return;
            }

            // Try to load service account JSON from multiple sources
            $serviceAccountJson = null;

            // 1. Try absolute path
            if (file_exists($serviceAccountPath)) {
                error_log('Newsletter: Loading service account from absolute path: ' . $serviceAccountPath);
                $serviceAccountJson = file_get_contents($serviceAccountPath);
                if ($serviceAccountJson === false) {
                    throw new \Exception('Failed to read service account file: ' . $serviceAccountPath);
                }
            } else {
                // 2. Try relative to project root
                $projectRoot = dirname(__DIR__, 2);
                $resolvedPath = $projectRoot . '/' . $serviceAccountPath;
                if (file_exists($resolvedPath)) {
                    error_log('Newsletter: Loading service account from resolved path: ' . $resolvedPath);
                    $serviceAccountJson = file_get_contents($resolvedPath);
                    if ($serviceAccountJson === false) {
                        throw new \Exception('Failed to read service account file: ' . $resolvedPath);
                    }
                } else {
                    // 3. Try as base64-encoded or raw JSON string
                    error_log('Newsletter: Service account file not found at ' . $serviceAccountPath . ' or ' . $resolvedPath . ', treating as JSON string');
                    $serviceAccountJson = $serviceAccountPath;
                }
            }

            error_log('Newsletter: Initializing FirestoreRest client...');
            $restClient = FirestoreRest::getInstance($projectId, $serviceAccountJson);
            error_log('Newsletter: FirestoreRest client initialized, calling setDocument...');

            $success = $restClient->setDocument('userNewsletterPreferences', $userId, [
                'subscribed' => $subscribed,
                'categories' => $categories,
                'updatedAt' => new \DateTime()
            ]);

            error_log('Newsletter: setDocument returned: ' . ($success ? 'true' : 'false'));

            if (!$success) {
                error_log('Newsletter: setDocument failed, returning error response');
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save preferences to database']);
                return;
            }

            // Save preferences to session for immediate use
            $_SESSION['newsletterSubscribed'] = $subscribed;
            $_SESSION['newsletterCategories'] = $categories;

            echo json_encode([
                'success' => true,
                'message' => 'Newsletter preferences updated'
            ]);

        } catch (\Exception $e) {
            error_log('Newsletter preference update error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode([
                'error' => 'Failed to update preferences',
                'details' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
        }
    }
}

<?php

namespace App;

use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Factory;

class Auth
{
    private static $auth = null;

    public static function getAuthService()
    {
        if (self::$auth === null) {
            $serviceAccountJson = Config::get('FIREBASE_SERVICE_ACCOUNT_JSON');

            if (!$serviceAccountJson) {
                error_log('FIREBASE_SERVICE_ACCOUNT_JSON not configured');
                error_log('Config keys: ' . implode(', ', array_keys(Config::all())));
                throw new \Exception('Firebase service account not configured');
            }

            error_log('Service account source: ' . (strlen($serviceAccountJson) > 100 ? 'JSON string' : 'file path'));

            // Handle both file path (localhost) and base64-encoded JSON (Render)
            if (!file_exists($serviceAccountJson)) {
                error_log('Service account path does not exist (expected for Render): ' . $serviceAccountJson);

                // Try to decode if it's base64-encoded (for Render.com)
                $decoded = base64_decode($serviceAccountJson, true);
                if ($decoded !== false && strlen($decoded) > 100) {
                    error_log('Successfully decoded base64-encoded service account');
                    $serviceAccountJson = $decoded;
                } else {
                    error_log('Could not decode as base64, will try as raw JSON');
                }

                // Now validate JSON
                $parsed = json_decode($serviceAccountJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log('JSON decode error: ' . json_last_error_msg());
                    throw new \Exception('FIREBASE_SERVICE_ACCOUNT_JSON is not valid JSON: ' . json_last_error_msg());
                }

                error_log('Service account JSON is valid, project_id: ' . ($parsed['project_id'] ?? 'missing'));

                // Ensure we have the required fields
                $required = ['type', 'project_id', 'private_key', 'client_email'];
                foreach ($required as $field) {
                    if (!isset($parsed[$field]) || empty($parsed[$field])) {
                        throw new \Exception("FIREBASE_SERVICE_ACCOUNT_JSON missing required field: $field");
                    }
                }

                // Try to create temporary file for kreait library
                $tempFile = self::createTempServiceAccountFile($serviceAccountJson);
                if ($tempFile) {
                    error_log('Created temporary service account file: ' . $tempFile);
                    $serviceAccountJson = $tempFile;
                } else {
                    throw new \Exception('Could not create temporary service account file and direct JSON not supported by kreait');
                }
            } else {
                error_log('Using local service account file: ' . $serviceAccountJson);
            }

            try {
                error_log('Initializing Firebase Admin SDK with service account');
                $factory = new Factory();
                $firebase = $factory->withServiceAccount($serviceAccountJson);
                self::$auth = $firebase->createAuth();
                error_log('Firebase Admin SDK initialized successfully');
            } catch (\Exception $e) {
                error_log('Firebase Auth Error: ' . $e->getMessage());
                error_log('Service account source was: ' . (strlen($serviceAccountJson) > 200 ? 'file path' : 'JSON'));
                throw $e;
            }
        }

        return self::$auth;
    }

    private static function createTempServiceAccountFile($jsonContent)
    {
        // Try multiple possible temp directories
        $tempDirs = [
            sys_get_temp_dir(),
            '/tmp',
            getcwd() . '/.cache',
            __DIR__ . '/../.cache'
        ];

        foreach ($tempDirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            if (is_dir($dir) && is_writable($dir)) {
                $tempFile = $dir . '/firebase_sa_' . uniqid() . '.json';
                if (file_put_contents($tempFile, $jsonContent) !== false) {
                    @chmod($tempFile, 0600);
                    error_log('Created temp service account file: ' . $tempFile);
                    return $tempFile;
                }
            }
        }

        error_log('Could not create temp service account file in any directory. Tried: ' . implode(', ', $tempDirs));
        return null;
    }

    /**
     * Analyze token type and issuer
     */
    private static function analyzeToken($idToken)
    {
        try {
            $parts = explode('.', $idToken);
            if (count($parts) !== 3) {
                return ['type' => 'invalid', 'reason' => 'Not a JWT (wrong part count)'];
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            $iss = $payload['iss'] ?? '';
            $aud = $payload['aud'] ?? '';

            // Identify token type by issuer
            if (strpos($iss, 'firebase-adminsdk') !== false) {
                return [
                    'type' => 'custom',
                    'reason' => 'Admin SDK custom token (issuer contains firebase-adminsdk)',
                    'issuer' => $iss
                ];
            } elseif (strpos($iss, 'https://securetoken.google.com/') === 0) {
                return [
                    'type' => 'id_token',
                    'reason' => 'Valid Firebase ID token',
                    'issuer' => $iss
                ];
            } else {
                return [
                    'type' => 'unknown',
                    'reason' => 'Unknown token type',
                    'issuer' => $iss
                ];
            }
        } catch (\Exception $e) {
            return ['type' => 'unparseable', 'reason' => 'Could not parse token'];
        }
    }

    /**
     * Verify Firebase ID token and get user info
     */
    public static function verifyToken($idToken)
    {
        try {
            if (!$idToken) {
                error_log('Token verification failed: empty token');
                return null;
            }

            // Validate token format (JWT should have 3 parts separated by dots)
            $tokenParts = explode('.', $idToken);
            if (count($tokenParts) !== 3) {
                error_log('Token verification failed: invalid JWT format (expected 3 parts, got ' . count($tokenParts) . ')');
                return null;
            }

            // Analyze token type
            $tokenAnalysis = self::analyzeToken($idToken);
            error_log('Token analysis: type=' . $tokenAnalysis['type'] . ', reason=' . $tokenAnalysis['reason']);

            // Decode header and payload to inspect (without verification first)
            try {
                $header = json_decode(base64_decode(strtr($tokenParts[0], '-_', '+/')), true);
                $payload = json_decode(base64_decode(strtr($tokenParts[1], '-_', '+/')), true);
                error_log('Token header: ' . json_encode($header));
                error_log('Token payload claims - iss: ' . ($payload['iss'] ?? 'missing') .
                         ', aud: ' . ($payload['aud'] ?? 'missing') .
                         ', exp: ' . ($payload['exp'] ?? 'missing') .
                         ', iat: ' . ($payload['iat'] ?? 'missing') .
                         ', current_time: ' . time());

                // Check if token is expired
                if (isset($payload['exp']) && $payload['exp'] < time()) {
                    error_log('Token has expired: exp=' . $payload['exp'] . ', current=' . time());
                }
                if (isset($payload['iat']) && $payload['iat'] > time() + 60) {
                    error_log('Token issued in future: iat=' . $payload['iat'] . ', current=' . time());
                }
            } catch (\Exception $e) {
                error_log('Error decoding token payload: ' . $e->getMessage());
            }

            $auth = self::getAuthService();
            if (!$auth) {
                error_log('Token verification failed: Firebase Auth service not initialized');
                return null;
            }

            error_log('Attempting to verify token with Firebase Admin SDK');
            $verifiedToken = $auth->verifyIdToken($idToken);
            error_log('Token verified successfully');

            $uid = $verifiedToken->claims()->get('sub');

            return [
                'uid' => $uid,
                'email' => $verifiedToken->claims()->get('email'),
                'emailVerified' => $verifiedToken->claims()->get('email_verified'),
                'displayName' => $verifiedToken->claims()->get('name') ?? ''
            ];
        } catch (\Throwable $e) {
            error_log('Token verification failed with exception: ' . get_class($e));
            error_log('Exception message: ' . $e->getMessage());

            // Provide specific error message based on token type
            $tokenAnalysis = self::analyzeToken($idToken);
            if ($tokenAnalysis['type'] === 'custom') {
                error_log('ERROR: Received Admin SDK custom token instead of Firebase ID token');
                error_log('FIX: Client must use user.getIdToken() to get ID token, not Admin SDK createCustomToken()');
            }

            error_log('Token verification stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Get user from Firestore (using REST API as fallback)
     */
    public static function getUserFromFirestore($uid)
    {
        try {
            // Try gRPC-based Kreait first
            $firestore = Firebase::firestore();
            if ($firestore !== null) {
                $doc = $firestore->collection('users')->document($uid)->snapshot();
                if ($doc->exists()) {
                    return $doc->data();
                }
            }
        } catch (\Exception $e) {
            error_log('Get user error (gRPC): ' . $e->getMessage());
        }

        // Fallback to REST API
        try {
            $projectId = Config::get('FIREBASE_PROJECT_ID');
            $serviceAccountJson = Config::get('FIREBASE_SERVICE_ACCOUNT_JSON');

            if (!$projectId || !$serviceAccountJson) {
                error_log('Get user error: Firebase not configured');
                return null;
            }

            // Handle file path
            if (file_exists($serviceAccountJson)) {
                $serviceAccountJson = file_get_contents($serviceAccountJson);
            }

            $restClient = FirestoreRest::getInstance($projectId, $serviceAccountJson);
            return $restClient->getDocument('users', $uid);
        } catch (\Exception $e) {
            error_log('Get user error (REST): ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create/Update user in Firestore after first login
     */
    public static function createOrUpdateUser($uid, $email, $displayName = '')
    {
        try {
            // Try gRPC-based approach first
            $firestore = Firebase::firestore();
            if ($firestore !== null) {
                $userData = [
                    'email' => $email,
                    'displayName' => $displayName,
                    'updatedAt' => new \DateTime()
                ];

                $userDoc = $firestore->collection('users')->document($uid);
                $snapshot = $userDoc->snapshot();

                if ($snapshot->exists()) {
                    $userDoc->update($userData);
                } else {
                    $userData['isAdmin'] = false;
                    $userData['createdAt'] = new \DateTime();
                    $userDoc->set($userData);
                }

                return true;
            }
        } catch (\Exception $e) {
            error_log('Create/update user error (gRPC): ' . $e->getMessage());
        }

        // Fallback to REST API
        try {
            $projectId = Config::get('FIREBASE_PROJECT_ID');
            $serviceAccountJson = Config::get('FIREBASE_SERVICE_ACCOUNT_JSON');

            if (!$projectId || !$serviceAccountJson) {
                error_log('Create/update user error: Firebase not configured');
                return false;
            }

            // Handle file path
            if (file_exists($serviceAccountJson)) {
                $serviceAccountJson = file_get_contents($serviceAccountJson);
            }

            // Check if user exists
            $restClient = FirestoreRest::getInstance($projectId, $serviceAccountJson);
            $existingUser = $restClient->getDocument('users', $uid);

            $userData = [
                'email' => $email,
                'displayName' => $displayName,
                'updatedAt' => new \DateTime()
            ];

            if (!$existingUser) {
                // New user
                $userData['isAdmin'] = false;
                $userData['createdAt'] = new \DateTime();
            }

            $restClient->setDocument('users', $uid, $userData);
            error_log('Create/update user: SUCCESS (REST API)');
            return true;
        } catch (\Exception $e) {
            error_log('Create/update user error (REST): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is admin
     * @param $uid string User UID
     * @param $email string User email (optional, used for fallback)
     */
    public static function isUserAdmin($uid, $email = null)
    {
        try {
            $user = self::getUserFromFirestore($uid);

            if ($user && isset($user['isAdmin'])) {
                $isAdmin = $user['isAdmin'];
                // Handle various truthy representations of admin status
                $result = ($isAdmin === true) || ($isAdmin === 1) || ($isAdmin === '1') || ($isAdmin === 'true');
                error_log('Admin check: uid=' . $uid . ', isAdmin=' . var_export($isAdmin, true) . ', result=' . ($result ? 'true' : 'false'));
                return $result;
            }

            error_log('Admin check: isAdmin field not found for uid=' . $uid);

            // Fallback: Check if this is a hardcoded admin user
            // This allows admin access when Firestore is unavailable
            $checkEmail = $user['email'] ?? $email ?? null;
            if ($checkEmail && in_array($checkEmail, ['test@example.com', 'admin@example.com'])) {
                error_log('Admin check: User ' . $checkEmail . ' is hardcoded admin');
                return true;
            }

            return false;
        } catch (\Exception $e) {
            error_log('Admin check error: ' . $e->getMessage());

            // Fallback for when Firestore is completely unavailable:
            // Use the provided email parameter or fall back to session
            $checkEmail = $email ?? $_SESSION['email'] ?? null;
            if ($checkEmail && in_array($checkEmail, ['test@example.com', 'admin@example.com'])) {
                error_log('Admin check fallback: User ' . $checkEmail . ' is hardcoded admin');
                return true;
            }

            return false;
        }
    }

    /**
     * Setup PHP session from verified token
     */
    public static function setupSession($uid, $email, $displayName)
    {
        try {
            // Regenerate session ID after login to prevent session fixation attacks
            session_regenerate_id(true);

            // Verify user exists or create
            self::createOrUpdateUser($uid, $email, $displayName);

            // Get admin status
            $isAdmin = self::isUserAdmin($uid, $email);

            // Set session variables
            $_SESSION['userId'] = $uid;
            $_SESSION['email'] = $email;
            $_SESSION['displayName'] = $displayName;
            $_SESSION['isAdmin'] = $isAdmin;
            $_SESSION['loginTime'] = time();

            return true;
        } catch (\Throwable $e) {
            error_log('Session setup error: ' . $e->getMessage());
            // Continue even if Firestore fails - just no admin status
            // Still regenerate session ID even if Firestore fails
            session_regenerate_id(true);
            $_SESSION['userId'] = $uid;
            $_SESSION['email'] = $email;
            $_SESSION['displayName'] = $displayName;
            $_SESSION['isAdmin'] = false;
            $_SESSION['loginTime'] = time();
            return true;
        }
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn()
    {
        return isset($_SESSION['userId']) && !empty($_SESSION['userId']);
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin()
    {
        return isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true;
    }

    /**
     * Get current user ID
     */
    public static function getCurrentUserId()
    {
        return $_SESSION['userId'] ?? null;
    }

    /**
     * Logout user
     */
    public static function logout()
    {
        $sessionId = session_id();
        error_log('Logout initiated - session_id: ' . $sessionId);

        // Unset all session variables
        $_SESSION = array();
        error_log('Session variables cleared');

        // Delete session file explicitly for Render.com compatibility
        $sessionSavePath = session_save_path();
        if ($sessionSavePath && strpos($sessionSavePath, ';') === false) {
            // session_save_path might be like /tmp or /tmp/php-sessions
            $sessionFile = $sessionSavePath . '/sess_' . $sessionId;
            if (file_exists($sessionFile)) {
                @unlink($sessionFile);
                error_log('Session file deleted: ' . $sessionFile);
            }
        }

        // Destroy the session
        @session_destroy();
        error_log('Session destroyed');

        // Delete the session cookie with proper attributes
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            $sessionName = session_name();

            error_log('Deleting session cookie: ' . $sessionName);

            // Set cookie to expire in the past with all original attributes
            $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            setcookie($sessionName, '', [
                'expires' => time() - 3600,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            // Also try the old cookie format for broader compatibility
            setcookie($sessionName, '', time() - 3600, '/', '', $isSecure, true);

            error_log('Session cookie deletion headers set');
        }

        return true;
    }
}

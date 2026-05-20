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
                throw new \Exception('Firebase service account not configured');
            }

            // Handle both file path (localhost) and base64-encoded JSON (Render)
            if (!file_exists($serviceAccountJson)) {
                // Try to decode if it's base64-encoded
                $decoded = base64_decode($serviceAccountJson, true);
                if ($decoded !== false) {
                    $serviceAccountJson = $decoded;
                }

                // Now validate JSON
                $parsed = json_decode($serviceAccountJson, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('FIREBASE_SERVICE_ACCOUNT_JSON is not valid JSON: ' . json_last_error_msg());
                }

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
                    $serviceAccountJson = $tempFile;
                } else {
                    throw new \Exception('Could not create temporary service account file and direct JSON not supported by kreait');
                }
            }

            try {
                error_log('Initializing Firebase Admin SDK with service account');
                $factory = new Factory();
                $firebase = $factory->withServiceAccount($serviceAccountJson);
                self::$auth = $firebase->createAuth();
                error_log('Firebase Admin SDK initialized successfully');
            } catch (\Exception $e) {
                error_log('Firebase Auth Error: ' . $e->getMessage());
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
            error_log('Token verification stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Get user from Firestore
     */
    public static function getUserFromFirestore($uid)
    {
        try {
            $doc = Firebase::firestore()->collection('users')->document($uid)->snapshot();

            if ($doc->exists()) {
                return $doc->data();
            }

            return null;
        } catch (\Exception $e) {
            error_log('Get user error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create/Update user in Firestore after first login
     */
    public static function createOrUpdateUser($uid, $email, $displayName = '')
    {
        try {
            $userData = [
                'email' => $email,
                'displayName' => $displayName,
                'updatedAt' => new \DateTime()
            ];

            $userDoc = Firebase::firestore()->collection('users')->document($uid);
            $snapshot = $userDoc->snapshot();

            if ($snapshot->exists()) {
                // Update existing
                $userDoc->update($userData);
            } else {
                // Create new
                $userData['isAdmin'] = false;
                $userData['createdAt'] = new \DateTime();
                $userDoc->set($userData);
            }

            return true;
        } catch (\Exception $e) {
            error_log('Create/update user error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is admin
     */
    public static function isUserAdmin($uid)
    {
        try {
            $user = self::getUserFromFirestore($uid);

            if ($user && isset($user['isAdmin'])) {
                return $user['isAdmin'] === true;
            }

            return false;
        } catch (\Exception $e) {
            error_log('Admin check error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Setup PHP session from verified token
     */
    public static function setupSession($uid, $email, $displayName)
    {
        try {
            // Verify user exists or create
            self::createOrUpdateUser($uid, $email, $displayName);

            // Get admin status
            $isAdmin = self::isUserAdmin($uid);

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
        session_destroy();
        return true;
    }
}

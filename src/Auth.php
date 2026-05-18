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

            if (!$serviceAccountJson || !file_exists($serviceAccountJson)) {
                throw new \Exception('Firebase service account not configured');
            }

            $factory = new Factory();
            $firebase = $factory->withServiceAccount($serviceAccountJson);
            self::$auth = $firebase->createAuth();
        }

        return self::$auth;
    }

    /**
     * Verify Firebase ID token and get user info
     */
    public static function verifyToken($idToken)
    {
        try {
            $auth = self::getAuthService();
            $verifiedToken = $auth->verifyIdToken($idToken);
            $uid = $verifiedToken->claims()->get('sub');

            return [
                'uid' => $uid,
                'email' => $verifiedToken->claims()->get('email'),
                'emailVerified' => $verifiedToken->claims()->get('email_verified'),
                'displayName' => $verifiedToken->claims()->get('name') ?? ''
            ];
        } catch (\Throwable $e) {
            error_log('Token verification failed: ' . $e->getMessage());
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

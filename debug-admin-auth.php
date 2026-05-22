<?php
/**
 * DEBUG SCRIPT: Verify Admin Authentication System
 * Usage: php debug-admin-auth.php
 *
 * This script tests the complete admin authentication flow
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/Firebase.php';
require_once __DIR__ . '/src/FirestoreRest.php';
require_once __DIR__ . '/src/Auth.php';

use App\Config;
use App\FirestoreRest;
use App\Auth;

// Initialize Config
Config::load();

echo "🔍 ADMIN AUTHENTICATION DEBUG SCRIPT\n";
echo "===================================\n\n";

// Test 1: Firestore Connection
echo "TEST 1: Firestore REST API Connection\n";
echo "--------------------------------------\n";

try {
    $projectId = Config::get('FIREBASE_PROJECT_ID');
    $serviceAccountJson = Config::get('FIREBASE_SERVICE_ACCOUNT_JSON');

    if (!$projectId || !$serviceAccountJson) {
        echo "❌ Firebase not configured\n";
        exit(1);
    }

    if (file_exists($serviceAccountJson)) {
        $serviceAccountJson = file_get_contents($serviceAccountJson);
    }

    $client = FirestoreRest::getInstance($projectId, $serviceAccountJson);
    echo "✅ Connected to Firestore REST API\n";
    echo "   Project ID: $projectId\n\n";
} catch (\Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Read Users from Firestore
echo "TEST 2: Reading Users from Firestore\n";
echo "------------------------------------\n";

try {
    $users = $client->getCollection('users');
    echo "✅ Found " . count($users) . " users\n\n";

    foreach ($users as $uid => $user) {
        $email = $user['email'] ?? 'unknown';
        $isAdmin = $user['isAdmin'] ?? 'MISSING';
        $adminStatus = $isAdmin === true ? '✅ ADMIN' : '⚪ USER';
        echo "   $adminStatus | $email (UID: " . substr($uid, 0, 8) . "...)\n";
    }
    echo "\n";
} catch (\Exception $e) {
    echo "❌ Failed to read users: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Verify test@example.com is Admin
echo "TEST 3: Verify test@example.com Admin Status\n";
echo "--------------------------------------------\n";

$testUser = null;
foreach ($users as $uid => $user) {
    if (($user['email'] ?? null) === 'test@example.com') {
        $testUser = $user;
        $testUid = $uid;
        break;
    }
}

if (!$testUser) {
    echo "❌ test@example.com not found in Firestore\n";
    exit(1);
}

$isAdmin = $testUser['isAdmin'] ?? false;
echo "✅ test@example.com found\n";
echo "   isAdmin field: " . var_export($isAdmin, true) . "\n";

if ($isAdmin === true || $isAdmin === 1 || $isAdmin === '1' || $isAdmin === 'true') {
    echo "   Status: ✅ IS ADMIN\n\n";
} else {
    echo "   Status: ❌ NOT ADMIN (PROBLEM!)\n\n";
    echo "   Expected: true (boolean)\n";
    echo "   Got: " . var_export($isAdmin, true) . " (" . gettype($isAdmin) . ")\n\n";
}

// Test 4: Test Auth::isUserAdmin() function
echo "TEST 4: Test Auth::isUserAdmin() Function\n";
echo "-----------------------------------------\n";

$result = Auth::isUserAdmin($testUid, 'test@example.com');
echo "Auth::isUserAdmin() returned: " . ($result ? "true" : "false") . "\n";

if ($result) {
    echo "✅ PASS: Admin check function works correctly\n\n";
} else {
    echo "❌ FAIL: Admin check function returned false\n\n";
}

// Test 5: Simulate Session Setup
echo "TEST 5: Simulate Session Setup\n";
echo "------------------------------\n";

// Start session for testing
session_start();

// Simulate createOrUpdateUser
echo "Calling createOrUpdateUser()...\n";
$updateResult = Auth::createOrUpdateUser($testUid, 'test@example.com', 'Test User');
echo "Result: " . ($updateResult ? "true" : "false") . "\n\n";

// Simulate setupSession
echo "Calling setupSession()...\n";
Auth::setupSession($testUid, 'test@example.com', 'Test User');

echo "Session variables set:\n";
echo "   \$_SESSION['userId']: " . ($_SESSION['userId'] ?? 'NOT SET') . "\n";
echo "   \$_SESSION['email']: " . ($_SESSION['email'] ?? 'NOT SET') . "\n";
echo "   \$_SESSION['isAdmin']: " . ($_SESSION['isAdmin'] ? 'true' : 'false') . "\n\n";

if ($_SESSION['isAdmin'] === true) {
    echo "✅ PASS: Session isAdmin is correctly set to true\n\n";
} else {
    echo "❌ FAIL: Session isAdmin is not true\n";
    echo "   Got: " . var_export($_SESSION['isAdmin'] ?? null, true) . "\n\n";
}

// Test 6: Test Auth::isAdmin() function (session check)
echo "TEST 6: Test Auth::isAdmin() Function (Session Check)\n";
echo "----------------------------------------------------\n";

$isAdminSessionCheck = Auth::isAdmin();
echo "Auth::isAdmin() returned: " . ($isAdminSessionCheck ? "true" : "false") . "\n\n";

if ($isAdminSessionCheck) {
    echo "✅ PASS: Session admin check works\n\n";
} else {
    echo "❌ FAIL: Session admin check failed\n\n";
}

// Final Summary
echo "═════════════════════════════════════════════════════\n";
echo "SUMMARY\n";
echo "═════════════════════════════════════════════════════\n\n";

$allPass = (
    $isAdmin === true &&
    $result === true &&
    $updateResult === true &&
    $_SESSION['isAdmin'] === true &&
    $isAdminSessionCheck === true
);

if ($allPass) {
    echo "✅ ALL TESTS PASSED - Admin authentication is working correctly!\n";
    echo "\nYou can now:\n";
    echo "  1. Login as test@example.com\n";
    echo "  2. Access /admin panel\n";
    echo "  3. Create/edit news, blog posts, and products\n";
} else {
    echo "❌ SOME TESTS FAILED - Debug the issues above\n";
}

echo "\n";
?>

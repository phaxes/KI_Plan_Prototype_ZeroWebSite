<?php
// Start session BEFORE any output
session_start();

require_once __DIR__ . '/vendor/autoload.php';

use App\Config;
use App\Auth;

Config::load();

$testUid = 'Cx2gaIuFwVSkHHrvpjPMdlx6ao42';
$testEmail = 'test@example.com';

echo "=== Admin Check Test ===\n\n";

echo "Test UID: $testUid\n";
echo "Test Email: $testEmail\n\n";

// Test 1: Get user from Firestore
echo "Test 1: Getting user from Firestore...\n";
try {
    $user = Auth::getUserFromFirestore($testUid);

    if ($user) {
        echo "✓ User found\n";
        echo "User data:\n";
        echo json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
    } else {
        echo "✗ User not found\n\n";
    }
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Check admin status
echo "Test 2: Checking admin status...\n";
try {
    $isAdmin = Auth::isUserAdmin($testUid, $testEmail);
    echo "isUserAdmin() returned: " . ($isAdmin ? 'true' : 'false') . "\n\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Simulate session setup
echo "Test 3: Simulating session setup...\n";
try {
    Auth::setupSession($testUid, $testEmail, 'Test User');

    echo "Session variables after setupSession():\n";
    echo "  - userId: " . ($_SESSION['userId'] ?? 'NOT SET') . "\n";
    echo "  - email: " . ($_SESSION['email'] ?? 'NOT SET') . "\n";
    echo "  - isAdmin: " . ($_SESSION['isAdmin'] ? 'true' : 'false') . "\n";
    echo "  - displayName: " . ($_SESSION['displayName'] ?? 'NOT SET') . "\n\n";
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 4: Check session-based admin status
echo "Test 4: Checking Auth::isAdmin()...\n";
$isAdminFromSession = Auth::isAdmin();
echo "Auth::isAdmin() returned: " . ($isAdminFromSession ? 'true' : 'false') . "\n";

echo "\n✓ Test complete.\n";
?>

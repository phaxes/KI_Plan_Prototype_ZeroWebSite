#!/usr/bin/env php
<?php
/**
 * Debug Login Session
 *
 * Simulates a login and checks what happens to $_SESSION['isAdmin']
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Auth;
use App\Config;

Config::load();

echo "\n=== Debug Login Session ===\n";
echo "Simulating login process for test@example.com\n\n";

// Start session
session_name('PHPSESSID');
session_start();

$uid = 'Cx2gaIuFwVSkHHrvpjPMdlx6ao42'; // test@example.com UID
$email = 'test@example.com';
$displayName = 'Test User';

try {
    echo "Step 1: Checking Auth::isLoggedIn() initially...\n";
    echo "  isLoggedIn: " . (Auth::isLoggedIn() ? 'true' : 'false') . "\n";

    echo "\nStep 2: Calling Auth::setupSession($uid, $email, $displayName)...\n";
    Auth::setupSession($uid, $email, $displayName);

    echo "\nStep 3: Checking $_SESSION after setupSession...\n";
    echo "  \$_SESSION['userId']: " . ($_SESSION['userId'] ?? 'NOT SET') . "\n";
    echo "  \$_SESSION['email']: " . ($_SESSION['email'] ?? 'NOT SET') . "\n";
    echo "  \$_SESSION['displayName']: " . ($_SESSION['displayName'] ?? 'NOT SET') . "\n";
    echo "  \$_SESSION['isAdmin']: " . ($_SESSION['isAdmin'] ?? 'NOT SET') . " (type: " . gettype($_SESSION['isAdmin'] ?? null) . ")\n";

    echo "\nStep 4: Checking Auth::isLoggedIn()...\n";
    echo "  isLoggedIn: " . (Auth::isLoggedIn() ? 'true' : 'false') . "\n";

    echo "\nStep 5: Checking Auth::isAdmin()...\n";
    echo "  isAdmin: " . (Auth::isAdmin() ? 'true' : 'false') . "\n";

    echo "\nStep 6: Direct Firestore check of isUserAdmin()...\n";
    $isUserAdmin = Auth::isUserAdmin($uid);
    echo "  Auth::isUserAdmin(\$uid): " . ($isUserAdmin ? 'true' : 'false') . "\n";

    echo "\n=== Diagnosis ===\n";

    if (!$_SESSION['isAdmin'] && $isUserAdmin) {
        echo "❌ PROBLEM FOUND:\n";
        echo "   - Firestore says user IS admin\n";
        echo "   - But \$_SESSION['isAdmin'] is false\n";
        echo "   - This means setupSession() failed to read from Firestore\n";
    } elseif (!$isUserAdmin && $_SESSION['isAdmin']) {
        echo "❌ PROBLEM FOUND:\n";
        echo "   - \$_SESSION says user IS admin\n";
        echo "   - But Firestore says user is NOT admin\n";
        echo "   - The isAdmin field is probably not in Firestore\n";
    } elseif (!$isUserAdmin && !$_SESSION['isAdmin']) {
        echo "❌ PROBLEM CONFIRMED:\n";
        echo "   - Both Firestore and Session say: NOT ADMIN\n";
        echo "   - The isAdmin field does not exist in Firestore for this user\n";
        echo "   - OR the field is set to false\n";
    } else {
        echo "✓ Everything looks good\n";
    }

    echo "\n=== Solution ===\n";
    echo "Run this to fix:\n";
    echo "  node debug-admin.js test@example.com\n\n";

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}

session_destroy();

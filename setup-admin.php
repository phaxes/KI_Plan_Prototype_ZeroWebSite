#!/usr/bin/env php
<?php
/**
 * Admin Setup Script
 *
 * Creates or promotes test@example.com to admin
 *
 * Usage: php setup-admin.php [email] [password]
 *
 * Examples:
 *   php setup-admin.php
 *   php setup-admin.php admin@example.com mypassword
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Config;
use App\Auth;
use Kreait\Firebase\Auth as FirebaseAuth;
use Kreait\Firebase\Factory;

Config::load();

// Get email and password from command line or use defaults
$email = $argv[1] ?? 'test@example.com';
$password = $argv[2] ?? 'password123';

echo "\n=== Admin Setup Script ===\n";
echo "Email: $email\n";
echo "Password: $password\n\n";

try {
    // Initialize Firebase Admin SDK
    $auth = Auth::getAuthService();
    $firestore = \App\Firebase::firestore();

    echo "Step 1: Checking if user exists in Firebase Auth...\n";

    // Check if user exists
    try {
        $user = $auth->getUserByEmail($email);
        echo "✓ User found: {$user->email}\n";
        echo "  UID: {$user->uid}\n";
        $uid = $user->uid;
    } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
        echo "✗ User not found. Creating new user...\n";

        // Create user
        $userRecord = $auth->createUserWithEmailAndPassword($email, $password);
        $uid = $userRecord->uid;
        echo "✓ User created: $email\n";
        echo "  UID: $uid\n";
    }

    echo "\nStep 2: Updating Firestore user document...\n";

    // Update user document in Firestore
    $userDoc = $firestore->collection('users')->document($uid);
    $userDoc->set([
        'email' => $email,
        'displayName' => 'Admin User',
        'isAdmin' => true,
        'updatedAt' => new \DateTime()
    ], ['merge' => true]);

    echo "✓ User document updated\n";

    // Verify the update
    $snapshot = $userDoc->snapshot();
    if ($snapshot->exists()) {
        $data = $snapshot->data();
        echo "\n=== User Data ===\n";
        echo "Email: " . ($data['email'] ?? 'N/A') . "\n";
        echo "Display Name: " . ($data['displayName'] ?? 'N/A') . "\n";
        echo "Is Admin: " . (($data['isAdmin'] ?? false) ? 'YES ✓' : 'NO ✗') . "\n";
        echo "Created At: " . (isset($data['createdAt']) ? $data['createdAt']->format('Y-m-d H:i:s') : 'N/A') . "\n";
        echo "Updated At: " . (isset($data['updatedAt']) ? $data['updatedAt']->format('Y-m-d H:i:s') : 'N/A') . "\n";
    }

    echo "\n✓ Admin setup completed successfully!\n";
    echo "\nYou can now login with:\n";
    echo "  Email: $email\n";
    echo "  Password: $password\n";
    echo "\nAfter login, you will have access to:\n";
    echo "  - /admin (Admin Dashboard)\n";
    echo "  - /admin/news (News Management)\n";
    echo "  - /admin/blog (Blog Management)\n";
    echo "  - /admin/products (Product Management)\n";
    echo "  - /admin/subscribers (Subscriber Management)\n";
    echo "\n";

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

#!/usr/bin/env php
<?php
/**
 * Fix Admin Status via PHP
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Config;
use App\Firebase;

Config::load();

$uid = 'Cx2gaIuFwVSkHHrvpjPMdlx6ao42'; // test@example.com UID
$email = 'test@example.com';

echo "\n=== Firestore Admin Fix (PHP) ===\n";
echo "UID: $uid\n";
echo "Email: $email\n\n";

try {
    echo "Step 1: Checking current Firestore document...\n";

    $firestore = Firebase::firestore();
    $userRef = $firestore->collection('users')->document($uid);
    $snapshot = $userRef->snapshot();

    if ($snapshot->exists()) {
        $data = $snapshot->data();
        echo "✓ Document found\n";
        echo "  Current isAdmin: " . ($data['isAdmin'] ?? 'NOT SET') . "\n";
    } else {
        echo "✗ Document does not exist\n";
        echo "\nStep 1b: Creating document...\n";
        $userRef->set([
            'email' => $email,
            'displayName' => 'Admin User',
            'isAdmin' => true,
            'createdAt' => new \DateTime(),
            'updatedAt' => new \DateTime()
        ]);
        echo "✓ Document created with isAdmin = true\n";
    }

    echo "\nStep 2: Setting isAdmin = true...\n";

    $userRef->set([
        'isAdmin' => true,
        'updatedAt' => new \DateTime()
    ], ['merge' => true]);

    echo "✓ isAdmin set to true\n";

    echo "\nStep 3: Verifying...\n";
    $verify = $userRef->snapshot();
    if ($verify->exists()) {
        $verifyData = $verify->data();
        echo "✓ Verified - isAdmin = " . ($verifyData['isAdmin'] ? 'TRUE' : 'FALSE') . "\n";
    }

    echo "\n=== Fix Complete ===\n";
    echo "\nNow do this:\n";
    echo "1. Go to https://zero-cost-website.onrender.com/logout\n";
    echo "2. Logout\n";
    echo "3. Go to https://zero-cost-website.onrender.com/login\n";
    echo "4. Login with test@example.com / password123\n";
    echo "5. Go to https://zero-cost-website.onrender.com/admin\n";
    echo "6. Should work now! ✓\n\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
    exit(1);
}

<?php
/**
 * CLI Script: Migrate isAdmin field to all users
 * Usage: php cli-admin-setup.php
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/FirestoreRest.php';

use App\Config;
use App\FirestoreRest;

// Initialize Config
Config::load();

$projectId = Config::get('FIREBASE_PROJECT_ID');
$serviceAccountJson = Config::get('FIREBASE_SERVICE_ACCOUNT_JSON');

if (!$projectId || !$serviceAccountJson) {
    echo "❌ Error: Firebase not configured (.env missing FIREBASE_PROJECT_ID or FIREBASE_SERVICE_ACCOUNT_JSON)\n";
    exit(1);
}

// Handle file path
if (file_exists($serviceAccountJson)) {
    $serviceAccountJson = file_get_contents($serviceAccountJson);
}

try {
    $client = FirestoreRest::getInstance($projectId, $serviceAccountJson);

    echo "🔄 Starting user migration...\n";

    // Get all users from Firestore
    $users = $client->getCollection('users');

    if (empty($users)) {
        echo "ℹ️  No users found in Firestore.\n";
        exit(0);
    }

    echo "📋 Found " . count($users) . " users\n\n";

    $updated = 0;
    $skipped = 0;

    foreach ($users as $uid => $userData) {
        $email = $userData['email'] ?? 'unknown';
        $currentIsAdmin = $userData['isAdmin'] ?? 'MISSING';

        // Determine new isAdmin value
        $newIsAdmin = ($email === 'test@example.com') ? true : false;

        // Only update if field is missing or different
        if ($currentIsAdmin === 'MISSING' || $currentIsAdmin !== $newIsAdmin) {
            $userData['isAdmin'] = $newIsAdmin;
            $client->setDocument('users', $uid, $userData);
            echo "✅ Updated: $email (isAdmin: " . ($newIsAdmin ? 'true' : 'false') . ")\n";
            $updated++;
        } else {
            echo "⏭️  Skipped: $email (already isAdmin: " . ($currentIsAdmin ? 'true' : 'false') . ")\n";
            $skipped++;
        }
    }

    echo "\n";
    echo "✨ Migration complete!\n";
    echo "   Updated: $updated users\n";
    echo "   Skipped: $skipped users\n";

    if ($updated > 0) {
        echo "\n🎉 Admin setup done:\n";
        echo "   - test@example.com is now an admin (isAdmin: true)\n";
        echo "   - All other users have isAdmin: false\n";
        echo "\n💡 Users need to re-login for changes to take effect.\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

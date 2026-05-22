<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Config.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use App\Config;

Config::load();

echo "=== Firebase Initialization Debug ===\n\n";

$serviceAccountJson = Config::get('FIREBASE_SERVICE_ACCOUNT_JSON');
echo "Service Account Path: " . $serviceAccountJson . "\n";
echo "File Exists: " . (file_exists($serviceAccountJson) ? "YES" : "NO") . "\n\n";

if (!file_exists($serviceAccountJson)) {
    echo "ERROR: Service account file not found!\n";
    exit(1);
}

echo "File Content (first 200 chars):\n";
echo substr(file_get_contents($serviceAccountJson), 0, 200) . "...\n\n";

try {
    echo "Creating Firebase factory...\n";
    $factory = new Factory();

    echo "Loading service account...\n";
    $firebase = $factory->withServiceAccount($serviceAccountJson);

    echo "SUCCESS: Firebase initialized\n\n";

    echo "Creating Firestore client...\n";
    $firestore = $firebase->createFirestore();

    echo "SUCCESS: Firestore client created\n\n";

    // Try to access posts collection
    echo "Testing collection access...\n";
    $collection = $firestore->collection('posts');
    echo "SUCCESS: Posts collection accessible\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== End Debug ===\n";
?>

<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Config;
use App\Firebase;

echo "=== Firebase Initialization Debug ===\n\n";

// Load config
Config::load();

// Check if FIREBASE_SERVICE_ACCOUNT_JSON is set
$serviceAccountJson = Config::get('FIREBASE_SERVICE_ACCOUNT_JSON');

echo "1. Config Check:\n";
echo "   FIREBASE_SERVICE_ACCOUNT_JSON set: " . ($serviceAccountJson ? 'YES' : 'NO') . "\n";

if (!$serviceAccountJson) {
    echo "   ❌ FIREBASE_SERVICE_ACCOUNT_JSON not configured in .env\n";
    exit(1);
}

if ($serviceAccountJson === '/dev/null') {
    echo "   ❌ FIREBASE_SERVICE_ACCOUNT_JSON is /dev/null (disabled)\n";
    exit(1);
}

// Check if it's a file path or JSON string
if (file_exists($serviceAccountJson)) {
    echo "   ✓ Using local file: $serviceAccountJson\n";
    $content = file_get_contents($serviceAccountJson);
    echo "   File size: " . strlen($content) . " bytes\n";
} else {
    echo "   Not a file path, treating as JSON/base64...\n";

    // Try base64 decode
    $decoded = base64_decode($serviceAccountJson, true);
    if ($decoded !== false && strlen($decoded) > 100) {
        echo "   ✓ Successfully decoded as base64\n";
        echo "   Decoded size: " . strlen($decoded) . " bytes\n";
        $content = $decoded;
    } else {
        echo "   Trying to parse as raw JSON...\n";
        $content = $serviceAccountJson;
    }
}

// Validate JSON
echo "\n2. JSON Validation:\n";
$parsed = json_decode($content, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "   ❌ Invalid JSON: " . json_last_error_msg() . "\n";
    exit(1);
}

echo "   ✓ Valid JSON\n";
echo "   Keys: " . implode(', ', array_keys($parsed)) . "\n";

// Check required fields
echo "\n3. Required Fields Check:\n";
$required = ['type', 'project_id', 'private_key', 'client_email'];
foreach ($required as $field) {
    $exists = isset($parsed[$field]) && !empty($parsed[$field]);
    echo "   " . ($exists ? '✓' : '❌') . " $field: " . ($exists ? 'OK' : 'MISSING') . "\n";
}

// Test Firebase initialization
echo "\n4. Firebase Initialization Test:\n";
try {
    // Enable detailed error reporting
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    $firebase = Firebase::getInstance();

    if ($firebase === null) {
        echo "   ❌ Firebase::getInstance() returned null\n";
        echo "   Check PHP error logs for details\n";
        exit(1);
    }

    echo "   ✓ Firebase instance created\n";

    // Test Firestore
    echo "\n5. Firestore Test:\n";
    $firestore = Firebase::firestore();

    if ($firestore === null) {
        echo "   ❌ Firebase::firestore() returned null\n";
        echo "   Check PHP error logs for details\n";
        exit(1);
    }

    echo "   ✓ Firestore initialized\n";

    // Try to read a test document
    echo "\n6. Document Read Test:\n";
    $testUid = 'Cx2gaIuFwVSkHHrvpjPMdlx6ao42';
    $doc = $firestore->collection('users')->document($testUid)->snapshot();

    if ($doc->exists()) {
        echo "   ✓ Document found\n";
        $data = $doc->data();
        echo "   isAdmin value: " . var_export($data['isAdmin'] ?? 'NOT SET', true) . "\n";
        echo "   isAdmin === true: " . (($data['isAdmin'] ?? null) === true ? 'YES' : 'NO') . "\n";
        echo "   isAdmin == true: " . (($data['isAdmin'] ?? null) == true ? 'YES' : 'NO') . "\n";
    } else {
        echo "   ❌ Document not found\n";
    }

} catch (\Throwable $e) {
    echo "   ❌ Exception: " . $e->getMessage() . "\n";
    echo "   Trace:\n";
    foreach (explode("\n", $e->getTraceAsString()) as $line) {
        echo "      " . $line . "\n";
    }
    exit(1);
}

echo "\n✓ All tests passed!\n";
?>

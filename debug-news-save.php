<?php
// Debug script to test news saving
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/Firebase.php';

use App\Firebase;
use App\Config;

Config::load();

echo "=== News Save Debug ===\n\n";

// Test 1: Check Firebase availability
echo "1. Firebase Availability:\n";
$isAvailable = Firebase::isAvailable();
echo "   Result: " . ($isAvailable ? "TRUE" : "FALSE") . "\n\n";

if (!$isAvailable) {
    echo "   ERROR: Firebase is not available!\n";
    echo "   Checking configuration...\n";
    echo "   FIREBASE_PROJECT_ID: " . Config::get('FIREBASE_PROJECT_ID') . "\n";
    echo "   FIREBASE_SERVICE_ACCOUNT_JSON: " . (file_exists(Config::get('FIREBASE_SERVICE_ACCOUNT_JSON')) ? 'FILE EXISTS' : 'FILE NOT FOUND') . "\n";
    exit(1);
}

// Test 2: Try to create a test post
echo "2. Creating Test News Post:\n";
$testData = [
    'title' => 'Test Post ' . date('Y-m-d H:i:s'),
    'content' => 'This is a test post for debugging',
    'type' => 'news',
];

$result = Firebase::createPost($testData);
echo "   Result: " . ($result ? "SUCCESS (ID: $result)" : "FAILED") . "\n\n";

// Test 3: Try to retrieve all news
echo "3. Retrieving All News Posts:\n";
$news = Firebase::getPosts('news');
echo "   Found: " . count($news) . " news posts\n";
foreach ($news as $post) {
    echo "   - " . $post['title'] . " (ID: " . $post['id'] . ")\n";
}

echo "\n=== End Debug ===\n";
?>
